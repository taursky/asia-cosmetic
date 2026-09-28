<script setup>
import { onMounted, ref } from 'vue'

const documents = ref([])
const loading = ref(true)
const error = ref('')

function typeText(type) {
    return {
        contract: 'Договор',
        invoice: 'Счёт',
        reconciliation: 'Акт сверки',
        other: 'Документ',
    }[type] || type
}

onMounted(async () => {
    try {
        const response = await fetch('/account/api/documents', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        const payload = await response.json().catch(() => ({}))
        if (!response.ok) throw new Error(payload.message || 'Не удалось загрузить документы.')
        documents.value = payload.documents || []
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div>
        <h2 class="text-2xl font-semibold text-zinc-950">Документы</h2>
        <p class="mt-1 text-sm text-zinc-500">Договоры, счета и другие документы, доступные вашему аккаунту.</p>

        <div v-if="loading" class="py-8 text-sm text-zinc-400">Загрузка...</div>
        <div v-else-if="error" class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>

        <div v-else class="mt-6 divide-y divide-zinc-100">
            <div v-for="document in documents" :key="document.id" class="flex items-center justify-between gap-4 py-4">
                <div class="min-w-0">
                    <div class="font-medium text-zinc-900">{{ document.title }}</div>
                    <div class="mt-1 text-xs text-zinc-400">{{ typeText(document.type) }}</div>
                </div>
                <a :href="document.download_url" class="shrink-0 text-sm font-semibold text-[#071d5d] hover:underline">Скачать</a>
            </div>
            <div v-if="!documents.length" class="py-8 text-sm text-zinc-400">Документов пока нет.</div>
        </div>
    </div>
</template>
