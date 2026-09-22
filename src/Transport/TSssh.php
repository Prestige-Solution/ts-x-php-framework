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

        if (! empty($this->config['fingerprint'])) {
            $this->verifyFingerprint($this->ssh->getServerPublicHostKey(), (string) $this->config['fingerprint']);
        }

        // activate non-blocking mode
        if (isset($this->config['blocking']) && $this->config['blocking'] === 0) {
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
     * Verifies the server host key against the expected fingerprint.
     *
     * @param mixed $serverHostKey
     * @param string $expectedFingerprint
     * @return bool
     * @throws TransportException
     */
    public function verifyFingerprint(mixed $serverHostKey, string $expectedFingerprint): bool
    {
        if ($serverHostKey === false || empty($serverHostKey)) {
            throw new TransportException('Host key verification failed: The servers host key could not be verified.');
        }

        // phpseclib3 returns "<format> <base64_blob>" (e.g. "rsa-sha2-512 AAAAB3NzaC1...") or raw key string
        $keyParts = explode(' ', trim((string) $serverHostKey));
        $keyBlobBase64 = count($keyParts) > 1 ? $keyParts[1] : $keyParts[0];
        $rawKeyBlob = base64_decode($keyBlobBase64, true) ?: (string) $serverHostKey;

        // 1. OpenSSH SHA256-Fingerprint (Base64 with and without padding)
        $rawSha256 = hash('sha256', $rawKeyBlob, true);
        $b64Fingerprint = base64_encode($rawSha256);
        $b64Unpadded = rtrim($b64Fingerprint, '=');
        $openSshFingerprint = 'SHA256:'.$b64Fingerprint;
        $openSshUnpadded = 'SHA256:'.$b64Unpadded;

        // 2. Hex SHA256-Fingerprint
        $hexFingerprint = hash('sha256', $rawKeyBlob);

        // 3. Normalized expected
        $expected = trim($expectedFingerprint);
        $expectedFixedPlus = str_replace(' ', '+', $expected);

        // 4. Tolerant comparison against all standard formats
        $isValid = hash_equals($openSshFingerprint, $expected)
            || hash_equals($openSshUnpadded, $expected)
            || hash_equals($b64Fingerprint, $expected)
            || hash_equals($b64Unpadded, $expected)
            || hash_equals($hexFingerprint, strtolower($expected))
            || hash_equals('sha256:'.$hexFingerprint, strtolower($expected))
            || hash_equals($openSshFingerprint, $expectedFixedPlus)
            || hash_equals($openSshUnpadded, $expectedFixedPlus)
            || hash_equals($b64Fingerprint, $expectedFixedPlus)
            || hash_equals($b64Unpadded, $expectedFixedPlus);

        if (! $isValid) {
            throw new TransportException('Hostkey verification failed: The expected fingerprint does not match the server fingerprint!');
        }

        return true;
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
            '/\x1B][^\x07]*\x07/',        // OSC ... BEL
            '/\x1B\[?=\d*[A-Za-z]/',        // fallback for exotic forms (optional)
        ], '', $data);

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

                usleep(20000);
                continue;
            }

            Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataRead', $data);

            $line .= $data;
        }

        //Cleanup Prompt
        $line = trim($line, "\0\t\n\r\x0B");
        // Remove immediately, e.g., “ts-bot-dev@9987(1):online>”
        $line = preg_replace('/^[A-Za-z0-9\-_@()]+:[^\s>]*>\s*/', '', $line);

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
    protected function waitForReadyRead(int $time = 5): void
    {
        if (! $this->isConnected()) {
            return;
        }

        $start = time();
        while ((time() - $start) < $time) {
            $data = $this->ssh->read();
            if ($data !== false && $data !== '') {
                return;
            }
            usleep(100_000); // 100 ms break
        }
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
