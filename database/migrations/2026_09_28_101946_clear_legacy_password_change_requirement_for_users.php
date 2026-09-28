<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Clear flags inherited from the previous new-user default.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('must_change_password', true)
            ->update(['must_change_password' => false]);
    }

    /**
     * The previous per-user values cannot be reconstructed safely.
     */
    public function down(): void
    {
        // Intentionally left empty.
    }
};
