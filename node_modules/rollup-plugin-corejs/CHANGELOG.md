<!-- 
SPDX-FileCopyrightText: 2023 Ferdinand Thiessen <opensource@fthiessen.de>
SPDX-License-Identifier: EUPL-1.2
--->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2024-12-05
### Changed
- Updated dependencies

## [1.0.0] - 2024-04-21
### Added
- New feature to add polyfills only for APIs that are used by your code.
  This is similar to Babels `useBuiltIns: 'usage'`. Please not that only your code is analyzed and not your third party dependencies.
- `usage` option added, set to true to enable detecting usage polyfills

### Changed
- `summary` option was removed
- Update dependencies

### Fixed

## [0.3.0] - 2023-03-22
### Changed
- Update dependencies

### Fixed
- Fix loading extended browserslist config when no targets are given

## [0.2.0] - 2023-03-08
### Changed
- Update dependencies
- Update `core-js-builder` to 3.29.0 which now provides its own types

### Fixed
- Provide plugin options types

## [0.1.0] - 2023-01-29
### Added 

- Initial release for rollup 3.x

[unreleased]: https://github.com/susnux/rollup-plugin-corejs/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/susnux/rollup-plugin-corejs/releases/tag/v0.2.0...v0.3.0
[0.2.0]: https://github.com/susnux/rollup-plugin-corejs/releases/tag/v0.1.0...v0.2.0
[0.1.0]: https://github.com/susnux/rollup-plugin-corejs/releases/tag/v0.1.0