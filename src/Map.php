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
    private const string TRUE = 't';
    private const string FALSE = 'f';
    private const string STRING_QUOTE = '`';

    /** @var array<non-empty-string, KVPair<K, V>> */
    private array $pairs = [];

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
        /** @var self<NK, NV> */
        return new self(...array_map(
            static fn(mixed $key, mixed $value): KVPair => new KVPair($key, $value),
            array_keys($values),
            array_values($values),
        ));
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     */
    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->pairs[self::hash($offset)]);
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     * @throws Exception\KeyIsNotDefined
     */
    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        $key = self::hash($offset);

        if (isset($this->pairs[$key])) {
            return $this->pairs[$key]->value;
        }

        throw new Exception\KeyIsNotDefined(self::stringify($offset));
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        /** @var K $key */
        $key = $offset;

        $this->pairs[self::hash($key)] = new KVPair(
            $key,
            $value,
        );
    }

    /**
     * @throws Exception\IllegalProtobufKeyMap
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->pairs[self::hash($offset)]);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->pairs as $pair) {
            yield $pair->key => $pair->value;
        }
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->pairs);
    }

    /**
     * @return non-empty-string
     * @throws Exception\IllegalProtobufKeyMap
     */
    private static function hash(mixed $value): string
    {
        if (\is_int($value)) {
            return (string) $value;
        }

        if (\is_string($value)) {
            return self::STRING_QUOTE . addcslashes($value, self::STRING_QUOTE) . self::STRING_QUOTE;
        }

        if ($value === true) {
            return self::TRUE;
        }

        if ($value === false) {
            return self::FALSE;
        }

        if ($value instanceof Number) {
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
