Alpari BinaryProtocol library 
-----------------

`alpari/binary-protocol` is a PHP library that provides an API for working with binary protocols, like FCGI, 
ApacheKafka and others. Applications can use this library to describe binary data and work with high-level API 
without manual working with binary data packing/unpacking.


Installation
------------

`alpari/binary-protocol` can be installed with composer. To download this library please type:

``` bash
composer require alpari/binary-protocol
```
 
TypeInterface API
------------
The TypeInterface API describes low-level representation of data, for example Int8, Int16, String, etc...

```php
<?php

namespace Alpari\BinaryProtocol;

use Alpari\BinaryProtocol\Stream\StreamInterface;

/**
 * Declares the type that can be packed/unpacked from/to binary stream
 */
interface TypeInterface
{
    /**
     * Reads a value from the stream
     *
     * @param StreamInterface $stream Instance of stream to read value from
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $path);

    /**
     * Writes the value to the given stream
     *
     * @param mixed           $value  Value to write
     * @param StreamInterface $stream Instance of stream to write to
     * @param string          $path   Path to the item to simplify debug of complex hierarchical structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $path): void;

    /**
     * Calculates the size in bytes of single item for given value
     *
     * @param mixed  $value Value to write
     * @param string $path  Path to the item to simplify debug of complex hierarchical structures
     *
     * @return int
     */
    public function sizeOf($value = null, string $path =''): int;
}
``` 
Each data type should be added as a separate class implementing `TypeInterface` contract with logic of packing and
unpacking data to/from `StreamInterface`

As you can see there are two main methods which are `read()` and `write()` that are used for reading and writing binary 
data to the stream. 

The method `sizeOf()` is used for calculation of data size in bytes. For simple data types it will return constant 
value and for more complex structures like arrays or objects it should be able to calculate the size of such field.

Method `getFormat()` is declared in the interface, but it is not used right now. It can be used later for fixed-size 
arrays or structures to speed up parsing, for example, array of Int16. 

SchemeDefinitionInterface API
------------

`SchemeDefinitionInterface` helps to use complex binary data structures in your application. Each complex type from the
binary protocol should be represented as a separate DTO (Plain PHP object with properties) implementing the
`SchemeDefinitionInterface` and it's method `getDefinition()`.

```php
<?php

/**
 * SchemeDefinitionInterface represents a class that holds definition of his scheme
 */
interface SchemeDefinitionInterface
{
    /**
     * Returns the definition of class scheme as an associative array if form of [property => type]
     */
    public static function getDefinition(): array;
}
```

Here is an example of definition of binary packet that is used for Kafka client:
```php
<?php

use Alpari\BinaryProtocol\Type\Int16BE as Int16;
use Alpari\BinaryProtocol\SchemeDefinitionInterface;

/**
 * ApiVersions response data
 */
class ApiVersionsResponseMetadata implements SchemeDefinitionInterface
{
    /**
     * Numerical code of API
     *
     * @var integer
     */
    public $apiKey;

    /**
     * Minimum supported version.
     *
     * @var integer
     */
    public $minVersion;

    /**
     * Maximum supported version.
     *
     * @var integer
     */
    public $maxVersion;

    /**
     * @inheritdoc
     */
    public static function getDefinition(): array
    {
        return [
            'apiKey'     => [Int16::class],
            'minVersion' => [Int16::class],
            'maxVersion' => [Int16::class],
        ];
    }
}
```
To add the definition to your existing class, just add "property" => "type" mapping as a scheme definition. Please
note that each property type is declared as an array in the `getDefinition()` method. This format is used to define
additional arguments for complex types.
 
`ArrayOf` type
---------------
 
 `ArrayOf` type is used to declare the sequence of repeated binary items (integers, strings or objects). It has
 several options that can be applied as an associative array of values.
 
```php
 return [
     'partitions' => [ArrayOf::class => [
        'item' => [Int32::class]
    ]]
];
``` 
Available options for `ArrayOf` are:
 - `item` **(required)** Definition of single item type, which is used in array, e.g `[Int32::class]`
 - `size` (optional) Definition of size type, you can use `[VarInt::class]`, `[Int64::class]` or anything else
 - `key` (optional) Name of the property from an object that will be used as associative key in array
 - `nullable` (optional) Boolean flag to enable `null` values encoded as size = -1

