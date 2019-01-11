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

namespace Alpari\BinaryProtocol;

use Alpari\BinaryProtocol\Stream\StreamInterface;

/**
 * Scheme defines common types and API for reading and writing primitives types into binary stream
 */
interface BinaryProtocolInterface
{
    /**
     * Decodes a value from the stream
     *
     * @param array           $type   Type definition
     * @param StreamInterface $stream Stream to read from
     * @param string          $path   Optional path to the element
     *
     * @return mixed
     */
    public function read(array $type, StreamInterface $stream, string $path = '');

    /**
     * Encodes the value to the stream
     *
     * @param mixed           $value
     * @param array           $type   Type definition
     * @param StreamInterface $stream Stream to write to
     * @param string          $path   Optional path to the element
     */
    public function write($value, array $type, StreamInterface $stream, string $path = ''): void;

    /**
     * Calculates the size of value item in bytes
     *
     * @param mixed  $value Give value
     * @param array  $type  Type definition
     * @param string $path  Optional path to the element
     *
     * @return int
     */
    public function sizeOf($value, array $type, string $path = ''): int;
}