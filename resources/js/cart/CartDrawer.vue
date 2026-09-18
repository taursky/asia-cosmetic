<script setup>
import { cartStore, removeCartItem, updateCartItem } from './cart-store'

function money(value) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: cartStore.currency || 'RUB',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

async function change(item, delta) {
    await updateCartItem(item.id, Math.max(0, Number(item.quantity) + delta))
}
</script>

<template>
    <Teleport to="body">
        <div v-if="cartStore.open" class="fixed inset-0 z-[100]">
            <button
                class="absolute inset-0 bg-black/35 backdrop-blur-[1px]"
                aria-label="Закрыть корзину"
                @click="cartStore.open = false"
            />

            <aside class="absolute right-0 top-0 flex h-full w-[92vw] max-w-[520px] flex-col bg-white shadow-2xl">
                <header class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                    <div>
                        <div class="text-lg font-semibold text-zinc-950">Корзина</div>
                        <div class="text-xs text-zinc-400">{{ cartStore.count }} шт.</div>
                    </div>
                    <button type="button" class="grid size-9 place-items-center rounded-full hover:bg-zinc-100" @click="cartStore.open = false">✕</button>
                </header>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div v-if="cartStore.error" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                        {{ cartStore.error }}
                    </div>

                    <div v-if="!cartStore.items.length" class="py-16 text-center text-sm text-zinc-400">
                        Корзина пока пуста.
                    </div>

                    <div v-else class="divide-y divide-zinc-100">
                        <article v-for="item in cartStore.items" :key="item.id" class="py-5">
                            <div class="flex gap-4">
                                <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-zinc-50">
                                    <img v-if="item.image" :src="`/storage/${item.image}`" alt="" class="size-full object-contain p-2">
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="line-clamp-2 text-sm font-medium text-zinc-950">
                                        {{ item.product_name || item.variant_name || item.sku }}
                                    </div>

                                    <div class="mt-1 text-xs text-zinc-400">{{ item.sku }}</div>

                                    <div v-if="item.options?.length" class="mt-2 text-xs text-zinc-500">
                                        <span v-for="(option, index) in item.options" :key="index">
                                            {{ option.name }}: {{ option.value }}<span v-if="index < item.options.length - 1"> · </span>
                                        </span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <div class="flex items-center rounded-lg border border-zinc-200">
                                            <button type="button" class="px-3 py-2" @click="change(item, -1)">−</button>
                                            <span class="min-w-10 text-center text-sm">{{ item.quantity }}</span>
                                            <button type="button" class="px-3 py-2" @click="change(item, 1)">+</button>
                                        </div>

                                        <button type="button" class="text-xs font-medium text-red-600 hover:underline" @click="removeCartItem(item.id)">
                                            Удалить
                                        </button>
                                    </div>

                                    <div class="mt-3 flex items-baseline justify-between gap-3">
                                        <span class="text-xs text-zinc-400">{{ money(item.unit_price) }} / шт.</span>
                                        <span class="font-semibold text-zinc-950">{{ money(item.line_total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <footer v-if="cartStore.items.length" class="border-t border-zinc-100 p-5">
                    <div
                        v-if="cartStore.orderRole && cartStore.baseRole && cartStore.orderRole.id !== cartStore.baseRole.id"
                        class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800"
                    >
                        Для этого заказа применяется уровень цены <strong>{{ cartStore.orderRole.name }}</strong>.
                    </div>

                    <div v-if="cartStore.discountAmount > 0" class="mb-2 flex justify-between text-sm text-zinc-500">
                        <span>Экономия</span>
                        <span>− {{ money(cartStore.discountAmount) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-lg font-semibold text-zinc-950">
                        <span>Итого</span>
                        <span>{{ money(cartStore.total) }}</span>
                    </div>

                    <a href="/checkout" class="mt-4 flex w-full items-center justify-center rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white hover:bg-[#0d2e84]">
                        Оформить заказ
                    </a>
                </footer>
            </aside>
        </div>
    </Teleport>
</template>
