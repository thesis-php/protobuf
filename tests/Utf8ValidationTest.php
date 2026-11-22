<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
use Thesis\Protobuf\Encoder\Internal\ReflectionEncoder;
use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\Reader;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\SerdeBytes;
use Thesis\Protobuf\Internal\Serde\SerdeString;
use Thesis\Protobuf\Type\MessageT;

#[CoversClass(Serializer::class)]
#[CoversClass(SerdeString::class)]
#[CoversClass(SerdeBytes::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(MapCodec::class)]
#[CoversClass(Reader::class)]
#[CoversClass(ByteBuffer::class)]
#[CoversClass(InvalidUtf8::class)]
#[CoversClass(MalformedUtf8::class)]
#[CoversClass(Encoder\Builder::class)]
#[CoversClass(Decoder\Builder::class)]
#[CoversClass(ReflectionEncoder::class)]
#[CoversClass(ReflectionDecoder::class)]
final class Utf8ValidationTest extends TestCase
{
    /**
     * @param list<positive-int> $fields
     */
    #[DataProvider('provideInvalidStringsCases')]
    public function testAnInvalidStringIsRejectedOnDecode(MessageT $type, Message $message, array $fields): void
    {
        $bytes = new Serializer(validateUtf8: false)->serialize($message);

        try {
            new Serializer()->deserialize($type, $bytes);
            self::fail('Deserialization must fail.');
        } catch (MalformedMessage $e) {
            self::assertInstanceOf(MalformedUtf8::class, $e);
            self::assertSame($fields, $e->fields);
            self::assertSame([], $e->properties);
            self::assertSame(
                \sprintf('The string in field %s is not valid UTF-8, as a protobuf string must be.', implode('.', $fields)),
                $e->getMessage(),
            );
        }
    }

    /**
     * @param list<positive-int> $fields
     */
    #[DataProvider('provideInvalidStringsCases')]
    public function testAnInvalidStringIsRejectedOnEncode(MessageT $type, Message $message, array $fields): void
    {
        try {
            new Serializer()->serialize($message);
            self::fail('Serialization must fail.');
        } catch (InvalidUtf8 $e) {
            self::assertSame($fields, $e->fields);
            self::assertSame([], $e->properties);
            self::assertSame(
                \sprintf(
                    'The string in field %s is not valid UTF-8, as a protobuf string must be: use bytes for binary data.',
                    implode('.', $fields),
                ),
                $e->getMessage(),
            );
        }
    }

    /**
     * @param list<positive-int> $fields
     */
    #[DataProvider('provideInvalidStringsCases')]
    public function testAnInvalidStringIsAcceptedWithTheCheckOff(MessageT $type, Message $message, array $fields): void
    {
        $serializer = new Serializer(validateUtf8: false);
        $bytes = $serializer->serialize($message);

        self::assertSame($bytes, $serializer->serialize($serializer->deserialize($type, $bytes)));
    }

    /**
     * @return iterable<string, array{MessageT, Message, list<positive-int>}>
     */
    public static function provideInvalidStringsCases(): iterable
    {
        foreach (self::invalid() as $sequence => $value) {
            foreach (self::stringFields($value) as $position => $case) {
                yield "{$sequence} in {$position}" => $case;
            }
        }
    }

    #[DataProvider('provideValidUtf8IsAcceptedCases')]
    public function testValidUtf8IsAccepted(MessageT $type, Message $message): void
    {
        $serializer = new Serializer();
        $bytes = $serializer->serialize($message);

        self::assertSame($bytes, $serializer->serialize($serializer->deserialize($type, $bytes)));
    }

    /**
     * @return iterable<string, array{MessageT, Message}>
     */
    public static function provideValidUtf8IsAcceptedCases(): iterable
    {
        $valid = [
            'an empty string' => '',
            'ASCII' => 'John Doe',
            'two-byte sequences' => 'Grüße, привет',
            'three-byte sequences' => '€ 日本語',
            'four-byte sequences' => "\u{1F680}\u{1F600}",
            'the highest code point' => "\u{10FFFF}",
            'the code points around the surrogates' => "\u{D7FF}\u{E000}",
        ];

        foreach ($valid as $name => $value) {
            foreach (self::stringFields($value) as $position => [$type, $message]) {
                yield "{$name} in {$position}" => [$type, $message];
            }
        }
    }

    #[DataProvider('provideBytesHoldAnyDataCases')]
    public function testBytesHoldAnyData(MessageT $type, Message $message): void
    {
        $serializer = new Serializer();
        $bytes = $serializer->serialize($message);

        self::assertSame($bytes, new Serializer(validateUtf8: false)->serialize($message));
        self::assertSame($bytes, $serializer->serialize($serializer->deserialize($type, $bytes)));
    }

    /**
     * @return iterable<string, array{MessageT, Message}>
     */
    public static function provideBytesHoldAnyDataCases(): iterable
    {
        foreach ([...self::invalid(), 'valid UTF-8' => 'Grüße', 'all the bytes' => implode('', array_map(chr(...), range(0, 255)))] as $name => $value) {
            foreach (self::bytesFields($value) as $position => $case) {
                yield "{$name} in {$position}" => $case;
            }
        }
    }

