<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 */
function copyBuffer(ReadBuffer $src, WriteBuffer $dst): void
{
    $written = $src->flush();
    if ($written !== '') {
        SerdeVarint::T->serialize($dst, new Number(\strlen($written)));
        $dst->write($written);
    }
}
