<script setup>
import { onMounted, reactive, ref } from 'vue'

const loading = ref(true)
const saving = ref(false)
const message = ref('')
const error = ref('')
const form = reactive({ name: '', email: '', phone: '' })

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

onMounted(async () => {
    try {
        const response = await fetch('/account/api/profile', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        const payload = await response.json()
        Object.assign(form, {
            name: payload.user?.name || '',
            email: payload.user?.email || '',
            phone: payload.user?.phone || '',
        })
    } finally {
        loading.value = false
    }
})

async function save() {
    saving.value = true
    message.value = ''
    error.value = ''

    const response = await fetch('/account/api/personal', {
        method: 'PUT',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
        },
        body: JSON.stringify({ name: form.name }),
    })

    const payload = await response.json().catch(() => ({}))
    if (!response.ok) error.value = payload.message || 'Не удалось сохранить данные.'
    else message.value = 'Личные данные сохранены.'
    saving.value = false
}
</script>

<template>
    <div v-if="loading" class="py-8 text-sm text-zinc-500">Загрузка...</div>
    <form v-else class="space-y-6" @submit.prevent="save">
        <div>
            <h2 class="text-2xl font-semibold text-zinc-950">Личные данные</h2>
            <p class="mt-1 text-sm text-zinc-500">Email и телефон меняются только с повторным подтверждением в разделе безопасности.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Имя / ФИО</label>
                <input v-model="form.name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" required>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                <input :value="form.email" disabled class="w-full px-4 py-2 border border-gray-300 rounded-xl bg-zinc-50 text-zinc-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Телефон</label>
                <input :value="form.phone" disabled class="w-full px-4 py-2 border border-gray-300 rounded-xl bg-zinc-50 text-zinc-500">
            </div>
        </div>

        <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>
        <div v-if="message" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ message }}</div>

        <button type="submit" :disabled="saving" class="rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white disabled:opacity-60">
            {{ saving ? 'Сохраняем...' : 'Сохранить' }}
        </button>
    </form>
</template>
