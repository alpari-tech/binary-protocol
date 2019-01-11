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

namespace Alpari\BinaryProtocol\Stream;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    public function testCanRead(): void
    {
        $stream = new StringStream('abc');
        $value  = $stream->read(1);
        $this->assertEquals('a', $value);
        $this->assertEquals('bc', $stream->getBuffer());
    }

    public function testReadMoreThanBuffer(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageRegExp("/^Not enough data in the buffer, has/");
        $stream = new StringStream('abc');
        $stream->read(4);
    }

    public function testCanWrite(): void
    {
        $stream = new StringStream();
        $stream->write("\x20");
        $this->assertEquals("\x20", $stream->getBuffer());
        $this->assertFalse($stream->isEmpty());
    }

    public function testReturnsBuffer(): void
    {
        $stream = new StringStream('Test');
        $this->assertEquals('Test', $stream->getBuffer());
    }
}
