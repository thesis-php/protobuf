# Conformance tests

The files here check the library with `conformance_test_runner`, the test suite every implementation of protobuf is
checked with. The runner starts `testee.php` and sends it a few thousand payloads, each with the message to read it as;
the program decodes the payload with the library, encodes the object back and returns the bytes, or the error. The
runner compares what it gets with what the reference implementation of C++ does.

- `testee.php` is the program the runner tests. It reads each request from stdin as its length, 4 bytes little-endian,
  and a `ConformanceRequest`, and writes the `ConformanceResponse` to stdout the same way. It decodes with
  `UnknownFields::handler()`, so the unknown fields of the payload are written back. A failure to decode is a
  `parse_error`, a failure to encode is a `serialize_error`. It skips the tests of the JSON and the text format, which
  the library does not support. Its decoder is built as by default, so it checks the extensions of the test messages
  registered in the global pool, `Reflection\ExtensionPool::global()`, as the reference implementation does.
- `TestMessagesExtensions.php` declares those extensions by hand, a class for each `.proto` file with a static method
  for each extension, and `extensions.php` registers them in the global pool with `register()`, a resolver for each
  class extended that matches the number with the method, as the autoload file that thesis/protoc-plugin generates is
  to do. `testee.php` loads the classes with an autoloader of its own, as Composer would load generated ones, so they
  are loaded only when a resolver first calls a method: the plugin does not generate extensions yet, and the code it
  generates for them is to replace both files. The extensions of the MessageSet `MessageSetCorrect` are not there:
  the plugin does not mark its class with `Reflection\MessageSet`, so they would be read as fields of their numbers
  instead of as items.
- `generated/` holds the classes of `conformance.proto`, of `test_messages_proto3.proto` and
  `test_messages_proto2.proto`, of the same two written in editions (`test_messages_proto3_editions.proto`,
  `test_messages_proto2_editions.proto`) and of `test_messages_edition2023.proto`, written by [thesis/protoc-plugin](https://github.com/thesis-php/protoc-plugin). The well-known types they refer to are
  those of the dev dependency `thesis/protobuf-known-types`. Do not edit them: they are excluded from PHP-CS-Fixer and
  Rector.
- `failure_list.txt` lists the tests the library is known to fail, each with the reason, a limitation of the library or
  of the plugin, or a bug of the library not fixed yet.
- `Dockerfile` builds the runner from the sources of protobuf with CMake, and `static-jsoncpp.cmake` makes it link
  statically.

## Running

```shell
make conformance
```

The runner is not part of the release archives of protobuf: on first use `make conformance-runner` builds it into
`var/tools` with `Dockerfile`, which takes a couple of minutes. It is linked statically, so it runs in the php
container as is. The runner gets `--enforce_recommended`, so the recommended tests count as much as the required ones,
and `--maximum_edition 2023`, without which it does not send the messages of editions. It fails both on a test that
fails and is not in `failure_list.txt`, and on a test that is in the list and passes. To run one test, pass the options
of the runner:

```shell
make conformance ARGS='--test Required.Proto3.ProtobufInput.BadTag_OverlongVarint --debug'
```

When a change fixes a test of the list, remove it from the list. A test that newly fails is a bug to fix; one that cannot
be passed goes to the list with the reason in a comment above it. The text after `#` on the line of a test is the failure
message the runner must report for it: an empty one matches any.

## Regenerating the classes

```shell
make conformance-generate
```

It runs `generate.sh` in the php container, which rewrites `generated/` with protoc, thesis/protoc-plugin and the
`.proto` files of the releases pinned in `tests/tools/protobuf.sh`, each downloaded into `var/tools` on first use and
checked against the SHA-256 that the release lists. The output is the same on every run.

## CI

CI does not build the runner: the `conformance` job copies it from the image
`ghcr.io/thesis-php/protobuf-conformance-runner:<version>`, which holds nothing but the binary, with
`make conformance-runner-pull`. The image is built once for each release of protobuf and published by hand:

```shell
make conformance-runner-image
docker push ghcr.io/thesis-php/protobuf-conformance-runner:36.2
```

## Moving to another release of protobuf

The runner, the `.proto` files and protoc of the golden files in `../testdata/golden` are the same release, pinned in
`tests/tools/protobuf.sh`. After changing the version and the checksums there, and the versions and checksums of abseil
and jsoncpp in `Dockerfile` to those `cmake/dependencies.cmake` of the release pins, run
`make golden conformance-generate conformance`, go through the failures, and publish the image of the new runner before
merging.
