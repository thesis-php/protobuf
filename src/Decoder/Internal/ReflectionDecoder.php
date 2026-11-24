<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder\Internal;

use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Exception\MalformedUtf8;
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
            try {
                $message = $this->serializer->deserialize($this->reflector->type($classType), $buffer);
            } catch (MalformedUtf8 $e) {
                throw $e->withProperties($this->reflector->propertyPath($classType, $e->fields));
            }

            // A string of an extension is located from the object it extends, as Extensions::get() locates it.
            return $this->reflector->map($message, $classType);
        } catch (\ReflectionException $e) {
            throw new Decoder\DecodingError($e->getMessage(), $e->getCode(), $e);
        }
    }
}
