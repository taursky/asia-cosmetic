<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AccountEmailVerificationService;
use App\Services\Auth\PhoneCodeService;
use App\Services\Auth\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AccountIdentityController extends Controller
{
    public function sendEmail(
        Request $request,
        AccountEmailVerificationService $verification,
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $verification->send($request->user(), $data['email']);

        return response()->json(['ok' => true]);
    }

    public function sendPhone(
        Request $request,
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): JsonResponse {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        try {
            $phone = $normalizer->normalize($data['phone']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'phone' => 'Проверьте формат номера телефона.',
            ]);
        }

        $exists = User::query()
            ->where('phone', $phone)
            ->whereKeyNot($request->user()->getKey())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'phone' => 'Этот номер телефона уже используется другим аккаунтом.',
            ]);
        }

        $codes->send(
            phone: $phone,
            purpose: 'account_phone:' . $request->user()->id,
        );

        return response()->json([
            'ok' => true,
            'phone' => $phone,
        ]);
    }

    public function verifyPhone(
        Request $request,
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): JsonResponse {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits_between:4,8'],
        ]);

        $phone = $normalizer->normalize($data['phone']);

        $exists = User::query()
            ->where('phone', $phone)
            ->whereKeyNot($request->user()->getKey())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'phone' => 'Этот номер телефона уже используется другим аккаунтом.',
            ]);
        }

        $verifiedPhone = $codes->verify(
            phone: $phone,
            purpose: 'account_phone:' . $request->user()->id,
            code: $data['code'],
        );

        $user = $request->user();
        $user->forceFill([
            'phone' => $verifiedPhone,
            'phone_verified_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'user' => $user->fresh()->load('customerRole.priceType'),
        ]);
    }
}
