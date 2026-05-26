<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addNumericGradeColumns();
        $this->backfillNumericGrades();
        $this->dropStudentAcademicCacheColumns();
        $this->dropEnrollmentCacheColumns();
        $this->dropDeprecatedMockTables();
    }

    public function down(): void
    {
        $this->restoreEnrollmentCacheColumns();
        $this->restoreStudentAcademicCacheColumns();
        $this->restoreDeprecatedMockTables();
    }

    private function addNumericGradeColumns(): void
    {
        if (! Schema::hasColumn('course_grade_records', 'grade')) {
            Schema::table('course_grade_records', function (Blueprint $table): void {
                $table->decimal('grade', 4, 1)->nullable()->after('score');
            });
        }

        if (! Schema::hasColumn('course_grade_records', 'weight')) {
            Schema::table('course_grade_records', function (Blueprint $table): void {
                $table->unsignedTinyInteger('weight')->nullable()->after('grade');
            });
        }
    }

    private function backfillNumericGrades(): void
    {
        DB::table('course_grade_records')
            ->whereNull('grade')
            ->orderBy('id')
            ->get(['id', 'score', 'weight_label'])
            ->each(function (object $record): void {
                $grade = $this->numberFromString($record->score);
                $weight = $this->numberFromString($record->weight_label);

                DB::table('course_grade_records')
                    ->where('id', $record->id)
                    ->update([
                        'grade' => $grade === null ? null : max(5, min(10, $grade)),
                        'weight' => $weight === null ? null : (int) max(0, min(100, round($weight))),
                    ]);
            });
    }

    private function numberFromString(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        preg_match('/\d+(?:\.\d+)?/', (string) $value, $matches);

        return isset($matches[0]) ? (float) $matches[0] : null;
    }

    private function dropEnrollmentCacheColumns(): void
    {
        foreach (['current_grade', 'current_grade_points', 'attendance_percentage'] as $column) {
            if (Schema::hasColumn('student_enrollments', $column)) {
                Schema::table('student_enrollments', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('student_enrollments', 'next_important_event_id')) {
            try {
                Schema::table('student_enrollments', function (Blueprint $table): void {
                    $table->dropForeign(['next_important_event_id']);
                });
            } catch (Throwable) {
                // Some drivers, notably SQLite in tests, rebuild tables without explicit FK drops.
            }

            Schema::table('student_enrollments', function (Blueprint $table): void {
                $table->dropColumn('next_important_event_id');
            });
        }
    }

    private function dropStudentAcademicCacheColumns(): void
    {
        foreach ([
            'current_semester_label',
            'academic_year_label',
            'current_gpa',
            'credits_earned',
            'credits_required',
            'academic_standing',
        ] as $column) {
            if (Schema::hasColumn('students', $column)) {
                Schema::table('students', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function dropDeprecatedMockTables(): void
    {
        foreach ([
            'student_dashboard_attendance_summaries',
            'student_dashboard_attendance_warnings',
            'student_dashboard_latest_grades',
            'student_dashboard_deadlines',
            'student_dashboard_classes',
            'student_dashboard_metrics',
            'transcript_course_grades',
            'grade_distributions',
            'transcript_summaries',
            'transcript_semester_options',
            'attendance_last_recorded',
            'attendance_schedule_blocks',
            'attendance_schedule_days',
            'attendance_summaries',
            'attendance_weeks',
            'course_attendance_summaries',
            'course_info_items',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function restoreEnrollmentCacheColumns(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_enrollments', 'current_grade')) {
                $table->string('current_grade')->nullable();
            }

            if (! Schema::hasColumn('student_enrollments', 'current_grade_points')) {
                $table->decimal('current_grade_points', 4, 1)->nullable();
            }

            if (! Schema::hasColumn('student_enrollments', 'attendance_percentage')) {
                $table->unsignedTinyInteger('attendance_percentage')->default(0);
            }

            if (! Schema::hasColumn('student_enrollments', 'next_important_event_id')) {
                $table->foreignId('next_important_event_id')->nullable()->constrained('course_events')->nullOnDelete();
            }
        });
    }

    private function restoreStudentAcademicCacheColumns(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'current_semester_label')) {
                $table->string('current_semester_label')->nullable();
            }

            if (! Schema::hasColumn('students', 'academic_year_label')) {
                $table->string('academic_year_label')->nullable();
            }

            if (! Schema::hasColumn('students', 'current_gpa')) {
                $table->decimal('current_gpa', 4, 2)->nullable();
            }

            if (! Schema::hasColumn('students', 'credits_earned')) {
                $table->unsignedSmallInteger('credits_earned')->default(0);
            }

            if (! Schema::hasColumn('students', 'credits_required')) {
                $table->unsignedSmallInteger('credits_required')->nullable();
            }

            if (! Schema::hasColumn('students', 'academic_standing')) {
                $table->string('academic_standing')->nullable();
            }
        });
    }

    private function restoreDeprecatedMockTables(): void
    {
        if (! Schema::hasTable('course_info_items')) {
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
        }

        if (! Schema::hasTable('course_attendance_summaries')) {
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
        }

        if (! Schema::hasTable('attendance_weeks')) {
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
        }

        if (! Schema::hasTable('attendance_summaries')) {
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
        }

        if (! Schema::hasTable('attendance_schedule_days')) {
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
        }

        if (! Schema::hasTable('attendance_schedule_blocks')) {
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
        }

        if (! Schema::hasTable('attendance_last_recorded')) {
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
        }

        if (! Schema::hasTable('transcript_semester_options')) {
            Schema::create('transcript_semester_options', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->string('semester_code');
                $table->string('label');
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['student_id', 'semester_code']);
            });
        }

        if (! Schema::hasTable('transcript_summaries')) {
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
        }

        if (! Schema::hasTable('grade_distributions')) {
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
        }

        if (! Schema::hasTable('transcript_course_grades')) {
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
        }

        if (! Schema::hasTable('student_dashboard_metrics')) {
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
        }

        if (! Schema::hasTable('student_dashboard_classes')) {
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
        }

        if (! Schema::hasTable('student_dashboard_deadlines')) {
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
        }

        if (! Schema::hasTable('student_dashboard_latest_grades')) {
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
        }

        if (! Schema::hasTable('student_dashboard_attendance_warnings')) {
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
        }

        if (! Schema::hasTable('student_dashboard_attendance_summaries')) {
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
    }
};
