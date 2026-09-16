<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AccountEmailVerificationService;
use App\Services\Auth\PhoneCodeService;
use App\Services\Auth\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('customerRoles');

//        return view('account.index', compact('user'));
        return view('account.vue', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Профиль обновлён.');
    }

    public function sendPhoneCode(
        Request $request,
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): RedirectResponse {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
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

        $codes->send(
            phone: $phone,
            purpose: 'account_phone:' . $request->user()->id,
        );

        return back()
            ->withInput(['phone' => $phone])
            ->with('phone_pending', $phone)
            ->with('success', 'Код подтверждения отправлен.');
    }

    public function verifyPhone(
        Request $request,
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): RedirectResponse {
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

        $request->user()->forceFill([
            'phone' => $verifiedPhone,
            'phone_verified_at' => now(),
        ])->save();

        return redirect()
            ->route('account.index')
            ->with('success', 'Телефон подтверждён. Теперь вы можете входить по SMS.');
    }

    public function sendEmailVerification(
        Request $request,
        AccountEmailVerificationService $verification,
    ): RedirectResponse {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $verification->send($request->user(), $data['email']);

        return back()->with(
            'success',
            'Мы отправили ссылку подтверждения на указанный email.',
        );
    }

    public function verifyEmail(
        Request $request,
        string $token,
        AccountEmailVerificationService $verification,
    ): RedirectResponse {
        $email = $verification->verify($request->user(), $token);

        return redirect()
            ->route('account.index')
            ->with('success', "Email {$email} подтверждён.");
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // Если пароль уже установлен, для его замены требуем текущий.
        if ($user->password) {
            $rules['current_password'] = ['required', 'current_password:web'];
        }

        $data = $request->validate($rules);

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return back()->with(
            'success',
            $user->wasChanged('password')
                ? 'Пароль сохранён.'
                : 'Пароль обновлён.',
        );
    }
}
