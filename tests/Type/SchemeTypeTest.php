<?php
/*
 * This file is part of the Alpari BinaryProtocol library.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Alpari\BinaryProtocol\Type;

use Alpari\BinaryProtocol\BinaryProtocol;
use Alpari\BinaryProtocol\Stream\StringStream;
use Alpari\BinaryProtocol\SchemeDefinitionInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SchemeTypeTest extends TestCase
{
    /**
     * @var SchemeType
     */
    private $field;

    /**
     * @var string
     */
    private $className;

    /**
     * @inheritDoc
     *
     * @throws \ReflectionException
     */
    protected function setUp()
    {
        $instance = new class implements SchemeDefinitionInterface {
            public $key;
            public $value;

            public static function getDefinition(): array
            {
                return [
                    'key'   => [BinaryString::class => ['size' => [Int8::class]]],
                    'value' => [BinaryString::class => ['size' => [Int8::class]]]
                ];
            }
        };
        $this->className = get_class($instance);
        $this->field     = new SchemeType(new BinaryProtocol(), [
            'class' => $this->className
        ]);
    }

    public function testConstructorRequiresClassOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Structure expects the `class` option to be specified');
        new SchemeType(new BinaryProtocol(), []);
    }

    public function testConstructorRequiresClassToBeStructureInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class should implement `SchemeDefinitionInterface`');
        new SchemeType(new BinaryProtocol(), [
            'class' => InvalidArgumentException::class
        ]);
    }

    public function testGetFormat(): void
    {
        // Currently, format is not determined, but later it will be possible to do complex expressions
        $this->assertEquals(null, $this->field->getFormat());
    }

    public function testWrite(): void
    {
        $stream   = new StringStream();
        $instance = new $this->className;
        $instance->key   = 'test';
        $instance->value = 'value';
        $this->field->write($instance, $stream, '/');
        $buffer         = $stream->getBuffer();
        $expectedLength =
            /* INT8 Key Length */ 1 +
            /* 'test' length */ 4 +
            /* INT8 Value Length */ 1 +
            /* 'value' Length */ 5;

        $this->assertEquals($expectedLength, strlen($buffer));
        $this->assertEquals('04746573740576616c7565', bin2hex($stream->getBuffer()));

    }

    public function testRead(): void
    {
        $stream = new StringStream("\x04\x74\x65\x73\x74\x05\x76\x61\x6c\x75\x65");
        $value  = $this->field->read($stream, '/');
        $this->assertInstanceOf($this->className, $value);
        $this->assertEquals('test', $value->key);
        $this->assertEquals('value', $value->value);
    }

    public function testGetSize(): void
    {
        $instance        = new $this->className;
        $instance->key   = 'test';
        $instance->value = 'value';

        $expectedLength =
            /* INT8 Key Length */ 1 +
            /* 'test' length */ 4 +
            /* INT8 Value Length */ 1 +
            /* 'value' Length */ 5;

        $this->assertEquals($expectedLength, $this->field->getSize($instance));
    }
}
