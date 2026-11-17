<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Encoder;

use Thesis\Protobuf\ProtobufException;

/**
 * @api
 * @deprecated Not thrown any more: encoding errors are reported by the specific subclasses of ProtobufException,
 *             such as Exception\ValueOutOfRange. Catch ProtobufException instead. Will be removed in the next minor version.
 */
final class EncodingError extends ProtobufException {}
