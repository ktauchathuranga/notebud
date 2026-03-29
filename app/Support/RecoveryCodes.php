<?php

namespace App\Support;

use App\Models\RecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RecoveryCodes
{
    private const CODE_SEGMENT_LENGTH = 4;

    private const CODE_SEGMENTS = 4;

    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function regenerateForUser(User $user, int $count = 10): array
    {
        return DB::transaction(function () use ($user, $count): array {
            RecoveryCode::query()->where('user_id', $user->id)->delete();

            $rawCodes = [];

            for ($i = 0; $i < $count; $i++) {
                $rawCode = self::generateCode();
                $rawCodes[] = $rawCode;

                RecoveryCode::query()->create([
                    'user_id' => $user->id,
                    'code_hash' => Hash::make(self::normalize($rawCode)),
                ]);
            }

            return $rawCodes;
        });
    }

    public static function consume(User $user, string $providedCode): bool
    {
        $normalizedCode = self::normalize($providedCode);

        if ($normalizedCode === '') {
            return false;
        }

        $candidate = RecoveryCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->get()
            ->first(function (RecoveryCode $code) use ($normalizedCode): bool {
                return Hash::check($normalizedCode, $code->code_hash);
            });

        if (! $candidate) {
            return false;
        }

        $candidate->forceFill(['used_at' => now()])->save();

        return true;
    }

    public static function normalize(string $code): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?? '';
    }

    private static function generateCode(): string
    {
        $alphabet = self::ALPHABET;
        $maxIndex = strlen($alphabet) - 1;
        $segments = [];

        for ($segment = 0; $segment < self::CODE_SEGMENTS; $segment++) {
            $part = '';

            for ($i = 0; $i < self::CODE_SEGMENT_LENGTH; $i++) {
                $part .= $alphabet[random_int(0, $maxIndex)];
            }

            $segments[] = $part;
        }

        return implode('-', $segments);
    }
}
