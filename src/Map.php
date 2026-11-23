<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 * @template K
 * @template V
 * @implements \IteratorAggregate<K, V>
 * @implements \ArrayAccess<K, V>
 */
final class Map implements
    \ArrayAccess,
    \IteratorAggregate,
    \Countable
{
    private const string TRUE = '`t';
    private const string FALSE = '`f';
    private const string ESCAPE = '`';

    /** @var array<array-key, K> the keys by their slot, see hash() */
    private array $keys = [];

    /** @var array<array-key, V> the values by the slot of their key, in the same order as the keys */
    private array $values = [];

    /**
     * @no-named-arguments
     * @param KVPair<K, V> ...$pairs
     */
    public function __construct(
        KVPair ...$pairs,
    ) {
        foreach ($pairs as $pair) {
            $this[$pair->key] = $pair->value;
        }
    }

    /**
     * @template NK
     * @template NV
     * @param array<NK, NV> $values
     * @return self<NK, NV>
     */
    public static function fromArray(array $values): self
    {
        /** @var self<NK, NV> $map */
        $map = new self();

        foreach ($values as $key => $value) {
            $map[$key] = $value;
        }

        return $map;
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     */
    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->keys[self::hash($offset)]);
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     * @throws Exception\KeyIsNotDefined
     */
    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        $slot = self::hash($offset);

        if (isset($this->keys[$slot])) {
            // The values are kept in the slots of their keys.
            /** @phpstan-ignore offsetAccess.notFound */
            return $this->values[$slot];
        }

        throw new Exception\KeyIsNotDefined(self::stringify($offset));
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $slot = self::hash($offset);

        /** @var K $offset */
        $this->keys[$slot] = $offset;
        $this->values[$slot] = $value;
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        $slot = self::hash($offset);

        unset($this->keys[$slot], $this->values[$slot]);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        // The entries as they are when the iteration starts, whatever is changed during it.
        $values = $this->values;

        foreach ($this->keys as $slot => $key) {
            /** @phpstan-ignore offsetAccess.notFound */
            yield $key => $values[$slot];
        }
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->keys);
    }

    /**
     * The slot of a key. An int and a Number of the same value share one, so that a uint64 key decoded as a Number
     * is found by its int: an int is its own slot, and a Number is written as digits with an optional '-' and a
     * fractional part, which PHP turns into the int key of the same value when it is one. A string is its own slot
     * too, unless it starts as such a number could, or with the escape character: then it is escaped. A bool is the
     * escape character and a letter, which no escaped string is.
     *
     * @throws Exception\IllegalProtobufKeyMap
     */
    private static function hash(mixed $value): int|string
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $first = \ord($value);

            // 0x30-0x39 are the digits, 0x2D is '-', 0x60 is the escape character.
            return ($first >= 0x30 && $first <= 0x39) || $first === 0x2D || $first === 0x60 ? self::ESCAPE . $value : $value;
        }

        if ($value === true) {
            return self::TRUE;
        }

        if ($value === false) {
            return self::FALSE;
        }

        if ($value instanceof Number) {
            // A Number keeps the scale it was made with: 1.0 is the same key as 1, as it is written.
            if ($value->scale > 0) {
                /** @var non-empty-string */
                return rtrim(rtrim($value->value, '0'), '.');
            }

            return $value->value;
        }

        throw new Exception\IllegalProtobufKeyMap($value);
    }

    /**
     * @return non-empty-string
     * @throws Exception\IllegalProtobufKeyMap
     */
    private static function stringify(mixed $value): string
    {
        return match (true) {
            \is_int($value), $value instanceof Number => (string) $value,
            \is_string($value) => \sprintf('"%s"', addcslashes($value, '"')),
            $value === true => 'true',
            $value === false => 'false',
            default => throw new Exception\IllegalProtobufKeyMap($value),
        };
    }
}
