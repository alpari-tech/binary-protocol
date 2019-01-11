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

namespace Alpari\BinaryProtocol;

use Alpari\BinaryProtocol\Type\BinaryString;
use Alpari\BinaryProtocol\Type\UInt8;
use Alpari\BinaryProtocol\Stream\StringStream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BinaryProtocolTest extends TestCase
{
    /**
     * @var BinaryProtocolInterface
     */
    private $protocol;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->protocol = new BinaryProtocol();
    }

    public function testCanReadDataFromStream(): void
    {
        $stream = new StringStream("\x20");
        $value = $this->protocol->read([UInt8::class], $stream);
        $this->assertEquals(0x20, $value);
        $this->assertEmpty($stream->getBuffer());
    }

    public function testCanWriteDataToStream(): void
    {
        $stream = new StringStream();
        $this->protocol->write(0x20, [UInt8::class], $stream);
        $this->assertEquals("\x20", $stream->getBuffer());
    }

    public function testCanWriteLazyFieldToStream(): void
    {
        $stream    = new StringStream();
        $lazyValue = function () {
            // we can initialize property here, calculate CRC, anything else
            return 42;
        };
        $this->protocol->write($lazyValue, [UInt8::class], $stream);
        $this->assertEquals("\x2A", $stream->getBuffer());
    }

    public function testCanGetDataSize(): void
    {
        $size = $this->protocol->sizeOf(0x20, [UInt8::class]);
        $this->assertEquals(1, $size);
    }

    public function testCanGetLazyFieldDataSize(): void
    {
        $lazyValue = function () {
            // we can initialize property here, calculate CRC, anything else
            return 100;
        };
        $size = $this->protocol->sizeOf($lazyValue, [UInt8::class]);
        $this->assertEquals(1, $size);
    }

    public function testCanReadWriteStructureByClassName(): void
    {
        $instance = new class implements SchemeDefinitionInterface {
            public $key = 'test';
            public $value;

            public static function getDefinition(): array
            {
                return [
                    'key'   => [BinaryString::class],
                    'value' => [BinaryString::class]
                ];
            }
        };
        $instance->value = 'another';

        $stream    = new StringStream();
        $anonClass = get_class($instance);
        $this->protocol->write($instance, [$anonClass], $stream);

        $value = $this->protocol->read([$anonClass], $stream);
        $this->assertEquals($instance, $value);
    }

    public function testThrowsExceptionForInvalidClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/^Received unknown scheme class/');
        $stream = new StringStream();
        $this->protocol->read([InvalidArgumentException::class], $stream);
    }
}
