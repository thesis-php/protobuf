<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\DeserializeMessage;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\ListCodec;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\SerializeMessage;
use Thesis\Protobuf\Internal\Serde\SingularCodec;

#[CoversClass(Serializer::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(SingularCodec::class)]
#[CoversClass(ListCodec::class)]
#[CoversClass(MapCodec::class)]
#[CoversClass(SerializeMessage::class)]
#[CoversClass(DeserializeMessage::class)]
final class RoundTripPropertyTest extends TestCase
{
    private const int MESSAGES_PER_SEED = 25;

    /**
     * Any message of any type is read back as it was written, but for what normalize() leaves out.
     */
    #[DataProvider('provideAnyMessageIsReadBackAsItWasWrittenCases')]
    public function testAnyMessageIsReadBackAsItWasWritten(int $seed): void
    {
        $random = new RandomMessages($seed);
        $serializer = new Serializer();

        for ($i = 0; $i < self::MESSAGES_PER_SEED; ++$i) {
            $type = $random->messageType(3);
            $message = $random->message($type);

            $bytes = $serializer->serialize($message);

            self::assertSame(
                self::normalize($message),
                self::normalize($serializer->deserialize($type, $bytes)),
                \sprintf('Message %d of seed %d, encoded as %s, was read back changed.', $i, $seed, bin2hex($bytes)),
            );
        }
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideAnyMessageIsReadBackAsItWasWrittenCases(): iterable
    {
        // Fixed seeds keep the suite deterministic; a failure names the seed and the message to reproduce it.
        foreach ([1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 144, 233] as $seed) {
            yield "seed {$seed}" => [$seed];
        }
    }

    /**
     * The value in a form that assertSame() compares as the round trip must keep it:
     *  - a list or a map without elements is not written at all, so the field is missing when read back, as if
     *    the message had no such field: an empty one is left out;
     *  - floats are compared by their bits, so that -0.0 differs from 0.0 and NaN equals NaN; NaNs differ only in
     *    the payload, which PHP need not keep when it widens a 32-bit float, so every NaN is the same;
     *  - a Number is compared by its value, a Map by its entries in order: they are written and read in order.
     */
    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof Message) {
            $fields = [];

            foreach ($value->fields as $num => $field) {
                $it = $field->value->value;

                if ($it !== [] && !($it instanceof Map && \count($it) === 0)) {
                    $fields[$num] = self::normalize($it);
                }
            }

            return ['fields' => $fields, 'unknowns' => $value->unknowns];
        }

        if ($value instanceof Map) {
            $entries = [];

            foreach ($value as $key => $it) {
                $entries[] = [self::normalize($key), self::normalize($it)];
            }

            return $entries;
        }

        if (\is_array($value)) {
            return array_map(self::normalize(...), $value);
        }

        if (\is_float($value)) {
            return is_nan($value) ? 'NaN' : bin2hex(pack('e', $value));
        }

        if ($value instanceof Number) {
            return "Number({$value->value})";
        }

        return $value;
    }
}
