<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\FieldDescriptorProto;

/**
 * @api
 */
enum Type: int
{
    case TYPE_DOUBLE = 1;
    case TYPE_FLOAT = 2;
    case TYPE_INT64 = 3;
    case TYPE_UINT64 = 4;
    case TYPE_INT32 = 5;
    case TYPE_FIXED64 = 6;
    case TYPE_FIXED32 = 7;
    case TYPE_BOOL = 8;
    case TYPE_STRING = 9;
    case TYPE_GROUP = 10;
    case TYPE_MESSAGE = 11;
    case TYPE_BYTES = 12;
    case TYPE_UINT32 = 13;
    case TYPE_ENUM = 14;
    case TYPE_SFIXED32 = 15;
    case TYPE_SFIXED64 = 16;
    case TYPE_SINT32 = 17;
    case TYPE_SINT64 = 18;
}
