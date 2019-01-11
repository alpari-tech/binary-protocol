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

use Alpari\BinaryProtocol\Field\Structure;
use Alpari\BinaryProtocol\Stream\StreamInterface;
use Closure;
use function count;
use function is_numeric;

/**
 * Scheme defines common types and API for reading and writing primitives types into binary stream
 */
class BinaryProtocol implements BinaryProtocolInterface
{

    /**
     * Cached fields, where key is unique field definition with arguments
     *
     * @var FieldInterface[]
     */
    private static $fieldCache = [];

    /**
     * Decodes a value from the stream
     *
     * @param array           $schemeType Definition of scheme
     * @param StreamInterface $stream     Stream to read from
     * @param string          $path       Optional path to the element
     *
     * @return mixed
     */
    public function read(array $schemeType, StreamInterface $stream, string $path = '')
    {
        $fieldInstance = $this->getFieldInstance($schemeType, $path);
        $value         = $fieldInstance->read($stream, $path);

        return $value;
    }

    /**
     * Encodes the value to the stream
     *
     * @param mixed           $value
     * @param array           $schemeType Definition of scheme
     * @param StreamInterface $stream     Stream to write to
     * @param string          $path       Optional path to the element
     */
    public function write($value, array $schemeType, StreamInterface $stream, string $path = ''): void
    {
        // If we have a Closure instance, then this field should be calculated now
        if ($value instanceof Closure) {
            $value = $value($this, $path);
        }
        $fieldInstance = $this->getFieldInstance($schemeType, $path);
        $fieldInstance->write($value, $stream, $path);
    }

    /**
     * Calculates the size of value item in bytes
     *
     * @param mixed  $value      Give value
     * @param array  $schemeType Definition of scheme
     * @param string $path       Optional path to the element
     *
     * @return int
     */
    public function sizeOf($value, array $schemeType, string $path = ''): int
    {
        // If we have a Closure instance, then this field should be calculated now
        if ($value instanceof Closure) {
            $value = $value($this, $path);
        }
        $fieldInstance = $this->getFieldInstance($schemeType, $path);
        return $fieldInstance->getSize($value, $path);
    }

    /**
     * Creates or returns a field instance by its definition
     *
     * @param array  $schemeType Scheme definition
     * @param string $path Path to the field
     *
     * @return FieldInterface
     */
    private function getFieldInstance(array $schemeType, string $path): FieldInterface
    {
        $key = json_encode($schemeType);
        if (!isset(self::$fieldCache[$key])) {
            assert(count($schemeType) === 1, 'Scheme should only contain one item');
            $schemeKey   = key($schemeType);
            $schemeValue = current($schemeType);
            // Resolve class aliases as valid struct item
            if (is_subclass_of($schemeValue, StructureInterface::class, true)) {
                $schemeKey   = Structure::class;
                $schemeValue = ['class' => $schemeValue];
            }
            $isAssoc     = !is_numeric($schemeKey);
            $fieldClass  = $isAssoc ? $schemeKey : $schemeValue;
            $fieldArgs   = $isAssoc ? $schemeValue : [];
            $isClassName = is_string($fieldClass) && class_exists($fieldClass);
            if (!$isClassName || !is_subclass_of($fieldClass, FieldInterface::class, true)) {
                throw new \InvalidArgumentException("Received unknown scheme class {$fieldClass} at {$path}");
            }
            /** @var FieldInterface $fieldInstance */
            self::$fieldCache[$key] = $fieldInstance = new $fieldClass($this, $fieldArgs);
        } else {
            $fieldInstance = self::$fieldCache[$key];
        }

        return $fieldInstance;
    }
}