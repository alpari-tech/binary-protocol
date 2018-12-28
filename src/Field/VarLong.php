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

/**
 * Represents an integer between -2^63 and 2^63-1 inclusive encoded with variable length.
 *
 * @link http://code.google.com/apis/protocolbuffers/docs/encoding.html
 */
class VarLong extends VarInt
{
    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        if (!isset($value)) {
            throw new \UnexpectedValueException('VarLong size depends on value itself');
        }
        $value = ($value << 1) ^ ($value >> 63);
        $bytes = 1;
        while (($value & 0xffffffffffffff80) !== 0) {
            ++$bytes;
            $value >>= 7;
        }
        return $bytes;
    }
}
