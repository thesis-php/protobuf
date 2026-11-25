<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Internal\Extension\ExtensionChecker;
use Thesis\Protobuf\Internal\Extension\ExtensionCodec;
use Thesis\Protobuf\Internal\Extension\ExtensionValues;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\ExtensionRegistry;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(Decoder\Builder::class)]
#[CoversClass(Extensions::class)]
#[CoversClass(ReflectionDecoder::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(ExtensionChecker::class)]
#[CoversClass(ExtensionCodec::class)]
#[CoversClass(ExtensionRegistry::class)]
#[CoversClass(ExtensionValues::class)]
final class ExtensionRegistryTest extends TestCase
{
    public function testAValidExtensionChangesNeitherTheObjectNorTheBytes(): void
    {
        // A string, an item {b: "x"} and two strings of the list, after the id, and after the child too.
        $extensions = '52036162635a03120178620161620162';
        $child = '0801' . $extensions;
        $hex = '0801' . \sprintf('12%02x', \strlen($child) / 2) . $child . $extensions;
        $registered = self::builder(self::string(), self::item(), self::strings())->build();

        $holder = $registered->decode((string) hex2bin($hex), ExtensionRegistryTestHolder::class);

        self::assertEquals(Decoder\Builder::buildDefault()->decode((string) hex2bin($hex), ExtensionRegistryTestHolder::class), $holder);
        self::assertSame($hex, bin2hex(Encoder\Builder::buildDefault()->encode($holder)));
        self::assertSame('abc', self::extensions()->get($holder, self::string()));
        self::assertEquals(new ExtensionRegistryTestItem(b: 'x'), self::extensions()->get($holder, self::item()));
        self::assertSame(['a', 'b'], self::extensions()->get($holder, self::strings()));
        self::assertNotNull($holder->child);
        self::assertSame('abc', self::extensions()->get($holder->child, self::string()));
    }

    public function testAStringExtensionThatIsNotValidUtf8FailsTheDecoding(): void
    {
        $e = self::assertFailsAsGet('08015201ff', [self::string()], self::string());

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([10], $e->fields);
        self::assertSame([], $e->properties);
    }

    public function testAStringInAMessageExtensionThatIsNotValidUtf8FailsTheDecoding(): void
    {
        $e = self::assertFailsAsGet('5a031201ff', [self::string(), self::item()], self::item());

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([11, 2], $e->fields);
        self::assertSame([ExtensionRegistryTestItem::class . '::$b'], $e->properties);
    }

    public function testAStringOfARepeatedExtensionThatIsNotValidUtf8FailsTheDecoding(): void
    {
        $e = self::assertFailsAsGet('6201616201ff', [self::strings()], self::strings());

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([12], $e->fields);
    }

    public function testAStringOfAMessageSetItemThatIsNotValidUtf8FailsTheDecoding(): void
    {
        $input = (string) hex2bin('0b10641a031201ff0c');
        $expected = self::failure(static fn() => self::extensions()->get(
            Decoder\Builder::buildDefault()->decode($input, ExtensionRegistryTestSet::class),
            self::setItem(),
        ));

        $e = self::failure(static fn() => self::builder(self::setItem())->build()->decode($input, ExtensionRegistryTestSet::class));

        self::assertEquals($expected, $e);
        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([1, 3, 2], $e->fields);
        self::assertSame([ExtensionRegistryTestItem::class . '::$b'], $e->properties);
    }

    public function testAMalformedMessageExtensionFailsTheDecoding(): void
    {
        // A string of 3 bytes in an item of 2.
        $e = self::assertFailsAsGet('5a021203', [self::item()], self::item());

        self::assertInstanceOf(Exception\MalformedMessage::class, $e);
    }

    public function testAnExtensionOfANestedObjectIsLocatedFromIt(): void
    {
        $e = self::assertFailsAsGet('12055a031201ff', [self::item()], self::item(), static fn(ExtensionRegistryTestHolder $holder): ?object => $holder->child);

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([11, 2], $e->fields);
        self::assertSame([ExtensionRegistryTestItem::class . '::$b'], $e->properties);
    }

