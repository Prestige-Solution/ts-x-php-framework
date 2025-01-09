<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class DevLiveServerTest extends TestCase
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

    private string $original_ssh_timeout;
    private string $original_execution_timeout;
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
            $this->active = "false";
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port=9987'.
            '&ssh=0'.
            '&no_query_clients'.
            '&blocking=0'.
            '&timeout=30'.
            '&nickname=UnitTestBot';

        $this->original_ssh_timeout = ini_get('default_socket_timeout');
        $this->original_execution_timeout = ini_get('max_execution_time');
        ini_set('default_socket_timeout', 2);
        ini_set('max_execution_time', 10);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_ssh_connection()
    {
        if ($this->active == "false") {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        //TODO: infinity connection on connection without responses. Explain in case 10022 is not allowed but connection is trying
        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $nodeInfo = $ts3_VirtualServer->getInfo();

        $this->assertEquals('Linux', $nodeInfo["virtualserver_platform"]);
    }

    public function tearDown(): void
    {
        ini_set('default_socket_timeout', $this->original_ssh_timeout);
        ini_set('max_execution_time', $this->original_execution_timeout);
    }
}
