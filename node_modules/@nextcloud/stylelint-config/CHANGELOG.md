<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog

## [v3.1.0](https://github.com/nextcloud/stylelint-config/tree/v3.1.0) (2025-05-22)

### Added
* Add `csstools/use-logical` to warn about LTR-RTL properties [\#121](https://github.com/nextcloud-libraries/stylelint-config/pull/121) ([ShGKme](https://github.com/ShGKme))

### Changed
* Updated stylelint and require stylelint version 16.13.2 or later
* Add SPDX header [\#109](https://github.com/nextcloud-libraries/stylelint-config/pull/109) ([AndyScherzinger](https://github.com/AndyScherzinger))

## [v3.0.1](https://github.com/nextcloud/stylelint-config/tree/v3.0.1) (2024-05-07)

### Fixed

* fix: Remove stylistic rules also for scss

## [v3.0.0](https://github.com/nextcloud/stylelint-config/tree/v3.0.0) (2024-04-15)
[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.4.0...v3.0.0)

### Breaking changes :boom:
stylelint was updated to version 16.0 which drops all stylistic changes.
The means stylelint only will check for code quality but not for stylistic rules.
So it is now recommended to instead use `prettier` with `@nextcloud/prettier-config` for stylistic rules.
See also [Nextcloud prettier setup](https://github.com/nextcloud-libraries/nextcloud-prettier-config/blob/main/README.md#setup).

### Changed
* Updated stylelint and require stylelint version 16.2.0 or later.

## [v2.4.0](https://github.com/nextcloud/stylelint-config/tree/v2.4.0) (2024-01-23)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.3.1...v2.4.0)

### Implemented enhancements

* Bump stylelint-config-recommended-scss to 13.1.0
* Update NPM version to `^10.0.0` to match version used by LTS Node 20

### Fixed bugs

* Fix indention to be consistent

## [v2.3.1](https://github.com/nextcloud/stylelint-config/tree/v2.3.1) (2023-07-11)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.3.0...v2.3.1)

**Implemented enhancements:**

- Bump stylelint and peers ([st3iny](https://github.com/st3iny))

**Changed:**

- Update engines versions to Node 20 with Npm 9 [\#78](https://github.com/nextcloud/stylelint-config/pull/78)

## [v2.3.0](https://github.com/nextcloud/stylelint-config/tree/v2.3.0) (2022-10-14)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.2.0...v2.3.0)

**Implemented enhancements:**

- `::v-deep` is deprecated in favor of `:deep` as of vue 2.7 [\#65](https://github.com/nextcloud/stylelint-config/pull/65) ([susnux](https://github.com/susnux))
- Also allow `:deep` as a pseudo class [\#66](https://github.com/nextcloud/stylelint-config/pull/66) ([susnux](https://github.com/susnux))

## [v2.2.0](https://github.com/nextcloud/stylelint-config/tree/v2.2.0) (2022-08-10)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.1.2...v2.2.0)

**Changed**

- Update engines versions to Node 16 with Npm 7 or Npm 8

## [v2.1.2](https://github.com/nextcloud/stylelint-config/tree/v2.1.2) (2022-01-08)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.1.1...v2.1.2)

**Implemented enhancements:**

- Bump stylelint and associated [\#27](https://github.com/nextcloud/stylelint-config/pull/27) ([skjnldsv](https://github.com/skjnldsv))

## [v2.1.1](https://github.com/nextcloud/stylelint-config/tree/v2.1.1) (2022-01-04)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.1.0...v2.1.1)

**Fixed bugs:**

- Do not lint svg files [\#25](https://github.com/nextcloud/stylelint-config/pull/25) ([skjnldsv](https://github.com/skjnldsv))
- Fix stylelint scss config [\#26](https://github.com/nextcloud/stylelint-config/pull/26) ([skjnldsv](https://github.com/skjnldsv))

## [v2.1.0](https://github.com/nextcloud/stylelint-config/tree/v2.1.0) (2021-11-11)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.0.1...v2.1.0)

**Fixed:**

- Do not lint js and ts files [\#22](https://github.com/nextcloud/stylelint-config/pull/22) ([skjnldsv](https://github.com/skjnldsv))

## [v2.0.1](https://github.com/nextcloud/stylelint-config/tree/v2.0.1) (2021-11-11)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v2.0.0...v2.0.1)

**Fixed:**

- Fix missing stylelint-config-recommended-vue [\#16](https://github.com/nextcloud/stylelint-config/pull/16) ([skjnldsv](https://github.com/skjnldsv))

## [v2.0.0](https://github.com/nextcloud/stylelint-config/tree/v2.0.0) (2021-11-11)

[Full Changelog](https://github.com/nextcloud/stylelint-config/compare/v1.0.0...v2.0.0)

**Merged pull requests:**

- ⚠️ Update stylelint base configs for stylelint 14 [\#14](https://github.com/nextcloud/stylelint-config/pull/14) ([juliushaertl](https://github.com/juliushaertl))
- ⚠️ Bump stylelint to 14.0.1
- Bump stylelint-config-recommended-scss to 5.0.1
- Bump stylelint-scss to 4.0.0

## [v1.0.0](https://github.com/nextcloud/stylelint-config/tree/v1.0.0) (2021-06-11)

Init repo config
