<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ConnectionTest extends TestCase
{
    /**
     * ATTENTION
     * Use the .env.testing Variable "DEV_LIVE_SERVER_AVAILABLE" to activate this Test
     * Use this Testcase only with a development Teamspeak Server
     * Otherwise the TS3 Server can be destroyed
     */
    private string $active;

    private string $host;

    private string $queryPort;

    private string $user;

    private string $password;

    private string $ts3_server_uri;

    public function setUp(): void
    {
        //proof test active
        if (file_exists('./.env.testing')) {
            $env = file('./.env.testing');
            //get live server is available
            $this->active = str_replace('DEV_LIVE_SERVER_AVAILABLE=', '', preg_replace('#\n(?!\n)#', '', $env[2]));
            $this->host = str_replace('DEV_LIVE_SERVER_HOST=', '', preg_replace('#\n(?!\n)#', '', $env[3]));
            $this->queryPort = str_replace('DEV_LIVE_SERVER_QUERY_PORT=', '', preg_replace('#\n(?!\n)#', '', $env[4]));
            $this->user = str_replace('DEV_LIVE_SERVER_QUERY_USER=', '', preg_replace('#\n(?!\n)#', '', $env[5]));
            $this->password = str_replace('DEV_LIVE_SERVER_QUERY_USER_PASSWORD=', '', preg_replace('#\n(?!\n)#', '', $env[6]));
        } else {
            $this->active = 'false';
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port=9987'.
            '&no_query_clients=0'.
            '&blocking=0'.
            '&timeout=30';
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_ssh_connect()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);
        $nodeInfo = $ts3_Host->getInfo();
        $whoami = $ts3_Host->whoami();

        $ts3_Host->getAdapter()->getTransport()->disconnect();

        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
        $this->assertEquals($this->user, $whoami['client_nickname']);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_ssh_connect_with_nickname()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $testUri = $this->ts3_server_uri.'&nickname=UnitTestBot';

        $ts3_Host = TeamSpeak3::factory($testUri);
        $nodeInfo = $ts3_Host->getInfo();
        $whoami = $ts3_Host->whoami();

        $ts3_Host->getAdapter()->getTransport()->disconnect();

        $this->assertEquals('UnitTestBot', $whoami['client_nickname']);
        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_ssh_multiple_connect_with_different_nicknames()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $conn1 = $this->ts3_server_uri.'&nickname=UnitTestBot1';
        $conn2 = $this->ts3_server_uri.'&nickname=UnitTestBot2';
        $conn3 = $this->ts3_server_uri.'&nickname=UnitTestBot3';

        $ts3_Host1 = TeamSpeak3::factory($conn1);
        $whoami1 = $ts3_Host1->whoami();

        $ts3_Host2 = TeamSpeak3::factory($conn2);
        $whoami2 = $ts3_Host2->whoami();

        $ts3_Host3 = TeamSpeak3::factory($conn3);
        $whoami3 = $ts3_Host3->whoami();

        $ts3_Host1->getAdapter()->getTransport()->disconnect();
        $ts3_Host2->getAdapter()->getTransport()->disconnect();
        $ts3_Host3->getAdapter()->getTransport()->disconnect();

        $this->assertEquals('UnitTestBot1', $whoami1['client_nickname']);
        $this->assertEquals('UnitTestBot2', $whoami2['client_nickname']);
        $this->assertEquals('UnitTestBot3', $whoami3['client_nickname']);
    }
}
