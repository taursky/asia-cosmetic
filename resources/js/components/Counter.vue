<script setup>
import { ref } from 'vue'

const props = defineProps({
    initial: {
        type: Number,
        default: 0,
    },
})

const count = ref(props.initial)
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
<!--        <div class="text-lg font-semibold">-->
<!--            Vue работает-->
<!--        </div>-->

        <div class="mt-4 flex items-center gap-4">
            <button
                type="button"
                class="rounded-lg bg-amber-500 px-4 py-2 font-medium text-white hover:bg-amber-600"
                @click="count--"
            >
                −
            </button>

            <span class="min-w-10 text-center text-xl font-bold">
                {{ count }}
            </span>

            <button
                type="button"
                class="rounded-lg bg-amber-500 px-4 py-2 font-medium text-white hover:bg-amber-600"
                @click="count++"
            >
                +
            </button>
        </div>
    </div>
</template>
