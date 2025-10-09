<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Transport;

use phpseclib3\Net\SSH2;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;

class TSssh extends Transport
{
    protected ?SSH2 $ssh = null;

    /**
     * Establish connection
     * @throws TransportException
     */
    public function connect(): void
    {
        $this->ssh = new SSH2($this->config['host'], $this->config['port']);

        $this->ssh->setPreferredAlgorithms([
            'hostkey' => ['rsa-sha2-512', 'rsa-sha2-256', 'ssh-rsa'],
        ]);

        // activate non-blocking mode
        if(isset($this->config['blocking']) && $this->config['blocking'] === 0) {
            $this->stream = $this->ssh->fsock ?? null;
            if (is_resource($this->stream)) {
                stream_set_blocking($this->stream, false);
            }
        }

        if (! $this->ssh->login($this->config['username'], $this->config['password'])) {
            throw new TransportException('Login failed: incorrect username or password');
        }
    }

    /**
     * Check whether connected
     */
    public function isConnected(): bool
    {
        return $this->ssh instanceof SSH2 && $this->ssh->isConnected();
    }

    /**
     * Read data
     * @throws TransportException
     */
    public function read(int $length = 4096): StringHelper
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $data = $this->ssh->read($length);

        if ($data === false || $data === '' || $data === null) {
            return new StringHelper('');
        }

        // Remove ANSI/CSI/OSC control sequences (robust patterns)
        // CSI: ESC [ ... @-~  (z.B. ESC[31m, ESC[47G)
        // OSC: ESC ] ... BEL (BEL = \x07)
        // individual ESC sequences: ESC followed by any char
        $data = preg_replace([
            '/\x1B\[[0-?]*[ -\/]*[@-~]/',   // CSI sequences
            '/\x1B\][^\x07]*\x07/',        // OSC ... BEL
            '/\x1B\[?=\d*[A-Za-z]/',        // fallback for exotic forms (optional)
        ], '', $data);

        // Remove unnecessary whitespace/CR/LF at the beginning/end
        $data = trim($data, "\0\t\n\r\x0B");

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataRead', $data);

        return new StringHelper($data);
    }

    /**
     * Read line
     * @throws TransportException
     */
    public function readLine(int $timeout = 1, string $token = "\n"): ?StringHelper
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $line = '';
        $start = time();

        while (! str_ends_with($line, $token)) {
            $data = $this->ssh->read($token);

            if ($data === false) {
                return null; // Timeout or connection lost
            }

            if ($data === '') {
                // wait a bit and check timeout
                if ((time() - $start) >= $timeout) {
                    return null; // no data within timeout
                }

                usleep(50_000); // 50 ms pause before next read
                continue;
            }

            Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataRead', $data);

            $line .= $data;
        }

        return StringHelper::factory($line)->trim();
    }

    /**
     * Write data
     * @throws TransportException
     */
    public function send(string $data): void
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->ssh->write($data);

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataSend', $data);
    }

    /**
     * Write data with line breaks
     * @throws TransportException
     */
    public function sendLine(string $data, string $separator = "\n"): void
    {
        $this->send($data.$separator);
    }

    /**
     * Wait until data is available
     */
    public function waitForReadyRead(int $time = 5): void
    {
        if (! $this->isConnected()) {
            return;
        }

        $start = time();
        while ((time() - $start) < $time) {
            $data = $this->ssh->read();
            if ($data !== false && $data !== '') {
                echo '📩 Neue Daten: '.$data.PHP_EOL;

                return;
            }
            usleep(100_000); // 100 ms break
        }

        echo "⏳ Timeout: no data within {$time}s".PHP_EOL;
    }

    /**
     * Close connection
     */
    public function disconnect(): void
    {
        if ($this->ssh !== null) {
            try {
                // Send quit command
                $this->ssh->write("quit\n");

                usleep(100_000); // Please wait a moment while the server processes the message.
            } catch (\Exception) {
                // Log or ignore errors
            }

            $this->ssh->disconnect();
        }

        $this->ssh = null;
    }
}
