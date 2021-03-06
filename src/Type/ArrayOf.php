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
use InvalidArgumentException;

/**
 * Represents a sequence of objects of a given type T.
 *
 * Type T can be either a primitive type (e.g. BinaryString) or a structure. First, the length N is given as an INT32.
 * Then N instances of type T follow. A null array is represented with a length of -1.
 */
final class ArrayOf extends AbstractType
{
    /**
     * Definition of size type
     *
     * @var array
     */
    protected $size = [Int32BE::class];

    /**
     * Definition of item type
     *
     * @var array
     */
    protected $item;

    /**
     * Optional key name, can be null for non-associative arrays
     *
     * @var string|null
     */
    protected $key;

    /**
     * Whether array is nullable or not
     */
    protected $nullable = false;

    /**
     * ArrayOf type
     *
     * Available options for array are following:
     *   - item array <required> Definition of item, which is used in array
     *   - size int|array <optional> Definition of size type, for example Int16, Int32 or VarInt, or integer
     *   - key string <optional> Name of the type from an item object that will be used as associative key in array
     *   - nullable bool <optional> Null value is supported as size = -1
     *
     * @param BinaryProtocolInterface $protocol
     * @param array                   $options
     */
    public function __construct(BinaryProtocolInterface $protocol, array $options)
    {
        if (!isset($options['item'])) {
            throw new InvalidArgumentException('ArrayOf expects the `item` option to be specified');
        }
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
        if (is_integer($this->size)) {
            $itemCount = $this->size;
        } else {
            $itemCount = $this->protocol->read($this->size, $stream, $path . '[size]');
        }
        if ($itemCount >= 0) {
            $value = [];
            for ($index = 0; $index < $itemCount; $index++) {
                $item = $this->protocol->read($this->item, $stream, $path . "[$index]");
                if (isset($this->key)) {
                    if (!is_object($item)) {
                        throw new InvalidArgumentException('Associative array can be applied to DTOs only');
                    }
                    $keyValue = $item->{$this->key};
                    $value[$keyValue] = $item;
                } else {
                    $value[] = $item;
                }
            }
        } elseif ($itemCount === -1 && $this->nullable) {
            $value = null;
        } else {
            throw new InvalidArgumentException('Received negative array length: ' . $itemCount . " for {$path}");
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
        if ($value === null && $this->nullable) {
            $itemCount = -1;
            $value     = []; // Redeclare as empty array to prevent extra check for foreach
        } elseif (is_array($value)) {
            $itemCount = count($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the array');
        }
        if (is_integer($this->size)) {
            if ($this->size !== $itemCount) {
                throw new InvalidArgumentException('Array size doesn\'t match expected array size');
            }
        } else {
            $this->protocol->write($itemCount, $this->size, $stream, $path . '[size]');
        }
        foreach ($value as $index => $item) {
            $this->protocol->write($item, $this->item, $stream, $path . "[$index]");
        }
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        if ($value === null && $this->nullable) {
            $itemCount = -1;
            $value     = []; // Redeclare as empty array to prevent extra check for foreach
        } elseif (is_array($value)) {
            $itemCount = count($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the array');
        }
        $totalSize = 0;
        if (!is_integer($this->size)) {
            $totalSize += $this->protocol->sizeOf($itemCount, $this->size, $path . '[size]');
        }
        foreach ($value as $index => $item) {
            $totalSize += $this->protocol->sizeOf($item, $this->item, $path . "[$index]");
        }

        return $totalSize;
    }
}
