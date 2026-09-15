@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] bg-zinc-50 py-12">
<div class="mx-auto max-w-xl px-4 sm:px-6">
<div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">

<h1 class="text-2xl font-semibold text-zinc-950">Регистрация</h1>
<p class="mt-2 text-sm text-zinc-500">Можно создать аккаунт по email или телефону.</p>
<div class="mt-8 grid gap-8 md:grid-cols-2">
    <form method="post" action="{{ route('register.email') }}" class="space-y-4">
        @csrf
        <h2 class="font-semibold">По email</h2>
        <input name="name" value="{{ old('name') }}" placeholder="Имя" class="w-full rounded-xl border-zinc-300" required>
        <input name="email" type="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-xl border-zinc-300" required>
        <input name="password" type="password" placeholder="Пароль" class="w-full rounded-xl border-zinc-300" required>
        <input name="password_confirmation" type="password" placeholder="Повторите пароль" class="w-full rounded-xl border-zinc-300" required>
        @if($errors->any())<div class="text-sm text-rose-600">{{ $errors->first() }}</div>@endif
        <button class="w-full rounded-full bg-[#071d5d] px-5 py-3 font-semibold text-white">Создать аккаунт</button>
    </form>
    <form method="post" action="{{ route('register.phone.send') }}" class="space-y-4">
        @csrf
        <h2 class="font-semibold">По телефону</h2>
        <input name="name" value="{{ old('name') }}" placeholder="Имя" class="w-full rounded-xl border-zinc-300" required>
        <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="+7 999 123-45-67" class="w-full rounded-xl border-zinc-300" required>
        @error('phone')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
        <button class="w-full rounded-full border border-[#071d5d] px-5 py-3 font-semibold text-[#071d5d]">Получить SMS-код</button>
    </form>
</div>
</div></div></div>
@endsection
