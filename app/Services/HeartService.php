<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HeartService
{
    public const MAX_HEARTS = 5;
    public const REGENERATION_SECONDS = 300;

    public function refresh(User $user): int
    {
        return $this->state($user)['hearts'];
    }

    public function state(User $user): array 
    {
        return DB::transaction(function () use ($user): array {
            $lockedUser = $this->lockUser($user);
            $now = Carbon::now();
            $hearts = $this->refreshLocked($lockedUser, $now);
            $gems = max(0, (int) $lockedUser->gems);
            $user->setAttribute('hearts', $hearts);
            $user->setAttribute('gems', $gems);
            $user->setAttribute('heart_regeneration_started_at', $lockedUser->heart_regeneration_started_at);
            $secondsToNextHeart = $hearts >= self::MAX_HEARTS
                ? 0
                : max(0, self::REGENERATION_SECONDS - max(
                    0,
                    $now->getTimestamp() - $lockedUser->heart_regeneration_started_at->getTimestamp()
                ));

            return [
                'hearts' => $hearts,
                'max_hearts' => self::MAX_HEARTS,
                'gems' => $gems,
                'regeneration_seconds' => self::REGENERATION_SECONDS,
                'seconds_to_next_heart' => $secondsToNextHeart,
                'seconds_to_full_refill' => $hearts >= self::MAX_HEARTS
                    ? 0
                    : $secondsToNextHeart + (self::MAX_HEARTS - $hearts - 1) * self::REGENERATION_SECONDS,
                'gem_refill_cost' => max(0, (int) config('gamification.heart_refill_gem_cost')),
            ];
        });
    }

    public function consume(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            $lockedUser = $this->lockUser($user);
            $hearts = $this->refreshLocked($lockedUser, Carbon::now());

            if ($hearts === 0) {
                return 0;
            }

            $lockedUser->hearts = $hearts - 1;
            if ($hearts === self::MAX_HEARTS) {
                $lockedUser->heart_regeneration_started_at = Carbon::now();
            }
            $lockedUser->save();

            $user->setAttribute('hearts', $lockedUser->hearts);
            $user->setAttribute('heart_regeneration_started_at', $lockedUser->heart_regeneration_started_at);

            return (int) $lockedUser->hearts;
        });
    }

    public function refillWithGems(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $lockedUser = $this->lockUser($user);
            $hearts = $this->refreshLocked($lockedUser, Carbon::now());
            $gems = max(0, (int) $lockedUser->gems);
            $cost = max(0, (int) config('gamification.heart_refill_gem_cost'));

            if ($hearts >= self::MAX_HEARTS) {
                return ['success' => false, 'reason' => 'hearts_full', 'hearts' => $hearts, 'gems' => $gems];
            }

            if ($gems < $cost) {
                return ['success' => false, 'reason' => 'insufficient_gems', 'hearts' => $hearts, 'gems' => $gems];
            }

            $lockedUser->gems = $gems - $cost;
            $lockedUser->hearts = self::MAX_HEARTS;
            $lockedUser->heart_regeneration_started_at = null;
            $lockedUser->save();

            $user->setAttribute('hearts', $lockedUser->hearts);
            $user->setAttribute('gems', $lockedUser->gems);
            $user->setAttribute('heart_regeneration_started_at', $lockedUser->heart_regeneration_started_at);

            return ['success' => true, 'hearts' => (int) $lockedUser->hearts, 'gems' => (int) $lockedUser->gems];
        });
    }

    private function lockUser(User $user): User
    {
        return User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
    }

    private function refreshLocked(User $user, Carbon $now): int
    {
        $hearts = max(0, min(self::MAX_HEARTS, (int) $user->hearts));
        $user->hearts = $hearts;

        if ($hearts >= self::MAX_HEARTS) {
            $user->heart_regeneration_started_at = null;
        } else {
            $startedAt = $user->heart_regeneration_started_at;

            if (!$startedAt) {
                $user->heart_regeneration_started_at = $now;
            } else {
                $elapsedSeconds = max(0, $now->getTimestamp() - $startedAt->getTimestamp());
                $regenerated = intdiv($elapsedSeconds, self::REGENERATION_SECONDS);

                if ($regenerated > 0) {
                    $hearts = min(self::MAX_HEARTS, $hearts + $regenerated);
                    $user->hearts = $hearts;
                    $user->heart_regeneration_started_at = $hearts === self::MAX_HEARTS
                        ? null
                        : $startedAt->copy()->addSeconds($regenerated * self::REGENERATION_SECONDS);
                }
            }
        }

        if ($user->isDirty()) {
            $user->save();
        }

        return (int) $user->hearts;
    }
}