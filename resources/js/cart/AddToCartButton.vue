<script setup>
import { ref } from 'vue'
import { addToCart } from './cart-store'

const props = defineProps({
    variantId: { type: Number, required: true },
    disabled: { type: Boolean, default: false },
})

const loading = ref(false)

async function add() {
    loading.value = true
    await addToCart(props.variantId, 1)
    loading.value = false
}
</script>

<template>
    <button
        type="button"
        :disabled="disabled || loading"
        class="w-full rounded-xl bg-[#071d5d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84] disabled:cursor-not-allowed disabled:bg-zinc-300"
        @click.stop.prevent="add"
    >
        {{ loading ? 'Добавляем...' : 'В корзину' }}
    </button>
</template>
