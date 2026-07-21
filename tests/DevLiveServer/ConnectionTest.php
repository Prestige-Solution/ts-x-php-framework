<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ConnectionTest extends TestCase
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

    private string $serverPort;

    private string $user;

    private string $password;

    private string $ts3_server_uri;

    private string $serverQueryLoginName;

    public function setUp(): void
    {
        //proof test active
        if (file_exists('./.env.testing')) {
            $env = file('./.env.testing');
            $this->active = str_replace('DEV_LIVE_SERVER_AVAILABLE=', '', preg_replace('#\n(?!\n)#', '', $env[2]));
            $this->host = str_replace('DEV_LIVE_SERVER_HOST=', '', preg_replace('#\n(?!\n)#', '', $env[3]));
            $this->queryPort = str_replace('DEV_LIVE_SERVER_QUERY_PORT=', '', preg_replace('#\n(?!\n)#', '', $env[4]));
            $this->user = str_replace('DEV_LIVE_SERVER_QUERY_USER=', '', preg_replace('#\n(?!\n)#', '', $env[5]));
            $this->password = str_replace('DEV_LIVE_SERVER_QUERY_USER_PASSWORD=', '', preg_replace('#\n(?!\n)#', '', $env[6]));
            $this->serverPort = str_replace('DEV_LIVE_SERVER_UNIT_TEST_SERVER_PORT=', '', preg_replace('#\n(?!\n)#', '', $env[12]));
            $this->serverQueryLoginName = str_replace('DEV_LIVE_SERVER_UNIT_TEST_SERVER_QUERY_LOGIN_NAME=', '', preg_replace('#\n(?!\n)#', '', $env[13]));
        } else {
            $this->active = 'false';
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port='.$this->serverPort.
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
    public function test_can_ssh_connect()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);
        $nodeInfo = $ts3_Host->getInfo();
        $whoami = $ts3_Host->whoami();

        $ts3_Host->getAdapter()->getTransport()->disconnect();

        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
        $this->assertEquals($this->user, $whoami['client_nickname']);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_ssh_connect_with_nickname()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $testUri = $this->ts3_server_uri.'&nickname=UnitTestBot';

        $ts3_Host = TeamSpeak3::factory($testUri);
        $nodeInfo = $ts3_Host->getInfo();
        $whoami = $ts3_Host->whoami();

        $ts3_Host->getAdapter()->getTransport()->disconnect();

        $this->assertEquals('UnitTestBot', $whoami['client_nickname']);
        $this->assertEquals('Linux', $nodeInfo['virtualserver_platform']);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws \Exception
     */
    public function test_can_ssh_multiple_connect_with_different_nicknames()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $conn1 = $this->ts3_server_uri.'&nickname=UnitTestBot1';
        $conn2 = $this->ts3_server_uri.'&nickname=UnitTestBot2';
        $conn3 = $this->ts3_server_uri.'&nickname=UnitTestBot3';

        $ts3_Host1 = TeamSpeak3::factory($conn1);
        $whoami1 = $ts3_Host1->whoami();

        $ts3_Host2 = TeamSpeak3::factory($conn2);
        $whoami2 = $ts3_Host2->whoami();

        $ts3_Host3 = TeamSpeak3::factory($conn3);
        $whoami3 = $ts3_Host3->whoami();

        $ts3_Host1->getAdapter()->getTransport()->disconnect();
        $ts3_Host2->getAdapter()->getTransport()->disconnect();
        $ts3_Host3->getAdapter()->getTransport()->disconnect();

        $this->assertEquals('UnitTestBot1', $whoami1['client_nickname']);
        $this->assertEquals('UnitTestBot2', $whoami2['client_nickname']);
        $this->assertEquals('UnitTestBot3', $whoami3['client_nickname']);
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_get_host_information()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_host = TeamSpeak3::factory($this->ts3_server_uri);
        $port = $ts3_host->getParent()->serverSelectedPort();
        $this->assertEquals($this->serverPort, $port);

        $version = $ts3_host->version();
        $this->assertIsArray($version);
        $this->assertArrayHasKey('version', $version);
        $this->assertArrayHasKey('platform', $version);
        $this->assertEquals('Linux', $version['platform']);
        $this->assertArrayHasKey('build', $version);

        $serverID = $ts3_host->serverIdGetByPort($this->serverPort);
        $this->assertIsInt($serverID);
        $this->assertEquals(1, $serverID);

        $PortByID = $ts3_host->serverGetPortById($serverID);
        $this->assertIsInt($PortByID);
        $this->assertEquals($this->serverPort, $PortByID);

        $server = $ts3_host->servergetByname('UnitTestServer');
        $this->assertIsArray($server);
        $this->assertArrayHasKey('virtualserver_name', $server);
        $this->assertArrayHasKey('virtualserver_uptime', $server);

        $serverByUID = $ts3_host->serverGetByUid($server['virtualserver_unique_identifier']);
        $this->assertArrayHasKey('virtualserver_name', $serverByUID);
        $this->assertArrayHasKey('virtualserver_uptime', $serverByUID);

        $permList = $ts3_host->permissionList();
        $this->assertIsArray($permList);
        $this->assertArrayHasKey('b_serverinstance_help_view', $permList);
        $this->assertArrayHasKey('permid', $permList['b_serverinstance_help_view']);
        $this->assertArrayHasKey('permname', $permList['b_serverinstance_help_view']);
        $this->assertArrayHasKey('permcatid', $permList['b_serverinstance_help_view']);

        $permCats = $ts3_host->permissionCats();
        $this->assertIsArray($permCats);
        $this->assertArrayHasKey('PERM_CAT_GLOBAL', $permCats);
        $this->assertArrayHasKey('PERM_CAT_GROUP_DELETE', $permCats);
        $this->assertArrayHasKey('PERM_CAT_CLIENT_BASICS', $permCats);

        $permTree = $ts3_host->permissionTree();
        $this->assertIsArray($permTree);
        $this->assertArrayHasKey('permcatid', $permTree[16]);
        $this->assertArrayHasKey('permcatname', $permTree[16]);
        $this->assertEquals('Global', $permTree[16]['permcatname']);

        $permFind = $ts3_host->permissionFind(['b_virtualserver_info_view']);
        $this->assertIsArray($permFind[0]);
        $this->assertArrayHasKey('t', $permFind[0]);
        $this->assertArrayHasKey('id1', $permFind[0]);
        $this->assertArrayHasKey('id2', $permFind[0]);

        $permFindMultiple = $ts3_host->permissionFind(['b_virtualserver_info_view', 'b_virtualserver_channel_list']);
        $this->assertIsArray($permFindMultiple[0]);
        $this->assertArrayHasKey('t', $permFindMultiple[0]);
        $this->assertArrayHasKey('id1', $permFindMultiple[0]);
        $this->assertArrayHasKey('id2', $permFindMultiple[0]);
        $this->assertArrayHasKey('t', $permFindMultiple[1]);
        $this->assertArrayHasKey('id1', $permFindMultiple[1]);
        $this->assertArrayHasKey('id2', $permFindMultiple[1]);

        try {
            $ts3_host->permissionFind(['b_serverinstance_help_view']);
        } catch (ServerQueryException $e) {
            $this->assertEquals('invalid permission ID', $e->getMessage());
        }

        $permID = $ts3_host->permissionGetIdByName('b_virtualserver_info_view');
        $this->assertIsInt($permID);

        $permName = $ts3_host->permissionGetNameById($permID);
        $this->assertEquals('b_virtualserver_info_view', $permName);

        $selfPermCheck = $ts3_host->selfPermCheck(['b_virtualserver_info_view']);
        $this->assertIsArray($selfPermCheck);
        $this->assertArrayHasKey('permsid', $selfPermCheck);
        $this->assertIsString($selfPermCheck['permsid']);
        $this->assertEquals('b_virtualserver_info_view', $selfPermCheck['permsid']);
        $this->assertEquals(1, $selfPermCheck['permvalue']);

        $ts3_host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_handle_log()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_host = TeamSpeak3::factory($this->ts3_server_uri);
        $ts3_host->serverGetByPort($this->serverPort)->logAdd('UnitTest', TeamSpeak3::LOGLEVEL_DEBUG);
        $log = $ts3_host->serverGetByPort($this->serverPort)->logView();
        $this->assertIsArray($log);
        $this->assertIsString($log[29]);
        $this->assertStringContainsString('UnitTest', $log[29]);

        $ts3_host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_handle_server_query()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_host = TeamSpeak3::factory($this->ts3_server_uri);
        $countQuery = $ts3_host->queryCountLogin();
        $this->assertIsInt($countQuery);
        $this->assertEquals(1, $countQuery);

        $queryLoginlist = $ts3_host->queryListLogin();

        foreach ($queryLoginlist as $queryLogin) {
            $this->assertIsString($queryLogin['client_login_name']);
            $this->assertEquals($this->serverQueryLoginName, $queryLogin['client_login_name']);
        }

        $ts3_host->getAdapter()->getTransport()->disconnect();
    }
}
