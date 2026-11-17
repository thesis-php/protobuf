<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * The input is not a valid protobuf encoding. Catch this to handle any malformed input; the subclasses
 * tell what exactly is wrong.
 *
 * @api
 */
abstract class MalformedMessage extends ProtobufException {}
