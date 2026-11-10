<?php

namespace Tests\Feature\Classrooms;

use App\Livewire\Classrooms\Show;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ImportStudentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_file_with_semicolons_and_accents_is_imported(): void
    {
        $classroom = Classroom::factory()->create();
        $content = mb_convert_encoding("Nome;E-mail\nJoão Conceição;joao@example.test\n", 'Windows-1252', 'UTF-8');

        $this->import($classroom, $content)
            ->assertHasNoErrors()
            ->assertSet('importResult.enrolled', 1);

        $this->assertDatabaseHas('users', ['name' => 'João Conceição', 'email' => 'joao@example.test']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enrollment.imported']);
    }

    public function test_utf8_file_with_bom_and_commas_is_imported(): void
    {
        $classroom = Classroom::factory()->create();

        $this->import($classroom, "\xEF\xBB\xBFnome,email\nKenji,kenji@example.test\n\n")
            ->assertSet('importResult.enrolled', 1);
    }

    public function test_invalid_rows_are_reported_and_valid_ones_are_kept(): void
    {
        $classroom = Classroom::factory()->create();
        $existing = Enrollment::factory()->for($classroom)->create()->user;

        $this->import($classroom, "nome,email\nAna,ana@example.test\nSem Email,\nJá,{$existing->email}\n")
            ->assertSet('importResult.enrolled', 1)
            ->assertSet('importResult.skipped', 1)
            ->assertSee('Linha 3: Preencha o campo e-mail.');
    }

    public function test_staff_accounts_in_the_file_are_reported_and_students_are_kept(): void
    {
        $classroom = Classroom::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->import($classroom, "nome,email\nAna,ana@example.test\nAdministração,{$admin->email}\n")
            ->assertSet('importResult.enrolled', 1)
            ->assertSee('Linha 3: Esse e-mail é de uma conta de professor ou da administração.');

        $this->assertFalse($classroom->enrollments()->where('user_id', $admin->id)->exists());
    }

    public function test_missing_columns_are_refused(): void
    {
        $classroom = Classroom::factory()->create();

        $this->import($classroom, "aluno,contato\nAna,ana@example.test\n")
            ->assertHasErrors(['csv'])
            ->assertSee('A primeira linha precisa ter as colunas nome e email.');

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.test']);
    }

    public function test_more_than_two_hundred_rows_are_refused(): void
    {
        $classroom = Classroom::factory()->create();
        $rows = collect(range(1, 201))->map(fn (int $row) => "Aluno {$row},aluno{$row}@example.test")->implode("\n");

        $this->import($classroom, "nome,email\n{$rows}\n")
            ->assertSee('Envie no máximo 200 alunos por arquivo.');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_other_file_types_are_refused(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('csv', UploadedFile::fake()->create('foto.png', 10, 'image/png'))
            ->call('importStudents')
            ->assertHasErrors(['csv']);
    }

    public function test_another_teacher_cannot_import(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        Livewire::actingAs($student)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('csv', UploadedFile::fake()->createWithContent('alunos.csv', "nome,email\nAna,ana@example.test\n"))
            ->call('importStudents')
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.test']);
    }

    private function import(Classroom $classroom, string $content): Testable
    {
        /** @var User $teacher */
        $teacher = $classroom->teacher;

        return Livewire::actingAs($teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('csv', UploadedFile::fake()->createWithContent('alunos.csv', $content))
            ->call('importStudents');
    }
}
