<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * A varint that does not end within 10 bytes, the most a 64-bit value takes.
 *
 * @api
 */
final class MalformedVarint extends MalformedMessage {}
