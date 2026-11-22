<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\SerdeBytes;
use Thesis\Protobuf\Internal\Serde\SerdeString;
use Thesis\Protobuf\Internal\Serde\SingularCodec;
use Thesis\Protobuf\Internal\Serde\ZeroValue;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Type\BytesT;
use Thesis\Protobuf\Type\StringT;
use Thesis\Protobuf\Type\Visitor\DefaultTypeVisitor;
use Thesis\Protobuf\Type\Visitor\IsPackable;

#[CoversClass(BytesT::class)]
#[CoversClass(StringT::class)]
#[CoversClass(Type\MessageT::class)]
#[CoversTrait(Type\Listed::class)]
#[CoversClass(Value::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(ZeroValue::class)]
#[CoversClass(IsPackable::class)]
#[CoversClass(DefaultTypeVisitor::class)]
#[CoversClass(ToProtobufTypeTypeVisitor::class)]
#[CoversClass(Reflector::class)]
#[CoversFunction('Thesis\Protobuf\bytesOf')]
final class BytesTypeTest extends TestCase
{
    public function testBytesIsATypeOfItsOwn(): void
    {
        self::assertSame(bytesT, bytesOf('a')->type);
        self::assertSame(bytesT, Value::bytes('a')->type);
        self::assertSame(stringT, stringOf('a')->type);
        self::assertSame('a', bytesOf('a')->value);
    }

    public function testBytesAreWrittenAsStringsAre(): void
    {
        $serializer = new Serializer();

        self::assertSame(
            $serializer->serialize(message(fieldOf(1, stringOf('abc')), fieldOf(2, listOf(stringT, ['a', 'b'])))),
            $serializer->serialize(message(fieldOf(1, bytesOf('abc')), fieldOf(2, listOf(bytesT, ['a', 'b'])))),
        );
    }

    public function testTheCodecsOfStringAndBytesDiffer(): void
    {
        $string = FieldCodecs::of(stringT);
        $bytes = FieldCodecs::of(bytesT);

        self::assertInstanceOf(SingularCodec::class, $string);
        self::assertInstanceOf(SingularCodec::class, $bytes);
        self::assertSame(SerdeString::T, $string->serializer);
        self::assertSame(SerdeBytes::T, $bytes->serializer);
        self::assertSame(SerdeBytes::T, $bytes->deserializer);
        self::assertSame(WireType::BYTES, $bytes->wireType);
    }

    public function testTheTypeVisitorsHandleBytes(): void
    {
        self::assertSame('', bytesT->accept(new ZeroValue()));
        self::assertFalse(bytesT->accept(new IsPackable()));

        $visitor = new class extends DefaultTypeVisitor {
            #[\Override]
            protected function default(Type $type): string
            {
                return $type::class;
            }
        };

        self::assertSame(BytesT::class, bytesT->accept($visitor));
        self::assertSame(StringT::class, stringT->accept($visitor));
    }

    public function testAListOfBytesIsListedByTheType(): void
    {
        $list = bytesT->list(['a', "\xFF"]);

        self::assertSame(['a', "\xFF"], $list->value);
        self::assertInstanceOf(Type\ListT::class, $list->type);
        self::assertSame(bytesT, $list->type->element);
    }

    public function testAListOfMessagesIsListedByTheType(): void
    {
        $type = messageT(fieldT(1, int32T));
        $list = $type->list([message(fieldOf(1, int32Of(5)))]);

        self::assertEquals([message(fieldOf(1, int32Of(5)))], $list->value);
        self::assertInstanceOf(Type\ListT::class, $list->type);
        self::assertSame($type, $list->type->element);
    }

    public function testTheReflectionMapsStringAndBytesToTheirTypes(): void
    {
        $type = Reflector::build()->type(BytesTypeTestMessage::class);

        self::assertSame(stringT, ($type->fields[1] ?? null)?->type);
        self::assertSame(bytesT, ($type->fields[2] ?? null)?->type);

        $list = ($type->fields[3] ?? null)?->type;
        self::assertInstanceOf(Type\ListT::class, $list);
        self::assertSame(bytesT, $list->element);

        $map = ($type->fields[4] ?? null)?->type;
        self::assertInstanceOf(Type\MapT::class, $map);
        self::assertSame(stringT, $map->keyT);
        self::assertSame(bytesT, $map->valueT);
    }
}

final readonly class BytesTypeTestMessage
{
    /**
     * @param list<string> $blobs
     * @param array<string, string> $blobsByName
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $text = '',
        #[Reflection\Field(2, Reflection\BytesT::T)]
        public string $data = '',
        #[Reflection\Field(3, new Reflection\ListT(Reflection\BytesT::T))]
        public array $blobs = [],
        #[Reflection\Field(4, new Reflection\MapT(Reflection\StringT::T, Reflection\BytesT::T))]
        public array $blobsByName = [],
    ) {}
}
