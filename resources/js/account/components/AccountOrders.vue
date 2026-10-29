<script setup>
import { onMounted, ref } from 'vue'

const orders = ref([])
const orderDocuments = ref({})
const loading = ref(true)
const error = ref('')
const expandedOrderId = ref(null)
const orderDetails = ref({})
const detailsLoading = ref(false)
const detailsError = ref('')
const orderStatuses = {
    new: 'Новый',
    pending: 'Ожидает обработки',
    awaiting_invoice: 'Ожидает выставления счёта',
    processing: 'В обработке',
    completed: 'Выполнен',
    cancelled: 'Отменён',
}
const paymentStatuses = {
    pending: 'Ожидает оплаты',
    paid: 'Оплачен',
    failed: 'Ошибка оплаты',
    refunded: 'Возврат средств',
}
const currentPage = ref(1)
const lastPage = ref(1)
const totalOrders = ref(0)
const activeFilter = ref('all')

const orderFilters = [
    { value: 'all', label: 'Все' },
    { value: 'unpaid', label: 'Ожидают оплаты' },
    { value: 'paid', label: 'Оплачены' },
    { value: 'cancelled', label: 'Отменены' },
]

function changeFilter(filter) {
    if (activeFilter.value === filter) return

    activeFilter.value = filter
    loadOrders(1)
}

function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
    }).format(Number(value ?? 0))
}

function formatDate(value) {
    return value
        ? new Date(value).toLocaleDateString('ru-RU')
        : '—'
}

function orderStatusLabel(status) {
    return orderStatuses[status] ?? status ?? '—'
}

function statusClass(status) {
    const classes = {
        new: 'bg-blue-50 text-blue-700',
        pending: 'bg-amber-50 text-amber-700',
        awaiting_invoice: 'bg-amber-50 text-amber-700',
        processing: 'bg-blue-50 text-blue-700',
        completed: 'bg-green-50 text-green-700',
        cancelled: 'bg-red-50 text-red-700',
        paid: 'bg-green-50 text-green-700',
        failed: 'bg-red-50 text-red-700',
        refunded: 'bg-zinc-100 text-zinc-700',
    }

    return classes[status] ?? 'bg-zinc-100 text-zinc-700'
}

function paymentStatusLabel(status) {
    return paymentStatuses[status] ?? status ?? '—'
}

