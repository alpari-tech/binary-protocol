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
use Alpari\BinaryProtocol\FieldInterface;
use InvalidArgumentException;

/**
 * Abstract field that stores common logic of instance initialization
 */
abstract class AbstractField implements FieldInterface
{
    /**
     * Binary scheme reader
     */
    protected $protocol;

    /**
     * Base field constructor
     *
     * Checks if given option is available for the concrete instance and performs initialization
     *
     * @param BinaryProtocol $protocol
     * @param array          $options
     */
    public function __construct(BinaryProtocol $protocol, array $options)
    {
        $this->protocol = $protocol;
        $className      = static::class;
        foreach ($options as $key => $value) {
            if (!property_exists($className, $key)) {
                throw new InvalidArgumentException("Unknown option {$key} for the {$className}");
            }
            $this->$key = $value;
        }
    }

    /**
     * Returns format for unpacking with unpack() function or null if no direct equivalent for this type
     *
     * @see pack() for details about format
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return null;
    }
}
