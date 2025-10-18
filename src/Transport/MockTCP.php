<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Transport;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;

class MockTCP extends TSssh
{
    public const S_WELCOME_L0 = 'TS3';

    public const S_WELCOME_L1 = 'Welcome to the TeamSpeak 3 ServerQuery interface, type "help" for a list of commands and "help <command>" for information on a specific command.';

    public const S_ERROR_OK = 'error id=0 msg=ok';

    public const CMD = [
        'login serveradmin secret' => self::S_ERROR_OK,
        'login client_login_name=serveradmin client_login_password=secret' => self::S_ERROR_OK,
    ];

    protected bool $connected = false;

    protected ?string $reply = null;

    protected array $buffer = [];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 10022,
            'blocking' => 0,
        ], $config);
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        // Simulated SSH connection
        $this->connected = true;
        $this->reply = sprintf("%s\n%s\n", self::S_WELCOME_L0, self::S_WELCOME_L1);
        $this->buffer = explode("\n", trim($this->reply));
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->reply = null;
        $this->buffer = [];
    }

    /**
     * Simulated reading of a line (SSH read)
     * @throws TransportException
     */
    public function readLine(int $timeout = 1, string $token = "\n"): StringHelper
    {
        $this->connect();
        $line = StringHelper::factory('');

        while (! $line->endsWith($token)) {
            // Read from SSH transport
            $data = $this->read(); // already returns StringHelper

            Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataRead', $data->toString());

            if ($data->count() === 0) {
                if ($line->count()) {
                    $line->append($token);
                } else {
                    throw new TransportException(
                        "Connection to server '{$this->config['host']}:{$this->config['port']}' lost"
                    );
                }
            } else {
                $line->append($data);
            }
        }

        return $line->trim();
    }

    /**
     * SSH-compatible “read” mock
     * @throws TransportException
     */
    public function read(int $length = 4096): StringHelper
    {
        if (! $this->connected) {
            $this->connect();
        }

        if (empty($this->reply)) {
            return StringHelper::factory('');
        }

        // Copy to a local variable instead of overwriting property
        $lines = explode("\n", $this->reply);
        $data = array_shift($lines);
        $this->reply = implode("\n", $lines);

        $data = substr($data, 0, $length);

        return StringHelper::factory($data."\n");
    }

    /**
     * SSH-kompatibles „write“ Mock
     * @throws TransportException
     */
    public function write(string $data): void
    {
        if (! $this->connected) {
            throw new TransportException('Not connected to mock server');
        }

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataSend', $data);

        // Simulate response
        if (isset(self::CMD[$data])) {
            $this->reply = self::CMD[$data]."\n".self::S_ERROR_OK."\n";
        } else {
            $this->reply = "error id=1 msg=unknown_command\n";
        }

        $this->buffer = explode("\n", trim($this->reply));
    }

    public function sendLine(string $data, string $separator = "\n"): void
    {
        $this->write($data);
    }

    public function getAdapterType(): string
    {
        return 'ssh';
    }
}
