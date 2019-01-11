<?php
/*
 * This file is part of the Alpari BinaryProtocol library.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Alpari\BinaryProtocol\Type;

use Alpari\BinaryProtocol\BinaryProtocol;
use Alpari\BinaryProtocol\Stream\StringStream;
use Alpari\BinaryProtocol\SchemeDefinitionInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BinaryStringTest extends TestCase
{
    /**
     * @var BinaryString
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new BinaryString(new BinaryProtocol(), [
            'size' => [Int32::class]
        ]);
    }

    public function testGetFormat(): void
    {
        // Currently, format is not determined, but later it will be possible to do N20 for array of 20 UINT32
        $this->assertEquals(null, $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        $this->field->write('abc', $stream, '/');
        $buffer = $stream->getBuffer();
        $this->assertEquals(/* INT32 Length */4 + /* 3 Char */ 3, strlen($buffer));
        $this->assertEquals('03000000616263', bin2hex($buffer));
    }

    public function testWriteNullToNotNullable(): void
    {
        $stream = new StringStream();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value received for the string');
        $this->field->write(null, $stream, '/');
    }

    public function testWriteNullable(): void
    {
        $field = new BinaryString(new BinaryProtocol(), [
            'size'     => [Int32::class],
            'nullable' => true,
        ]);
        $stream = new StringStream();
        $field->write(null, $stream, '/');
        $this->assertEquals(bin2hex("\xFF\xFF\xFF\xFF"), bin2hex($stream->getBuffer()));
    }

    public function testWriteEnvelopeToStringBuffer(): void
    {
        $instance = new class implements SchemeDefinitionInterface {
            public $key;
            public $value;

            public static function getDefinition(): array
            {
                return [
                    'key'   => [BinaryString::class],
                    'value' => [BinaryString::class]
                ];
            }
        };
        $instance->key   = 'test';
        $instance->value = 'value';

        $field = new BinaryString(new BinaryProtocol(), [
            'size'     => [Int32::class],
            'envelope' => [get_class($instance)]
        ]);

        $stream = new StringStream();
        $field->write($instance, $stream, '/');
        $this->assertEquals('0d000000000474657374000576616c7565', bin2hex($stream->getBuffer()));
    }

    public function testRead(): void
    {
        $stream = new StringStream("\x03\x00\x00\x00\x61\x62\x63");
        $value  = $this->field->read($stream, '/');
        $this->assertEquals('abc', $value);
    }

    public function testReadNullForNotNullable(): void
    {
        $stream = new StringStream("\xFF\xFF\xFF\xFF");
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Received negative string length/');
        $this->field->read($stream, '/');
    }

    public function testReadNullable(): void
    {
        $field = new BinaryString(new BinaryProtocol(), [
            'size'     => [Int32::class],
            'nullable' => true,
        ]);
        $stream = new StringStream("\xFF\xFF\xFF\xFF");
        $value  = $field->read($stream, '/');
        $this->assertEquals(null, $value);
    }

    public function testReadEnvelopeFromString(): void
    {
        $instance = new class implements SchemeDefinitionInterface {
            public $key;
            public $value;

            public static function getDefinition(): array
            {
                return [
                    'key'   => [BinaryString::class],
                    'value' => [BinaryString::class]
                ];
            }
        };

        $anonClass = get_class($instance);
        $field     = new BinaryString(new BinaryProtocol(), [
            'size'     => [Int32::class],
            'envelope' => [$anonClass]
        ]);

        $stream = new StringStream("\x0D\x00\x00\x00\x00\x04\x74\x65\x73\x74\x00\x05\x76\x61\x6c\x75\x65");
        $value  = $field->read($stream, '/');
        $this->assertInstanceOf($anonClass, $value);
        $this->assertEquals('test', $value->key);
        $this->assertEquals('value', $value->value);
    }

    public function testGetSize(): void
    {
        $this->assertEquals(/* INT32 Length */4 + /* Char x 4 */ 4, $this->field->getSize('test'));
    }

    public function testGetSizeThrowsExceptionForNonStrings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value received for the string');
        $this->field->getSize([]);
    }

    public function testGetSizeForNullable(): void
    {
        $field = new BinaryString(new BinaryProtocol(), [
            'size'     => [Int32::class],
            'nullable' => true,
        ]);
        $this->assertEquals(/* INT32 Length */4, $field->getSize(null));
    }

    public function testGetSizeForEnvelope(): void
    {
        $instance = new class implements SchemeDefinitionInterface {
            public $key;
            public $value;

            public static function getDefinition(): array
            {
                return [
                    'key'   => [BinaryString::class => ['size' => [Int8::class]]],
                    'value' => [BinaryString::class => ['size' => [Int8::class]]]
                ];
            }
        };
        $instance->key   = 'test';
        $instance->value = 'value';

        $field = new BinaryString(new BinaryProtocol(), [
            'size'     => [Int32::class],
            'envelope' => [get_class($instance)]
        ]);
        $expectedLength =
            /* INT32 Length */ 4 +
            /* INT8 Key Length */ 1 +
            /* 'test' length */ 4 +
            /* INT8 Value Length */ 1 +
            /* 'value' Length */ 5;

        $this->assertEquals($expectedLength, $field->getSize($instance));
    }
}
