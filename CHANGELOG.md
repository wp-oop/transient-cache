# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD

## [0.1.0-alpha3] - 2020-07-08
### Added
- Missing function imports.

### Changed
- (#13) Improved exception messages

## [0.1.0-alpha2] - 2020-04-19
### Fixed
- False-negative check used to confirm the negative because of wrong option name.
- Non-getter interface methods of `CachePool` now return `true` on success.
- Non-getter interface methods of `CachePool` now declare and throw proper exceptions.

### Added
- `SilentPool`, which wraps cache pools that throw non-PSR-16 exceptions and suppress them, making the pool compatible (#7).
- Missing documentation.

### Changed
- Centralized behaviour like option deletion, and option/transient retrieval/assignment.

## [0.1.0-alpha1] - 2020-04-14
Initial version.
