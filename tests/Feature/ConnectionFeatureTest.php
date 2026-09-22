<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\MockServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP;

class ConnectionFeatureTest extends TestCase
{
    /**
     * Test connection via TeamSpeak3::factory() with mock scheme.
     */
    public function testFactoryConnectMockScheme(): void
    {
        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987';
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertEquals(1, $server->getId());
        $this->assertTrue($server->getAdapter()->getTransport()->isConnected());

        $server->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($server->getAdapter()->getTransport()->isConnected());
    }

    /**
     * Test factory connect without selecting a server (returns Host node).
     */
    public function testFactoryConnectHostOnly(): void
    {
        $uri = 'mock://serveradmin:secret@127.0.0.1:10022';
        $host = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Host::class, $host);
        $whoami = $host->whoami();

        $this->assertEquals('serveradmin', $whoami['client_nickname']);
        $this->assertEquals('1', $whoami['virtualserver_id']);

        $host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test factory connection with server_id parameter.
     */
    public function testFactoryConnectWithServerId(): void
    {
        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_id=1';
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertEquals(1, $server->getId());

        $server->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test factory connection with nickname parameter.
     */
    public function testFactoryConnectWithNickname(): void
    {
        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&nickname=UnitTestBot';
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertTrue($server->getAdapter()->getTransport()->isConnected());

        $server->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test factory connection with URI flags and query options.
     */
    public function testFactoryConnectWithOptionsAndFlags(): void
    {
        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&use_offline_as_virtual=1&no_query_clients=1&clients_before_channels=1';
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertTrue($server->getParent()->getUseOfflineAsVirtual());
        $this->assertTrue($server->getParent()->getExcludeQueryClients());
        $this->assertTrue($server->getParent()->getLoadClientlistFirst());

        $server->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test SSH connection failure simulation.
     */
    public function testSSHConnectFailure(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Connection to');

        new MockServerQuery([
            'host' => '127.0.0.1',
            'port' => 10022,
            'fail_connect' => true,
        ]);
    }

    /**
     * Test login failure simulation.
     */
    public function testLoginFailure(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Login failed: incorrect username or password');

        new MockServerQuery([
            'host' => '127.0.0.1',
            'port' => 10022,
            'fail_login' => true,
        ]);
    }

    /**
     * Test SSH prompt stripping during command response reading.
     */
    public function testSSHPromptStripping(): void
    {
        $mockQuery = new MockServerQuery([
            'host' => '127.0.0.1',
            'port' => 10022,
            'simulate_prompt' => true,
        ]);

        $reply = $mockQuery->request('whoami');
        $this->assertInstanceOf(\PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Reply::class, $reply);
        $this->assertArrayHasKey('client_nickname', $reply->toList());
        $this->assertEquals('serveradmin', $reply->toList()['client_nickname']);

        $mockQuery->getTransport()->disconnect();
    }

    /**
     * Test ANSI escape sequence filtering during SSH reading.
     */
    public function testSSHAnsiEscapeFiltering(): void
    {
        $mockQuery = new MockServerQuery([
            'host' => '127.0.0.1',
            'port' => 10022,
            'simulate_ansi' => true,
        ]);

        $reply = $mockQuery->request('version');
        $this->assertArrayHasKey('version', $reply->toList());
        $this->assertStringContainsString('3.13.7', $reply->toList()['version']);

        $mockQuery->getTransport()->disconnect();
    }

    /**
     * Test TCP transport mode simulation.
     */
    public function testTCPTransportMode(): void
    {
        $mockQuery = new MockServerQuery([
            'host' => '127.0.0.1',
            'port' => 10011,
            'transport_type' => 'tcp',
        ]);

        /** @var MockTCP $transport */
        $transport = $mockQuery->getTransport();
        $this->assertTrue($transport->isConnected());

        $reply = $mockQuery->request('whoami');
        $this->assertEquals('serveradmin', $reply->toList()['client_nickname']);

        $transport->disconnect();
        $this->assertFalse($transport->isConnected());
    }

    /**
     * Test connection lost simulation.
     */
    public function testConnectionLostSimulation(): void
    {
        $mockQuery = new MockServerQuery([
            'host' => '127.0.0.1',
            'port' => 10022,
        ]);

        /** @var MockTCP $transport */
        $transport = $mockQuery->getTransport();
        $transport->setSimulateDropConnection(true);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('lost');

        $mockQuery->request('whoami');
    }

    /**
     * Test Signal emissions for connection and data transfer.
     */
    public function testConnectionSignalsEmitted(): void
    {
        $events = [];

        Signal::getInstance()->subscribe('serverqueryConnected', function ($adapter) use (&$events) {
            $events[] = 'connected';
        });

        Signal::getInstance()->subscribe('serverqueryCommandStarted', function ($cmd) use (&$events) {
            $events[] = 'cmd_started: '.$cmd;
        });

        Signal::getInstance()->subscribe('serverqueryCommandFinished', function ($cmd, $reply) use (&$events) {
            $events[] = 'cmd_finished: '.$cmd;
        });

        Signal::getInstance()->subscribe('serverqueryDisconnected', function () use (&$events) {
            $events[] = 'disconnected';
        });

        $mockQuery = new MockServerQuery(['host' => '127.0.0.1', 'port' => '10022']);
        $mockQuery->request('whoami');
        $mockQuery->getTransport()->disconnect();

        $this->assertContains('connected', $events);
        $this->assertContains('cmd_started: whoami', $events);
        $this->assertContains('cmd_finished: whoami', $events);
        $this->assertContains('disconnected', $events);
    }

    /**
     * Test factory connection with matching fingerprint in URI.
     */
    public function testFactoryConnectWithMatchingFingerprint(): void
    {
        $rawBlob = base64_decode('AAAAB3NzaC1yc2EAAAADAQABAAABAQC3r7Yh5N1xXj1234567890abcdefghijklmnopqrstuvwxyz');
        $validFingerprint = 'SHA256:'.base64_encode(hash('sha256', $rawBlob, true));

        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&fingerprint='.urlencode($validFingerprint);
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertTrue($server->getAdapter()->getTransport()->isConnected());
        $this->assertEquals($validFingerprint, $server->getAdapter()->getTransport()->getConfig('fingerprint'));

        $server->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test factory connection with raw (unencoded plus) fingerprint in URI.
     */
    public function testFactoryConnectWithRawFingerprint(): void
    {
        $rawBlob = base64_decode('AAAAB3NzaC1yc2EAAAADAQABAAABAQC3r7Yh5N1xXj1234567890abcdefghijklmnopqrstuvwxyz');
        $validFingerprint = 'SHA256:'.base64_encode(hash('sha256', $rawBlob, true));

        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&fingerprint='.$validFingerprint;
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertTrue($server->getAdapter()->getTransport()->isConnected());

        $server->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test factory connection with Hex fingerprint in URI.
     */
    public function testFactoryConnectWithHexFingerprint(): void
    {
        $rawBlob = base64_decode('AAAAB3NzaC1yc2EAAAADAQABAAABAQC3r7Yh5N1xXj1234567890abcdefghijklmnopqrstuvwxyz');
        $hexFingerprint = hash('sha256', $rawBlob);

        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&fingerprint='.$hexFingerprint;
        $server = TeamSpeak3::factory($uri);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertTrue($server->getAdapter()->getTransport()->isConnected());

        $server->getAdapter()->getTransport()->disconnect();
    }

    /**
     * Test factory connection with mismatched fingerprint throws TransportException.
     */
    public function testFactoryConnectWithMismatchedFingerprintThrowsException(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Hostkey verification failed: The expected fingerprint does not match the server fingerprint!');

        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&fingerprint=SHA256:invalidfingerprint';
        TeamSpeak3::factory($uri);
    }

    /**
     * Test factory connection with fail_fingerprint flag throws TransportException.
     */
    public function testFactoryConnectWithFailFingerprintFlagThrowsException(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Hostkey verification failed: The expected fingerprint does not match the server fingerprint!');

        $rawBlob = base64_decode('AAAAB3NzaC1yc2EAAAADAQABAAABAQC3r7Yh5N1xXj1234567890abcdefghijklmnopqrstuvwxyz');
        $validFingerprint = 'SHA256:'.base64_encode(hash('sha256', $rawBlob, true));

        $uri = 'mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987&fingerprint='.urlencode($validFingerprint).'&fail_fingerprint=1';
        TeamSpeak3::factory($uri);
    }
}
