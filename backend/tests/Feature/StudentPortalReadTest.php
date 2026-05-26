<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StudentPortalReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-26 12:00:00');

        config([
            'jwt.secret' => 'test-jwt-secret',
            'jwt.issuer' => 'sems-test',
            'jwt.audience' => 'sems-test',
            'jwt.ttl_minutes' => 120,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_student_courses_overview_is_database_backed_and_filterable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $token = $this->loginToken('STU-1001');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/courses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.semester', '4th Semester')
            ->assertJsonPath('data.academicYear', '2025/2026')
            ->assertJsonPath('data.summary.enrolledCourses', 12)
            ->assertJsonPath('data.summary.totalEcts', 58)
            ->assertJsonPath('data.summary.statusCounts.active', 6)
            ->assertJsonPath('data.summary.upcomingDeadlines', 6)
            ->assertJsonCount(12, 'data.courses')
            ->assertJsonCount(6, 'data.upcomingDeadlines')
            ->assertJsonPath('data.courses.0.courseId', 'ce-4-web')
            ->assertJsonStructure([
                'data' => [
                    'filters' => ['semesters', 'statuses'],
                    'courses' => [
                        '*' => [
                            'courseId',
                            'code',
                            'name',
                            'professor',
                            'ects',
                            'schedule' => ['days', 'time', 'room', 'label'],
                            'room',
                            'semester',
                            'enrollmentStatus',
                            'enrollmentStatusLabel',
                            'currentGrade',
                            'currentGradePoints',
                            'attendancePercentage',
                            'nextImportantEvent',
                        ],
                    ],
                ],
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/courses?semester=4th%20Semester&status=active&search=security&sort=grade-desc')
            ->assertOk()
            ->assertJsonPath('data.summary.enrolledCourses', 1)
            ->assertJsonPath('data.courses.0.courseId', 'ce-4-sec')
            ->assertJsonPath('data.courses.0.attendancePercentage', 78);
    }

    public function test_course_detail_is_limited_to_authenticated_student_enrollment(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstStudentToken = $this->loginToken('STU-1001');
        $secondStudentToken = $this->loginToken('STU-1002');

        $this->withHeader('Authorization', 'Bearer '.$firstStudentToken)
            ->getJson('/api/student/courses/ce-4-sec')
            ->assertOk()
            ->assertJsonPath('data.courseId', 'ce-4-sec')
            ->assertJsonPath('data.code', 'CE210')
            ->assertJsonPath('data.professor.name', 'Dr. Arben Krasniqi')
            ->assertJsonPath('data.attendance.percentage', 78)
            ->assertJsonPath('data.grades.currentGradePoints', '7')
            ->assertJsonCount(2, 'data.materials')
            ->assertJsonCount(1, 'data.assessments')
            ->assertJsonCount(1, 'data.exams')
            ->assertJsonCount(2, 'data.announcements')
            ->assertJsonCount(3, 'data.grades.records')
            ->assertJsonCount(8, 'data.attendance.records');

        $this->withHeader('Authorization', 'Bearer '.$secondStudentToken)
            ->getJson('/api/student/courses/ce-4-sec')
            ->assertOk()
            ->assertJsonPath('data.courseId', 'ce-4-sec')
            ->assertJsonPath('data.attendance.percentage', 70)
            ->assertJsonPath('data.grades.currentGradePoints', '6');

        $this->withHeader('Authorization', 'Bearer '.$firstStudentToken)
            ->getJson('/api/student/courses/not-a-course')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_grades_transcript_returns_student_rows_and_filters(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstStudentToken = $this->loginToken('STU-1001');
        $secondStudentToken = $this->loginToken('STU-1002');

        $this->withHeader('Authorization', 'Bearer '.$firstStudentToken)
            ->getJson('/api/student/grades-transcript')
            ->assertOk()
            ->assertJsonPath('data.studentKey', 'stu-demo-1001')
            ->assertJsonPath('data.selectedSemester', 'all')
            ->assertJsonPath('data.summary.averageGrade', 8.67)
            ->assertJsonCount(3, 'data.filters.semesters')
            ->assertJsonCount(12, 'data.filters.courses')
            ->assertJsonCount(12, 'data.courseGrades')
            ->assertJsonPath('data.courseGrades.4.courseId', 'ce-4-sec')
            ->assertJsonPath('data.courseGrades.4.numericGrade', 7);

        $this->withHeader('Authorization', 'Bearer '.$firstStudentToken)
            ->getJson('/api/student/grades-transcript?semester=sem-3&courseId=ce-3-dsa')
            ->assertOk()
            ->assertJsonPath('data.selectedSemester', 'sem-3')
            ->assertJsonPath('data.selectedCourseId', 'ce-3-dsa')
            ->assertJsonCount(1, 'data.courseGrades')
            ->assertJsonPath('data.courseGrades.0.numericGrade', 10)
            ->assertJsonPath('data.courseGrades.0.status', 'passed');

        $this->withHeader('Authorization', 'Bearer '.$secondStudentToken)
            ->getJson('/api/student/grades-transcript?semester=sem-4&courseId=ce-4-sec')
            ->assertOk()
            ->assertJsonPath('data.studentKey', 'stu-demo-1002')
            ->assertJsonPath('data.courseGrades.0.numericGrade', 6);
    }

    public function test_grade_record_change_propagates_to_dashboard_transcript_and_course_detail(): void
    {
        $this->seed(DatabaseSeeder::class);

        $token = $this->loginToken('STU-1001');
        $enrollmentId = DB::table('student_enrollments')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->join('courses', 'courses.id', '=', 'student_enrollments.course_id')
            ->where('users.institution_id', 'STU-1001')
            ->where('courses.course_key', 'ce-4-sec')
            ->value('student_enrollments.id');

        DB::table('course_grade_records')
            ->where('student_enrollment_id', $enrollmentId)
            ->where('grade_key', 'midterm')
            ->update(['grade' => 10]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.metrics.0.value', '8.9')
            ->assertJsonPath('data.latestGrades.1.course', 'CE210')
            ->assertJsonPath('data.latestGrades.1.grade', '10 Excellent');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/grades-transcript?semester=all&courseId=ce-4-sec')
            ->assertOk()
            ->assertJsonPath('data.summary.averageGrade', 8.64)
            ->assertJsonPath('data.gradeDistribution.1.grade', 9)
            ->assertJsonPath('data.gradeDistribution.1.count', 1)
            ->assertJsonPath('data.courseGrades.0.numericGrade', 8.64);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/courses/ce-4-sec')
            ->assertOk()
            ->assertJsonPath('data.grades.currentGradePoints', '8.6')
            ->assertJsonPath('data.grades.records.0.score', '10/10');
    }

    private function loginToken(string $identifier): string
    {
        $response = $this->postJson('/api/auth/login', [
            'identifier' => $identifier,
            'password' => 'password',
        ]);

        $response->assertOk();

        return (string) $response->json('data.token');
    }
}
