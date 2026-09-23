<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->foreignId('expected_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('expected_location_name')->nullable();
            $table->string('original_status');
            $table->string('result')->default('pending');
            $table->foreignId('observed_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('observed_location_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['stock_take_id', 'asset_id']);
            $table->index(['stock_take_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
};
