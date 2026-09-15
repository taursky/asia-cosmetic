<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\Auth\PhoneCodeService;
use App\Services\Auth\PhoneNormalizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerAuthForm extends Component
{
    public string $method = 'email';

    public string $email = '';
    public string $password = '';
    public bool $rememberEmail = false;

    public string $phone = '';
    public string $code = '';
    public bool $rememberPhone = false;
    public bool $phoneCodeSent = false;

    public function mount(): void
    {
        $this->method = 'email';
    }

    public function setMethod(string $method): void
    {
        if (! in_array($method, ['email', 'phone'], true)) {
            return;
        }

        $this->resetValidation();
        $this->method = $method;

        $this->dispatch('customer-auth-method-changed', method: $method);
    }

    public function loginByEmail()
    {
        $data = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Укажите email. Если email не добавлен к аккаунту, войдите по телефону и добавьте его в личном кабинете.',
            'email.email' => 'Проверьте email — адрес указан в неверном формате.',
            'password.required' => 'Введите пароль. Если пароль ещё не создан, войдите по телефону и задайте его в личном кабинете.',
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
                'password' => 'Для этого аккаунта ещё не установлен пароль. Войдите по телефону и создайте пароль в личном кабинете.',
            ]);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Неверный пароль.',
            ]);
        }

        Auth::guard('web')->login($user, $this->rememberEmail);

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function sendPhoneCode(
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): void {
        $this->validate([
            'phone' => ['required', 'string', 'max:32'],
        ], [
            'phone.required' => 'Укажите номер телефона. Если телефон не добавлен к аккаунту, войдите по email и добавьте его в личном кабинете.',
        ]);

        try {
            $phone = $normalizer->normalize($this->phone);
        } catch (\InvalidArgumentException) {
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

        // SMS отправляем ТОЛЬКО после проверки пользователя.
        $this->phone = $codes->send(
            phone: $phone,
            purpose: 'login',
        );

        $this->phoneCodeSent = true;
        $this->code = '';
        $this->resetValidation();

        $this->dispatch('customer-auth-code-sent');
    }

    public function verifyPhoneCode(
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ) {
        $this->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'digits_between:4,8'],
        ], [
            'phone.required' => 'Номер телефона не указан.',
            'code.required' => 'Введите код из SMS.',
            'code.digits_between' => 'Код должен состоять только из цифр.',
        ]);

        $phone = $normalizer->normalize($this->phone);

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

        $codes->verify(
            phone: $phone,
            purpose: 'login',
            code: $this->code,
        );

        Auth::guard('web')->login($user, $this->rememberPhone);

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function resendPhoneCode(
        PhoneNormalizer $normalizer,
        PhoneCodeService $codes,
    ): void {
        $this->sendPhoneCode($normalizer, $codes);
    }

    public function render()
    {
        return view('livewire.customer-auth-form');
    }
}
