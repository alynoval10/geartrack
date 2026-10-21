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
        Schema::table('loans', function (Blueprint $table) {
            // Keep this as an indexed reference without a database foreign key.
            // SQLite rebuilds existing tables when adding foreign keys, which
            // risks losing the rest of the loans table during this migration.
            $table->foreignId('borrower_id')->nullable()->after('borrower_contact')->index();
            $table->string('borrower_type', 20)->nullable()->after('borrower_id');
            $table->string('borrower_class', 100)->nullable()->after('borrower_type');
            $table->string('borrower_academic_year', 30)->nullable()->after('borrower_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['borrower_id']);
            $table->dropColumn(['borrower_id', 'borrower_type', 'borrower_class', 'borrower_academic_year']);
        });
    }
};
