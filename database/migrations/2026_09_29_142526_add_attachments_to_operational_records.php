<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['asset_disposals', 'asset_transfers', 'maintenance_reports', 'maintenance_entries', 'stock_take_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->json('attachments')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['asset_disposals', 'asset_transfers', 'maintenance_reports', 'maintenance_entries', 'stock_take_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('attachments');
            });
        }
    }
};
