<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Reflector;

/**
 * Checks the library against the reference implementation: each .bin file under testdata/golden is what protoc writes
 * for the .txtpb file next to it (see testdata/golden/README.md), and each expected.php there gives the object that
 * the input of every case of its directory stands for, written out by hand.
 *
 * protoc writes the entries of a map in the order of their keys (it runs with --deterministic_output), while
 * the library writes them in the order the map holds them: the expected objects hold them in the order of their keys,
 * so the bytes are the same.
 */
#[CoversClass(Reflector::class)]
#[CoversClass(Serializer::class)]
final class GoldenFilesTest extends TestCase
{
    private const string DIR = __DIR__ . '/testdata/golden';

    /**
     * A case in a decode-only directory is the input of a case of the directory above written by protoc from a variant
     * of its schema: it is decoded into the same object, but the library does not write it so.
     */
    private const string DECODE_ONLY = 'decode-only/';

    /**
     * Cases the library writes the same records for as protoc, but in another order. protoc writes the fields of
     * a message in the order of their numbers, the library in the order of the properties that declare them, and
     * Developer declares its oneof, of the fields 3 to 5, after the field 6. Protobuf leaves the order to
     * the implementation and a parser must accept any, so the bytes differ only in it: the test checks that they do,
     * and that the records are the same once ordered by field number.
     */
    private const array IN_DECLARATION_ORDER = [
        'oneof/email',
        'oneof/telegram',
    ];

    /**
     * @param non-empty-string $case
     */
    #[DataProvider('provideCases')]
    #[DataProvider('provideDecodeOnlyCases')]
    public function testDecode(string $case, object $expected): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(self::golden($case), $expected::class);

