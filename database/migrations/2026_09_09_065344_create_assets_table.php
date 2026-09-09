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
    Schema::create('assets', function (Blueprint $table) {
        $table->id();

        // Identitas
        $table->string('asset_code')->unique();
        $table->uuid('qr_token')->unique();

        // Data alat
        $table->string('name');
        $table->foreignId('category_id')->constrained()->restrictOnDelete();
        $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
        $table->string('model')->nullable();
        $table->string('serial_number')->nullable();

        // Penempatan
        $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();

        // Kondisi & status
        $table->string('condition')->default('good');
        $table->string('status')->default('available');

        // Pengadaan
        $table->date('acquisition_date')->nullable();
        $table->string('funding_source')->nullable();
        $table->decimal('purchase_price', 15, 2)->nullable();

        // Dokumentasi
        $table->string('photo')->nullable();
        $table->text('notes')->nullable();

        $table->timestamps();

        $table->index('serial_number');
        $table->index(['category_id', 'location_id']);
        $table->index(['condition', 'status']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
