---
name: Bug Report
about: Create a report to help us improve
title: '[BUG] '
labels: bug
assignees: ''
---

## Bug Description
A clear and concise description of what the bug is.

## Steps To Reproduce
Steps to reproduce the behavior:
1. Create a controller extending ApiController with '...'
2. Make a GET request to '...' with parameters '...'
3. Observe error '...'

## Expected Behavior
A clear and concise description of what you expected to happen.

## Actual Behavior
What actually happened instead.

## Code Example
```php
// Your controller code
class ProductController extends ApiController
{
    protected $model = Product::class;
    protected array $searchableFields = ['name'];
    // ...
}

// The request
GET /api/products?search=test
```

## Environment
- **PHP Version**: [e.g., 8.2.1]
- **Laravel Version**: [e.g., 11.0.0]
- **Package Version**: [e.g., 1.0.0]
- **Database**: [e.g., MySQL 8.0]
- **OS**: [e.g., Ubuntu 22.04]

## Stack Trace
If applicable, add the full stack trace:
```
[Stack trace here]
```

## Additional Context
Add any other context about the problem here, such as:
- Does this happen consistently or intermittently?
- Are there any error messages in the logs?
- Have you tried any workarounds?

## Possible Solution
If you have ideas on how to fix this, please share them here.
