<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const loading = ref(true)
const saving = ref(false)
const message = ref('')
const errors = ref({})

const form = reactive({
    legal_type: 'individual',
    company_name: '',
    full_company_name: '',
    inn: '',
    kpp: '',
    ogrn: '',
    ogrnip: '',
    legal_address: '',
    actual_address: '',
    bank_name: '',
    bank_bik: '',
    bank_account: '',
    bank_corr_account: '',
    director_name: '',
    director_position: '',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    verification_status: 'draft',
    verified_at: null,
    verification_comment: '',
})

const isBusiness = computed(() => form.legal_type !== 'individual')
const isLegalEntity = computed(() => form.legal_type === 'legal_entity')
const isIp = computed(() => form.legal_type === 'individual_entrepreneur')

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

function firstError(field) {
    return errors.value?.[field]?.[0] || null
}

function statusText(status) {
    return {
        draft: 'Черновик',
        pending: 'На проверке',
        verified: 'Подтверждены',
        rejected: 'Требуют исправления',
    }[status] || status
}

function statusClass(status) {
    return {
        verified: 'bg-emerald-50 text-emerald-700',
        pending: 'bg-amber-50 text-amber-700',
        rejected: 'bg-red-50 text-red-700',
    }[status] || 'bg-zinc-100 text-zinc-600'
}

onMounted(async () => {
    try {
        const response = await fetch('/account/api/profile', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        const payload = await response.json()
        if (payload.user?.customer_profile) Object.assign(form, payload.user.customer_profile)
    } finally {
        loading.value = false
    }
})

async function save() {
    saving.value = true
    errors.value = {}
    message.value = ''

    const response = await fetch('/account/api/profile', {
        method: 'PUT',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
        },
        body: JSON.stringify(form),
    })

    const payload = await response.json().catch(() => ({}))

    if (!response.ok) {
        errors.value = payload.errors || { profile: [payload.message || 'Не удалось сохранить реквизиты.'] }
    } else {
        Object.assign(form, payload.profile || {})
        message.value = 'Реквизиты сохранены и отправлены на проверку.'
    }

    saving.value = false
}
</script>

<template>
    <div v-if="loading" class="py-8 text-sm text-zinc-500">Загрузка...</div>

    <form v-else class="space-y-8" @submit.prevent="save">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-zinc-950">Платёжные реквизиты</h2>
                <p class="mt-1 text-sm leading-6 text-zinc-500">
                    Используются для договора, счёта на оплату и документов по заказам.
                </p>
            </div>
            <span class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusClass(form.verification_status)">
                {{ statusText(form.verification_status) }}
            </span>
        </div>

        <div v-if="form.verification_comment" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ form.verification_comment }}
        </div>

        <section class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Правовой статус</h3>
            <div class="mt-4">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Тип покупателя</label>
                <select v-model="form.legal_type" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200">
                    <option value="individual">Физическое лицо</option>
                    <option value="individual_entrepreneur">Индивидуальный предприниматель</option>
                    <option value="legal_entity">Юридическое лицо</option>
                </select>
                <p v-if="firstError('legal_type')" class="mt-1.5 text-xs text-red-600">{{ firstError('legal_type') }}</p>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Организация</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Краткое наименование</label>
                    <input v-model="form.company_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ООО Азия Косметик">
                    <p v-if="firstError('company_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('company_name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Полное наименование</label>
                    <input v-model="form.full_company_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Общество с ограниченной ответственностью ...">
                    <p v-if="firstError('full_company_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('full_company_name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">ИНН</label>
                    <input v-model="form.inn" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ИНН">
                    <p v-if="firstError('inn')" class="mt-1.5 text-xs text-red-600">{{ firstError('inn') }}</p>
                </div>
                <div v-if="isLegalEntity">
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">КПП</label>
                    <input v-model="form.kpp" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="КПП">
                    <p v-if="firstError('kpp')" class="mt-1.5 text-xs text-red-600">{{ firstError('kpp') }}</p>
                </div>
                <div v-if="isLegalEntity">
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">ОГРН</label>
                    <input v-model="form.ogrn" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ОГРН">
                    <p v-if="firstError('ogrn')" class="mt-1.5 text-xs text-red-600">{{ firstError('ogrn') }}</p>
                </div>
                <div v-if="isIp">
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">ОГРНИП</label>
                    <input v-model="form.ogrnip" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ОГРНИП">
                    <p v-if="firstError('ogrnip')" class="mt-1.5 text-xs text-red-600">{{ firstError('ogrnip') }}</p>
                </div>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Адреса</h3>
            <div class="mt-4 grid gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Юридический адрес</label>
                    <textarea v-model="form.legal_address" rows="2" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Юридический адрес"></textarea>
                    <p v-if="firstError('legal_address')" class="mt-1.5 text-xs text-red-600">{{ firstError('legal_address') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Фактический адрес</label>
                    <textarea v-model="form.actual_address" rows="2" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Фактический адрес"></textarea>
                    <p v-if="firstError('actual_address')" class="mt-1.5 text-xs text-red-600">{{ firstError('actual_address') }}</p>
                </div>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Банковские реквизиты</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Наименование банка</label>
                    <input v-model="form.bank_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Банк">
                    <p v-if="firstError('bank_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">БИК</label>
                    <input v-model="form.bank_bik" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="9 цифр">
                    <p v-if="firstError('bank_bik')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_bik') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Расчётный счёт</label>
                    <input v-model="form.bank_account" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="20 цифр">
                    <p v-if="firstError('bank_account')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_account') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Корреспондентский счёт</label>
                    <input v-model="form.bank_corr_account" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="20 цифр">
                    <p v-if="firstError('bank_corr_account')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_corr_account') }}</p>
                </div>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Руководитель / подписант</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">ФИО</label>
                    <input v-model="form.director_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Иванов Иван Иванович">
                    <p v-if="firstError('director_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('director_name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Должность</label>
                    <input v-model="form.director_position" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Генеральный директор">
                    <p v-if="firstError('director_position')" class="mt-1.5 text-xs text-red-600">{{ firstError('director_position') }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Контактные данные для документов и заказов</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Контактное лицо</label>
                    <input v-model="form.contact_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ФИО">
                    <p v-if="firstError('contact_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('contact_name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Телефон</label>
                    <input v-model="form.contact_phone" type="tel" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="+7 999 123-45-67">
                    <p v-if="firstError('contact_phone')" class="mt-1.5 text-xs text-red-600">{{ firstError('contact_phone') }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                    <input v-model="form.contact_email" type="email" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="docs@company.ru">
                    <p v-if="firstError('contact_email')" class="mt-1.5 text-xs text-red-600">{{ firstError('contact_email') }}</p>
                </div>
            </div>
        </section>

        <div v-if="errors.profile" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ errors.profile[0] }}
        </div>
        <div v-if="message" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ message }}</div>

        <div class="flex justify-end">
            <button type="submit" :disabled="saving" class="rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:opacity-60">
                {{ saving ? 'Сохраняем...' : 'Сохранить реквизиты' }}
            </button>
        </div>
    </form>
</template>
