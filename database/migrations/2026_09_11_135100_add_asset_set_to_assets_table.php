<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('asset_set_id')
                ->nullable()
                ->after('location_id')
                ->constrained('asset_sets')
                ->nullOnDelete();

            $table->string('set_role')
                ->nullable()
                ->after('asset_set_id');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_set_id']);

            $table->dropColumn([
                'asset_set_id',
                'set_role',
            ]);
        });
    }
};