    public function testAnExtensionOfAnElementIsChecked(): void
    {
        $e = self::assertFailsAsGet('1a001a035201ff', [self::string()], self::string(), static fn(ExtensionRegistryTestHolder $holder): ?object => $holder->children[1] ?? null);

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([10], $e->fields);
    }

    public function testTheExtensionsAmongTheFieldsOfAOneofVariantAreThoseOfTheObjectThatHoldsIt(): void
    {
        $holder = self::builder(self::string())->build()->decode((string) hex2bin('20075203616263'), ExtensionRegistryTestHolder::class);

        self::assertEquals(new ExtensionRegistryTestNumber(7), $holder->choice);
        self::assertSame('abc', self::extensions()->get($holder, self::string()));

        $e = self::assertFailsAsGet('20075201ff', [self::string()], self::string());

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([10], $e->fields);
    }

    public function testAnExtensionOfAnObjectInAOneofVariantIsChecked(): void
    {
        $e = self::assertFailsAsGet(
            '2a035201ff',
            [self::string()],
            self::string(),
            static fn(ExtensionRegistryTestHolder $holder): ?object => $holder->choice instanceof ExtensionRegistryTestNested ? $holder->choice->holder : null,
        );

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([10], $e->fields);
    }

    public function testTheExtensionsOfTheValueOfAnExtensionAreChecked(): void
    {
        // An item with field 20, a string, which extends it.
        $input = (string) hex2bin('5a04a20101ff');

        self::assertEquals(new ExtensionRegistryTestHolder(), self::builder(self::item())->build()->decode($input, ExtensionRegistryTestHolder::class));

        $e = self::failure(static fn() => self::builder(self::item(), self::itemNote())->build()->decode($input, ExtensionRegistryTestHolder::class));

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([20], $e->fields);
    }

    public function testTheDecodingDoesNotFailIfStringsAreNotChecked(): void
    {
        $builder = self::builder(self::string(), self::item())->withUtf8Validation(false);

        $holder = $builder->build()->decode((string) hex2bin('5201ff5a031201ff'), ExtensionRegistryTestHolder::class);

        self::assertSame("\xff", $builder->buildExtensions()->get($holder, self::string()));
        self::assertEquals(new ExtensionRegistryTestItem(b: "\xff"), $builder->buildExtensions()->get($holder, self::item()));
    }

    public function testAnOccurrenceOfAnotherWireTypeIsAnUnknownField(): void
    {
        // A varint and a fixed32 of the string, a group of the item.
        $hex = '500555010000005b08015c';

        $holder = self::builder(self::string(), self::item())->build()->decode((string) hex2bin($hex), ExtensionRegistryTestHolder::class);

        self::assertSame($hex, bin2hex(Encoder\Builder::buildDefault()->encode($holder)));
        self::assertNull(self::extensions()->get($holder, self::string()));
        self::assertNull(self::extensions()->get($holder, self::item()));
    }

    public function testAnExtensionThatIsNotRegisteredIsNotChecked(): void
    {
        $holder = self::builder(self::item())->build()->decode((string) hex2bin('5201ff'), ExtensionRegistryTestHolder::class);

        self::assertSame('5201ff', bin2hex(Encoder\Builder::buildDefault()->encode($holder)));

        $this->expectException(MalformedUtf8::class);

        self::extensions()->get($holder, self::string());
    }

    public function testAnExtensionOfAnotherClassIsNotChecked(): void
    {
        $item = self::builder(self::string())->build()->decode((string) hex2bin('5201ff'), ExtensionRegistryTestItem::class);

        self::assertEquals(new ExtensionRegistryTestItem(), $item);
    }

