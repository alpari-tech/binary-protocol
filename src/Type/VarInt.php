<?php
/*
 * This file is part of the Alpari BinaryProtocol library.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Alpari\BinaryProtocol\Type;

use Alpari\BinaryProtocol\Stream\StreamInterface;
use InvalidArgumentException;

/**
 * Represents an integer between -2^31 and 2^31-1 inclusive encoded with variable length.
 *
 * @link http://code.google.com/apis/protocolbuffers/docs/encoding.html
 */
class VarInt extends AbstractType
{
    /**
     * Reads a value from the stream
     *
     * @param StreamInterface $stream Instance of stream to read value from
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $path)
    {
        $value  = 0;
        $offset = 0;
        do {
            $byte   = ord($stream->read(1));
            $value  += ($byte & 0x7f) << $offset;
            $offset += 7;
        } while (($byte & 0x80) !== 0);

        return $value;
    }

    /**
     * Writes the value to the given stream
     *
     * @param mixed           $value  Value to write
     * @param StreamInterface $stream Instance of stream to write to
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $path): void
    {
        do {
            $byte  = $value & 0x7f;
            $value >>= 7;
            $byte  = $value > 0 ? ($byte | 0x80) : $byte;
            $stream->write(chr($byte));
        } while ($value > 0);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        if (!isset($value) || !is_integer($value)) {
            throw new InvalidArgumentException('VarInt size depends on value itself and it should be int type');
        }
        $bytes = 1;
        while (($value & 0xffffff80) !== 0) {
            ++$bytes;
            $value >>= 7;
        }
        return $bytes;
    }
}
