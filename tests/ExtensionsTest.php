<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Internal\Extension\ExtensionCodec;
use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Exception\MappingError;
use Thesis\Protobuf\Reflection\Extension;

#[CoversClass(Extensions::class)]
#[CoversClass(ExtensionCodec::class)]
#[CoversClass(Extension::class)]
final class ExtensionsTest extends TestCase
{
    public function testAScalarExtensionIsRead(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T);
        $holder = self::decode('08015005');

        self::assertSame(5, self::extensions()->get($holder, $extension));
        self::assertTrue(self::extensions()->has($holder, $extension));
        self::assertSame(1, $holder->id);
    }

    public function testAnAbsentExtensionIsNull(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T);
        $holder = self::decode('0801');

        self::assertNull(self::extensions()->get($holder, $extension));
        self::assertFalse(self::extensions()->has($holder, $extension));
    }

    public function testOnlyTheFieldsOfTheNumberOfTheExtensionAreRead(): void
    {
        // Fields 11, 10 and 12.
        $holder = self::decode('580150056002');
        $extensions = self::extensions();

        self::assertSame(5, $extensions->get($holder, new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T)));
        self::assertSame(1, $extensions->get($holder, new Extension(ExtensionsTestHolder::class, 11, Reflection\Int32T::T)));
        self::assertSame(2, $extensions->get($holder, new Extension(ExtensionsTestHolder::class, 12, Reflection\Int32T::T)));
    }

    public function testTheLastOccurrenceOfAScalarWins(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\StringT::T);

        self::assertSame('b', self::extensions()->get(self::decode('5201610801520162'), $extension));
    }

    public function testTheValueReadIsKept(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));
        $holder = self::decode('52020801');
        $provider = new ExtensionsTestCountingProvider();
        $extensions = new Decoder\Builder()->buildExtensions($provider);

        self::assertTrue($extensions->has($holder, $extension));
        $item = $extensions->get($holder, $extension);

        self::assertEquals(new ExtensionsTestItem(1), $item);
        self::assertSame($item, $extensions->get($holder, $extension));
        self::assertSame(1, $provider->calls);
        self::assertCount(1, UnknownFields::of($holder));
    }

    public function testAnAbsentValueIsKept(): void
    {
        $provider = new ExtensionsTestCountingProvider();
        $extensions = new Decoder\Builder()->buildExtensions($provider);
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T);
        $holder = self::decode('0801');

        self::assertNull($extensions->get($holder, $extension));
        self::assertFalse($extensions->has($holder, $extension));
        self::assertSame(1, $provider->calls);
    }

    public function testTheValueIsKeptForTheObjectAndTheInstanceOfTheExtension(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));
        $same = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));
        $holder = self::decode('52020801');
        $extensions = self::extensions();

        $item = $extensions->get($holder, $extension);

        self::assertNotSame($item, $extensions->get(self::decode('52020801'), $extension));
        self::assertNotSame($item, $extensions->get($holder, $same));
        self::assertNotSame($item, self::extensions()->get($holder, $extension));
        self::assertEquals($item, $extensions->get($holder, $same));
    }

    public function testAReadThatFailsIsNotKept(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\StringT::T);
        $holder = self::decode('5201ff');
        $extensions = self::extensions();

        $failures = 0;

        for ($i = 0; $i < 2; ++$i) {
            try {
                $extensions->get($holder, $extension);
            } catch (MalformedUtf8) {
                ++$failures;
            }
        }

        self::assertSame(2, $failures);
    }

    public function testTheOccurrencesOfARepeatedExtensionAreConcatenatedPackedOrNot(): void
    {
        // 1 and 2 one by one, 3 and 4 packed, then 5.
        $holder = self::decode('50015002520203045005');
        $extensions = self::extensions();

        foreach ([null, true, false] as $packed) {
            $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ListT(Reflection\Int32T::T, $packed));

            self::assertSame([1, 2, 3, 4, 5], $extensions->get($holder, $extension));
            self::assertTrue($extensions->has($holder, $extension));
        }
    }

    public function testARepeatedExtensionOfStringsIsRead(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ListT(Reflection\StringT::T));

        self::assertSame(['a', '', 'b'], self::extensions()->get(self::decode('5201615200520162'), $extension));
    }

    public function testAnAbsentRepeatedExtensionIsAnEmptyList(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ListT(Reflection\Int32T::T));
        $holder = self::decode('0801');

        self::assertSame([], self::extensions()->get($holder, $extension));
        self::assertFalse(self::extensions()->has($holder, $extension));
    }

    public function testTheOccurrencesOfAMessageExtensionAreMerged(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));

        // {a: 1}, {b: "x", c: [3]}, {a: 2, c: [4]}.
        $item = self::extensions()->get(self::decode('52020801520512017818035204080218040801'), $extension);

        self::assertEquals(new ExtensionsTestItem(2, 'x', [3, 4]), $item);
    }

    public function testAnEmptyMessageExtensionIsAnObject(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));

        self::assertEquals(new ExtensionsTestItem(), self::extensions()->get(self::decode('5200'), $extension));
    }

    public function testTheOccurrencesOfAGroupExtensionAreMerged(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\GroupT(ExtensionsTestItem::class));

        // {a: 1}, a field of the holder, {b: "x"}.
        $item = self::extensions()->get(self::decode('5308015408025312017854'), $extension);

        self::assertEquals(new ExtensionsTestItem(1, 'x'), $item);
    }

    public function testEachOccurrenceOfARepeatedGroupExtensionIsAnElement(): void
    {
        $extension = new Extension(
            ExtensionsTestHolder::class,
            10,
            new Reflection\ListT(new Reflection\GroupT(ExtensionsTestItem::class)),
        );

        $items = self::extensions()->get(self::decode('53080154535453120178180354'), $extension);

        self::assertEquals([new ExtensionsTestItem(1), new ExtensionsTestItem(), new ExtensionsTestItem(b: 'x', c: [3])], $items);
    }

    public function testAGroupExtensionOfTheLargestFieldNumberIsRead(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 536870911, new Reflection\GroupT(ExtensionsTestItem::class));

        self::assertEquals(new ExtensionsTestItem(7), self::extensions()->get(self::decode('fbffffff0f0807fcffffff0f'), $extension));
    }

    public function testAScalarExtensionOfTheLargestFieldNumberIsRead(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 536870911, Reflection\SInt32T::T);

        self::assertSame(-3, self::extensions()->get(self::decode('f8ffffff0f05'), $extension));
    }

    public function testAnOccurrenceOfAnotherWireTypeIsSkipped(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T);

        // A varint, then a length-delimited value and a fixed32 one.
        $holder = self::decode('50055201055501000000');

        self::assertSame(5, self::extensions()->get($holder, $extension));
        self::assertNull(self::extensions()->get(self::decode('520105'), $extension));
        self::assertFalse(self::extensions()->has(self::decode('520105'), $extension));
    }

    public function testAMessageExtensionWrittenAsAGroupIsSkipped(): void
    {
        $message = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));
        $group = new Extension(ExtensionsTestHolder::class, 10, new Reflection\GroupT(ExtensionsTestItem::class));

        self::assertNull(self::extensions()->get(self::decode('53080154'), $message));
        self::assertNull(self::extensions()->get(self::decode('52020801'), $group));
    }

    public function testAnEnumExtensionIsACase(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\EnumT(ExtensionsTestColor::class));

        self::assertSame(ExtensionsTestColor::GREEN, self::extensions()->get(self::decode('5002'), $extension));
    }

    public function testAnEnumNumberWithoutACaseIsSkipped(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\EnumT(ExtensionsTestColor::class));

        self::assertNull(self::extensions()->get(self::decode('5009'), $extension));
        self::assertFalse(self::extensions()->has(self::decode('5009'), $extension));
        self::assertSame(ExtensionsTestColor::RED, self::extensions()->get(self::decode('50015009'), $extension));
    }

    public function testTheEnumNumbersWithoutACaseAreSkippedInARepeatedExtension(): void
    {
        $extension = new Extension(
            ExtensionsTestHolder::class,
            10,
            new Reflection\ListT(new Reflection\EnumT(ExtensionsTestColor::class)),
        );

        self::assertSame(
            [ExtensionsTestColor::RED, ExtensionsTestColor::GREEN],
            self::extensions()->get(self::decode('500150095002'), $extension),
        );
        self::assertFalse(self::extensions()->has(self::decode('5009'), $extension));
    }

    public function testAnUint64ExtensionIsANumber(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Uint64T::T);

        $value = self::extensions()->get(self::decode('50ffffffffffffffffff01'), $extension);

        self::assertInstanceOf(Number::class, $value);
        self::assertSame('18446744073709551615', $value->value);
    }

    public function testABytesExtensionIsNotCheckedForUtf8(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\BytesT::T);

        self::assertSame("\xff\x00", self::extensions()->get(self::decode('5202ff00'), $extension));
    }

    public function testAStringExtensionMustBeValidUtf8(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\StringT::T);
        $holder = self::decode('5201ff');

        try {
            self::extensions()->get($holder, $extension);
            self::fail('The string is not valid UTF-8.');
        } catch (MalformedUtf8 $e) {
            self::assertSame([10], $e->fields);
            self::assertSame([], $e->properties);
        }

        self::assertSame("\xff", self::extensions(new Decoder\Builder()->withUtf8Validation(false))->get($holder, $extension));
    }

    public function testAStringInAMessageExtensionMustBeValidUtf8(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));
        $holder = self::decode('52031201ff');

        try {
            self::extensions()->get($holder, $extension);
            self::fail('The string is not valid UTF-8.');
        } catch (MalformedUtf8 $e) {
            self::assertSame([10, 2], $e->fields);
            self::assertSame([ExtensionsTestItem::class . '::$b'], $e->properties);
        }

        self::assertEquals(new ExtensionsTestItem(b: "\xff"), self::extensions(new Decoder\Builder()->withUtf8Validation(false))->get($holder, $extension));
    }

    public function testTheRecursionLimitCountsTheMessagesOfTheExtension(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestNode::class));
        // A node in a node.
        $holder = self::decode('52020a00');

        self::assertEquals(
            new ExtensionsTestNode(new ExtensionsTestNode()),
            self::extensions(new Decoder\Builder()->withRecursionLimit(2))->get($holder, $extension),
        );

        $this->expectException(RecursionLimitExceeded::class);

        self::extensions(new Decoder\Builder()->withRecursionLimit(1))->get($holder, $extension);
    }

    public function testARecursionLimitOfZeroAllowsNoMessageExtension(): void
    {
        $scalar = new Extension(ExtensionsTestHolder::class, 11, Reflection\Int32T::T);
        $message = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestNode::class));
        $holder = self::decode('52005801');

        self::assertSame(1, self::extensions(new Decoder\Builder()->withRecursionLimit(0))->get($holder, $scalar));

        $this->expectException(RecursionLimitExceeded::class);

        self::extensions(new Decoder\Builder()->withRecursionLimit(0))->get($holder, $message);
    }

    public function testTheObjectsOfAnExtensionGetTheirUnknownFields(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class));
        $nested = new Extension(ExtensionsTestItem::class, 20, Reflection\Int32T::T);

        // An item with field 20, which it extends.
        $item = self::extensions()->get(self::decode('52050801a00107'), $extension);

        self::assertInstanceOf(ExtensionsTestItem::class, $item);
        self::assertSame(7, self::extensions()->get($item, $nested));
    }

    public function testTheHandlerGetsTheUnknownFieldsOfTheObjectsOfAnExtension(): void
    {
        /** @var \ArrayObject<int, array{object, non-empty-list<UnknownFields\UnknownField>}> $handled */
        $handled = new \ArrayObject();
        $extensions = new Decoder\Builder()
            ->withUnknownHandler(new UnknownFields\UnknownFieldsCallback(
                static function (object $object, array $unknowns) use ($handled): void {
                    $handled[] = [$object, $unknowns];
                },
            ))
            ->buildExtensions(UnknownFields::handler());

        $holder = self::decode('52050801a00107');
        $item = $extensions->get($holder, new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestItem::class)));

        self::assertEquals(
            [[$item, [new UnknownFields\UnknownField(new Tag(20, WireType::VARINT), new Number(7))]]],
            $handled->getArrayCopy(),
        );
        self::assertSame($item, $handled[0][0] ?? null);

        // The occurrences that are skipped are not unknown fields of any object.
        $extensions->get(self::decode('520105'), new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T));

        self::assertCount(1, $handled);
    }

    public function testTheUnknownFieldsAreTakenFromTheProvider(): void
    {
        $provider = new class implements UnknownFields\Provider {
            #[\Override]
            public function provide(object $message): array
            {
                return [new UnknownFields\UnknownField(new Tag(10, WireType::VARINT), new Number(5))];
            }
        };
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T);

        self::assertSame(5, new Decoder\Builder()->buildExtensions($provider)->get(new ExtensionsTestHolder(), $extension));
        self::assertNull(self::extensions()->get(new ExtensionsTestHolder(), $extension));
    }

    public function testTheObjectsOfAnExtensionAreDecodedWithTheZeroValuesIfAsked(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestRequired::class));
        $holder = self::decode('5200');

        self::assertEquals(new ExtensionsTestRequired(0), self::extensions(new Decoder\Builder()->withMissingFieldsAsZeroValues(true))->get($holder, $extension));

        $this->expectException(MappingError::class);

        self::extensions()->get($holder, $extension);
    }

    public function testTheClassesOfAnAbsentExtensionAreNotReflected(): void
    {
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT(ExtensionsTestInvalid::class));

        self::assertNull(self::extensions()->get(self::decode('5801'), $extension));

        $this->expectException(InvalidProperty::class);

        self::extensions()->get(self::decode('5200'), $extension);
    }

    public function testAnExtensionOfAnotherClassIsRejected(): void
    {
        $extension = new Extension(ExtensionsTestItem::class, 10, Reflection\Int32T::T);

        $this->expectExceptionObject(new \InvalidArgumentException(\sprintf(
            'Extension 10 extends "%s", not "%s".',
            ExtensionsTestItem::class,
            ExtensionsTestHolder::class,
        )));

        self::extensions()->has(self::decode('5005'), $extension);
    }

    public function testAnExtensionCannotBeAMap(): void
    {
        $this->expectExceptionObject(new InvalidSchema('An extension cannot be a map: protobuf has no such extensions.'));

        new Extension(ExtensionsTestHolder::class, 10, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T));
    }

    public function testAStringInARepeatedGroupExtensionIsLocatedFromTheClassOfTheElements(): void
    {
        $extension = new Extension(
            ExtensionsTestHolder::class,
            10,
            new Reflection\ListT(new Reflection\GroupT(ExtensionsTestNamed::class)),
        );

        try {
            // An element in a list of the element.
            self::extensions()->get(self::decode('530a031201ff54'), $extension);
            self::fail('The string is not valid UTF-8.');
        } catch (MalformedUtf8 $e) {
            self::assertSame([10, 1, 2], $e->fields);
            self::assertSame([ExtensionsTestNamed::class . '::$item', ExtensionsTestItem::class . '::$b'], $e->properties);
        }
    }

    public function testAClassOfTheValueThatDoesNotExistIsADecodingError(): void
    {
        /** @phpstan-ignore argument.type */
        $extension = new Extension(ExtensionsTestHolder::class, 10, new Reflection\ObjectT('ExtensionsTestNoSuchClass'));

        $this->expectException(Decoder\DecodingError::class);

        self::extensions()->get(self::decode('5200'), $extension);
    }

    public function testTheExtensionsAreReadFromTheUnknownHandlerOfTheBuilder(): void
    {
        $handler = new ExtensionsTestHandler();
        $extension = new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T);
        $builder = new Decoder\Builder()->withUnknownHandler($handler);

        $holder = $builder->build()->decode((string) hex2bin('5005'), ExtensionsTestHolder::class);

        self::assertSame([], UnknownFields::of($holder));
        self::assertSame(5, $builder->buildExtensions()->get($holder, $extension));
    }

    public function testAnUnknownHandlerThatDoesNotProvideTheFieldsNeedsAProvider(): void
    {
        $builder = new Decoder\Builder()->withUnknownHandler(new UnknownFields\UnknownFieldsCallback(static function (): void {}));

        self::assertSame(
            5,
            $builder->buildExtensions(UnknownFields::handler())->get(self::decode('5005'), new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T)),
        );

        $this->expectExceptionObject(new \LogicException(\sprintf(
            'Extensions are read from the unknown fields of an object, but the unknown handler of the builder (%s) does not provide them: pass the provider of the fields it gets.',
            UnknownFields\UnknownFieldsCallback::class,
        )));

        $builder->buildExtensions();
    }

    public function testNoUnknownHandlerNeedsAProvider(): void
    {
        $builder = new Decoder\Builder()->withUnknownHandler(null);

        self::assertSame(
            5,
            $builder->buildExtensions(UnknownFields::handler())->get(self::decode('5005'), new Extension(ExtensionsTestHolder::class, 10, Reflection\Int32T::T)),
        );

        $this->expectExceptionObject(new \LogicException(
            'Extensions are read from the unknown fields of an object, but the unknown handler of the builder (null) does not provide them: pass the provider of the fields it gets.',
        ));

        $builder->buildExtensions();
    }

    private static function extensions(Decoder\Builder $builder = new Decoder\Builder()): Extensions
    {
        return $builder->buildExtensions();
    }

    private static function decode(string $hex): ExtensionsTestHolder
    {
        return Decoder\Builder::buildDefault()->decode((string) hex2bin($hex), ExtensionsTestHolder::class);
    }
}

