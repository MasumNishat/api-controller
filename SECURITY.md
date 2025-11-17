# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability within Laravel API Controller, please send an email to **contact@example.com** (replace with your actual email before publishing). All security vulnerabilities will be promptly addressed.

**Please do not publicly disclose the issue until it has been addressed by the maintainers.**

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Security Features

This package includes several built-in security features:

### SQL Injection Prevention
- **Search queries**: All search terms are sanitized by escaping LIKE wildcards (`%`, `_`, `\`)
- **Sort operations**: Whitelist-based validation ensures only allowed columns can be sorted
- **Parameter binding**: All database queries use Laravel's parameter binding

### Input Validation
- **Max search length**: Search terms are limited to 255 characters
- **Filterable field whitelist**: Only explicitly configured fields can be filtered
- **Sort column whitelist**: Only allowed columns (filterable + searchable + timestamps) can be sorted

### Information Disclosure Prevention
- **SQL error sanitization**: In production, SQL errors are sanitized (20+ patterns detected)
- **Exception handling**: Full errors logged server-side, generic messages shown to users
- **Debug mode aware**: Detailed errors only shown when `APP_DEBUG=true`

### DoS Protection
- **Enforced pagination**: Prevents fetching unlimited records
- **Max pagination limit**: Configurable maximum per-page limit (default: 100)
- **Search term length limit**: Prevents resource exhaustion

### Mass Assignment Protection
- **Fail-secure defaults**: Empty `filterableFields` denies all filtering
- **Explicit whitelisting**: Only listed fields can be filtered

## Secure Configuration

### Recommended Settings

```php
// config/api-controller.php
return [
    // Always sanitize SQL errors in production
    'sanitize_sql_errors' => env('API_SANITIZE_SQL_ERRORS', true),

    // Set reasonable pagination limits
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100, // Don't set too high
    ],
];
```

### Controller Best Practices

```php
class ProductController extends ApiController
{
    protected string $model = Product::class;

    // ALWAYS explicitly define searchable fields
    protected array $searchableFields = ['name', 'sku'];

    // ALWAYS explicitly define filterable fields
    protected array $filterableFields = [
        'status',
        'category_id',
        'created_at',
        'updated_at',
    ];

    // NEVER leave these arrays empty in production
    // Empty arrays will deny all filtering/searching (fail-secure)
}
```

### Environment Variables

```env
# Production settings
APP_ENV=production
APP_DEBUG=false
API_SANITIZE_SQL_ERRORS=true
API_MAX_PER_PAGE=100
```

## Security Audit History

### Version 1.0.0 (2025)
- **SQL Injection in Search**: FIXED - Added wildcard escaping
- **SQL Injection in Sort**: FIXED - Added whitelist validation
- **Information Disclosure**: FIXED - Enhanced error handling
- **DoS via Pagination**: FIXED - Enforced pagination limits
- **Mass Assignment**: FIXED - Secure default behavior

## Bug Bounty Program

We do not currently have a bug bounty program. However, we appreciate responsible disclosure and will acknowledge security researchers in our release notes.

## Hall of Fame

Security researchers who have responsibly disclosed vulnerabilities:

- *None yet*

## Contact

For security issues, please contact:
- **Email**: contact@example.com (update this before publishing)
- **Response Time**: We aim to respond within 48 hours
- **Fix Timeline**: Critical issues will be patched within 7 days

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
