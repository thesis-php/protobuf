<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class FeatureSet
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\EnumT(FeatureSet\FieldPresence::class))]
        public ?FeatureSet\FieldPresence $presence = null,
        #[Reflection\Field(2, new Reflection\EnumT(FeatureSet\EnumType::class))]
        public ?FeatureSet\EnumType $enumType = null,
        #[Reflection\Field(3, new Reflection\EnumT(FeatureSet\RepeatedFieldEncoding::class))]
        public ?FeatureSet\RepeatedFieldEncoding $repeatedFieldEncoding = null,
        #[Reflection\Field(4, new Reflection\EnumT(FeatureSet\Utf8Validation::class))]
        public ?FeatureSet\Utf8Validation $utf8Validation = null,
        #[Reflection\Field(5, new Reflection\EnumT(FeatureSet\MessageEncoding::class))]
        public ?FeatureSet\MessageEncoding $messageEncoding = null,
        #[Reflection\Field(6, new Reflection\EnumT(FeatureSet\JsonFormat::class))]
        public ?FeatureSet\JsonFormat $jsonFormat = null,
        #[Reflection\Field(7, new Reflection\EnumT(FeatureSet\EnforceNamingStyle::class))]
        public ?FeatureSet\EnforceNamingStyle $enforceNamingStyle = null,
        #[Reflection\Field(8, new Reflection\EnumT(FeatureSet\VisibilityFeature\DefaultSymbolVisibility::class))]
        public ?FeatureSet\VisibilityFeature\DefaultSymbolVisibility $defaultSymbolVisibility = null,
    ) {}
}
