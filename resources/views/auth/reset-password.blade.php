@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] bg-zinc-50 py-12">
<div class="mx-auto max-w-xl px-4 sm:px-6">
<div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">

<h1 class="text-2xl font-semibold">Новый пароль</h1>
<form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input name="email" type="email" value="{{ old('email', $email) }}" placeholder="Email" class="w-full rounded-xl border-zinc-300" required>
    <input name="password" type="password" placeholder="Новый пароль" class="w-full rounded-xl border-zinc-300" required>
    <input name="password_confirmation" type="password" placeholder="Повторите пароль" class="w-full rounded-xl border-zinc-300" required>
    @if($errors->any())<div class="text-sm text-rose-600">{{ $errors->first() }}</div>@endif
    <button class="w-full rounded-full bg-[#071d5d] px-5 py-3 font-semibold text-white">Сохранить пароль</button>
</form>
</div></div></div>
@endsection
