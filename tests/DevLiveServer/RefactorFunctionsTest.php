<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class RefactorFunctionsTest extends TestCase
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
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_clientupdate_getErrorProperty()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        $clientUpdate = $this->ts3_VirtualServer->getAdapter()->request('clientupdate');
        $clientUpdateResult = $clientUpdate->getErrorProperty('msg')->toString();

        $this->assertEquals('ok', $clientUpdateResult);

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws NodeException
     * @throws \Exception
     */
    public function test_serverGroupList_serverGroupClientList()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        // Resetting lists
        $this->ts3_VirtualServer->clientListReset();
        $this->ts3_VirtualServer->serverGroupListReset();

        // Get servergroup client info
        $this->ts3_VirtualServer->clientList(['client_type' => 0]);
        $servergrouplist = $this->ts3_VirtualServer->serverGroupList(['type' => 1]);

        $servergroup_clientlist = [];
        foreach ($servergrouplist as $servergroup) {
            $servergroup_clientlist[$servergroup->sgid] = count($this->ts3_VirtualServer->serverGroupClientList($servergroup->sgid));
            $arrayResult = $this->ts3_VirtualServer->serverGroupClientList($servergroup->sgid);

            $this->assertIsArray($arrayResult);
            foreach ($arrayResult as $entry) {
                $this->assertArrayHasKey('client_nickname', $entry);
            }
        }

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_create_server_group(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        $sid = $this->ts3_VirtualServer->serverGroupCreate('UniTest', 1);
        $this->ts3_VirtualServer->serverGroupDelete($sid);

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_create_channel_group(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        $cgid = $this->ts3_VirtualServer->channelGroupCreate('UniTest', 1);
        $this->ts3_VirtualServer->channelGroupDelete($cgid);

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_invalid_parameter_size(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        //#channel name
        //30 chars
        $cgid = $this->ts3_VirtualServer->channelGroupCreate('Lorem ipsum dolor sit amet, co', 1);
        $result = $this->ts3_VirtualServer->channelGroupGetById($cgid)->getInfo()['name'];
        $this->assertEquals(30, strlen($result));
        $this->assertEquals('Lorem ipsum dolor sit amet, co', $result);
        $this->ts3_VirtualServer->channelGroupDelete($cgid);

        //more than 30 chars
        $cgid = $this->ts3_VirtualServer->channelGroupCreate('Lorem ipsum dolor sit amet, consetetur s', 1);
        $result = $this->ts3_VirtualServer->channelGroupGetById($cgid)->getInfo()['name'];
        $this->assertEquals(30, strlen($result));
        $this->assertEquals('Lorem ipsum dolor sit amet, co', $result);
        $this->ts3_VirtualServer->channelGroupDelete($cgid);

        //#servergroup name
        //<= 30 chars
        $sid = $this->ts3_VirtualServer->serverGroupCreate('-----Unit Täst / Unit Test----', 1);
        $result = $this->ts3_VirtualServer->serverGroupGetById($sid)->getInfo()['name'];
        $this->assertEquals('-----Unit Täst \/ Unit Test----', $result);
        $this->ts3_VirtualServer->serverGroupDelete($sid);

        //more than 30 chars
        $sid = $this->ts3_VirtualServer->serverGroupCreate('-----Unit Täst / Unit Test----', 1);
        $result = $this->ts3_VirtualServer->serverGroupGetById($sid)->getInfo()['name'];
        $this->assertEquals('-----Unit Täst \/ Unit Test----', $result);
        $this->ts3_VirtualServer->serverGroupDelete($sid);

        //more chars
        $sid = $this->ts3_VirtualServer->serverGroupCreate('--Lorem ipsum dolür sit amet, conäetetur sadip--', 1);
        $result = $this->ts3_VirtualServer->serverGroupGetById($sid)->getInfo()['name'];
        $this->assertEquals(30, strlen($result));
        $this->assertEquals('--Lorem ipsum dolür sit amet,', $result);
        $this->ts3_VirtualServer->serverGroupDelete($sid);

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws \Exception
     */
    public function test_can_get_isOnline_isOffline(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        $statusOnline = $this->ts3_VirtualServer->isOnline();
        $statusOffline = $this->ts3_VirtualServer->isOffline();

        $this->assertTrue($statusOnline);
        $this->assertFalse($statusOffline);

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }
}
