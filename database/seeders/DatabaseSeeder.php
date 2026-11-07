<?php

namespace Database\Seeders;

use App\Actions\Classrooms\GenerateEnrollmentCode;
use App\Actions\Posts\PublishPost;
use App\Actions\Quizzes\PublishQuiz;
use App\Actions\Quizzes\SaveQuiz;
use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const TEACHER_EMAIL = 'joao.ribeiro@example.test';

    private const CLASSROOM_NAME = 'Programação Web T1';

    private const STUDENTS = [
        'Ana Beatriz Souza',
        'Caio Nascimento',
        'Yara Potiguara',
        'Kenji Tanaka',
        'Lucas Oliveira',
        'Mariana Santos',
        'Thiago Ferreira',
        'Júlia Almeida',
        'Rafael Lima',
        'Camila Rocha',
        'Aline Nakamura',
        'Pedro Henrique Dias',
        'Luana Cardoso',
        'Dona Marta Silva',
        'Enzo Barbosa',
    ];

    /**
     * Seed the application's database with fictitious demonstration data.
     */
    public function run(GenerateEnrollmentCode $generateEnrollmentCode, PublishPost $publishPost, SaveQuiz $saveQuiz, PublishQuiz $publishQuiz): void
    {
        if (app()->isProduction()) {
            $this->command->error('Os dados de demonstração não podem ser usados em produção.');

            return;
        }

        if (! User::where('email', self::TEACHER_EMAIL)->exists()) {
            $this->createPeople($generateEnrollmentCode);
        }

        $classroom = Classroom::where('name', self::CLASSROOM_NAME)->first();

        if ($classroom && ! $classroom->posts()->exists()) {
            $this->createContent($classroom, $publishPost, $saveQuiz, $publishQuiz);
        }
    }

    private function createPeople(GenerateEnrollmentCode $generateEnrollmentCode): void
    {
        $password = config('app.demo_password');

        User::factory()->admin()->create([
            'name' => 'Administração',
            'email' => 'admin@example.test',
            'password' => $password,
        ]);

        $teacher = User::factory()->teacher()->create([
            'name' => 'João Ribeiro',
            'email' => self::TEACHER_EMAIL,
            'password' => $password,
        ]);

        $classroom = Classroom::factory()->for($teacher, 'teacher')->create([
            'name' => self::CLASSROOM_NAME,
            'course' => 'Técnico em Informática',
            'curricular_unit' => 'Desenvolvimento Web',
            'enrollment_code' => $generateEnrollmentCode->handle(),
        ]);

        $secondTeacher = User::factory()->teacher()->create([
            'name' => 'Renata Costa',
            'email' => 'renata.costa@example.test',
            'password' => $password,
        ]);

        Classroom::factory()->for($secondTeacher, 'teacher')->create([
            'name' => 'Redes de Computadores T2',
            'course' => 'Técnico em Redes',
            'curricular_unit' => 'Infraestrutura de Redes',
            'period' => 'Tarde',
            'enrollment_code' => $generateEnrollmentCode->handle(),
        ]);

        foreach (self::STUDENTS as $name) {
            $student = User::factory()->create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@example.test',
                'password' => $password,
                'role' => UserRole::Student,
            ]);

            Enrollment::factory()->for($classroom)->for($student)->create();
        }
    }

    private function createContent(Classroom $classroom, PublishPost $publishPost, SaveQuiz $saveQuiz, PublishQuiz $publishQuiz): void
    {
        $teacher = $classroom->teacher;
        $html = $classroom->subjects()->create(['name' => 'HTML']);
        $classroom->subjects()->create(['name' => 'CSS']);

        $publishPost->handle($teacher, $classroom, [
            'type' => PostType::Announcement,
            'title' => 'Bem-vindos à turma!',
            'body' => "Tudo da nossa aula vai aparecer neste mural.\nCurta para eu saber que você viu.",
        ]);

        $publishPost->handle($teacher, $classroom, [
            'type' => PostType::Link,
            'title' => 'Referência de HTML',
            'body' => 'Material de consulta para a aula.',
            'url' => 'https://developer.mozilla.org/pt-BR/docs/Web/HTML',
            'subject_id' => $html->id,
        ]);

        $publishPost->handle($teacher, $classroom, [
            'type' => PostType::Activity,
            'title' => 'Monte a sua primeira página',
            'body' => 'Crie uma página com título, parágrafo, imagem e link.',
            'due_on' => now()->addWeek()->toDateString(),
            'subject_id' => $html->id,
        ]);

        $quiz = $saveQuiz->handle($teacher, $classroom, null, [
            'title' => 'Quiz de HTML',
            'description' => 'Três perguntas rápidas sobre a aula.',
            'subject_id' => $html->id,
        ], [
            ['prompt' => 'Qual tag cria um link?', 'options' => ['<a>', '<p>', '<img>'], 'correct' => 0, 'explanation' => 'A tag a, com o atributo href, cria um link.'],
            ['prompt' => 'Qual tag mostra uma imagem?', 'options' => ['<div>', '<img>', '<h1>'], 'correct' => 1, 'explanation' => 'A tag img mostra a imagem indicada em src.'],
            ['prompt' => 'Qual tag é o título principal da página?', 'options' => ['<span>', '<title>', '<h1>'], 'correct' => 2, 'explanation' => 'O h1 é o título principal do conteúdo.'],
        ]);

        $publishQuiz->handle($teacher, $quiz);
    }
}
