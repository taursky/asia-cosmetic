<script setup>
import { onMounted } from 'vue'
import { cartStore, loadCart } from './cart-store'

onMounted(() => {
    loadCart().catch(() => {})
})
</script>

<template>
    <button
        type="button"
        class="relative grid size-10 place-items-center rounded-full text-zinc-700 transition hover:bg-zinc-100"
        aria-label="Корзина"
        @click="cartStore.open = true"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5">
            <path d="M3 4h2l2 11h10l2-8H6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="19" r="1"/>
            <circle cx="17" cy="19" r="1"/>
        </svg>

        <span
            v-if="cartStore.count"
            class="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-5 text-white"
        >
            {{ cartStore.count }}
        </span>
    </button>
</template>
