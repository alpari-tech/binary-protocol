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

namespace Alpari\BinaryProtocol\Stream;

/**
 * General stream interface to read/write binary packets
 */
interface StreamInterface
{
    /**
     * Writes packet to the stream
     *
     * @param string $packet Data to write
     */
    public function write(string $packet): void;

    /**
     * Reads packet from the stream, advance internal pointer
     *
     * @param int $packetSize Size of packet to read
     *
     * @return string Fetched data, up to $packetSize
     */
    public function read(int $packetSize): string;
}
