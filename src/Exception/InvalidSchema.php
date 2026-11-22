<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * A type or schema declaration that protobuf does not allow, e.g. a packed repeated field of strings or two fields
 * with the same number, the latter in a message built by hand as well, or an unknown field to write whose value does
 * not fit its wire type. It is a configuration error, not a malformed message.
 *
 * @api
 */
final class InvalidSchema extends ProtobufException {}
