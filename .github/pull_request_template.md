# Pull Request

## Description
<!-- Provide a clear and concise description of your changes -->

## Type of Change
<!-- Mark the relevant option with an "x" -->
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Code refactoring
- [ ] Performance improvement
- [ ] Test improvement

## Related Issue
<!-- Link to the issue this PR addresses -->
Fixes #(issue number)
Closes #(issue number)

## Changes Made
<!-- Describe the changes in detail -->
- Change 1
- Change 2
- Change 3

## Testing
<!-- Describe the tests you ran to verify your changes -->

### Test Cases Added
- [ ] Unit tests added
- [ ] Feature tests added
- [ ] Security tests added (if applicable)

### Manual Testing
```bash
# Commands used for manual testing
composer test
composer analyse
composer format-check
```

### Test Results
```
# Paste relevant test output here
```

## Breaking Changes
<!-- If this is a breaking change, describe the impact and migration path -->
- [ ] This PR introduces breaking changes
- [ ] Migration guide added to UPGRADE.md
- [ ] CHANGELOG.md updated with breaking changes section

**Breaking Changes Description:**
<!-- Describe what breaks and how users should adapt -->

## Checklist
<!-- Ensure all items are checked before submitting -->
- [ ] My code follows the PSR-12 coding standards
- [ ] I have run `composer all-checks` and all checks pass
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] All new and existing tests pass (`composer test`)
- [ ] PHPStan analysis passes (`composer analyse`)
- [ ] Code style is correct (`composer format-check`)
- [ ] I have added PHPDoc comments for new methods
- [ ] I have updated the documentation (README.md) if needed
- [ ] I have updated CHANGELOG.md in the "Unreleased" section
- [ ] My commits follow the conventional commit format
- [ ] I have added examples in PHPDoc if introducing new features

## Screenshots / Examples
<!-- If applicable, add screenshots or code examples -->

### Before
```php
// Code before your changes
```

### After
```php
// Code after your changes
```

## Performance Impact
<!-- If applicable, describe any performance implications -->
- [ ] No performance impact
- [ ] Performance improved
- [ ] Performance degraded (explain why this trade-off is acceptable)

**Performance Notes:**
<!-- Add benchmarks or profiling results if relevant -->

## Additional Notes
<!-- Any additional information that reviewers should know -->

## Reviewer Notes
<!-- Optional: specific areas where you'd like feedback -->
- [ ] Please review the security implications
- [ ] Please check the test coverage
- [ ] Please verify the documentation is clear
- [ ] Please validate the breaking changes are necessary

---

**For Maintainers:**
- [ ] Code review completed
- [ ] Tests verified
- [ ] Documentation reviewed
- [ ] Ready to merge
