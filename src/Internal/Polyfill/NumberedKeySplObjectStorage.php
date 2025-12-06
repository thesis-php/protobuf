<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Polyfill;

use BcMath\Number;

/**
 * @internal
 * @template V
 * @template-extends \SplObjectStorage<Number, V>
 */
final class NumberedKeySplObjectStorage extends \SplObjectStorage
{
    #[\Override]
    public function getHash(object $object): string
    {
        return $object->value;
    }
}
