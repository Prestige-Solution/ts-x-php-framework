<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Helper;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Char;

class CharTest extends TestCase
{
    /**
     * @throws HelperException
     */
    public function testASCIILetter()
    {
        $testLower = chr(97);
        $testUpper = chr(65);
        $testOrd = 97;
        $char = new Char(chr(97));

        $this->assertTrue($char->isLetter());
        $this->assertTrue($char->isPrintable());
        $this->assertTrue($char->isLower());

        $this->assertFalse($char->isDigit());
        $this->assertFalse($char->isSpace());
        $this->assertFalse($char->isMark());
        $this->assertFalse($char->isControl());
        $this->assertFalse($char->isNull());
        $this->assertFalse($char->isUpper());

        $this->assertEquals($testLower, (string) $char);  // Expect: 'a'
        $this->assertEquals($testUpper, (string) $char->toUpper()); // Expect: 'A'
        $this->assertEquals($testLower, (string) $char->toLower()); // Expect: 'a'

        $this->assertEquals($testOrd, $char->toAscii()); // Expect: 97
        $this->assertEquals($testOrd, hexdec($char->toHex())); // hexdec('61')
        $this->assertEquals(
            $testLower,
            (string) Char::fromHex('61')
        );

        $this->assertEquals($testLower, $char->toString()); // Expect: 97
        $this->assertIsString($char->toString());
        $this->assertEquals((int) $testLower, $char->toInt()); // Expect: 97
        $this->assertIsInt($char->toInt());
    }

    /**
     * @throws HelperException
     */
    public function testASCIIDigit()
    {
        $testChar = chr(57);
        $testOrd = 57;
        $char = new Char($testChar); // (ASCII) '9'

        $this->assertTrue($char->isDigit());
        $this->assertTrue($char->isPrintable());
        $this->assertTrue($char->isUpper());
        $this->assertTrue($char->isLower());

        $this->assertFalse($char->isLetter());
        $this->assertFalse($char->isSpace());
        $this->assertFalse($char->isMark());
        $this->assertFalse($char->isControl());
        $this->assertFalse($char->isNull());

        $this->assertEquals($testChar, (string) $char);  // Expect: '9'
        $this->assertEquals($testChar, (string) $char->toUpper()); // Expect: '9'
        $this->assertEquals($testChar, (string) $char->toLower()); // Expect: '9'

        $this->assertEquals($testOrd, $char->toAscii()); // Expect: 57
        $this->assertEquals($testOrd, hexdec($char->toHex())); // hexdec('39')
        $this->assertEquals(
            $testChar,
            (string) Char::fromHex('39')
        );

        $this->assertEquals($testChar, $char->toString()); // Expect: 57
        $this->assertIsString($char->toString());
        $this->assertEquals((int) $testChar, $char->toInt()); // Expect: 57
        $this->assertIsInt($char->toInt());
    }

    /**
     * @throws HelperException
     */
    public function testASCIISpace()
    {
        $testChar = chr(32);
        $testOrd = 32;
        $char = new Char($testChar); // (ASCII) ' '

        $this->assertTrue($char->isSpace());
        $this->assertTrue($char->isPrintable());
        $this->assertTrue($char->isUpper());
        $this->assertTrue($char->isLower());

        $this->assertFalse($char->isLetter());
        $this->assertFalse($char->isDigit());
        $this->assertFalse($char->isMark());
        $this->assertFalse($char->isControl());
        $this->assertFalse($char->isNull());

        $this->assertEquals($testChar, (string) $char);  // Expect: ' '
        $this->assertEquals($testChar, (string) $char->toUpper()); // Expect: ' '
        $this->assertEquals($testChar, (string) $char->toLower()); // Expect: ' '

        $this->assertEquals($testOrd, $char->toAscii()); // Expect: 32
        $this->assertEquals($testOrd, hexdec($char->toHex())); // hexdec('20')
        $this->assertEquals(
            $testChar,
            (string) Char::fromHex('20')
        );

        $this->assertEquals($testChar, $char->toString()); // Expect: 32
        $this->assertIsString($char->toString());
        $this->assertEquals((int) $testChar, $char->toInt()); // Expect: 32
        $this->assertIsInt($char->toInt());
    }

    /**
     * @throws HelperException
     */
    public function testASCIIMark()
    {
        $testChar = chr(45);
        $testOrd = 45;
        $char = new Char($testChar); // (ASCII) '-'

        $this->assertTrue($char->isMark());
        $this->assertTrue($char->isPrintable());
        $this->assertTrue($char->isLower());
        $this->assertTrue($char->isUpper());

        $this->assertFalse($char->isLetter());
        $this->assertFalse($char->isDigit());
        $this->assertFalse($char->isSpace());
        $this->assertFalse($char->isControl());
        $this->assertFalse($char->isNull());

        $this->assertEquals($testChar, (string) $char);  // Expect: '-'
        $this->assertEquals($testChar, (string) $char->toUpper()); // Expect: '-'
        $this->assertEquals($testChar, (string) $char->toLower()); // Expect: '-'

        $this->assertEquals($testOrd, $char->toAscii()); // Expect: 45
        $this->assertEquals($testOrd, hexdec($char->toHex())); // hexdec('2d')
        $this->assertEquals(
            $testChar,
            (string) Char::fromHex('2d')
        );

        $this->assertEquals($testChar, $char->toString()); // Expect: 45
        $this->assertIsString($char->toString());
        $this->assertEquals((int) $testChar, $char->toInt()); // Expect: 45
        $this->assertIsInt($char->toInt());
    }

