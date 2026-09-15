@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] bg-zinc-50 py-12">
<div class="mx-auto max-w-xl px-4 sm:px-6">
<div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">

<h1 class="text-2xl font-semibold">Восстановление пароля</h1>
<p class="mt-2 text-sm text-zinc-500">Отправим ссылку на email, привязанный к аккаунту.</p>
@if(session('status'))<div class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
<form method="post" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <input name="email" type="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-xl border-zinc-300" required>
    @error('email')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
    <button class="w-full rounded-full bg-[#071d5d] px-5 py-3 font-semibold text-white">Отправить ссылку</button>
</form>
</div></div></div>
@endsection
