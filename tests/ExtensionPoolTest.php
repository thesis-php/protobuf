<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Internal\Extension\ExtensionChecker;
use Thesis\Protobuf\Internal\Extension\ExtensionCodec;
use Thesis\Protobuf\Internal\Extension\ExtensionValues;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\ExtensionPool;
use Thesis\Protobuf\Reflection\ExtensionRegistry;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(ExtensionPool::class)]
#[CoversClass(Decoder\Builder::class)]
#[CoversClass(ReflectionDecoder::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(ExtensionChecker::class)]
#[CoversClass(ExtensionCodec::class)]
#[CoversClass(ExtensionRegistry::class)]
#[CoversClass(ExtensionValues::class)]
final class ExtensionPoolTest extends TestCase
{
    /** A string that is not valid UTF-8 in field 10. */
    private const string MALFORMED_STRING = '5201ff';

    /** The same in the child, field 2. */
    private const string MALFORMED_CHILD_STRING = '12035201ff';

    /** An item of 100 of a MessageSet whose message has a string that is not valid UTF-8 in field 2. */
    private const string MALFORMED_SET_ITEM = '0b10641a031201ff0c';

    /** The global pool before the test, which the test replaces with an empty one of its own. */
    private ?ExtensionPool $global = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->global = ExtensionPool::global();
        self::setGlobal(new ExtensionPool());
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::setGlobal($this->global);
    }

    public function testTheGlobalPoolIsOne(): void
    {
        self::setGlobal(null);

        self::assertSame(ExtensionPool::global(), ExtensionPool::global());
    }

    public function testTheDecodersReadTheGlobalPoolByDefault(): void
    {
        $built = new Decoder\Builder()->build();
        ExtensionPool::global()->register(
            ExtensionPoolTestHolder::class,
            static fn(int $number): ?Extension => match ($number) {
                10 => ExtensionPoolTestExtensions::string(),
                default => null,
            },
        );

        foreach ([$built, new Decoder\Builder()->build(), Decoder\Builder::buildDefault()] as $decoder) {
            self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class)));
        }
    }

    public function testNullReadsNoPool(): void
    {
        $calls = self::calls();
        ExtensionPool::global()->register(ExtensionPoolTestHolder::class, self::counted($calls, ExtensionPoolTestExtensions::string()));
        $builder = new Decoder\Builder()->withExtensionPool(null);

        self::assertEquals(new ExtensionPoolTestHolder(), $builder->build()->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class));
        $holder = $builder->build()->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        self::assertSame([], $calls->getArrayCopy());
        // The extensions of the builder read without a registry.
        self::assertSame('abc', $builder->buildExtensions()->get($holder, ExtensionPoolTestExtensions::string()));

        // The extensions given are still read.
        $e = self::failure(static fn() => $builder->withExtensions(ExtensionPoolTestExtensions::item())->build()->decode(self::bytes('5a031201ff'), ExtensionPoolTestHolder::class));
        self::assertInstanceOf(MalformedUtf8::class, $e);
    }

    public function testAPoolOfItsOwnIsReadInsteadOfTheGlobalOne(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string()));
        ExtensionPool::global()->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::item()));
        $decoder = self::decoder($pool);

        self::assertEquals(new ExtensionPoolTestHolder(), $decoder->decode(self::bytes('5a031201ff'), ExtensionPoolTestHolder::class));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class)));

        // The builder is not changed.
        $builder = new Decoder\Builder();
        $builder->withExtensionPool($pool);
        self::assertEquals(new ExtensionPoolTestHolder(), $builder->build()->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class));
    }

    public function testAnExtensionOfThePoolIsCheckedAsOneGiven(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string(), ExtensionPoolTestExtensions::item()));
        $input = self::bytes('0801' . self::MALFORMED_CHILD_STRING);

        $e = self::failure(static fn() => self::decoder($pool)->decode($input, ExtensionPoolTestHolder::class));
        $given = self::failure(static fn() => new Decoder\Builder()->withExtensionPool(null)->withExtensions(ExtensionPoolTestExtensions::string())->build()->decode($input, ExtensionPoolTestHolder::class));

        self::assertEquals($given, $e);
        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([10], $e->fields);

        $holder = self::decoder($pool)->decode(self::bytes('52036162635a03120178'), ExtensionPoolTestHolder::class);
        self::assertSame('52036162635a03120178', bin2hex(Encoder\Builder::buildDefault()->encode($holder)));
        self::assertEquals(new ExtensionPoolTestItem(b: 'x'), new Decoder\Builder()->buildExtensions()->get($holder, ExtensionPoolTestExtensions::item()));
    }

    public function testRegisteringCallsNothingAndLoadsNoClass(): void
    {
        /** @var \ArrayObject<int, string> $loaded */
        $loaded = new \ArrayObject();
        $autoload = static function (string $class) use ($loaded): void {
            $loaded->append($class);
        };
        spl_autoload_register($autoload, prepend: true);

        try {
            $pool = new ExtensionPool();
            $pool->register(ExtensionPoolTestHolder::class, static fn(int $number): never => self::fail('The resolver is called.'));
            $pool->register(
                // @phpstan-ignore argument.type
                'Thesis\Protobuf\ExtensionPoolTestAbsent',
                // @phpstan-ignore return.type
                static fn(int $number): ?Extension => match ($number) {
                    // @phpstan-ignore class.notFound
                    10 => ExtensionPoolTestAbsentExtensions::string(),
                    default => null,
                },
            );

            self::assertSame([], $loaded->getArrayCopy());

            // An object without unknown fields, and one of a class of no resolver.
            $decoder = self::decoder($pool);
            self::assertEquals(new ExtensionPoolTestHolder(1), $decoder->decode(self::bytes('0801'), ExtensionPoolTestHolder::class));
            self::assertEquals(new ExtensionPoolTestItem(), $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestItem::class));

            self::assertSame([], $loaded->getArrayCopy());
        } finally {
            spl_autoload_unregister($autoload);
        }
    }

    public function testTheClassThatAResolverReturnsAnExtensionOfIsLoadedWhenTheNumberIsFirstDecoded(): void
    {
        self::assertFalse(class_exists(ExtensionPoolLazyExtensions::class, false));

        $pool = new ExtensionPool();
        $pool->register(
            ExtensionPoolTestHolder::class,
            static fn(int $number): ?Extension => match ($number) {
                10 => ExtensionPoolLazyExtensions::string(),
                default => null,
            },
        );
        $decoder = self::decoder($pool);
        $decoder->decode(self::bytes('08015801'), ExtensionPoolTestHolder::class);

        self::assertFalse(class_exists(ExtensionPoolLazyExtensions::class, false));

        $e = self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class));

        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertTrue(class_exists(ExtensionPoolLazyExtensions::class, false));
    }

    public function testTheNameOfTheClassExtendedIsComparedWhateverItsCase(): void
    {
        $pool = new ExtensionPool();
        $pool->register(strtolower(ExtensionPoolTestHolder::class), self::resolverOf(ExtensionPoolTestExtensions::string()));
        $pool->register(strtoupper(ExtensionPoolTestExtendable::class), self::resolverOf(new Extension(ExtensionPoolTestExtendable::class, 11, new Reflection\ObjectT(ExtensionPoolTestItem::class))));
        $decoder = self::decoder($pool);

        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class)));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes('5a031201ff'), ExtensionPoolTestHolder::class)));

        // Two names of the same class are one class.
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(new Extension(strtoupper(ExtensionPoolTestHolder::class), 10, Reflection\StringT::T)));

        self::assertEquals(
            new InvalidSchema(\sprintf('Two extensions of "%s" have the number 10: a field number is declared once.', ExtensionPoolTestHolder::class)),
            self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)),
        );
    }

    public function testAResolverIsAskedOnceForAnExtensionFoundAndEachTimeForANumberOfNoExtension(): void
    {
        $pool = new ExtensionPool();
        $calls = self::calls();
        $pool->register(ExtensionPoolTestHolder::class, self::counted($calls, ExtensionPoolTestExtensions::string()));
        $decoder = self::decoder($pool);

        // Without unknown fields, then with a number of no extension, which is not kept: once for each object.
        $decoder->decode(self::bytes('0801'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('5801'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('58015802'), ExtensionPoolTestHolder::class);

        self::assertSame([11, 11], $calls->getArrayCopy());

        // The extension found is kept, for the child of the class too.
        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('52036162635801'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('120552036162635203616263'), ExtensionPoolTestHolder::class);

        self::assertSame([11, 11, 10, 11], $calls->getArrayCopy());

        // Numbers of no extension, each met again, are asked for again: none of them is kept.
        $many = implode('', array_map(static fn(int $num): string => bin2hex(\chr($num << 3 | 0)) . '01', range(3, 15)));
        $decoder->decode(self::bytes($many), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes($many), ExtensionPoolTestHolder::class);

        $asked = [...range(3, 9), ...range(11, 15)];
        self::assertSame([11, 11, 10, 11, ...$asked, ...$asked], $calls->getArrayCopy());
    }

    public function testAnExtensionFoundIsKeptForEachClassThatExtendsItsClass(): void
    {
        $pool = new ExtensionPool();
        $calls = self::calls();
        $pool->register(ExtensionPoolTestParent::class, self::counted($calls, ExtensionPoolTestExtensions::parentString()));
        $decoder = self::decoder($pool);

        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestOther::class);
        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestOther::class);

        self::assertSame([10, 10], $calls->getArrayCopy());
    }

    public function testTheUnknownFieldsOfAClassThatIsNotAMessageSetAreNotReadAsItems(): void
    {
        $pool = new ExtensionPool();
        $calls = self::calls();
        $pool->register(ExtensionPoolTestOther::class, self::counted($calls));

        // A group of field 1 with field 2, 77, as an item of a MessageSet is.
        self::decoder($pool)->decode(self::bytes('0b104d0c'), ExtensionPoolTestOther::class);

        self::assertSame([1], $calls->getArrayCopy());
    }

    public function testARegistrationForgetsTheExtensionsFound(): void
    {
        $pool = new ExtensionPool();
        $calls = self::calls();
        $pool->register(ExtensionPoolTestHolder::class, self::counted($calls, ExtensionPoolTestExtensions::string()));
        $decoder = self::decoder($pool);

        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        self::assertSame([10], $calls->getArrayCopy());

        // A resolver of another class: the extensions of the class may be others now.
        $pool->register(ExtensionPoolTestItem::class, self::resolverOf());

        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);
        self::assertSame([10, 10], $calls->getArrayCopy());
    }

    public function testEachResolverOfAClassIsAsked(): void
    {
        $pool = new ExtensionPool();
        $string = self::calls();
        $item = self::calls();
        $pool->register(ExtensionPoolTestHolder::class, self::counted($string, ExtensionPoolTestExtensions::string()));
        $pool->register(ExtensionPoolTestHolder::class, self::counted($item, ExtensionPoolTestExtensions::item()));
        $decoder = self::decoder($pool);

        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class)));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes('5a031201ff'), ExtensionPoolTestHolder::class)));

        $holder = $decoder->decode(self::bytes('52036162635a03120178'), ExtensionPoolTestHolder::class);
        self::assertSame('abc', new Decoder\Builder()->buildExtensions()->get($holder, ExtensionPoolTestExtensions::string()));
        self::assertSame([10, 11], $string->getArrayCopy());
        self::assertSame([10, 11], $item->getArrayCopy());
    }

    public function testAnExtensionOfAParentClassOrAnInterfaceIsChecked(): void
    {
        foreach ([ExtensionPoolTestParent::class, ExtensionPoolTestExtendable::class] as $extendee) {
            $pool = new ExtensionPool();
            $pool->register($extendee, self::resolverOf(new Extension($extendee, 10, Reflection\StringT::T)));

            $e = self::failure(static fn() => self::decoder($pool)->decode(self::bytes(self::MALFORMED_CHILD_STRING), ExtensionPoolTestHolder::class));

            self::assertInstanceOf(MalformedUtf8::class, $e);
            self::assertSame([10], $e->fields);
        }
    }

    public function testAnExtensionOfAMessageSetIsAnItem(): void
    {
        $pool = new ExtensionPool();
        $calls = self::calls();
        $pool->register(ExtensionPoolTestSet::class, self::counted($calls, ExtensionPoolTestExtensions::setItem()));
        $decoder = self::decoder($pool);

        // An item of 102 whose message is malformed, and an item of 100: the resolver is asked for their numbers.
        $set = $decoder->decode(self::bytes('0b10661a031201ff0c0b10641a031201780c'), ExtensionPoolTestSet::class);
        self::assertEquals(new ExtensionPoolTestItem(b: 'x'), new Decoder\Builder()->buildExtensions()->get($set, ExtensionPoolTestExtensions::setItem()));
        self::assertSame([102, 100], $calls->getArrayCopy());

        $e = self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_SET_ITEM), ExtensionPoolTestSet::class));
        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([1, 3, 2], $e->fields);

        // The extension written as a field of its number.
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes('a2060312 01ff'), ExtensionPoolTestSet::class)));
        self::assertSame([102, 100], $calls->getArrayCopy());
    }

    public function testTheUnknownFieldsOfAMessageSetWithAnExtensionOfAnInterfaceItImplementsAreReadAsFieldsAndAsItems(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestSet::class, self::resolverOf(ExtensionPoolTestExtensions::setItem()));
        // The fields of an interface it implements are read as fields: the item, field 1, is one of no extension.
        $calls = self::calls();
        $pool->register(ExtensionPoolTestExtendableSet::class, self::counted($calls));
        $decoder = self::decoder($pool);

        $e = self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_SET_ITEM), ExtensionPoolTestSet::class));
        self::assertInstanceOf(MalformedUtf8::class, $e);
        self::assertSame([1, 3, 2], $e->fields);
        self::assertSame([1, 100], $calls->getArrayCopy());

        // Field 100 written as a message is read with the item, as one; field 1, of no extension, is asked for again.
        $set = $decoder->decode(self::bytes('a206031201780b10641a0312017a0c'), ExtensionPoolTestSet::class);
        self::assertEquals(new ExtensionPoolTestItem(b: 'z'), new Decoder\Builder()->buildExtensions()->get($set, ExtensionPoolTestExtensions::setItem()));
        self::assertSame([1, 100, 1], $calls->getArrayCopy());
    }

    public function testAnExtensionOfAMessageSetThatIsNotAMessageFailsTheDecodingOfAnObjectWithItsNumber(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestSet::class, self::resolverOf(new Extension(ExtensionPoolTestSet::class, 100, Reflection\Int32T::T)));
        $decoder = self::decoder($pool);

        self::assertEquals(new ExtensionPoolTestSet(), $decoder->decode(self::bytes('0b10651a000c'), ExtensionPoolTestSet::class));

        $expected = new InvalidSchema(\sprintf(
            'Extension 100 of the MessageSet "%s" must be a message: a MessageSet has no other extensions.',
            ExtensionPoolTestSet::class,
        ));
        self::assertEquals($expected, self::failure(static fn() => $decoder->decode(self::bytes('0b10641a000c'), ExtensionPoolTestSet::class)));
        // It fails again.
        self::assertEquals($expected, self::failure(static fn() => $decoder->decode(self::bytes('0b10641a000c'), ExtensionPoolTestSet::class)));
    }

    public function testADecoderBuiltBeforeARegistrationReadsIt(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::item()));
        $decoder = self::decoder($pool);

        self::assertEquals(new ExtensionPoolTestHolder(), $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class));

        $pool->register(ExtensionPoolTestParent::class, self::resolverOf(ExtensionPoolTestExtensions::parentString()));

        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class)));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes('5a031201ff'), ExtensionPoolTestHolder::class)));
    }

    public function testTwoResolversThatReturnDifferentInstancesFailTheDecodingOfTheNumber(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string(), ExtensionPoolTestExtensions::item()));
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(new Extension(ExtensionPoolTestHolder::class, 10, Reflection\StringT::T)));
        $decoder = self::decoder($pool);

        // Other numbers and other classes are read.
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $decoder->decode(self::bytes('5a031201ff'), ExtensionPoolTestHolder::class)));
        self::assertEquals(new ExtensionPoolTestItem(), $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestItem::class));

        $expected = new InvalidSchema(\sprintf('Two extensions of "%s" have the number 10: a field number is declared once.', ExtensionPoolTestHolder::class));
        self::assertEquals($expected, self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)));
        // It fails again.
        self::assertEquals($expected, self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)));
    }

    public function testAnExtensionOfThePoolAndADifferentOneGivenFailTheDecodingOfTheNumber(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(new Extension(ExtensionPoolTestHolder::class, 10, Reflection\StringT::T)));
        $decoder = new Decoder\Builder()->withExtensionPool($pool)->withExtensions(ExtensionPoolTestExtensions::string())->build();

        self::assertEquals(new ExtensionPoolTestHolder(1), $decoder->decode(self::bytes('08015801'), ExtensionPoolTestHolder::class));
        self::assertEquals(
            new InvalidSchema(\sprintf('Two extensions of "%s" have the number 10: a field number is declared once.', ExtensionPoolTestHolder::class)),
            self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)),
        );
    }

    public function testExtensionsOfAClassAndOfItsParentWithTheSameNumberFailTheDecodingOfTheNumber(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string()));
        $pool->register(ExtensionPoolTestParent::class, self::resolverOf(ExtensionPoolTestExtensions::parentString()));
        $decoder = self::decoder($pool);

        // The parent alone has the extension.
        self::assertEquals(new ExtensionPoolTestOther(), $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestOther::class));

        self::assertEquals(
            new InvalidSchema(\sprintf(
                'Two extensions of "%s" have the number 10: one extends "%s", the other "%s".',
                ExtensionPoolTestHolder::class,
                ExtensionPoolTestHolder::class,
                ExtensionPoolTestParent::class,
            )),
            self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)),
        );
    }

    public function testTheResolversOfAClassAreAskedInTheOrderOfTheirRegistrations(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestExtendable::class, self::resolverOf(new Extension(ExtensionPoolTestExtendable::class, 10, Reflection\StringT::T)));
        $pool->register(ExtensionPoolTestParent::class, self::resolverOf(ExtensionPoolTestExtensions::parentString()));
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string()));

        self::assertEquals(
            new InvalidSchema(\sprintf(
                'Two extensions of "%s" have the number 10: one extends "%s", the other "%s".',
                ExtensionPoolTestHolder::class,
                ExtensionPoolTestExtendable::class,
                ExtensionPoolTestParent::class,
            )),
            self::failure(static fn() => self::decoder($pool)->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)),
        );
    }

    public function testWithoutAnyExtensionTheClassesAreNotGoneThrough(): void
    {
        $pool = new ExtensionPool();
        $checker = new ExtensionRegistry([], pool: $pool)->checker(Reflector::build());

        self::assertFalse($checker->reaches(ExtensionPoolTestBroken::class));

        $pool->register(ExtensionPoolTestItem::class, self::resolverOf(ExtensionPoolTestExtensions::string()));

        $this->expectException(\ReflectionException::class);

        $checker->reaches(ExtensionPoolTestBroken::class);
    }

    public function testAnExtensionOfAMessageSetAndAnotherOfAnInterfaceItImplementsWithTheSameNumberFailTheDecodingOfTheNumber(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestExtendableSet::class, self::resolverOf(new Extension(ExtensionPoolTestExtendableSet::class, 100, Reflection\StringT::T)));
        $pool->register(ExtensionPoolTestSet::class, self::resolverOf(ExtensionPoolTestExtensions::setItem()));

        self::assertEquals(
            new InvalidSchema(\sprintf(
                'Two extensions of "%s" have the number 100: one extends "%s", the other "%s".',
                ExtensionPoolTestSet::class,
                ExtensionPoolTestExtendableSet::class,
                ExtensionPoolTestSet::class,
            )),
            self::failure(static fn() => self::decoder($pool)->decode(self::bytes('0b10641a000c'), ExtensionPoolTestSet::class)),
        );
    }

    public function testTheSameInstanceFromSeveralSourcesIsReadOnce(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string()));
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string()));
        $builder = new Decoder\Builder()->withExtensionPool($pool)->withExtensions(ExtensionPoolTestExtensions::string());

        $holder = $builder->build()->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class);

        self::assertSame('abc', $builder->buildExtensions()->get($holder, ExtensionPoolTestExtensions::string()));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $builder->build()->decode(self::bytes(self::MALFORMED_STRING), ExtensionPoolTestHolder::class)));
    }

    public function testAResolverThatReturnsNeitherAnExtensionNorNullFailsTheDecodingOfTheNumber(): void
    {
        $pool = new ExtensionPool();
        // @phpstan-ignore argument.type
        $pool->register(ExtensionPoolTestHolder::class, static fn(int $number): ?string => $number === 10 ? 'string' : null);
        $decoder = self::decoder($pool);

        self::assertEquals(new ExtensionPoolTestHolder(), $decoder->decode(self::bytes('5801'), ExtensionPoolTestHolder::class));

        $expected = new InvalidSchema(\sprintf(
            'The resolver of the extensions of "%s" registered in the pool returns string for the number 10: it must return an extension or null.',
            ExtensionPoolTestHolder::class,
        ));
        self::assertEquals($expected, self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)));
        // It fails again.
        self::assertEquals($expected, self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)));
    }

    public function testAResolverThatReturnsAnExtensionOfAnotherNumberOrClassFailsTheDecodingOfTheNumber(): void
    {
        $pool = new ExtensionPool();
        $pool->register(
            ExtensionPoolTestHolder::class,
            static fn(int $number): ?Extension => match ($number) {
                12 => ExtensionPoolTestExtensions::string(),
                10 => ExtensionPoolTestExtensions::parentString(),
                default => null,
            },
        );
        $decoder = self::decoder($pool);

        self::assertEquals(
            new InvalidSchema(\sprintf('The resolver of the extensions of "%s" registered in the pool returns extension 10 of "%1$s" for the number 12.', ExtensionPoolTestHolder::class)),
            self::failure(static fn() => $decoder->decode(self::bytes('6203616263'), ExtensionPoolTestHolder::class)),
        );
        self::assertEquals(
            new InvalidSchema(\sprintf(
                'The resolver of the extensions of "%s" registered in the pool returns extension 10 of "%s" for the number 10.',
                ExtensionPoolTestHolder::class,
                ExtensionPoolTestParent::class,
            )),
            self::failure(static fn() => $decoder->decode(self::bytes('5203616263'), ExtensionPoolTestHolder::class)),
        );
    }

    public function testTheExtensionsOfTheBuilderGetTheValuesItsDecodersReadFromThePool(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::item()));
        $handler = new ExtensionPoolTestHandler();
        $builder = new Decoder\Builder()->withExtensionPool($pool)->withUnknownHandler($handler);
        $extensions = $builder->buildExtensions();

        // An item with an unknown field, field 20.
        $holder = $builder->build()->decode(self::bytes('5a050801a00107'), ExtensionPoolTestHolder::class);

        self::assertCount(2, $handler->objects);
        // The item the decoder read, not decoded again.
        self::assertSame($handler->objects[0], $extensions->get($holder, ExtensionPoolTestExtensions::item()));
    }

    public function testARegistryGivenToAReflectorReadsThePool(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestHolder::class, self::resolverOf(ExtensionPoolTestExtensions::string()));
        $map = static fn(string $hex, ExtensionRegistry $registry): object => Reflector::build(extensions: $registry)->map(
            new Serializer()->deserialize(Reflector::build()->type(ExtensionPoolTestHolder::class), self::bytes($hex)),
            ExtensionPoolTestHolder::class,
        );

        self::assertEquals(new ExtensionPoolTestHolder(), $map(self::MALFORMED_STRING, new ExtensionRegistry([])));
        self::assertEquals(new ExtensionPoolTestHolder(), $map(self::MALFORMED_STRING, new ExtensionRegistry([], validateUtf8: false, pool: $pool)));
        self::assertInstanceOf(MalformedUtf8::class, self::failure(static fn() => $map(self::MALFORMED_STRING, new ExtensionRegistry([], pool: $pool))));
        self::assertSame([], new ExtensionRegistry([], pool: $pool)->extensions);
    }

    public function testAnExtensionOfAClassThatAnObjectMayHoldIsChecked(): void
    {
        $inputs = [
            ExtensionPoolTestListed::class => '0a035201ff',
            ExtensionPoolTestMapped::class => '1207080112035201ff',
            ExtensionPoolTestGrouped::class => '1b5201ff1c',
            ExtensionPoolTestVariantHeld::class => '22035201ff',
        ];

        foreach ($inputs as $class => $hex) {
            $pool = new ExtensionPool();
            $decoder = self::decoder($pool);
            // Decoded once without the extension, which the decoder then reads once registered.
            $decoder->decode(self::bytes($hex), ExtensionPoolTestOuter::class);
            $pool->register($class, self::resolverOf(new Extension($class, 10, Reflection\StringT::T)));

            $e = self::failure(static fn() => $decoder->decode(self::bytes($hex), ExtensionPoolTestOuter::class));
            self::assertInstanceOf(MalformedUtf8::class, $e, $class);
            self::assertSame([10], $e->fields, $class);
        }
    }

    public function testAnObjectReachesTheExtensionsOfTheClassesItMayHold(): void
    {
        $pool = new ExtensionPool();
        $checker = new ExtensionRegistry([], pool: $pool)->checker(Reflector::build());

        self::assertFalse($checker->reaches(ExtensionPoolTestOuter::class));

        $pool->register(ExtensionPoolTestVariantHeld::class, self::resolverOf(ExtensionPoolTestExtensions::string()));

        self::assertTrue($checker->reaches(ExtensionPoolTestOuter::class));
        self::assertTrue($checker->reaches(ExtensionPoolTestVariant::class));
        self::assertFalse($checker->reaches(ExtensionPoolTestListed::class));

        $given = new ExtensionRegistry([ExtensionPoolTestExtensions::string()])->checker(Reflector::build());
        self::assertFalse($given->reaches(ExtensionPoolTestOuter::class));
        self::assertTrue($given->reaches(ExtensionPoolTestHolder::class));
    }

    public function testTheClassesAnObjectMayHoldAreThoseOfItsFieldsAndOfItsOneofs(): void
    {
        self::assertSame(
            [
                ExtensionPoolTestOuter::class,
                ExtensionPoolTestListed::class,
                ExtensionPoolTestMapped::class,
                ExtensionPoolTestGrouped::class,
                ExtensionPoolTestVariant::class,
                ExtensionPoolTestVariantHeld::class,
            ],
            Reflector::build()->classesHeld(ExtensionPoolTestOuter::class),
        );
        self::assertSame([ExtensionPoolTestHolder::class], Reflector::build()->classesHeld(ExtensionPoolTestHolder::class));
    }

    public function testADecodingInsideAnotherDoesNotChangeHowDeepItsObjectsAre(): void
    {
        $pool = new ExtensionPool();
        $pool->register(ExtensionPoolTestTree::class, self::resolverOf(new Extension(ExtensionPoolTestTree::class, 10, Reflection\StringT::T)));

        // The tree the handler decodes, and one whose decoding fails.
        foreach (['', self::MALFORMED_STRING] as $inner) {
            $handler = new ExtensionPoolTestReentrantHandler(self::bytes($inner));
            $decoder = new Decoder\Builder()->withExtensionPool($pool)->withUnknownHandler($handler)->build();
            $handler->decoder = $decoder;

            // A child with a child with an unknown field, for which the handler decodes a tree, then a child with
            // a child whose extension is a string that is not valid UTF-8.
            $e = self::failure(static fn() => $decoder->decode(self::bytes('0a050a03a00107 0a050a035201ff'), ExtensionPoolTestTree::class));

            self::assertSame(1, $handler->decoded);
            self::assertInstanceOf(MalformedUtf8::class, $e);
            self::assertSame([10], $e->fields);
        }
    }

    private static function setGlobal(?ExtensionPool $pool): void
    {
        new \ReflectionProperty(ExtensionPool::class, 'global')->setValue(null, $pool);
    }

    private static function decoder(ExtensionPool $pool): Decoder
    {
        return new Decoder\Builder()->withExtensionPool($pool)->build();
    }

    /**
     * A resolver of the extensions, which are created already.
     *
     * @param Extension<mixed> ...$extensions
     * @return \Closure(int): ?Extension<mixed>
     */
    private static function resolverOf(Extension ...$extensions): \Closure
    {
        return static function (int $number) use ($extensions): ?Extension {
            foreach ($extensions as $extension) {
                if ($extension->num === $number) {
                    return $extension;
                }
            }

            return null;
        };
    }

    /**
     * The numbers a resolver is asked for.
     *
     * @return \ArrayObject<int, int>
     */
    private static function calls(): \ArrayObject
    {
        /** @var \ArrayObject<int, int> */
        return new \ArrayObject();
    }

    /**
     * A resolver of the extensions that appends the number it is asked for to the calls.
     *
     * @param \ArrayObject<int, int> $calls
     * @param Extension<mixed> ...$extensions
     * @return \Closure(int): ?Extension<mixed>
     */
    private static function counted(\ArrayObject $calls, Extension ...$extensions): \Closure
    {
        $resolver = self::resolverOf(...$extensions);

        return static function (int $number) use ($calls, $resolver): ?Extension {
            $calls->append($number);

            return $resolver($number);
        };
    }

    private static function bytes(string $hex): string
    {
        return (string) hex2bin(str_replace(' ', '', $hex));
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
}

