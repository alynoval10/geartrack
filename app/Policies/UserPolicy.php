<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, User $record): bool { return $user->isAdmin(); }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, User $record): bool { return $user->isAdmin() && ! $record->trashed(); }
    public function delete(User $user, User $record): bool { return $user->isAdmin() && $user->id !== $record->id; }
    public function deleteAny(User $user): bool { return false; }
    public function restore(User $user, User $record): bool { return $user->isAdmin(); }
    public function restoreAny(User $user): bool { return false; }
    public function forceDelete(User $user, User $record): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
}
