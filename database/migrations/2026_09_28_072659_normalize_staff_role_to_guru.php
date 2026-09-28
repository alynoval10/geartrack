<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menormalkan instalasi yang sempat memakai nama peran "staff".
        DB::table('users')->where('role', 'staff')->update(['role' => 'guru']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'guru')->update(['role' => 'staff']);
    }
};
