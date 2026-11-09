<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['classroom_id', 'id']);
        });

        $subjects = [
            'App\Models\Classroom' => ['classrooms', 'id'],
            'App\Models\Enrollment' => ['enrollments', 'classroom_id'],
            'App\Models\Post' => ['posts', 'classroom_id'],
            'App\Models\Quiz' => ['quizzes', 'classroom_id'],
        ];

        foreach ($subjects as $type => [$table, $column]) {
            DB::table('audit_logs')->where('subject_type', $type)->update([
                'classroom_id' => DB::table($table)->select($column)->whereColumn("{$table}.id", 'audit_logs.subject_id'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['classroom_id', 'id']);
            $table->dropConstrainedForeignId('classroom_id');
        });
    }
};
