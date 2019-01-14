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

class Int16Test extends TestCase
{
    /**
     * @var Int16
     */
    private $type;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->type = new Int16(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        $this->assertEquals('s', $this->type->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        $this->type->write(127, $stream, '/');
        $this->assertSame("\x7F\x00", $stream->getBuffer());
    }

    public function testRead(): void
    {
        $stream = new StringStream("\xFF\xFF");
        $value  = $this->type->read($stream, '/');
        $this->assertSame(-1, $value);
    }

    public function testGetSize(): void
    {
        $this->assertEquals(2, $this->type->sizeOf());
    }
}
