<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevServer;

use Exception;
use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TeamSpeak3Exception;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
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

    private string $ts3_server_uri;

    private string $ts3_server_uri_ssh;

    private string $ts3_unit_test_channel_name;

    private string $user_test_active;
    private string $ts3_unit_test_userName;
    private string $ts3_unit_test_signals;

    private int $test_cid;

    private int $duration;

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
            $this->ts3_unit_test_channel_name = str_replace('DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=', '', preg_replace('#\n(?!\n)#', '', $env[7]));
            $this->user_test_active = str_replace('DEV_LIVE_SERVER_UNIT_TEST_USER_ACTIVE=', '', preg_replace('#\n(?!\n)#', '', $env[8]));
            $this->ts3_unit_test_userName = str_replace('DEV_LIVE_SERVER_UNIT_TEST_USER=', '', preg_replace('#\n(?!\n)#', '', $env[9]));
            $this->ts3_unit_test_signals = str_replace('DEV_LIVE_SERVER_UNIT_TEST_SIGNALS=', '', preg_replace('#\n(?!\n)#', '', $env[10]));
        } else {
            $this->active = 'false';
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port=9987'.
            '&ssh=0'.
            '&no_query_clients'.
            '&blocking=0'.
            '&timeout=30'.
            '&nickname=UnitTestBot';

        $this->ts3_server_uri_ssh = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':10022'.
            '/?server_port=9987'.
            '&ssh=1'.
            '&no_query_clients'.
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

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
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

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_get_channel_info()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $channelInfo = $ts3_VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getInfo();

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();

        $this->assertEquals($this->ts3_unit_test_channel_name, $channelInfo['channel_name']);
        $this->assertEquals(1, $channelInfo['channel_flag_permanent']);
        $this->assertEquals('-1', $channelInfo['channel_maxclients']);
        $this->assertEquals('-1', $channelInfo['channel_maxfamilyclients']);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
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
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_get_user_attributes()
    {
        if ($this->user_test_active == 'false' || $this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $userInfo = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->getInfo();

        $this->assertIsArray($userInfo);
        $this->assertEquals($this->ts3_unit_test_userName, $userInfo['client_nickname']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_set_user_attributes()
    {
        if ($this->user_test_active == 'false' || $this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $userInfo = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->getInfo();
        $this->assertIsArray($userInfo);
        $this->assertEquals($this->ts3_unit_test_userName, $userInfo['client_nickname']);
        $this->assertEquals(0, $userInfo['client_is_talker']);

        $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->modify(['client_is_talker'=> 1]);
        $userInfoModified = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->getInfo();
        $this->assertIsArray($userInfoModified);
        $this->assertEquals(1, $userInfoModified['client_is_talker']);

        //reset user modify
        $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->modify(['client_is_talker'=> 0]);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_move_user()
    {
        if ($this->user_test_active == 'false' || $this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate(['channel_name' => 'Standard Channel', 'channel_flag_permanent' => 1, 'cpid' => $this->test_cid]);
        $userID = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->getId();
        $ts3_VirtualServer->clientMove($userID, $testCid);

        $userMoved = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->getInfo();
        $this->assertEquals($userMoved['cid'], $testCid);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     * @throws Exception
     */
    public function test_ssh_signal_on_wait_timeout()
    {
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        //define duration
        $this->duration = strtotime('+6 minutes');

        try {
            // Connect to the specified server, authenticate and spawn an object for the virtual server
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri_ssh);
        } catch(TeamSpeak3Exception $e) {
            //catch exception
            echo $e->getMessage();
        }

        // Register a callback for serverqueryWaitTimeout events
        Signal::getInstance()->subscribe('serverqueryWaitTimeout', array($this, 'onWaitTimeout'));

        // Register for server events
        $this->ts3_VirtualServer->serverGetSelected()->notifyRegister('server');

        try {
            while (true) {
                $this->ts3_VirtualServer->getParent()->getAdapter()->wait();
            }
        }catch(TeamSpeak3Exception $e) {
            //catch disconnect exception
            $this->assertEquals("node method 'getTransport()' does not exist", $e->getMessage());
            $this->assertEquals(0,$e->getCode());
        }
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException|\PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException
     * @throws Exception
     */
    function onWaitTimeout(int $idle_seconds, ServerQuery $serverquery): void
    {
        // If the timestamp on the last query is more than 300 seconds (5 minutes) in the past, send 'keepalive'
        // 'keepalive' command is just server query command 'clientupdate' which does nothing without properties. So nothing changes.
        if ($serverquery->getQueryLastTimestamp() < time() - 260) {
            $serverquery->request('clientupdate');
        }

        // Get data every minute
        if ($idle_seconds % 60 == 0) {
            // Resetting lists
            $this->ts3_VirtualServer->clientListReset();
            $this->ts3_VirtualServer->serverGroupListReset();

            // Get servergroup client info
            $this->ts3_VirtualServer->clientList(['client_type' => 0]);
            $servergrouplist = $this->ts3_VirtualServer->serverGroupList(['type' => 1]);

            $servergroup_clientlist = [];
            foreach ($servergrouplist as $servergroup) {
                $servergroup_clientlist[$servergroup->sgid] = count($this->ts3_VirtualServer->serverGroupClientList($servergroup->sgid));
            }

            // Get virtualserver info
            $this->ts3_VirtualServer->getInfo(true, true);
            $this->ts3_VirtualServer->connectionInfo();
        }

        if (time() >= $this->duration)
        {
            $this->ts3_VirtualServer->getParent()->getTransport()->disconnect();
        }
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    private function set_play_test_channel($ts3VirtualServer): int
    {
        $cid = $ts3VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getInfo();

        $createdCID = $ts3VirtualServer->channelCreate(['channel_name' => 'Play-Test', 'channel_flag_permanent' => 1, 'cpid' => $cid['cid']]);
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
}
