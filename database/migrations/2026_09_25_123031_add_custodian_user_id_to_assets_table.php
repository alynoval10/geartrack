<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('custodian_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
        $users = DB::table('users')->where('is_active', true)->whereNull('deleted_at')->get(['id', 'name']);
        foreach ($users->groupBy('name') as $name => $matches) {
            if ($matches->count() === 1) {
                DB::table('assets')->where('custodian_name', $name)->update(['custodian_user_id' => $matches->first()->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('custodian_user_id');
        });
    }
};
