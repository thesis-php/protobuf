<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Buffer\Reader;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\ListCodec;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\SingularCodec;

#[CoversClass(Serializer::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(SingularCodec::class)]
#[CoversClass(ListCodec::class)]
#[CoversClass(MapCodec::class)]
#[CoversClass(Reader::class)]
final class DeserializeFuzzTest extends TestCase
{
    private const int TYPES_PER_SEED = 10;
    private const int INPUTS_PER_TYPE = 40;

    /**
     * Random bytes and valid encodings with bits flipped, cut short or spliced together are read or rejected with
     * a ProtobufException, never with another error, a warning or a notice.
     */
    #[DataProvider('provideAnyInputIsReadOrRejectedWithAProtobufExceptionCases')]
    public function testAnyInputIsReadOrRejectedWithAProtobufException(int $seed): void
    {
        $random = new RandomMessages($seed);
        $serializer = new Serializer();

        set_error_handler(static function (int $level, string $message): never {
            throw new \ErrorException($message, 0, $level);
        });

        try {
            for ($t = 0; $t < self::TYPES_PER_SEED; ++$t) {
                $type = $random->messageType(3);
                $valid = [
                    $serializer->serialize($random->message($type)),
                    $serializer->serialize($random->message($type)),
                ];

                for ($i = 0; $i < self::INPUTS_PER_TYPE; ++$i) {
                    $bytes = self::input($random, $valid);

                    try {
                        $serializer->deserialize($type, $bytes);
                    } catch (ProtobufException) {
                        // A malformed input is rejected.
                    } catch (\Throwable $e) {
                        self::fail(\sprintf(
                            'Input %d for type %d of seed %d, %s, failed with %s: %s',
                            $i,
                            $t,
                            $seed,
                            bin2hex($bytes),
                            $e::class,
                            $e->getMessage(),
                        ));
                    }
                }
            }
        } finally {
            restore_error_handler();
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideAnyInputIsReadOrRejectedWithAProtobufExceptionCases(): iterable
    {
        // Fixed seeds keep the suite deterministic; a failure names the seed and the input to reproduce it.
        foreach ([7, 42, 1_000, 65_537, 20_261_122] as $seed) {
            yield "seed {$seed}" => [$seed];
        }
    }

    /**
     * @param array{string, string} $valid two valid encodings of the type
     */
    private static function input(RandomMessages $random, array $valid): string
    {
        $bytes = $random->int(0, 1) === 0 ? $valid[0] : $valid[1];
        $length = \strlen($bytes);

        if ($length === 0) {
            return $random->bytes(1, 32);
        }

        return match ($random->int(0, 5)) {
            // Random bytes.
            0 => $random->bytes(1, 64),
            // One bit flipped.
            1 => self::flipBit($bytes, $random->int(0, $length - 1), $random->int(0, 7) & 7),
            // A few bits flipped.
            2 => self::flipBit(
                self::flipBit($bytes, $random->int(0, $length - 1), $random->int(0, 7) & 7),
                $random->int(0, $length - 1),
                $random->int(0, 7) & 7,
            ),
            // Cut short.
            3 => substr($bytes, 0, $random->int(0, $length - 1)),
            // Random bytes put in.
            4 => substr_replace($bytes, $random->bytes(1, 8), $random->int(0, $length), $random->int(0, 2)),
            // The start of one encoding followed by the end of the other.
            default => substr($bytes, 0, $random->int(0, $length)) . substr($valid[1], $random->int(0, \strlen($valid[1]))),
        };
    }

    /**
     * @param int<0, 7> $bit
     */
    private static function flipBit(string $bytes, int $offset, int $bit): string
    {
        $bytes[$offset] = \chr((\ord($bytes[$offset]) ^ (1 << $bit)) & 0xFF);

        return $bytes;
    }
}
