<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Transport;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\MockServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\TCP;

class TCPTest extends TestCase
{
    private string $host;

    private string $port;

    public function setUp(): void
    {
        if (file_exists('./.env.testing')) {
            $env = file('./.env.testing');

            $this->host = str_replace('HOST=', '', preg_replace('#\n(?!\n)#', '', $env[0]));
            $this->port = str_replace('PORT=', '', preg_replace('#\n(?!\n)#', '', $env[1]));
        }
    }

    /**
     * @throws TransportException
     */
    public function testConstructorNoException()
    {
        $adapter = new TCP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertInstanceOf(TCP::class, $adapter);

        $this->assertArrayHasKey('host', $adapter->getConfig());
        $this->assertEquals($this->host, $adapter->getConfig('host'));

        $this->assertArrayHasKey('port', $adapter->getConfig());
        $this->assertEquals($this->port, $adapter->getConfig('port'));

        $this->assertArrayHasKey('timeout', $adapter->getConfig());
        $this->assertIsInt($adapter->getConfig('timeout'));

        $this->assertArrayHasKey('blocking', $adapter->getConfig());
        $this->assertIsInt($adapter->getConfig('blocking'));
    }

    public function testConstructorExceptionNoHost()
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'host'");

        new TCP(['port' => $this->port]);
    }

    public function testConstructorExceptionNoPort()
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'port'");

        new TCP(['host' => $this->host]);
    }

    /**
     * @throws TransportException
     */
    public function testGetConfig()
    {
        $adapter = new TCP(
            ['host' => $this->host, 'port' => $this->port]
        );

        $this->assertIsArray($adapter->getConfig());
        $this->assertCount(4, $adapter->getConfig());
        $this->assertArrayHasKey('host', $adapter->getConfig());
        $this->assertEquals($this->host, $adapter->getConfig()['host']);
        $this->assertEquals($this->host, $adapter->getConfig('host'));
    }

    public function testSetGetAdapter()
    {
        $this->markTestSkipped();
//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );
//        // Mocking adaptor since `stream_socket_client()` depends on running server
//        $adaptor = $this->createMock(ServerQuery::class);
//        $transport->setAdapter($adaptor);
//
//        $this->assertSame($adaptor, $transport->getAdapter());
    }

    /**
     * @throws TransportException
     */
    public function testGetStream()
    {
        $transport = new TCP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertNull($transport->getStream());
    }

    /**
     * @throws AdapterException
     */
    protected function createMockServerQuery(): MockServerQuery
    {
        return new MockServerQuery(['host' => '0.0.0.0', 'port' => 9987]);
    }

    /**
     * Tests if the connection status gets properly returned.
     * @throws AdapterException
     */
    public function testConnectionStatus()
    {
        $mockServerQuery = $this->createMockServerQuery();
        $this->assertTrue($mockServerQuery->getTransport()->isConnected());
        $mockServerQuery->getTransport()->disconnect();
        $this->assertFalse($mockServerQuery->getTransport()->isConnected());
    }

    public function testConnectBadHost()
    {
        $this->markTestSkipped();

//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );
//        $this->expectException(TransportException::class);
//        if (PHP_VERSION_ID < 80100) {
//            $this->expectExceptionMessage('getaddrinfo failed');
//        } else {
//            //TODO Not sure how to handle different languages
//            //TODO handle different expectExceptionMessages
//            //$this->expectExceptionMessage("getaddrinfo for $this->host failed");
//            $this->expectExceptionMessage('Es konnte keine Verbindung hergestellt werden, da der Zielcomputer die Verbindung verweigerte');
//        }
//        $transport->connect();
    }

    public function testConnectHostRefuseConnection()
    {
        $this->markTestSkipped();

//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );

//        $this->expectException(TransportException::class);
        //TODO Not sure how to handle different languages
        //TODO handle different expectExceptionMessages
        //$this->expectExceptionMessage('Connection refused');
//        $this->expectExceptionMessage('Es konnte keine Verbindung hergestellt werden, da der Zielcomputer die Verbindung verweigerte');
//        $transport->connect();
    }

    /**
     * @throws TransportException
     */
    public function testDisconnect()
    {
        $transport = new TCP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $transport->disconnect();
        $this->assertNull($transport->getStream());
    }

    /**
     * @throws TransportException
     */
    public function testDisconnectNoConnection()
    {
        $transport = new TCP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertNull($transport->getStream());
        $transport->disconnect();
    }

    public function testReadNoConnection()
    {
        $this->markTestSkipped();
//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );
//        $this->expectException(TransportException::class);
//        if (PHP_VERSION_ID < 80100) {
//            $this->expectExceptionMessage('getaddrinfo failed');
//        } else {
//            //TODO Not sure how to handle different languages
//            //TODO handle different expectExceptionMessages
//            //$this->expectExceptionMessage("getaddrinfo for $this->port failed");
//            $this->expectExceptionMessage('Es konnte keine Verbindung hergestellt werden, da der Zielcomputer die Verbindung verweigerte');
//        }
//        $transport->read();
    }

    public function testReadLineNoConnection()
    {
        $this->markTestSkipped();
//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );
//        $this->expectException(TransportException::class);
//        if (PHP_VERSION_ID < 80100) {
//            $this->expectExceptionMessage('getaddrinfo failed');
//        } else {
//            //TODO Not sure how to handle different languages
//            //TODO handle different expectExceptionMessages
//            //$this->expectExceptionMessage("getaddrinfo for $this->host failed");
//            $this->expectExceptionMessage('Es konnte keine Verbindung hergestellt werden, da der Zielcomputer die Verbindung verweigerte');
//        }
//        $transport->readLine();
    }

    public function testSendNoConnection()
    {
        $this->markTestSkipped();
//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );
//        $this->expectException(TransportException::class);
//        if (PHP_VERSION_ID < 80100) {
//            $this->expectExceptionMessage('getaddrinfo failed');
//        } else {
//            //TODO Not sure how to handle different languages
//            //TODO handle different expectExceptionMessages
//            //$this->expectExceptionMessage("getaddrinfo for $this->host failed");
//            $this->expectExceptionMessage('Es konnte keine Verbindung hergestellt werden, da der Zielcomputer die Verbindung verweigerte');
//        }
//        $transport->send('testsend');
    }

    public function testSendLineNoConnection()
    {
        $this->markTestSkipped();
//        $transport = new TCP(
//            ['host' => $this->host, 'port' => $this->port]
//        );
//        $this->expectException(TransportException::class);
//        if (PHP_VERSION_ID < 80100) {
//            $this->expectExceptionMessage('getaddrinfo failed');
//        } else {
//            //TODO Not sure how to handle different languages
//            //TODO handle different expectExceptionMessages
//            //$this->expectExceptionMessage("getaddrinfo for $this->host failed");
//            $this->expectExceptionMessage('Es konnte keine Verbindung hergestellt werden, da der Zielcomputer die Verbindung verweigerte');
//        }
//        $transport->sendLine('test.sendLine');
    }
}
