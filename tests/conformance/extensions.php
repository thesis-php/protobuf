<?php

/**
 * Registers the extensions of the test messages, those of `TestMessagesExtensions.php`, in the global pool, as
 * the autoload file that thesis/protoc-plugin generates is to register them: a call for each class extended, with
 * a resolver that matches the number with the static method that returns the extension. The resolver loads no class
 * until the decoder first asks it for a number of an extension, among the unknown fields of an object of the class
 * extended, unlike `Class::method(...)`, which would load the class when the registration is made.
 */

declare(strict_types=1);

namespace Thesis\Protobuf\Conformance;

use ProtobufTestMessages\Editions;
use ProtobufTestMessages\Proto2;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\ExtensionPool;

ExtensionPool::global()->register(
    Proto2\TestAllTypesProto2::class,
    static fn(int $number): ?Extension => match ($number) {
        120 => TestMessagesProto2Extensions::extensionInt32(),
        133 => TestMessagesProto2Extensions::extensionString(),
        134 => TestMessagesProto2Extensions::extensionBytes(),
        121 => TestMessagesProto2Extensions::groupfield(),
        default => null,
    },
);

ExtensionPool::global()->register(
    Editions\Proto2\TestAllTypesProto2::class,
    static fn(int $number): ?Extension => match ($number) {
        120 => TestMessagesProto2EditionsExtensions::extensionInt32(),
        133 => TestMessagesProto2EditionsExtensions::extensionString(),
        134 => TestMessagesProto2EditionsExtensions::extensionBytes(),
        121 => TestMessagesProto2EditionsExtensions::groupfield(),
        default => null,
    },
);

ExtensionPool::global()->register(
    Editions\TestAllTypesEdition2023::class,
    static fn(int $number): ?Extension => match ($number) {
        120 => TestMessagesEdition2023Extensions::extensionInt32(),
        133 => TestMessagesEdition2023Extensions::extensionString(),
        134 => TestMessagesEdition2023Extensions::extensionBytes(),
        121 => TestMessagesEdition2023Extensions::groupliketype(),
        122 => TestMessagesEdition2023Extensions::delimitedExt(),
        default => null,
    },
);
