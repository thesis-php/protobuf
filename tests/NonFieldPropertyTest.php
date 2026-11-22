<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Exception\PropertyNotInitialized;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(ClassReflector::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(InvalidProperty::class)]
#[CoversClass(PropertyNotInitialized::class)]
final class NonFieldPropertyTest extends TestCase
{
    public function testPropertiesWithoutAttributeAreNotEncoded(): void
    {
        $message = new NonFieldPropertyTestMessage();
        $message->id = 5;

        self::assertSame('0805', bin2hex(Encoder\Builder::buildDefault()->encode($message)));
    }

    public function testPropertiesWithoutAttributeAreNotDecoded(): void
    {
        NonFieldPropertyTestMessage::$counter = 3;

        $decoded = Decoder\Builder::buildDefault()->decode("\x08\x05", NonFieldPropertyTestMessage::class);

        self::assertSame(5, $decoded->id);
        self::assertFalse(new \ReflectionProperty($decoded, 'uninitialized')->isInitialized($decoded));
        self::assertSame(3, NonFieldPropertyTestMessage::$counter);
    }

    public function testUninitializedFieldIsReported(): void
    {
        $this->expectExceptionObject(new PropertyNotInitialized(NonFieldPropertyTestMessage::class, 'id'));

        Encoder\Builder::buildDefault()->encode(new NonFieldPropertyTestMessage());
    }

    public function testTheMessageNamesTheProperty(): void
    {
        $previous = new \Error('uninitialized');
        $exception = new PropertyNotInitialized(NonFieldPropertyTestMessage::class, 'id', $previous);

        self::assertSame(
            \sprintf('The property "%s::$id" must be initialized before the object is encoded.', NonFieldPropertyTestMessage::class),
            $exception->getMessage(),
        );
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testUninitializedOneOfIsReported(): void
    {
        $message = new NonFieldPropertyTestOneOf();

        $this->expectExceptionObject(new PropertyNotInitialized(NonFieldPropertyTestOneOf::class, 'variant'));

        Encoder\Builder::buildDefault()->encode($message);
    }

    /**
     * @param class-string $class
     * @param non-empty-string $message
     */
    #[DataProvider('provideInvalidPropertyCases')]
    public function testFieldThatIsNotStoredInObjectIsRejectedOnEncode(object $object, string $class, string $message): void
    {
        $this->expectExceptionObject(new InvalidProperty($class, 'value', $message));

        Encoder\Builder::buildDefault()->encode($object);
    }

    /**
     * @param class-string $class
     * @param non-empty-string $message
     */
    #[DataProvider('provideInvalidPropertyCases')]
    public function testFieldThatIsNotStoredInObjectIsRejectedOnDecode(object $object, string $class, string $message): void
    {
        $this->expectExceptionObject(new InvalidProperty($class, 'value', $message));

        Decoder\Builder::buildDefault()->decode('', $object::class);
    }

    /**
     * @return iterable<string, array{object, class-string, non-empty-string}>
     */
    public static function provideInvalidPropertyCases(): iterable
    {
        yield 'static' => [
            new NonFieldPropertyTestStaticField(),
            NonFieldPropertyTestStaticField::class,
            'is static: only an instance property can be a protobuf field',
        ];

        yield 'virtual' => [
            new NonFieldPropertyTestVirtualField(),
            NonFieldPropertyTestVirtualField::class,
            'is virtual: only a property that stores its value can be a protobuf field',
        ];
    }
}

final class NonFieldPropertyTestMessage
{
    public static int $counter;

    #[Reflection\Field(1, Reflection\Int32T::T)]
    public int $id; // @phpstan-ignore property.uninitialized

    public int $uninitialized; // @phpstan-ignore property.uninitialized

    // @phpstan-ignore property.uninitialized
    public int $virtual {
        // @phpstan-ignore propertyGetHook.noRead
        get => throw new \LogicException('A property without attribute is read.');
        set => throw new \LogicException('A property without attribute is written.');
    }
}

final class NonFieldPropertyTestVariant
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
    ) {}
}

final class NonFieldPropertyTestOneOf
{
    #[Reflection\OneOf([NonFieldPropertyTestVariant::class])]
    public NonFieldPropertyTestVariant $variant; // @phpstan-ignore property.uninitialized
}

final class NonFieldPropertyTestStaticField
{
    #[Reflection\Field(1, Reflection\Int32T::T)]
    public static int $value = 0;
}

final class NonFieldPropertyTestVirtualField
{
    #[Reflection\Field(1, Reflection\Int32T::T)]
    public int $value {
        get => 1;
    }
}
