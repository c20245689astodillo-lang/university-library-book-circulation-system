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
    public function syncAccount(User $user): Student
    {
        $email = Str::lower(trim($user->email));

        return DB::transaction(function () use ($user, $email): Student {
            $student = Student::query()
                ->whereRaw('LOWER(student_email) = ?', [$email])
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
            ->latest()
            ->paginate(10);
    }

    public function create(array $data): Student
    {
        return Student::create($this->sanitize($data));
    }

    public function show(Student $student): Student
    {
        return $student->load(['loans.book', 'loans.fine']);
    }

    public function update(Student $student, array $data): bool
    {
        return $student->update($this->sanitize($data));
    }

    public function hasLoans(Student $student): bool
    {
        return $student->loans()->exists();
    }

    public function delete(Student $student): void
    {
        if ($this->hasLoans($student)) {
            throw ValidationException::withMessages([
                'student' => 'This student cannot be deleted because they have loan records.',
            ]);
        }

        $student->delete();
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

        //============== USE  FILTER VALDIATE EMAIL TO CHECK SANG VALIDITY KA EMAIL =================
        if (isset($data['student_email'])) {
            $data['student_email'] = Str::lower($data['student_email']);
        }

        return $data;
    }
}
