<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('topic', 100)->nullable();
            $table->string('intro', 500)->nullable();
            $table->unsignedSmallInteger('seconds_per_question');
            $table->json('theme');
            $table->json('questions');
            $table->timestamps();
        });

        Schema::table('live_quizzes', function (Blueprint $table) {
            $table->foreignId('question_game_id')->nullable()->constrained()->nullOnDelete();
            $table->json('game_settings')->nullable();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('tool_key')->nullable()->unique();
            $table->foreignId('live_quiz_id')->nullable()->unique()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['tool_key']);
            $table->dropUnique(['live_quiz_id']);
            $table->dropConstrainedForeignId('live_quiz_id');
            $table->dropColumn('tool_key');
        });

        Schema::table('live_quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_game_id');
            $table->dropColumn('game_settings');
        });

        Schema::dropIfExists('question_games');
    }
};
