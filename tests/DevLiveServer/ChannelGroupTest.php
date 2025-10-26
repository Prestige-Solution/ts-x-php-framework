<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ChannelGroupTest extends TestCase
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

    private int $cgid;

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
     * @throws HelperException
     */
    public function test_can_get_channelgroup_by_name()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channelgroup($this->ts3_VirtualServer);

        $channelgroup = $this->ts3_VirtualServer->channelGroupGetByName('UnitTest');

        $this->assertIsString($channelgroup['name']);
        $this->assertEquals('UnitTest', $channelgroup['name']);

        $this->unset_play_test_channelgroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_rename_channelgroup()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channelgroup($this->ts3_VirtualServer);

        $channelgroup = $this->ts3_VirtualServer->channelGroupGetByName('UnitTest');
        $channelgroup->rename('UnitTest-Renamed');
        $renamedChannelGroup = $this->ts3_VirtualServer->channelGroupGetByName('UnitTest-Renamed');

        $this->assertIsString($renamedChannelGroup['name']);
        $this->assertEquals('UnitTest-Renamed', $renamedChannelGroup['name']);

        $this->unset_play_test_channelgroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_copy_delete_channelgroup()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channelgroup($this->ts3_VirtualServer);

        $this->ts3_VirtualServer->channelGroupGetByName('UnitTest')->copy('UnitTest-Copy');
        $copiedChannelGroup = $this->ts3_VirtualServer->channelGroupGetByName('UnitTest-Copy');

        $this->assertIsString($copiedChannelGroup['name']);
        $this->assertEquals('UnitTest-Copy', $copiedChannelGroup['name']);
        $this->ts3_VirtualServer->channelGroupGetByName('UnitTest-Copy')->delete();

        try {
            $this->ts3_VirtualServer->channelGroupGetByName('UnitTest-Copy');
            $this->fail('ServerGroup should not exist');
        } catch (ServerQueryException $e) {
            $this->assertEquals('invalid groupID', $e->getMessage());
        }

        $this->unset_play_test_channelgroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws HelperException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     */
    public function test_can_assign_remove_permissions_to_channelgroup()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channelgroup($this->ts3_VirtualServer);

        $this->ts3_VirtualServer->channelGroupPermAssign($this->cgid, ['i_client_private_textmessage_power'], [75]);
        $this->ts3_VirtualServer->channelGroupGetById($this->cgid)->permAssign(['i_client_talk_power'], 75);

        $permList = $this->ts3_VirtualServer->channelGroupGetById($this->cgid)->permList(true);
        $this->assertEquals(75, $permList['i_client_talk_power']['permvalue']);
        $this->assertEquals(75, $permList['i_client_private_textmessage_power']['permvalue']);

        $this->ts3_VirtualServer->channelGroupGetById($this->cgid)->permRemove(['i_client_private_textmessage_power']);
        $this->ts3_VirtualServer->channelGroupGetById($this->cgid)->permRemove(['i_client_talk_power']);

        $permListKeyRemoved = $this->ts3_VirtualServer->channelGroupGetById($this->cgid)->permList(true);

        $this->assertArrayNotHasKey('i_client_talk_power', $permListKeyRemoved);
        $this->assertArrayNotHasKey('i_client_private_textmessage_power', $permListKeyRemoved);

        $this->unset_play_test_channelgroup($this->ts3_VirtualServer);
        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_channelGroupList()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $channelgrouplist = $this->ts3_VirtualServer->channelGroupList(['type' => 1]);

        foreach ($channelgrouplist as $channelgroup) {
            $this->assertContains($channelgroup['name'], ['Channel Admin', 'Guest', 'Operator']);
            $this->assertIsInt($channelgroup['cgid']);
        }


        $this->ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($this->ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    private function set_play_test_channelgroup(Server $ts3VirtualServer): void
    {
        $this->cgid = $ts3VirtualServer->channelGroupCreate('UnitTest', 1);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function unset_play_test_channelgroup(Server $ts3_VirtualServer): void
    {
        $ts3_VirtualServer->channelGroupDelete($this->cgid);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function dev_reset_channelgroup(): void
    {
        $channelgrouplist = $this->ts3_VirtualServer->channelGroupList(['type' => 1]);
        foreach ($channelgrouplist as $channelgroup) {
            if ($channelgroup['name'] != 'Channel Admin' && $channelgroup['name'] != 'Guest' && $channelgroup['name'] != 'Operator') {
                $this->ts3_VirtualServer->channelGroupDelete($channelgroup['cgid'], true);
            }
        }
    }
}
