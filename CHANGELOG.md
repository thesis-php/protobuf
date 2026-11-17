# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Declaring a repeated field of a non-packable type (string, bytes, message) as `packed: true` now throws
  `Exception\InvalidSchema` when the type is created. It used to produce output that no parser reads correctly.
- Serializing a value that its protobuf type cannot represent now throws `Exception\ValueOutOfRange` instead of
  wrapping it or writing a broken varint: `int32`/`sint32`/`sfixed32`/enum outside [-2^31, 2^31-1] (e.g.
  `int32Of(2 ** 31)` used to wrap to a negative number), `uint32`/`fixed32` outside [0, 2^32-1], and
  `uint64`/`fixed64` that are negative, above 2^64-1 or fractional.
- Serializing a string-backed PHP enum as a protobuf enum throws `Exception\InvalidSchema` instead of
  `\InvalidArgumentException`.

### Fixed

- Negative enum numbers are encoded as 10-byte sign-extended varints, as protobuf requires. They used to be
  written as a truncated varint that no parser, including this one, could read.
