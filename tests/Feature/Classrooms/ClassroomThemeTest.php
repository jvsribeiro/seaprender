<?php

namespace Tests\Feature\Classrooms;

use App\Livewire\Classrooms\Form;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClassroomThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_edit_a_theme_without_changing_other_classrooms(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherClassroom = Classroom::factory()->for($teacher, 'teacher')->create();
        $originalTheme = $otherClassroom->theme();

        Livewire::actingAs($teacher)->test(Form::class)
            ->set('name', 'Introdução a HTML')->set('course', 'Informática')->set('curricular_unit', 'HTML')
            ->set('starts_on', '2026-11-09')->set('ends_on', '2026-12-12')
            ->set('theme_palette', 'orbit')->assertSet('theme_pattern', 'stars')->assertSee('Órbita roxa')
            ->set('theme_pattern', 'stripes')->call('save')->assertHasNoErrors();

        $classroom = Classroom::query()->where('name', 'Introdução a HTML')->sole();
        $this->assertSame('stripes', $classroom->theme()['pattern']);

        Livewire::test(Form::class, ['classroom' => $classroom])
            ->set('theme_palette', 'custom')->set('theme_pattern', 'waves')
            ->set('theme_base', '#304F70')->set('theme_accent', '#A1D99B')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'theme_palette' => 'custom',
            'theme_pattern' => 'waves',
            'theme_base' => '#304F70',
            'theme_accent' => '#A1D99B',
        ]);
        $this->assertSame($originalTheme, $otherClassroom->fresh()->theme());

        $student = Enrollment::factory()->for($classroom)->create()->user;
        $this->actingAs($student)->get(route('classrooms.show', $classroom))
            ->assertOk()->assertSeeHtml('data-classroom-pattern="waves"')->assertSeeHtml('--classroom-base: #304F70');
        $this->get(route('classrooms.edit', $classroom))->assertForbidden();
    }

    public function test_theme_rejects_unknown_patterns_and_arbitrary_css(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Form::class, ['classroom' => $classroom])
            ->set('theme_palette', 'custom')
            ->set('theme_pattern', 'custom-css')
            ->set('theme_base', '#fff; background: url(https://example.test)')
            ->assertDontSeeHtml('--classroom-base: #fff; background: url(')
            ->call('save')->assertHasErrors(['theme_pattern', 'theme_base']);

        $this->assertNull($classroom->fresh()->theme_base);
    }
}
