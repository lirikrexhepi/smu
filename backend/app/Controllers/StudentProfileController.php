<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTO\StudentProfileUpdateData;
use App\Services\StudentProfileService;
use App\Validators\StudentProfileAvatarValidator;
use App\Validators\StudentProfileUpdateValidator;

final class StudentProfileController
{
    public function __construct(
        private readonly StudentProfileService $profileService,
        private readonly StudentProfileUpdateValidator $validator,
        private readonly StudentProfileAvatarValidator $avatarValidator,
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        return Response::success(
            $this->profileService->profile($this->studentKey($request))->toArray(),
            'Student profile loaded',
            ['source' => 'mock-repository'],
        );
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): Response
    {
        $errors = $this->validator->validate($request->body());

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        return Response::success(
            $this->profileService
                ->updateProfile($this->studentKey($request), StudentProfileUpdateData::fromArray($request->body()))
                ->toArray(),
            'Student profile updated',
            ['source' => 'mock-repository', 'auditAction' => 'profile.update'],
        );
    }

    /**
     * @param array<string, string> $params
     */
    public function uploadAvatar(Request $request, array $params = []): Response
    {
        $avatar = $request->file('avatar');
        $errors = $this->avatarValidator->validate($avatar);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        if ($avatar === null) {
            return Response::error('Validation failed', 422, ['avatar' => ['Profile image is required.']]);
        }

        return Response::success(
            $this->profileService
                ->updateAvatar($this->studentKey($request), $avatar, $this->avatarValidator->detectedMimeType($avatar))
                ->toArray(),
            'Student profile image updated',
            ['source' => 'mock-repository', 'auditAction' => 'profile.avatar.update'],
        );
    }

    private function studentKey(Request $request): string
    {
        return (string) ($request->query()['studentKey'] ?? $request->header('x-sems-student-key', 'luri'));
    }
}