        self::assertSame(self::dump($expected), self::dump($decoded));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, object}>
     */
    public static function provideDecodeOnlyCases(): iterable
    {
        foreach (self::expected() as $case => $object) {
            if (str_contains($case, self::DECODE_ONLY)) {
                yield $case => [$case, $object];
            }
        }
    }

    /**
     * @param non-empty-string $case
     */
    #[DataProvider('provideCases')]
    public function testEncode(string $case, object $expected): void
    {
        $golden = self::golden($case);
        $encoded = Encoder\Builder::buildDefault()->encode($expected);

        if (\in_array($case, self::IN_DECLARATION_ORDER, true)) {
            self::assertNotSame(bin2hex($golden), bin2hex($encoded), 'The case is written as protoc writes it now.');
            self::assertSame(self::records($golden), self::inNumberOrder(self::records($encoded)));
        } else {
            self::assertSame(bin2hex($golden), bin2hex($encoded));
        }
    }

    /**
     * @param non-empty-string $case
     */
    #[DataProvider('provideCases')]
    public function testSerializerWritesBackWhatItReads(string $case, object $expected): void
    {
        $golden = self::golden($case);
        $serializer = new Serializer();

        $message = $serializer->deserialize(Reflector::build()->type($expected::class), $golden);

        self::assertSame(bin2hex($golden), bin2hex($serializer->serialize($message)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, object}>
     */
    public static function provideCases(): iterable
    {
        foreach (self::expected() as $case => $object) {
            if (!str_contains($case, self::DECODE_ONLY)) {
                yield $case => [$case, $object];
            }
        }
    }

    public function testEveryGoldenFileHasAnExpectedObject(): void
    {
        $inputs = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::DIR, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);

            if ($file->getExtension() === 'txtpb') {
                $case = substr($file->getPathname(), \strlen(self::DIR) + 1, -\strlen('.txtpb'));
                self::assertFileExists(self::DIR . "/{$case}.bin", 'Run `make golden` to write it.');
                $inputs[] = $case;
            }
        }

        $expected = array_keys([...self::provideCases(), ...self::provideDecodeOnlyCases()]);

        sort($inputs);
        sort($expected);

        self::assertSame($inputs, $expected);
    }

    /**
     * @return array<non-empty-string, object> the expected object of each case, by its path relative to the directory
     *         of the golden files, without the extension
     */
    private static function expected(): array
    {
        $cases = [];

        $paths = glob(self::DIR . '/*/expected.php');
        if ($paths === false || $paths === []) {
            throw new \RuntimeException('Could not find the expected objects of the golden files.');
        }

        foreach ($paths as $path) {
            $schema = basename(\dirname($path));
            ReflectionFixture::load($schema);

            /** @var array<non-empty-string, object> $objects */
            $objects = require $path;

            foreach ($objects as $case => $object) {
                $cases["{$schema}/{$case}"] = $object;
            }
        }

        return $cases;
    }

    /**
     * @param non-empty-string $case
     */
    private static function golden(string $case): string
    {
        $bytes = file_get_contents(self::DIR . "/{$case}.bin");
        self::assertIsString($bytes);

        return $bytes;
    }

    /**
     * Splits the bytes of a message into its records, each the tag and the value of a field as written, in hex.
     *
     * @return list<array{int, string}> the field number and the record
     */
    private static function records(string $bytes): array
    {
        $records = [];
        $offset = 0;

        while ($offset < \strlen($bytes)) {
            $start = $offset;
            $tag = self::varint($bytes, $offset);
            $wireType = $tag & 7;

            if ($wireType === 0) {
                self::varint($bytes, $offset);
            } elseif ($wireType === 1) {
                $offset += 8;
            } elseif ($wireType === 2) {
                $length = self::varint($bytes, $offset);
                $offset += $length;
            } elseif ($wireType === 5) {
                $offset += 4;
            } else {
                self::fail("Unexpected wire type {$wireType} at {$start}.");
            }

            self::assertLessThanOrEqual(\strlen($bytes), $offset);

            $records[] = [$tag >> 3, bin2hex(substr($bytes, $start, $offset - $start))];
        }

        return $records;
    }

    /**
     * @param list<array{int, string}> $records
     * @return list<array{int, string}>
     */
    private static function inNumberOrder(array $records): array
    {
        // usort() is stable: the records of a repeated field keep their order.
        usort($records, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        return $records;
    }

    /**
     * Reads a varint of the tag or the length of a record, which fits an int.
     */
    private static function varint(string $bytes, int &$offset): int
    {
        $value = 0;

        for ($shift = 0;; $shift += 7) {
            self::assertLessThan(\strlen($bytes), $offset, 'The varint ends past the bytes.');
            $byte = \ord($bytes[$offset++]);
            $value |= ($byte & 0x7F) << $shift;

            if ($byte < 0x80) {
                return $value;
            }
        }
    }

    /**
     * Turns a value into one that assertSame() compares exactly: a float by its bits, so that -0.0 differs from 0.0
     * and NAN equals itself, a Number by its digits and the entries of a map in their order.
     */
    private static function dump(mixed $value): mixed
    {
        return match (true) {
            \is_float($value) => \sprintf('float(%s)', bin2hex(pack('E', $value))),
            $value instanceof Number => "Number({$value})",
            $value instanceof Map => [
                Map::class => array_map(
                    static fn(KVPair $pair): array => [self::dump($pair->key), self::dump($pair->value)],
                    self::pairs($value),
                ),
            ],
            $value instanceof \UnitEnum => $value::class . '::' . $value->name,
            \is_object($value) => [$value::class => array_map(self::dump(...), get_object_vars($value))],
            \is_array($value) => array_map(self::dump(...), $value),
            default => $value,
        };
    }

    /**
     * @param Map<mixed, mixed> $map
     * @return list<KVPair<mixed, mixed>>
     */
    private static function pairs(Map $map): array
    {
        $pairs = [];

        foreach ($map as $key => $value) {
            $pairs[] = new KVPair($key, $value);
        }

        return $pairs;
    }
}