`BinaryString` type
---------------
`BinaryString` class is general representation of any string or raw buffers with binary data. Typically it is encoded
 as buffer length field and sequence of bytes as a data.

Available options for `BinaryString` are:
 - `envelope` (optional) Declares the type of nested data, that is stored in the binary packet.
 - `size` (optional) Definition of size type, you can use `[VarInt::class]`, `[Int64::class]` or anything else
 - `nullable` (optional) Boolean flag to enable `null` values encoded as size = -1
 
`envelope` feature is used for some protocols when low-level protocol just defines some buffer and top-level 
protocol extracts specific packet from this temporary buffer. Example is TCP packet over IP. 

By default `BinaryString` uses `Int16` in big-endian encoding for size, if you need more data, just configure the `size`
option accordingly.

`SchemeType` type
---------------
`SchemeType` class represents a complex structure that can be mapped to PHP's object instance. It has following options:
 - `class` **(required)** String with FQN name of the corresponding class that holds this structure
 - `scheme` (optional) Definition of each item type as key => definition. Key is used as property name for this object.

**Note**: if your `SomeClass` class implements the `SchemeDefinitionInterface`, then you can simply refer to it in the 
scheme as `[SomeClass::class]`.


`BinaryProtocol` class usage
--------------------------
This library introduces `BinaryProtocol` class as a top-level binary coder/decoder API. To read the data, please, 
prepare a suitable data stream via implementation of `StreamInterface` contract or just utilize `StringStream` 
built-in class to write content into temporary buffer. Then just ask protocol to read or write something in it:

```php
<?php

use Alpari\BinaryProtocol\BinaryProtocol;
use Alpari\BinaryProtocol\Type\Int32;

$protocol = new BinaryProtocol();
$protocol->write(-10000, [Int32::class], $stream);
$value = $protocol->read([Int32::class], $stream);
var_dump($value);
```

Lazy-evaluated fields
---------------------
In some cases binary protocols contains fields that depend on existing data, for example: packet length, CRC,
lazy-evaluated or encoded fields. Such fields should be calculated lazily before writing them to a stream. To use
lazy evaluation just declare the value of such field as `Closure` instance.

Here is an example of `length` field calculation for the Kafka's `Record` class:
```php
<?php

use Alpari\BinaryProtocol\BinaryProtocolInterface;
use Alpari\BinaryProtocol\Type\SchemeType;
use Alpari\BinaryProtocol\SchemeDefinitionInterface;

class Record implements SchemeDefinitionInterface
{
    /**
     * Record constructor
     */
    public function __construct(
        string $value,
        ?string $key = null,
        array $headers = [],
        int $attributes = 0,
        int $timestampDelta = 0,
        int $offsetDelta = 0
    ) {
        $this->value          = $value;
        $this->key            = $key;
        $this->headers        = $headers;
        $this->attributes     = $attributes;
        $this->timestampDelta = $timestampDelta;
        $this->offsetDelta    = $offsetDelta;

        // Length field uses delayed evaluation to allow size calculation
        $this->length = function (BinaryProtocolInterface $scheme, string $path) {
            // To calculate full length we use scheme without `length` field
            $recordSchemeDefinition = self::getDefinition();
            unset($recordSchemeDefinition['length']);
            $recordSchemeType = [SchemeType::class => ['class' => self::class, 'scheme' => $recordSchemeDefinition]];
            // Redefine our lazy field with calculated value
            $this->length = $size = $scheme->sizeOf($this, $recordSchemeType, $path);
            return $size;
        };
    }

    /**
     * @inheritdoc
     */
    public static function getDefinition(): array
    {
        return [
            'length'         => [VarIntZigZag::class],
            'attributes'     => [Int8::class],
            'timestampDelta' => [VarLongZigZag::class],
            'offsetDelta'    => [VarIntZigZag::class],
            'key'            => [BinaryString::class => [
                'size'     => [VarIntZigZag::class],
                'nullable' => true
            ]],
            'value'          => [BinaryString::class => ['size' => [VarIntZigZag::class]]],
            'headers'        => [ArrayOf::class => [
                'key'  => 'key',
                'item' => [Header::class],
                'size' => [VarInt::class]
            ]]
        ];
    }
}
```
