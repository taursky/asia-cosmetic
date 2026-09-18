@extends('layouts.app')

@section('title', 'Заказ оформлен — Asia Cosmetic')

@section('content')
<div class="bg-zinc-50 py-16">
    <div class="mx-auto max-w-2xl px-4 text-center">
        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-black/5">
            <div class="text-sm font-medium text-emerald-700">Заказ оформлен</div>
            <h1 class="mt-2 text-3xl font-semibold">{{ $order->number }}</h1>
            <p class="mt-4 text-sm text-zinc-500">Статус заказа и документы будут доступны в личном кабинете.</p>
            <div class="mt-6 text-2xl font-semibold">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</div>
            <a href="{{ route('account.index') }}" class="mt-8 inline-flex rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white">
                Перейти в личный кабинет
            </a>
        </div>
    </div>
</div>
@endsection
