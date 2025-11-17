<?php

namespace Masum\ApiController\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasBranchFiltering
 *
 * Provides multi-tenancy support through branch-based filtering.
 * This trait is OPTIONAL and should only be used if your application
 * has a branch-based multi-tenancy model.
 *
 * Example usage:
 * ```php
 * use Masum\ApiController\Controllers\ApiController;
 * use Masum\ApiController\Traits\HasBranchFiltering;
 *
 * class ProductController extends ApiController
 * {
 *     use HasBranchFiltering;
 *
 *     protected function getBaseIndexQuery(Request $request): Builder
 *     {
 *         $query = parent::getBaseIndexQuery($request);
 *         return $this->applyBranchFilter($query);
 *     }
 * }
 * ```
 *
 * @package Masum\ApiController\Traits
 */
trait HasBranchFiltering
{
    /**
     * Apply branch filter to query for multi-tenancy support.
     *
     * Automatically filters records by the authenticated user's branch ID.
     * Super admins are exempt from branch filtering.
     *
     * @param Builder $query The query builder instance
     * @param string $branchColumn The column name for branch ID (default: 'branch_id')
     * @return Builder The filtered query builder
     *
     * @example
     * ```php
     * // Filter by default branch_id column
     * $query = $this->applyBranchFilter($query);
     *
     * // Filter by custom column
     * $query = $this->applyBranchFilter($query, 'company_branch_id');
     * ```
     */
    protected function applyBranchFilter(Builder $query, string $branchColumn = 'branch_id'): Builder
    {
        $user = $this->getUser();

        // If user is not super admin, filter by their branch
        if ($user && method_exists($user, 'isSuperAdmin')) {
            try {
                if (!$user->isSuperAdmin()) {
                    $branchId = $this->getUserBranchId();
                    if ($branchId) {
                        $query->where($branchColumn, $branchId);
                    }
                }
            } catch (\Exception $e) {
                // If there's an error checking super admin status, apply branch filter as a safety measure
                $branchId = $this->getUserBranchId();
                if ($branchId) {
                    $query->where($branchColumn, $branchId);
                }
            }
        }

        return $query;
    }

    /**
     * Check if the authenticated user can access a specific branch.
     *
     * Super admins can access all branches.
     * Regular users can only access their own branch.
     *
     * @param int|null $branchId The branch ID to check access for
     * @return bool True if user can access the branch, false otherwise
     *
     * @example
     * ```php
     * if (!$this->canAccessBranch($request->input('branch_id'))) {
     *     return $this->forbidden('You cannot access this branch');
     * }
     * ```
     */
    protected function canAccessBranch(?int $branchId): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        // Check if user is super admin
        if (method_exists($user, 'isSuperAdmin')) {
            try {
                if ($user->isSuperAdmin()) {
                    return true;
                }
            } catch (\Exception $e) {
                // If error checking super admin, fall through to branch check
            }
        }

        // Check if the branch ID matches user's branch
        return $this->getUserBranchId() === $branchId;
    }

    /**
     * Get the authenticated user's branch ID.
     *
     * This method assumes the user model has an 'employee' relationship
     * with a 'branch_id' field. Override this method if your structure differs.
     *
     * @return int|null The user's branch ID, or null if not found
     *
     * @example
     * ```php
     * $branchId = $this->getUserBranchId();
     * if ($branchId) {
     *     $query->where('branch_id', $branchId);
     * }
     * ```
     */
    protected function getUserBranchId(): ?int
    {
        $user = $this->getUser();

        if (!$user) {
            return null;
        }

        // Assumes user->employee->branch_id structure
        // Override this method if your structure is different
        return $user->employee->branch_id ?? null;
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
