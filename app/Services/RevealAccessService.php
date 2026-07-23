<?php

namespace App\Services;

use App\Models\RevealPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RevealAccessService
{
    public function issue(User $user, RevealPresentation $presentation): string
    {
        $token = Str::random(64);

        Cache::put($this->cacheKey($token), [
            'presentation_id' => $presentation->id,
            'version' => $presentation->version,
            'user_id' => $user->id,
        ], now()->addMinutes(config('reveal.token_ttl_minutes')));

        return $token;
    }

    /**
     * @return array{presentation_id: int, version: string, user_id: int}|null
     */
    public function resolve(string $token): ?array
    {
        if (strlen($token) !== 64 || ! ctype_alnum($token)) {
            return null;
        }

        $payload = Cache::get($this->cacheKey($token));

        return is_array($payload) ? $payload : null;
    }

    public function revoke(string $token): void
    {
        Cache::forget($this->cacheKey($token));
    }

    private function cacheKey(string $token): string
    {
        return 'reveal-access:'.hash('sha256', $token);
    }
}
