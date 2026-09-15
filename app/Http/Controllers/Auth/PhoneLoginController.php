<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PhoneCodeService;
use App\Services\Auth\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class PhoneLoginController extends Controller
{
    public function send(
        Request $request,
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): RedirectResponse {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'phone.required' => 'Укажите номер телефона. Если телефон не добавлен к аккаунту, войдите по email и добавьте его в личном кабинете.',
        ]);

        try {
            $phone = $normalizer->normalize($data['phone']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'phone' => 'Проверьте номер телефона. Используйте формат +7 999 123-45-67.',
            ]);
        }

        $user = User::query()
            ->where('phone', $phone)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'Аккаунт с таким номером не найден. Если вы регистрировались по email, войдите по email и добавьте номер телефона в личном кабинете.',
            ]);
        }

        if (! $user->phone_verified_at) {
            throw ValidationException::withMessages([
                'phone' => 'Этот номер ещё не подтверждён. Войдите по email и подтвердите телефон в личном кабинете.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'phone' => 'Этот аккаунт заблокирован. Обратитесь в поддержку.',
            ]);
        }

        // Критично: SMS отправляем только после проверки существования,
        // подтверждения номера и активности аккаунта.
        $codes->send($phone, 'login');

        return redirect()
            ->route('login.phone.verify')
            ->with('phone', $phone)
            ->with('remember_phone', (bool) ($data['remember'] ?? false));
    }

    public function verifyForm(Request $request): View
    {
        return view('auth.phone-verify', [
            'phone' => session('phone'),
        ]);
    }

    public function verify(
        Request $request,
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): RedirectResponse {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits_between:4,8'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $phone = $normalizer->normalize($data['phone']);

        $user = User::query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->where('is_active', true)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'Аккаунт с этим подтверждённым номером не найден.',
            ]);
        }

        $codes->verify($phone, 'login', $data['code']);

        Auth::guard('web')->login(
            $user,
            (bool) ($data['remember'] ?? session('remember_phone', false)),
        );

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('home'));
    }
}
