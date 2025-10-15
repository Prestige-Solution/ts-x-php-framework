<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TeamSpeak3Exception;
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

    private string $ts3_unit_test_signals;

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
            $this->ts3_unit_test_signals = str_replace('DEV_LIVE_SERVER_UNIT_TEST_SIGNALS=', '', preg_replace('#\n(?!\n)#', '', $env[10]));
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
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            // Connect to the specified server, authenticate and spawn an object for the virtual server
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            //catch exception
            echo $e->getMessage();
        }

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
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            // Connect to the specified server, authenticate and spawn an object for the virtual server
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            //catch exception
            echo $e->getMessage();
        }

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
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws \Exception
     */
    public function test_channelGroupList_channelGroupClientList()
    {
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            // Connect to the specified server, authenticate and spawn an object for the virtual server
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            //catch exception
            echo $e->getMessage();
        }

        // Resetting lists
        $this->ts3_VirtualServer->clientListReset();
        $this->ts3_VirtualServer->channelGroupListReset();

        // Get servergroup client info
        $this->ts3_VirtualServer->clientList(['client_type' => 0]);
        $channelGroupList = $this->ts3_VirtualServer->channelGroupList();

        $channelgroup_clientlist = [];
        foreach ($channelGroupList as $channelgroup) {
            $channelgroup_clientlist[$channelgroup->cgid] = count($this->ts3_VirtualServer->channelGroupClientList($channelgroup->cgid));
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
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            echo $e->getMessage();
        }

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
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            echo $e->getMessage();
        }

        $cgid = $this->ts3_VirtualServer->channelGroupCreate('UniTest', 1);
        $this->ts3_VirtualServer->channelGroupDelete($cgid);

        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }
}
