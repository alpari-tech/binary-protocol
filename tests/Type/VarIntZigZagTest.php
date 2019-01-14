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
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VarIntZigZagTest extends TestCase
{
    /**
     * @var VarIntZigZag
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new VarIntZigZag(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        // VarInt never has direct format
        $this->assertEquals(null, $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        // -1 is encoded as 0x01, 1 as 0x02
        $this->field->write(-1, $stream, '/');
        $this->field->write(1, $stream, '/');
        $this->assertSame(bin2hex("\x01\x02"), bin2hex($stream->getBuffer()));
    }

    public function testRead(): void
    {
        $stream = new StringStream("\x01\x02");
        $value  = $this->field->read($stream, '/');
        $this->assertSame(-1, $value);
        $value = $this->field->read($stream, '/');
        $this->assertSame(1, $value);
        $this->assertTrue($stream->isEmpty());
    }

    public function testGetSize(): void
    {
        $this->assertEquals(1, $this->field->sizeOf(0b00111111)); // 0b01111110
        $this->assertEquals(2, $this->field->sizeOf(0b01111111)); // 0b00000010 0b11111110
        $this->assertEquals(2, $this->field->sizeOf(0b10000000)); // 0b00000100 0b10000000

    }

    public function testGetSizeThrowsExceptionWithoutValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('VarIntZigZag size depends on value itself and it should be int type');
        $this->field->sizeOf();
    }

    public function testGetSizeThrowsExceptionForNonInt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('VarIntZigZag size depends on value itself and it should be int type');
        $this->field->sizeOf('test');
    }
}
