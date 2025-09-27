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
     * Verbindung aufbauen
     * @throws TransportException
     */
    public function connect(): void
    {
        $this->ssh = new SSH2($this->config['host'], $this->config['port']);

        if (!$this->ssh->login($this->config['username'], $this->config['password'])) {
            throw new TransportException('Login fehlgeschlagen: falscher Benutzername oder Passwort');
        }
    }

    /**
     * Prüfen, ob verbunden
     */
    public function isConnected(): bool
    {
        return $this->ssh instanceof SSH2 && $this->ssh->isConnected();
    }

    /**
     * Lese Daten
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
     * Lese Zeile
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
     * Schreibe Daten
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
     * Schreibe Daten mit Zeilenumbruch
     * @throws TransportException
     */
    public function sendLine(string $data, string $separator = "\n"): void
    {
        $this->send($data . $separator);
    }

    /**
     * Warten, bis Daten verfügbar sind
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

        echo "⏳ Timeout: keine Daten innerhalb von {$time}s" . PHP_EOL;
    }

    /**
     * Verbindung schließen
     */
    public function disconnect(string $leaveMessage = "job successfully"): void
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
