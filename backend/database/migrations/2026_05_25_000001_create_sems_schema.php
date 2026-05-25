<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['faculty_id', 'name']);
        });

        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('degree_level')->nullable();
            $table->unsignedSmallInteger('required_credits')->nullable();
            $table->timestamps();

            $table->unique(['department_id', 'name']);
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id')->unique();
            $table->enum('role', ['student', 'professor', 'admin']);
            $table->string('institution_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role', 'institution_id']);
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number')->unique();
            $table->string('student_key')->unique();
            $table->string('status')->default('Active');
            $table->string('status_label')->nullable();
            $table->string('year_of_study')->nullable();
            $table->string('current_semester_label')->nullable();
            $table->string('academic_year_label')->nullable();
            $table->decimal('current_gpa', 4, 2)->nullable();
            $table->unsignedSmallInteger('credits_earned')->default(0);
            $table->unsignedSmallInteger('credits_required')->nullable();
            $table->string('academic_standing')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('personal_number')->nullable()->unique();
            $table->timestamp('profile_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_emergency_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });

        Schema::create('professors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('office')->nullable();
            $table->string('office_hours')->nullable();
            $table->string('consultation')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('number')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('course_key')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('ects')->default(0);
            $table->string('status')->default('Active');
            $table->string('room')->nullable();
            $table->text('description')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->json('topics')->nullable();
            $table->text('grading_breakdown')->nullable();
            $table->timestamps();
        });

        Schema::create('course_professor', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('instructor');
            $table->timestamps();

            $table->unique(['course_id', 'professor_id', 'role']);
        });

        Schema::create('course_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('days_label')->nullable();
            $table->json('days')->nullable();
            $table->string('time_label')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('room')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('course_info_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('item_key');
            $table->string('label');
            $table->text('value');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'item_key']);
        });

        Schema::create('course_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('material_key');
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('size_label')->nullable();
            $table->string('download_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'material_key']);
        });

        Schema::create('course_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('event_key');
            $table->enum('category', ['assessment', 'exam', 'deadline']);
            $table->string('title');
            $table->string('type')->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->string('date_label')->nullable();
            $table->string('time_label')->nullable();
            $table->string('status_label')->nullable();
            $table->string('tone')->nullable();
            $table->string('mode')->nullable();
            $table->string('duration')->nullable();
            $table->string('room')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'event_key', 'category']);
            $table->index(['category', 'event_date']);
        });

        Schema::create('course_announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('announcement_key');
            $table->string('title');
            $table->text('body');
            $table->date('published_on')->nullable();
            $table->string('date_label')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'announcement_key']);
        });

        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'registered', 'upcoming', 'completed', 'dropped'])->default('active');
            $table->string('status_label')->nullable();
            $table->string('current_grade')->nullable();
            $table->decimal('current_grade_points', 4, 1)->nullable();
            $table->unsignedTinyInteger('attendance_percentage')->default(0);
            $table->foreignId('next_important_event_id')->nullable()->constrained('course_events')->nullOnDelete();
            $table->date('enrolled_on')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'semester_id']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('course_attendance_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('required_percentage')->default(75);
            $table->unsignedSmallInteger('sessions_held')->default(0);
            $table->unsignedSmallInteger('sessions_attended')->default(0);
            $table->string('status')->nullable();
            $table->json('summary_items')->nullable();
            $table->timestamps();
        });

        Schema::create('course_attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('record_key')->nullable();
            $table->date('held_on')->nullable();
            $table->string('date_label')->nullable();
            $table->string('type')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'recorded', 'scheduled'])->default('present');
            $table->string('status_label')->nullable();
            $table->timestamps();

            $table->index(['student_enrollment_id', 'held_on']);
        });

        Schema::create('course_grade_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('component');
            $table->unsignedTinyInteger('weight');
            $table->timestamps();

            $table->unique(['course_id', 'component']);
        });

        Schema::create('course_grade_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('grade_key')->nullable();
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('score')->nullable();
            $table->string('weight_label')->nullable();
            $table->date('graded_on')->nullable();
            $table->string('date_label')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['student_enrollment_id', 'graded_on']);
        });

        Schema::create('attendance_weeks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('label')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'starts_on', 'ends_on']);
        });

        Schema::create('attendance_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('overall_attendance')->default(0);
            $table->unsignedSmallInteger('present_sessions')->default(0);
            $table->unsignedSmallInteger('total_sessions')->default(0);
            $table->unsignedSmallInteger('absences')->default(0);
            $table->unsignedSmallInteger('late_records')->default(0);
            $table->smallInteger('comparison_value')->default(0);
            $table->string('comparison_direction')->default('flat');
            $table->string('comparison_label')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'semester_id']);
        });

        Schema::create('attendance_schedule_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_week_id')->constrained()->cascadeOnDelete();
            $table->date('day_on');
            $table->string('day_name')->nullable();
            $table->string('day_short')->nullable();
            $table->string('date_label')->nullable();
            $table->boolean('is_today')->default(false);
            $table->timestamps();

            $table->unique(['attendance_week_id', 'day_on']);
        });

        Schema::create('attendance_schedule_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_schedule_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('block_key')->nullable();
            $table->string('time_label')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('room')->nullable();
            $table->string('type')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'recorded', 'scheduled'])->default('scheduled');
            $table->string('status_label')->nullable();
            $table->string('tone')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'starts_at']);
        });

        Schema::create('attendance_history_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('record_key')->nullable();
            $table->date('recorded_on')->nullable();
            $table->string('date_label')->nullable();
            $table->string('time_label')->nullable();
            $table->string('type')->nullable();
            $table->string('professor_name')->nullable();
            $table->enum('result', ['present', 'absent', 'late'])->default('present');
            $table->string('result_label')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'recorded_on']);
            $table->index(['course_id', 'recorded_on']);
        });

        Schema::create('attendance_last_recorded', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on')->nullable();
            $table->string('date_label')->nullable();
            $table->string('time_label')->nullable();
            $table->string('status')->default('recorded');
            $table->string('status_label')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'course_id']);
        });

        Schema::create('transcript_semester_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('semester_code');
            $table->string('label');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'semester_code']);
        });

        Schema::create('transcript_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('academic_year')->nullable();
            $table->decimal('average_grade', 4, 2)->default(0);
            $table->string('grade_status')->nullable();
            $table->unsignedSmallInteger('total_credits_earned')->default(0);
            $table->unsignedSmallInteger('required_credits')->default(0);
            $table->unsignedSmallInteger('courses_completed')->default(0);
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->string('academic_standing')->nullable();
            $table->string('eligibility_status')->nullable();
            $table->string('transcript_action_label')->nullable();
            $table->string('transcript_action_status')->nullable();
            $table->timestamps();
        });

        Schema::create('grade_distributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grade');
            $table->string('label');
            $table->unsignedSmallInteger('count')->default(0);
            $table->unsignedTinyInteger('percentage')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'grade']);
        });

        Schema::create('transcript_course_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('semester_code');
            $table->decimal('numeric_grade', 4, 1)->default(0);
            $table->decimal('grade_points', 4, 1)->default(0);
            $table->enum('status', ['passed', 'failed', 'in-progress'])->default('in-progress');
            $table->string('status_label')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'semester_code'], 'transcript_course_grades_unique');
        });

        Schema::create('student_dashboard_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('metric_key');
            $table->string('label');
            $table->string('value');
            $table->string('helper')->nullable();
            $table->string('tone')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'metric_key']);
        });

        Schema::create('student_dashboard_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('class_key');
            $table->string('time_label');
            $table->string('course_code');
            $table->string('course_name');
            $table->string('room')->nullable();
            $table->string('type')->nullable();
            $table->string('tone')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'class_key']);
        });

        Schema::create('student_dashboard_deadlines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('deadline_key');
            $table->string('title');
            $table->string('course_code');
            $table->string('date_label')->nullable();
            $table->string('status_label')->nullable();
            $table->string('tone')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'deadline_key']);
        });

        Schema::create('student_dashboard_latest_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('grade_key');
            $table->string('course');
            $table->string('assessment');
            $table->string('type')->nullable();
            $table->string('grade')->nullable();
            $table->string('date_label')->nullable();
            $table->string('tone')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'grade_key']);
        });

        Schema::create('student_dashboard_attendance_warnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('course_code');
            $table->string('course_name');
            $table->unsignedTinyInteger('rate');
            $table->unsignedTinyInteger('required_rate');
            $table->string('message');
            $table->text('detail')->nullable();
            $table->timestamps();
        });

        Schema::create('student_dashboard_attendance_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('course_name');
            $table->unsignedTinyInteger('rate');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_dashboard_attendance_summaries');
        Schema::dropIfExists('student_dashboard_attendance_warnings');
        Schema::dropIfExists('student_dashboard_latest_grades');
        Schema::dropIfExists('student_dashboard_deadlines');
        Schema::dropIfExists('student_dashboard_classes');
        Schema::dropIfExists('student_dashboard_metrics');
        Schema::dropIfExists('transcript_course_grades');
        Schema::dropIfExists('grade_distributions');
        Schema::dropIfExists('transcript_summaries');
        Schema::dropIfExists('transcript_semester_options');
        Schema::dropIfExists('attendance_last_recorded');
        Schema::dropIfExists('attendance_history_records');
        Schema::dropIfExists('attendance_schedule_blocks');
        Schema::dropIfExists('attendance_schedule_days');
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attendance_weeks');
        Schema::dropIfExists('course_grade_records');
        Schema::dropIfExists('course_grade_components');
        Schema::dropIfExists('course_attendance_records');
        Schema::dropIfExists('course_attendance_summaries');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('course_announcements');
        Schema::dropIfExists('course_events');
        Schema::dropIfExists('course_materials');
        Schema::dropIfExists('course_info_items');
        Schema::dropIfExists('course_schedules');
        Schema::dropIfExists('course_professor');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('professors');
        Schema::dropIfExists('student_emergency_contacts');
        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('faculties');
    }
};
