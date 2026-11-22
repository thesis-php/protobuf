<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * Text: it must be valid UTF-8 when encoded and decoded, unless the check is turned off (see
 * `Encoder\Builder::withUtf8Validation()` and `Decoder\Builder::withUtf8Validation()`). Binary data is `BytesT`.
 *
 * @api
 * @template-implements Type<string>
 */
enum StringT implements Type
{
    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->string($this);
    }
}
