# Contributing to Laravel API Controller

First off, thank you for considering contributing to Laravel API Controller! It's people like you that make this package better for everyone.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the issue tracker as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* **Use a clear and descriptive title** for the issue
* **Describe the exact steps to reproduce the problem**
* **Provide specific examples** to demonstrate the steps
* **Describe the behavior you observed** and what you expected to see
* **Include your environment details**: PHP version, Laravel version, package version

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* **Use a clear and descriptive title**
* **Provide a step-by-step description** of the suggested enhancement
* **Provide specific examples** to demonstrate the enhancement
* **Explain why this enhancement would be useful** to most users

### Pull Requests

* Fill in the required template
* Follow the coding standards (PSR-12)
* Include tests for your changes
* Update documentation as needed
* End files with a newline

## Development Setup

### Prerequisites

* PHP 8.1, 8.2, or 8.3
* Composer
* Git

### Setting Up Your Development Environment

1. **Fork the repository** on GitHub

2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR-USERNAME/laravel-api-controller.git
   cd laravel-api-controller
   ```

3. **Install dependencies**:
   ```bash
   composer install
   ```

4. **Create a branch** for your changes:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Running Tests

We use PHPUnit for testing. Run the test suite with:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

### Writing Tests

* Write tests for all new features
* Write tests for bug fixes to prevent regressions
* Aim for high code coverage (80%+)
* Place unit tests in `tests/Unit/`
* Place feature tests in `tests/Feature/`

Example test structure:

```php
<?php

namespace Masum\ApiController\Tests\Unit;

use Masum\ApiController\Tests\TestCase;

class YourFeatureTest extends TestCase
{
    /** @test */
    public function it_does_something()
    {
        // Arrange
        $this->createTestModels(5);

        // Act
        $result = $this->controller->someMethod();

        // Assert
        $this->assertTrue($result);
    }
}
```

## Code Quality

### Running Code Analysis

We use PHPStan for static analysis:

```bash
composer analyse
```

### Code Style

We follow PSR-12 coding standards and use PHP-CS-Fixer:

Check code style:
```bash
composer format-check
```

Fix code style automatically:
```bash
composer format
```

### Run All Checks

Before submitting a PR, run all checks:

```bash
composer all-checks
```

## Coding Standards

### PHP Standards

* Follow **PSR-12** coding standards
* Use **type hints** for all method parameters and return types
* Add **PHPDoc blocks** for all methods with descriptions and examples
* Use **meaningful variable and method names**
* Keep methods small and focused (Single Responsibility Principle)

### Security

* **Never** trust user input - always validate and sanitize
* Use **parameter binding** for SQL queries (Laravel's query builder does this automatically)
* **Whitelist** allowed values instead of blacklisting
* Add **security tests** for any input handling code

### Testing

* Write tests **before** or **alongside** your code (TDD/BDD)
* Test **happy paths** and **edge cases**
* Test **error handling**
* Use **descriptive test names** (`it_filters_by_status_correctly`)
* Follow **Arrange-Act-Assert** pattern

### Documentation

* Update **README.md** if adding new features
* Add **PHPDoc comments** with:
  * Description
  * `@param` tags for all parameters
  * `@return` tag
  * `@throws` tag if applicable
  * `@example` tag with usage examples
* Update **CHANGELOG.md** following [Keep a Changelog](https://keepachangelog.com/)

## Commit Message Guidelines

We follow conventional commit messages for clarity and automated changelog generation:

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

* **feat**: A new feature
* **fix**: A bug fix
* **docs**: Documentation only changes
* **style**: Code style changes (formatting, missing semicolons, etc.)
* **refactor**: Code change that neither fixes a bug nor adds a feature
* **perf**: Performance improvements
* **test**: Adding or updating tests
* **chore**: Changes to build process or auxiliary tools

### Examples

```
feat(filter): add support for NOT IN operator

Adds support for filtering with NOT IN operator using
the nin suffix (e.g., ?status[nin]=pending,rejected).

Closes #123
```

```
fix(search): prevent SQL injection in relationship search

Sanitizes search terms before applying to relationship queries.
Added additional test cases for SQL injection attempts.

Fixes #456
```

```
docs(readme): update installation instructions

Added troubleshooting section for common installation issues.
```

## Pull Request Process

1. **Update documentation** if you're changing functionality
2. **Add tests** for your changes
3. **Run all checks**: `composer all-checks`
4. **Update CHANGELOG.md** in the "Unreleased" section
5. **Create the Pull Request** with a clear title and description
6. **Link related issues** using "Fixes #123" or "Closes #456"
7. **Respond to feedback** from maintainers

### PR Checklist

Before submitting, ensure:

- [ ] Code follows PSR-12 standards
- [ ] All tests pass (`composer test`)
- [ ] PHPStan analysis passes (`composer analyse`)
- [ ] Code style is correct (`composer format-check`)
- [ ] New features have tests
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated
- [ ] Commit messages follow conventions
- [ ] No merge conflicts

## Project Structure

```
laravel-api-controller/
├── .github/
│   ├── workflows/          # GitHub Actions CI/CD
│   └── ISSUE_TEMPLATE/     # Issue templates
├── src/
│   ├── Controllers/        # Base ApiController
│   ├── Responses/          # Response classes
│   ├── Traits/             # Optional traits
│   ├── config/             # Configuration file
│   └── helpers.php         # Global helper functions
├── tests/
│   ├── Unit/               # Unit tests
│   ├── Feature/            # Feature tests
│   └── TestCase.php        # Base test class
├── composer.json
├── phpunit.xml             # PHPUnit configuration
├── phpstan.neon            # PHPStan configuration
└── .php-cs-fixer.php       # Code style configuration
```

## Need Help?

* **Documentation**: Check the [README.md](README.md)
* **Issues**: Browse [existing issues](https://github.com/masum/laravel-api-controller/issues)
* **Discussions**: Start a [discussion](https://github.com/masum/laravel-api-controller/discussions)

## Recognition

Contributors will be recognized in:
* The project's README
* Release notes
* The CHANGELOG

Thank you for contributing! 🎉
