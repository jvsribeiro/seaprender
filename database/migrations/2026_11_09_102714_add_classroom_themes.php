<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('theme_palette', 16)->nullable();
            $table->string('theme_pattern', 16)->nullable();
            $table->string('theme_base', 7)->nullable();
            $table->string('theme_accent', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['theme_palette', 'theme_pattern', 'theme_base', 'theme_accent']);
        });
    }
};