final readonly class ExtensionsTestHolder
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
    ) {}
}

final readonly class ExtensionsTestItem
{
    /**
     * @param list<int> $c
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $a = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $b = null,
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Int32T::T, packed: false))]
        public array $c = [],
    ) {}
}

final readonly class ExtensionsTestNamed
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(ExtensionsTestItem::class))]
        public ?ExtensionsTestItem $item = null,
    ) {}
}

final class ExtensionsTestCountingProvider implements UnknownFields\Provider
{
    public int $calls = 0;

    #[\Override]
    public function provide(object $message): array
    {
        ++$this->calls;

        return UnknownFields::of($message);
    }
}

/**
 * Keeps the unknown fields of the objects decoded and gives them back, as UnknownFields does.
 */
final class ExtensionsTestHandler implements UnknownFields\Handler, UnknownFields\Provider
{
    /** @var \WeakMap<object, list<UnknownFields\UnknownField>> */
    private \WeakMap $fields;

    public function __construct()
    {
        $this->fields = new \WeakMap();
    }

    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        $this->fields[$message] = $unknowns;
    }

    #[\Override]
    public function provide(object $message): array
    {
        return $this->fields[$message] ?? [];
    }
}

final readonly class ExtensionsTestNode
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
    ) {}
}

final readonly class ExtensionsTestRequired
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value,
    ) {}
}

final readonly class ExtensionsTestInvalid
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $a = null,
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $b = null,
    ) {}
}

enum ExtensionsTestColor: int
{
    case RED = 1;
    case GREEN = 2;
}
