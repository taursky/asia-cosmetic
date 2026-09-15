<?php

namespace App\Services\Auth;

use InvalidArgumentException;

class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('Некорректный номер телефона.');
        }

        $countryCode = (string) config('customer_auth.default_country_code', '7');

        if ($countryCode === '7' && strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = $countryCode . $digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            throw new InvalidArgumentException('Некорректный номер телефона.');
        }

        return '+' . $digits;
    }
}
