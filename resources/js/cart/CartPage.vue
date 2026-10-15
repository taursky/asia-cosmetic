<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import {
    cartStore,
    loadCart,
    updateCartItem,
    removeCartItem,
    toggleCartItemSelection,
    selectAllCartItems,
    clearCartSelection,
} from './cart-store'

onMounted(() => {
    loadCart()
})

const selectedItems = computed(() =>
    cartStore.items.filter(item =>
        cartStore.selectedItemIds.includes(Number(item.id))
    )
)

const allSelected = computed(() =>
    cartStore.items.length > 0 &&
    selectedItems.value.length === cartStore.items.length
)

const selectedTotal = computed(() =>
    selectedItems.value.reduce(
        (sum, item) => sum + Number(item.line_total ?? 0),
        0
    )
)

function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
    }).format(Number(value ?? 0))
}

async function changeQuantity(item, quantity) {
    const value = Number(quantity)

    if (!Number.isFinite(value) || value <= 0) {
        return
    }

    await updateCartItem(item.id, value)
}

async function deleteItem(item) {
    await removeCartItem(item.id)
}

const selectionPreview = ref(null)
const previewLoading = ref(false)
const previewError = ref(null)

const checkoutLoading = ref(false)
const checkoutError = ref(null)

async function checkoutSelected() {
    if (
        checkoutLoading.value ||
        previewLoading.value ||
        !selectionPreview.value ||
        cartStore.selectedItemIds.length === 0
    ) {
        return
    }

    checkoutLoading.value = true
    checkoutError.value = null

    try {
        const response = await fetch('/checkout/select-items', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content ?? '',
            },
            body: JSON.stringify({
                item_ids: cartStore.selectedItemIds,
            }),
        })

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}))

            throw new Error(
                payload.message ?? 'Не удалось перейти к оформлению заказа.'
            )
        }

        window.location.assign(response.url)
    } catch (error) {
        checkoutError.value = error.message
    } finally {
        checkoutLoading.value = false
    }
}

let previewRequestId = 0

watch(
    () => ({
        ids: [...cartStore.selectedItemIds],
        quantities: cartStore.items.map(item => [
            item.id,
            item.quantity,
        ]),
    }),
    async ({ ids }) => {
        const requestId = ++previewRequestId

        selectionPreview.value = null
        previewError.value = null

        if (ids.length === 0) {
            previewLoading.value = false
            return
        }

        previewLoading.value = true

        try {
            const response = await fetch('/api/cart/selection/preview', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content ?? '',
                },
                body: JSON.stringify({
                    item_ids: ids,
                }),
            })

            const payload = await response.json()

            if (!response.ok) {
                throw new Error(
                    payload.message ?? 'Не удалось рассчитать стоимость.'
                )
            }

            if (requestId === previewRequestId) {
                selectionPreview.value = payload
            }
        } catch (error) {
            if (requestId === previewRequestId) {
                previewError.value = error.message
            }
        } finally {
            if (requestId === previewRequestId) {
                previewLoading.value = false
            }
        }
    },
    { deep: true, immediate: true }
)
</script>

