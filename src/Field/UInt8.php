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
 * Represents simple UINT8 type, encoded as one single unsigned byte
 */
final class UInt8 extends AbstractField
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
        $value = $stream->read(1);

        return unpack('CUINT8', $value)['UINT8'];
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
        $stream->write(pack('С', $value));
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        return 1;
    }

    /**
     * Returns format for unpacking with unpack() function or null if no direct equivalent for this type
     *
     * @see pack() for details about format
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return 'С';
    }
}
