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

use OutOfBoundsException;

/**
 * Simple string stream implementation that can be used for reading/writing data from/to string buffer
 */
class StringStream implements StreamInterface
{
    /**
     * Internal binary buffer
     */
    private $buffer;

    /**
     * String stream constructor.
     *
     * @param string $stringBuffer Optional buffer to read from
     */
    public function __construct(string $stringBuffer = null)
    {
        $this->buffer = $stringBuffer ?? '';
    }

    /**
     * Writes packet to the stream
     *
     * @param string $packet Data to write
     */
    public function write(string $packet): void
    {
        $this->buffer .= $packet;
    }

    /**
     * Reads packet from the stream, advance internal pointer
     *
     * @param int $packetSize Size of packet to read
     *
     * @return string Fetched data, up to $packetSize
     */
    public function read(int $packetSize): string
    {
        $bufferLength = strlen($this->buffer);
        if ($packetSize > $bufferLength) {
            throw new OutOfBoundsException("Not enough data in the buffer, has {$bufferLength} bytes");
        }
        $packet       = substr($this->buffer, 0, $packetSize);
        $this->buffer = substr($this->buffer, $packetSize);

        return $packet;
    }

    /**
     * Returns the current buffer, useful for write operations
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Checks if buffer is empty
     */
    public function isEmpty(): bool
    {
        return $this->buffer === '';
    }
}
