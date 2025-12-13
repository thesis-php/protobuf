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
        public FeatureSet\FieldPresence $presence = FeatureSet\FieldPresence::FIELD_PRESENCE_UNKNOWN,
        #[Reflection\Field(2, new Reflection\EnumT(FeatureSet\EnumType::class))]
        public FeatureSet\EnumType $enumType = FeatureSet\EnumType::ENUM_TYPE_UNKNOWN,
        #[Reflection\Field(3, new Reflection\EnumT(FeatureSet\RepeatedFieldEncoding::class))]
        public FeatureSet\RepeatedFieldEncoding $repeatedFieldEncoding = FeatureSet\RepeatedFieldEncoding::REPEATED_FIELD_ENCODING_UNKNOWN,
        #[Reflection\Field(4, new Reflection\EnumT(FeatureSet\Utf8Validation::class))]
        public FeatureSet\Utf8Validation $utf8Validation = FeatureSet\Utf8Validation::UTF8_VALIDATION_UNKNOWN,
        #[Reflection\Field(5, new Reflection\EnumT(FeatureSet\MessageEncoding::class))]
        public FeatureSet\MessageEncoding $messageEncoding = FeatureSet\MessageEncoding::MESSAGE_ENCODING_UNKNOWN,
        #[Reflection\Field(6, new Reflection\EnumT(FeatureSet\JsonFormat::class))]
        public FeatureSet\JsonFormat $jsonFormat = FeatureSet\JsonFormat::JSON_FORMAT_UNKNOWN,
        #[Reflection\Field(7, new Reflection\EnumT(FeatureSet\EnforceNamingStyle::class))]
        public FeatureSet\EnforceNamingStyle $enforceNamingStyle = FeatureSet\EnforceNamingStyle::ENFORCE_NAMING_STYLE_UNKNOWN,
        #[Reflection\Field(8, new Reflection\EnumT(FeatureSet\VisibilityFeature\DefaultSymbolVisibility::class))]
        public FeatureSet\VisibilityFeature\DefaultSymbolVisibility $defaultSymbolVisibility = FeatureSet\VisibilityFeature\DefaultSymbolVisibility::DEFAULT_SYMBOL_VISIBILITY_UNKNOWN,
    ) {}
}
