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
 * The values are encoded using two bytes in machine-dependent byte order.
 */
class Int16 extends AbstractType
{
    /**
     * Reads a value from the stream
     *
     * @param StreamInterface $stream    Instance of stream to read value from
     * @param string          $fieldPath Path to the type to simplify debug of complex hierarchical structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $fieldPath)
    {
        $packet = $stream->read(2);

        return unpack('sINT16', $packet)['INT16'];
    }

    /**
     * Writes the value to the given stream
     *
     * @param mixed           $value     Value to write
     * @param StreamInterface $stream    Instance of stream to write to
     * @param string          $fieldPath Path to the type to simplify debug of complex hierarchical structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $fieldPath): void
    {
        $packet = pack('s', $value);
        $stream->write($packet);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
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
        return 's';
    }
}
