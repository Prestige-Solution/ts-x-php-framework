<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Transport;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\TSssh;

class TSsshTest extends TestCase
{
    private string $host = '127.0.0.1';

    private string $port = '10022';

    // Sample RSA public key in OpenSSH format: "<algorithm> <base64_blob>"
    private string $sampleHostKey = 'rsa-sha2-512 AAAAB3NzaC1yc2EAAAADAQABAAABAQC3r7Yh5N1xXj1234567890abcdefghijklmnopqrstuvwxyz';

    /**
     * @throws TransportException
     */
    public function testConstructorNoException(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $this->assertInstanceOf(TSssh::class, $transport);
        $this->assertEquals($this->host, $transport->getConfig('host'));
        $this->assertEquals($this->port, $transport->getConfig('port'));
        $this->assertEquals(10, $transport->getConfig('timeout'));
        $this->assertEquals(1, $transport->getConfig('blocking'));
    }

    public function testConstructorExceptionNoHost(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'host'");

        new TSssh(['port' => $this->port]);
    }

    public function testConstructorExceptionNoPort(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("config must have a key for 'port'");

        new TSssh(['host' => $this->host]);
    }

    /**
     * @throws TransportException
     */
    public function testConstructorWithFingerprintConfig(): void
    {
        $fingerprint = 'SHA256:Dta1vhZkN87oWmXT6jgjXfFRvvLHxtxoOCr+tUR/7XY';
        $transport = new TSssh([
            'host' => $this->host,
            'port' => $this->port,
            'fingerprint' => $fingerprint,
        ]);

        $this->assertEquals($fingerprint, $transport->getConfig('fingerprint'));
        $this->assertCount(5, $transport->getConfig());
    }

    /**
     * @throws TransportException
     */
    public function testGetConfigWithoutFingerprint(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $config = $transport->getConfig();
        $this->assertIsArray($config);
        $this->assertCount(4, $config);
        $this->assertArrayNotHasKey('fingerprint', $config);
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintOpenSshFormat(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $keyParts = explode(' ', $this->sampleHostKey);
        $rawBlob = base64_decode($keyParts[1]);
        $expectedB64 = base64_encode(hash('sha256', $rawBlob, true));
        $expectedOpenSsh = 'SHA256:'.$expectedB64;

        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, $expectedOpenSsh));
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintUnpaddedOpenSshFormat(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $keyParts = explode(' ', $this->sampleHostKey);
        $rawBlob = base64_decode($keyParts[1]);
        $expectedB64Unpadded = rtrim(base64_encode(hash('sha256', $rawBlob, true)), '=');
        $expectedOpenSshUnpadded = 'SHA256:'.$expectedB64Unpadded;

        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, $expectedOpenSshUnpadded));
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintBase64Format(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $keyParts = explode(' ', $this->sampleHostKey);
        $rawBlob = base64_decode($keyParts[1]);
        $expectedB64 = base64_encode(hash('sha256', $rawBlob, true));

        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, $expectedB64));
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintUnpaddedBase64Format(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $keyParts = explode(' ', $this->sampleHostKey);
        $rawBlob = base64_decode($keyParts[1]);
        $expectedB64Unpadded = rtrim(base64_encode(hash('sha256', $rawBlob, true)), '=');

        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, $expectedB64Unpadded));
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintHexFormat(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $keyParts = explode(' ', $this->sampleHostKey);
        $rawBlob = base64_decode($keyParts[1]);
        $expectedHex = hash('sha256', $rawBlob);

        // Test lowercase hex
        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, strtolower($expectedHex)));
        // Test uppercase hex
        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, strtoupper($expectedHex)));
        // Test sha256: prefixed hex
        $this->assertTrue($transport->verifyFingerprint($this->sampleHostKey, 'sha256:'.$expectedHex));
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintWithRawBlob(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $rawBlob = 'some_raw_binary_key_data_string_for_testing';
        $expectedOpenSsh = 'SHA256:'.base64_encode(hash('sha256', $rawBlob, true));

        $this->assertTrue($transport->verifyFingerprint($rawBlob, $expectedOpenSsh));
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintMismatchThrowsException(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Hostkey verification failed: The expected fingerprint does not match the server fingerprint!');

        $transport->verifyFingerprint($this->sampleHostKey, 'SHA256:invalidfingerprintstring1234567890');
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintEmptyHostKeyThrowsException(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Host key verification failed: The servers host key could not be verified.');

        $transport->verifyFingerprint('', 'SHA256:test');
    }

    /**
     * @throws TransportException
     */
    public function testVerifyFingerprintFalseHostKeyThrowsException(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Host key verification failed: The servers host key could not be verified.');

        $transport->verifyFingerprint(false, 'SHA256:test');
    }

    /**
     * @throws TransportException
     */
    public function testDisconnectNoConnection(): void
    {
        $transport = new TSssh(['host' => $this->host, 'port' => $this->port]);
        $this->assertNull($transport->getStream());
        $transport->disconnect();
        $this->assertFalse($transport->isConnected());
    }
}
