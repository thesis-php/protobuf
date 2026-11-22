<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Type;

/**
 * Text: it must be valid UTF-8 when serialized and deserialized, unless the check is turned off (see
 * `Serializer::__construct()`). Binary data is `BytesT`.
 *
 * @template-implements Type<string>
 * @template-implements Listable<string>
 */
enum StringT implements
    Type,
    Listable
{
    /** @use Listed<string> */
    use Listed;

    case T;

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->string($this);
    }
}
