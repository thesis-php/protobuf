# The one place that pins the release of protobuf the tests are checked against, and the release of thesis/protoc-plugin
# the conformance test messages are generated with. Sourced by the scripts that need protoc
# (tests/testdata/golden/generate.sh, tests/conformance/generate.sh) and read by the Makefile, which passes the version
# and the source checksum to tests/conformance/Dockerfile.
#
# Every checksum is the SHA-256 that GitHub lists for the asset of the release. The functions download into var/tools
# once, so they run from the root of the repository.

protobuf_version=36.2

# protobuf-<version>.tar.gz: the sources the conformance test runner is built from.
protobuf_source_sha256=3d9642a662d10e68ebae5e53f14dcce5105684212d5078f8e0d47d1ab3ae6b64

# protoc-gen-php: the phar of the thesis/protoc-plugin release, run by protoc in the php container.
protoc_plugin_version=0.6.3
protoc_plugin_sha256=5c9d7b0a0eaa6f7d1e65dd31ee1b1d21cda09b5d201b335f1c435ade79dd90e4

# Downloads the static protoc of the release into var/tools once and sets $protoc to its path.
install_protoc() {
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
            echo "No protoc $protobuf_version release is pinned for $(uname -m)." >&2
            exit 1
            ;;
    esac

    tools=var/tools/protoc-$protobuf_version
    protoc=$tools/bin/protoc

    if [ ! -x "$protoc" ]; then
        zip=$tools-linux-$arch.zip
        mkdir -p var/tools
        curl --fail --silent --show-error --location --output "$zip" \
            "https://github.com/protocolbuffers/protobuf/releases/download/v$protobuf_version/protoc-$protobuf_version-linux-$arch.zip"
        echo "$sha256  $zip" | sha256sum -c -
        rm -rf "$tools"
        unzip -q -d "$tools" "$zip"
        rm "$zip"
    fi

    if [ "$("$protoc" --version)" != "libprotoc $protobuf_version" ]; then
        echo "$protoc is not protoc $protobuf_version." >&2
        exit 1
    fi
}

# Downloads the phar of protoc-gen-php into var/tools once and sets $protoc_plugin to its path.
install_protoc_plugin() {
    protoc_plugin=var/tools/protoc-gen-php-$protoc_plugin_version

    if [ ! -x "$protoc_plugin" ]; then
        mkdir -p var/tools
        curl --fail --silent --show-error --location --output "$protoc_plugin.download" \
            "https://github.com/thesis-php/protoc-plugin/releases/download/$protoc_plugin_version/protoc-gen-php"
        echo "$protoc_plugin_sha256  $protoc_plugin.download" | sha256sum -c -
        chmod +x "$protoc_plugin.download"
        mv "$protoc_plugin.download" "$protoc_plugin"
    fi
}

# Downloads the sources of the release into var/tools once and sets $protobuf_source to the directory they are in.
install_protobuf_source() {
    protobuf_source=var/tools/protobuf-$protobuf_version

    if [ ! -d "$protobuf_source" ]; then
        archive=$protobuf_source.tar.gz
        mkdir -p var/tools
        curl --fail --silent --show-error --location --output "$archive" \
            "https://github.com/protocolbuffers/protobuf/releases/download/v$protobuf_version/protobuf-$protobuf_version.tar.gz"
        echo "$protobuf_source_sha256  $archive" | sha256sum -c -
        tar -xzf "$archive" -C var/tools
        rm "$archive"
    fi
}
