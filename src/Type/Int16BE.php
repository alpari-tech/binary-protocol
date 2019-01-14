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

/**
 * Represents an integer between -2^15 and 2^15-1 inclusive.
 *
 * The values are encoded using two bytes in big-endian byte order.
 */
final class Int16BE extends AbstractType
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
        $packet = $stream->read(2);
        $value  = unpack('nUINT16', $packet)['UINT16'];

        // Big endian was parsed as unsigned, need to convert it to signed
        if ($value & 0x8000) {
            $value -= 0x10000;
        }

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
        // Please note that big endian is encoded as unsigned
        $packet = pack('n', $value);
        $stream->write($packet);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        return 2;
    }

    /**
     * Returns format for unpacking with unpack() function or null if no direct equivalent for this type
     *
     * @see pack() for details about format
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return 'n';
    }
}
