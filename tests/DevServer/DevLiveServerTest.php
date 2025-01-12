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
            $this->active = "false";
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port=9987'.
            '&ssh=0'.
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
    public function test_can_connect()
    {
        if ($this->active == "false") {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        //TODO: infinity connection on connection without responses. Explain in case 10022 is not allowed but connection is trying
        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $nodeInfo = $ts3_VirtualServer->getInfo();

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertEquals('Linux', $nodeInfo["virtualserver_platform"]);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_get_channel_info()
    {
        if ($this->active == "false") {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $channelInfo = $ts3_VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getInfo();

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();

        $this->assertEquals($this->ts3_unit_test_channel_name, $channelInfo["channel_name"]);
        $this->assertEquals(1, $channelInfo["channel_flag_permanent"]);
        $this->assertEquals("-1", $channelInfo["channel_maxclients"]);
        $this->assertEquals("-1", $channelInfo["channel_maxfamilyclients"]);

    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_create_play_test_channel()
    {
        if ($this->active == "false") {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $cid = $this->set_play_test_channel($ts3_VirtualServer);
        $cidTest = $ts3_VirtualServer->channelGetById($cid)->getInfo();

        $this->assertNotNull($cid);
        $this->assertEquals('Play-Test', $cidTest["channel_name"]);

        $this->unset_play_test_channel($ts3_VirtualServer);

        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_create_standard_channel()
    {
        if ($this->active == "false") {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_channel($ts3_VirtualServer);

        $testCid = $ts3_VirtualServer->channelCreate([
            "channel_name" => 'Standard Channel',
            "channel_codec" => 4,
            "channel_codec_quality" => 6,
            "channel_flag_semi_permanent" => 0,
            "channel_flag_permanent" => 1,
            "cpid" => $this->test_cid,
        ]);
        $testCidResult = $ts3_VirtualServer->channelGetById($testCid)->getInfo();

        $this->assertEquals('Standard Channel', $testCidResult["channel_name"]);
        $this->assertEquals(4, $testCidResult["channel_codec"]);
        $this->assertEquals(6, $testCidResult["channel_codec_quality"]);
        $this->assertEquals(0, $testCidResult["channel_flag_semi_permanent"]);
        $this->assertEquals(1, $testCidResult["channel_flag_permanent"]);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    private function set_play_test_channel($ts3VirtualServer): int
    {
        $cid = $ts3VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getInfo();

        $createdCID = $ts3VirtualServer->channelCreate([
            "channel_name" => 'Play-Test',
            'channel_flag_permanent' => 1,
            "cpid" => $cid['cid'],
        ]);
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
