<?php

namespace Tests\Feature;

use Database\Seeders\SemsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class StudentAttendanceTest extends TestCase
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

    public function test_student_attendance_returns_database_backed_contract(): void
    {
        $this->seed(SemsDemoSeeder::class);

        $token = $this->loginToken('STU-1001');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/attendance');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.studentKey', 'stu-demo-1001')
            ->assertJsonPath('data.selectedSemester', '4th Semester')
            ->assertJsonPath('data.week.startDate', '2026-05-25')
            ->assertJsonPath('data.week.endDate', '2026-05-29')
            ->assertJsonPath('data.summary.overallAttendance', 83)
            ->assertJsonPath('data.summary.totalSessions', 12)
            ->assertJsonPath('data.summary.comparisonVsLast4Weeks.label', 'higher than previous 4 weeks')
            ->assertJsonPath('data.lastRecorded.status', 'recorded')
            ->assertJsonCount(6, 'data.filters.courses')
            ->assertJsonCount(5, 'data.weeklySchedule')
            ->assertJsonCount(12, 'data.history')
            ->assertJsonStructure([
                'data' => [
                    'filters' => ['courses', 'semesters'],
                    'summary' => ['overallAttendance', 'presentSessions', 'totalSessions', 'absences', 'lateRecords', 'absenceRate', 'lateRate', 'comparisonVsLast4Weeks'],
                    'lastRecorded' => ['courseId', 'courseCode', 'courseName', 'date', 'dateLabel', 'time', 'status', 'statusLabel'],
                    'weeklySchedule' => [
                        '*' => [
                            'date',
                            'dayName',
                            'dayShort',
                            'dateLabel',
                            'isToday',
                            'blocks' => [
                                '*' => ['id', 'courseId', 'courseCode', 'courseName', 'professor', 'time', 'startTime', 'endTime', 'room', 'type', 'status', 'statusLabel', 'tone'],
                            ],
                        ],
                    ],
                    'history' => [
                        '*' => ['id', 'courseId', 'courseCode', 'courseName', 'date', 'dateIso', 'time', 'type', 'professor', 'result', 'resultLabel'],
                    ],
                ],
            ]);
    }

    public function test_student_attendance_filters_by_course_and_student(): void
    {
        $this->seed(SemsDemoSeeder::class);

        $firstStudentToken = $this->loginToken('STU-1001');
        $secondStudentToken = $this->loginToken('STU-1002');

        $this->withHeader('Authorization', 'Bearer '.$firstStudentToken)
            ->getJson('/api/student/attendance?courseId=ce-4-sec&semester=4th%20Semester')
            ->assertOk()
            ->assertJsonPath('data.studentKey', 'stu-demo-1001')
            ->assertJsonPath('data.selectedCourseId', 'ce-4-sec')
            ->assertJsonPath('data.summary.overallAttendance', 78)
            ->assertJsonPath('data.lastRecorded.courseId', 'ce-4-sec');

        $this->withHeader('Authorization', 'Bearer '.$secondStudentToken)
            ->getJson('/api/student/attendance?courseId=ce-4-sec&semester=4th%20Semester')
            ->assertOk()
            ->assertJsonPath('data.studentKey', 'stu-demo-1002')
            ->assertJsonPath('data.selectedCourseId', 'ce-4-sec')
            ->assertJsonPath('data.summary.overallAttendance', 70);
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