async function loadOrders(page = 1) {
    loading.value = true
    error.value = ''

    try {
        const response = await fetch(
            `/account/api/orders?page=${page}&filter=${activeFilter.value}`,
            {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            }
        )

        if (!response.ok) {
            throw new Error('Не удалось загрузить заказы.')
        }

        const result = await response.json()

        orders.value = result.data ?? []
        currentPage.value = result.current_page ?? 1
        lastPage.value = result.last_page ?? 1
        totalOrders.value = result.total ?? 0

        expandedOrderId.value = null
        orderDocuments.value = {}
        const ids = orders.value.map(order => order.id)
        if (ids.length) {
            try {
                const params = new URLSearchParams()
                ids.forEach(id => params.append('ids[]', id))
                const documentsResponse = await fetch(`/account/api/orders/documents?${params}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                })
                if (documentsResponse.ok) {
                    const payload = await documentsResponse.json()
                    orderDocuments.value = payload.documents ?? {}
                }
            } catch (_) {
                // Orders remain visible if the document service is unavailable.
            }
        }
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

async function toggleOrder(orderId) {
    if (expandedOrderId.value === orderId) {
        expandedOrderId.value = null
        return
    }

    expandedOrderId.value = orderId
    detailsError.value = ''

    if (orderDetails.value[orderId]) {
        return
    }

    detailsLoading.value = true

    try {
        const response = await fetch(`/account/api/orders/${orderId}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })

        if (!response.ok) {
            throw new Error('Не удалось загрузить состав заказа.')
        }

        orderDetails.value[orderId] = await response.json()
    } catch (e) {
        detailsError.value = e.message
    } finally {
        detailsLoading.value = false
    }
}

onMounted(loadOrders)
</script>

<template>
    <section>
        <h2 class="mb-6 text-2xl font-bold text-zinc-900">
            Мои заказы
        </h2>

        <div class="mb-6 flex flex-wrap gap-2">
            <button
                v-for="filter in orderFilters"
                :key="filter.value"
                type="button"
                :disabled="loading"
                class="rounded-full border px-4 py-2 text-sm font-medium transition disabled:opacity-50"
                :class="
            activeFilter === filter.value
                ? 'border-zinc-900 bg-zinc-900 text-white'
                : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-400'
        "
                @click="changeFilter(filter.value)"
            >
                {{ filter.label }}
            </button>
        </div>



        <p v-if="loading" class="text-zinc-500">
            Загружаем заказы...
        </p>

        <p v-else-if="error" class="text-red-600">
            {{ error }}
        </p>

        <p v-else-if="!orders.length" class="text-zinc-500">
            {{
                activeFilter === 'all'
                    ? 'У вас пока нет заказов.'
                    : 'По выбранному фильтру заказов не найдено.'
            }}
        </p>

        <div v-else class="space-y-4">
            <div v-for="order in orders" :key="order.id" class="rounded-2xl border border-zinc-200 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-zinc-900">
                            Заказ №{{ order.number ?? order.id }}
                        </h3>

                        <p class="mt-1 text-sm text-zinc-500">
                            {{ formatDate(order.created_at) }}
                        </p>
                    </div>

                    <p class="text-lg font-bold text-zinc-900">
                        {{ formatPrice(order.total) }}
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-600">

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-zinc-500">Заказ:</span>

                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(order.status)">
                            {{ orderStatusLabel(order.status) }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-zinc-500">Оплата:</span>

                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(order.payment_status)">
                            {{ paymentStatusLabel(order.payment_status) }}
                        </span>
                    </div>


                    <span>
                        Позиций: {{ order.items_count }}
                    </span>
                </div>

                <div v-if="orderDocuments[order.id]?.length" class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
                    <span class="mr-1 text-sm font-medium text-zinc-600">Документы:</span>
                    <div v-for="document in orderDocuments[order.id]" :key="document.type" class="inline-flex items-center gap-1 rounded-xl border border-zinc-200 bg-zinc-50 p-1">
                        <span class="px-2 text-xs font-semibold text-zinc-700">{{ document.label }}</span>
                        <a :href="document.download_url" class="rounded-lg p-2 text-[#071d5d] hover:bg-white" :aria-label="`Скачать ${document.label.toLowerCase()}`" :title="`Скачать ${document.label.toLowerCase()}`">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m-4-4 4 4 4-4M4 17v3h16v-3"/></svg>
                        </a>
                        <a :href="document.print_url" target="_blank" rel="noopener noreferrer" class="rounded-lg p-2 text-[#071d5d] hover:bg-white" :aria-label="`Открыть для печати ${document.label.toLowerCase()}`" :title="`Открыть для печати ${document.label.toLowerCase()}`">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6zM18 12h.01"/></svg>
                        </a>
                    </div>
                </div>

                <div class="mt-4 border-t border-zinc-100 pt-4">
                    <button type="button" class="text-sm font-semibold text-zinc-900 hover:underline" @click="toggleOrder(order.id)">
                        {{ expandedOrderId === order.id ? 'Скрыть товары ↑' : 'Подробнее ↓' }}
                    </button>

                    <div v-if="expandedOrderId === order.id" class="mt-4">
                        <p v-if="detailsLoading" class="text-sm text-zinc-500">
                            Загружаем товары...
                        </p>

                        <p v-else-if="detailsError" class="text-sm text-red-600">
                            {{ detailsError }}
                        </p>

                        <div v-else-if="orderDetails[order.id]" class="space-y-3">
                            <div v-for="item in orderDetails[order.id].items"
                                :key="item.id"
                                class="flex flex-wrap items-start justify-between gap-3 rounded-xl bg-zinc-50 p-4">


                                <div class="flex min-w-0 flex-1 items-start gap-4">
                                    <img
                                        v-if="item.image"
                                        :src="item.image"
                                        :alt="item.name"
                                        loading="lazy"
                                        class="h-20 w-20 shrink-0 rounded-xl border border-zinc-200 bg-white object-contain"
                                    />

                                    <div
                                        v-else
                                        class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-xs text-zinc-400"
                                    >
                                        Нет фото
                                    </div>

                                    <div class="min-w-0">
                                        <p class="font-medium text-zinc-900">
                                            {{ item.name }}
                                        </p>

                                        <p class="mt-1 text-xs text-zinc-500">
                                            Артикул: {{ item.sku || '—' }}
                                        </p>

                                        <p class="mt-1 text-sm text-zinc-600">
                                            {{ item.quantity }} × {{ formatPrice(item.price) }}
                                        </p>
                                    </div>
                                </div>

                                <p class="font-semibold text-zinc-900">
                                    {{ formatPrice(item.total) }}
                                </p>
                            </div>

                            <div class="flex justify-between border-t border-zinc-200 pt-3">
                                <span class="font-semibold">Итого:</span>
                                <span class="font-bold">
                                    {{ formatPrice(orderDetails[order.id].total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div v-if="lastPage > 1" class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 pt-5">
                <p class="text-sm text-zinc-500">
                    Всего заказов: {{ totalOrders }}
                </p>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        :disabled="loading || currentPage === 1"
                        class="rounded-lg border border-zinc-200 px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                        @click="loadOrders(currentPage - 1)"
                    >
                        Назад
                    </button>

                    <span class="text-sm text-zinc-600">
                        {{ currentPage }} / {{ lastPage }}
                    </span>

                    <button
                        type="button"
                        :disabled="loading || currentPage === lastPage"
                        class="rounded-lg border border-zinc-200 px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                        @click="loadOrders(currentPage + 1)"
                    >
                        Вперёд
                    </button>
                </div>
            </div>

        </div>
    </section>
</template>
