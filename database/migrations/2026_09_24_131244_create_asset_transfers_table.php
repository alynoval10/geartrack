<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('asset_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('package_name')->nullable();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('destination_location_name');
            $table->string('sender_name', 150);
            $table->string('receiver_name', 150);
            $table->text('reason');
            $table->dateTime('transferred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};
