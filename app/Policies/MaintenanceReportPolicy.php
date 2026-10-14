<?php

namespace App\Policies;

use App\Models\MaintenanceReport;
use App\Models\User;

class MaintenanceReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || ($user->is_active && ! $user->trashed());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MaintenanceReport $maintenanceReport): bool
    {
        return $user->isAdmin()
            || ($user->is_active && ! $user->trashed()
                && ($maintenanceReport->reported_by === $user->id
                    || $maintenanceReport->asset?->custodian_user_id === $user->id));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->is_active && ! $user->trashed());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaintenanceReport $maintenanceReport): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaintenanceReport $maintenanceReport): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaintenanceReport $maintenanceReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaintenanceReport $maintenanceReport): bool
    {
        return false;
    }
}
