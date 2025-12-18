<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/type.proto
 */
final readonly class MessageType
{
    /**
     * @param list<FieldDefinition> $fields
     * @param list<Option> $options
     * @param list<string> $oneofs
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ListT(
            new Reflection\ObjectT(FieldDefinition::class),
        ))]
        public array $fields = [],
        #[Reflection\Field(3, new Reflection\ListT(Reflection\StringT::T))]
        public array $oneofs = [],
        #[Reflection\Field(4, new Reflection\ListT(
            new Reflection\ObjectT(Option::class),
        ))]
        public array $options = [],
        #[Reflection\Field(5, new Reflection\ObjectT(SourceContext::class))]
        public ?SourceContext $context = null,
        #[Reflection\Field(6, new Reflection\EnumT(Syntax::class))]
        public ?Syntax $syntax = null,
        #[Reflection\Field(7, Reflection\StringT::T)]
        public ?string $edition = null,
    ) {}
}
