<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * A field that holds an object of the class, as {@see ObjectT} does, but is written as a group: the fields of the
 * object between a start group tag and an end group tag instead of after their length. It is a group field of proto2
 * and a field with `features.message_encoding = DELIMITED` of editions. The encoding belongs to the field: another
 * field may hold an object of the same class as an ObjectT.
 *
 * @api
 * @template-covariant T of object
 * @template-implements Type<T, 'repeatable', 'not-indexed', 'not-map-value'>
 */
final readonly class GroupT implements Type
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        public string $class,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->group($this);
    }
}
