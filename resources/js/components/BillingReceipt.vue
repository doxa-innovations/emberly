<script setup>
import { gsap } from 'gsap';
import { onMounted, onUnmounted, ref } from 'vue';
import { reducedMotion } from '../lib/motion';

const receipt = ref(null);
let context;

const lines = [
    { label: 'Estimate shown before you submit', amount: '$0.40245', tone: 'text-fg-muted' },
    { label: 'Held from your balance while it runs', amount: '- $0.45715', tone: 'text-fg-muted' },
    { label: 'Pixelforge cost from the tokens used', amount: '$0.30551', tone: 'text-fg-muted' },
    { label: 'Charged, cost plus 25%', amount: '- $0.3818875', tone: 'text-fg' },
    { label: 'Hold released', amount: '+ $0.45715', tone: 'text-ok' },
];

// The lines arrive in the order the ledger records them: the motion explains the billing sequence.
onMounted(() => {
    if (reducedMotion()) return;

    context = gsap.context(() => {
        gsap.timeline({ delay: 0.3 })
            .from('[data-line]', { opacity: 0, y: 8, duration: 0.5, stagger: 0.45, ease: 'power3.out' })
            .from('[data-total]', { opacity: 0, duration: 0.4, ease: 'power2.out' }, '+=0.15');
    }, receipt.value);
});

onUnmounted(() => context?.revert());
</script>

<template>
    <figure ref="receipt" class="panel grid gap-5 p-6" aria-label="Example of how one generation is billed">
        <figcaption class="grid gap-1">
            <span class="label">One high quality landscape with a reference image</span>
            <span class="hint">Every amount is exact and recorded once, even when webhooks arrive twice.</span>
        </figcaption>
        <ol class="grid gap-3 font-mono text-[13px] tabular-nums">
            <li v-for="line in lines" :key="line.label" data-line class="flex items-baseline justify-between gap-4" :class="line.tone">
                <span class="font-sans text-sm">{{ line.label }}</span>
                <span>{{ line.amount }}</span>
            </li>
        </ol>
        <div data-total class="flex items-baseline justify-between gap-4 border-t border-line pt-4">
            <span class="text-sm font-medium">You paid</span>
            <span class="font-mono text-xl font-medium tabular-nums text-ember">$0.3818875</span>
        </div>
    </figure>
</template>
