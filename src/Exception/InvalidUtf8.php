<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * A value of a string field to serialize that is not valid UTF-8, which a protobuf string must be. Binary data
 * belongs in a bytes field. The check can be turned off, see `Serializer::__construct()` and
 * `Encoder\Builder::withUtf8Validation()`.
 *
 * @api
 */
final class InvalidUtf8 extends ProtobufException
{
    use FieldLocation;

    public function __construct()
    {
        parent::__construct($this->describe());
    }

    #[\Override]
    private function describe(): string
    {
        return \sprintf('The string%s is not valid UTF-8, as a protobuf string must be: use bytes for binary data.', $this->location());
    }
}
