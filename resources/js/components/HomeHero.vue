<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    slides: { type: Array, default: () => [] },
})

const active = ref(0)
const availableSlides = computed(() => props.slides.filter(slide => slide?.desktop_image || slide?.mobile_image))
const currentSlide = computed(() => availableSlides.value[active.value] ?? availableSlides.value[0] ?? null)
let timer = null

function select(index) {
    if (index >= 0 && index < availableSlides.value.length) active.value = index
}
function next() {
    if (availableSlides.value.length > 1) active.value = (active.value + 1) % availableSlides.value.length;
    startTimer();
}
function previous() {
    if (availableSlides.value.length > 1) active.value = (active.value - 1 + availableSlides.value.length) % availableSlides.value.length;
    startTimer();
}
function stopTimer() {
    if (timer !== null) window.clearInterval(timer)
    timer = null
}
function startTimer() {
    stopTimer()
    if (availableSlides.value.length > 1) timer = window.setInterval(next, 8000)
}
watch(availableSlides, () => {
    active.value = 0
    startTimer()
})
onMounted(startTimer)
onBeforeUnmount(stopTimer)
</script>

<template>
    <section class="w-full">
        <div v-if="currentSlide" class="relative overflow-hidden rounded-[2rem] bg-[#bdbdbd]">
            <div class="relative aspect-[4/5] sm:aspect-[16/7] lg:aspect-[21/8]">
                <transition name="fade" mode="out-in">
                    <a
                        v-if="currentSlide.link"
                        :key="currentSlide.id ?? active"
                        :href="currentSlide.link"
                        class="absolute inset-0 block"
                        :aria-label="`Перейти по баннеру ${active + 1}`"
                    >
                        <picture>
                            <source v-if="currentSlide.mobile_image" media="(max-width: 639px)" :srcset="currentSlide.mobile_image" />
                            <img :src="currentSlide.desktop_image || currentSlide.mobile_image" :alt="currentSlide.alt || 'Рекламный баннер'" class="h-full w-full object-cover" :fetchpriority="active === 0 ? 'high' : 'auto'" />
                        </picture>
                    </a>
                    <div v-else :key="currentSlide.id ?? active" class="absolute inset-0">
                        <picture>
                            <source v-if="currentSlide.mobile_image" media="(max-width: 639px)" :srcset="currentSlide.mobile_image" />
                            <img :src="currentSlide.desktop_image || currentSlide.mobile_image" :alt="currentSlide.alt || 'Рекламный баннер'" class="h-full w-full object-cover" :fetchpriority="active === 0 ? 'high' : 'auto'" />
                        </picture>
                    </div>
                </transition>
            </div>
            <div v-if="availableSlides.length > 1" class="flex items-center justify-between gap-4 px-2 py-4 sm:px-4">
                <div class="flex gap-2" aria-label="Выбор баннера">
                    <button v-for="(_, index) in availableSlides" :key="index" type="button" @click="select(index)" :class="['h-1.5 rounded-full transition-all', active === index ? 'w-9 bg-white' : 'w-4 bg-white/50']" :aria-label="`Баннер ${index + 1}`" :aria-current="active === index ? 'true' : undefined"></button>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="previous" aria-label="Предыдущий баннер" class="grid size-9 place-items-center rounded-full border border-white/40 bg-black/30 text-xl text-white backdrop-blur hover:bg-black/50">‹</button>
                    <button type="button" @click="next" aria-label="Следующий баннер" class="grid size-9 place-items-center rounded-full border border-white/40 bg-black/30 text-xl text-white backdrop-blur hover:bg-black/50">›</button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .3s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
@media (prefers-reduced-motion: reduce){.fade-enter-active,.fade-leave-active{transition:none}}
</style>
