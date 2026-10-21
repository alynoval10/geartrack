<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrowers', function (Blueprint $table): void {
            $table->string('nis', 50)->nullable();
            $table->string('class_level', 10)->nullable()->after('class_name');
            $table->unique(['nis', 'academic_year']);
        });

        DB::table('borrowers')
            ->select(['id', 'class_name'])
            ->where('type', 'student')
            ->whereNotNull('class_name')
            ->orderBy('id')
            ->chunkById(500, function (Collection $borrowers): void {
                foreach ($borrowers as $borrower) {
                    if (preg_match('/^(XII|XI|X)\s+/i', $borrower->class_name, $matches) === 1) {
                        DB::table('borrowers')->where('id', $borrower->id)->update([
                            'class_level' => strtoupper($matches[1]),
                            'class_name' => preg_replace('/^(XII|XI|X)\s+/i', '', $borrower->class_name),
                        ]);
                    }
                }
            });

        Schema::table('loans', function (Blueprint $table): void {
            $table->string('borrower_nis', 50)->nullable()->after('borrower_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('borrowers')
            ->select(['id', 'class_level', 'class_name'])
            ->where('type', 'student')
            ->whereNotNull('class_level')
            ->whereNotNull('class_name')
            ->orderBy('id')
            ->chunkById(500, function (Collection $borrowers): void {
                foreach ($borrowers as $borrower) {
                    DB::table('borrowers')->where('id', $borrower->id)->update([
                        'class_name' => trim($borrower->class_level.' '.$borrower->class_name),
                    ]);
                }
            });

        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn('borrower_nis');
        });

        Schema::table('borrowers', function (Blueprint $table): void {
            $table->dropUnique(['nis', 'academic_year']);
            $table->dropColumn(['nis', 'class_level']);
        });
    }
};
