<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTO\StudentProfileUpdateData;
use App\Services\StudentProfileService;
use App\Validators\StudentProfileAvatarValidator;
use App\Validators\StudentProfileUpdateValidator;
use RuntimeException;

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
        $studentKey = $this->studentKey($request);

        if ($studentKey === null) {
            return Response::error('Student key is required', 422, [
                'studentKey' => ['Login as a student or provide studentKey.'],
            ]);
        }

        try {
            $profile = $this->profileService->profile($studentKey)->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student profile data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $profile,
            'Student profile loaded',
            ['source' => 'json-mock-repository', 'studentKey' => $studentKey],
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

        $studentKey = $this->studentKey($request);

        if ($studentKey === null) {
            return Response::error('Student key is required', 422, [
                'studentKey' => ['Login as a student or provide studentKey.'],
            ]);
        }

        try {
            $profile = $this->profileService
                ->updateProfile($studentKey, StudentProfileUpdateData::fromArray($request->body()))
                ->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student profile data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $profile,
            'Student profile updated',
            ['source' => 'json-mock-repository', 'auditAction' => 'profile.update', 'studentKey' => $studentKey],
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

        $studentKey = $this->studentKey($request);

        if ($studentKey === null) {
            return Response::error('Student key is required', 422, [
                'studentKey' => ['Login as a student or provide studentKey.'],
            ]);
        }

        try {
            $profile = $this->profileService
                ->updateAvatar($studentKey, $avatar, $this->avatarValidator->detectedMimeType($avatar))
                ->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student profile data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $profile,
            'Student profile image updated',
            ['source' => 'json-mock-repository', 'auditAction' => 'profile.avatar.update', 'studentKey' => $studentKey],
        );
    }

    private function studentKey(Request $request): ?string
    {
        $studentKey = (string) ($request->query()['studentKey'] ?? $request->header('x-sems-student-key', ''));
        $studentKey = trim($studentKey);

        return $studentKey === '' ? null : $studentKey;
    }
}
