<script setup>
import { onMounted, ref } from 'vue'
const documents = ref([])
onMounted(async () => {
    const response = await fetch('/account/api/documents', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    const payload = await response.json()
    documents.value = payload.documents || []
})
</script>

<template>
    <div>
        <h2 class="text-2xl font-semibold text-zinc-950">Документы</h2>
        <p class="mt-1 text-sm text-zinc-500">Договоры, счета и другие доступные документы.</p>
        <div class="mt-6 divide-y divide-zinc-100">
            <div v-for="document in documents" :key="document.id" class="flex items-center justify-between gap-4 py-4">
                <div><div class="font-medium text-zinc-900">{{ document.title }}</div><div class="text-xs text-zinc-400">{{ document.type }}</div></div>
                <a :href="`/account/documents/${document.id}`" class="text-sm font-semibold text-[#071d5d]">Скачать</a>
            </div>
            <div v-if="!documents.length" class="py-8 text-sm text-zinc-400">Документов пока нет.</div>
        </div>
    </div>
</template>
