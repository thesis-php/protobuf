<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * A varint that does not end within 10 bytes, the most a 64-bit value takes, or a tag that does not end within 5.
 *
 * @api
 */
final class MalformedVarint extends MalformedMessage {}
