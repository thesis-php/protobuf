<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder\Internal;

use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;

/**
 * @internal
 */
final readonly class ReflectionDecoder implements Decoder
{
    public function __construct(
        private Serializer $serializer,
        private Reflector $reflector,
    ) {}

    #[\Override]
    public function decode(string $buffer, string $classType): object
    {
        try {
            return $this->reflector->map(
                $this->serializer->deserialize(
                    $this->reflector->type($classType),
                    $buffer,
                ),
                $classType,
            );
        } catch (\Throwable $e) {
            throw new Decoder\DecodingError($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
