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
 * Declares the single binary type that can be packed/unpacked from/to binary stream
 */
interface TypeInterface
{
    /**
     * Reads a value from the stream
     *
     * @param StreamInterface $stream Instance of stream to read value from
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $path);

    /**
     * Writes the value to the given stream
     *
     * @param mixed           $value  Value to write
     * @param StreamInterface $stream Instance of stream to write to
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $path): void;

    /**
     * Calculates the size in bytes of single item for given value
     *
     * @param mixed  $value Value to write
     * @param string $path  Path to the item to simplify debug of complex hierarchical structures
     *
     * @return int
     */
    public function sizeOf($value = null, string $path =''): int;

    /**
     * Returns format for unpacking with unpack() function or null if no direct equivalent for this type
     *
     * @see pack() for details about format
     * @return string|null
     */
    public function getFormat(): ?string;
}
