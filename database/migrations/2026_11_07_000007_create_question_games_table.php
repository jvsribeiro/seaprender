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

            $table->index(['classroom_id', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_games');
    }
};