/**
 * Returns each extension as generated code does.
 */
final class ExtensionPoolTestExtensions
{
    /**
     * @return Extension<string>
     */
    public static function string(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(ExtensionPoolTestHolder::class, 10, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function parentString(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(ExtensionPoolTestParent::class, 10, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<ExtensionPoolTestItem>
     */
    public static function item(): Extension
    {
        /** @var Extension<ExtensionPoolTestItem> $extension */
        static $extension = new Extension(ExtensionPoolTestHolder::class, 11, new Reflection\ObjectT(ExtensionPoolTestItem::class));

        return $extension;
    }

    /**
     * @return Extension<ExtensionPoolTestItem>
     */
    public static function setItem(): Extension
    {
        /** @var Extension<ExtensionPoolTestItem> $extension */
        static $extension = new Extension(ExtensionPoolTestSet::class, 100, new Reflection\ObjectT(ExtensionPoolTestItem::class));

        return $extension;
    }
}

interface ExtensionPoolTestExtendable {}

abstract readonly class ExtensionPoolTestParent implements ExtensionPoolTestExtendable {}

final readonly class ExtensionPoolTestHolder extends ExtensionPoolTestParent
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $child = null,
    ) {}
}

final readonly class ExtensionPoolTestOther extends ExtensionPoolTestParent {}

final readonly class ExtensionPoolTestItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $a = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $b = null,
    ) {}
}

