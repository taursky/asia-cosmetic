<script setup>
import { onMounted, reactive, ref } from 'vue'
const loading = ref(true)
const saving = ref(false)
const message = ref('')
const errors = ref({})
const form = reactive({
    legal_type: 'individual', company_name: '', full_company_name: '', inn: '', kpp: '', ogrn: '', ogrnip: '',
    legal_address: '', actual_address: '', bank_name: '', bank_bik: '', bank_account: '', bank_corr_account: '',
    director_name: '', director_position: '', contact_name: '', contact_phone: '', contact_email: '',
})

onMounted(async () => {
    const response = await fetch('/account/api/profile', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    const payload = await response.json()
    if (payload.user?.customer_profile) Object.assign(form, payload.user.customer_profile)
    loading.value = false
})

async function save() {
    saving.value = true
    errors.value = {}
    message.value = ''
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
    const response = await fetch('/account/api/profile', {
        method: 'PUT', credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(form),
    })
    const payload = await response.json()
    if (!response.ok) errors.value = payload.errors || {}
    else message.value = 'Реквизиты сохранены и отправлены на проверку.'
    saving.value = false
}
</script>

<template>
    <div v-if="loading" class="text-sm text-zinc-500">Загрузка...</div>
    <form v-else class="space-y-6" @submit.prevent="save">
        <div>
            <h2 class="text-2xl font-semibold text-zinc-950">Платёжные реквизиты</h2>
            <p class="mt-1 text-sm text-zinc-500">Эти данные используются для договора и выставления счёта.</p>
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-zinc-700">Статус покупателя</label>
            <select v-model="form.legal_type" class="w-full rounded-xl border-zinc-200">
                <option value="individual">Физическое лицо</option>
                <option value="individual_entrepreneur">ИП</option>
                <option value="legal_entity">Юридическое лицо</option>
            </select>
        </div>
        <div v-if="form.legal_type !== 'individual'" class="grid gap-4 sm:grid-cols-2">
            <input v-model="form.company_name" class="rounded-xl border-zinc-200" placeholder="Название компании">
            <input v-model="form.inn" class="rounded-xl border-zinc-200" placeholder="ИНН">
            <template v-if="form.legal_type === 'legal_entity'">
                <input v-model="form.kpp" class="rounded-xl border-zinc-200" placeholder="КПП">
                <input v-model="form.ogrn" class="rounded-xl border-zinc-200" placeholder="ОГРН">
            </template>
            <input v-if="form.legal_type === 'individual_entrepreneur'" v-model="form.ogrnip" class="rounded-xl border-zinc-200" placeholder="ОГРНИП">
        </div>
        <div v-if="form.legal_type !== 'individual'" class="grid gap-4 sm:grid-cols-2">
            <input v-model="form.bank_name" class="rounded-xl border-zinc-200" placeholder="Банк">
            <input v-model="form.bank_bik" class="rounded-xl border-zinc-200" placeholder="БИК">
            <input v-model="form.bank_account" class="rounded-xl border-zinc-200" placeholder="Расчётный счёт">
            <input v-model="form.bank_corr_account" class="rounded-xl border-zinc-200" placeholder="Корр. счёт">
        </div>
        <div v-if="Object.keys(errors).length" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">Проверьте заполнение реквизитов.</div>
        <div v-if="message" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700">{{ message }}</div>
        <button type="submit" :disabled="saving" class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white disabled:opacity-60">
            {{ saving ? 'Сохраняем...' : 'Сохранить реквизиты' }}
        </button>
    </form>
</template>
