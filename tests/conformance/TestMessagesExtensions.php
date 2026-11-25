<?php

/**
 * The extensions that the .proto files of the test messages declare with `extend`, a class for each file, as
 * thesis/protoc-plugin is to generate them: each extension is returned by a static method that keeps it in a static
 * variable, so that every call gives the same instance. The plugin does not generate extensions yet: they are written
 * here by hand, and `extensions.php` registers them in the global pool.
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

/**
 * The extensions of google/protobuf/test_messages_proto2.proto.
 */
final class TestMessagesProto2Extensions
{
    /**
     * @return Extension<int>
     */
    public static function extensionInt32(): Extension
    {
        /** @var Extension<int> $extension */
        static $extension = new Extension(Proto2\TestAllTypesProto2::class, 120, Reflection\Int32T::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function extensionString(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Proto2\TestAllTypesProto2::class, 133, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function extensionBytes(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Proto2\TestAllTypesProto2::class, 134, Reflection\BytesT::T);

        return $extension;
    }

    /**
     * @return Extension<Proto2\GroupField>
     */
    public static function groupfield(): Extension
    {
        /** @var Extension<Proto2\GroupField> $extension */
        static $extension = new Extension(Proto2\TestAllTypesProto2::class, 121, new Reflection\GroupT(Proto2\GroupField::class));

        return $extension;
    }
}


/**
 * The extensions of editions/golden/test_messages_proto2_editions.proto.
 */
final class TestMessagesProto2EditionsExtensions
{
    /**
     * @return Extension<int>
     */
    public static function extensionInt32(): Extension
    {
        /** @var Extension<int> $extension */
        static $extension = new Extension(Editions\Proto2\TestAllTypesProto2::class, 120, Reflection\Int32T::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function extensionString(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Editions\Proto2\TestAllTypesProto2::class, 133, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function extensionBytes(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Editions\Proto2\TestAllTypesProto2::class, 134, Reflection\BytesT::T);

        return $extension;
    }

    /**
     * @return Extension<Editions\Proto2\GroupField>
     */
    public static function groupfield(): Extension
    {
        /** @var Extension<Editions\Proto2\GroupField> $extension */
        static $extension = new Extension(Editions\Proto2\TestAllTypesProto2::class, 121, new Reflection\GroupT(Editions\Proto2\GroupField::class));

        return $extension;
    }
}


/**
 * The extensions of conformance/test_protos/test_messages_edition2023.proto, whose messages are delimited by default.
 */
final class TestMessagesEdition2023Extensions
{
    /**
     * @return Extension<int>
     */
    public static function extensionInt32(): Extension
    {
        /** @var Extension<int> $extension */
        static $extension = new Extension(Editions\TestAllTypesEdition2023::class, 120, Reflection\Int32T::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function extensionString(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Editions\TestAllTypesEdition2023::class, 133, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function extensionBytes(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Editions\TestAllTypesEdition2023::class, 134, Reflection\BytesT::T);

        return $extension;
    }

    /**
     * @return Extension<Editions\GroupLikeType>
     */
    public static function groupliketype(): Extension
    {
        /** @var Extension<Editions\GroupLikeType> $extension */
        static $extension = new Extension(Editions\TestAllTypesEdition2023::class, 121, new Reflection\GroupT(Editions\GroupLikeType::class));

        return $extension;
    }

    /**
     * @return Extension<Editions\GroupLikeType>
     */
    public static function delimitedExt(): Extension
    {
        /** @var Extension<Editions\GroupLikeType> $extension */
        static $extension = new Extension(Editions\TestAllTypesEdition2023::class, 122, new Reflection\GroupT(Editions\GroupLikeType::class));

        return $extension;
    }
}
