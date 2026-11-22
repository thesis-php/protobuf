# Builds conformance_test_runner from the sources of the protobuf release pinned in tests/tools/protobuf.sh.
# The runner is not part of the release archives. `make conformance-runner` builds it with this file and exports the
# binary into var/tools: it is linked statically, so it runs as is in the php container, whatever its PHP version.
#
# CMake is used rather than Bazel: it needs only the compiler of the distribution and two pinned source archives, while
# Bazel would download its own toolchains and dozens of modules on every cold build.

FROM alpine:3.24 AS build

RUN apk add --no-cache build-base cmake curl linux-headers ninja

ARG PROTOBUF_VERSION
ARG PROTOBUF_SHA256
# The versions protobuf pins in cmake/dependencies.cmake of that release.
ARG ABSEIL_VERSION=20250512.1
ARG ABSEIL_SHA256=9b7a064305e9fd94d124ffa6cc358592eb42b5da588fb4e07d09254aa40086db
ARG JSONCPP_VERSION=1.9.6
ARG JSONCPP_SHA256=f93b6dd7ce796b13d02c108bc9f79812245a82e577581c4c9aabe57075c90ea2

WORKDIR /src

RUN set -eu; \
    fetch() { \
        curl --fail --silent --show-error --location --output "$1.tar.gz" "$2"; \
        echo "$3  $1.tar.gz" | sha256sum -c -; \
        mkdir "$1"; \
        tar -xzf "$1.tar.gz" -C "$1" --strip-components=1; \
        rm "$1.tar.gz"; \
    }; \
    fetch protobuf "https://github.com/protocolbuffers/protobuf/releases/download/v${PROTOBUF_VERSION}/protobuf-${PROTOBUF_VERSION}.tar.gz" "${PROTOBUF_SHA256}"; \
    fetch abseil "https://github.com/abseil/abseil-cpp/archive/refs/tags/${ABSEIL_VERSION}.tar.gz" "${ABSEIL_SHA256}"; \
    fetch jsoncpp "https://github.com/open-source-parsers/jsoncpp/archive/refs/tags/${JSONCPP_VERSION}.tar.gz" "${JSONCPP_SHA256}"

COPY static-jsoncpp.cmake /src/

# protoc is built too: the runner is linked with the C++ code it generates for the test messages.
RUN cmake -S protobuf -B build -G Ninja \
        -DCMAKE_BUILD_TYPE=Release \
        -DCMAKE_EXE_LINKER_FLAGS=-static \
        -Dprotobuf_BUILD_CONFORMANCE=ON \
        -Dprotobuf_BUILD_TESTS=OFF \
        -Dprotobuf_BUILD_LIBUPB=OFF \
        -Dprotobuf_BUILD_SHARED_LIBS=OFF \
        -Dprotobuf_INSTALL=OFF \
        -Dprotobuf_WITH_ZLIB=OFF \
        -Dprotobuf_FORCE_FETCH_DEPENDENCIES=ON \
        -DFETCHCONTENT_FULLY_DISCONNECTED=ON \
        -DFETCHCONTENT_SOURCE_DIR_ABSL=/src/abseil \
        -DCMAKE_PROJECT_protobuf_INCLUDE=/src/static-jsoncpp.cmake \
    && cmake --build build --target conformance_test_runner \
    && strip build/conformance_test_runner

FROM scratch
COPY --from=build /src/build/conformance_test_runner /conformance_test_runner