    public function testTheExtensionsAreCheckedWithoutAnUnknownHandler(): void
    {
        $decoder = self::builder(self::string())->withUnknownHandler(null)->build();

        self::assertEquals(new ExtensionRegistryTestHolder(1), $decoder->decode((string) hex2bin('08015203616263'), ExtensionRegistryTestHolder::class));

        $this->expectException(MalformedUtf8::class);

        $decoder->decode((string) hex2bin('5201ff'), ExtensionRegistryTestHolder::class);
    }

    public function testAnExtensionOfAParentClassOrAnInterfaceIsChecked(): void
    {
        $extension = new Extension(ExtensionRegistryTestExtendable::class, 10, Reflection\StringT::T);

        $e = self::assertFailsAsGet('5201ff', [$extension], $extension);

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([10], $e->fields);
    }

    public function testTheMessagesOfAnExtensionCountTowardsTheRecursionLimitWithThoseThatHoldTheObject(): void
    {
        // A node in a node, in the extension of the top-level object.
        $top = (string) hex2bin('6a020a00');
        // The same in the child.
        $nested = (string) hex2bin('12046a020a00');

        self::assertEquals(new ExtensionRegistryTestHolder(), self::builder(self::node())->withRecursionLimit(2)->build()->decode($top, ExtensionRegistryTestHolder::class));
        self::assertEquals(new ExtensionRegistryTestHolder(child: new ExtensionRegistryTestHolder()), self::builder(self::node())->withRecursionLimit(3)->build()->decode($nested, ExtensionRegistryTestHolder::class));
        self::assertInstanceOf(RecursionLimitExceeded::class, self::failure(static fn() => self::builder(self::node())->withRecursionLimit(1)->build()->decode($top, ExtensionRegistryTestHolder::class)));
        self::assertInstanceOf(RecursionLimitExceeded::class, self::failure(static fn() => self::builder(self::node())->withRecursionLimit(2)->build()->decode($nested, ExtensionRegistryTestHolder::class)));

        // get() counts from the extension only.
        $child = Decoder\Builder::buildDefault()->decode($nested, ExtensionRegistryTestHolder::class)->child;
        self::assertNotNull($child);
        self::assertEquals(
            new ExtensionRegistryTestNode(new ExtensionRegistryTestNode()),
            new Decoder\Builder()->withRecursionLimit(2)->buildExtensions()->get($child, self::node()),
        );
    }

    public function testTheDepthOfAnObjectIsThatOfItsOwnMessage(): void
    {
        $decoder = self::builder(self::node())->withRecursionLimit(3)->build();
        // Two children with a node in a node each, as deep as the limit allows.
        $children = (string) hex2bin('1a046a020a001a046a020a00');

        self::assertCount(2, $decoder->decode($children, ExtensionRegistryTestHolder::class)->children);

        // After a failure, the next object decoded is the top-level one again.
        $tight = self::builder(self::node())->withRecursionLimit(2)->build();
        self::failure(static fn() => $tight->decode((string) hex2bin('12046a020a00'), ExtensionRegistryTestHolder::class));
        self::assertEquals(new ExtensionRegistryTestHolder(), $tight->decode((string) hex2bin('6a020a00'), ExtensionRegistryTestHolder::class));
    }

    public function testTheBuilderIsNotChanged(): void
    {
        $builder = new Decoder\Builder();
        $builder->withExtensions(self::string());

        self::assertEquals(new ExtensionRegistryTestHolder(), $builder->build()->decode((string) hex2bin('5201ff'), ExtensionRegistryTestHolder::class));
    }

    public function testAnInstanceRegisteredAgainIsRegisteredOnce(): void
    {
        $builder = self::builder(self::string(), self::string())->withExtensions(self::string());

        $holder = $builder->build()->decode((string) hex2bin('5203616263'), ExtensionRegistryTestHolder::class);

        self::assertSame('abc', $builder->buildExtensions()->get($holder, self::string()));
    }

    public function testExtensionsOfDifferentClassesMayHaveTheSameNumber(): void
    {
        $other = new Extension(ExtensionRegistryTestItem::class, 10, Reflection\Int32T::T);

        $holder = self::builder(self::string(), $other)->build()->decode((string) hex2bin('5203616263'), ExtensionRegistryTestHolder::class);

        self::assertSame('abc', self::extensions()->get($holder, self::string()));
    }

