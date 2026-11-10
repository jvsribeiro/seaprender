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
        Schema::create('live_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->json('questions');
            $table->json('game_settings')->nullable();
            $table->unsignedSmallInteger('seconds_per_question');
            $table->string('phase', 12)->default('lobby');
            $table->unsignedSmallInteger('question_index')->default(0);
            $table->timestamp('question_ends_at')->nullable();
            $table->timestamps();

            $table->index(['classroom_id', 'phase']);
        });

        Schema::create('live_quiz_players', function (Blueprint $table) {
            $table->foreignId('live_quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['live_quiz_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('live_quiz_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('live_quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('question_index');
            $table->unsignedTinyInteger('choice');
            $table->unsignedSmallInteger('score');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['live_quiz_id', 'user_id', 'question_index']);
            $table->foreign(['live_quiz_id', 'user_id'])->references(['live_quiz_id', 'user_id'])->on('live_quiz_players')->cascadeOnDelete();
            $table->index(['live_quiz_id', 'question_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_quiz_answers');
        Schema::dropIfExists('live_quiz_players');
        Schema::dropIfExists('live_quizzes');
    }
};
