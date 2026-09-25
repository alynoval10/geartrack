<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('guru');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('session_version')->default(1);
            $table->softDeletes();
        });
        $firstId = DB::table('users')->min('id');
        if ($firstId !== null) {
            DB::table('users')->where('id', $firstId)->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'is_active', 'session_version', 'deleted_at']);
        });
    }
};
