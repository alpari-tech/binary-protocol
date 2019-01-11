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

namespace Alpari\BinaryProtocol\Field;

use Alpari\BinaryProtocol\Stream\StreamInterface;

/**
 * Represents an integer between 0 and 2^64-1 inclusive.
 *
 * The values are encoded using eight bytes in little-endian byte order.
 */
final class UInt64LE extends AbstractField
{
    /**
     * Reads a value of field from the stream
     *
     * @param StreamInterface $stream    Instance of stream to read value from
     * @param string          $fieldPath Path to the field to simplify debug of complex hierarchial structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $fieldPath)
    {
        $packet = $stream->read(8);

        return unpack('PUINT64', $packet)['UINT64'];
    }

    /**
     * Writes the value of field to the given stream
     *
     * @param mixed           $value     Field value to write
     * @param StreamInterface $stream    Instance of stream to write to
     * @param string          $fieldPath Path to the field to simplify debug of complex hierarchial structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $fieldPath): void
    {
        $packet = pack('P', $value);
        $stream->write($packet);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        return 8;
    }

    /**
     * Returns format for unpacking with unpack() function or null if no direct equivalent for this type
     *
     * @see pack() for details about format
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return 'P';
    }
}
