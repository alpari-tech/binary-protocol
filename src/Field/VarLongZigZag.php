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
 * Represents an integer between -2^63 and 2^63-1 inclusive encoded with variable length.
 *
 * Encoding follows the zig-zag encoding from Google Protocol Buffers.
 *
 * @link http://code.google.com/apis/protocolbuffers/docs/encoding.html
 */
final class VarLongZigZag extends VarLong
{
    /**
     * @inheritDoc
     */
    public function read(StreamInterface $stream, string $fieldPath)
    {
        $value = parent::read($stream, $fieldPath);

        // ZigZag-decoding
        $value = ($value >> 1) ^ (-($value & 1));

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function write($value, StreamInterface $stream, string $fieldPath): void
    {
        // ZigZag-encoding
        $value = ($value << 1) ^ ($value >> 63);

        parent::write($value, $stream, $fieldPath);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        // ZigZag-encoding
        $value = ($value << 1) ^ ($value >> 63);

        return parent::getSize($value);
    }
}
