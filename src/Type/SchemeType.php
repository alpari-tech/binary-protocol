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
use Alpari\BinaryProtocol\SchemeDefinitionInterface;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Represents a complex structure with arbitrary fields and (probably) nested structures.
 *
 * Typically, structure maps to the PHP's data transfer classes (DTO).
 */
final class SchemeType extends AbstractType
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
     * Structure type
     *
     * Available options for array are following:
     *   - class string <required> Name of the corresponding class that holds this structure
     *   - scheme array <optional> Definition of each item as key => definition
     *
     * @param BinaryProtocolInterface $protocol
     * @param array                   $options
     *
     * @throws \ReflectionException
     */
    public function __construct(BinaryProtocolInterface $protocol, array $options)
    {
        if (!isset($options['class'])) {
            throw new InvalidArgumentException('SchemeType expects the `class` option to be specified');
        }
        /** @var SchemeDefinitionInterface $className */
        $className = $options['class'];
        if (!is_subclass_of($className, SchemeDefinitionInterface::class, true)) {
            throw new InvalidArgumentException("Class `{$className}` should implement `SchemeDefinitionInterface`");
        }
        parent::__construct($protocol, $options);

        $this->reflection = new ReflectionClass($className);
        if (!isset($this->scheme)) {
            $this->scheme = $className::getDefinition();
        }
        foreach (array_keys($this->scheme) as $propertyName) {
            if (!$this->reflection->hasProperty($propertyName)) {
                throw new InvalidArgumentException("Class `{$className}` does not contain {$propertyName} property");
            }
        }
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
        $scheme   = $this->scheme;
        $protocol = $this->protocol;
        $result   = $this->reflection->newInstanceWithoutConstructor();

        $accessor = function (StreamInterface $stream, string $path) use ($protocol, $scheme, $result) {
            foreach ($scheme as $key => $propertyType) {
                $result->$key = $protocol->read($propertyType, $stream, $path . '->' . $key);
            }
        };
        $accessor->call($result, $stream, $path . ':' . $this->class);

        return $result;
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
        $scheme   = $this->scheme;
        $protocol = $this->protocol;

        $accessor = function (StreamInterface $stream, string $path) use ($protocol, $scheme, $value) {
            foreach ($scheme as $key => $propertyType) {
                $protocol->write($value->$key, $propertyType, $stream, $path . '->' . $key);
            }
        };
        $accessor->call($value, $stream, $path . ':' . $this->class);
    }

    /**
     * Calculates the size in bytes of single item for given value
     */
    public function sizeOf($value = null, string $path = ''): int
    {
        $scheme   = $this->scheme;
        $protocol = $this->protocol;

        $accessor = function ($value, string $path) use ($protocol, $scheme) {
            $total = 0;
            foreach ($scheme as $key => $propertyType) {
                $total += $protocol->sizeOf($value->$key, $propertyType, $path . '->' . $key);
            }

            return $total;
        };

        return $accessor->call($value, $value, $path . ':' . $this->class);
    }
}
