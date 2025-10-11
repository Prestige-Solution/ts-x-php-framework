<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use Throwable;

class ChannelTest extends TestCase
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

    private string $ts3_unit_test_channel_name;

    private int $test_cid;

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
            $this->ts3_unit_test_channel_name = str_replace('DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=', '', preg_replace('#\n(?!\n)#', '', $env[7]));
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
    public function test_can_get_channel_info()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $channelInfo = $ts3_VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getInfo();

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();

        $this->assertEquals($this->ts3_unit_test_channel_name, $channelInfo['channel_name']);
        $this->assertEquals(1, $channelInfo['channel_flag_permanent']);
        $this->assertEquals('-1', $channelInfo['channel_maxclients']);
        $this->assertEquals('-1', $channelInfo['channel_maxfamilyclients']);
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_create_play_test_channel()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $cid = $this->set_play_test_channel($ts3_VirtualServer);
        $cidTest = $ts3_VirtualServer->channelGetById($cid)->getInfo();

        $this->assertNotNull($cid);
        $this->assertEquals('Play-Test', $cidTest['channel_name']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_create_channels()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate([
            'channel_name' => 'Standard Channel',
            'channel_codec' => 4,
            'channel_codec_quality' => 6,
            'channel_flag_semi_permanent' => 0,
            'channel_flag_permanent' => 1,
            'cpid' => $this->test_cid,
        ]);
        $testCidResult = $ts3_VirtualServer->channelGetById($testCid)->getInfo();

        $this->assertEquals('Standard Channel', $testCidResult['channel_name']);
        $this->assertEquals(4, $testCidResult['channel_codec']);
        $this->assertEquals(6, $testCidResult['channel_codec_quality']);
        $this->assertEquals(0, $testCidResult['channel_flag_semi_permanent']);
        $this->assertEquals(1, $testCidResult['channel_flag_permanent']);

        //increase channels
        for ($i = 0; $i <= 20; $i++) {
            $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel'.$i, 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        }

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_channel_name_utf8()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        for ($i = 0; $i <= 3; $i++) {
            $createdCID = $ts3_VirtualServer->channelCreate(['channel_name' => 'public-'.$i, 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

            //get to create a name
            $channelName = $ts3_VirtualServer->channelGetById($createdCID)->getInfo();
            $this->assertEquals('public-'.$i, $channelName['channel_name']);
        }

        //increase channels
        for ($i = 0; $i <= 10; $i++) {
            $createdCID = $ts3_VirtualServer->channelCreate(['channel_name' => '3on3-'.$i, 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

            //get to create a name
            $channelName = $ts3_VirtualServer->channelGetById($createdCID)->getInfo();
            $this->assertEquals('3on3-'.$i, $channelName['channel_name']);
        }

        $createdCID = $ts3_VirtualServer->channelCreate(['channel_name' => 'Äpfel Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $channelName = $ts3_VirtualServer->channelGetById($createdCID)->getInfo();
        $this->assertEquals('Äpfel Channel', $channelName['channel_name']);

        $createdCID = $ts3_VirtualServer->channelCreate(['channel_name' => '¶ Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $channelName = $ts3_VirtualServer->channelGetById($createdCID)->getInfo();
        $this->assertEquals('¶ Channel', $channelName['channel_name']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_edit_channel()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

        $channelUnmodified = $ts3_VirtualServer->channelGetById($testCid)->getInfo();
        $this->assertEquals('Standard Channel', $channelUnmodified['channel_name']);
        $this->assertEquals(4, $channelUnmodified['channel_codec']); // 4 = standard
        $this->assertEquals(5, $channelUnmodified['channel_codec_quality']); // 5 = standard
        $this->assertEquals(0, $channelUnmodified['channel_flag_semi_permanent']);
        $this->assertEquals(1, $channelUnmodified['channel_flag_permanent']);

        $ts3_VirtualServer->channelGetById($testCid)->modify([
            'channel_name' => 'Standard Channel Modified',
            'channel_codec' => 5,
            'channel_codec_quality' => 6,
            'channel_flag_semi_permanent' => 1,
            'channel_flag_permanent'=>0,
        ]);

        $channelModifiedResult = $ts3_VirtualServer->channelGetById($testCid)->getInfo();

        $this->assertEquals('Standard Channel Modified', $channelModifiedResult['channel_name']);
        $this->assertEquals(5, $channelModifiedResult['channel_codec']);
        $this->assertEquals(6, $channelModifiedResult['channel_codec_quality']);
        $this->assertEquals(1, $channelModifiedResult['channel_flag_semi_permanent']);
        $this->assertEquals(0, $channelModifiedResult['channel_flag_permanent']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_delete_channels()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid1 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel1', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid2 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel2', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid3 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel3', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid4 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel4', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid5 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel5', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

        $ts3_VirtualServer->channelDelete($testCid1);
        $ts3_VirtualServer->channelDelete($testCid2);
        $ts3_VirtualServer->channelDelete($testCid3);
        $ts3_VirtualServer->channelDelete($testCid4);
        $ts3_VirtualServer->channelDelete($testCid5);

        try {
            $ts3_VirtualServer->channelGetById($testCid1);
        } catch (ServerQueryException $e) {
            $this->assertEquals('invalid channelID', $e->getMessage());
        } finally {
            $this->unset_play_test_channel($ts3_VirtualServer);
            $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        }
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_move_channels()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);
        $stdChannel = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

        $testCid1 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel1', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid2 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel2', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid3 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel3', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid4 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel4', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $testCid5 = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel5', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

        $ts3_VirtualServer->channelMove($testCid1, $stdChannel);
        $result = $ts3_VirtualServer->channelGetById($testCid1)->getInfo();
        $this->assertEquals($stdChannel, $result['pid']);

        $ts3_VirtualServer->channelMove($testCid2, $testCid1);
        $result = $ts3_VirtualServer->channelGetById($testCid2)->getInfo();
        $this->assertEquals($testCid1, $result['pid']);

        $ts3_VirtualServer->channelMove($testCid3, $testCid2);
        $result = $ts3_VirtualServer->channelGetById($testCid3)->getInfo();
        $this->assertEquals($testCid2, $result['pid']);

        $ts3_VirtualServer->channelMove($testCid4, $testCid3);
        $result = $ts3_VirtualServer->channelGetById($testCid4)->getInfo();
        $this->assertEquals($testCid3, $result['pid']);

        $ts3_VirtualServer->channelMove($testCid5, $testCid4);
        $result = $ts3_VirtualServer->channelGetById($testCid5)->getInfo();
        $this->assertEquals($testCid4, $result['pid']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_whoami()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $ts3_VirtualServer->whoamiSet('client_channel_id', $testCid);

        $whoami = $ts3_VirtualServer->whoami();
        $whoamiChannelName = $ts3_VirtualServer->channelGetById($whoami['client_channel_id'])->getInfo();
        $this->assertEquals('Standard Channel', $whoamiChannelName['channel_name']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_get_channel_permissions()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel5', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);

        $channel = $ts3_VirtualServer->channelGetById($testCid);
        $channelPermission = $channel->permList(true);

        $this->assertEquals(75, $channelPermission['i_channel_needed_permission_modify_power']['permvalue']);
        $this->assertEquals(75, $channelPermission['i_channel_needed_delete_power']['permvalue']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_set_channel_permissions()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $ts3_VirtualServer->channelPermAssign($testCid, ['i_channel_needed_join_power'], [50]);
        $ts3_VirtualServer->channelPermAssign($testCid, ['i_channel_needed_subscribe_power'], [50]);

        $channel = $ts3_VirtualServer->channelGetById($testCid);
        $channelPermission = $channel->permList(true);

        $this->assertEquals(50, $channelPermission['i_channel_needed_join_power']['permvalue']);
        $this->assertEquals(50, $channelPermission['i_channel_needed_subscribe_power']['permvalue']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_delete_channel_permissions()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $ts3_VirtualServer->channelPermAssign($testCid, ['i_channel_needed_join_power'], [50]);
        $ts3_VirtualServer->channelPermAssign($testCid, ['i_channel_needed_subscribe_power'], [50]);

        $ts3_VirtualServer->channelPermRemove($testCid, ['i_channel_needed_join_power']);
        $ts3_VirtualServer->channelPermRemove($testCid, ['i_channel_needed_subscribe_power']);

        $channel = $ts3_VirtualServer->channelGetById($testCid);
        $channelPermission = $channel->permList(true);

        $this->assertIsArray($channelPermission);
        $this->assertArrayNotHasKey('i_channel_needed_join_power', $channelPermission);
        $this->assertArrayNotHasKey('i_channel_needed_subscribe_power', $channelPermission);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_channel_get_info_has_cid()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $channelInfoGetByName = $ts3_VirtualServer->channelGetByName($this->ts3_unit_test_channel_name);
        $channelInfoGetById = $ts3_VirtualServer->channelGetById($channelInfoGetByName['cid']);

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());

        $this->assertArrayHasKey('cid', $channelInfoGetByName);
        $this->assertArrayHasKey('cid', $channelInfoGetByName->getInfo());
        $this->assertIsInt($channelInfoGetByName['cid']);
        $this->assertArrayHasKey('cid', $channelInfoGetById);
        $this->assertArrayHasKey('cid', $channelInfoGetById->getInfo());
        $this->assertIsInt($channelInfoGetById['cid']);
        $this->assertEquals($this->ts3_unit_test_channel_name, $channelInfoGetByName['channel_name']);
        $this->assertEquals(1, $channelInfoGetByName['channel_flag_permanent']);
        $this->assertEquals('-1', $channelInfoGetByName['channel_maxclients']);
        $this->assertEquals('-1', $channelInfoGetByName['channel_maxfamilyclients']);

        $this->assertTrue($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_channel_has_necessary_keys()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $channelInfoGetByName = $ts3_VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getInfo();

        $this->assertArrayHasKey('cid', $channelInfoGetByName);
        $this->assertArrayHasKey('pid', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_order', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_name', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_topic', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_default', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_password', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_permanent', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_semi_permanent', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_codec', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_codec_quality', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_needed_talk_power', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_icon_id', $channelInfoGetByName);
        $this->assertArrayHasKey('total_clients_family', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_maxclients', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_maxfamilyclients', $channelInfoGetByName);
        $this->assertArrayHasKey('total_clients', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_needed_subscribe_power', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_banner_gfx_url', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_banner_mode', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_description', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_password', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_codec_latency_factor', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_codec_is_unencrypted', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_security_salt', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_delete_delay', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_unique_identifier', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_maxclients_unlimited', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_maxfamilyclients_unlimited', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_flag_maxfamilyclients_inherited', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_filepath', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_forced_silence', $channelInfoGetByName);
        $this->assertArrayHasKey('channel_name_phonetic', $channelInfoGetByName);
        $this->assertArrayHasKey('seconds_empty', $channelInfoGetByName);

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    private function set_play_test_channel(Server $ts3VirtualServer): int
    {
        $cid = $ts3VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getId();

        $createdCID = $ts3VirtualServer->channelCreate(['channel_name' => 'Play-Test', 'channel_flag_permanent' => 1, 'cpid' => $cid]);
        $this->test_cid = $createdCID;

        return $createdCID;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function unset_play_test_channel($ts3_VirtualServer): void
    {
        $ts3_VirtualServer->channelDelete($this->test_cid, true);
    }

    protected function onNotSuccessfulTest(Throwable $t): never
    {
        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();

        parent::onNotSuccessfulTest($t);
    }
}
