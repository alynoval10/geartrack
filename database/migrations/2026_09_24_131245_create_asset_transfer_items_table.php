<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_transfer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->string('serial_number')->nullable();
            $table->string('condition');
            $table->string('source_location_name')->nullable();
            $table->string('previous_custodian')->nullable();
            $table->timestamps();
            $table->unique(['asset_transfer_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transfer_items');
    }
};
