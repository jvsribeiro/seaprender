<?php

namespace Tests\Feature\Classrooms;

use App\Enums\PostType;
use App\Livewire\Classrooms\Feed;
use App\Livewire\Classrooms\Participation;
use App\Livewire\Classrooms\Progress;
use App\Livewire\Classrooms\Tools;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParticipationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_edit_a_private_reflection_and_find_it_in_progress(): void
    {
        $classroom = Classroom::factory()->create();
        [$student, $classmate] = Enrollment::factory()->for($classroom)->count(2)->create()->pluck('user')->all();

        Livewire::actingAs($classroom->teacher)->test(Tools::class, ['classroom' => $classroom])
            ->set('reflectionTitle', 'Fechamento da aula de HTML')
            ->call('publishReflection')->assertHasNoErrors();

        $post = $classroom->posts()->where('type', PostType::Reflection)->sole();
        Livewire::actingAs($student)->test(Feed::class, ['classroom' => $classroom])->assertSee('Enviar reflexão');
        Livewire::test(Participation::class, ['post' => $post])
            ->call('saveReflection')->assertHasErrors(['liked', 'improve', 'summary'])
            ->set('liked', 'Os exercícios de links')
            ->set('improve', 'Mais exemplos de acessibilidade')
            ->set('summary', 'Aprendi como montar um hiperlink.')
            ->call('saveReflection')->assertHasNoErrors()->assertSee('Reflexão enviada.')
            ->set('summary', 'Aprendi a dar um nome claro aos hiperlinks.')
            ->call('saveReflection')->assertHasNoErrors();

        $this->assertDatabaseCount('post_responses', 1);
        $this->assertDatabaseHas('post_responses', ['post_id' => $post->id, 'user_id' => $student->id, 'summary' => 'Aprendi a dar um nome claro aos hiperlinks.']);

        Livewire::test(Progress::class, ['classroom' => $classroom])
            ->assertSee('Meus resumos')->assertSee('Aprendi a dar um nome claro aos hiperlinks.');
        Livewire::actingAs($classmate)->test(Participation::class, ['post' => $post])
            ->set('showResponses', true)
            ->assertDontSee('Aprendi a dar um nome claro aos hiperlinks.');
        Livewire::test(Progress::class, ['classroom' => $classroom])->assertDontSee('Aprendi a dar um nome claro aos hiperlinks.');
        Livewire::actingAs($classroom->teacher)->test(Participation::class, ['post' => $post])
            ->set('showResponses', true)->assertSee('Aprendi a dar um nome claro aos hiperlinks.');

        $this->assertDatabaseHas('audit_logs', [
            'classroom_id' => $classroom->id,
            'action' => 'post.reflection_submitted',
            'actor_id' => $student->id,
        ]);
        Livewire::actingAs(User::factory()->create())->test(Participation::class, ['post' => $post])->assertStatus(404);
    }

    public function test_poll_hides_tallies_until_closed_and_preserves_the_first_vote(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        Livewire::actingAs($classroom->teacher)->test(Tools::class, ['classroom' => $classroom])
            ->set('pollQuestion', 'O que revisar amanhã?')
            ->set('pollOptions', "HTML\nhtml")
            ->call('publishPoll')->assertHasErrors(['pollOptions'])
            ->set('pollOptions', "HTML\n\n CSS")
            ->call('publishPoll')->assertHasNoErrors();

        $post = $classroom->posts()->where('type', PostType::Poll)->sole();
        Livewire::actingAs($student)->test(Participation::class, ['post' => $post])
            ->call('vote', 0)->assertSee('Seu voto foi registrado.')
            ->call('vote', 1)->assertDontSee('1 voto');

        $this->assertDatabaseCount('post_responses', 1);
        $this->assertDatabaseHas('post_responses', ['post_id' => $post->id, 'user_id' => $student->id, 'choice' => 0]);

        Livewire::test(Participation::class, ['post' => $post])->call('close')->assertForbidden();
        Livewire::actingAs($classroom->teacher)->test(Participation::class, ['post' => $post])
            ->assertSee('1 voto')->call('close')->assertSee('Encerrada');
        Livewire::actingAs($student)->test(Participation::class, ['post' => $post->fresh()])
            ->assertSee('1 voto')->call('vote', 1)->assertHasErrors(['response']);
    }

    public function test_teacher_can_draw_without_repeating_students_and_split_the_class_into_groups(): void
    {
        $classroom = Classroom::factory()->create();
        $students = Enrollment::factory()->for($classroom)->count(3)->create()->pluck('user');

        $tools = Livewire::actingAs($classroom->teacher)->test(Tools::class, ['classroom' => $classroom]);
        $tools->call('drawStudent')->call('drawStudent')->call('drawStudent')->call('drawStudent')->assertHasErrors(['draw']);
        $this->assertEqualsCanonicalizing($students->pluck('id')->all(), $tools->get('drawnIds'));

        $tools->call('resetDraw')->assertSet('drawnIds', [])
            ->set('groupCount', '4')->call('drawGroups')->assertHasErrors(['groupCount'])
            ->set('groupCount', '2')->call('drawGroups')->assertHasNoErrors()->assertSee('Grupo 2');

        $groups = $tools->get('groups');
        $this->assertCount(2, $groups[0]);
        $this->assertCount(1, $groups[1]);
        $this->assertEqualsCanonicalizing($students->pluck('name')->all(), array_merge(...$groups));
    }
}