<template>
    <section class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="mb-6 text-3xl font-bold">
            Корзина
        </h1>

        <p v-if="cartStore.error" class="mb-4 text-red-600">
            {{ cartStore.error }}
        </p>

        <p v-if="cartStore.loading && !cartStore.items.length"
           class="text-gray-500">
            Загружаем корзину...
        </p>

        <p v-else-if="!cartStore.items.length" class="text-gray-500">
            Ваша корзина пуста.
        </p>

        <template v-else>
            <div class="mb-5 flex flex-wrap items-center gap-4 border-b pb-4">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        @change="$event.target.checked
                            ? selectAllCartItems()
                            : clearCartSelection()"
                    >
                    Выбрать всё
                </label>

                <button
                    type="button"
                    class="text-sm text-gray-600 underline"
                    @click="clearCartSelection"
                >
                    Снять выбор
                </button>

                <span class="ml-auto text-sm text-gray-500">
                    Выбрано: {{ selectedItems.length }}
                    из {{ cartStore.items.length }}
                </span>
            </div>

            <div
                v-for="item in cartStore.items"
                :key="item.id"
                class="flex flex-wrap items-center gap-5 border-b py-5"
            >
                <input
                    type="checkbox"
                    class="h-5 w-5"
                    :checked="cartStore.selectedItemIds.includes(Number(item.id))"
                    @change="toggleCartItemSelection(item.id)"
                >

                <div
                    class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100"
                >
                    <img
                        v-if="item.image"
                        :src="item.image"
                        :alt="item.product_name"
                        class="h-full w-full object-contain"
                    >

                    <span v-else class="text-xs text-gray-400">
                        Нет фото
                    </span>
                </div>

                <div class="min-w-48 flex-1">
                    <p class="font-medium">
                        {{ item.product_name }}
                    </p>

                    <p v-if="item.variant_name" class="mt-1 text-sm text-gray-500">
                        {{ item.variant_name }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Артикул: {{ item.sku ?? '—' }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        {{ item.price_type_name }}
                    </p>

                    <button
                        type="button"
                        class="mt-2 text-sm text-red-600 hover:underline"
                        :disabled="cartStore.loading"
                        @click="deleteItem(item)"
                    >
                        Удалить
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded border px-3 py-1"
                        :disabled="cartStore.loading || Number(item.quantity) <= 1"
                        @click="changeQuantity(item, Number(item.quantity) - 1)"
                    >
                        −
                    </button>

                    <span class="min-w-8 text-center">
                        {{ item.quantity }}
                    </span>

                    <button
                        type="button"
                        class="rounded border px-3 py-1"
                        :disabled="cartStore.loading || Number(item.quantity) >= Number(item.stock)"
                        @click="changeQuantity(item, Number(item.quantity) + 1)"
                    >
                        +
                    </button>
                </div>

                <div class="w-36 text-right">
                    <p class="font-semibold">
                        {{ formatPrice(item.line_total) }}
                    </p>

                    <p class="text-sm text-gray-600">
                        {{ formatPrice(item.unit_price) }} / шт.
                    </p>

                    <p v-if="item.old_price" class="text-sm text-gray-400 line-through">
                        {{ formatPrice(item.old_price) }}
                    </p>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <div class="w-full space-y-3">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Вся корзина</span>
                        <span>{{ formatPrice(cartStore.total) }}</span>
                    </div>

                    <div class="flex justify-between font-semibold">
                        <span>Выбрано товаров: {{ selectedItems.length }}</span>
                        <span v-if="previewLoading">
                            Пересчёт...
                        </span>
                        <span v-else-if="selectionPreview">
                            {{ formatPrice(selectionPreview.total) }}
                        </span>
                        <span v-else> — </span>

                        <p v-if="previewError" class="text-sm text-red-600">
                            {{ previewError }}
                        </p>
                        <p v-if="selectionPreview?.order_role" class="text-sm text-gray-500">
                            Ценовой уровень:
                            {{ selectionPreview.order_role.name }}
                        </p>

                    </div>

                    <p class="text-xs text-gray-500">
                        Предварительная сумма. При оформлении
                        сервер пересчитает цены выбранных товаров.
                    </p>

                    <button
                        type="button"
                        :disabled="
                            checkoutLoading ||
                            previewLoading ||
                            !selectionPreview ||
                            cartStore.selectedItemIds.length === 0
                        "
                        class="w-full rounded-lg bg-blue-900 px-6 py-3 font-medium text-white disabled:opacity-50"
                        @click="checkoutSelected"
                    >
                        {{ checkoutLoading ? 'Переходим к оформлению...' : 'Оформить выбранное' }}
                    </button>

                    <p v-if="checkoutError" class="text-sm text-red-600">
                        {{ checkoutError }}
                    </p>
                </div>
            </div>
        </template>
    </section>
</template>
