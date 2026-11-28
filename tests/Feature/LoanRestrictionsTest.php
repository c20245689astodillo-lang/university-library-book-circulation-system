<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_teacher_cannot_create_a_fourth_active_loan(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->createStudent(10);
        $publisher = $this->createPublisher();

        for ($number = 1; $number <= 4; $number++) {
            $book = $this->createBook($publisher, $number);
            $response = $this->from(route('loans.create'))
                ->post(route('loans.store'), $this->loanData($student, $book));

            if ($number < 4) {
                $response->assertRedirect(route('loans.index'));
            } else {
                $response->assertSessionHasErrors('student_id');
            }
        }

        $this->assertDatabaseCount('loans', 3);
    }

    public function test_an_authenticated_teacher_cannot_loan_a_book_that_is_already_reserved(): void
    {
        $this->actingAs(User::factory()->create());
        $publisher = $this->createPublisher();
        $book = $this->createBook($publisher, 1);
        $firstStudent = $this->createStudent(10);
        $secondStudent = $this->createStudent(11);

        Loan::create($this->loanData($firstStudent, $book));

        $this->from(route('loans.create'))
            ->post(route('loans.store'), $this->loanData($secondStudent, $book))
            ->assertSessionHasErrors('book_id');

        $this->assertDatabaseCount('loans', 1);
    }

    public function test_a_teacher_cannot_delete_an_unreturned_loan_or_archive_its_book_or_student(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->createStudent(10);
        $book = $this->createBook($this->createPublisher(), 1);
        $loan = Loan::create($this->loanData($student, $book));

        $this->from(route('loans.index'))
            ->delete(route('loans.destroy', $loan))
            ->assertSessionHasErrors('loan');

        $this->from(route('books.index'))
            ->post(route('archives.archive', ['type' => 'books', 'id' => $book->id]))
            ->assertSessionHasErrors('book');

        $this->from(route('students.index'))
            ->post(route('archives.archive', ['type' => 'students', 'id' => $student->id]))
            ->assertSessionHasErrors('student');

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'return_date' => null]);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'archived_at' => null]);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'archived_at' => null]);
    }

    public function test_a_teacher_cannot_delete_a_loan_with_an_unpaid_fine(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->createStudent(10);
        $book = $this->createBook($this->createPublisher(), 1);
        $loan = Loan::create($this->loanData($student, $book) + ['return_date' => today()->toDateString()]);
        Fine::create(['loan_id' => $loan->id, 'calculated_fine' => 20, 'payment_status' => 'unpaid']);

        $this->from(route('loans.index'))
            ->delete(route('loans.destroy', $loan))
            ->assertSessionHasErrors('loan');

        $this->assertDatabaseHas('fines', ['loan_id' => $loan->id, 'payment_status' => 'unpaid']);
    }

    public function test_direct_loan_requests_cannot_bypass_issue_or_due_date_limits(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->createStudent(10);
        $book = $this->createBook($this->createPublisher(), 1);
        $today = today();

        $this->from(route('loans.create'))
            ->post(route('loans.store'), $this->loanData(
                $student,
                $book,
                $today->copy()->addDays(6)->toDateString(),
                $today->copy()->addDays(16)->toDateString(),
            ))
            ->assertSessionHasErrors('issue_date');

        $this->from(route('loans.create'))
            ->post(route('loans.store'), $this->loanData(
                $student,
                $book,
                $today->toDateString(),
                $today->copy()->addDays(11)->toDateString(),
            ))
            ->assertSessionHasErrors('due_date');
    }

    private function createStudent(int $number): Student
    {
        return Student::create([
            'student_number' => 'USR-'.$number,
            'student_name' => 'Test Student '.$number,
            'student_email' => 'student'.$number.'@example.test',
        ]);
    }

    private function createPublisher(): Publisher
    {
        return Publisher::create(['publisher_name' => 'Test Publisher '.uniqid()]);
    }

    private function createBook(Publisher $publisher, int $number): Book
    {
        return Book::create([
            'publisher_id' => $publisher->id,
            'book_isbn' => 'TEST-'.$number,
            'book_title' => 'Test Book '.$number,
            'author_name' => 'Test Author',
        ]);
    }

    private function loanData(Student $student, Book $book, ?string $issueDate = null, ?string $dueDate = null): array
    {
        $issueDate ??= today()->toDateString();
        $dueDate ??= today()->addDays(10)->toDateString();

        return [
            'student_id' => $student->id,
            'book_id' => $book->id,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
        ];
    }
}
