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

class UInt32Test extends TestCase
{
    /**
     * @var UInt32
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new UInt32(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        $this->assertEquals('L', $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        $this->field->write(127, $stream, '/');
        $this->assertSame(bin2hex("\x7f\x00\x00\x00"), bin2hex($stream->getBuffer()));
    }

    public function testRead(): void
    {
        $stream = new StringStream("\xFF\xFF\xFF\xFF");
        $value  = $this->field->read($stream, '/');
        $this->assertSame(0xFFFFFFFF, $value);
    }

    public function testGetSize(): void
    {
        $this->assertEquals(4, $this->field->getSize());
    }
}
