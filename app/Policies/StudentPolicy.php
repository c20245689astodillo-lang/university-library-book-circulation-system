<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        return $this->ownsStudentRecord($user, $student);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->ownsStudentRecord($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->ownsStudentRecord($user, $student);
    }

    private function ownsStudentRecord(User $user, Student $student): bool
    {
        return $user->libraryStudent()?->is($student) ?? false;
    }
}
