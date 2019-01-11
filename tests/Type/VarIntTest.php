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

class VarIntTest extends TestCase
{
    /**
     * @var VarInt
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new VarInt(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        // VarInt never has direct format
        $this->assertEquals(null, $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        // Up to 0x7F is encoded as single byte
        $this->field->write(127, $stream, '/');
        // 128 will be encoded as 0x01 0x80
        $this->field->write(128, $stream, '/');
        $this->assertSame(bin2hex("\x7F\x80\x01"), bin2hex($stream->getBuffer()));
    }

    public function testRead(): void
    {
        $stream = new StringStream("\x7F\x80\x01");
        $value  = $this->field->read($stream, '/');
        $this->assertSame(127, $value);
        $value = $this->field->read($stream, '/');
        $this->assertSame(128, $value);
        $this->assertTrue($stream->isEmpty());
    }

    public function testGetSize(): void
    {
        $this->assertEquals(1, $this->field->getSize(0b01111111));
        $this->assertEquals(2, $this->field->getSize(0b10000000));
        $this->assertEquals(3, $this->field->getSize(0b000111111111111111111111));

    }

    public function testGetSizeThrowsExceptionWithoutValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('VarInt size depends on value itself and it should be int type');
        $this->field->getSize();
    }

    public function testGetSizeThrowsExceptionForNonInt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('VarInt size depends on value itself and it should be int type');
        $this->field->getSize('test');
    }
}
