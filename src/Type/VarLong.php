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

use InvalidArgumentException;

/**
 * Represents an integer between -2^63 and 2^63-1 inclusive encoded with variable length.
 *
 * Note: for PHP we limited -2^62 and 2^62-1 due to UINT64 representation as always signed
 *
 * @link http://code.google.com/apis/protocolbuffers/docs/encoding.html
 */
class VarLong extends VarInt
{
    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        if (!isset($value) || !is_integer($value)) {
            throw new InvalidArgumentException('VarLong size depends on value itself and it should be int type');
        }
        $bytes = 1;
        // PHP limits us with UINT64, so let's restrict top byte to be 0x7F
        while (($value & 0x7fffffffffffff80) !== 0) {
            ++$bytes;
            $value >>= 7;
        }
        return $bytes;
    }
}
