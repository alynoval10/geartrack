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
        Schema::create('student_loan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('borrower_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('borrower_name', 150);
            $table->string('borrower_nis', 50);
            $table->string('borrower_class', 120);
            $table->string('academic_year', 30);
            $table->text('purpose');
            $table->date('due_date');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('student_loan_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_loan_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_code', 100);
            $table->string('asset_name', 150);
            $table->timestamps();
            $table->unique(['student_loan_request_id', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_loan_request_items');
        Schema::dropIfExists('student_loan_requests');
    }
};
