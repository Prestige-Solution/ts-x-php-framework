<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Helper;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Convert;

class ConvertTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('UTC');
    }

    public function testConvertBytesToHumanReadableWithFactor1000()
    {
        $output = Convert::bytes(0);
        $this->assertEquals('0 B', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1000);
        $this->assertEquals('1000 B', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1000 * 1000);
        $this->assertEquals('976.5625 KiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1000 * 1000 * 1000);
        $this->assertEquals('953.6743164063 MiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1000 * 1000 * 1000 * 1000);
        $this->assertEquals('931.3225746155 GiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1000 * 1000 * 1000 * 1000 * 1000);
        $this->assertEquals('909.4947017729 TiB', $output);
        $this->assertIsString($output);
    }

    public function testConvertBytesToHumanReadableWithFactor1024()
    {
        $output = Convert::bytes(0);
        $this->assertEquals('0 B', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024);
        $this->assertEquals('1 KiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024);
        $this->assertEquals('1 MiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 * 1024);
        $this->assertEquals('1 GiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 * 1024 * 1024);
        $this->assertEquals('1 TiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 * 1024 * 1024 * 1024);
        $this->assertEquals('1 PiB', $output);
        $this->assertIsString($output);
    }

    public function testConvertBytesToHumanReadableWithOddNumbers()
    {
        $output = Convert::bytes(1);
        $this->assertEquals('1 B', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 + 256);
        $this->assertEquals('1.25 KiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 + 256);
        $this->assertEquals('1.0002441406 MiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 * 1024 + 256);
        $this->assertEquals('1.0000002384 GiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 * 1024 * 1024 + 256);
        $this->assertEquals('1.0000000002 TiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(1024 * 1024 * 1024 * 1024 * 1024 + (256 * 1024 * 1024 * 1024));
        $this->assertEquals('1.0002441406 PiB', $output);
        $this->assertIsString($output);
    }

    public function testConvertBytesToHumanReadableWithNegativeNumbers()
    {
        $output = Convert::bytes(0);
        $this->assertEquals('0 B', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1000);
        $this->assertEquals('-1000 B', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1024);
        $this->assertEquals('-1 KiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1000 * 1000);
        $this->assertEquals('-976.5625 KiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1000 * 1000 * 1000);
        $this->assertEquals('-953.6743164063 MiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1024 * 1024);
        $this->assertEquals('-1 MiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1024 * 1024 * 1024);
        $this->assertEquals('-1 GiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1024 * 1024 * 1024 * 1024);
        $this->assertEquals('-1 TiB', $output);
        $this->assertIsString($output);

        $output = Convert::bytes(-1024 * 1024 * 1024 * 1024 - 256);
        $this->assertEquals('-1.0000000002 TiB', $output);
        $this->assertIsString($output);
    }

    public function testConvertSecondsToHumanReadable()
    {
        $output = Convert::seconds(0);
        $this->assertEquals('0D 00:00:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(1);
        $this->assertEquals('0D 00:00:01', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(59);
        $this->assertEquals('0D 00:00:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(60);
        $this->assertEquals('0D 00:01:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('59 minutes 59 seconds',0));
        $this->assertEquals('0D 00:59:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('1 hours',0));
        $this->assertEquals('0D 01:00:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('23 hours 59 minutes 59 seconds', 0));
        $this->assertEquals('0D 23:59:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('1 day', 0));
        $this->assertEquals('1D 00:00:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('1 day 23 hours 59 minutes 59 seconds', 0));
        $this->assertEquals('1D 23:59:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(90.083);
        $this->assertEquals('0D 00:01:30', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(-0);
        $this->assertEquals('0D 00:00:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(-1);
        $this->assertEquals('-0D 00:00:01', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(-59);
        $this->assertEquals('-0D 00:00:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(-60);
        $this->assertEquals('-0D 00:01:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('59 minutes 59 seconds ago', 0));
        $this->assertEquals('-0D 00:59:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('1 hour ago', 0));
        $this->assertEquals('-0D 01:00:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('23 hours 59 minutes 59 seconds ago', 0));
        $this->assertEquals('-0D 23:59:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('1 day ago', 0));
        $this->assertEquals('-1D 00:00:00', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(strtotime('1 day 23 hours 59 minutes 59 seconds ago', 0));
        $this->assertEquals('-1D 23:59:59', $output);
        $this->assertIsString($output);

        $output = Convert::seconds(-90.083);
        $this->assertEquals('-0D 00:01:30', $output);
        $this->assertIsString($output);
    }

    public function testConvertCodecIDToHumanReadable()
    {
        $hexArray = [
            strval(0x00),
            strval(0x01),
            strval(0x02),
            strval(0x03),
            strval(0x04),
            strval(0x05),
        ];

        $this->assertEquals('Speex Narrowband', Convert::codec($hexArray[0]));
        $this->assertEquals('Speex Wideband', Convert::codec($hexArray[1]));
        $this->assertEquals('Speex Ultra-Wideband', Convert::codec($hexArray[2]));
        $this->assertEquals('CELT Mono', Convert::codec($hexArray[3]));
        $this->assertEquals('Opus Voice', Convert::codec($hexArray[4]));
        $this->assertEquals('Opus Music', Convert::codec($hexArray[5]));
        $this->assertEquals('Unknown', Convert::codec(hexdec(0x99)));
    }

    public function testConvertGroupTypeIDToHumanReadable()
    {
        $hexArray = [
            strval(0x00),
            strval(0x01),
            strval(0x02),
        ];

        $this->assertEquals('Template', Convert::groupType($hexArray[0]));
        $this->assertEquals('Regular', Convert::groupType($hexArray[1]));
        $this->assertEquals('ServerQuery', Convert::groupType($hexArray[2]));
        $this->assertEquals('Unknown', Convert::groupType(hexdec(0x99)));
    }

    public function testConvertPermTypeIDToHumanReadable()
    {
        $hexArray = [
            strval(0x00),
            strval(0x01),
            strval(0x02),
            strval(0x03),
            strval(0x04),
        ];

        $this->assertEquals('Server Group', Convert::permissionType($hexArray[0]));
        $this->assertEquals('Client', Convert::permissionType($hexArray[1]));
        $this->assertEquals('Channel', Convert::permissionType($hexArray[2]));
        $this->assertEquals('Channel Group', Convert::permissionType($hexArray[3]));
        $this->assertEquals('Channel Client', Convert::permissionType($hexArray[4]));
        $this->assertEquals('Unknown', Convert::permissionType(hexdec(0x99)));
    }

    public function testConvertPermCategoryIDToHumanReadable()
    {
        $hexArrayIssue = [
            strval(0x10),
            strval(0x11),
            strval(0x12),
            strval(0x13),
            strval(0x14),
            strval(0x20),
            strval(0x21),
            strval(0x22),
            strval(0x23),
            strval(0x30),
            strval(0x31),
            strval(0x32),
            strval(0x33),
            strval(0x34),
            strval(0x35),
            strval(0x40),
            strval(0x41),
            strval(0x42),
            strval(0x43),
            strval(0x44),
            strval(0x50),
            strval(0x51),
            strval(0x52),
            strval(0x53),
            strval(0x54),
            strval(0x60),
            strval(0xFF),
        ];

        $this->assertEquals('Global', Convert::permissionCategory($hexArrayIssue[0]));
        $this->assertEquals('Global / Information', Convert::permissionCategory($hexArrayIssue[1]));
        $this->assertEquals('Global / Virtual Server Management', Convert::permissionCategory($hexArrayIssue[2]));
        $this->assertEquals('Global / Administration', Convert::permissionCategory($hexArrayIssue[3]));
        $this->assertEquals('Global / Settings', Convert::permissionCategory($hexArrayIssue[4]));
        $this->assertEquals('Virtual Server', Convert::permissionCategory($hexArrayIssue[5]));
        $this->assertEquals('Virtual Server / Information', Convert::permissionCategory($hexArrayIssue[6]));
        $this->assertEquals('Virtual Server / Administration', Convert::permissionCategory($hexArrayIssue[7]));
        $this->assertEquals('Virtual Server / Settings', Convert::permissionCategory($hexArrayIssue[8]));
        $this->assertEquals('Channel', Convert::permissionCategory($hexArrayIssue[9]));
        $this->assertEquals('Channel / Information', Convert::permissionCategory($hexArrayIssue[10]));
        $this->assertEquals('Channel / Create', Convert::permissionCategory($hexArrayIssue[11]));
        $this->assertEquals('Channel / Modify', Convert::permissionCategory($hexArrayIssue[12]));
        $this->assertEquals('Channel / Delete', Convert::permissionCategory($hexArrayIssue[13]));
        $this->assertEquals('Channel / Access', Convert::permissionCategory($hexArrayIssue[14]));
        $this->assertEquals('Group', Convert::permissionCategory($hexArrayIssue[15]));
        $this->assertEquals('Group / Information', Convert::permissionCategory($hexArrayIssue[16]));
        $this->assertEquals('Group / Create', Convert::permissionCategory($hexArrayIssue[17]));
        $this->assertEquals('Group / Modify', Convert::permissionCategory($hexArrayIssue[18]));
        $this->assertEquals('Group / Delete', Convert::permissionCategory($hexArrayIssue[19]));
        $this->assertEquals('Client', Convert::permissionCategory($hexArrayIssue[20]));
        $this->assertEquals('Client / Information', Convert::permissionCategory($hexArrayIssue[21]));
        $this->assertEquals('Client / Admin', Convert::permissionCategory($hexArrayIssue[22]));
        $this->assertEquals('Client / Basics', Convert::permissionCategory($hexArrayIssue[23]));
        $this->assertEquals('Client / Modify', Convert::permissionCategory($hexArrayIssue[24]));
        $this->assertEquals('File Transfer', Convert::permissionCategory($hexArrayIssue[25]));
        $this->assertEquals('Grant', Convert::permissionCategory($hexArrayIssue[26]));
        $this->assertEquals('Unknown', Convert::permissionCategory(hexdec(0x99)));
    }

    public function testConvertLogLevelIDToHumanReadable()
    {
        $hexArray = [
            strval(0x00),
            strval(0x01),
            strval(0x02),
            strval(0x03),
            strval(0x04),
            strval(0x05),
        ];

        $this->assertEquals('CRITICAL', Convert::logLevel($hexArray[0]));
        $this->assertEquals('ERROR', Convert::logLevel($hexArray[1]));
        $this->assertEquals('WARNING', Convert::logLevel($hexArray[2]));
        $this->assertEquals('DEBUG', Convert::logLevel($hexArray[3]));
        $this->assertEquals('INFO', Convert::logLevel($hexArray[4]));
        $this->assertEquals('DEVELOP', Convert::logLevel($hexArray[5]));

        $stringArray = [
            'critical',
            'error',
            'warning',
            'debug',
            'info',
        ];

        $this->assertEquals(0x00, Convert::logLevel($stringArray[0]));
        $this->assertEquals(0x01, Convert::logLevel($stringArray[1]));
        $this->assertEquals(0x02, Convert::logLevel($stringArray[2]));
        $this->assertEquals(0x03, Convert::logLevel($stringArray[3]));
        $this->assertEquals(0x04, Convert::logLevel($stringArray[4]));
        $this->assertEquals(0x05, Convert::logLevel('Unknown'));
    }

    public function testConvertLogEntryToArray()
    {
        // @todo: Implement matching integration test for testing real log entries
        $mock_data = [
            '2017-06-26 21:55:30.307009|INFO    |Query         |   |query from 47 [::1]:62592 issued: login with account "serveradmin"(serveradmin)',
        ];

        foreach ($mock_data as $entry) {
            $entryParsed = Convert::logEntry($entry);
            $this->assertFalse(
                $entryParsed['malformed'],
                'Log entry appears malformed, dumping: '.print_r($entryParsed, true)
            );
        }
    }

    public function testConvertToPassword()
    {
        $this->assertEquals(
            'W6ph5Mm5Pz8GgiULbPgzG37mj9g=',
            Convert::password('password')
        );
    }

    public function testConvertVersionToClientFormat()
    {
        $this->assertEquals(
            '3.0.13.6 (2016-11-08 08:48:33)',
            Convert::version('3.0.13.6 [Build: 1478594913]')->toString()
        );
    }

    public function testConvertVersionShortToClientFormat()
    {
        $this->assertEquals(
            '3.0.13.6',
            Convert::versionShort('3.0.13.6 [Build: 1478594913]')
        );
    }

    public function testDetectImageMimeType()
    {
        // Test image binary base64 encoded is 1px by 1px GIF
        $this->assertEquals(
            'image/gif',
            Convert::imageMimeType(
                base64_decode('R0lGODdhAQABAIAAAPxqbAAAACwAAAAAAQABAAACAkQBADs=')
            )
        );
    }
}