    public function testAnInvalidStringInAOneOfVariantIsLocated(): void
    {
        $bytes = new Encoder\Builder()->withUtf8Validation(false)->build()->encode(
            new Utf8ValidationTestText(choice: new Utf8ValidationTestVariant("\xFF")),
        );

        try {
            Decoder\Builder::buildDefault()->decode($bytes, Utf8ValidationTestText::class);
            self::fail('Decoding must fail.');
        } catch (MalformedUtf8 $e) {
            self::assertSame([8], $e->fields);
            self::assertSame(
                [Utf8ValidationTestText::class . '::$choice', Utf8ValidationTestVariant::class . '::$label'],
                $e->properties,
            );
        }
    }

    /**
     * @param list<positive-int> $fields
     * @param list<non-empty-string> $properties
     */
    #[DataProvider('provideTheEncoderAndTheDecoderNameTheClassAndPropertyCases')]
    public function testTheEncoderRejectsAnInvalidString(Utf8ValidationTestText $object, array $fields, array $properties): void
    {
        try {
            Encoder\Builder::buildDefault()->encode($object);
            self::fail('Encoding must fail.');
        } catch (InvalidUtf8 $e) {
            self::assertSame($fields, $e->fields);
            self::assertSame($properties, $e->properties);
            self::assertStringContainsString(\sprintf(' in field %s (%s) ', implode('.', $fields), implode(' → ', $properties)), $e->getMessage());
        }
    }

    /**
     * @param list<positive-int> $fields
     * @param list<non-empty-string> $properties
     */
    #[DataProvider('provideTheEncoderAndTheDecoderNameTheClassAndPropertyCases')]
    public function testTheDecoderRejectsAnInvalidString(Utf8ValidationTestText $object, array $fields, array $properties): void
    {
        $bytes = new Encoder\Builder()->withUtf8Validation(false)->build()->encode($object);

        try {
            Decoder\Builder::buildDefault()->decode($bytes, Utf8ValidationTestText::class);
            self::fail('Decoding must fail.');
        } catch (MalformedUtf8 $e) {
            self::assertSame($fields, $e->fields);
            self::assertSame($properties, $e->properties);
            self::assertStringContainsString(\sprintf(' in field %s (%s) ', implode('.', $fields), implode(' → ', $properties)), $e->getMessage());
        }
    }

    /**
     * @param list<positive-int> $fields
     * @param list<non-empty-string> $properties
     */
    #[DataProvider('provideTheEncoderAndTheDecoderNameTheClassAndPropertyCases')]
    public function testTheEncoderAndTheDecoderAcceptAnInvalidStringWithTheCheckOff(Utf8ValidationTestText $object, array $fields, array $properties): void
    {
        $bytes = new Encoder\Builder()->withUtf8Validation(false)->build()->encode($object);

        self::assertEquals($object, new Decoder\Builder()->withUtf8Validation(false)->build()->decode($bytes, Utf8ValidationTestText::class));
    }

    /**
     * @return iterable<string, array{Utf8ValidationTestText, list<positive-int>, list<non-empty-string>}>
     */
    public static function provideTheEncoderAndTheDecoderNameTheClassAndPropertyCases(): iterable
    {
        $text = Utf8ValidationTestText::class;
        $inner = Utf8ValidationTestInner::class;
        $invalid = "\xC0\xAF";

        yield 'a property' => [new Utf8ValidationTestText(text: $invalid), [1], ["{$text}::\$text"]];
        yield 'a list element' => [new Utf8ValidationTestText(texts: ['ok', $invalid]), [3], ["{$text}::\$texts"]];
        yield 'a map key' => [new Utf8ValidationTestText(byName: [$invalid => 'ok']), [4, 1], ["{$text}::\$byName[key]"]];
        yield 'a map value' => [new Utf8ValidationTestText(byName: ['ok' => $invalid]), [4, 2], ["{$text}::\$byName[value]"]];
        yield 'a nested object' => [
            new Utf8ValidationTestText(inner: new Utf8ValidationTestInner($invalid)),
            [7, 1],
            ["{$text}::\$inner", "{$inner}::\$name"],
        ];
    }

