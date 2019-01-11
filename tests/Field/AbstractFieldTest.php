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

namespace Alpari\BinaryProtocol\Field;

use Alpari\BinaryProtocol\BinaryProtocolInterface;
use Alpari\BinaryProtocol\Stream\StreamInterface;
use Alpari\BinaryProtocol\Stream\StringStream;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AbstractFieldTest extends TestCase
{
    /**
     * Name of anonymous class
     *
     * @var string
     */
    private static $class;

    /**
     * @var BinaryProtocolInterface
     */
    private static $protocol;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        $protocol = new class implements BinaryProtocolInterface {
            public function read(array $schemeType, StreamInterface $stream, string $path = '') {}
            public function write($value, array $schemeType, StreamInterface $stream, string $path = ''): void {}
            public function sizeOf($value, array $schemeType, string $path = ''): int {return 0;}
        };
        self::$protocol = $protocol;

        $instance = new class($protocol, []) extends AbstractField {
            protected $someOption;

            public function read(StreamInterface $stream, string $fieldPath) {}
            public function write($value, StreamInterface $stream, string $fieldPath): void {}
            public function getSize($value = null, string $fieldPath = ''): int { return 0;}
        };

        self::$class = get_class($instance);
    }

    public function testConstructorCanInitializePropertiesFromConfig(): void
    {
        $field    = new self::$class(self::$protocol, ['someOption' => 'test']);
        $property = new ReflectionProperty($field, 'someOption');
        $property->setAccessible(true);
        $this->assertEquals('test', $property->getValue($field));
    }

    public function testConstructorThrowsExceptionForUnknownConfig(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageRegExp('/^Unknown option anotherOption for the/');
        $field = new self::$class(self::$protocol, ['anotherOption' => 'test']);
    }
}