    public function testTwoExtensionsOfAClassWithTheSameNumberAreRejected(): void
    {
        $other = new Extension(ExtensionRegistryTestHolder::class, 10, Reflection\StringT::T);
        $message = \sprintf('Two extensions of "%s" have the number 10: a field number is declared once.', ExtensionRegistryTestHolder::class);

        self::assertEquals(new InvalidSchema($message), self::failure(static fn() => new Decoder\Builder()->withExtensions(self::string(), $other)));
        self::assertEquals(new InvalidSchema($message), self::failure(static fn() => self::builder(self::string())->withExtensions($other)));
    }

    public function testARegistryGivenToAReflectorReadsItsExtensionsWithItsOptions(): void
    {
        $other = new Extension(ExtensionRegistryTestHolder::class, 10, Reflection\StringT::T);
        $registry = new ExtensionRegistry([self::string(), self::item(), self::string()]);

        self::assertSame([self::string(), self::item()], $registry->extensions);
        self::assertSame([], new ExtensionRegistry([])->extensions);
        self::assertEquals(
            new InvalidSchema(\sprintf('Two extensions of "%s" have the number 10: a field number is declared once.', ExtensionRegistryTestHolder::class)),
            self::failure(static fn() => new ExtensionRegistry([self::string(), $other])),
        );

        $serializer = new Serializer();
        $map = static fn(string $hex, ExtensionRegistry $registry): object => Reflector::build(extensions: $registry)->map(
            $serializer->deserialize(Reflector::build()->type(ExtensionRegistryTestHolder::class), (string) hex2bin($hex)),
            ExtensionRegistryTestHolder::class,
        );

        self::assertEquals(new ExtensionRegistryTestHolder(), $map('5203616263', $registry));
        self::assertEquals(new ExtensionRegistryTestHolder(), $map('5201ff', new ExtensionRegistry([])));
        self::assertEquals(new ExtensionRegistryTestHolder(), $map('5201ff', new ExtensionRegistry([self::string()], validateUtf8: false)));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $map('5201ff', $registry)));
        // The node in the node of the extension is the second message nested.
        self::assertEquals(new ExtensionRegistryTestHolder(), $map('6a020a00', new ExtensionRegistry([self::node()], 2)));
        self::assertInstanceOf(RecursionLimitExceeded::class, self::failure(static fn() => $map('6a020a00', new ExtensionRegistry([self::node()], 1))));
    }

    public function testTwoExtensionsThatAnObjectIsAnInstanceOfTheExtendeesOfWithTheSameNumberFailTheDecoding(): void
    {
        $other = new Extension(ExtensionRegistryTestExtendable::class, 10, Reflection\Int32T::T);
        $decoder = self::builder(self::string(), $other)->build();

        // An object without unknown fields reads no extensions, nor does one without the number.
        self::assertEquals(new ExtensionRegistryTestHolder(1), $decoder->decode((string) hex2bin('0801'), ExtensionRegistryTestHolder::class));
        self::assertEquals(new ExtensionRegistryTestHolder(), $decoder->decode((string) hex2bin('5801'), ExtensionRegistryTestHolder::class));

        self::assertEquals(
            new InvalidSchema(\sprintf(
                'Two extensions of "%s" have the number 10: one extends "%s", the other "%s".',
                ExtensionRegistryTestHolder::class,
                ExtensionRegistryTestHolder::class,
                ExtensionRegistryTestExtendable::class,
            )),
            self::failure(static fn() => $decoder->decode((string) hex2bin('5001'), ExtensionRegistryTestHolder::class)),
        );
    }

    public function testAnExtensionOfAMessageSetThatIsNotAMessageFailsTheDecodingOfAnObjectWithUnknownFields(): void
    {
        $decoder = self::builder(new Extension(ExtensionRegistryTestSet::class, 100, Reflection\Int32T::T))->build();

        self::assertEquals(new ExtensionRegistryTestSet(), $decoder->decode('', ExtensionRegistryTestSet::class));

        $this->expectExceptionObject(new InvalidSchema(\sprintf(
            'Extension 100 of the MessageSet "%s" must be a message: a MessageSet has no other extensions.',
            ExtensionRegistryTestSet::class,
        )));

        $decoder->decode((string) hex2bin('0b10641a000c'), ExtensionRegistryTestSet::class);
    }

    public function testTheExtensionsOfTheBuilderGetTheValuesItsDecodersRead(): void
    {
        $handler = new ExtensionRegistryTestHandler();
        $builder = self::builder(self::item(), self::string())->withUnknownHandler($handler);
        $extensions = $builder->buildExtensions();
        // An item with an unknown field, field 20.
        $input = (string) hex2bin('5a050801a00107');

        $holder = $builder->build()->decode($input, ExtensionRegistryTestHolder::class);

        self::assertSame(2, $handler->handled);
        self::assertSame($handler->objects[0] ?? null, $extensions->get($holder, self::item()));
        self::assertNull($extensions->get($holder, self::string()));
        self::assertSame(2, $handler->handled);

        // A value set replaces the one read.
        $store = new ExtensionRegistryTestHandler();
        $builder = self::builder(self::item())->withUnknownHandler($store);
        $extensions = $builder->buildExtensions();
        $holder = $builder->build()->decode($input, ExtensionRegistryTestHolder::class);
        $extensions->set($holder, self::item(), new ExtensionRegistryTestItem(2));

        self::assertEquals(new ExtensionRegistryTestItem(2), $extensions->get($holder, self::item()));
    }

    public function testTheValuesAreKeptOnlyForTheExtensionsOfTheBuilderWithoutAProvider(): void
    {
        $input = (string) hex2bin('5a050801a00107');

        // None built before the decoding.
        $handler = new ExtensionRegistryTestHandler();
        $builder = self::builder(self::item())->withUnknownHandler($handler);
        $holder = $builder->build()->decode($input, ExtensionRegistryTestHolder::class);
        $builder->buildExtensions()->get($holder, self::item());

        self::assertSame(3, $handler->handled);

        // One with a provider.
        $handler = new ExtensionRegistryTestHandler();
        $builder = self::builder(self::item())->withUnknownHandler($handler);
        $extensions = $builder->buildExtensions($handler);
        $holder = $builder->build()->decode($input, ExtensionRegistryTestHolder::class);
        $extensions->get($holder, self::item());

        self::assertSame(3, $handler->handled);
    }

    public function testOnlyTheExtensionsTheObjectHasAreRead(): void
    {
        $handler = new ExtensionRegistryTestHandler();
        $builder = self::builder(self::string(), self::item(), self::strings(), self::node())->withUnknownHandler($handler);
        $extensions = $builder->buildExtensions();

        // An item and a node, each with an unknown field, and a string of the list; no string, between them.
        $holder = $builder->build()->decode((string) hex2bin('5a050801a001076201616a03a00107'), ExtensionRegistryTestHolder::class);

        self::assertSame(3, $handler->handled);
        $item = $extensions->get($holder, self::item());
        $node = $extensions->get($holder, self::node());
        self::assertSame($handler->objects[0] ?? null, $item);
        self::assertSame($handler->objects[1] ?? null, $node);
        self::assertSame(['a'], $extensions->get($holder, self::strings()));
        self::assertNull($extensions->get($holder, self::string()));
        self::assertSame($item, $extensions->get($holder, self::item()));
        self::assertSame(3, $handler->handled);

        // An absent repeated extension is an empty list.
        $holder = $builder->build()->decode((string) hex2bin('5a020801'), ExtensionRegistryTestHolder::class);
        self::assertSame([], $extensions->get($holder, self::strings()));
    }

    public function testOnlyTheItemsOfTheRegisteredExtensionsOfAMessageSetAreRead(): void
    {
        $other = new Extension(ExtensionRegistryTestSet::class, 101, new Reflection\ObjectT(ExtensionRegistryTestItem::class));
        $foreign = new Extension(ExtensionRegistryTestSet::class, 102, new Reflection\ObjectT(ExtensionRegistryTestItem::class));
        // {a: 1} of 100, a message of 102 with a string that is not valid UTF-8, an item without a type_id, a message
        // of 103 that ends inside its string, and {b: "x"} of 100.
        $hex = '0b10641a0208010c0b10661a031201ff0c0b1a0208010c0b10671a0212030c0b10641a031201780c';
        $builder = self::builder(self::setItem(), $other);

        $set = $builder->build()->decode((string) hex2bin($hex), ExtensionRegistryTestSet::class);

        self::assertSame($hex, bin2hex(Encoder\Builder::buildDefault()->encode($set)));
        self::assertEquals(new ExtensionRegistryTestItem(1, 'x'), $builder->buildExtensions()->get($set, self::setItem()));
        self::assertNull($builder->buildExtensions()->get($set, $other));

        // An item of an extension that is not registered fails only when it is read, as without the registration.
        $this->expectException(MalformedUtf8::class);

        $builder->buildExtensions()->get($set, $foreign);
    }

    public function testTheExtensionsOfAMessageSetAndOthersOfTheSameObjectAreRead(): void
    {
        $plain = new Extension(ExtensionRegistryTestExtendableSet::class, 10, Reflection\StringT::T);
        $builder = self::builder($plain, self::setItem());
        $extensions = $builder->buildExtensions();

        // Field 10, then an item of 100.
        $set = $builder->build()->decode((string) hex2bin('52036162630b10641a0208010c'), ExtensionRegistryTestSet::class);

        self::assertSame('abc', $extensions->get($set, $plain));
        self::assertEquals(new ExtensionRegistryTestItem(1), $extensions->get($set, self::setItem()));

        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $builder->build()->decode((string) hex2bin('5201ff'), ExtensionRegistryTestSet::class)));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $builder->build()->decode((string) hex2bin('0b10641a031201ff0c'), ExtensionRegistryTestSet::class)));
    }

    public function testABuilderReturnedByAWithMethodHasValuesOfItsOwn(): void
    {
        $builder = self::builder(self::string());
        $extensions = $builder->buildExtensions();

        $holder = $builder->withUtf8Validation(false)->build()->decode((string) hex2bin('5201ff'), ExtensionRegistryTestHolder::class);

        $this->expectException(MalformedUtf8::class);

        $extensions->get($holder, self::string());
    }

    /**
     * Decodes the input with the extensions registered, which must fail as get() of the extension fails for the object
     * that the path leads to, decoded without them.
     *
     * @param list<Extension<mixed>> $registered
     * @param Extension<mixed> $extension
     * @param ?\Closure(ExtensionRegistryTestHolder): ?object $path
     */
    private static function assertFailsAsGet(string $hex, array $registered, Extension $extension, ?\Closure $path = null): ProtobufException
    {
        $input = (string) hex2bin($hex);
        $holder = Decoder\Builder::buildDefault()->decode($input, ExtensionRegistryTestHolder::class);
        $object = $path === null ? $holder : $path($holder);
        self::assertNotNull($object);
        $expected = self::failure(static fn() => self::extensions()->get($object, $extension));

        $e = self::failure(static fn() => self::builder(...$registered)->build()->decode($input, ExtensionRegistryTestHolder::class));

        self::assertEquals($expected, $e);

        return $e;
    }

    /**
     * @param \Closure(): mixed $call
     */
    private static function failure(\Closure $call): ProtobufException
    {
        try {
            $call();
        } catch (ProtobufException $e) {
            return $e;
        }

        self::fail('The call does not fail.');
    }

    /**
     * @param Extension<mixed> ...$extensions
     */
    private static function builder(Extension ...$extensions): Decoder\Builder
    {
        return new Decoder\Builder()->withExtensions(...$extensions);
    }

    private static function extensions(): Extensions
    {
        return new Decoder\Builder()->buildExtensions();
    }

    /**
     * @return Extension<string>
     */
    private static function string(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(ExtensionRegistryTestHolder::class, 10, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<ExtensionRegistryTestItem>
     */
    private static function item(): Extension
    {
        /** @var Extension<ExtensionRegistryTestItem> $extension */
        static $extension = new Extension(ExtensionRegistryTestHolder::class, 11, new Reflection\ObjectT(ExtensionRegistryTestItem::class));

        return $extension;
    }

    /**
     * @return Extension<list<string>>
     */
    private static function strings(): Extension
    {
        /** @var Extension<list<string>> $extension */
        static $extension = new Extension(ExtensionRegistryTestHolder::class, 12, new Reflection\ListT(Reflection\StringT::T));

        return $extension;
    }

    /**
     * @return Extension<ExtensionRegistryTestNode>
     */
    private static function node(): Extension
    {
        /** @var Extension<ExtensionRegistryTestNode> $extension */
        static $extension = new Extension(ExtensionRegistryTestHolder::class, 13, new Reflection\ObjectT(ExtensionRegistryTestNode::class));

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    private static function itemNote(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(ExtensionRegistryTestItem::class, 20, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<ExtensionRegistryTestItem>
     */
    private static function setItem(): Extension
    {
        /** @var Extension<ExtensionRegistryTestItem> $extension */
        static $extension = new Extension(ExtensionRegistryTestSet::class, 100, new Reflection\ObjectT(ExtensionRegistryTestItem::class));

        return $extension;
    }
}

interface ExtensionRegistryTestExtendable {}

final readonly class ExtensionRegistryTestHolder implements ExtensionRegistryTestExtendable
{
    /**
     * @param list<ExtensionRegistryTestHolder> $children
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $child = null,
        #[Reflection\Field(3, new Reflection\ListT(new Reflection\ObjectT(self::class)))]
        public array $children = [],
        #[Reflection\OneOf([ExtensionRegistryTestNumber::class, ExtensionRegistryTestNested::class])]
        public ?ExtensionRegistryTestChoice $choice = null,
    ) {}
}

interface ExtensionRegistryTestChoice {}

final readonly class ExtensionRegistryTestNumber implements ExtensionRegistryTestChoice
{
    public function __construct(
        #[Reflection\Field(4, Reflection\Int32T::T)]
        public int $number = 0,
    ) {}
}

final readonly class ExtensionRegistryTestNested implements ExtensionRegistryTestChoice
{
    public function __construct(
        #[Reflection\Field(5, new Reflection\ObjectT(ExtensionRegistryTestHolder::class))]
        public ?ExtensionRegistryTestHolder $holder = null,
    ) {}
}

final readonly class ExtensionRegistryTestItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $a = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $b = null,
    ) {}
}

final readonly class ExtensionRegistryTestNode
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
    ) {}
}

interface ExtensionRegistryTestExtendableSet {}

#[Reflection\MessageSet]
final readonly class ExtensionRegistryTestSet implements ExtensionRegistryTestExtendableSet {}

/**
 * Keeps the unknown fields of the objects decoded and gives them back, as UnknownFields does, and counts the objects
 * it gets.
 */
final class ExtensionRegistryTestHandler implements UnknownFields\Handler, UnknownFields\Store
{
    public int $handled = 0;

    /** @var list<object> */
    public array $objects = [];

    /** @var \WeakMap<object, list<UnknownFields\UnknownField>> */
    public \WeakMap $fields;

    public function __construct()
    {
        $this->fields = new \WeakMap();
    }

    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        ++$this->handled;
        $this->objects[] = $message;
        $this->fields[$message] = $unknowns;
    }

    #[\Override]
    public function provide(object $message): array
    {
        return $this->fields[$message] ?? [];
    }

    #[\Override]
    public function replace(object $message, array $unknowns): void
    {
        $this->fields[$message] = $unknowns;
    }
}
