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

namespace Alpari\BinaryProtocol\Field;

use Alpari\BinaryProtocol\BinaryProtocol;
use Alpari\BinaryProtocol\Stream\StringStream;
use Alpari\BinaryProtocol\StructureInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ArrayOfTest extends TestCase
{
    /**
     * @var ArrayOf
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new ArrayOf(new BinaryProtocol(), [
            'item' => [UInt32::class],
            'size' => [Int32::class]
        ]);
    }

    public function testConstructorRequiresItemOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ArrayOf expects the `item` field to be specified');
        new ArrayOf(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        // Currently, format is not determined, but later it will be possible to do N20 for array of 20 UINT32
        $this->assertEquals(null, $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        $this->field->write([1, 2, 3], $stream, '/');
        $buffer = $stream->getBuffer();
        $this->assertEquals(/* INT32 Size */4 + /* UINT32 x 3 Item */ 4 * 3, strlen($buffer));
        $this->assertEquals('03000000010000000200000003000000', bin2hex($buffer));
    }

    public function testWriteNullToNotNullable(): void
    {
        $stream = new StringStream();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value received for the array');
        $this->field->write(null, $stream, '/');
    }

    public function testWriteNullable(): void
    {
        $field = new ArrayOf(new BinaryProtocol(), [
            'item'     => [UInt32::class],
            'size'     => [Int32::class],
            'nullable' => true,
        ]);
        $stream = new StringStream();
        $field->write(null, $stream, '/');
        $this->assertEquals(bin2hex("\xFF\xFF\xFF\xFF"), bin2hex($stream->getBuffer()));
    }

    public function testWriteAssociativeArrayOfObjects(): void
    {
        $instance = new class implements StructureInterface {
            public $key;
            public $value;

            public static function getScheme(): array
            {
                return [
                    'key'   => [BinaryString::class],
                    'value' => [BinaryString::class]
                ];
            }
        };
        $instance->key   = 'test';
        $instance->value = 'value';

        $field = new ArrayOf(new BinaryProtocol(), [
            'item' => [get_class($instance)],
            'size' => [Int32::class],
            'key'  => 'key'
        ]);

        $stream = new StringStream();
        $field->write(['test' => $instance], $stream, '/');
        $this->assertEquals('01000000000474657374000576616c7565', bin2hex($stream->getBuffer()));
    }

    public function testRead(): void
    {
        $stream = new StringStream("\x02\x00\x00\x00\x01\x00\x00\x00\x02\x00\x00\x00");
        $value  = $this->field->read($stream, '/');
        $this->assertEquals([1, 2], $value);
    }

    public function testReadNullForNotNullable(): void
    {
        $stream = new StringStream("\xFF\xFF\xFF\xFF");
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Received negative array length/');
        $this->field->read($stream, '/');
    }

    public function testReadNullable(): void
    {
        $field = new ArrayOf(new BinaryProtocol(), [
            'item'     => [UInt32::class],
            'size'     => [Int32::class],
            'nullable' => true,
        ]);
        $stream = new StringStream("\xFF\xFF\xFF\xFF");
        $value  = $field->read($stream, '/');
        $this->assertEquals(null, $value);
    }

    public function testReadAssociativeArrayOfObjects(): void
    {
        $instance = new class implements StructureInterface {
            public $key;
            public $value;

            public static function getScheme(): array
            {
                return [
                    'key'   => [BinaryString::class],
                    'value' => [BinaryString::class]
                ];
            }
        };

        $anonClass = get_class($instance);
        $field     = new ArrayOf(new BinaryProtocol(), [
            'item' => [$anonClass],
            'size' => [Int32::class],
            'key'  => 'key'
        ]);

        $stream = new StringStream("\x01\x00\x00\x00\x00\x04\x74\x65\x73\x74\x00\x05\x76\x61\x6c\x75\x65");
        $value  = $field->read($stream, '/');
        $this->assertCount(1, $value);
        $this->assertArrayHasKey('test', $value);
        $this->assertInstanceOf($anonClass, $value['test']);
    }

    public function testReadAssociativeArrayForNonObjects(): void
    {
        $field = new ArrayOf(new BinaryProtocol(), [
            'item' => [UInt32::class],
            'size' => [Int32::class],
            'key'  => 'key'
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Associative array can be applied to DTOs only');

        $stream = new StringStream("\x02\x00\x00\x00\x01\x00\x00\x00\x02\x00\x00\x00");
        $field->read($stream, '/');
    }

    public function testGetSize(): void
    {
        $this->assertEquals(/* INT32 Size */4 + /* UINT32 Item */ 4, $this->field->getSize([20]));
    }

    public function testGetSizeThrowsExceptionForNonArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value received for the array');
        $this->field->getSize(1005000);
    }

    public function testGetSizeForNullable(): void
    {
        $field = new ArrayOf(new BinaryProtocol(), [
            'item'     => [UInt32::class],
            'size'     => [Int32::class],
            'nullable' => true,
        ]);
        $this->assertEquals(/* INT32 Size */4, $field->getSize(null));
    }
}
