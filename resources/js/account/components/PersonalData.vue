<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const loading = ref(true)
const saving = ref(false)
const identitySaving = ref(false)
const message = ref('')
const identityMessage = ref('')
const errors = ref({})
const identityErrors = ref({})
const phoneCodeSent = ref(false)

const user = reactive({
    name: '',
    email: '',
    phone: '',
    email_verified_at: null,
    phone_verified_at: null,
    customer_role: null,
    customer_role_valid_until: null,
})

const personal = reactive({ name: '' })
const emailForm = reactive({ email: '' })
const phoneForm = reactive({ phone: '', code: '' })

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

const roleName = computed(() => user.customer_role?.name || 'Не назначена')

async function parse(response) {
    const payload = await response.json().catch(() => ({}))
    if (!response.ok) {
        const error = new Error(payload.message || 'Ошибка запроса')
        error.payload = payload
        throw error
    }
    return payload
}

async function load() {
    loading.value = true
    errors.value = {}

    try {
        const response = await fetch('/account/api/profile', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
        const payload = await parse(response)
        Object.assign(user, payload.user || {})
        personal.name = payload.user?.name || ''
        emailForm.email = payload.user?.email || ''
        phoneForm.phone = payload.user?.phone || ''
    } catch (e) {
        errors.value = e.payload?.errors || { profile: [e.message] }
    } finally {
        loading.value = false
    }
}

async function savePersonal() {
    saving.value = true
    message.value = ''
    errors.value = {}

    try {
        const response = await fetch('/account/api/personal', {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(personal),
        })
        const payload = await parse(response)
        Object.assign(user, payload.user || {})
        message.value = 'Личные данные сохранены.'
    } catch (e) {
        errors.value = e.payload?.errors || { profile: [e.message] }
    } finally {
        saving.value = false
    }
}

async function sendEmailVerification() {
    identitySaving.value = true
    identityMessage.value = ''
    identityErrors.value = {}

    try {
        const response = await fetch('/account/api/email/send', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(emailForm),
        })
        await parse(response)
        identityMessage.value = 'На новый email отправлена ссылка подтверждения.'
    } catch (e) {
        identityErrors.value = e.payload?.errors || { email: [e.message] }
    } finally {
        identitySaving.value = false
    }
}

async function sendPhoneCode() {
    identitySaving.value = true
    identityMessage.value = ''
    identityErrors.value = {}

    try {
        const response = await fetch('/account/api/phone/send', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ phone: phoneForm.phone }),
        })
        const payload = await parse(response)
        phoneForm.phone = payload.phone || phoneForm.phone
        phoneCodeSent.value = true
        identityMessage.value = 'Код подтверждения отправлен по SMS.'
    } catch (e) {
        identityErrors.value = e.payload?.errors || { phone: [e.message] }
    } finally {
        identitySaving.value = false
    }
}

async function verifyPhone() {
    identitySaving.value = true
    identityMessage.value = ''
    identityErrors.value = {}

    try {
        const response = await fetch('/account/api/phone/verify', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(phoneForm),
        })
        const payload = await parse(response)
        Object.assign(user, payload.user || {})
        phoneForm.code = ''
        phoneCodeSent.value = false
        identityMessage.value = 'Телефон подтверждён и сохранён.'
    } catch (e) {
        identityErrors.value = e.payload?.errors || { code: [e.message] }
    } finally {
        identitySaving.value = false
    }
}

function firstError(field) {
    return errors.value?.[field]?.[0] || null
}

function firstIdentityError(field) {
    return identityErrors.value?.[field]?.[0] || null
}

onMounted(load)
</script>

