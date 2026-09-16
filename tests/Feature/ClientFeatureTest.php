<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Client;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ClientFeatureTest extends TestCase
{
    private Server $server;

    protected function setUp(): void
    {
        $this->server = TeamSpeak3::factory('mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987');
    }

    protected function tearDown(): void
    {
        if ($this->server->getAdapter()->getTransport()->isConnected()) {
            $this->server->getAdapter()->getTransport()->disconnect();
        }
    }

    /**
     * Test retrieving client list.
     */
    public function testClientList(): void
    {
        $clients = $this->server->clientList();

        $this->assertIsArray($clients);
        $this->assertCount(2, $clients);
        $this->assertArrayHasKey(1, $clients);
        $this->assertArrayHasKey(2, $clients);
        $this->assertEquals('serveradmin', $clients[1]['client_nickname']);
        $this->assertEquals('UnitTestUser', $clients[2]['client_nickname']);
    }

    /**
     * Test getting client by ID.
     */
    public function testClientGetById(): void
    {
        $client = $this->server->clientGetById(2);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals(2, $client->getId());
        $this->assertEquals('UnitTestUser', $client['client_nickname']);
    }

    /**
     * Test getting client by Name.
     */
    public function testClientGetByName(): void
    {
        $client = $this->server->clientGetByName('UnitTestUser');

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals(2, $client->getId());
        $this->assertEquals('UnitTestUser', $client['client_nickname']);
    }

    /**
     * Test getting client by UID.
     */
    public function testClientGetByUid(): void
    {
        $client = $this->server->clientGetByUid('mock_user_uid_123');

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals(2, $client->getId());
        $this->assertEquals('UnitTestUser', $client['client_nickname']);
    }

    /**
     * Test client properties and info.
     */
    public function testClientProperties(): void
    {
        $client = $this->server->clientGetById(2);
        $info = $client->getInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('client_nickname', $info);
        $this->assertEquals('UnitTestUser', $info['client_nickname']);
        $this->assertEquals('mock_user_uid_123', $client['client_unique_identifier']);
        $this->assertEquals(2, $client['client_database_id']);
        $this->assertEquals(1, $client['cid']);
        $this->assertEquals(0, $client['client_type']);
        $this->assertEquals(0, $client['client_away']);
    }

    /**
     * Test client poke action.
     */
    public function testClientPoke(): void
    {
        $client = $this->server->clientGetById(2);
        $client->poke('Wake up!');

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('clientpoke clid=2 msg=Wake\sup!'));
    }

    /**
     * Test client kick action.
     */
    public function testClientKick(): void
    {
        $client = $this->server->clientGetById(2);
        $client->kick(TeamSpeak3::KICK_SERVER, 'Rule violation');

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('clientkick clid=2 reasonid=5 reasonmsg=Rule\sviolation'));
    }

    /**
     * Test client move action.
     */
    public function testClientMove(): void
    {
        $client = $this->server->clientGetById(2);
        $client->move(2);

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('clientmove clid=2 cid=2'));
    }

    /**
     * Test client message action.
     */
    public function testClientMessage(): void
    {
        $client = $this->server->clientGetById(2);
        $client->message('Private message');

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('sendtextmessage msg=Private\smessage target=2 targetmode=1'));
    }

    /**
     * Test server group assignment to client.
     */
    public function testClientServerGroupAssignment(): void
    {
        $this->server->serverGroupClientAdd(6, 2);

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('servergroupaddclient sgid=6 cldbid=2'));

        $this->server->serverGroupClientDel(6, 2);
        $this->assertTrue($transport->hasSentCommand('servergroupdelclient sgid=6 cldbid=2'));
    }

    /**
     * Test client not found exception.
     */
    public function testClientNotFoundException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Client not found');

        $this->server->clientGetByName('UnknownNonExistentUser');
    }
}
