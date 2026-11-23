<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Exception\ValueOutOfRange;

/**
 * A type of constant width: a packed record of its elements is written and read with one call to pack() and unpack()
 * rather than one per element.
 *
 * @internal
 * @template T
 */
interface SerdePacked
{
    /**
     * @param list<mixed> $values
     * @return ?string the elements one after another; null if one of them is not of the PHP type of the elements, for
     *         them to be written one by one, which fails as it does for a single value
     * @throws ValueOutOfRange
     */
    public function serializePacked(array $values): ?string;

    /**
     * @param non-empty-string $bytes whole elements, one after another
     * @return array<int, T> the elements in order, whatever their keys
     */
    public function deserializePacked(string $bytes): array;
}
