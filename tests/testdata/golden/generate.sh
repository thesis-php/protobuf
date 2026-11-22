#!/bin/sh
# Writes the .bin golden file of every .txtpb one under tests/testdata/golden with the reference protoc.
# Run it with `make golden` from the root of the repository: see README.md next to this script.
#
# protoc is the static binary of the official release pinned in tests/tools/protobuf.sh, downloaded once into
# var/tools and checked against the SHA-256 that GitHub lists for the release asset, so it runs in the php container as is.

set -eu

. tests/tools/protobuf.sh

install_protoc

# Each .txtpb names its schema, relative to tests/testdata, and its message in the header of the text format.
find tests/testdata/golden -name '*.bin' -delete

find tests/testdata/golden -name '*.txtpb' | sort | while read -r txtpb; do
    file=$(sed -n 's/^# proto-file: //p' "$txtpb")
    message=$(sed -n 's/^# proto-message: //p' "$txtpb")

    if [ -z "$file" ] || [ -z "$message" ]; then
        echo "$txtpb has no proto-file or proto-message header." >&2
        exit 1
    fi

    # Without --deterministic_output protoc may write the entries of a map in any order.
    "$protoc" --proto_path=tests/testdata --deterministic_output --encode="$message" "tests/testdata/$file" \
        < "$txtpb" > "${txtpb%.txtpb}.bin"
done
