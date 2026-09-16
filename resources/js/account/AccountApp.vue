<script setup>
import { ref } from 'vue'
import AccountOverview from './components/AccountOverview.vue'
import CompanyDetails from './components/CompanyDetails.vue'
import AccountDocuments from './components/AccountDocuments.vue'

const section = ref('overview')
const sections = [
    { key: 'overview', title: 'Обзор' },
    { key: 'company', title: 'Реквизиты' },
    { key: 'orders', title: 'Заказы' },
    { key: 'documents', title: 'Документы' },
    { key: 'security', title: 'Безопасность' },
]
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[250px_minmax(0,1fr)]">
        <aside class="h-fit rounded-3xl bg-white p-3 shadow-sm ring-1 ring-black/5">
            <button
                v-for="item in sections"
                :key="item.key"
                type="button"
                class="w-full rounded-2xl px-4 py-3 text-left text-sm font-medium transition"
                :class="section === item.key ? 'bg-[#071d5d] text-white' : 'text-zinc-700 hover:bg-zinc-50'"
                @click="section = item.key"
            >
                {{ item.title }}
            </button>
        </aside>

        <main class="min-w-0 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-7">
            <AccountOverview v-if="section === 'overview'" />
            <CompanyDetails v-else-if="section === 'company'" />
            <AccountDocuments v-else-if="section === 'documents'" />
            <div v-else class="py-10 text-sm text-zinc-500">Раздел будет подключён к соответствующему API.</div>
        </main>
    </div>
</template>
