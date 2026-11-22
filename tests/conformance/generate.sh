#!/bin/sh
# Generates the PHP classes of the messages the conformance test runner sends into tests/conformance/generated with
# thesis/protoc-plugin. Run it with `make conformance-generate` from the root of the repository: see README.md next to
# this script.
#
# protoc, the plugin and the sources of the .proto files are the releases pinned in tests/tools/protobuf.sh: the
# messages are those of the runner built from the same release.

set -eu

. tests/tools/protobuf.sh

install_protoc
install_protoc_plugin
install_protobuf_source

out=tests/conformance/generated

rm -rf "$out"
mkdir -p "$out"

# The well-known types the test messages import are not generated: the plugin refers to the classes of
# thesis/protobuf-known-types for them. No descriptor metadata is generated either: nothing looks the messages up by name.
"$protoc" \
    --proto_path="$protobuf_source" \
    --proto_path="$protobuf_source/src" \
    --plugin=protoc-gen-php-plugin="$protoc_plugin" \
    --php-plugin_opt=metadata=none \
    --php-plugin_opt=grpc=none \
    --php-plugin_out="$out" \
    conformance/conformance.proto \
    google/protobuf/test_messages_proto3.proto \
    google/protobuf/test_messages_proto2.proto
