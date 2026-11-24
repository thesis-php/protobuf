<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

/**
 * The PHP types a property may have to hold the values of a protobuf type.
 *
 * @internal
 */
final readonly class ExpectedPhpType
{
    /**
     * @param list<non-empty-string> $builtins builtin types that hold the values, e.g. "int"
     * @param ?class-string $class the class of the values, if they are objects
     */
    public function __construct(
        public array $builtins = [],
        public ?string $class = null,
    ) {}

    /**
     * Whether the value is one of the types, as a property of the type would hold it: an int is a float too.
     */
    public function holds(mixed $value): bool
    {
        if ($this->class !== null && $value instanceof $this->class) {
            return true;
        }

        $type = get_debug_type($value);

        return \in_array($type, $this->builtins, true) || ($type === 'int' && \in_array('float', $this->builtins, true));
    }

    /**
     * @param class-string $self the class that declares the property, which "self" and "static" stand for
     */
    public function accepts(?\ReflectionType $type, string $self): bool
    {
        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();

            if ($name === 'mixed' || \in_array($name, $this->builtins, true)) {
                return true;
            }

            if ($this->class === null) {
                return false;
            }

            if ($name === 'object') {
                return true;
            }

            if ($name === 'self' || $name === 'static') {
                $name = $self;
            }

            return is_a($this->class, $name, true);
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_any($type->getTypes(), fn(\ReflectionType $member): bool => $this->accepts($member, $self));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return array_all($type->getTypes(), fn(\ReflectionType $member): bool => $this->accepts($member, $self));
        }

        // An untyped property holds anything.
        return true;
    }

    /**
     * @return non-empty-string
     */
    public function describe(): string
    {
        return $this->class ?? $this->builtins[0] ?? 'mixed';
    }
}
