<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('active_asset_id')->nullable()->unique()->constrained('assets')->restrictOnDelete();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->string('condition_before');
            $table->string('previous_status');
            $table->string('condition_after')->nullable();
            $table->text('return_notes')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['loan_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};
