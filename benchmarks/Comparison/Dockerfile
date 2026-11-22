# The php image of the project with what the comparison with google/protobuf needs: see README.md next to this file.
ARG PHP_IMAGE_VERSION

FROM ghcr.io/phpyh/php:${PHP_IMAGE_VERSION}

# The version of the runtime that goes with protoc pinned in tests/tools/protobuf.sh.
ARG GOOGLE_PROTOBUF_VERSION

USER root

# Xdebug slows the code down even when it is off, so it is not loaded at all. The extension of google/protobuf is
# installed, but loaded only by the run that measures it: PHP_INI_SCAN_DIR adds /usr/local/etc/php/protobuf then.
RUN install-php-extensions protobuf-${GOOGLE_PROTOBUF_VERSION} \
    && mkdir /usr/local/etc/php/protobuf \
    && mv /usr/local/etc/php/conf.d/docker-php-ext-protobuf.ini /usr/local/etc/php/protobuf/ \
    && rm /usr/local/etc/php/conf.d/*xdebug*.ini

USER dev
