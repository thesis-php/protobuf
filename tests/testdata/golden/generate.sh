#!/bin/sh
# Writes the .bin golden file of every .txtpb one under tests/testdata/golden with the reference protoc.
# Run it with `make golden` from the root of the repository: see README.md next to this script.
#
# protoc is the static binary of the official release, downloaded once into var/tools and checked against
# the SHA-256 that GitHub lists for the release asset, so it runs in the php container as is.

set -eu

version=36.2

case "$(uname -m)" in
    x86_64)
        arch=x86_64
        sha256=121f6c7afe1d4d0e3ea6aab9432038599250134cbf4474cb1167d2c7decd4278
        ;;
    aarch64 | arm64)
        arch=aarch_64
        sha256=8b8f18bd2b30346efbc698dd5a73dd7c805f3ef8380f6dfc95c768f3f1852f6a
        ;;
    *)
        echo "No protoc $version release is pinned for $(uname -m)." >&2
        exit 1
        ;;
esac

tools=var/tools/protoc-$version
protoc=$tools/bin/protoc

if [ ! -x "$protoc" ]; then
    zip=$tools-linux-$arch.zip
    mkdir -p var/tools
    curl --fail --silent --show-error --location --output "$zip" \
        "https://github.com/protocolbuffers/protobuf/releases/download/v$version/protoc-$version-linux-$arch.zip"
    echo "$sha256  $zip" | sha256sum -c -
    rm -rf "$tools"
    unzip -q -d "$tools" "$zip"
    rm "$zip"
fi

if [ "$("$protoc" --version)" != "libprotoc $version" ]; then
    echo "$protoc is not protoc $version." >&2
    exit 1
fi

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
