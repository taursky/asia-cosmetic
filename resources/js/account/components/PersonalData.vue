<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const loading = ref(true)
const saving = ref(false)
const message = ref('')
const error = ref('')
const form = reactive({ last_name: '', first_name: '', middle_name: '', email: '', phone: '', legal_type: '' })
const user = ref(null)
const role = computed(() => user.value?.customer_role || null)
const profile = computed(() => user.value?.customer_profile || null)
const statusText = (status) => ({ draft: 'Черновик', pending: 'На проверке', verified: 'Подтверждены', rejected: 'Требуют исправления' }[status] || 'Не заполнены')

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

onMounted(async () => {
    try {
        const response = await fetch('/account/api/profile', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        if (!response.ok) throw new Error('Не удалось загрузить профиль.')
        const payload = await response.json()
        user.value = payload.user
        Object.assign(form, {
            last_name: payload.user?.last_name || '',
            first_name: payload.user?.first_name || '',
            middle_name: payload.user?.middle_name || '',
            email: payload.user?.email || '',
            phone: payload.user?.phone || '',
            legal_type: payload.user?.customer_profile?.legal_type || '',
        })
    } catch (e) {
        error.value = e.message || 'Ошибка загрузки профиля.'
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
        body: JSON.stringify({
            last_name: form.last_name,
            first_name: form.first_name,
            middle_name: form.middle_name,
        }),
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
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl bg-zinc-50 p-5"><div class="text-xs uppercase tracking-wide text-zinc-400">Уровень покупателя</div><div class="mt-2 text-lg font-semibold">{{ role?.name || 'Не назначен' }}</div><div v-if="user?.customer_role_valid_until" class="mt-1 text-xs text-zinc-500">Действует до {{ new Date(user.customer_role_valid_until).toLocaleDateString('ru-RU') }}</div></div>
            <div class="rounded-2xl bg-zinc-50 p-5"><div class="text-xs uppercase tracking-wide text-zinc-400">Тип цены</div><div class="mt-2 text-lg font-semibold">{{ role?.price_type?.name || 'Не назначен' }}</div><div v-if="role?.price_type?.code" class="mt-1 text-xs text-zinc-400">{{ role.price_type.code }}</div></div>

            <div v-if="form.legal_type !== 'individual'" class="rounded-2xl bg-zinc-50 p-5"><div class="text-xs uppercase tracking-wide text-zinc-400">Реквизиты</div><div class="mt-2 text-lg font-semibold">{{ statusText(profile?.verification_status) }}</div><div v-if="profile?.company_name" class="mt-1 text-xs text-zinc-500">{{ profile.company_name }}</div></div>
        </div>
        <div>
            <h2 class="text-2xl font-semibold text-zinc-950">Личные данные</h2>
            <p class="mt-1 text-sm text-zinc-500">Email и телефон меняются только с повторным подтверждением в разделе безопасности.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Фамилия</label>
                <input v-model="form.last_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" required>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Имя</label>
                <input v-model="form.first_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" required>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Отчество (необязательно)</label>
                <input v-model="form.middle_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                <!--bg-zinc-50 -->
                <input v-model="form.email" class="w-full px-4 py-2 border border-gray-300 rounded-xl text-zinc-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Телефон</label>
                <input v-model="form.phone" class="w-full px-4 py-2 border border-gray-300 rounded-xl bg-zinc-50 text-zinc-500">
                <p class="mt-1 text-sm text-red-600">При изменении номера телефона может потребоваться обращение в службу поддержки.</p>
            </div>
        </div>

        <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>
        <div v-if="message" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ message }}</div>

        <button type="submit" :disabled="saving" class="rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white disabled:opacity-60">
            {{ saving ? 'Сохраняем...' : 'Сохранить' }}
        </button>
    </form>
</template>
