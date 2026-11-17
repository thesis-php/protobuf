# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Declaring a repeated field of a non-packable type (string, bytes, message) as `packed: true` now throws
  `Exception\InvalidSchema` when the type is created. It used to produce output that no parser reads correctly.
