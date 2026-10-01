<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const loading = ref(true)
const saving = ref(false)
const message = ref('')
const errors = ref({})

const fnsQuery = ref('')
const fnsLoading = ref(false)
const fnsSuggestions = ref([])
const fnsMessage = ref('')
const fnsMismatches = ref([])
const fnsCheckedAt = ref(null)

let suggestTimer = null

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
    fns_status: '',
    fns_checked_at: null,
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

function fnsStatusClass(status) {
    if (!status) return 'bg-zinc-100 text-zinc-600'
    const s = String(status).toLowerCase()
    if (s.includes('действ')) return 'bg-emerald-50 text-emerald-700'
    if (s.includes('ликвид') || s.includes('прекрат')) return 'bg-red-50 text-red-700'
    return 'bg-amber-50 text-amber-700'
}

onMounted(async () => {
    try {
        const response = await fetch('/account/api/profile', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        const payload = await response.json()
        if (payload.user?.customer_profile) {
            Object.assign(form, payload.user.customer_profile)
            fnsCheckedAt.value = payload.user.customer_profile.fns_checked_at || null
        }
    } finally {
        loading.value = false
    }
})

function searchFns() {
    clearTimeout(suggestTimer)
    fnsSuggestions.value = []
    fnsMessage.value = ''

    const q = fnsQuery.value.trim()
    if (q.length < 3 && !/^\d{6,}$/.test(q)) return

    suggestTimer = setTimeout(async () => {
        fnsLoading.value = true
        try {
            const response = await fetch(`/account/api/fns/suggest?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
            const payload = await response.json()
            fnsSuggestions.value = payload.items || []
        } catch (_) {
            fnsMessage.value = 'Не удалось получить подсказки ФНС.'
        } finally {
            fnsLoading.value = false
        }
    }, 350)
}

async function selectFnsCompany(item) {
    const req = item.inn || item.ogrn
    if (!req) return

    fnsLoading.value = true
    fnsSuggestions.value = []
    fnsMessage.value = ''

    try {
        const response = await fetch(`/account/api/fns/lookup?req=${encodeURIComponent(req)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        const payload = await response.json()
        if (!response.ok) throw new Error(payload.message || 'Не удалось загрузить данные ФНС.')

        const profile = payload.profile || {}
        Object.keys(profile).forEach((key) => {
            if (key in form && profile[key] !== null && profile[key] !== undefined) {
                form[key] = profile[key]
            }
        })

        fnsQuery.value = item.name || req
        fnsMessage.value = 'Реквизиты заполнены по данным ЕГРЮЛ/ЕГРИП. Проверьте их и сохраните.'
        fnsMismatches.value = []
    } catch (error) {
        fnsMessage.value = error.message || 'Ошибка API-ФНС.'
    } finally {
        fnsLoading.value = false
    }
}

async function verifyWithFns() {
    fnsLoading.value = true
    fnsMessage.value = ''
    fnsMismatches.value = []

    try {
        const response = await fetch('/account/api/fns/verify', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({}),
        })
        const payload = await response.json().catch(() => ({}))
        if (!response.ok) throw new Error(payload.message || payload.errors?.fns?.[0] || 'Проверка не выполнена.')

        fnsMismatches.value = payload.mismatches || []
        fnsCheckedAt.value = payload.checked_at || new Date().toISOString()
        form.fns_checked_at = fnsCheckedAt.value
        form.fns_status = payload.official_profile?.fns_status || form.fns_status

        if (payload.ok) {
            fnsMessage.value = 'Реквизиты совпадают с актуальными данными ЕГРЮЛ/ЕГРИП.'
        } else {
            fnsMessage.value = 'Обнаружены расхождения с данными ФНС.'
        }
    } catch (error) {
        fnsMessage.value = error.message || 'Ошибка проверки ФНС.'
    } finally {
        fnsLoading.value = false
    }
}

function applyOfficialMismatch(item) {
    if (item?.field && item.field in form) form[item.field] = item.official ?? ''
}

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
                <p class="mt-1 text-sm leading-6 text-zinc-500">Используются для договора, счёта на оплату и документов по заказам.</p>
            </div>
            <span class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusClass(form.verification_status)">
                {{ statusText(form.verification_status) }}
            </span>
        </div>

        <div v-if="form.verification_comment" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ form.verification_comment }}
        </div>

        <section class="rounded-2xl border border-blue-200 bg-blue-50/40 p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-zinc-950">Автозаполнение и проверка ФНС</h3>
                    <p class="mt-1 text-sm leading-6 text-zinc-600">Найдите организацию или ИП по названию или ИНН. Мы заполним реквизиты из ЕГРЮЛ/ЕГРИП и сможем проверить расхождения.</p>
                </div>
                <span v-if="form.fns_status" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="fnsStatusClass(form.fns_status)">
                    ФНС: {{ form.fns_status }}
                </span>
            </div>

            <div class="relative mt-4">
                <input
                    v-model="fnsQuery"
                    type="text"
                    autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200"
                    placeholder="Название компании или ИНН"
                    @input="searchFns"
                >

                <div v-if="fnsLoading" class="mt-2 text-xs text-zinc-500">Запрос к ФНС...</div>

                <div v-if="fnsSuggestions.length" class="absolute left-0 right-0 z-20 mt-2 max-h-80 overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl">
                    <button
                        v-for="item in fnsSuggestions"
                        :key="`${item.inn}-${item.ogrn}`"
                        type="button"
                        class="block w-full border-b border-zinc-100 px-4 py-3 text-left last:border-0 hover:bg-zinc-50"
                        @click="selectFnsCompany(item)"
                    >
                        <div class="font-medium text-zinc-950">{{ item.name }}</div>
                        <div class="mt-1 text-xs text-zinc-500">ИНН {{ item.inn }}<span v-if="item.ogrn"> · ОГРН {{ item.ogrn }}</span></div>
                        <div v-if="item.address" class="mt-1 text-xs text-zinc-400">{{ item.address }}</div>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" :disabled="fnsLoading || (!form.inn && !form.ogrn && !form.ogrnip)" class="rounded-xl border border-[#071d5d] px-4 py-2 text-sm font-semibold text-[#071d5d] transition hover:bg-[#071d5d] hover:text-white disabled:opacity-50" @click="verifyWithFns">
                    Проверить реквизиты по ФНС
                </button>
                <div v-if="fnsCheckedAt" class="self-center text-xs text-zinc-500">Последняя проверка: {{ new Date(fnsCheckedAt).toLocaleString('ru-RU') }}</div>
            </div>

            <div v-if="fnsMessage" class="mt-4 rounded-xl bg-white px-4 py-3 text-sm text-zinc-700 ring-1 ring-zinc-200">{{ fnsMessage }}</div>

            <div v-if="fnsMismatches.length" class="mt-4 space-y-2">
                <div v-for="item in fnsMismatches" :key="item.field" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
                    <div class="font-semibold text-amber-900">{{ item.label }}</div>
                    <div class="mt-1 text-xs text-amber-800">У вас: {{ item.current || '—' }}</div>
                    <div class="text-xs text-amber-800">ФНС: {{ item.official || '—' }}</div>
                    <button type="button" class="mt-2 text-xs font-semibold text-[#071d5d] hover:underline" @click="applyOfficialMismatch(item)">Использовать данные ФНС</button>
                </div>
            </div>
        </section>

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
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Краткое наименование</label><input v-model="form.company_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ООО Азия Косметик"><p v-if="firstError('company_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('company_name') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Полное наименование</label><input v-model="form.full_company_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Общество с ограниченной ответственностью ..."><p v-if="firstError('full_company_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('full_company_name') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">ИНН</label><input v-model="form.inn" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ИНН"><p v-if="firstError('inn')" class="mt-1.5 text-xs text-red-600">{{ firstError('inn') }}</p></div>
                <div v-if="isLegalEntity"><label class="mb-1.5 block text-sm font-medium text-zinc-700">КПП</label><input v-model="form.kpp" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="КПП"><p v-if="firstError('kpp')" class="mt-1.5 text-xs text-red-600">{{ firstError('kpp') }}</p></div>
                <div v-if="isLegalEntity"><label class="mb-1.5 block text-sm font-medium text-zinc-700">ОГРН</label><input v-model="form.ogrn" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ОГРН"><p v-if="firstError('ogrn')" class="mt-1.5 text-xs text-red-600">{{ firstError('ogrn') }}</p></div>
                <div v-if="isIp"><label class="mb-1.5 block text-sm font-medium text-zinc-700">ОГРНИП</label><input v-model="form.ogrnip" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ОГРНИП"><p v-if="firstError('ogrnip')" class="mt-1.5 text-xs text-red-600">{{ firstError('ogrnip') }}</p></div>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Адреса</h3>
            <div class="mt-4 grid gap-4">
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Юридический адрес</label><textarea v-model="form.legal_address" rows="2" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Юридический адрес"></textarea><p v-if="firstError('legal_address')" class="mt-1.5 text-xs text-red-600">{{ firstError('legal_address') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Фактический адрес</label><textarea v-model="form.actual_address" rows="2" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Фактический адрес"></textarea><p v-if="firstError('actual_address')" class="mt-1.5 text-xs text-red-600">{{ firstError('actual_address') }}</p></div>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Банковские реквизиты</h3>
            <p class="mt-1 text-xs text-zinc-500">Банковские реквизиты не приходят из ЕГРЮЛ/ЕГРИП и заполняются вручную.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-zinc-700">Наименование банка</label><input v-model="form.bank_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Банк"><p v-if="firstError('bank_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_name') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">БИК</label><input v-model="form.bank_bik" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="9 цифр"><p v-if="firstError('bank_bik')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_bik') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Расчётный счёт</label><input v-model="form.bank_account" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="20 цифр"><p v-if="firstError('bank_account')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_account') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Корреспондентский счёт</label><input v-model="form.bank_corr_account" inputmode="numeric" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="20 цифр"><p v-if="firstError('bank_corr_account')" class="mt-1.5 text-xs text-red-600">{{ firstError('bank_corr_account') }}</p></div>
            </div>
        </section>

        <section v-if="isBusiness" class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Руководитель / подписант</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">ФИО</label><input v-model="form.director_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Иванов Иван Иванович"><p v-if="firstError('director_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('director_name') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Должность</label><input v-model="form.director_position" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="Генеральный директор"><p v-if="firstError('director_position')" class="mt-1.5 text-xs text-red-600">{{ firstError('director_position') }}</p></div>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Контактные данные для документов и заказов</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Контактное лицо</label><input v-model="form.contact_name" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="ФИО"><p v-if="firstError('contact_name')" class="mt-1.5 text-xs text-red-600">{{ firstError('contact_name') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-zinc-700">Телефон</label><input v-model="form.contact_phone" type="tel" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="+7 999 123-45-67"><p v-if="firstError('contact_phone')" class="mt-1.5 text-xs text-red-600">{{ firstError('contact_phone') }}</p></div>
                <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label><input v-model="form.contact_email" type="email" class="w-full px-4 py-2 border border-gray-400 rounded-xl border-zinc-200" placeholder="docs@company.ru"><p v-if="firstError('contact_email')" class="mt-1.5 text-xs text-red-600">{{ firstError('contact_email') }}</p></div>
            </div>
        </section>

        <div v-if="errors.profile" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ errors.profile[0] }}</div>
        <div v-if="message" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ message }}</div>

        <div class="flex justify-end">
            <button type="submit" :disabled="saving" class="rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:opacity-60">
                {{ saving ? 'Сохраняем...' : 'Сохранить реквизиты' }}
            </button>
        </div>
    </form>
</template>
