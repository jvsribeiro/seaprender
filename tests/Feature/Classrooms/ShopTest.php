<?php

namespace Tests\Feature\Classrooms;

use App\Actions\Classrooms\ManageRewards;
use App\Livewire\Classrooms\Shop;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_awards_points_and_cancelling_a_redemption_refunds_stock_and_balance_once(): void
    {
        $classroom = Classroom::factory()->create();
        [$student, $classmate] = Enrollment::factory()->for($classroom)->count(2)->create()->pluck('user')->all();
        $teacher = $classroom->teacher;

        $shop = Livewire::actingAs($teacher)->test(Shop::class, ['classroom' => $classroom]);
        $awardOperation = $shop->get('operationId');
        $shop->set('studentId', (string) $student->id)->set('points', '50')
            ->set('reason', 'Ajudou no exercício de HTML')->call('award')->assertHasNoErrors()
            ->set('rewardTitle', 'Adesivo pixel')->set('cost', '30')->set('stock', '1')
            ->call('createReward')->assertHasNoErrors();

        app(ManageRewards::class)->award($teacher, $classroom, $student->id, 50, 'Ajudou no exercício de HTML', $awardOperation);

        $rewardId = DB::table('classroom_rewards')->sole()->id;
        Livewire::actingAs($student)->test(Shop::class, ['classroom' => $classroom])
            ->assertSee('50 pontos')->call('redeem', $rewardId)->assertHasNoErrors()
            ->assertSee('20 pontos')->assertSee('Aguardando entrega');

        $this->assertDatabaseHas('classroom_rewards', ['id' => $rewardId, 'stock' => 0]);
        $redemptionId = DB::table('reward_redemptions')->sole()->id;

        Livewire::actingAs($classmate)->test(Shop::class, ['classroom' => $classroom])
            ->assertDontSee('Ajudou no exercício de HTML')->assertDontSee('Aguardando entrega')
            ->call('settle', $redemptionId, true)->assertForbidden();

        Livewire::actingAs($teacher)->test(Shop::class, ['classroom' => $classroom])
            ->call('settle', $redemptionId, false)->assertSee('Cancelado')
            ->call('settle', $redemptionId, false)->assertHasNoErrors();

        $this->assertDatabaseHas('classroom_rewards', ['id' => $rewardId, 'stock' => 1]);
        $this->assertDatabaseCount('classroom_point_entries', 3);
        $this->assertDatabaseHas('classroom_point_entries', ['reward_redemption_id' => $redemptionId, 'amount' => 30]);
        Livewire::actingAs($student)->test(Shop::class, ['classroom' => $classroom])->assertSee('50 pontos');

        Livewire::test(Shop::class, ['classroom' => $classroom])->call('redeem', $rewardId)->assertSee('20 pontos');
        $deliveredId = DB::table('reward_redemptions')->where('status', 'pending')->sole()->id;
        Livewire::actingAs($teacher)->test(Shop::class, ['classroom' => $classroom])
            ->call('settle', $deliveredId, true)->assertSee('Entregue')
            ->call('settle', $deliveredId, false)->assertSee('Entregue');

        $this->assertDatabaseHas('classroom_rewards', ['id' => $rewardId, 'stock' => 0]);
        $this->assertDatabaseHas('reward_redemptions', ['id' => $deliveredId, 'status' => 'delivered']);
        $this->assertDatabaseCount('classroom_point_entries', 4);
    }

    public function test_redemption_rejects_insufficient_balance_and_rewards_from_another_class(): void
    {
        $classroom = Classroom::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        foreach ([$classroom, $otherClassroom] as $owner) {
            Livewire::actingAs($owner->teacher)->test(Shop::class, ['classroom' => $owner])
                ->set('rewardTitle', 'Adesivo')->set('cost', '10')->set('stock', '1')
                ->call('createReward')->assertHasNoErrors();
        }

        $ownReward = DB::table('classroom_rewards')->where('classroom_id', $classroom->id)->sole()->id;
        $otherReward = DB::table('classroom_rewards')->where('classroom_id', $otherClassroom->id)->sole()->id;

        Livewire::actingAs($student)->test(Shop::class, ['classroom' => $classroom])
            ->call('redeem', $ownReward)->assertHasErrors(['shop'])->assertSee('Seu saldo de pontos é insuficiente');
        Livewire::test(Shop::class, ['classroom' => $classroom])->call('redeem', $otherReward)->assertStatus(404);

        $this->assertDatabaseCount('reward_redemptions', 0);
        $this->assertDatabaseCount('classroom_point_entries', 0);
        $this->assertDatabaseHas('classroom_rewards', ['id' => $ownReward, 'stock' => 1]);
    }
}
