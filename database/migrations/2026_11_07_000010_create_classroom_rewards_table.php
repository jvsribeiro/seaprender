<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classroom_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('cost');
            $table->unsignedInteger('stock');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['classroom_id', 'enabled']);
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('cost');
            $table->string('status', 12)->default('pending');
            $table->timestamps();

            $table->index(['classroom_reward_id', 'status']);
            $table->index('user_id');
        });

        Schema::create('classroom_point_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reward_redemption_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('operation_id')->nullable()->unique();
            $table->integer('amount');
            $table->string('reason', 160);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['classroom_id', 'user_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_point_entries');
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('classroom_rewards');
    }
};
