<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Adapter;

use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Event;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Reply;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Profiler;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\Transport;

class ServerQuery extends Adapter
{
    protected ?Host $host = null;

    protected ?int $timer = null;

    protected int $count = 0;

    protected array $block = ['help'];

    /**
     * Initialize the Transport and check server response
     *
     * @throws AdapterException
     * @throws TransportException
     */
    protected function syn(): void
    {
        $this->initTransport($this->options);
        $this->transport->setAdapter($this);

        Profiler::init(spl_object_hash($this));

        $rdy = $this->getTransport()?->readLine();
        $rdy = StringHelper::factory($rdy ?? '');

        if (! $rdy->startsWith(TeamSpeak3::TS3_PROTO_IDENT) &&
            ! $rdy->startsWith(TeamSpeak3::TEA_PROTO_IDENT)) {
            throw new AdapterException('invalid reply from the server ('.$rdy.')');
        }

        Signal::getInstance()->emit('serverqueryConnected', $this);
    }

    /**
     * @throws TransportException
     */
    public function __destruct()
    {
        $transport = $this->getTransport();
        if (! $transport?->getConfig('blocking')) {
            return;
        }

        if ($transport instanceof Transport && $transport->isConnected()) {
            try {
                $this->request('quit');
            } catch (AdapterException) {
                return;
            }
        }
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function request(string $cmd, bool $throw = true): Reply
    {
        $query = StringHelper::factory($cmd)->section(TeamSpeak3::SEPARATOR_CELL);

        if (str_contains($cmd, "\r") || str_contains($cmd, "\n")) {
            throw new AdapterException("illegal characters in command '".$query."'");
        } elseif (in_array($query, $this->block, true)) {
            throw new ServerQueryException('command not found', 0x100);
        }

        Signal::getInstance()->emit('serverqueryCommandStarted', $cmd);

        $this->getProfiler()?->start();
        $this->getTransport()?->sendLine($cmd);
        $this->timer = time();
        $this->count++;

        $rpl = [];
        $transport = $this->getTransport();

        do {
            if (! $transport?->isConnected()) {
                break;
            }

            $str = $transport->readLine();
            $str = StringHelper::factory($str);

            $rpl[] = $str;
        } while ($str->section(TeamSpeak3::SEPARATOR_CELL) != TeamSpeak3::ERROR);

        $this->getProfiler()?->stop();

        $reply = new Reply($rpl, $cmd, $this->getHost(), $throw);

        Signal::getInstance()->emit('serverqueryCommandFinished', $cmd, $reply);

        return $reply;
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function wait(int $timeout = 5): ?Event
    {
        if ($this->getTransport()?->getConfig('blocking')) {
            throw new AdapterException('only available in non-blocking mode');
        }

        $transport = $this->getTransport();

        if (! $transport?->isConnected()) {
            throw new \phpseclib3\Exception\ConnectionClosedException('Connection closed by server');
        }

        // Attempt: read one line within timeout
        $line = $transport->readLine($timeout);

        if ($line === null) {
            // Timeout → No event, but connection is still active
            $idle_seconds = time() - $this->getQueryLastTimestamp();
            Signal::getInstance()->emit('serverqueryWaitTimeout', [$idle_seconds, $this]);

            // no event, so zero return
            return null;
        }

        // Parse event data
        $evt = StringHelper::factory($line);

        if ($evt->section(TeamSpeak3::SEPARATOR_CELL)->startsWith(TeamSpeak3::EVENT)) {
            return new Event($evt, $this->getHost());
        }

        return null;
    }

    public function prepare(string $cmd, array $params = []): string
    {
        $args = [];
        $cells = [];

        foreach ($params as $ident => $value) {
            $ident = is_numeric($ident) ? '' : strtolower($ident).TeamSpeak3::SEPARATOR_PAIR;

            if (is_array($value)) {
                $value = array_values($value);
                foreach ($value as $i => $v) {
                    if ($v === null) {
                        continue;
                    }
                    if ($v === false) {
                        $v = 0x00;
                    }
                    if ($v === true) {
                        $v = 0x01;
                    }
                    if ($v instanceof Node) {
                        $v = $v->getId();
                    }

                    $cells[$i][] = $ident.StringHelper::factory($v)->toUtf8()->escape()->toString();
                }
            } else {
                if ($value === null) {
                    continue;
                }
                if ($value === false) {
                    $value = 0x00;
                }
                if ($value === true) {
                    $value = 0x01;
                }
                if ($value instanceof Node) {
                    $value = $value->getId();
                }

                $args[] = $ident.StringHelper::factory($value)->toUtf8()->escape()->toString();
            }
        }

        foreach (array_keys($cells) as $i) {
            $cells[$i] = implode(TeamSpeak3::SEPARATOR_CELL, $cells[$i]);
        }

        if (! empty($args)) {
            $cmd .= ' '.implode(TeamSpeak3::SEPARATOR_CELL, $args);
        }

        if (! empty($cells)) {
            $cmd .= ' '.implode(TeamSpeak3::SEPARATOR_LIST, $cells);
        }

        return trim($cmd);
    }

    public function getQueryLastTimestamp(): ?int
    {
        return $this->timer;
    }

    public function getQueryCount(): int
    {
        return $this->count;
    }

    public function getQueryRuntime(): float
    {
        return $this->getProfiler()?->getRuntime() ?? 0.0;
    }

    public function getHost(): ?Host
    {
        if ($this->host === null) {
            $this->host = new Host($this);
        }

        return $this->host;
    }
}
