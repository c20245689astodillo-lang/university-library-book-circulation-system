<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'student_number_normalization_backup';

    public function up(): void
    {
        Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->primary();
            $table->string('original_student_number', 20);
        });

        DB::transaction(function (): void {
            $students = DB::table('students')->get(['id', 'student_number'])->all();

            if ($students === []) {
                return;
            }

            DB::table(self::BACKUP_TABLE)->insert(array_map(
                fn (object $student): array => [
                    'student_id' => $student->id,
                    'original_student_number' => $student->student_number,
                ],
                $students,
            ));

            usort($students, function (object $left, object $right): int {
                $leftNumber = preg_match('/^USR-(\d+)$/i', $left->student_number, $leftMatches) === 1
                    ? (int) $leftMatches[1]
                    : PHP_INT_MAX;
                $rightNumber = preg_match('/^USR-(\d+)$/i', $right->student_number, $rightMatches) === 1
                    ? (int) $rightMatches[1]
                    : PHP_INT_MAX;

                return ($leftNumber <=> $rightNumber) ?: ($left->id <=> $right->id);
            });

            $studentIds = array_map(fn (object $student): int => (int) $student->id, $students);
            $this->moveToTemporaryNumbers($studentIds);

            foreach ($students as $index => $student) {
                DB::table('students')->where('id', $student->id)->update([
                    'student_number' => 'USR-'.($index + 1),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::transaction(function (): void {
            $backups = DB::table(self::BACKUP_TABLE)->orderBy('student_id')->get();
            $backupIds = array_fill_keys($backups->pluck('student_id')->all(), true);
            $students = DB::table('students')->get(['id', 'student_number']);
            $this->moveToTemporaryNumbers($students->pluck('id')->map(fn ($id): int => (int) $id)->all());

            foreach ($backups as $backup) {
                DB::table('students')->where('id', $backup->student_id)->update([
                    'student_number' => $backup->original_student_number,
                ]);
            }

            $lastNumber = 0;

            foreach ($backups as $backup) {
                if (preg_match('/^USR-(\d+)$/i', $backup->original_student_number, $matches) === 1) {
                    $lastNumber = max($lastNumber, (int) $matches[1]);
                }
            }

            foreach ($students as $student) {
                if (isset($backupIds[$student->id])) {
                    continue;
                }

                do {
                    $studentNumber = 'USR-'.(++$lastNumber);
                } while (DB::table('students')->where('student_number', $studentNumber)->exists());

                DB::table('students')->where('id', $student->id)->update([
                    'student_number' => $studentNumber,
                ]);
            }

            Schema::drop(self::BACKUP_TABLE);
        });
    }

    private function moveToTemporaryNumbers(array $studentIds): void
    {
        $reserved = array_fill_keys(DB::table('students')->pluck('student_number')->all(), true);

        foreach ($studentIds as $studentId) {
            do {
                $temporaryNumber = 'TMP-'.strtoupper(bin2hex(random_bytes(8)));
            } while (isset($reserved[$temporaryNumber]));

            $reserved[$temporaryNumber] = true;

            DB::table('students')->where('id', $studentId)->update([
                'student_number' => $temporaryNumber,
            ]);
        }
    }
};
