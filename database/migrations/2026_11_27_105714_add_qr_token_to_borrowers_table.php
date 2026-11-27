<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->string('qr_token', 36)->nullable();
        });

        DB::table('borrowers')
            ->where('type', 'student')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->chunkById(500, function (Collection $borrowers): void {
                foreach ($borrowers as $borrower) {
                    DB::table('borrowers')
                        ->where('id', $borrower->id)
                        ->update(['qr_token' => (string) Str::uuid()]);
                }
            });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->unique('qr_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