<template>
    <div v-if="loading" class="py-8 text-sm text-zinc-500">Загрузка...</div>

    <div v-else class="space-y-8">
        <div>
            <h2 class="text-2xl font-semibold text-zinc-950">Личные данные</h2>
            <p class="mt-1 text-sm leading-6 text-zinc-500">
                Основная информация аккаунта и способы связи.
            </p>
        </div>

        <section class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-zinc-950">Основная информация</h3>
                    <p class="mt-1 text-xs text-zinc-500">Коммерческая роль изменяется менеджером или автоматически.</p>
                </div>
                <div class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#071d5d]">
                    {{ roleName }}
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="savePersonal">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Имя / контактное лицо</label>
                    <input
                        v-model="personal.name"
                        type="text"
                        autocomplete="name"
                        class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                    >
                    <p v-if="firstError('name')" class="mt-1.5 text-xs text-red-600">{{ firstError('name') }}</p>
                </div>

                <div v-if="user.customer_role_valid_until" class="text-xs text-zinc-500">
                    Текущий уровень действует до: {{ new Date(user.customer_role_valid_until).toLocaleDateString('ru-RU') }}
                </div>

                <div v-if="message" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ message }}</div>

                <button
                    type="submit"
                    :disabled="saving"
                    class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:opacity-60"
                >
                    {{ saving ? 'Сохраняем...' : 'Сохранить' }}
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Email</h3>
            <div class="mt-1 flex items-center gap-2 text-xs">
                <span :class="user.email_verified_at ? 'text-emerald-600' : 'text-amber-600'">
                    {{ user.email_verified_at ? 'Подтверждён' : 'Не подтверждён' }}
                </span>
                <span v-if="user.email" class="text-zinc-400">Текущий: {{ user.email }}</span>
            </div>

            <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="sendEmailVerification">
                <div class="min-w-0 flex-1">
                    <input
                        v-model="emailForm.email"
                        type="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                    >
                    <p v-if="firstIdentityError('email')" class="mt-1.5 text-xs text-red-600">{{ firstIdentityError('email') }}</p>
                </div>
                <button
                    type="submit"
                    :disabled="identitySaving"
                    class="rounded-xl border border-[#071d5d] px-5 py-3 text-sm font-semibold text-[#071d5d] hover:bg-blue-50 disabled:opacity-60"
                >
                    Изменить / подтвердить
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 sm:p-6">
            <h3 class="font-semibold text-zinc-950">Телефон</h3>
            <div class="mt-1 flex items-center gap-2 text-xs">
                <span :class="user.phone_verified_at ? 'text-emerald-600' : 'text-amber-600'">
                    {{ user.phone_verified_at ? 'Подтверждён' : 'Не подтверждён' }}
                </span>
                <span v-if="user.phone" class="text-zinc-400">Текущий: {{ user.phone }}</span>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="phoneCodeSent ? verifyPhone() : sendPhoneCode()">
                <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                    <div>
                        <input
                            v-model="phoneForm.phone"
                            type="tel"
                            autocomplete="tel"
                            placeholder="+7 999 123-45-67"
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                        >
                        <p v-if="firstIdentityError('phone')" class="mt-1.5 text-xs text-red-600">{{ firstIdentityError('phone') }}</p>
                    </div>
                    <button
                        v-if="!phoneCodeSent"
                        type="submit"
                        :disabled="identitySaving"
                        class="rounded-xl border border-[#071d5d] px-5 py-3 text-sm font-semibold text-[#071d5d] hover:bg-blue-50 disabled:opacity-60"
                    >
                        Получить SMS-код
                    </button>
                </div>

                <div v-if="phoneCodeSent" class="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                    <div>
                        <input
                            v-model="phoneForm.code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            placeholder="Код из SMS"
                            class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:border-[#071d5d] focus:ring-[#071d5d]/10"
                        >
                        <p v-if="firstIdentityError('code')" class="mt-1.5 text-xs text-red-600">{{ firstIdentityError('code') }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="identitySaving"
                        class="rounded-xl bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                    >
                        Подтвердить
                    </button>
                    <button
                        type="button"
                        :disabled="identitySaving"
                        class="rounded-xl border border-zinc-200 px-5 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50"
                        @click="sendPhoneCode"
                    >
                        Отправить ещё раз
                    </button>
                </div>
            </form>
        </section>

        <div v-if="identityMessage" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ identityMessage }}
        </div>
    </div>
</template>
