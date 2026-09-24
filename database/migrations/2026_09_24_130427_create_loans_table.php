<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('borrower_name', 150);
            $table->string('borrower_contact', 150)->nullable();
            $table->string('responsible_name', 150);
            $table->text('purpose');
            $table->foreignId('asset_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('package_name')->nullable();
            $table->dateTime('borrowed_at');
            $table->date('due_date');
            $table->string('status')->default('open');
            $table->dateTime('returned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
