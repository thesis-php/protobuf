<?php

/**
 * The extensions that the .proto files of the test messages declare with `extend`, which testee.php registers in its
 * decoder, so that it checks them as the reference implementation does. thesis/protoc-plugin does not generate
 * extensions yet: they are written here by hand, and the code it generates for them is to replace this file.
 *
 * Not here: the extensions of `MessageSetCorrect`, a MessageSet, whose class the plugin does not mark with
 * `Reflection\MessageSet`, so that they would be read as fields of their numbers instead of as items; and those of
 * `TestAllRequiredTypesProto2`, which the runner sends no payloads of.
 */

declare(strict_types=1);

namespace Thesis\Protobuf\Conformance;

use ProtobufTestMessages\Editions;
use ProtobufTestMessages\Proto2;
use Thesis\Protobuf\Reflection;
use Thesis\Protobuf\Reflection\Extension;

/** @var list<Extension<mixed>> */
return [
    // google/protobuf/test_messages_proto2.proto
    new Extension(Proto2\TestAllTypesProto2::class, 120, Reflection\Int32T::T),
    new Extension(Proto2\TestAllTypesProto2::class, 133, Reflection\StringT::T),
    new Extension(Proto2\TestAllTypesProto2::class, 134, Reflection\BytesT::T),
    new Extension(Proto2\TestAllTypesProto2::class, 121, new Reflection\GroupT(Proto2\GroupField::class)),
    // editions/golden/test_messages_proto2_editions.proto
    new Extension(Editions\Proto2\TestAllTypesProto2::class, 120, Reflection\Int32T::T),
    new Extension(Editions\Proto2\TestAllTypesProto2::class, 133, Reflection\StringT::T),
    new Extension(Editions\Proto2\TestAllTypesProto2::class, 134, Reflection\BytesT::T),
    new Extension(Editions\Proto2\TestAllTypesProto2::class, 121, new Reflection\GroupT(Editions\Proto2\GroupField::class)),
    // conformance/test_protos/test_messages_edition2023.proto, whose messages are delimited by default
    new Extension(Editions\TestAllTypesEdition2023::class, 120, Reflection\Int32T::T),
    new Extension(Editions\TestAllTypesEdition2023::class, 133, Reflection\StringT::T),
    new Extension(Editions\TestAllTypesEdition2023::class, 134, Reflection\BytesT::T),
    new Extension(Editions\TestAllTypesEdition2023::class, 121, new Reflection\GroupT(Editions\GroupLikeType::class)),
    new Extension(Editions\TestAllTypesEdition2023::class, 122, new Reflection\GroupT(Editions\GroupLikeType::class)),
];
