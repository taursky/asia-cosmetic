<?php

namespace App\Services\Auth;

use App\Contracts\SmsSender;
use App\Models\PhoneAuthCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PhoneCodeService
{
    public function __construct(private readonly PhoneNormalizer $normalizer, private readonly SmsSender $sms,) {}

    public function send(string $phone, string $purpose): string
    {
        $phone = $this->normalizer->normalize($phone);
        $key = 'sms-send:' . sha1($purpose . '|' . $phone);
        $seconds = (int) config('customer_auth.sms.resend_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw ValidationException::withMessages([
                'phone' => 'Код уже отправлен. Повторите запрос немного позже.',
            ]);
        }

        RateLimiter::hit($key, $seconds);

        PhoneAuthCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $length = (int) config('customer_auth.sms.code_length', 6);
        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;
        //todo: для теста.
        $code = '101010';//(string) random_int($min, $max);

        PhoneAuthCode::query()->create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('customer_auth.sms.ttl_minutes', 5)),
        ]);

        $this->sms->send($phone, "Код Asia Cosmetic: {$code}");

        return $phone;
    }

    public function verify(string $phone, string $purpose, string $code): string
    {
        $phone = $this->normalizer->normalize($phone);

        $record = PhoneAuthCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $record || $record->isExpired()) {
            throw ValidationException::withMessages(['code' => 'Код истёк или не найден.']);
        }

        $maxAttempts = (int) config('customer_auth.sms.max_attempts', 5);
        if ($record->attempts >= $maxAttempts) {
            throw ValidationException::withMessages(['code' => 'Превышено количество попыток. Запросите новый код.']);
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');
            throw ValidationException::withMessages(['code' => 'Неверный код.']);
        }

        $record->update(['consumed_at' => now()]);

        return $phone;
    }
}
