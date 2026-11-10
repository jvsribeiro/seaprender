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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('live_quiz_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->uuid('tool_key')->nullable()->unique();
            $table->string('type', 20);
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->json('poll_options')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['classroom_id', 'id']);
        });

        Schema::create('post_likes', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['post_id', 'user_id']);
        });

        Schema::create('post_views', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();

            $table->primary(['post_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('post_responses', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('choice')->nullable();
            $table->text('liked')->nullable();
            $table->text('improve')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->primary(['post_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_responses');
        Schema::dropIfExists('post_views');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('posts');
    }
};
