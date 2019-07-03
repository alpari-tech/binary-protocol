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

use Alpari\BinaryProtocol\BinaryProtocolInterface;
use Alpari\BinaryProtocol\Stream\StreamInterface;
use Alpari\BinaryProtocol\Stream\StringStream;
use InvalidArgumentException;

/**
 * Represents a raw sequence of bytes or characters (string).
 *
 * First the length N is given as an integer. Then N bytes follow.
 * A null value is encoded with length of -1 and there are no following bytes.
 */
class BinaryString extends AbstractType
{
    /**
     * Defines an internal type that is stored in the buffer
     */
    protected $envelope;

    /**
     * Definition of size type or concrete size in bytes for fixed-size strings
     *
     * @var array|int
     */
    protected $size = [Int16BE::class];

    /**
     * Whether string is nullable or not
     */
    protected $nullable = false;

    /**
     * BinaryString type
     *
     * Available options for array are following:
     *   - envelope string <optional> Definition of nested type in the buffer
     *   - size array|int <optional> Definition of size type, for example Int16, Int32 or VarInt, or just int value
     *   - nullable bool <optional> Null value is supported as string length = -1
     *
     * @inheritDoc
     */
    public function __construct(BinaryProtocolInterface $protocol, array $options)
    {
        parent::__construct($protocol, $options);
    }

    /**
     * Reads a value from the stream
     *
     * @param StreamInterface $stream Instance of stream to read value from
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $path)
    {
        // If we have fixed-size, then use it as-is, do not read from the protocol
        if (is_integer($this->size)) {
            $stringLength = $this->size;
        } else {
            $stringLength = $this->protocol->read($this->size, $stream, $path.'[size]');
        }
        if ($stringLength >= 0) {
            $value = $stream->read($stringLength);
        } elseif ($stringLength === -1 && $this->nullable) {
            $value = null;
        } else {
            throw new InvalidArgumentException('Received negative string length: ' . $stringLength);
        }
        // Binary buffer could contains nested envelope, which we can unpack
        if ($value !== null && $this->envelope !== null) {
            $buffer = new StringStream($value);
            $value  = $this->protocol->read($this->envelope, $buffer, $path . '[envelope]');
        }
        return $value;
    }

    /**
     * Writes the value to the given stream
     *
     * @param mixed           $value  Value to write
     * @param StreamInterface $stream Instance of stream to write to
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $path): void
    {
        // Envelopes should be encoded as raw buffer before future processing
        if ($value !== null && $this->envelope !== null) {
            $buffer = new StringStream();
            $this->protocol->write($value, $this->envelope, $buffer, $path . '[envelope]');
            $value = $buffer->getBuffer();
        }
        if ($value === null && $this->nullable) {
            $stringLength = -1;
        } elseif (is_string($value)) {
            $stringLength = strlen($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the string');
        }
        if (is_integer($this->size)) {
            if ($this->size !== $stringLength) {
                throw new InvalidArgumentException('String buffer doesn\'t match expected buffer size');
            }
        } else {
            $this->protocol->write($stringLength, $this->size, $stream, $path.'[size]');
        }
        if ($stringLength > 0) {
            $stream->write($value);
        }
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        // Envelopes should be encoded as raw buffer before future processing
        if ($value !== null && $this->envelope !== null) {
            $buffer = new StringStream();
            $this->protocol->write($value, $this->envelope, $buffer, $path);
            $value = $buffer->getBuffer();
        }
        if ($value === null && $this->nullable) {
            $stringLength = -1;
            $value        = '';
        } elseif (is_string($value)) {
            $stringLength = strlen($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the string');
        }

        $totalSize = 0;
        if (is_array($this->size)) {
            $totalSize .= $this->protocol->sizeOf($stringLength, $this->size, $path . '[size]');
        }
        $totalSize += strlen($value);

        return $totalSize;
    }
}