    public function testBytesPropertiesAreNotChecked(): void
    {
        $binary = "\xFF\xC0\xAF\xED\xA0\x80";
        $object = new Utf8ValidationTestText(data: $binary, blobs: [$binary], blobsById: [1 => $binary]);

        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, Utf8ValidationTestText::class));
    }

    public function testWithUtf8ValidationDoesNotChangeTheBuilder(): void
    {
        $object = new Utf8ValidationTestText(text: "\xFF");

        $encoder = new Encoder\Builder();
        $bytes = $encoder->withUtf8Validation(false)->build()->encode($object);

        $decoder = new Decoder\Builder();
        $decoder->withUtf8Validation(false);

        $this->expectException(MalformedUtf8::class);
        $decoder->build()->decode($bytes, Utf8ValidationTestText::class);
    }

    public function testTheEncoderBuilderDoesNotChangeEither(): void
    {
        $encoder = new Encoder\Builder();
        $encoder->withUtf8Validation(false);

        $this->expectException(InvalidUtf8::class);
        $encoder->build()->encode(new Utf8ValidationTestText(text: "\xFF"));
    }

    public function testTheCheckCanBeTurnedBackOn(): void
    {
        $this->expectException(InvalidUtf8::class);

        new Encoder\Builder()->withUtf8Validation(false)->withUtf8Validation(true)->build()->encode(new Utf8ValidationTestText(text: "\xFF"));
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    private static function invalid(): array
    {
        return [
            'a lone continuation byte' => "a\x80b",
            'a truncated sequence' => "\xE2\x82",
            'a sequence cut by the end of the string' => "ok\xF0\x9F\x9A",
            'an overlong encoding' => "\xC0\xAF",
            'an overlong three-byte encoding' => "\xE0\x80\xAF",
            'a surrogate half' => "\xED\xA0\x80",
            'a code point above U+10FFFF' => "\xF4\x90\x80\x80",
            'a byte that is never in UTF-8' => "\xFF",
        ];
    }

    /**
     * Messages with the value in each place bytes can be, with the types to read them.
     *
     * @return iterable<string, array{MessageT, Message}>
     */
    private static function bytesFields(string $value): iterable
    {
        yield 'a field' => [
            messageT(fieldT(3, bytesT)),
            message(fieldOf(3, bytesOf($value))),
        ];
        yield 'a list element' => [
            messageT(fieldT(3, listT(bytesT))),
            message(fieldOf(3, listOf(bytesT, ['ok', $value]))),
        ];
        yield 'a map value' => [
            messageT(fieldT(3, mapT(int32T, bytesT))),
            message(fieldOf(3, mapOf(int32T, bytesT, Map::fromArray([1 => $value])))),
        ];
        yield 'a nested message' => [
            messageT(fieldT(3, messageT(fieldT(1, bytesT)))),
            message(fieldOf(3, messageOf(fieldOf(1, bytesOf($value))))),
        ];
    }

    /**
     * Messages with the value in each place a string can be, with the types to read them and the field numbers
     * that lead to the value.
     *
     * @return iterable<string, array{MessageT, Message, list<positive-int>}>
     */
    private static function stringFields(string $value): iterable
    {
        yield 'a field' => [
            messageT(fieldT(3, stringT)),
            message(fieldOf(3, stringOf($value))),
            [3],
        ];
        yield 'a list element' => [
            messageT(fieldT(3, listT(stringT))),
            message(fieldOf(3, listOf(stringT, ['ok', $value]))),
            [3],
        ];
        yield 'a map key' => [
            messageT(fieldT(3, mapT(stringT, int32T))),
            message(fieldOf(3, mapOf(stringT, int32T, Map::fromArray([$value => 1])))),
            [3, 1],
        ];
        yield 'a map value' => [
            messageT(fieldT(3, mapT(int32T, stringT))),
            message(fieldOf(3, mapOf(int32T, stringT, Map::fromArray([1 => $value])))),
            [3, 2],
        ];
        yield 'a nested message' => [
            messageT(fieldT(3, messageT(fieldT(1, stringT)))),
            message(fieldOf(3, messageOf(fieldOf(1, stringOf($value))))),
            [3, 1],
        ];
        yield 'a message in a map value' => [
            messageT(fieldT(3, mapT(int32T, messageT(fieldT(5, stringT))))),
            message(fieldOf(3, mapOf(int32T, messageT(fieldT(5, stringT)), Map::fromArray([1 => message(fieldOf(5, stringOf($value)))])))),
            [3, 2, 5],
        ];
    }
}

final readonly class Utf8ValidationTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
    ) {}
}

final readonly class Utf8ValidationTestVariant
{
    public function __construct(
        #[Reflection\Field(8, Reflection\StringT::T)]
        public string $label = '',
    ) {}
}

final readonly class Utf8ValidationTestText
{
    /**
     * @param list<string> $texts
     * @param array<string, string> $byName
     * @param list<string> $blobs
     * @param array<int, string> $blobsById
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $text = '',
        #[Reflection\Field(2, Reflection\BytesT::T)]
        public string $data = '',
        #[Reflection\Field(3, new Reflection\ListT(Reflection\StringT::T))]
        public array $texts = [],
        #[Reflection\Field(4, new Reflection\MapT(Reflection\StringT::T, Reflection\StringT::T))]
        public array $byName = [],
        #[Reflection\Field(5, new Reflection\ListT(Reflection\BytesT::T))]
        public array $blobs = [],
        #[Reflection\Field(6, new Reflection\MapT(Reflection\Int32T::T, Reflection\BytesT::T))]
        public array $blobsById = [],
        #[Reflection\Field(7, new Reflection\ObjectT(Utf8ValidationTestInner::class))]
        public ?Utf8ValidationTestInner $inner = null,
        #[Reflection\OneOf([Utf8ValidationTestVariant::class])]
        public ?Utf8ValidationTestVariant $choice = null,
    ) {}
}
