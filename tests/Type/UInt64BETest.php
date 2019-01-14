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
use PHPUnit\Framework\TestCase;

class UInt64BETest extends TestCase
{
    /**
     * @var UInt64BE
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new UInt64BE(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        $this->assertEquals('J', $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        $this->field->write(127, $stream, '/');
        $this->assertSame(bin2hex("\x00\x00\x00\x00\x00\x00\x00\x7F"), bin2hex($stream->getBuffer()));
    }

    public function testRead(): void
    {
        // PHP has limited support for UINT64 values, PHP_INT_MAX = UNIT64_MAX >> 2
        $stream = new StringStream("\x7F\xFF\xFF\xFF\xFF\xFF\xFF\xFF");
        $value  = $this->field->read($stream, '/');
        $this->assertSame(PHP_INT_MAX, $value);
    }

    public function testGetSize(): void
    {
        $this->assertEquals(8, $this->field->sizeOf());
    }
}
