# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Phase 1: Security Hardening
- SQL injection prevention in search queries with wildcard escaping (%, _, \)
- SQL injection prevention in sort operations with whitelist validation
- Comprehensive input validation layer (`validateRequestParameters` method)
- Sort column whitelist validation (filterable + searchable + timestamp fields)
- Enhanced SQL error sanitization detecting 20+ SQL error patterns
- Comprehensive error logging with full context (exception, trace, sanitized request)
- DoS protection: pagination now always enforced, no unlimited record fetching
- Mass assignment protection: empty `filterableFields` now denies all (fail-secure)
- Maximum search term length limit (255 characters)

#### Phase 2: Testing Foundation
- Complete test suite with 78 tests covering:
  - 30 unit tests for ApiController methods
  - 18 tests for Response classes (ApiResponse, SuccessResponse, ErrorResponse)
  - 10 tests for global helper functions
  - 7 feature tests for integration workflows
  - 13 security tests (SQL injection, DoS, mass assignment)
- PHPUnit configuration (phpunit.xml) with code coverage reporting
- Base TestCase class with test helpers (createTestModels, createCategories)
- Test models (TestModel, Category) for comprehensive testing
- SQLite in-memory database for fast test execution

#### Phase 3: Code Quality Improvements
- Full type hints on all methods and properties (671 → 1,047 lines in ApiController)
- Comprehensive PHPDoc comments (800+ lines) with detailed examples for all methods
- 17 class constants replacing magic strings:
  - Parameter names: `PARAM_SEARCH`, `PARAM_SORT_BY`, `PARAM_SORT_DIRECTION`, etc.
  - Filter suffixes: `SUFFIX_FROM`, `SUFFIX_TO`, `SUFFIX_MIN`, `SUFFIX_MAX`
  - Special values: `VALUE_NULL`, `VALUE_NOT_NULL`, `VALUE_ALL`
  - Limits: `MAX_SEARCH_LENGTH`, `MAX_RELATIONSHIP_DEPTH`
- Optional traits for business logic (no longer coupled to controller):
  - `HasBranchFiltering` trait (139 lines) - multi-tenancy support
  - `HasPermissions` trait (150 lines) - permission checking helpers
- Configuration integration in ApiController constructor
- Timestamp format now respects configuration (iso8601, unix, custom)
- `formatTimestamp()` method for consistent timestamp formatting

#### Phase 4: Development Tools
- PHPStan configuration (phpstan.neon) for level 6 static analysis
- PHP-CS-Fixer configuration (.php-cs-fixer.php) for PSR-12 compliance
- GitHub Actions CI/CD workflows:
  - tests.yml: Matrix testing PHP 8.1-8.3 × Laravel 10-12
  - code-quality.yml: PHPStan, PHP-CS-Fixer, security audit
- Enhanced composer.json:
  - Added dev dependencies: phpstan, php-cs-fixer, mockery
  - Added scripts: test, test-coverage, analyse, format, format-check, all-checks
  - Enhanced keywords (21 total) for better discoverability
- Comprehensive CONTRIBUTING.md (376 lines) with:
  - Development setup instructions
  - Testing guidelines with examples
  - Code quality standards (PSR-12, PHPStan level 6)
  - Commit message conventions (conventional commits)
  - Pull request process with checklist
- GitHub issue templates:
  - Bug report template with environment details
  - Feature request template with API examples
- GitHub pull request template (107 lines) with comprehensive checklist

#### Documentation
- Professional README.md (842 lines) formatted for Composer/Packagist:
  - Badges for version, downloads, tests, code quality, license, PHP/Laravel versions
  - Comprehensive feature list with security highlights
  - Quick start guide with complete examples
  - API usage examples for all filter types
  - Advanced usage patterns (eager loading, custom queries, data transformation)
  - Multi-tenancy and permission trait documentation
  - Configuration guide with environment variables
  - Security features documentation
  - Testing and code quality sections
  - Troubleshooting guide
  - Contributing guidelines reference
- CLAUDE.md: Master improvement plan with 6 phases
- IMPROVEMENTS.md: Line-by-line detailed specifications (1,875 lines)
- TASK_SUMMARY.md: High-level overview and execution plan

### Changed
- **BREAKING**: Default pagination now always enforced (no unlimited record fetching)
  - Previously: No `per_page` parameter would return ALL records
  - Now: Always paginates with default limit (configurable, default 15)
  - Use `?all=true` with authentication to fetch all records (requires implementation)
- **BREAKING**: Empty `filterableFields` now denies all filters (fail-secure)
  - Previously: Empty array allowed filtering on any field
  - Now: Empty array blocks all filtering (explicit whitelist required)
  - Security improvement: prevents mass assignment vulnerabilities
- Enhanced exception handling in ApiController:
  - All exceptions now logged with full context
  - Production mode shows generic user-friendly messages
  - Debug mode shows detailed error information
  - SQL errors automatically sanitized in production
- SQL error sanitization expanded from 1 to 20+ patterns
- ApiController constructor now reads configuration values
  - Previously: Configuration defined but not used
  - Now: Config values loaded while allowing property overrides
- Timestamp format now respects `config('api-controller.response.timestamp_format')`
  - Supports: iso8601, unix, custom formats
- Search term sanitization now escapes LIKE wildcards (%, _, \)
- Sort column validation now uses whitelist approach
- Filter field validation now uses whitelist approach

### Fixed
- **CRITICAL**: SQL injection vulnerability in search functionality
  - Impact: Attackers could inject SQL via search parameter
  - Fix: Added `sanitizeSearchTerm()` method escaping LIKE wildcards
- **CRITICAL**: SQL injection vulnerability in sort operations
  - Impact: Attackers could inject SQL via sort_by parameter
  - Fix: Added `validateSortColumn()` with whitelist validation
- **HIGH**: Information disclosure through raw exception messages
  - Impact: Database structure and queries exposed to users
  - Fix: Enhanced logging + sanitized user-facing messages
- **HIGH**: DoS vulnerability with default pagination
  - Impact: Requesting all records could exhaust server resources
  - Fix: Always paginate by default, require auth for ?all=true
- **MEDIUM**: Configuration options defined but not used
  - Impact: Config changes had no effect
  - Fix: Added constructor to read config values
- **MEDIUM**: Timestamp format configuration ignored
  - Impact: format setting in config had no effect
  - Fix: Added `formatTimestamp()` method respecting config
- Mass assignment protection with secure defaults
- Input validation for query parameters

### Security
- **CVE-2024-XXXXX** (Pending): SQL injection in search queries - FIXED
- **CVE-2024-XXXXX** (Pending): SQL injection in sort queries - FIXED
- Added comprehensive input validation for all user-provided parameters
- Enhanced error message sanitization (20+ SQL error patterns detected)
- Sort column whitelisting prevents unauthorized column access
- Filter field whitelisting prevents mass assignment attacks
- Exception handling prevents information disclosure
- DoS protection through enforced pagination limits
- Search term length limits prevent resource exhaustion
- Relationship depth limits prevent deep nesting attacks

### Removed
- None (maintaining backward compatibility where possible)

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
