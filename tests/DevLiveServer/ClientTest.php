<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ClientTest extends TestCase
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

    private string $user_test_active;

    private string $ts3_unit_test_userName;

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
            $this->user_test_active = str_replace('DEV_LIVE_SERVER_UNIT_TEST_USER_ACTIVE=', '', preg_replace('#\n(?!\n)#', '', $env[8]));
            $this->ts3_unit_test_userName = str_replace('DEV_LIVE_SERVER_UNIT_TEST_USER=', '', preg_replace('#\n(?!\n)#', '', $env[9]));
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
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
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
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws NodeException
     * @throws \Exception
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
        $this->assertEquals('', $userInfo['client_description']);

        $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->modify(['client_description'=> 'unittest']);
        $userInfoModified = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->getInfo();
        $this->assertIsArray($userInfoModified);
        $this->assertEquals('unittest', $userInfoModified['client_description']);

        //reset user modify
        $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName)->modify(['client_description'=> '']);

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
     * @throws TransportException
     * @throws ServerQueryException
     */
    private function set_play_test_channel(Server $ts3VirtualServer): void
    {
        $cid = $ts3VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getId();

        $createdCID = $ts3VirtualServer->channelCreate(['channel_name' => 'Play-Test', 'channel_flag_permanent' => 1, 'cpid' => $cid]);
        $this->test_cid = $createdCID;
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
