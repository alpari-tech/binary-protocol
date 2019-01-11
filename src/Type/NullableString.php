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

/**
 * Represents a sequence of characters or null.
 *
 * For non-null strings, first the length N is given as an integer. Then N bytes follow which are the UTF-8 encoding of
 * the character sequence.
 *
 * A null value is encoded with length of -1 and there are no following bytes.
 */
final class NullableString extends BinaryString
{
    /**
     * NullableString constructor.
     */
    public function __construct(BinaryProtocolInterface $protocol, array $options)
    {
        $options = ['nullable' => true] + $options;
        parent::__construct($protocol, $options);
    }
}
