<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private StudentService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('students/index', [
            'students' => $this->service->index(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('students/form');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('students.index')->with('success', 'Student added.');
    }

    public function show(Student $student): Response
    {
        return Inertia::render('students/show', [
            'student' => $this->service->show($student),
        ]);
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('students/form', ['student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->service->update($student, $request->validated());

        return redirect()->route('students.index')->with('success', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->service->delete($student);

        return redirect()->route('students.index')->with('success', 'Student deleted.');
    }
}
