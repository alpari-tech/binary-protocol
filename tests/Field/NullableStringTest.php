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
use PHPUnit\Framework\TestCase;

class NullableStringTest extends TestCase
{
    /**
     * @var NullableString
     */
    private $field;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->field = new NullableString(new BinaryProtocol(), [
            'size' => [Int32::class]
        ]);
    }

    public function testWriteNullable(): void
    {
        $stream = new StringStream();
        $this->field->write(null, $stream, '/');
        $this->assertEquals(bin2hex("\xFF\xFF\xFF\xFF"), bin2hex($stream->getBuffer()));
    }

    public function testReadNullable(): void
    {
        $stream = new StringStream("\xFF\xFF\xFF\xFF");
        $value  = $this->field->read($stream, '/');
        $this->assertEquals(null, $value);
    }

    public function testGetSizeForNullable(): void
    {
        $this->assertEquals(/* INT32 Length */4, $this->field->getSize(null));
    }
}
