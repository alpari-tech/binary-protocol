Alpari BinaryProtocol library 
-----------------

`alpari/binary-protocol` is a PHP library that provides an API for working with binary protocols, like FCGI, 
ApacheKafka and others. Applications can use this library to describe binary data and work with high-level API 
without manual working with binary data packing/unpacking.


Installation
------------

`alpari/binary-protocol` can be installed with composer. To download this library please type:

``` bash
composer require alpari/kafka-client
```
 
FieldInterface API
------------
The FieldInterface API describes low-level representation of data, for example Int8, Int16, String, etc...

```php
/**
 * Declares the single binary field that can be packed/unpacked from/to binary stream
 */
interface FieldInterface
{

    /**
     * Reads a value of field from the stream
     *
     * @param StreamInterface $stream    Instance of stream to read value from
     * @param string          $fieldPath Path to the field to simplify debug of complex hierarchial structures
     *
     * @return mixed
     */
    public function read(StreamInterface $stream, string $fieldPath);

    /**
     * Writes the value of field to the given stream
     *
     * @param mixed           $value     Field value to write
     * @param StreamInterface $stream    Instance of stream to write to
     * @param string          $fieldPath Path to the field to simplify debug of complex hierarchial structures
     *
     * @return void
     */
    public function write($value, StreamInterface $stream, string $fieldPath): void;

    /**
     * Calculates the size in bytes of single item for given value
     *
     * @param mixed  $value     Field value to write
     * @param string $fieldPath Path to the field to simplify debug of complex hierarchial structures
     *
     * @return int
     */
    public function getSize($value = null, string $fieldPath =''): int;
}
``` 
Each data type should be added as a separate class implementing `FieldInterface` contract with logic of packing and 
unpacking data to/from `StreamInterface`

As you can see there are two main methods which are `read()` and `write()` that are used for reading and writing binary 
data to the stream. 

The method `getSize()` is used for calculation of data size in bytes. For simple data types it will return constant 
value and for more complex structures like arrays or objects it should be able to calculate the size of such field.

Method `getFormat()` is declared in the interface, but it is not used right now. It can be used later for fixed-size 
arrays or structures to speed up parsing, for example, array of Int16. 

StructureInterface API
------------

`StructureInterface` helps to use complex binary data structures in your application. Each complex type from the 
binary protocol should be represented as a DTO (Plain PHP object with public properties) implementing the 
`StructureInterface` and it's method `getScheme()` which is declared as following.

```php
<?php

/**
 * StructureInterface represents classes that can be encoded as packed structure
 */
interface StructureInterface
{
    /**
     * Returns definition of binary packet for the class or object
     */
    public static function getScheme(): array;
}
```

Here is an example of definition of binary packet that is used for Kafka client:
```php
<?php

use Alpari\BinaryProtocol\Field\Int16BE as Int16;
use Alpari\BinaryProtocol\StructureInterface;

/**
 * ApiVersions response data
 */
class ApiVersionsResponseMetadata implements StructureInterface
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
    public static function getScheme(): array
    {
        return [
            'apiKey'     => [Int16::class],
            'minVersion' => [Int16::class],
            'maxVersion' => [Int16::class],
        ];
    }
}
```
To add the binary scheme to your existing class, just add "class key" => "type" mapping as a scheme definition. Please
 note that value is declared as an array in the `getScheme()` method. This format is used to define additional 
 arguments for complex types.
 
`ArrayOf` type
---------------
 
 `ArrayOf` field is used to declare the sequence of repeated binary items (integers, strings or objects). It has 
 several options that can be applied as associative array values.
 
```php
 return [
     'partitions' => [ArrayOf::class => [
        'item' => [Int32::class]
    ]]
];
``` 
Available options for `ArrayOf` are:
 - `item` **(required)** Definition of single item type, which is used in array, e.g `[Int32::class]`
 - `size` (optional) Definition of size item type, you can use `[VarInt::class]`, `[Int64::class]` or anything else 
 - `key` (optional) Name of the field from an item object that will be used as associative key in array
 - `nullable` (optional) Boolean flag to enable `null` values encoded as size = -1

`BinaryString` type
---------------
`BinaryString` class is general representation of any string or raw buffers with binary data. Typically it is encoded
 as buffer length field and sequence of bytes as a data.

Available options for `BinaryString` are:
 - `envelope` (optional) Declares the definition of nested data, that is stored in the binary packet.
 - `size` (optional) Definition of size item type, you can use `[VarInt::class]`, `[Int64::class]` or anything else 
 - `nullable` (optional) Boolean flag to enable `null` values encoded as size = -1
 
`envelope` feature is used for open-protocols, when low-level protocol just defines some buffer and top-level 
protocol extracts specific packet from this temporary buffer. Example is TCP packet over IP. 

By default `BinaryString` uses `Int16` in big-endian encoding, if you need bigged data, just configure the `size` 
option accordingly.

`Structure` type
---------------
`Structure` class represents a complex structure that can be mapped to PHP's object instance. It has following options:
 - `class` **(required)** String with FQN name of the corresponding class that holds this structure
 - `scheme` (optional) Definition of each item as key => definition. Key is used as property name for the object.

**Note**: if you class implements the `StructureInterface`, then you can simply refer to it in the scheme as 
`[SomeStructure::class]`.


`BinaryProtocol` class usage
--------------------------
This library introduces `BinaryProtocol` class as a top-level binary coder/decoder API. To read the data, please, 
prepare a suitable data stream via implementation of `StreamInterface` contract or just utilze `StringStream` 
built-in class to write content into temporary buffer. Then just ask protocol to read or write something in it:

```php
<?php

use Alpari\BinaryProtocol\BinaryProtocol;
use Alpari\BinaryProtocol\Field\Int32;

$protocol = new BinaryProtocol();
$protocol->write(-10000, [Int32::class], $stream);
$value = $protocol->read([Int32::class], $stream);
var_dump($value);
```
