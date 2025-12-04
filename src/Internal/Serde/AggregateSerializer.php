<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @template T
 * @template-implements SerializeValue<T>
 */
final readonly class AggregateSerializer implements SerializeValue
{
    /** @var array<class-string<SerializeValue<T>>, SerializeValue<T>> */
    private array $serializers;

    /**
     * @no-named-arguments
     * @param SerializeValue<T> ...$serializers
     */
    public function __construct(
        SerializeValue ...$serializers,
    ) {
        $map = [];

        foreach ($serializers as $serializer) {
            $map[$serializer::class] = $serializer;
        }

        $this->serializers = $map;
    }

    /**
     * @param SerializeValue<T> $serializer
     * @return self<T>
     */
    public function with(SerializeValue $serializer): self
    {
        return new self(...[...array_values($this->serializers), $serializer]);
    }

    /**
     * @param class-string<SerializeValue<*>> $serializer
     * @return self<T>
     */
    public function without(string $serializer): self
    {
        $map = $this->serializers;
        unset($map[$serializer]);

        return new self(...array_values($map));
    }

    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        foreach ($this->serializers as $serializer) {
            $serializer->serialize($buffer, $value);
        }
    }
}
