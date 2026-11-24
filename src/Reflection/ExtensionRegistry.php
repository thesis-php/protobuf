<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Internal\Extension\ExtensionChecker;
use Thesis\Protobuf\Internal\Extension\ExtensionCodec;
use Thesis\Protobuf\Internal\Extension\ExtensionValues;
use Thesis\Protobuf\Serializer;

/**
 * The extensions that a reflector reads among the unknown fields of each object it maps, of a class they extend, so
 * that a malformed one fails the mapping: see `Decoder\Builder::withExtensions()`, which registers them in a decoder.
 * It is given to `Reflector::build()` with the options the extensions are read with, those of the serializer
 * the messages mapped are read with.
 *
 * @api
 */
final readonly class ExtensionRegistry
{
    /**
     * The extensions registered, each instance once.
     *
     * @var list<Extension<mixed>>
     */
    public array $extensions;

    private ExtensionValues $values;

    /**
     * @param array<Extension<mixed>> $extensions an instance given again is registered once
     * @param non-negative-int $recursionLimit how many messages and groups the input may nest, see
     *        `Decoder\Builder::withRecursionLimit()`: those of the value of an extension count with the messages that
     *        hold the object extended
     * @param bool $validateUtf8 whether the strings of the extensions must be valid UTF-8
     * @throws InvalidSchema if two different instances extend the same class with the same number; two that extend
     *         a class and its parent, or an interface it implements, with the same number fail the mapping of
     *         an object of the class instead
     */
    public function __construct(
        array $extensions,
        private int $recursionLimit = Serializer::DEFAULT_RECURSION_LIMIT,
        private bool $validateUtf8 = true,
    ) {
        $registered = [];
        $distinct = [];

        foreach ($extensions as $extension) {
            $other = $registered[$extension->extendee][$extension->num] ?? null;

            if ($other === null) {
                $registered[$extension->extendee][$extension->num] = $extension;
                $distinct[] = $extension;
            } elseif ($other !== $extension) {
                throw new InvalidSchema(\sprintf(
                    'Two extensions of "%s" have the number %d: a field number is declared once.',
                    $extension->extendee,
                    $extension->num,
                ));
            }
        }

        $this->extensions = $distinct;
        $this->values = new ExtensionValues();
    }

    /**
     * @internal
     */
    public function checker(Reflector $reflector): ExtensionChecker
    {
        return new ExtensionChecker(
            $this->extensions,
            new ExtensionCodec(new Serializer($this->recursionLimit, $this->validateUtf8), $reflector),
            $this->recursionLimit,
            $this->validateUtf8,
            $this->values,
        );
    }

    /**
     * The values of the extensions that the reflectors of the registry read, once they are asked for.
     *
     * @internal
     */
    public function values(): ExtensionValues
    {
        $this->values->read = true;

        return $this->values;
    }
}
