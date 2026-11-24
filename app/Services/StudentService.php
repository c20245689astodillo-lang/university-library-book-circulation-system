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

            return Student::query()->firstOrCreate(
                ['student_email' => $email],
                [
                    'student_number' => $this->availableAccountStudentNumber($user->id),
                    'student_name' => trim($user->name),
                ],
            );
        });
    }

    public function index(): LengthAwarePaginator
    {
        return Student::query()
            ->select(['id', 'student_number', 'student_name', 'student_email'])
            ->withCount('loans')
            ->latest('created_at')
            ->latest('id')
            ->paginate(10);
    }

    public function create(array $data): Student
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($data): Student {
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

    private function availableAccountStudentNumber(int $userId): string
    {
        $base = 'USR-'.$userId;
        $studentNumber = substr($base, 0, 20);
        $suffix = 1;

        while (Student::query()->where('student_number', $studentNumber)->exists()) {
            $ending = '-'.$suffix++;
            $studentNumber = substr($base, 0, 20 - strlen($ending)).$ending;
        }

        return $studentNumber;
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
