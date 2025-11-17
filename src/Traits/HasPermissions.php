<?php

namespace Masum\ApiController\Traits;

/**
 * Trait HasPermissions
 *
 * Provides helper methods for checking user permissions and roles.
 * This trait is OPTIONAL and should only be used if your application
 * has a custom permission system.
 *
 * Example usage:
 * ```php
 * use Masum\ApiController\Controllers\ApiController;
 * use Masum\ApiController\Traits\HasPermissions;
 *
 * class ProductController extends ApiController
 * {
 *     use HasPermissions;
 *
 *     public function store(Request $request): JsonResponse
 *     {
 *         if (!$this->hasPermission('create', 'products')) {
 *             return $this->forbidden('You do not have permission to create products');
 *         }
 *
 *         // Your logic here...
 *     }
 * }
 * ```
 *
 * @package Masum\ApiController\Traits
 */
trait HasPermissions
{
    /**
     * Check if the authenticated user has a specific permission.
     *
     * This method assumes your User model has a hasPermission() method.
     * Override this method if your permission check logic differs.
     *
     * @param string $action The action to check (e.g., 'create', 'update', 'delete')
     * @param string $resource The resource to check (e.g., 'products', 'users')
     * @return bool True if user has permission, false otherwise
     *
     * @example
     * ```php
     * if ($this->hasPermission('delete', 'products')) {
     *     // User can delete products
     * }
     * ```
     */
    protected function hasPermission(string $action, string $resource): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        // Assumes user model has hasPermission method
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($action, $resource);
        }

        return false;
    }

    /**
     * Check if the authenticated user is a super administrator.
     *
     * This method assumes your User model has an isSuperAdmin() method.
     * Override this method if your admin check logic differs.
     *
     * @return bool True if user is super admin, false otherwise
     *
     * @example
     * ```php
     * if ($this->isSuperAdmin()) {
     *     // Grant full access
     * }
     * ```
     */
    protected function isSuperAdmin(): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        // Assumes user model has isSuperAdmin method
        if (method_exists($user, 'isSuperAdmin')) {
            try {
                return $user->isSuperAdmin();
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if the authenticated user has any of the specified roles.
     *
     * This method assumes your User model has a hasRole() or hasAnyRole() method.
     * Override this method if your role check logic differs.
     *
     * @param string|array $roles Single role or array of roles
     * @return bool True if user has any of the roles, false otherwise
     *
     * @example
     * ```php
     * if ($this->hasRole(['admin', 'manager'])) {
     *     // User is either admin or manager
     * }
     * ```
     */
    protected function hasRole(string|array $roles): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        // Try hasAnyRole method first (common in permission packages)
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($roles);
        }

        // Try hasRole method
        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the authenticated user has all of the specified roles.
     *
     * @param array $roles Array of roles to check
     * @return bool True if user has all roles, false otherwise
     *
     * @example
     * ```php
     * if ($this->hasAllRoles(['admin', 'developer'])) {
     *     // User has both admin AND developer roles
     * }
     * ```
     */
    protected function hasAllRoles(array $roles): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        // Try hasAllRoles method first
        if (method_exists($user, 'hasAllRoles')) {
            return $user->hasAllRoles($roles);
        }

        // Manual check
        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if (!$user->hasRole($role)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Get the authenticated user.
     *
     * This method must be defined in the class using this trait.
     * Typically provided by ApiController.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    abstract protected function getUser();
}
