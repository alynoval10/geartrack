<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_specifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('key');
            $table->string('label');
            $table->text('value')->nullable();

            $table->unsignedInteger('sort')->default(0);

            $table->timestamps();

            $table->unique([
                'asset_id',
                'key',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_specifications');
    }
};