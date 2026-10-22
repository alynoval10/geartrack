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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('funding_source');
            $table->string('invoice_number')->nullable()->after('supplier_name');
            $table->date('warranty_start_date')->nullable()->after('invoice_number');
            $table->date('warranty_end_date')->nullable()->after('warranty_start_date')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['warranty_end_date']);
            $table->dropColumn(['supplier_name', 'invoice_number', 'warranty_start_date', 'warranty_end_date']);
        });
    }
};
