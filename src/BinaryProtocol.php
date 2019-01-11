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

use Alpari\BinaryProtocol\Type\SchemeType;
use Alpari\BinaryProtocol\Stream\StreamInterface;
use Closure;
use function count;
use function is_numeric;

/**
 * Binary protocol defines an API for reading and writing primitives types into binary stream
 */
class BinaryProtocol implements BinaryProtocolInterface
{
    /**
     * Cached fields, where key is unique type definition with arguments
     *
     * @var TypeInterface[]
     */
    private static $fieldCache = [];

    /**
     * Decodes a value from the stream
     *
     * @param array           $type   Type definition
     * @param StreamInterface $stream Stream to read from
     * @param string          $path   Optional path to the element
     *
     * @return mixed
     */
    public function read(array $type, StreamInterface $stream, string $path = '')
    {
        $fieldInstance = $this->getFieldInstance($type, $path);
        $value         = $fieldInstance->read($stream, $path);

        return $value;
    }

    /**
     * Encodes the value to the stream
     *
     * @param mixed           $value
     * @param array           $type   Type definition
     * @param StreamInterface $stream Stream to write to
     * @param string          $path   Optional path to the element
     */
    public function write($value, array $type, StreamInterface $stream, string $path = ''): void
    {
        // If we have a Closure instance, then this type should be calculated now
        if ($value instanceof Closure) {
            $value = $value($this, $path);
        }
        $fieldInstance = $this->getFieldInstance($type, $path);
        $fieldInstance->write($value, $stream, $path);
    }

    /**
     * Calculates the size of value item in bytes
     *
     * @param mixed  $value Give value
     * @param array  $type  Type definition
     * @param string $path  Optional path to the element
     *
     * @return int
     */
    public function sizeOf($value, array $type, string $path = ''): int
    {
        // If we have a Closure instance, then this type should be calculated now
        if ($value instanceof Closure) {
            $value = $value($this, $path);
        }
        $fieldInstance = $this->getFieldInstance($type, $path);
        return $fieldInstance->getSize($value, $path);
    }

    /**
     * Creates or returns a type instance by its definition
     *
     * @param array  $schemeType Scheme definition
     * @param string $path Path to the type
     *
     * @return TypeInterface
     */
    private function getFieldInstance(array $schemeType, string $path): TypeInterface
    {
        $key = json_encode($schemeType);
        if (!isset(self::$fieldCache[$key])) {
            assert(count($schemeType) === 1, 'Scheme should only contain one item');
            $schemeKey   = key($schemeType);
            $schemeValue = current($schemeType);
            // Resolve class aliases as valid struct item
            if (is_subclass_of($schemeValue, SchemeDefinitionInterface::class, true)) {
                $schemeKey   = SchemeType::class;
                $schemeValue = ['class' => $schemeValue];
            }
            $isAssoc     = !is_numeric($schemeKey);
            $fieldClass  = $isAssoc ? $schemeKey : $schemeValue;
            $fieldArgs   = $isAssoc ? $schemeValue : [];
            $isClassName = is_string($fieldClass) && class_exists($fieldClass);
            if (!$isClassName || !is_subclass_of($fieldClass, TypeInterface::class, true)) {
                throw new \InvalidArgumentException("Received unknown scheme class {$fieldClass} at {$path}");
            }
            /** @var TypeInterface $fieldInstance */
            self::$fieldCache[$key] = $fieldInstance = new $fieldClass($this, $fieldArgs);
        } else {
            $fieldInstance = self::$fieldCache[$key];
        }

        return $fieldInstance;
    }
}