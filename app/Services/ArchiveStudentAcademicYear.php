<?php

namespace App\Services;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveStudentAcademicYear
{
    /**
     * Archive students from one school year and prepare active records for the current year.
     *
     * @return array{archived: int, promoted: int, graduated: int, updated: int}
     */
    public function handle(string $sourceAcademicYear): array
    {
        return DB::transaction(function () use ($sourceAcademicYear): array {
            $targetAcademicYear = SchoolSetting::query()->lockForUpdate()->value('academic_year');

            if (blank($targetAcademicYear)) {
                throw ValidationException::withMessages([
                    'source_academic_year' => 'Atur tahun ajaran aktif di Pengaturan Sekolah terlebih dahulu.',
                ]);
            }

            if ($sourceAcademicYear === $targetAcademicYear) {
                throw ValidationException::withMessages([
                    'source_academic_year' => 'Tahun ajaran sumber dan tujuan harus berbeda.',
                ]);
            }

            $students = Borrower::query()
                ->where('type', 'student')
                ->where('academic_year', $sourceAcademicYear)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($students->isEmpty()) {
                throw ValidationException::withMessages([
                    'source_academic_year' => 'Tidak ada siswa aktif pada tahun ajaran tersebut.',
                ]);
            }

            $invalidStudents = $students->filter(fn (Borrower $student): bool => blank($student->nis)
                || blank($student->class_name)
                || ! array_key_exists($student->class_level ?? '', Borrower::LEVELS));

            if ($invalidStudents->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'source_academic_year' => $invalidStudents->count().' siswa belum memiliki NIS, tingkat, atau kelas yang valid. Lengkapi data tersebut sebelum mengarsipkan tahun ajaran.',
                ]);
            }

            $targetStudents = Borrower::query()
                ->where('type', 'student')
                ->where('academic_year', $targetAcademicYear)
                ->whereIn('nis', $students->pluck('nis'))
                ->get()
                ->keyBy('nis');

            $promoted = 0;
            $graduated = 0;
            $updated = 0;

            foreach ($students as $student) {
                $student->update(['is_active' => false]);

                if ($student->class_level === 'XII') {
                    $graduated++;

                    continue;
                }

                $nextLevel = $student->class_level === 'X' ? 'XI' : 'XII';
                $nextYearStudent = $targetStudents->get($student->nis);

                if ($nextYearStudent instanceof Borrower) {
                    $nextYearStudent->update([
                        'name' => $student->name,
                        'class_level' => $nextLevel,
                        'class_name' => $student->class_name,
                        'contact' => $student->contact,
                        'is_active' => true,
                    ]);
                    $updated++;
                } else {
                    $nextYearStudent = Borrower::query()->create([
                        'type' => 'student',
                        'name' => $student->name,
                        'nis' => $student->nis,
                        'class_level' => $nextLevel,
                        'class_name' => $student->class_name,
                        'academic_year' => $targetAcademicYear,
                        'contact' => $student->contact,
                        'is_active' => true,
                    ]);
                    $targetStudents->put($student->nis, $nextYearStudent);
                }

                $promoted++;
            }

            return [
                'archived' => $students->count(),
                'promoted' => $promoted,
                'graduated' => $graduated,
                'updated' => $updated,
            ];
        }, 3);
    }
}
