<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Transport;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;

class TCP extends Transport
{
    /**
     * Connects to a remote server.
     *
     * @throws TransportException
     */
    public function connect(): void
    {
        if ($this->stream !== null) {
            return;
        }

        $host = strval($this->config['host']);
        $port = strval($this->config['port']);

        $address = 'tcp://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port;
        $timeout = (int) $this->config['timeout'];

        $this->stream = @stream_socket_client($address, $errno, $errstr, $timeout);

        if ($this->stream === false) {
            $this->stream = null;

            throw new TransportException(StringHelper::factory($errstr)->toUtf8()->toString(), $errno);
        }

        stream_set_timeout($this->stream, $timeout);
        stream_set_blocking($this->stream, $this->config['blocking'] ? 1 : 0);
    }

    /**
     * Disconnects from a remote server.
     */
    public function disconnect(): void
    {
        if ($this->stream === null) {
            return;
        }

        fclose($this->stream);
        $this->stream = null;

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'Disconnected');
    }

    /**
     * Reads data from the stream.
     *
     * @throws TransportException
     */
    public function read(int $length = 4096): StringHelper
    {
        $this->connect();
        $this->waitForReadyRead();

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new TransportException("connection to server '".$this->config['host'].':'.$this->config['port']."' lost");
        }

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataRead', $data);

        return new StringHelper($data);
    }

    /**
     * Writes data to the stream.
     *
     * @throws TransportException
     */
    public function send(string $data): void
    {
        $this->connect();

        $written = fwrite($this->stream, $data);

        if ($written === false) {
            throw new TransportException("connection to server '".$this->config['host'].':'.$this->config['port']."' lost");
        }

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataSend', $data);
    }
}
