<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Transport;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\UDP;

class UDPTest extends TestCase
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
        $adapter = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertInstanceOf(UDP::class, $adapter);

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

        new UDP(['port' => $this->port]);
    }

    public function testConstructorExceptionNoPort()
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'port'");

        new UDP(['host' => $this->host]);
    }

    /**
     * @throws TransportException
     */
    public function testGetConfig()
    {
        $adapter = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );

        $this->assertIsArray($adapter->getConfig());
        $this->assertCount(4, $adapter->getConfig());
        $this->assertArrayHasKey('host', $adapter->getConfig());
        $this->assertEquals($this->host, $adapter->getConfig()['host']);
        $this->assertEquals($this->host, $adapter->getConfig('host'));
    }

    /**
     * @throws TransportException
     * @throws Exception
     */
    public function testSetGetAdapter()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        // Mocking adaptor since `stream_socket_client()` depends on running server
        $adaptor = $this->createMock(ServerQuery::class);
        $transport->setAdapter($adaptor);

        $this->assertSame($adaptor, $transport->getAdapter());
    }

    /**
     * @throws TransportException
     */
    public function testGetStream()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertNull($transport->getStream());
    }

    /**
     * @throws TransportException
     */
    public function testConnect()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $transport->connect();
        $this->assertIsResource($transport->getStream());
    }

    /**
     * @throws TransportException
     */
    public function testConnectBadHost()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->expectException(TransportException::class);
        if (PHP_VERSION_ID < 80100) {
            $this->expectExceptionMessage('getaddrinfo failed');
        } else {
            $this->expectExceptionMessage("getaddrinfo for $this->host failed");
        }
        $transport->connect();
    }

    /**
     * @throws TransportException
     */
    public function testDisconnect()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $transport->connect();
        $this->assertIsResource($transport->getStream());
        $transport->disconnect();
        $this->assertNull($transport->getStream());
    }

    /**
     * @throws TransportException
     */
    public function testDisconnectNoConnection()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertNull($transport->getStream());
        $transport->disconnect();
    }

    /**
     * @throws TransportException
     */
    public function testReadNoConnection()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->expectException(TransportException::class);
        if (PHP_VERSION_ID < 80100) {
            $this->expectExceptionMessage('getaddrinfo failed');
        } else {
            $this->expectExceptionMessage("getaddrinfo for $this->host failed");
        }
        $transport->read();
    }

    /**
     * @throws TransportException
     */
    public function testSendNoConnection()
    {
        $transport = new UDP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->expectException(TransportException::class);
        if (PHP_VERSION_ID < 80100) {
            $this->expectExceptionMessage('getaddrinfo failed');
        } else {
            $this->expectExceptionMessage("getaddrinfo for $this->host failed");
        }
        $transport->send('test.send');
    }
}
