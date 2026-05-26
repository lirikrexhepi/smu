<?php

namespace App\Services;

use App\Models\Identity\User;
use Illuminate\Support\Carbon;
use RuntimeException;

final class JwtService
{
    /**
     * @return array<string, mixed>
     */
    public function claimsFor(User $user): array
    {
        $issuedAt = Carbon::now()->timestamp;
        $expiresAt = Carbon::now()->addMinutes((int) config('jwt.ttl_minutes'))->timestamp;

        return [
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'sub' => (string) $user->id,
            'pid' => $user->public_id,
            'role' => $user->role,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];
    }

    public function issue(User $user): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];
        $claims = $this->claimsFor($user);
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));

        return $encodedHeader.'.'.$encodedPayload.'.'.$this->signature($encodedHeader, $encodedPayload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function validate(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = $this->decodeJson($encodedHeader);
        $payload = $this->decodeJson($encodedPayload);

        if (! is_array($header) || ! is_array($payload) || ($header['alg'] ?? null) !== 'HS256') {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->secret(), true);

        if (! hash_equals($expectedSignature, $this->base64UrlDecode($encodedSignature))) {
            return null;
        }

        $now = Carbon::now()->timestamp;

        if (($payload['iss'] ?? null) !== config('jwt.issuer')) {
            return null;
        }

        if (($payload['aud'] ?? null) !== config('jwt.audience')) {
            return null;
        }

        if (! isset($payload['sub'], $payload['pid'], $payload['role'], $payload['iat'], $payload['exp'])) {
            return null;
        }

        if (! is_numeric($payload['iat']) || ! is_numeric($payload['exp']) || (int) $payload['exp'] <= $now) {
            return null;
        }

        return $payload;
    }

    private function signature(string $encodedHeader, string $encodedPayload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->secret(), true));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $encoded): ?array
    {
        $json = $this->base64UrlDecode($encoded);

        if ($json === '') {
            return null;
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '') {
            throw new RuntimeException('JWT secret is not configured.');
        }

        return str_starts_with($secret, 'base64:')
            ? base64_decode(substr($secret, 7), true) ?: $secret
            : $secret;
    }
}
