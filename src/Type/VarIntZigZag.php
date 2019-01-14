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
 * Encoding follows the zig-zag encoding from Google Protocol Buffers.
 *
 * @link http://code.google.com/apis/protocolbuffers/docs/encoding.html
 */
final class VarIntZigZag extends VarInt
{
    /**
     * @inheritDoc
     */
    public function read(StreamInterface $stream, string $path)
    {
        $value = parent::read($stream, $path);

        // ZigZag-decoding
        $value = ($value >> 1) ^ (-($value & 1));

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function write($value, StreamInterface $stream, string $path): void
    {
        // ZigZag-encoding
        $value = ($value << 1) ^ ($value >> 31);

        parent::write($value, $stream, $path);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        if (!isset($value) || !is_integer($value)) {
            throw new InvalidArgumentException('VarIntZigZag size depends on value itself and it should be int type');
        }

        // ZigZag-encoding
        $value = ($value << 1) ^ ($value >> 31);

        return parent::sizeOf($value);
    }
}
