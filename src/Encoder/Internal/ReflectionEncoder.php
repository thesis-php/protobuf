<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Encoder\Internal;

use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;

/**
 * @internal
 */
final readonly class ReflectionEncoder implements Encoder
{
    public function __construct(
        private Serializer $serializer,
        private Reflector $reflector,
    ) {}

    #[\Override]
    public function encode(object $message): string
    {
        try {
            return $this->serializer->serialize(
                $this->reflector->message($message),
            );
        } catch (ValueOutOfRange $e) {
            throw $e->withProperties($this->reflector->propertyPath($message::class, $e->fields));
        }
    }
}
