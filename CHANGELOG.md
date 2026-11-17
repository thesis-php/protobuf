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
- `enumT()`, `enumOf()`, `Type\EnumT` and `Reflection\EnumT` accept only int-backed PHP enums and throw
  `Exception\InvalidSchema` for any other class when the type is declared. A string-backed enum used to fail
  with `\InvalidArgumentException` only when a value was serialized.
- Malformed input makes `Serializer::deserialize()` and `Decoder::decode()` throw a subclass of the new
  `Exception\MalformedMessage`: `Exception\BufferUnderflow` (now a subclass of it) for a truncated input,
  `Exception\MalformedVarint` for a varint longer than 10 bytes, `Exception\UnsupportedWireType` for an unknown
  wire type. They used to throw `Thesis\Varint\MalformedVarintNumber` and `\ValueError`, which escaped
  `catch (ProtobufException)` when the low-level API was used.
- `Encoder::encode()` and `Decoder::decode()` no longer wrap every `\Throwable` into `EncodingError`/`DecodingError`.
  Library errors are `ProtobufException`s already; anything else, such as a `\TypeError` from a property whose
  PHP type does not match its protobuf type, is a bug in the schema or the code and now propagates as is.
  `Decoder::decode()` still reports a class that cannot be reflected as `DecodingError`.

### Deprecated

- `Encoder\EncodingError` is not thrown any more: a `catch (EncodingError)` no longer catches anything. Catch
  `ProtobufException` or one of its specific subclasses instead.

### Fixed

- Negative enum numbers are encoded as 10-byte sign-extended varints, as protobuf requires. They used to be
  written as a truncated varint that no parser, including this one, could read.