    /**
     * @throws HelperException
     */
    public function testASCIIControl()
    {
        $testChar = chr(6);
        $testOrd = 6;
        $char = new Char($testChar); // (ASCII) [ACK]

        $this->assertTrue($char->isControl());
        $this->assertTrue($char->isLower());
        $this->assertTrue($char->isUpper());

        $this->assertFalse($char->isLetter());
        $this->assertFalse($char->isDigit());
        $this->assertFalse($char->isSpace());
        $this->assertFalse($char->isMark());
        $this->assertFalse($char->isPrintable());
        $this->assertFalse($char->isNull());

        $this->assertEquals($testChar, (string) $char);  // Expect: [ACK]
        $this->assertEquals($testChar, (string) $char->toUpper()); // Expect: [ACK]
        $this->assertEquals($testChar, (string) $char->toLower()); // Expect: [ACK]

        $this->assertEquals($testOrd, $char->toAscii()); // Expect: 6
        $this->assertEquals($testOrd, hexdec($char->toHex())); // hexdec('06')
        $this->assertEquals(
            $testChar,
            (string) Char::fromHex('06')
        );

        $this->assertEquals($testChar, $char->toString()); // Expect: 6
        $this->assertIsString($char->toString());
        $this->assertEquals((int) $testChar, $char->toInt()); // Expect: 6
        $this->assertIsInt($char->toInt());
    }

    /**
     * @throws HelperException
     */
    public function testASCIINull()
    {
        $testChar = chr(0);
        $testOrd = 0;
        $char = new Char($testChar); // (ASCII) [NUL]

        $this->assertTrue($char->isControl());
        $this->assertTrue($char->isNull());
        $this->assertTrue($char->isLower());
        $this->assertTrue($char->isUpper());

        $this->assertFalse($char->isLetter());
        $this->assertFalse($char->isDigit());
        $this->assertFalse($char->isSpace());
        $this->assertFalse($char->isMark());
        $this->assertFalse($char->isPrintable());

        $this->assertEquals($testChar, (string) $char);  // Expect: [NUL]
        $this->assertEquals($testChar, (string) $char->toUpper()); // Expect: [NUL]
        $this->assertEquals($testChar, (string) $char->toLower()); // Expect: [NUL]

        $this->assertEquals($testOrd, $char->toAscii()); // Expect: 0
        $this->assertEquals($testOrd, hexdec($char->toHex())); // hexdec('00')
        $this->assertEquals(
            $testChar,
            (string) Char::fromHex('00')
        );

        $this->assertEquals($testChar, $char->toString()); // Expect: 0
        $this->assertIsString($char->toString());
        $this->assertEquals((int) $testChar, $char->toInt()); // Expect: 0
        $this->assertIsInt($char->toInt());
    }

    /**
     * @throws HelperException
     */
    public function testUnicode1Byte()
    {
        // Arbitrary value: first lowercase letter from English alphabet
        // (hex) "\x61": (ASCII) 'a'
        $this->assertEquals(
            static::calculateUTF8Ordinal("\x61"),
            Char::fromHex('61')->toUnicode()
        );

        // Lower bound: first available character
        // (hex) "\x00": (ASCII) 'NUL' (non-printable control character)
        $this->assertEquals(
            static::calculateUTF8Ordinal("\x00"),
            Char::fromHex('00')->toUnicode()
        );

        // Upper bound: last available character
        // (hex) "\x7F": (ASCII) 'DEL'
        $this->assertEquals(
            static::calculateUTF8Ordinal("\x7F"),
            Char::fromHex('7F')->toUnicode()
        );
    }

    /**
     * Return integer value of a string, specifically for UTF8 strings.
     *
     * @param string $char
     *
     * @return int
     */
    private static function calculateUTF8Ordinal(string $char): int
    {
        $bytes = array_map('ord', str_split($char));
        $length = strlen($char);

        if ($length === 1) {
            // 1-byte (ASCII)
            return $bytes[0];
        } elseif ($length === 2) {
            // 2-byte
            return (($bytes[0] & 0x1F) << 6) |
                ($bytes[1] & 0x3F);
        } elseif ($length === 3) {
            // 3-byte
            return (($bytes[0] & 0x0F) << 12) |
                (($bytes[1] & 0x3F) << 6) |
                ($bytes[2] & 0x3F);
        } elseif ($length === 4) {
            // 4-byte
            return (($bytes[0] & 0x07) << 18) |
                (($bytes[1] & 0x3F) << 12) |
                (($bytes[2] & 0x3F) << 6) |
                ($bytes[3] & 0x3F);
        }

        // invalid UTF-8 (longer than 4 bytes)
        return -1;
    }
}
