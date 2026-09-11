<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    slides: { type: Array, default: () => [] },
})

const active = ref(0)
let timer = null

function select(index) {
    active.value = index
}

function next() {
    if (!props.slides.length) return
    active.value = (active.value + 1) % props.slides.length
}

function previous() {
    if (!props.slides.length) return
    active.value = (active.value - 1 + props.slides.length) % props.slides.length
}

onMounted(() => {
    if (props.slides.length > 1) timer = window.setInterval(next, 5500)
})

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer)
})
</script>

<template>
    <section class="relative overflow-hidden rounded-[2rem] bg-[#071d5d] text-white">
        <div class="absolute inset-0 opacity-60">
            <div class="absolute -right-24 -top-24 size-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="absolute -bottom-32 left-1/4 size-[28rem] rounded-full bg-indigo-400/20 blur-3xl"></div>
        </div>

        <div v-if="slides.length" class="relative min-h-[430px] sm:min-h-[500px]">
            <transition name="fade" mode="out-in">
                <div :key="active" class="grid min-h-[430px] items-center px-7 py-14 sm:min-h-[500px] sm:px-12 lg:grid-cols-12 lg:px-16">
                    <div class="max-w-3xl lg:col-span-8">
                        <div class="mb-5 text-xs font-semibold uppercase tracking-[.24em] text-cyan-100">{{ slides[active].eyebrow }}</div>
                        <h1 class="max-w-3xl text-4xl font-semibold leading-[1.05] tracking-[-.04em] sm:text-5xl lg:text-6xl">{{ slides[active].title }}</h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-blue-100 sm:text-lg">{{ slides[active].text }}</p>
                        <a :href="slides[active].url" class="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#071d5d] transition hover:bg-blue-50">
                            {{ slides[active].button }}
                        </a>
                    </div>
                </div>
            </transition>

            <div v-if="slides.length > 1" class="absolute bottom-7 left-7 right-7 flex items-center justify-between sm:left-12 sm:right-12 lg:left-16 lg:right-16">
                <div class="flex gap-2">
                    <button v-for="(_, index) in slides" :key="index" type="button" @click="select(index)" :class="['h-1.5 rounded-full transition-all', active === index ? 'w-9 bg-white' : 'w-4 bg-white/35']" :aria-label="`Слайд ${index + 1}`"></button>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="previous" class="grid size-10 place-items-center rounded-full border border-white/30 bg-white/10 text-xl backdrop-blur hover:bg-white/20">‹</button>
                    <button type="button" @click="next" class="grid size-10 place-items-center rounded-full border border-white/30 bg-white/10 text-xl backdrop-blur hover:bg-white/20">›</button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .25s ease,transform .25s ease}.fade-enter-from{opacity:0;transform:translateX(12px)}.fade-leave-to{opacity:0;transform:translateX(-12px)}
</style>
