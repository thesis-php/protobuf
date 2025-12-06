<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use Thesis\Protobuf\Internal\Polyfill;

/**
 * @api
 * @phpstan-type Key = Number|int|numeric-string
 * @template V
 * @template-implements \ArrayAccess<Key, V>
 * @template-implements \IteratorAggregate<Number, V>
 */
final class NumberedKeyArray implements
    \ArrayAccess,
    \IteratorAggregate,
    \Countable
{
    /**
     * @param Polyfill\NumberedKeySplObjectStorage<V> $array
     */
    public function __construct(
        private Polyfill\NumberedKeySplObjectStorage $array,
    ) {}

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->array[toNumber($offset)]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->array[toNumber($offset)] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        /** @phpstan-ignore argument.type */
        $this->array[toNumber($offset)] = $value;
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->array[toNumber($offset)]);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        for (; $this->array->valid(); $this->array->next()) {
            $key = $this->array->current();
            yield $key => $this->array[$key];
        }
    }

    #[\Override]
    public function count(): int
    {
        return $this->array->count();
    }
}
