<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private StudentService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('students/index', [
            'students' => $this->service->index(),
            'currentStudentId' => $request->user()->libraryStudent()?->id,
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
        $this->authorize('view', $student);

        return Inertia::render('students/show', [
            'student' => $this->service->show($student),
        ]);
    }

    public function edit(Student $student): Response
    {
        $this->authorize('update', $student);

        return Inertia::render('students/form', ['student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->service->update($student, $request->validated());

        return redirect()->route('students.index')->with('success', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);
        $this->service->delete($student);

        return redirect()->route('students.index')->with('success', 'Student deleted.');
    }
}
