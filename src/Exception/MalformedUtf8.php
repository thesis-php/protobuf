<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * A string field in the input that is not valid UTF-8, which protobuf requires of a string. A bytes field holds
 * any bytes. The check can be turned off, see `Serializer::__construct()` and
 * `Decoder\Builder::withUtf8Validation()`.
 *
 * @api
 */
final class MalformedUtf8 extends MalformedMessage
{
    use FieldLocation;

    public function __construct()
    {
        parent::__construct($this->describe());
    }

    #[\Override]
    private function describe(): string
    {
        return \sprintf('The string%s is not valid UTF-8, as a protobuf string must be.', $this->location());
    }
}
