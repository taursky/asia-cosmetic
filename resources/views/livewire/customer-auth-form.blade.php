<div
    x-data="{
        init() {
            let saved = 'email';

            try {
                saved = localStorage.getItem('asia-cosmetic.auth-method') || 'email';
            } catch (e) {}

            if (saved === 'phone' || saved === 'email') {
                $wire.setMethod(saved);
            }
        },
        remember(method) {
            try {
                localStorage.setItem('asia-cosmetic.auth-method', method);
            } catch (e) {}
        }
    }"
    x-on:customer-auth-method-changed.window="remember($event.detail.method)"
>
    <div class="mb-5">
        <div class="text-xs font-medium uppercase tracking-[.16em] text-zinc-400">
            Asia Cosmetic
        </div>

        <div class="mt-1 text-xl font-semibold text-zinc-950">
            Вход в аккаунт
        </div>
    </div>

    @if($method === 'email')
        <section>
            <div class="mb-4">
                <div class="text-sm font-semibold text-zinc-900">
                    Вход по email
                </div>
                <p class="mt-1 text-xs leading-5 text-zinc-500">
                    Введите email и пароль от вашего аккаунта.
                </p>
            </div>

            <form wire:submit="loginByEmail" class="space-y-3">
                <div>
                    <input
                        wire:model.blur="email"
                        type="email"
                        autocomplete="email"
                        placeholder="Email"
                        class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition placeholder:text-zinc-400 focus:ring-2
                            @error('email')
                                border-red-300 bg-red-50/40 focus:border-red-400 focus:ring-red-100
                            @else
                                border-zinc-200 bg-white focus:border-[#071d5d] focus:ring-[#071d5d]/10
                            @enderror"
                    >

                    @error('email')
                        <p class="mt-1.5 text-xs leading-5 text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <input
                        wire:model="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Пароль"
                        class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition placeholder:text-zinc-400 focus:ring-2
                            @error('password')
                                border-red-300 bg-red-50/40 focus:border-red-400 focus:ring-red-100
                            @else
                                border-zinc-200 bg-white focus:border-[#071d5d] focus:ring-[#071d5d]/10
                            @enderror"
                    >

                    @error('password')
                        <p class="mt-1.5 text-xs leading-5 text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-xs text-zinc-500">
                        <input
                            wire:model="rememberEmail"
                            type="checkbox"
                            class="rounded border-zinc-300 text-[#071d5d] focus:ring-[#071d5d]"
                        >
                        Запомнить меня
                    </label>

                    <a
                        href="{{ url('/forgot-password') }}"
                        class="text-xs font-medium text-[#071d5d] hover:underline"
                    >
                        Забыли пароль?
                    </a>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="loginByEmail"
                    class="flex w-full items-center justify-center rounded-xl bg-[#071d5d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="loginByEmail">Войти</span>
                    <span wire:loading wire:target="loginByEmail">Проверяем...</span>
                </button>
            </form>

            <button
                type="button"
                wire:click="setMethod('phone')"
                class="mt-3 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-[#071d5d] hover:bg-blue-50 hover:text-[#071d5d]"
            >
                Войти по телефону
            </button>
        </section>
    @else
        <section>
            <div class="mb-4">
                <div class="text-sm font-semibold text-zinc-900">
                    Вход по телефону
                </div>
                <p class="mt-1 text-xs leading-5 text-zinc-500">
                    SMS отправляется только на номер, который уже добавлен и подтверждён в аккаунте.
                </p>
            </div>

            @if(! $phoneCodeSent)
                <form wire:submit="sendPhoneCode" class="space-y-3">
                    <div>
                        <input
                            wire:model.blur="phone"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            placeholder="+7 999 123-45-67"
                            class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition placeholder:text-zinc-400 focus:ring-2
                                @error('phone')
                                    border-red-300 bg-red-50/40 focus:border-red-400 focus:ring-red-100
                                @else
                                    border-zinc-200 bg-white focus:border-[#071d5d] focus:ring-[#071d5d]/10
                                @enderror"
                        >

                        @error('phone')
                            <p class="mt-1.5 text-xs leading-5 text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <label class="flex items-start gap-2 text-xs leading-5 text-zinc-500">
                        <input
                            wire:model="rememberPhone"
                            type="checkbox"
                            class="mt-0.5 rounded border-zinc-300 text-[#071d5d] focus:ring-[#071d5d]"
                        >
                        Запомнить вход на этом устройстве
                    </label>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="sendPhoneCode"
                        class="flex w-full items-center justify-center rounded-xl bg-[#071d5d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:cursor-wait disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="sendPhoneCode">Получить код по SMS</span>
                        <span wire:loading wire:target="sendPhoneCode">Проверяем номер...</span>
                    </button>
                </form>
            @else
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-800">
                    Код отправлен на {{ $phone }}.
                </div>

                <form wire:submit="verifyPhoneCode" class="space-y-3">
                    <div>
                        <input
                            wire:model="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="8"
                            placeholder="Код из SMS"
                            class="w-full rounded-xl border px-4 py-3 text-center text-lg tracking-[.3em] outline-none transition focus:ring-2
                                @error('code')
                                    border-red-300 bg-red-50/40 focus:border-red-400 focus:ring-red-100
                                @else
                                    border-zinc-200 bg-white focus:border-[#071d5d] focus:ring-[#071d5d]/10
                                @enderror"
                        >

                        @error('code')
                            <p class="mt-1.5 text-xs leading-5 text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @error('phone')
                        <p class="text-xs leading-5 text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="verifyPhoneCode"
                        class="flex w-full items-center justify-center rounded-xl bg-[#071d5d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:cursor-wait disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="verifyPhoneCode">Войти</span>
                        <span wire:loading wire:target="verifyPhoneCode">Проверяем код...</span>
                    </button>

                    <button
                        type="button"
                        wire:click="resendPhoneCode"
                        wire:loading.attr="disabled"
                        wire:target="resendPhoneCode"
                        class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-60"
                    >
                        Отправить код ещё раз
                    </button>
                </form>
            @endif

            <button
                type="button"
                wire:click="setMethod('email')"
                class="mt-3 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-[#071d5d] hover:bg-blue-50 hover:text-[#071d5d]"
            >
                Войти по email
            </button>
        </section>
    @endif

    <div class="mt-6 border-t border-zinc-100 pt-4 text-center text-sm text-zinc-500">
        Нет аккаунта?
        <a
            href="{{ url('/register') }}"
            class="font-semibold text-[#071d5d] hover:underline"
        >
            Зарегистрироваться
        </a>
    </div>
</div>
