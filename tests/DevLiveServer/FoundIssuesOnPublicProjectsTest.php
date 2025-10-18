<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TeamSpeak3Exception;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class FoundIssuesOnPublicProjectsTest extends TestCase
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

    private Server|Adapter|Node|Host $ts3_VirtualServer;

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
            '&timeout=30';
    }

    /**
     * @throws \Exception
     */
    public function test_can_get_virtual_servername(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            echo $e->getMessage();
        }

        try {
            $this->ts3_VirtualServer->virtualserver_name;
        }catch (TeamSpeak3Exception $e) {
            $this->assertEquals("node 'PlanetTeamSpeak\TeamSpeak3Framework\Node\Server' has no property named 'virtualserver_name'", $e->getMessage());
        }

        $serverName =  $this->ts3_VirtualServer->getInfo()['virtualserver_name'];
        $this->assertNotEmpty($serverName);


        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }
}
