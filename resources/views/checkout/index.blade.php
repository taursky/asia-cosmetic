@extends('layouts.app')

@section('title', 'Оформление заказа — Asia Cosmetic')

@section('content')
<div class="bg-zinc-50 py-8 sm:py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">Оформление заказа</h1>

        @if($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('checkout.store') }}" class="mt-8 grid gap-6 lg:grid-cols-[1fr_380px]">
            @csrf

            <div class="space-y-6">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                    <h2 class="text-lg font-semibold">Покупатель</h2>
                    <div class="mt-4 text-sm text-zinc-600">{{ $user->name }}</div>
                    <div class="text-sm text-zinc-500">{{ $user->email ?: $user->phone }}</div>

                    @if(($cart['order_role']['level'] ?? 0) > 0 && (!$user->customerProfile || $user->customerProfile->verification_status !== 'verified'))
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Для оптового заказа требуется подтверждение реквизитов.
                            <a href="{{ route('account.index') }}" class="font-semibold underline">Заполнить реквизиты</a>
                        </div>
                    @endif
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                    <h2 class="text-lg font-semibold">Доставка</h2>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="rounded-2xl border border-zinc-200 p-4">
                            <input type="radio" name="delivery_method" value="pickup" @checked(old('delivery_method', 'pickup') === 'pickup')>
                            <span class="ml-2 text-sm font-medium">Самовывоз</span>
                        </label>
                        <label class="rounded-2xl border border-zinc-200 p-4">
                            <input type="radio" name="delivery_method" value="delivery" @checked(old('delivery_method') === 'delivery')>
                            <span class="ml-2 text-sm font-medium">Доставка</span>
                        </label>
                    </div>

                    <textarea name="delivery_address" rows="3" placeholder="Адрес доставки" class="mt-4 w-full rounded-xl border-zinc-200">{{ old('delivery_address') }}</textarea>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                    <h2 class="text-lg font-semibold">Оплата</h2>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="rounded-2xl border border-zinc-200 p-4">
                            <input type="radio" name="payment_method" value="invoice" @checked(old('payment_method', 'invoice') === 'invoice')>
                            <span class="ml-2 text-sm font-medium">Счёт на оплату</span>
                        </label>
                        <label class="rounded-2xl border border-zinc-200 p-4">
                            <input type="radio" name="payment_method" value="card" @checked(old('payment_method') === 'card')>
                            <span class="ml-2 text-sm font-medium">Банковская карта</span>
                        </label>
                    </div>

                    <textarea name="comment" rows="3" placeholder="Комментарий" class="mt-4 w-full rounded-xl border-zinc-200">{{ old('comment') }}</textarea>
                </section>
            </div>

            <aside class="h-fit rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:sticky lg:top-28">
                <h2 class="text-lg font-semibold">Ваш заказ</h2>

                <div class="mt-5 divide-y divide-zinc-100">
                    @foreach($cart['items'] as $item)
                        <div class="flex justify-between gap-4 py-3 text-sm">
                            <div class="min-w-0">
                                <div class="line-clamp-2 font-medium">{{ $item['product_name'] ?: $item['sku'] }}</div>
                                <div class="mt-1 text-xs text-zinc-400">{{ $item['quantity'] }} × {{ number_format($item['unit_price'], 0, ',', ' ') }} ₽</div>
                            </div>
                            <div class="shrink-0 font-medium">{{ number_format($item['line_total'], 0, ',', ' ') }} ₽</div>
                        </div>
                    @endforeach
                </div>

                @if($cart['discount_amount'] > 0)
                    <div class="mt-4 flex justify-between text-sm text-emerald-700">
                        <span>Экономия</span>
                        <span>− {{ number_format($cart['discount_amount'], 0, ',', ' ') }} ₽</span>
                    </div>
                @endif

                <div class="mt-4 flex justify-between border-t border-zinc-100 pt-4 text-lg font-semibold">
                    <span>Итого</span>
                    <span>{{ number_format($cart['total'], 0, ',', ' ') }} ₽</span>
                </div>

                <button type="submit" class="mt-5 w-full rounded-xl bg-[#071d5d] px-5 py-4 text-sm font-semibold text-white">
                    Оформить заказ
                </button>
            </aside>
        </form>
    </div>
</div>
@endsection
