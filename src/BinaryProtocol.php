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
     * Cached types, where key is unique type definition with arguments
     *
     * @var TypeInterface[]
     */
    private static $typeCache = [];

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
        $typeInstance = $this->getTypeInstance($type, $path);
        $value         = $typeInstance->read($stream, $path);

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
        $typeInstance = $this->getTypeInstance($type, $path);
        $typeInstance->write($value, $stream, $path);
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
        $typeInstance = $this->getTypeInstance($type, $path);

        return $typeInstance->sizeOf($value, $path);
    }

    /**
     * Creates or returns a type instance by its definition
     *
     * @param array  $typeDefinition Type definition
     * @param string $path           Path to the item
     *
     * @return TypeInterface
     */
    private function getTypeInstance(array $typeDefinition, string $path): TypeInterface
    {
        $cacheKey = json_encode($typeDefinition);
        if (!isset(self::$typeCache[$cacheKey])) {
            assert(count($typeDefinition) === 1, 'Type definition should contain only one item');
            $typeKey   = key($typeDefinition);
            $typeValue = current($typeDefinition);
            // Resolve class aliases as valid SchemeType item
            if (is_subclass_of($typeValue, SchemeDefinitionInterface::class, true)) {
                $typeKey   = SchemeType::class;
                $typeValue = ['class' => $typeValue];
            }
            $isAssoc     = !is_numeric($typeKey);
            $typeClass   = $isAssoc ? $typeKey : $typeValue;
            $typeArgs    = $isAssoc ? $typeValue : [];
            $isClassName = is_string($typeClass) && class_exists($typeClass);
            if (!$isClassName || !is_subclass_of($typeClass, TypeInterface::class, true)) {
                $pathSuffix = $path ? " at {$path}" : '';
                throw new \InvalidArgumentException("Received unknown type class `{$typeClass}`{$pathSuffix}");
            }
            self::$typeCache[$cacheKey] = $typeInstance = new $typeClass($this, $typeArgs);
        } else {
            $typeInstance = self::$typeCache[$cacheKey];
        }

        return $typeInstance;
    }
}