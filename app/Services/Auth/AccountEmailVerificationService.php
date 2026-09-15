<?php

namespace App\Services\Auth;

use App\Models\AccountEmailVerification;
use App\Models\User;
use App\Notifications\VerifyAccountEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AccountEmailVerificationService
{
    public function send(User $user, string $email): void
    {
        $email = mb_strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Укажите корректный email.',
            ]);
        }

        $exists = User::query()
            ->where('email', $email)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => 'Этот email уже используется другим аккаунтом.',
            ]);
        }

        AccountEmailVerification::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $token = bin2hex(random_bytes(32));

        AccountEmailVerification::query()->create([
            'user_id' => $user->id,
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);

        $url = URL::temporarySignedRoute(
            'account.email.verify',
            now()->addMinutes(30),
            ['token' => $token],
        );

        $user->notify(new VerifyAccountEmail(
            email: $email,
            verificationUrl: $url,
        ));
    }

    public function verify(User $user, string $token): string
    {
        return DB::transaction(function () use ($user, $token): string {
            $verification = AccountEmailVerification::query()
                ->where('user_id', $user->id)
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $verification || $verification->isExpired()) {
                throw ValidationException::withMessages([
                    'email' => 'Ссылка подтверждения недействительна или истекла.',
                ]);
            }

            $exists = User::query()
                ->where('email', $verification->email)
                ->whereKeyNot($user->getKey())
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => 'Этот email уже используется другим аккаунтом.',
                ]);
            }

            $user->forceFill([
                'email' => $verification->email,
                'email_verified_at' => now(),
            ])->save();

            $verification->update([
                'consumed_at' => now(),
            ]);

            return $verification->email;
        });
    }
}
