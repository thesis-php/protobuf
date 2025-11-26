<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
enum WireType: int
{
    /** Used for int32s, int64s, uint32s, uint64s, sint32s and sint64s data types. Uses variable-length encoding. */
    case varint = 0;

    /** Always four bytes. More efficient than uint32 if values are often greater than 2^28. */
    case fixed32 = 5;

    /** Always eight bytes. More efficient than uint64 if values are often greater than 2^56. */
    case fixed64 = 1;

    /** May contain any arbitrary sequence of bytes no longer than 2^32 (strings, lists, maps, messages). */
    case bytes = 2;
}
