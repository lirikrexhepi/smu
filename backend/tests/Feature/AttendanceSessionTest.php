<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AttendanceSessionTest extends TestCase
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

    public function test_professor_can_start_current_class_and_students_check_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $professorToken = $this->loginToken('PROF-1001');
        $studentOneToken = $this->loginToken('STU-1001');
        $studentTwoToken = $this->loginToken('STU-1002');

        $available = $this->withHeader('Authorization', 'Bearer '.$professorToken)
            ->getJson('/api/professor/attendance/available-classes')
            ->assertOk()
            ->assertJsonPath('data.0.courseId', 'ce-4-web')
            ->json('data.0');

        $sessionResponse = $this->withHeader('Authorization', 'Bearer '.$professorToken)
            ->postJson('/api/professor/attendance/sessions', [
                'courseId' => $available['courseId'],
                'courseScheduleId' => $available['courseScheduleId'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.courseId', 'ce-4-web')
            ->assertJsonPath('data.totalEnrolled', 2)
            ->assertJsonPath('data.pendingCount', 2)
            ->assertJsonStructure([
                'data' => ['id', 'checkInCode', 'qrToken', 'qrPayload', 'records'],
            ]);

        $sessionId = (string) $sessionResponse->json('data.id');
        $code = (string) $sessionResponse->json('data.checkInCode');
        $qrToken = (string) $sessionResponse->json('data.qrToken');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->withHeader('Authorization', 'Bearer '.$professorToken)
            ->postJson('/api/professor/attendance/sessions', [
                'courseId' => $available['courseId'],
                'courseScheduleId' => $available['courseScheduleId'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $sessionId);

        $this->assertSame(1, DB::table('attendance_sessions')->count());

        $this->withHeader('Authorization', 'Bearer '.$studentOneToken)
            ->postJson('/api/student/attendance/check-in', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.status', 'present')
            ->assertJsonPath('data.course.courseId', 'ce-4-web')
            ->assertJsonPath('data.professor.name', 'Dr. Arben Krasniqi');

        Carbon::setTestNow('2026-05-26 12:11:00');

        $this->withHeader('Authorization', 'Bearer '.$studentTwoToken)
            ->postJson('/api/student/attendance/check-in', ['qrToken' => $qrToken])
            ->assertOk()
            ->assertJsonPath('data.status', 'late');

        $this->withHeader('Authorization', 'Bearer '.$studentOneToken)
            ->postJson('/api/student/attendance/check-in', ['code' => $code])
            ->assertStatus(409)
            ->assertJsonPath('message', 'You have already checked in for this session.');

        $this->withHeader('Authorization', 'Bearer '.$professorToken)
            ->getJson('/api/professor/attendance/sessions/'.$sessionId)
            ->assertOk()
            ->assertJsonPath('data.presentCount', 1)
            ->assertJsonPath('data.lateCount', 1)
            ->assertJsonPath('data.checkedInCount', 2)
            ->assertJsonPath('data.records.0.status', 'present')
            ->assertJsonPath('data.records.1.status', 'late');

        Carbon::setTestNow('2026-05-26 13:31:00');

        $this->withHeader('Authorization', 'Bearer '.$studentOneToken)
            ->postJson('/api/student/attendance/check-in', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Attendance session has expired.');
    }

    public function test_professor_available_classes_only_uses_current_schedule(): void
    {
        $this->seed(DatabaseSeeder::class);

        Carbon::setTestNow('2026-05-26 17:00:00');

        $professorToken = $this->loginToken('PROF-1001');

        $this->withHeader('Authorization', 'Bearer '.$professorToken)
            ->getJson('/api/professor/attendance/available-classes')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeader('Authorization', 'Bearer '.$professorToken)
            ->postJson('/api/professor/attendance/sessions', [
                'courseId' => 'ce-4-web',
                'courseScheduleId' => DB::table('course_schedules')
                    ->join('courses', 'courses.id', '=', 'course_schedules.course_id')
                    ->where('courses.course_key', 'ce-4-web')
                    ->value('course_schedules.id'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This class is not active right now.');
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
