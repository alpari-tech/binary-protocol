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

use Alpari\BinaryProtocol\BinaryProtocol;
use Alpari\BinaryProtocol\Stream\StreamInterface;
use InvalidArgumentException;

/**
 * Represents a sequence of objects of a given type T.
 *
 * Type T can be either a primitive type (e.g. BinaryString) or a structure. First, the length N is given as an INT32.
 * Then N instances of type T follow. A null array is represented with a length of -1.
 */
final class ArrayOf extends AbstractField
{
    /**
     * Definition of size field
     *
     * @var array
     */
    protected $size = [Int32BE::class];

    /**
     * Definition of item field
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
     * ArrayOf field
     *
     * Available options for array are following:
     *   - item array <required> Definition of item, which is used in array
     *   - size array <optional> Definition of size field, for example Int16, Int32 or VarInt, etc..
     *   - key string <optional> Name of the field from an item object that will be used as associative key in array
     *   - nullable bool <optional> Null value is supported as size = -1
     *
     * @param BinaryProtocol $protocol
     * @param array          $options
     */
    public function __construct(BinaryProtocol $protocol, array $options)
    {
        if (!isset($options['item'])) {
            throw new InvalidArgumentException('ArrayOf expects the `item` field to be specified');
        }
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
        $itemCount = $this->protocol->read($this->size, $stream, $fieldPath . '[size]');
        if ($itemCount >= 0) {
            $value = [];
            for ($index = 0; $index < $itemCount; $index++) {
                $item = $this->protocol->read($this->item, $stream, $fieldPath . "[$index]");
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
            throw new InvalidArgumentException('Received negative array length: ' . $itemCount . " for {$fieldPath}");
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
        if ($value === null && $this->nullable) {
            $itemCount = -1;
            $value     = []; // Redeclare as empty array to prevent extra check for foreach
        } elseif (is_array($value)) {
            $itemCount = count($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the array');
        }
        $this->protocol->write($itemCount, $this->size, $stream, $fieldPath . '[size]');
        foreach ($value as $index => $item) {
            $this->protocol->write($item, $this->item, $stream, $fieldPath . "[$index]");
        }
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        if ($value === null && $this->nullable) {
            $itemCount = -1;
            $value     = []; // Redeclare as empty array to prevent extra check for foreach
        } elseif (is_array($value)) {
            $itemCount = count($value);
        } else {
            throw new InvalidArgumentException('Invalid value received for the array');
        }
        // TODO: use field paths
        $totalSize = $this->protocol->sizeOf($itemCount, $this->size, $fieldPath . '[size]');
        foreach ($value as $index => $item) {
            $totalSize += $this->protocol->sizeOf($item, $this->item, $fieldPath . "[$index]");
        }

        return $totalSize;
    }
}
