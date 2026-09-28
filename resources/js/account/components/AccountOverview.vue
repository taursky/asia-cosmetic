<script setup>
import { computed, onMounted, ref } from 'vue'

const data = ref(null)
const loading = ref(true)
const error = ref('')

const role = computed(() => data.value?.user?.customer_role || null)
const profile = computed(() => data.value?.user?.customer_profile || null)

function statusText(status) {
    return {
        draft: 'Черновик',
        pending: 'На проверке',
        verified: 'Подтверждены',
        rejected: 'Требуют исправления',
    }[status] || 'Не заполнены'
}

onMounted(async () => {
    try {
        const response = await fetch('/account/api/profile', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })

        const payload = await response.json().catch(() => ({}))

        if (!response.ok) throw new Error(payload.message || 'Не удалось загрузить профиль.')
        data.value = payload
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div v-if="loading" class="py-8 text-sm text-zinc-500">Загрузка...</div>
    <div v-else-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>

    <div v-else>
        <h2 class="text-2xl font-semibold text-zinc-950">Ваш аккаунт</h2>
        <p class="mt-1 text-sm text-zinc-500">Коммерческие условия и состояние профиля покупателя.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl bg-zinc-50 p-5">
                <div class="text-xs uppercase tracking-wide text-zinc-400">Уровень покупателя</div>
                <div class="mt-2 text-lg font-semibold text-zinc-950">{{ role?.name || 'Не назначен' }}</div>
                <div v-if="data?.user?.customer_role_valid_until" class="mt-1 text-xs text-zinc-500">
                    действует до {{ new Date(data.user.customer_role_valid_until).toLocaleDateString('ru-RU') }}
                </div>
            </div>

            <div class="rounded-2xl bg-zinc-50 p-5">
                <div class="text-xs uppercase tracking-wide text-zinc-400">Тип цены</div>
                <div class="mt-2 text-lg font-semibold text-zinc-950">{{ role?.price_type?.name || 'Не назначен' }}</div>
                <div v-if="role?.price_type?.code" class="mt-1 text-xs text-zinc-400">{{ role.price_type.code }}</div>
            </div>

            <div class="rounded-2xl bg-zinc-50 p-5">
                <div class="text-xs uppercase tracking-wide text-zinc-400">Реквизиты</div>
                <div class="mt-2 text-lg font-semibold text-zinc-950">{{ statusText(profile?.verification_status) }}</div>
                <div v-if="profile?.company_name" class="mt-1 text-xs text-zinc-500">{{ profile.company_name }}</div>
            </div>
        </div>
    </div>
</template>
