<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
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

    private string $apiKey;

    private string $ts3_server_uri;

    private string $ts3_server_uri_ssh;

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
            $this->apiKey = str_replace('DEV_LIVE_SERVER_API_KEY=', '', preg_replace('#\n(?!\n)#', '', $env[11]));
        } else {
            $this->active = 'false';
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port=9987'.
            '&ssh=0'.
            '&no_query_clients=0'.
            '&blocking=0'.
            '&timeout=30'.
            '&nickname=UnitTestBot';

        $this->ts3_server_uri_ssh = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':10022'.
            '/?server_port=9987'.
            '&ssh=1'.
            '&no_query_clients=0'.
            '&blocking=0'.
            '&timeout=30'.
            '&nickname=UnitTestBot';
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_raw_connect()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        //TODO: infinity connection on connection without access or wrong port
        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $nodeInfo = $ts3_VirtualServer->getInfo();

        $ts3_VirtualServer->getParent()->getAdapter()->getTransport()->disconnect();
        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_ssh_connect()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        //TODO: infinity connection on connection without access or wrong port
        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri_ssh);
        $nodeInfo = $ts3_VirtualServer->getInfo();

        $ts3_VirtualServer->getParent()->getAdapter()->getTransport()->disconnect();
        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
    }
}
