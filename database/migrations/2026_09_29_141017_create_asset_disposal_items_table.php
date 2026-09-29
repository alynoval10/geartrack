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
        Schema::create('asset_disposal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_disposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->string('serial_number')->nullable();
            $table->string('condition', 30);
            $table->string('previous_status', 30);
            $table->string('location_name')->nullable();
            $table->string('custodian_name')->nullable();
            $table->timestamps();
            $table->index(['asset_disposal_id', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposal_items');
    }
};
