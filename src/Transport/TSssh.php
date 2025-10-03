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
            'hostkey' => ['rsa-sha2-512','rsa-sha2-256','ssh-rsa'],
        ]);

        if (!$this->ssh->login($this->config['username'], $this->config['password'])) {
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
        if (!$this->isConnected()) {
            $this->connect();
        }

        $data = $this->ssh->read($length);

        Signal::getInstance()->emit(strtolower($this->getAdapterType()) . 'DataRead', $data);

        if ($data === false) {
            throw new TransportException("Connection to server '{$this->config['host']}:{$this->config['port']}' lost");
        }

        return new StringHelper($data);
    }

    /**
     * Read line
     * @throws TransportException
     */
    public function readLine(string $token = "\n"): StringHelper
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $line = '';

        while (!str_ends_with($line, $token)) {
            $data = $this->ssh->read($token);

            if ($data === false) {
                break; // Timeout or connection lost
            }

            Signal::getInstance()->emit(strtolower($this->getAdapterType()) . 'DataRead', $data);

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
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->ssh->write($data);

        Signal::getInstance()->emit(strtolower($this->getAdapterType()) . 'DataSend', $data);
    }

    /**
     * Write data with line breaks
     * @throws TransportException
     */
    public function sendLine(string $data, string $separator = "\n"): void
    {
        $this->send($data . $separator);
    }

    /**
     * Wait until data is available
     */
    public function waitForReadyRead(int $time = 5): void
    {
        if (!$this->isConnected()) {
            return;
        }

        $start = time();
        while ((time() - $start) < $time) {
            $data = $this->ssh->read();
            if ($data !== false && $data !== '') {
                echo "📩 Neue Daten: " . $data . PHP_EOL;
                return;
            }
            usleep(100_000); // 100 ms break
        }

        echo "⏳ Timeout: no data within {$time}s" . PHP_EOL;
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
