<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function save(array $data, ?User $record = null): User
    {
        Gate::authorize($record ? 'update' : 'create', $record ?? User::class);
        return DB::transaction(function () use ($data, $record): User {
            $record = $record ? User::lockForUpdate()->findOrFail($record->id) : new User;
            $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
            $data = Validator::make($data, [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($record->id)],
                'role' => ['required', Rule::in(array_keys(User::ROLES))],
                'is_active' => ['required', 'boolean'],
                'password' => [$record->exists ? 'nullable' : 'required', 'string', 'min:8', 'max:255', 'confirmed'],
            ])->validate();
            if (empty($data['password'])) {
                unset($data['password']);
            }
            if ($record->exists && $record->id === auth()->id() && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat menonaktifkan atau menurunkan peran akun sendiri.']);
            }
            if ($record->isAdmin() && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                $this->ensureOtherAdmin($record);
            }
            $record->fill($data);
            $revoke = $record->exists && $record->isDirty(['password', 'role', 'is_active']);
            if ($revoke) {
                $record->session_version++;
                $record->remember_token = Str::random(60);
            }
            $record->save();
            Asset::where('custodian_user_id', $record->id)->update(['custodian_name' => $record->name]);
            if ($revoke) {
                $this->clearSessions($record);
            }
            return $record;
        });
    }

    public function delete(User $record): bool
    {
        Gate::authorize('delete', $record);
        return DB::transaction(function () use ($record): bool {
            $record = User::lockForUpdate()->findOrFail($record->id);
            if ($record->isAdmin()) {
                $this->ensureOtherAdmin($record);
            }
            $record->forceFill(['is_active' => false, 'remember_token' => Str::random(60), 'session_version' => $record->session_version + 1])->save();
            $record->delete();
            $this->clearSessions($record);
            return true;
        });
    }

    public function restore(User $record): bool
    {
        Gate::authorize('restore', $record);
        $record->restore();
        return true;
    }

    private function ensureOtherAdmin(User $record): void
    {
        if (! User::where('role', 'admin')->where('is_active', true)->whereKeyNot($record->id)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['role' => 'Harus ada minimal satu admin aktif.']);
        }
    }

    private function clearSessions(User $record): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $record->id)->delete();
        }
    }
}
