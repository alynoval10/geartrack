<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table): void {
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('schedule_due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('maintenance_schedule_id');
            $table->dropColumn('schedule_due_date');
        });
    }
};
