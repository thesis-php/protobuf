# Golden files

The files here check the library against the reference implementation of protobuf. Each directory belongs to a schema
of `../reflection/` with the same name, whose PHP file declares the classes of its messages:

- `<case>.txtpb` is a message in the protobuf text format. Its header names the schema, relative to `tests/testdata`,
  and the message:

  ```
  # proto-file: reflection/oneof.proto
  # proto-message: test.v1.Developer
  ```

- `<case>.bin` is what `protoc --encode` writes for it. It is committed: the tests do not need protoc.
- `expected.php` gives, by hand, the object each case stands for.
- `decode-only/` holds cases written from a variant of the schema, which is next to them: they are decoded into the
  object of the case with the same name in the directory above, but the library writes that object otherwise.

`GoldenFilesTest` decodes each `.bin` file into the expected object and encodes the object back to the same bytes.

`extensions/` is checked by `ExtensionsGoldenFilesTest` instead: an object does not hold its extensions, so its
`expected.php` gives, for each case, the decoded object under `''` and the value of each extension it has under the
path to it, the full names of the extensions from the object separated by `/`, such as
`'test.v1.note/test.v1.priority'`. Any extension of those objects without a path must be absent. The library writes
extensions as unknown fields after the known ones: the test encodes the decoded object back to the same bytes, and
the expected object, with its extensions set in the order of their numbers, as protoc writes them, to the same bytes
too, but for the cases of `decode-only/`. `message_set*` are cases of a MessageSet, whose extensions are written as
its items.

## Regenerating

After adding or changing a `.txtpb` file, and an entry in `expected.php` for it, run:

```shell
make golden
```

It runs `generate.sh` in the php container, which rewrites every `.bin` file with protoc **36.2**, the static binary of
the [official release](https://github.com/protocolbuffers/protobuf/releases/tag/v36.2) pinned in
`tests/tools/protobuf.sh`. The binary is downloaded into `var/tools` on first use and checked against the SHA-256 that
the release lists. protoc runs with `--deterministic_output`, so it writes the entries of a map in the order of their
keys, and the same input always gives the same bytes. To move to another version of protoc, change it and the checksums
in `tests/tools/protobuf.sh`, and the version here: the conformance test runner moves with it, see
`tests/conformance/README.md`.