interface ExtensionPoolTestExtendableSet {}

#[Reflection\MessageSet]
final readonly class ExtensionPoolTestSet implements ExtensionPoolTestExtendableSet {}

final readonly class ExtensionPoolTestOuter
{
    /**
     * @param list<ExtensionPoolTestListed> $list
     * @param array<int, ExtensionPoolTestMapped> $map
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(ExtensionPoolTestListed::class)))]
        public array $list = [],
        #[Reflection\Field(2, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(ExtensionPoolTestMapped::class)))]
        public array $map = [],
        #[Reflection\Field(3, new Reflection\GroupT(ExtensionPoolTestGrouped::class))]
        public ?ExtensionPoolTestGrouped $group = null,
        #[Reflection\OneOf([ExtensionPoolTestVariant::class])]
        public ?ExtensionPoolTestVariant $choice = null,
    ) {}
}

final readonly class ExtensionPoolTestListed {}

final readonly class ExtensionPoolTestMapped {}

final readonly class ExtensionPoolTestGrouped {}

final readonly class ExtensionPoolTestVariant
{
    public function __construct(
        #[Reflection\Field(4, new Reflection\ObjectT(ExtensionPoolTestVariantHeld::class))]
        public ?ExtensionPoolTestVariantHeld $held = null,
    ) {}
}

final readonly class ExtensionPoolTestVariantHeld {}

final readonly class ExtensionPoolTestBroken
{
    public function __construct(
        // @phpstan-ignore argument.type
        #[Reflection\Field(1, new Reflection\ObjectT('Thesis\Protobuf\ExtensionPoolTestMissing'))]
        public ?object $missing = null,
    ) {}
}

final readonly class ExtensionPoolTestTree
{
    /**
     * @param list<ExtensionPoolTestTree> $children
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\ObjectT(self::class)))]
        public array $children = [],
    ) {}
}

/**
 * Decodes a tree with the decoder for the first object it gets, and lets the decoding fail.
 */
final class ExtensionPoolTestReentrantHandler implements UnknownFields\Handler
{
    public ?Decoder $decoder = null;

    public int $decoded = 0;

    public function __construct(
        private readonly string $input,
    ) {}

    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        if ($this->decoded === 0 && $this->decoder !== null) {
            ++$this->decoded;

            try {
                $this->decoder->decode($this->input, ExtensionPoolTestTree::class);
            } catch (ProtobufException) {
            }
        }
    }
}

/**
 * Keeps the unknown fields of the objects decoded and gives them back, as UnknownFields does, and keeps the objects.
 */
final class ExtensionPoolTestHandler implements UnknownFields\Handler, UnknownFields\Store
{
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
