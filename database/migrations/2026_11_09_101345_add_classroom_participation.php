<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('poll_options')->nullable();
            $table->timestamp('closed_at')->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('post_responses');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['poll_options', 'closed_at']);
        });
    }
};
