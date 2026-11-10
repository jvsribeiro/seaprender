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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('course');
            $table->string('curricular_unit');
            $table->string('period');
            $table->string('enrollment_code', 8)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('active');
            $table->string('theme_palette', 16)->nullable();
            $table->string('theme_pattern', 16)->nullable();
            $table->string('theme_base', 7)->nullable();
            $table->string('theme_accent', 7)->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
