# Release Checklist for Packagist Publication

This checklist will guide you through publishing the Laravel API Controller package to Packagist/Composer.

## Pre-Publication Checklist

### 1. Update Author Information

- [ ] **CRITICAL**: Update email in `composer.json`
  ```json
  "authors": [
      {
          "name": "Masum Nishat",
          "email": "your.real.email@example.com"  // ← Change this!
      }
  ]
  ```

- [ ] Update email in `SECURITY.md` (2 places)
  - Line 5: "please send an email to..."
  - Line 78: "Email: your.real.email@example.com"

- [ ] Update email in `README.md`
  - Line 822: "If you discover a security vulnerability..."

### 2. Verify Package Metadata

- [x] Package name: `masum/laravel-api-controller` ✓
- [x] Description is clear and concise ✓
- [x] Keywords are relevant (21 keywords) ✓
- [x] License is set (MIT) ✓
- [ ] Author email is real (currently placeholder)
- [x] Homepage URL is correct ✓
- [x] Support URLs are correct ✓

### 3. Run Quality Checks

Run these commands to ensure everything works:

```bash
# Install dependencies (if not already done)
composer install

# Run all tests
composer test

# Expected output: 78 tests, all passing
# OK (78 tests, XXX assertions)

# Run static analysis
composer analyse

# Expected output: No errors
# [OK] No errors

# Check code style
composer format-check

# Expected output: No files need formatting
# Checked all files in X.XX seconds

# Run all checks together
composer all-checks
```

- [ ] All 78 tests pass
- [ ] PHPStan analysis passes (level 6)
- [ ] Code style checks pass (PSR-12)

### 4. Verify Documentation

- [ ] README.md is comprehensive and accurate
  - Installation instructions
  - Quick start guide
  - API usage examples
  - Configuration options
  - Security features documented
  - Troubleshooting section
  - Contributing guidelines reference

- [ ] CHANGELOG.md is up to date
  - All phases documented
  - Breaking changes clearly marked
  - Security fixes highlighted

- [ ] CONTRIBUTING.md is complete
  - Development setup
  - Testing guidelines
  - Code quality standards
  - Commit conventions

- [ ] SECURITY.md is complete
  - Security features documented
  - Reporting process clear
  - Contact information updated

- [ ] LICENSE file is present and correct
  - MIT License
  - Copyright year: 2025
  - Copyright holder: Masum

### 5. Version Tagging

- [ ] Update version in CHANGELOG.md
  ```markdown
  ## [1.0.0] - 2025-XX-XX
  ```
  Replace `YYYY-MM-DD` with actual release date

- [ ] Commit all changes
  ```bash
  git add .
  git commit -m "chore: Prepare v1.0.0 release"
  git push
  ```

- [ ] Create and push version tag
  ```bash
  git tag -a v1.0.0 -m "Release v1.0.0: Production-ready with security hardening"
  git push origin v1.0.0
  ```

### 6. GitHub Repository Setup

- [ ] Ensure repository is public (required for Packagist)
- [ ] Add repository description on GitHub
- [ ] Add topics/tags on GitHub:
  - laravel
  - php
  - api
  - rest
  - laravel-package
  - eloquent
  - filtering
  - pagination

- [ ] Enable GitHub Actions
  - tests.yml workflow should run automatically
  - code-quality.yml workflow should run automatically

- [ ] Verify workflows pass
  - Check: https://github.com/masum/laravel-api-controller/actions

### 7. Packagist Submission

#### Option A: Via Packagist Website

1. [ ] Go to https://packagist.org
2. [ ] Sign in with your GitHub account
3. [ ] Click "Submit" in the top navigation
4. [ ] Enter repository URL: `https://github.com/masum/laravel-api-controller`
5. [ ] Click "Check" to validate
6. [ ] Click "Submit" to add package
7. [ ] Wait for initial import (may take a few minutes)

#### Option B: Via GitHub Webhook (Recommended for auto-updates)

1. [ ] Submit package via Packagist website (Option A)
2. [ ] Go to GitHub repository Settings → Webhooks
3. [ ] Click "Add webhook"
4. [ ] Set Payload URL: Get from Packagist package settings
5. [ ] Set Content type: `application/json`
6. [ ] Set events: "Just the push event"
7. [ ] Click "Add webhook"

### 8. Post-Publication Tasks

- [ ] Verify package appears on Packagist
  - URL: https://packagist.org/packages/masum/laravel-api-controller

- [ ] Test installation with Composer
  ```bash
  # In a test Laravel project
  composer require masum/laravel-api-controller

  # Verify it installs successfully
  composer show masum/laravel-api-controller
  ```

- [ ] Update README.md badges (if needed)
  - Packagist badges should work immediately
  - GitHub Actions badges should work if workflows are enabled

- [ ] Verify GitHub Actions workflows
  - Check that tests run on new commits
  - Check that code quality checks run
  - Fix any issues that arise

### 9. Announce the Package (Optional)

- [ ] Submit to Laravel News
  - https://laravel-news.com/submit

- [ ] Share on social media
  - Twitter/X with #Laravel hashtag
  - LinkedIn Laravel groups
  - Reddit /r/laravel

- [ ] Write blog post (optional)
  - Features and benefits
  - Installation guide
  - Common use cases
  - Security features

- [ ] Submit to Awesome Laravel
  - https://github.com/chiraggude/awesome-laravel

## Quick Command Reference

```bash
# Update author email in composer.json (manual edit required)
# Then run:
git add composer.json SECURITY.md README.md
git commit -m "chore: Update author contact information"
git push

# Create release tag
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0

# Run all quality checks
composer all-checks

# Test installation in another project
cd /path/to/test-project
composer require masum/laravel-api-controller
```

## Common Issues and Solutions

### Issue: Packagist says "Could not find package"

**Solution**: Ensure repository is public and composer.json is valid.

```bash
composer validate
```

### Issue: Badge URLs don't work

**Solution**: Wait 5-10 minutes after publication. Shields.io and other badge services need time to cache.

### Issue: GitHub Actions failing

**Solution**: Check workflow logs and ensure all dependencies are available:
- PHP versions: 8.1, 8.2, 8.3
- Laravel versions: 10, 11, 12
- All composer dependencies install successfully

### Issue: Version not updating on Packagist

**Solution**:
1. Check webhook is configured correctly
2. Manually trigger update on Packagist (go to package page, click "Update")
3. Ensure tag was pushed: `git push --tags`

## Support After Publication

After publishing, monitor:
- [ ] GitHub Issues for bug reports
- [ ] GitHub Discussions for questions
- [ ] Packagist download stats
- [ ] GitHub Actions for failing builds

## Version Management

For future releases:

```bash
# Create new version tag
git tag -a v1.1.0 -m "Release v1.1.0: New features and bug fixes"
git push origin v1.1.0

# Packagist will auto-update if webhook is configured
# Otherwise, manually update on Packagist
```

## Checklist Summary

**Before submitting to Packagist:**
- [ ] Update email in 3 files (composer.json, SECURITY.md, README.md)
- [ ] Run `composer all-checks` - all must pass
- [ ] Commit changes and push
- [ ] Create and push v1.0.0 tag
- [ ] Verify GitHub workflows pass

**After submitting to Packagist:**
- [ ] Test installation with `composer require`
- [ ] Configure auto-update webhook
- [ ] Announce package (optional)
- [ ] Monitor issues and discussions

---

**Ready to publish?** Start with step 1: Update the author email in all files!
