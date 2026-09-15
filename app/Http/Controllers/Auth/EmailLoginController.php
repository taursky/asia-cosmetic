<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Укажите email. Если email не добавлен к аккаунту, войдите по телефону и добавьте его в личном кабинете.',
            'email.email' => 'Проверьте email — адрес указан в неверном формате.',
            'password.required' => 'Введите пароль.',
        ]);

        $email = mb_strtolower(trim($data['email']));

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Аккаунт с таким email не найден. Если вы регистрировались по телефону, войдите по телефону и добавьте email в личном кабинете.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Этот аккаунт заблокирован. Обратитесь в поддержку.',
            ]);
        }

        if (! $user->password) {
            throw ValidationException::withMessages([
                'password' => 'Для этого аккаунта пароль ещё не создан. Войдите по телефону и задайте пароль в личном кабинете.',
            ]);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Неверный пароль.',
            ]);
        }

        Auth::guard('web')->login(
            $user,
            (bool) ($data['remember'] ?? false),
        );

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('home'));
    }
}
