@extends('layouts.app')

@section('title', 'Личный кабинет — Asia Cosmetic')

@section('content')
<div class="min-h-[70vh] bg-zinc-50 py-8 sm:py-12">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="text-xs font-semibold uppercase tracking-[.18em] text-[#071d5d]">
                Asia Cosmetic
            </div>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">
                Личный кабинет
            </h1>
            <p class="mt-2 text-sm text-zinc-500">
                Управляйте профилем и способами входа в аккаунт.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                <h2 class="text-lg font-semibold text-zinc-950">Профиль</h2>

                <form method="post" action="{{ route('account.profile.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Имя</label>
                        <input
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                        >
                    </div>

                    <button class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white hover:bg-[#0d2e84]">
                        Сохранить
                    </button>
                </form>

                @if($user->customerRoles->isNotEmpty())
                    <div class="mt-6 border-t border-zinc-100 pt-5">
                        <div class="text-xs font-medium uppercase tracking-[.14em] text-zinc-400">Тип покупателя</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($user->customerRoles as $role)
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-[#071d5d]">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                <h2 class="text-lg font-semibold text-zinc-950">Способы входа</h2>
                <p class="mt-1 text-sm leading-6 text-zinc-500">
                    После подтверждения можно входить и по email, и по телефону.
                </p>

                <div class="mt-6 space-y-6">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-zinc-900">Email</div>
                                @if($user->email)
                                    <div class="mt-1 break-all text-sm text-zinc-600">{{ $user->email }}</div>
                                    <div class="mt-1 text-xs {{ $user->email_verified_at ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ $user->email_verified_at ? 'Подтверждён' : 'Не подтверждён' }}
                                    </div>
                                @else
                                    <div class="mt-1 text-sm text-zinc-400">Не добавлен</div>
                                @endif
                            </div>
                        </div>

                        <form method="post" action="{{ route('account.email.send') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                            @csrf
                            <input
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                placeholder="you@example.com"
                                class="min-w-0 flex-1 rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                            >
                            <button class="rounded-xl border border-[#071d5d] px-4 py-3 text-sm font-semibold text-[#071d5d] hover:bg-blue-50">
                                {{ $user->email ? 'Изменить / подтвердить' : 'Добавить email' }}
                            </button>
                        </form>
                    </div>

                    <div class="border-t border-zinc-100 pt-6">
                        <div class="text-sm font-semibold text-zinc-900">Телефон</div>

                        @if($user->phone)
                            <div class="mt-1 text-sm text-zinc-600">{{ $user->phone }}</div>
                            <div class="mt-1 text-xs {{ $user->phone_verified_at ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $user->phone_verified_at ? 'Подтверждён' : 'Не подтверждён' }}
                            </div>
                        @else
                            <div class="mt-1 text-sm text-zinc-400">Не добавлен</div>
                        @endif

                        <form method="post" action="{{ route('account.phone.send') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                            @csrf
                            <input
                                name="phone"
                                type="tel"
                                value="{{ old('phone', session('phone_pending', $user->phone)) }}"
                                required
                                placeholder="+7 999 123-45-67"
                                class="min-w-0 flex-1 rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                            >
                            <button class="rounded-xl border border-[#071d5d] px-4 py-3 text-sm font-semibold text-[#071d5d] hover:bg-blue-50">
                                Получить SMS-код
                            </button>
                        </form>

                        @if(session('phone_pending') || old('code'))
                            <form method="post" action="{{ route('account.phone.verify') }}" class="mt-3 grid gap-3 sm:grid-cols-[1fr_180px_auto]">
                                @csrf
                                <input
                                    name="phone"
                                    type="hidden"
                                    value="{{ session('phone_pending', old('phone')) }}"
                                >
                                <input
                                    disabled
                                    value="{{ session('phone_pending', old('phone')) }}"
                                    class="rounded-xl border border-zinc-100 bg-zinc-50 px-4 py-3 text-sm text-zinc-500"
                                >
                                <input
                                    name="code"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    required
                                    placeholder="Код"
                                    class="rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                                >
                                <button class="rounded-xl bg-[#071d5d] px-4 py-3 text-sm font-semibold text-white">
                                    Подтвердить
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:col-span-2">
                <h2 class="text-lg font-semibold text-zinc-950">
                    {{ $user->password ? 'Изменить пароль' : 'Создать пароль' }}
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Пароль нужен для входа по email. Вход по SMS будет работать независимо от него.
                </p>

                <form method="post" action="{{ route('account.password.update') }}" class="mt-5 grid gap-4 md:grid-cols-3">
                    @csrf
                    @method('PUT')

                    @if($user->password)
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-zinc-700">Текущий пароль</label>
                            <input
                                name="current_password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                            >
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Новый пароль</label>
                        <input
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Повторите пароль</label>
                        <input
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                        >
                    </div>

                    <div class="md:col-span-3">
                        <button class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white hover:bg-[#0d2e84]">
                            Сохранить пароль
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
