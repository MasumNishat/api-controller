# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-31

### Added
- Initial release
- ApiController base class with dynamic filtering, searching, sorting, and pagination
- Support for relationship searches
- Advanced filtering (array values, ranges, date ranges, boolean, null checks)
- Standardized JSON response format
- ApiResponse, SuccessResponse, and ErrorResponse classes
- Global helper functions for responses
- Configurable pagination and sorting defaults
- SQL error sanitization for security
- Multiple query customization hooks
- Comprehensive documentation and examples
- Laravel 10.x and 11.x support
- Multi-tenancy support with branch filtering helpers
- Helper methods: getUser(), getUserBranchId(), applyBranchFilter(), canAccessBranch()

### Changed
- Completed full migration of Fiber Map v2 project (55 controllers)
- Removed legacy local ApiController in favor of package-based implementation

### Documentation
- Added comprehensive migration guide
- Added troubleshooting section with 7 common issues
- Added branch filtering and multi-tenancy examples
- Added model mismatch handling guide
- Updated README with real-world migration notes