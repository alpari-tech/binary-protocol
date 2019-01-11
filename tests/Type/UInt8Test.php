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

class UInt8Test extends TestCase
{
    /**
     * @var UInt8
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new UInt8(new BinaryProtocol(), []);
    }

    public function testGetFormat(): void
    {
        $this->assertEquals('C', $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream = new StringStream();
        $this->field->write(128, $stream, '/');
        $this->assertEquals("\x80", $stream->getBuffer());
    }

    public function testRead(): void
    {
        $stream = new StringStream("\xFF");
        $value  = $this->field->read($stream, '/');
        $this->assertSame(255, $value);
    }

    public function testGetSize(): void
    {
        $this->assertEquals(1, $this->field->getSize());
    }
}
