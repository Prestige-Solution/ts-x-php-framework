<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Adapter;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Profiler;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP;

class MockServerQuery extends ServerQuery
{
    /**
     * Connects the Transport object and performs initial actions on the remote
     * server.
     *
     * @return void
     * @throws AdapterException
     * @throws TransportException
     */
    protected function syn(): void
    {
        $this->initTransport($this->options, MockTCP::class);
        $this->transport->setAdapter($this);

        Profiler::init(spl_object_hash($this));

        $rdy = $this->getTransport()->readLine();
        $rdy = StringHelper::factory($rdy ?? '');

        if (! $rdy->startsWith(TeamSpeak3::TS3_PROTO_IDENT) && ! $rdy->startsWith(TeamSpeak3::TEA_PROTO_IDENT)) {
            throw new AdapterException('invalid reply from the server ('.$rdy.')');
        }

        Signal::getInstance()->emit('serverqueryConnected', $this);
    }

    /**
     * Return the underlying MockTCP transport.
     */
    public function getMockTransport(): MockTCP
    {
        /** @var MockTCP $transport */
        $transport = $this->getTransport();

        return $transport;
    }

    /**
     * Register a custom response on the mock transport.
     */
    public function registerResponse(string $cmd, string $reply): self
    {
        $this->getMockTransport()->registerResponse($cmd, $reply);

        return $this;
    }

    /**
     * Queue a response on the mock transport.
     */
    public function queueResponse(string $reply): self
    {
        $this->getMockTransport()->queueResponse($reply);

        return $this;
    }

    /**
     * Set a custom dynamic command handler.
     */
    public function setCommandHandler(callable $handler): self
    {
        $this->getMockTransport()->setCommandHandler($handler);

        return $this;
    }

    /**
     * Get sent commands.
     */
    public function getSentCommands(): array
    {
        return $this->getMockTransport()->getSentCommands();
    }

    /**
     * Get the last sent command.
     */
    public function getLastCommand(): ?string
    {
        return $this->getMockTransport()->getLastCommand();
    }

    /**
     * Check if a command was sent.
     */
    public function hasSentCommand(string $command): bool
    {
        return $this->getMockTransport()->hasSentCommand($command);
    }
}
