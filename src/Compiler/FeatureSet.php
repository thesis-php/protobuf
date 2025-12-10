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
        public FeatureSet\FieldPresence $presence = FeatureSet\FieldPresence::Unknown,
        #[Reflection\Field(2, new Reflection\EnumT(FeatureSet\EnumType::class))]
        public FeatureSet\EnumType $enumType = FeatureSet\EnumType::Unknown,
        #[Reflection\Field(3, new Reflection\EnumT(FeatureSet\RepeatedFieldEncoding::class))]
        public FeatureSet\RepeatedFieldEncoding $repeatedFieldEncoding = FeatureSet\RepeatedFieldEncoding::Unknown,
        #[Reflection\Field(4, new Reflection\EnumT(FeatureSet\Utf8Validation::class))]
        public FeatureSet\Utf8Validation $utf8Validation = FeatureSet\Utf8Validation::Unknown,
        #[Reflection\Field(5, new Reflection\EnumT(FeatureSet\MessageEncoding::class))]
        public FeatureSet\MessageEncoding $messageEncoding = FeatureSet\MessageEncoding::Unknown,
        #[Reflection\Field(6, new Reflection\EnumT(FeatureSet\JsonFormat::class))]
        public FeatureSet\JsonFormat $jsonFormat = FeatureSet\JsonFormat::Unknown,
        #[Reflection\Field(7, new Reflection\EnumT(FeatureSet\EnforceNamingStyle::class))]
        public FeatureSet\EnforceNamingStyle $enforceNamingStyle = FeatureSet\EnforceNamingStyle::Unknown,
        #[Reflection\Field(8, new Reflection\EnumT(FeatureSet\DefaultSymbolVisibility::class))]
        public FeatureSet\DefaultSymbolVisibility $defaultSymbolVisibility = FeatureSet\DefaultSymbolVisibility::Unknown,
    ) {}
}
