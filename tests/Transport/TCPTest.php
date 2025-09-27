<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Transport;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\MockServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
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
        $this->markTestSkipped('Deprecated TCP transport');

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
        $this->markTestSkipped('Deprecated TCP transport');
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'host'");

        new TCP(['port' => $this->port]);
    }

    public function testConstructorExceptionNoPort()
    {
        $this->markTestSkipped('Deprecated TCP transport');
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'port'");

        new TCP(['host' => $this->host]);
    }

    /**
     * @throws TransportException
     */
    public function testGetConfig()
    {
        $this->markTestSkipped('Deprecated TCP transport');
        $adapter = new TCP(
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
     */
    public function testGetStream()
    {
        $this->markTestSkipped('Deprecated TCP transport');
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
        $this->markTestSkipped('Deprecated TCP transport');
        return new MockServerQuery(['host' => '0.0.0.0', 'port' => 9987]);
    }

    /**
     * Tests if the connection status gets properly returned.
     * @throws AdapterException
     */
    public function testConnectionStatus()
    {
        $this->markTestSkipped('Deprecated TCP transport');
        $mockServerQuery = $this->createMockServerQuery();
        $this->assertTrue($mockServerQuery->getTransport()->isConnected());
        $mockServerQuery->getTransport()->disconnect();
        $this->assertFalse($mockServerQuery->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     */
    public function testDisconnect()
    {
        $this->markTestSkipped('Deprecated TCP transport');
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
        $this->markTestSkipped('Deprecated TCP transport');
        $transport = new TCP(
            ['host' => $this->host, 'port' => $this->port]
        );
        $this->assertNull($transport->getStream());
        $transport->disconnect();
    }
}
