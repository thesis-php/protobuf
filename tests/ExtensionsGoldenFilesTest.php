<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\Reflector;

/**
 * Checks the extensions against the reference implementation: each .bin file under testdata/golden/extensions is what
 * protoc writes for the .txtpb file next to it, a message of extensions.proto with extensions of every kind, those of
 * a MessageSet included, and expected.php there gives the decoded object of each case and the value of each extension
 * it has (see the comment in that file). The extensions are read from the bytes, and set and written to the same bytes.
 */
#[CoversClass(Extensions::class)]
#[CoversClass(Extension::class)]
final class ExtensionsGoldenFilesTest extends TestCase
{
    private const string DIR = __DIR__ . '/testdata/golden/extensions';

    /**
     * @param non-empty-string $case
     * @param non-empty-array<string, mixed> $expected
     */
    #[DataProvider('provideDecodeCases')]
    public function testDecode(string $case, array $expected): void
    {
        $decoder = Decoder\Builder::buildDefault();
        $object = $decoder->decode(self::golden($case), self::classOf($expected));

        $read = self::read(new Decoder\Builder()->buildExtensions(), $object, '', $expected);
        ksort($read);
        ksort($expected);

        self::assertSame(GoldenFilesTest::dump($expected), GoldenFilesTest::dump($read));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-array<string, mixed>}>
     */
    public static function provideDecodeCases(): iterable
    {
        foreach (self::expected() as $case => $expected) {
            yield $case => [$case, $expected];
        }
    }

    /**
     * The objects of a case, with the extensions of the case set, are written as protoc writes the case: the
     * extensions are written after the fields, and set in the order of their numbers, as protoc writes them.
     *
     * @param non-empty-string $case
     * @param non-empty-array<string, mixed> $expected
     */
    #[DataProvider('provideWriteCases')]
    public function testWrite(string $case, array $expected): void
    {
        $object = self::write(new Decoder\Builder()->buildExtensions(), $expected, '');

        self::assertSame(bin2hex(self::golden($case)), bin2hex(Encoder\Builder::buildDefault()->encode($object)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-array<string, mixed>}>
     */
    public static function provideWriteCases(): iterable
    {
        foreach (self::expected() as $case => $expected) {
            if (!str_starts_with($case, 'decode-only/')) {
                yield $case => [$case, $expected];
            }
        }
    }

    /**
     * The decoded object is encoded to the bytes it was read from, the extensions among its unknown fields.
     *
     * @param non-empty-string $case
     * @param class-string $class
     */
    #[DataProvider('provideInputs')]
    public function testEncodeWritesTheExtensionsBack(string $case, string $class): void
    {
        $golden = self::golden($case);

        $encoded = Encoder\Builder::buildDefault()->encode(Decoder\Builder::buildDefault()->decode($golden, $class));

        self::assertSame(bin2hex($golden), bin2hex($encoded));
    }

    /**
     * @param non-empty-string $case
     * @param class-string $class
     */
    #[DataProvider('provideInputs')]
    public function testSerializerWritesBackWhatItReads(string $case, string $class): void
    {
        $golden = self::golden($case);
        $serializer = new Serializer();

        $message = $serializer->deserialize(Reflector::build()->type($class), $golden);

        self::assertSame(bin2hex($golden), bin2hex($serializer->serialize($message)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, class-string}>
     */
    public static function provideInputs(): iterable
    {
        foreach (self::expected() as $case => $expected) {
            yield $case => [$case, self::classOf($expected)];
        }
    }

    public function testEveryGoldenFileHasACase(): void
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

        $expected = array_keys(self::expected());

        sort($inputs);
        sort($expected);

        self::assertSame($inputs, $expected);
    }

    /**
     * The object under the path and the value of each extension it has, under the path to it, followed by those of
     * the objects that the extensions in the expected paths hold, recursively.
     *
     * @param array<string, mixed> $expected
     * @return array<string, mixed>
     */
    private static function read(Extensions $extensions, object $object, string $path, array $expected): array
    {
        $read = [$path => $object];

        foreach (\ExtensionsProto::all() as $name => $extension) {
            if ($extension->extendee !== $object::class) {
                continue;
            }

            $extensionPath = $path === '' ? $name : "{$path}/{$name}";
            $value = $extensions->get($object, $extension);

            if (!$extensions->has($object, $extension)) {
                self::assertSame($extension->type instanceof Reflection\ListT ? [] : null, $value, $extensionPath);

                continue;
            }

            if (\is_object($value) && \array_key_exists($extensionPath, $expected)) {
                $read = [...$read, ...self::read($extensions, $value, $extensionPath, $expected)];
            } else {
                $read[$extensionPath] = $value;
            }
        }

        return $read;
    }

    /**
     * Sets the extensions of the object under the path to the values under the paths to them, in the order of their
     * numbers, those of the objects of their values first.
     *
     * @param array<string, mixed> $expected
     */
    private static function write(Extensions $extensions, array $expected, string $path): object
    {
        $object = $expected[$path] ?? null;
        self::assertIsObject($object);

        $all = \ExtensionsProto::all();
        uasort($all, static fn(Extension $a, Extension $b): int => $a->num <=> $b->num);

        foreach ($all as $name => $extension) {
            $extensionPath = $path === '' ? $name : "{$path}/{$name}";

            if ($extension->extendee === $object::class && \array_key_exists($extensionPath, $expected)) {
                $value = $expected[$extensionPath];
                $extensions->set($object, $extension, \is_object($value) ? self::write($extensions, $expected, $extensionPath) : $value);
            }
        }

        return $object;
    }

    /**
     * @param array<string, mixed> $expected
     * @return class-string
     */
    private static function classOf(array $expected): string
    {
        $object = $expected[''] ?? null;
        self::assertIsObject($object);

        return $object::class;
    }

    /**
     * @return array<non-empty-string, non-empty-array<string, mixed>>
     */
    private static function expected(): array
    {
        ReflectionFixture::load('extensions');

        /** @var array<non-empty-string, non-empty-array<string, mixed>> */
        return require self::DIR . '/expected.php';
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
}
