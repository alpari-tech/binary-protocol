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
