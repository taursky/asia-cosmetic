<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerRole;
use App\Models\User;
use App\Services\Auth\PhoneCodeService;
use App\Services\Auth\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $this->assignRetailRole($user);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function phoneSend(Request $request, PhoneCodeService $codes, PhoneNormalizer $normalizer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $phone = $normalizer->normalize($data['phone']);

        if (User::query()->where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'Этот телефон уже зарегистрирован.'])->withInput();
        }

        $codes->send($phone, 'register');
        $request->session()->put('phone_register', [
            'name' => $data['name'],
            'phone' => $phone,
        ]);

        return redirect()->route('register.phone.verify.form')->with('status', 'Код отправлен по SMS.');
    }

    public function phoneVerifyForm(Request $request): View|RedirectResponse
    {
        $payload = $request->session()->get('phone_register');
        if (! is_array($payload)) {
            return redirect()->route('register');
        }

        return view('auth.phone-verify', [
            'phone' => $payload['phone'],
            'purpose' => 'register',
        ]);
    }

    public function phoneVerify(Request $request, PhoneCodeService $codes): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $payload = $request->session()->get('phone_register');

        if (! is_array($payload)) {
            return redirect()->route('register');
        }

        $phone = $codes->verify($payload['phone'], 'register', $data['code']);

        $user = User::query()->create([
            'name' => $payload['name'],
            'phone' => $phone,
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->assignRetailRole($user);
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('phone_register');

        return redirect()->route('home');
    }

    private function assignRetailRole(User $user): void
    {
        $role = CustomerRole::query()->where('code', 'retail')->first();
        if ($role) {
            $user->customerRoles()->syncWithoutDetaching([$role->id]);
        }
    }
}
