<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentService
{
    private const INITIAL_PASSWORD = 'uni123';

    public function syncAccount(User $user): Student
    {
        $email = Str::lower(trim($user->email));

        return DB::transaction(function () use ($user, $email): Student {
            $student = Student::query()
                ->where('student_email', $email)
                ->first();

            if ($student) {
                return $student;
            }

            $this->lockStudentNumberSequence();

            return Student::query()->firstOrCreate(
                ['student_email' => $email],
                [
                    'student_number' => $this->nextAvailableStudentNumber(),
                    'student_name' => trim($user->name),
                ],
            );
        });
    }

    public function index(?string $search = null): LengthAwarePaginator
    {
        $studentNumberSort = DB::connection()->getDriverName() === 'mysql'
            ? 'CAST(SUBSTRING(student_number, 5) AS UNSIGNED)'
            : 'CAST(SUBSTR(student_number, 5) AS INTEGER)';

        return Student::query()
            ->select(['id', 'student_number', 'student_name', 'student_email'])
            ->whereNull('archived_at')
            ->withCount([
                'loans',
                'loans as active_loans_count' => fn ($query) => $query->whereNull('return_date'),
            ])
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('student_number', 'like', '%'.$search.'%')
                        ->orWhere('student_name', 'like', '%'.$search.'%')
                        ->orWhere('student_email', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw($studentNumberSort)
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function nextAvailableStudentNumbers(int $limit = 5): array
    {
        $lastNumber = 0;

        foreach (Student::query()->pluck('student_number') as $studentNumber) {
            if (preg_match('/^USR-(\d+)$/i', (string) $studentNumber, $matches) === 1) {
                $lastNumber = max($lastNumber, (int) $matches[1]);
            }
        }

        $numbers = [];

        for ($number = $lastNumber + 1; count($numbers) < $limit; $number++) {
            $studentNumber = 'USR-'.$number;

            if (strlen($studentNumber) > 20) {
                break;
            }

            $numbers[] = $studentNumber;
        }

        if ($numbers !== []) {
            return $numbers;
        }

        throw ValidationException::withMessages([
            'student_number' => 'All automatically generated student numbers are in use.',
        ]);
    }

    public function create(array $data): Student
    {
        $data = $this->sanitize($data);
        $selectedNumber = $data['student_number'] ?? null;
        unset($data['student_number']);

        return DB::transaction(function () use ($data, $selectedNumber): Student {
            $this->lockStudentNumberSequence();
            $availableNumbers = $this->nextAvailableStudentNumbers();

            if ($selectedNumber !== null && $selectedNumber !== $availableNumbers[0]) {
                throw ValidationException::withMessages([
                    'student_number' => 'Choose the next student number in order. Refresh the page to get the current number.',
                ]);
            }

            $data['student_number'] = $selectedNumber ?? $availableNumbers[0];
            $student = Student::create($data);

            User::create([
                'name' => $student->student_name,
                'email' => $student->student_email,
                'password' => self::INITIAL_PASSWORD,
            ]);

            return $student;
        });
    }

    public function show(Student $student): Student
    {
        return $student->loadCount('loans');
    }

    public function update(Student $student, array $data): bool
    {
        $password = $data['password'] ?? null;
        unset($data['password']);
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($student, $data, $password): bool {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());
            $linkedUser = User::query()
                ->where('email', $lockedStudent->student_email)
                ->lockForUpdate()
                ->first();

            $updated = $lockedStudent->update($data);

            if ($updated && $linkedUser) {
                $accountChanges = [
                    'name' => $lockedStudent->student_name,
                    'email' => $lockedStudent->student_email,
                ];

                if (is_string($password) && $password !== '') {
                    $accountChanges['password'] = $password;
                }

                $linkedUser->update($accountChanges);
            }

            return $updated;
        });
    }

    public function hasLoans(Student $student): bool
    {
        return $student->loans()->exists();
    }

    public function archive(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if ($lockedStudent->archived_at !== null) {
                return;
            }

            if ($lockedStudent->loans()->whereNull('return_date')->exists()) {
                throw ValidationException::withMessages([
                    'student' => 'A student with an unreturned book cannot be archived.',
                ]);
            }

            $lockedStudent->forceFill(['archived_at' => now()])->save();
        });
    }

    public function restore(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());
            $lockedStudent->forceFill(['archived_at' => null])->save();
        });
    }

    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if ($this->hasLoans($lockedStudent)) {
                throw ValidationException::withMessages([
                    'student' => 'This student cannot be deleted because they have loan records.',
                ]);
            }

            $lockedStudent->delete();
        });
    }

    private function nextAvailableStudentNumber(): string
    {
        return $this->nextAvailableStudentNumbers(1)[0];
    }

    private function lockStudentNumberSequence(): void
    {
        User::query()->orderBy('id')->lockForUpdate()->firstOrFail();
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        
        if (isset($data['student_email'])) {
            $data['student_email'] = Str::lower($data['student_email']);
        }

        return $data;
    }
}
