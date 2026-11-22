#!/bin/sh
# Generates the classes of google/protobuf for comparison.proto into var/tools/google-protobuf/generated with protoc
# pinned in tests/tools/protobuf.sh. Run by `make bench-compare` from the root of the repository.

set -eu

. tests/tools/protobuf.sh

install_protoc

out=var/tools/google-protobuf/generated

rm -rf "$out"
mkdir -p "$out"

"$protoc" --proto_path=benchmarks/Comparison --php_out="$out" comparison.proto
