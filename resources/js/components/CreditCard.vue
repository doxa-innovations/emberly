<script setup>
import { PhFire } from '@phosphor-icons/vue';
import { gsap } from 'gsap';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { reducedMotion } from '../lib/motion';

const props = defineProps({
    number: { type: String, default: '' },
    name: { type: String, default: '' },
    expiry: { type: String, default: '' },
    cvc: { type: String, default: '' },
    flipped: { type: Boolean, default: false },
    state: { type: String, default: 'idle' },
});

const scene = ref(null);
const card = ref(null);
const sheen = ref(null);
let tiltX;
let tiltY;

const digits = computed(() => props.number.replace(/\D/g, '').padEnd(16, '•').slice(0, 16).match(/.{4}/g));

watch(() => props.flipped, (flipped) => {
    gsap.to(card.value, { rotateY: flipped ? 180 : 0, duration: reducedMotion() ? 0 : 0.7, ease: 'power3.inOut' });
});

watch(() => props.state, (state) => {
    if (reducedMotion() || state === 'idle') return;

    if (state === 'paying') {
        gsap.fromTo(sheen.value, { xPercent: 0 }, { xPercent: 400, duration: 1.1, ease: 'power2.inOut', repeat: -1, repeatDelay: 0.3 });
    } else {
        gsap.killTweensOf(sheen.value);
        gsap.set(sheen.value, { xPercent: 0 });
        gsap.fromTo(card.value, { scale: 1 }, { scale: 1.03, duration: 0.25, yoyo: true, repeat: 1, ease: 'power2.out' });
    }
});

function tilt(event) {
    const rect = scene.value.getBoundingClientRect();
    tiltX?.(((event.clientY - rect.top) / rect.height - 0.5) * -10);
    tiltY?.(((event.clientX - rect.left) / rect.width - 0.5) * 12);
}

function resetTilt() {
    tiltX?.(0);
    tiltY?.(0);
}

onMounted(() => {
    if (reducedMotion() || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    tiltX = gsap.quickTo(scene.value, 'rotateX', { duration: 0.6, ease: 'power3.out' });
    tiltY = gsap.quickTo(scene.value, 'rotateY', { duration: 0.6, ease: 'power3.out' });
});

onUnmounted(() => gsap.killTweensOf([card.value, scene.value, sheen.value]));
</script>

<template>
    <div class="[perspective:1200px]" @pointermove="tilt" @pointerleave="resetTilt">
        <div ref="scene" class="[transform-style:preserve-3d]">
            <div ref="card" class="relative aspect-[1.586] w-full max-w-[380px] [transform-style:preserve-3d]" aria-hidden="true">
                <div class="card-face absolute inset-0 flex flex-col justify-between overflow-hidden rounded-2xl p-6 [backface-visibility:hidden]">
                    <span ref="sheen" class="pointer-events-none absolute inset-y-0 -left-1/3 w-1/3 bg-linear-to-r from-transparent via-white/10 to-transparent" />
                    <div class="flex items-start justify-between">
                        <span class="chip" />
                        <span class="flex items-center gap-1.5 text-sm font-semibold tracking-tight text-fg">
                            <PhFire :size="18" weight="fill" class="text-ember" /> Emberly
                        </span>
                    </div>
                    <div class="grid gap-4">
                        <div class="flex justify-between font-mono text-[clamp(1rem,4.6vw,1.375rem)] tracking-wider text-fg">
                            <span v-for="(group, index) in digits" :key="index">{{ group }}</span>
                        </div>
                        <div class="flex items-end justify-between gap-4 text-xs">
                            <div class="grid min-w-0 gap-0.5">
                                <span class="text-[10px] tracking-wider text-fg-subtle uppercase">Card holder</span>
                                <span class="truncate font-medium tracking-wide text-fg uppercase">{{ name || 'Your name' }}</span>
                            </div>
                            <div class="grid gap-0.5 text-right">
                                <span class="text-[10px] tracking-wider text-fg-subtle uppercase">Expires</span>
                                <span class="font-mono text-fg">{{ expiry || 'MM/YY' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-face absolute inset-0 overflow-hidden rounded-2xl [backface-visibility:hidden] [transform:rotateY(180deg)]">
                    <div class="mt-6 h-11 bg-ink-950" />
                    <div class="mx-6 mt-5 flex items-center justify-end gap-3">
                        <span class="text-[10px] tracking-wider text-fg-subtle uppercase">CVC</span>
                        <span class="rounded bg-fg px-3 py-1.5 font-mono text-sm text-ink-950">{{ cvc || '•••' }}</span>
                    </div>
                    <p class="mx-6 mt-5 text-[10px] leading-relaxed text-fg-subtle">Demo card. It only exists in this browser tab.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card-face {
    background:
        radial-gradient(120% 90% at 100% 0%, oklch(0.75 0.17 52 / 0.32), transparent 55%),
        radial-gradient(90% 80% at 0% 100%, oklch(0.6 0.12 40 / 0.18), transparent 60%),
        linear-gradient(160deg, oklch(0.25 0.01 50), oklch(0.17 0 0));
    box-shadow:
        inset 0 0 0 1px oklch(1 0 0 / 0.12),
        0 24px 60px oklch(0 0 0 / 0.55);
}

.chip {
    width: 2.75rem;
    height: 2rem;
    border-radius: 0.375rem;
    background: linear-gradient(135deg, oklch(0.86 0.06 85), oklch(0.68 0.06 75));
    box-shadow: inset 0 0 0 1px oklch(0 0 0 / 0.25);
}
</style>
