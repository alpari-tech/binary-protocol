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
use Alpari\BinaryProtocol\StructureInterface;
use Alpari\BinaryProtocol\Stream\StreamInterface;
use ReflectionClass;

/**
 * Represents a complex structure with arbitrary fields and (probably) nested structures.
 *
 * Typically, structure maps to the PHP's data transfer classes (DTO).
 */
final class Structure extends AbstractField
{
    /**
     * Name of the class with corresponding structure
     *
     * @var string
     */
    protected $class;

    /**
     * Contains the definition of binary scheme structure as key => value definition
     *
     * @var array
     */
    protected $scheme;

    /**
     * Instance of class reflection to optimize object initialization
     *
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * Structure field
     *
     * Available options for array are following:
     *   - class string <required> Name of the corresponding class that holds this structure
     *   - scheme array <optional> Definition of each item as key => definition
     *
     * @param BinaryProtocol $protocol
     * @param array          $options
     *
     * @throws \ReflectionException
     */
    public function __construct(BinaryProtocol $protocol, array $options)
    {
        if (!isset($options['class'])) {
            throw new \InvalidArgumentException('Structure expects the `class` field to be specified');
        }
        $className = $options['class'];
        if (!is_subclass_of($className, StructureInterface::class, true)) {
            throw new \InvalidArgumentException('Structure should implement `StructInterface`');
        }
        parent::__construct($protocol, $options);

        $this->reflection = new ReflectionClass($className);
        if (!isset($this->scheme)) {
            $this->scheme = $className::getScheme();
        }
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
        $fields   = $this->scheme;
        $protocol = $this->protocol;
        $result   = $this->reflection->newInstanceWithoutConstructor();

        $accessor = function (StreamInterface $stream, string $fieldPath) use ($protocol, $fields, $result) {
            foreach ($fields as $fieldKey => $fieldScheme) {
                $result->$fieldKey = $protocol->read($fieldScheme, $stream, $fieldPath . '->' . $fieldKey);
            }
        };
        $accessor->call($result, $stream, $fieldPath . ':' . $this->class);

        return $result;
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
        $fields   = $this->scheme;
        $protocol = $this->protocol;

        $accessor = function (StreamInterface $stream, string $fieldPath) use ($protocol, $fields, $value) {
            foreach ($fields as $fieldKey => $fieldScheme) {
                $protocol->write($value->$fieldKey, $fieldScheme, $stream, $fieldPath . '->' . $fieldKey);
            }
        };
        $accessor->call($value, $stream, $fieldPath . ':' . $this->class);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function getSize($value = null, string $fieldPath = ''): int
    {
        $fields   = $this->scheme;
        $protocol = $this->protocol;

        $accessor = function ($value, string $fieldPath) use ($protocol, $fields) {
            $total = 0;
            foreach ($fields as $fieldKey => $fieldScheme) {
                $total += $protocol->sizeOf($value->$fieldKey, $fieldScheme, $fieldPath . '->' . $fieldKey);
            }

            return $total;
        };

        return $accessor->call($value, $value, $fieldPath . ':' . $this->class);
    }
}
