<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ServerFeatureTest extends TestCase
{
    private Server $server;

    private Host $host;

    protected function setUp(): void
    {
        $this->server = TeamSpeak3::factory('mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987');
        $this->host = $this->server->getParent();
    }

    protected function tearDown(): void
    {
        if ($this->server->getAdapter()->getTransport()->isConnected()) {
            $this->server->getAdapter()->getTransport()->disconnect();
        }
    }

    /**
     * Test retrieving host version info.
     */
    public function testHostVersion(): void
    {
        $version = $this->host->version();

        $this->assertIsArray($version);
        $this->assertArrayHasKey('version', $version);
        $this->assertStringContainsString('3.13.7', $version['version']);
        $this->assertEquals('Linux', $version['platform']);
    }

    /**
     * Test retrieving host whoami info.
     */
    public function testHostWhoami(): void
    {
        $whoami = $this->host->whoami();

        $this->assertIsArray($whoami);
        $this->assertEquals('serveradmin', $whoami['client_nickname']);
        $this->assertEquals(1, $whoami['client_id']);
        $this->assertEquals(1, $whoami['virtualserver_id']);
    }

    /**
     * Test retrieving virtual server list.
     */
    public function testHostServerList(): void
    {
        $serverList = $this->host->serverList();

        $this->assertIsArray($serverList);
        $this->assertArrayHasKey(1, $serverList);
        $this->assertEquals('TeamSpeak ]Test[ Server', $serverList[1]['virtualserver_name']);
        $this->assertEquals('9987', $serverList[1]['virtualserver_port']);
    }

    /**
     * Test retrieving server by ID.
     */
    public function testHostServerGetById(): void
    {
        $server = $this->host->serverGetById(1);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertEquals(1, $server->getId());
    }

    /**
     * Test retrieving server by Port.
     */
    public function testHostServerGetByPort(): void
    {
        $server = $this->host->serverGetByPort(9987);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertEquals(1, $server->getId());
    }

    /**
     * Test server basic info and properties.
     */
    public function testServerInfoAndProperties(): void
    {
        $info = $this->server->getInfo();

        $this->assertIsArray($info);
        $this->assertEquals('TeamSpeak ]Test[ Server', $this->server['virtualserver_name']);
        $this->assertEquals('Linux', $this->server['virtualserver_platform']);
        $this->assertEquals(32, $this->server['virtualserver_maxclients']);
        $this->assertEquals('online', $this->server['virtualserver_status']);
        $this->assertEquals(1, $this->server->getId());
        $this->assertEquals('mock_server_uid', $this->server['virtualserver_unique_identifier']);
        $this->assertTrue($this->server->isOnline());
    }

    /**
     * Test sending a server message.
     */
    public function testServerMessage(): void
    {
        $this->server->message('Hello from feature test!');

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('sendtextmessage msg=Hello\sfrom\sfeature\stest! target=1 targetmode=3'));
    }

    /**
     * Test server group list.
     */
    public function testServerGroupList(): void
    {
        $groups = $this->server->serverGroupList();

        $this->assertIsArray($groups);
        $this->assertArrayHasKey(6, $groups);
        $this->assertEquals('Server Admin', $groups[6]['name']);
    }

    /**
     * Test channel group list.
     */
    public function testChannelGroupList(): void
    {
        $groups = $this->server->channelGroupList();

        $this->assertIsArray($groups);
        $this->assertArrayHasKey(5, $groups);
        $this->assertEquals('Channel Admin', $groups[5]['name']);
    }

    /**
     * Test server start / stop / create / delete signals and execution.
     */
    public function testServerLifecycleSignals(): void
    {
        $signals = [];
        Signal::getInstance()->subscribe('notifyServerstopped', function ($host, $sid) use (&$signals) {
            $signals[] = 'stopped: '.$sid;
        });
        Signal::getInstance()->subscribe('notifyServerstarted', function ($host, $sid) use (&$signals) {
            $signals[] = 'started: '.$sid;
        });
        Signal::getInstance()->subscribe('notifyServerdeleted', function ($host, $sid) use (&$signals) {
            $signals[] = 'deleted: '.$sid;
        });

        $this->host->serverStop(1, 'Maintenance');
        $this->host->serverStart(1);
        $this->host->serverDelete(2);

        $this->assertContains('stopped: 1', $signals);
        $this->assertContains('started: 1', $signals);
        $this->assertContains('deleted: 2', $signals);
    }

    /**
     * Test error reply when server ID is invalid.
     */
    public function testInvalidServerIdException(): void
    {
        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Adapter\MockServerQuery $adapter */
        $adapter = $this->server->getAdapter();
        $adapter->registerResponse('use sid=999', "error id=1024 msg=invalid\sserverID\n");

        $this->expectException(ServerQueryException::class);
        $this->expectExceptionCode(0x400);

        $this->host->serverSelectById(999);
    }

    /**
     * Test serverGroupCreate and serverGroupCopy.
     */
    public function testServerGroupCreateAndCopy(): void
    {
        $sgid = $this->server->serverGroupCreate('TestServerGroup');
        $this->assertEquals(10, $sgid);

        $copySgid = $this->server->serverGroupCopy(10, 'TestServerGroupCopy');
        $this->assertEquals(11, $copySgid);
    }

    /**
     * Test channelGroupCreate and channelGroupCopy.
     */
    public function testChannelGroupCreateAndCopy(): void
    {
        $cgid = $this->server->channelGroupCreate('TestChannelGroup');
        $this->assertEquals(10, $cgid);

        $copyCgid = $this->server->channelGroupCopy(10, 'TestChannelGroupCopy');
        $this->assertEquals(11, $copyCgid);
    }

    /**
     * Test clientInfoDb.
     */
    public function testClientInfoDb(): void
    {
        $info = $this->server->clientInfoDb(1);
        $this->assertIsArray($info);
        $this->assertEquals('1', $info['cldbid']);
        $this->assertEquals('serveradmin', $info['client_nickname']);
    }

    /**
     * Test serverIdGetByPort on Host.
     */
    public function testServerIdGetByPort(): void
    {
        $sid = $this->host->serverIdGetByPort(9987);
        $this->assertEquals(1, $sid);
    }

    /**
     * Test privilegeKeyCreate on ServerGroup and ChannelGroup.
     */
    public function testPrivilegeKeyCreateOnGroups(): void
    {
        $serverGroup = $this->server->serverGroupGetById(6);
        $token1 = $serverGroup->privilegeKeyCreate('Admin Token');
        $this->assertInstanceOf(\PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper::class, $token1);
        $this->assertEquals('mock_token_created_123', (string) $token1);

        $channelGroup = $this->server->channelGroupGetById(5);
        $token2 = $channelGroup->privilegeKeyCreate(1, 'Channel Admin Token');
        $this->assertInstanceOf(\PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper::class, $token2);
        $this->assertEquals('mock_token_created_123', (string) $token2);
    }

    /**
     * Test selfPermOverview.
     */
    public function testSelfPermOverview(): void
    {
        $overview = $this->server->selfPermOverview();
        $this->assertIsArray($overview);
    }
}
