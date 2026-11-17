<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * The input ends before the value being read does.
 *
 * @api
 */
final class BufferUnderflow extends MalformedMessage {}
