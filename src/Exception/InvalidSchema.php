<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * A type or schema declaration that protobuf does not allow, e.g. a packed repeated field of strings.
 * It is a configuration error, not a malformed message.
 *
 * @api
 */
final class InvalidSchema extends ProtobufException {}
