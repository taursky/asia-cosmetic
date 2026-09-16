<script setup>
import { onMounted, ref } from 'vue'
const data = ref(null)
const loading = ref(true)
onMounted(async () => {
    const response = await fetch('/account/api/profile', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    data.value = await response.json()
    loading.value = false
})
</script>

<template>
    <div v-if="loading" class="text-sm text-zinc-500">Загрузка...</div>
    <div v-else>
        <h2 class="text-2xl font-semibold text-zinc-950">Ваш аккаунт</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-zinc-50 p-5">
                <div class="text-xs uppercase tracking-wide text-zinc-400">Уровень цен</div>
                <div class="mt-2 text-lg font-semibold text-zinc-950">{{ data?.user?.customer_role?.name || 'Не назначен' }}</div>
            </div>
            <div class="rounded-2xl bg-zinc-50 p-5">
                <div class="text-xs uppercase tracking-wide text-zinc-400">Реквизиты</div>
                <div class="mt-2 text-lg font-semibold text-zinc-950">{{ data?.user?.customer_profile?.verification_status || 'Не заполнены' }}</div>
            </div>
        </div>
    </div>
</template>
