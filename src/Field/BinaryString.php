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
class BinaryString extends AbstractField
{
    /**
     * Defines an internal field that is stored in the buffer
     */
    protected $envelope;

    /**
     * Definition of size field
     *
     * @var array
     */
    protected $size = [Int16BE::class];

    /**
     * Whether string is nullable or not
     */
    protected $nullable = false;

    /**
     * BinaryString field
     *
     * Available options for array are following:
     *   - envelope string <optional> Definition of nested field in the buffer
     *   - size array <optional> Definition of size field, for example Int16, Int32 or VarInt, etc..
     *   - nullable bool <optional> Null value is supported as string length = -1
     *
     * @inheritDoc
     */
    public function __construct(BinaryProtocolInterface $protocol, array $options)
    {
        parent::__construct($protocol, $options);
    }

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
        $stringLength = $this->protocol->read($this->size, $stream, $fieldPath.'[size]');
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
            $value  = $this->protocol->read($this->envelope, $buffer, $fieldPath . '[envelope]');
        }
        return $value;
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
        // Envelopes should be encoded as raw buffer before future processing
        if ($value !== null && $this->envelope !== null) {
            $buffer = new StringStream();
            $this->protocol->write($value, $this->envelope, $buffer, $fieldPath . '[envelope]');
            $value = $buffer->getBuffer();
        }
        if ($value === null && $this->nullable) {
            $stringLength = -1;
        } elseif (is_string($value)) {
            $stringLength = strlen($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the string');
        }

        $this->protocol->write($stringLength, $this->size, $stream, $fieldPath.'[size]');
        if ($stringLength > 0) {
            $stream->write($value);
        }
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        // Envelopes should be encoded as raw buffer before future processing
        if ($value !== null && $this->envelope !== null) {
            $buffer = new StringStream();
            $this->protocol->write($value, $this->envelope, $buffer, $fieldPath);
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

        $totalSize  = $this->protocol->sizeOf($stringLength, $this->size, $fieldPath . '[size]');
        $totalSize += strlen($value);

        return $totalSize;
    }
}
