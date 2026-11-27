<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 50)->unique();
            $table->string('password');
            $table->boolean('must_change_password')->default(true);
            $table->timestamps();
        });

        $academicYear = DB::table('school_settings')->value('academic_year');

        if (blank($academicYear)) {
            return;
        }

        DB::table('borrowers')
            ->select('nis')
            ->where('type', 'student')
            ->where('is_active', true)
            ->where('academic_year', $academicYear)
            ->whereNotNull('nis')
            ->where('nis', '!=', '')
            ->distinct()
            ->orderBy('nis')
            ->get()
            ->each(function (object $borrower): void {
                DB::table('student_accounts')->insert([
                    'nis' => $borrower->nis,
                    'password' => Hash::make($borrower->nis),
                    'must_change_password' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_accounts');
    }
};
