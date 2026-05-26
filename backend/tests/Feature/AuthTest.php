<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => 'test-jwt-secret',
            'jwt.issuer' => 'sems-test',
            'jwt.audience' => 'sems-test',
            'jwt.ttl_minutes' => 120,
        ]);
    }

    public function test_login_by_email(): void
    {
        $user = $this->createUser([
            'email' => 'student@example.com',
            'institution_id' => 'STU-1001',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => 'student@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->public_id)
            ->assertJsonPath('data.user.institutionId', 'STU-1001')
            ->assertJsonPath('data.redirectPath', '/student/dashboard')
            ->assertJsonStructure([
                'data' => ['token', 'user', 'redirectPath'],
            ]);
    }

    public function test_login_by_institution_id(): void
    {
        $user = $this->createUser([
            'institution_id' => 'STU-2002',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => 'STU-2002',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->public_id)
            ->assertJsonPath('data.redirectPath', '/student/dashboard');
    }

    public function test_failed_login_returns_generic_unauthorized_response(): void
    {
        $this->createUser([
            'email' => 'student@example.com',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'identifier' => 'student@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_session_returns_authenticated_user_for_valid_token(): void
    {
        $user = $this->createUser();
        $token = app(JwtService::class)->issue($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/session');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user.id', $user->public_id);
    }

    public function test_session_returns_unauthenticated_for_missing_or_invalid_token(): void
    {
        $this->getJson('/api/auth/session')
            ->assertOk()
            ->assertJsonPath('data.authenticated', false)
            ->assertJsonPath('data.user', null);

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/auth/session')
            ->assertOk()
            ->assertJsonPath('data.authenticated', false)
            ->assertJsonPath('data.user', null);
    }

    public function test_protected_student_route_blocks_unauthenticated_users(): void
    {
        $this->getJson('/api/student/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_protected_student_route_blocks_wrong_role(): void
    {
        $professor = $this->createUser([
            'role' => 'professor',
            'institution_id' => 'PROF-1001',
        ]);
        $token = app(JwtService::class)->issue($professor);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/dashboard')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('secret-password'),
        ], $attributes));
    }
}
