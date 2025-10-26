<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ServerGroupTest extends TestCase
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

    private int $sgid;

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
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws NodeException
     * @throws HelperException
     */
    public function test_can_get_servergroup_by_name()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($this->ts3_VirtualServer);

        $serverGroupName = $this->ts3_VirtualServer->serverGroupGetByName('UnitTest');
        $this->assertEquals('UnitTest', $serverGroupName['name']);
        $this->assertIsString($serverGroupName['name']);

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_rename_servergroup()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($this->ts3_VirtualServer);

        $this->ts3_VirtualServer->servergrouprename($this->sgid, 'UnitTest-Renamed');
        $renamedServerGroup = $this->ts3_VirtualServer->serverGroupGetById($this->sgid);
        $this->assertEquals('UnitTest-Renamed', $renamedServerGroup['name']);

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);

        //test by ServerGroup Class
        $this->set_play_test_servergroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->rename('UnitTest-Renamed');
        $renamedByChain = $this->ts3_VirtualServer->serverGroupGetById($this->sgid);
        $this->assertEquals('UnitTest-Renamed', $renamedByChain['name']);

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws NodeException
     * @throws HelperException
     */
    public function test_can_copy_delete_servergroup()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($this->ts3_VirtualServer);

        $duplicatedSGID = $this->ts3_VirtualServer->serverGroupCopy($this->sgid, 'UnitTest-Copy');
        $getDuplicatedServerGroup = $this->ts3_VirtualServer->serverGroupGetById($duplicatedSGID);
        $this->assertEquals('UnitTest-Copy', $getDuplicatedServerGroup['name']);

        $this->ts3_VirtualServer->serverGroupGetById($duplicatedSGID)->delete();
        try {
            $this->ts3_VirtualServer->serverGroupGetById($duplicatedSGID);
            $this->fail('ServerGroup should not exist');
        } catch (ServerQueryException $e) {
            $this->assertEquals('invalid groupID', $e->getMessage());
        }

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);

        //test by ServerGroup Class
        $this->set_play_test_servergroup($this->ts3_VirtualServer);

        $duplicatedSGIDChain = $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->copy('UnitTest-Copy');
        $getDuplicatedServerGroupChain = $this->ts3_VirtualServer->serverGroupGetById($duplicatedSGIDChain);
        $this->assertEquals('UnitTest-Copy', $getDuplicatedServerGroupChain['name']);

        $this->ts3_VirtualServer->serverGroupDelete($getDuplicatedServerGroupChain->getId());
        try {
            $this->ts3_VirtualServer->serverGroupGetById($getDuplicatedServerGroupChain->getId());
            $this->fail('ServerGroup should not exist');
        } catch (ServerQueryException $e) {
            $this->assertEquals('invalid groupID', $e->getMessage());
        }

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws NodeException
     * @throws HelperException
     */
    public function test_can_assign_remove_permissions_to_servergroup()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($this->ts3_VirtualServer);

        $this->ts3_VirtualServer->serverGroupPermAssign($this->sgid, ['i_client_private_textmessage_power'], [75], [0], [0]);
        $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->permAssign(['i_client_talk_power'], 75);

        $permList = $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->permList(true);
        $this->assertEquals(75, $permList['i_client_talk_power']['permvalue']);
        $this->assertEquals(75, $permList['i_client_private_textmessage_power']['permvalue']);

        $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->permRemove(['i_client_private_textmessage_power']);
        $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->permRemove(['i_client_talk_power']);

        $permListKeyRemoved = $this->ts3_VirtualServer->serverGroupGetById($this->sgid)->permList(true);

        $this->assertArrayNotHasKey('i_client_talk_power', $permListKeyRemoved);
        $this->assertArrayNotHasKey('i_client_private_textmessage_power', $permListKeyRemoved);

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function test_can_get_iconList()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($this->ts3_VirtualServer);

        //memo: an array can be empty if no icons uploaded
        $iconList = $this->ts3_VirtualServer->iconList();
        $this->assertIsarray($iconList);

        $this->unset_play_test_servergroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    private function set_play_test_servergroup(Server $ts3VirtualServer): void
    {
        $this->sgid = $ts3VirtualServer->serverGroupCreate('UnitTest', 1);
    }

    /**
     * @param  Server  $ts3_VirtualServer
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function unset_play_test_servergroup(Server $ts3_VirtualServer): void
    {
        $ts3_VirtualServer->serverGroupDelete($this->sgid);
    }

    /**
     * @throws AdapterException
     * @throws NodeException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function dev_reset_servergroup(): void
    {
        $servergrouplist = $this->ts3_VirtualServer->serverGroupList(['type' => 1]);
        foreach ($servergrouplist as $servergroup) {
            if ($servergroup['name'] != 'Server Admin' && $servergroup['name'] != 'Guest') {
                $this->ts3_VirtualServer->serverGroupDelete($servergroup['sgid'], true);
            }
        }
    }
}
