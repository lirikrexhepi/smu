<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 6)->unique();
            $table->string('qr_token', 96)->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('late_after_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['professor_id', 'starts_at']);
            $table->index(['course_id', 'course_schedule_id', 'starts_at'], 'attendance_sessions_class_window_index');
            $table->index(['closed_at', 'ends_at']);
        });

        Schema::create('attendance_session_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'present', 'late', 'absent'])->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->enum('method', ['qr', 'code', 'manual'])->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'attendance_session_records_student_unique');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_session_records');
        Schema::dropIfExists('attendance_sessions');
    }
};
