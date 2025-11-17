# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive security hardening
- Complete test suite with 80%+ code coverage
- PHPDoc comments for all methods
- PHPStan static analysis support
- PHP-CS-Fixer code style enforcement
- GitHub Actions CI/CD pipeline
- Type hints throughout the codebase
- Constants for magic strings
- Input validation layer
- Sort column validation
- Extended query operators support
- Optional traits for business logic (HasBranchFiltering, HasPermissions)
- Caching layer support
- Logging and monitoring capabilities
- Rate limiting middleware
- Request validation middleware
- Laravel Resource transformation support
- Sparse fieldsets (field filtering)
- Dynamic relationship inclusion
- CONTRIBUTING.md guide
- SECURITY.md policy
- UPGRADE.md guide
- Issue and PR templates
- Comprehensive documentation

### Changed
- **BREAKING**: Default pagination behavior - now always paginates by default
- **BREAKING**: Empty filterableFields now denies all filters instead of allowing all
- **BREAKING**: Empty string no longer treated as NULL in filters
- Improved SQL error sanitization with broader pattern matching
- Enhanced exception handling - errors logged, generic messages returned in production
- Boolean filters now recognize '1', '0', 1, 0 in addition to 'true', 'false'
- Range filters now support partial ranges (min only or max only)
- Date filters now validate date format before querying
- Configuration values now actually used in ApiController
- Timestamp format now respects configuration settings
- SQL injection vulnerabilities fixed in search and sort operations
- Mass assignment protection enhanced

### Fixed
- SQL injection vulnerability in search functionality
- SQL injection vulnerability in sort functionality
- Information disclosure through exception messages
- Default pagination returning all records
- Boolean filter not recognizing numeric values
- Range filter requiring both min and max
- Date filter not validating input
- Empty filterableFields allowing all filters
- Configuration options not being used
- Timestamp format configuration being ignored
- Relationship search depth not validated

### Security
- Fixed SQL injection in search queries
- Fixed SQL injection in sort queries
- Added input validation for all query parameters
- Enhanced error message sanitization
- Added sort column whitelisting
- Added filter field whitelisting
- Improved exception handling to prevent information disclosure

### Deprecated
- Direct use of business logic methods (moved to optional traits)
  - `applyBranchFilter()` - use `HasBranchFiltering` trait
  - `canAccessBranch()` - use `HasBranchFiltering` trait
  - `getUserBranchId()` - use `HasBranchFiltering` trait
  - `hasPermission()` - use `HasPermissions` trait
  - `isSuperAdmin()` - use `HasPermissions` trait

## [1.0.0] - YYYY-MM-DD (Not Released)

### Added
- Initial release
- ApiController base class with filtering, searching, sorting, pagination
- Standardized JSON response format
- Response helper methods
- Global helper functions
- Configuration file support
- Multi-tenancy helpers
- Laravel 10.x, 11.x, 12.x support
- PHP 8.1, 8.2, 8.3 support

[Unreleased]: https://github.com/masum/laravel-api-controller/compare/v1.0.0...HEAD
