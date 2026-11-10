<?php

namespace Database\Seeders;

use App\Actions\Classrooms\GenerateEnrollmentCode;
use App\Actions\Classrooms\ManageRewards;
use App\Actions\Posts\PublishPost;
use App\Actions\Quizzes\PublishQuiz;
use App\Actions\Quizzes\SaveQuiz;
use App\Actions\Quizzes\SubmitQuizAttempt;
use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\QuestionGame;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\ClassroomTheme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const TEACHER_EMAIL = 'joao.ribeiro@example.test';

    private const CLASSROOM_NAME = 'Programação Web T1';

    private const SECOND_CLASSROOM_NAME = 'Redes de Computadores T2';

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
     * Students who have already opened the platform. The others show up in the "never accessed" filters.
     */
    private const ACTIVE_STUDENTS = 10;

    /**
     * Students enrolled in the second classroom, followed by one pending request.
     */
    private const SECOND_CLASSROOM_STUDENTS = 5;

    /**
     * Seed the application's database with fictitious demonstration data.
     */
    public function run(GenerateEnrollmentCode $generateEnrollmentCode, PublishPost $publishPost, SaveQuiz $saveQuiz, PublishQuiz $publishQuiz, SubmitQuizAttempt $submitQuizAttempt, ManageRewards $manageRewards): void
    {
        if (app()->isProduction()) {
            $this->command->error('Os dados de demonstração não podem ser usados em produção.');

            return;
        }

        DB::transaction(function () use ($generateEnrollmentCode, $publishPost, $saveQuiz, $publishQuiz, $submitQuizAttempt, $manageRewards) {
            if (! User::where('email', self::TEACHER_EMAIL)->exists()) {
                $this->createPeople($generateEnrollmentCode);
            }

            $classroom = Classroom::where('name', self::CLASSROOM_NAME)->first();

            if ($classroom && ! $classroom->posts()->exists()) {
                $this->createContent($classroom, $publishPost, $saveQuiz, $publishQuiz, $submitQuizAttempt, $manageRewards);
            }
        });
    }

    private function createPeople(GenerateEnrollmentCode $generateEnrollmentCode): void
    {
        $password = config('app.demo_password');

        User::factory()->admin()->create([
            'name' => 'Administração',
            'email' => 'admin@example.test',
            'password' => $password,
            'last_seen_at' => now(),
        ]);

        $teacher = User::factory()->teacher()->create([
            'name' => 'João Ribeiro',
            'email' => self::TEACHER_EMAIL,
            'password' => $password,
            'last_seen_at' => now(),
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
            'last_seen_at' => now()->subDay(),
        ]);

        $secondClassroom = Classroom::factory()->for($secondTeacher, 'teacher')->create([
            'name' => self::SECOND_CLASSROOM_NAME,
            'course' => 'Técnico em Redes',
            'curricular_unit' => 'Infraestrutura de Redes',
            'period' => 'Tarde',
            'enrollment_code' => $generateEnrollmentCode->handle(),
            'theme_palette' => 'ocean',
        ]);

        foreach (self::STUDENTS as $index => $name) {
            $student = User::factory()->create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@example.test',
                'password' => $password,
                'role' => UserRole::Student,
                'last_seen_at' => $index < self::ACTIVE_STUDENTS ? now()->subHours($index * 5) : null,
            ]);

            Enrollment::factory()->for($classroom)->for($student)->create();

            if ($index < self::SECOND_CLASSROOM_STUDENTS) {
                Enrollment::factory()->for($secondClassroom)->for($student)->create();
            } elseif ($index === self::SECOND_CLASSROOM_STUDENTS) {
                Enrollment::factory()->for($secondClassroom)->for($student)->pending()->create();
            }
        }
    }

    private function createContent(Classroom $classroom, PublishPost $publishPost, SaveQuiz $saveQuiz, PublishQuiz $publishQuiz, SubmitQuizAttempt $submitQuizAttempt, ManageRewards $manageRewards): void
    {
        $teacher = $classroom->teacher;
        $students = $classroom->activeEnrollments()->with('user')->orderBy('id')->get()->pluck('user');
        $html = $classroom->subjects()->create(['name' => 'HTML']);
        $css = $classroom->subjects()->create(['name' => 'CSS']);

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
        $this->answerQuiz($quiz, $students->take(8), $submitQuizAttempt);

        $poll = $publishPost->handle($teacher, $classroom, [
            'type' => PostType::Poll,
            'title' => 'Qual projeto vocês querem construir primeiro?',
            'poll_options' => ['Portfólio pessoal', 'Página de receitas', 'Site da escola'],
        ]);
        $this->respond($poll, $students->take(9), fn (int $index) => ['choice' => $index % 3]);

        $reflection = $publishPost->handle($teacher, $classroom, [
            'type' => PostType::Reflection,
            'title' => 'Reflexão da aula de HTML',
            'body' => 'Conte o que você gostou, o que pode melhorar e resuma a aula com suas palavras.',
        ]);
        $this->respond($reflection, $students->take(4), fn () => [
            'liked' => 'Montar a página e ver o resultado na hora.',
            'improve' => 'Mais tempo para praticar os exercícios.',
            'summary' => 'Aprendi a estruturar uma página com títulos, parágrafos, imagens e links.',
        ]);

        $this->markPostsAsViewed($classroom, $students->take(self::ACTIVE_STUDENTS));
        $this->stockShop($classroom, $teacher, $students, $manageRewards);

        QuestionGame::create([
            'classroom_id' => $classroom->id,
            'title' => 'Revisão relâmpago de CSS',
            'topic' => 'Seletores e cores',
            'intro' => 'Três perguntas para aquecer antes da aula prática.',
            'seconds_per_question' => 20,
            'theme' => ClassroomTheme::resolve('orbit'),
            'questions' => [
                ['prompt' => 'Qual propriedade muda a cor do texto?', 'options' => ['color', 'background', 'font-size'], 'correct' => 0, 'explanation' => 'color define a cor do texto.'],
                ['prompt' => 'Como selecionar um elemento pelo id "menu"?', 'options' => ['.menu', '#menu', 'menu'], 'correct' => 1, 'explanation' => 'O # seleciona pelo id.'],
                ['prompt' => 'Qual unidade é relativa ao tamanho da fonte?', 'options' => ['px', 'em', 'cm'], 'correct' => 1, 'explanation' => 'em é relativo ao tamanho da fonte do elemento.'],
            ],
        ]);

        $secondClassroom = Classroom::where('name', self::SECOND_CLASSROOM_NAME)->first();
        if ($secondClassroom) {
            $publishPost->handle($secondClassroom->teacher, $secondClassroom, [
                'type' => PostType::Announcement,
                'title' => 'Boas-vindas à turma de Redes',
                'body' => 'Na primeira aula vamos montar uma rede local do zero.',
            ]);
        }
    }

    /**
     * Each student gets a different mix of right and wrong answers.
     *
     * @param  Collection<int, User>  $students
     */
    private function answerQuiz(Quiz $quiz, Collection $students, SubmitQuizAttempt $submitQuizAttempt): void
    {
        $questions = $quiz->questions()->get();

        foreach ($students->values() as $index => $student) {
            $answers = $questions->mapWithKeys(fn (QuizQuestion $question, int $position) => [
                $question->id => ($index + $position) % 3 === 0
                    ? ($question->correct_option + 1) % count($question->options)
                    : $question->correct_option,
            ])->all();

            $submitQuizAttempt->handle($student, $quiz, $answers);
        }
    }

    /**
     * @param  Collection<int, User>  $students
     * @param  callable(int): array<string, mixed>  $answer
     */
    private function respond(Post $post, Collection $students, callable $answer): void
    {
        DB::table('post_responses')->insert($students->values()->map(fn (User $student, int $index) => [
            'post_id' => $post->id,
            'user_id' => $student->id,
            ...$answer($index),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function markPostsAsViewed(Classroom $classroom, Collection $students): void
    {
        $postIds = $classroom->posts()->pluck('id');

        DB::table('post_views')->insertOrIgnore($students->crossJoin($postIds)->map(fn (array $pair) => [
            'post_id' => $pair[1],
            'user_id' => $pair[0]->id,
            'viewed_at' => now(),
        ])->all());
    }

    /**
     * Rewards cost more than most balances, so the shop shows locked items next to affordable ones.
     *
     * @param  Collection<int, User>  $students
     */
    private function stockShop(Classroom $classroom, User $teacher, Collection $students, ManageRewards $manageRewards): void
    {
        $rewards = [
            ['title' => 'Escolher a música da aula', 'description' => 'A trilha da próxima aula prática fica por sua conta.', 'cost' => 30, 'stock' => 5],
            ['title' => 'Adesivo pixel do Se Aprender', 'description' => 'Entregue na aula seguinte ao resgate.', 'cost' => 60, 'stock' => 10],
            ['title' => 'Sentar com quem quiser na prática', 'description' => null, 'cost' => 120, 'stock' => 3],
        ];

        foreach ($rewards as $reward) {
            $manageRewards->create($teacher, $classroom, $reward);
        }

        foreach ($students->values() as $index => $student) {
            $manageRewards->award($teacher, $classroom, $student->id, 80 - ($index % 5) * 15, 'Participação na aula', (string) Str::uuid());
        }

        $musicId = DB::table('classroom_rewards')->where('classroom_id', $classroom->id)->where('cost', 30)->value('id');
        $manageRewards->redeem($students->first(), $classroom, $musicId, (string) Str::uuid());
    }
}
