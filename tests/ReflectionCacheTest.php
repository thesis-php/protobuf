<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Decoding\FieldDecoder;
use Thesis\Protobuf\Reflection\Internal\Decoding\OneOfDecoder;
use Thesis\Protobuf\Reflection\Internal\Encoding\FieldEncoder;
use Thesis\Protobuf\Reflection\Internal\Encoding\OneOfEncoder;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(ClassReflector::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(FieldEncoder::class)]
#[CoversClass(OneOfEncoder::class)]
#[CoversClass(IsValueEmpty::class)]
#[CoversClass(ToProtobufValueTypeVisitor::class)]
#[CoversClass(FieldDecoder::class)]
#[CoversClass(OneOfDecoder::class)]
#[CoversClass(ToValueTypeVisitor::class)]
final class ReflectionCacheTest extends TestCase
{
    public function testClassIsReflectedOnce(): void
    {
        $reflector = new ClassReflector();

        self::assertSame(
            $reflector->reflect(ReflectionCacheTestInner::class),
            $reflector->reflect(ReflectionCacheTestInner::class),
        );
    }

    public function testValuesOfPropertyShareOneType(): void
    {
        $reflector = Reflector::build();

        $first = $reflector->message(self::message(1));
        $second = $reflector->message(self::message(2));

        foreach ([1, 2, 3, 4, 6] as $num) {
            self::assertArrayHasKey($num, $first->fields);
            self::assertArrayHasKey($num, $second->fields);
            self::assertSame($first->fields[$num]->value->type, $second->fields[$num]->value->type, "field {$num}");
        }
    }

    public function testMessageValueHasTypeOfItsClass(): void
    {
        $reflector = Reflector::build();

        $message = $reflector->message(self::message(1));

        self::assertArrayHasKey(4, $message->fields);
        self::assertSame($reflector->type(ReflectionCacheTestInner::class), $message->fields[4]->value->type);
    }

    public function testElementsOfListShareTypesOfTheirFields(): void
    {
        $reflector = Reflector::build();

        $message = $reflector->message(new ReflectionCacheTestList([
            new ReflectionCacheTestInner([1]),
            new ReflectionCacheTestInner([2, 3]),
        ]));

        self::assertArrayHasKey(1, $message->fields);
        $elements = $message->fields[1]->value->value;
        self::assertIsArray($elements);
        self::assertCount(2, $elements);
        $first = $elements[0] ?? null;
        $second = $elements[1] ?? null;
        self::assertInstanceOf(Message::class, $first);
        self::assertInstanceOf(Message::class, $second);
        self::assertArrayHasKey(1, $first->fields);
        self::assertArrayHasKey(1, $second->fields);
        self::assertSame($first->fields[1]->value->type, $second->fields[1]->value->type);
    }

    public function testEmptyValuesAreNotWrittenAndVariantValuesAre(): void
    {
        $encoder = Encoder\Builder::buildDefault();

        self::assertSame('', $encoder->encode(new ReflectionCacheTestMessage()));
        // The variant set is written with its zero value.
        self::assertSame('3000', bin2hex($encoder->encode(new ReflectionCacheTestMessage(
            variant: new ReflectionCacheTestVariant(0),
        ))));
    }

    public function testDecodedMapIsNotSharedWithMessage(): void
    {
        $reflector = Reflector::build();
        $bytes = Encoder\Builder::buildDefault()->encode(self::message(1));

        $message = new Serializer()->deserialize($reflector->type(ReflectionCacheTestMessage::class), $bytes);
        $object = $reflector->map($message, ReflectionCacheTestMessage::class);

        self::assertArrayHasKey(2, $message->fields);
        self::assertNotSame($message->fields[2]->value->value, $object->map);
        self::assertEquals($message->fields[2]->value->value, $object->map);
    }

    public function testFirstDeclaredVariantWithFieldInMessageIsChosen(): void
    {
        $decoder = Decoder\Builder::buildDefault();

        // Field 7 of the second variant goes first, field 6 of the first variant after it.
        $decoded = $decoder->decode("\x38\x01\x30\x02", ReflectionCacheTestMessage::class);
        self::assertEquals(new ReflectionCacheTestVariant(2), $decoded->variant);

        $decoded = $decoder->decode("\x38\x01", ReflectionCacheTestMessage::class);
        self::assertEquals(new ReflectionCacheTestOtherVariant(1), $decoded->variant);

        $decoded = $decoder->decode('', ReflectionCacheTestMessage::class);
        self::assertNull($decoded->variant);
    }

    private static function message(int $i): ReflectionCacheTestMessage
    {
        return new ReflectionCacheTestMessage(
            values: [$i, $i + 1],
            map: Map::fromArray(['a' => $i]),
            kind: ReflectionCacheTestKind::Two,
            inner: new ReflectionCacheTestInner([$i]),
            variant: new ReflectionCacheTestVariant($i),
        );
    }
}

enum ReflectionCacheTestKind: int
{
    case One = 1;
    case Two = 2;
}

final readonly class ReflectionCacheTestInner
{
    /**
     * @param list<int> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int64T::T))]
        public array $values = [],
    ) {}
}

final readonly class ReflectionCacheTestVariant
{
    public function __construct(
        #[Reflection\Field(6, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final readonly class ReflectionCacheTestOtherVariant
{
    public function __construct(
        #[Reflection\Field(7, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final readonly class ReflectionCacheTestMessage
{
    /**
     * @param list<int> $values
     * @param Map<string, int> $map
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int64T::T))]
        public array $values = [],
        #[Reflection\Field(2, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public Map $map = new Map(),
        #[Reflection\Field(3, new Reflection\EnumT(ReflectionCacheTestKind::class))]
        public ?ReflectionCacheTestKind $kind = null,
        #[Reflection\Field(4, new Reflection\ObjectT(ReflectionCacheTestInner::class))]
        public ?ReflectionCacheTestInner $inner = null,
        #[Reflection\OneOf([ReflectionCacheTestVariant::class, ReflectionCacheTestOtherVariant::class])]
        public null|ReflectionCacheTestVariant|ReflectionCacheTestOtherVariant $variant = null,
    ) {}
}

final readonly class ReflectionCacheTestList
{
    /**
     * @param list<ReflectionCacheTestInner> $items
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(ReflectionCacheTestInner::class)))]
        public array $items = [],
    ) {}
}
