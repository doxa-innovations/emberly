<script setup>
import { PhImageBroken, PhWarningCircle } from '@phosphor-icons/vue';
import { gsap } from 'gsap';
import { computed, onUnmounted, ref, watch } from 'vue';
import { errorMessage, sizeLabel } from '../lib/generation';
import { exactUsd, usd } from '../lib/money';
import { reducedMotion } from '../lib/motion';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({ generation: { type: Object, required: true } });
defineEmits(['open']);

const image = ref(null);
const expired = ref(false);
const justFinished = ref(false);
const now = ref(Date.now());
let ticker;

const elapsed = computed(() => Math.max(0, Math.round((now.value - Date.parse(props.generation.started_at ?? props.generation.created_at)) / 1000)));

watch(() => props.generation.status, (status, previous) => {
    justFinished.value = previous !== undefined && previous !== 'succeeded' && status === 'succeeded';
    clearInterval(ticker);

    if (status === 'queued' || status === 'processing') {
        ticker = setInterval(() => (now.value = Date.now()), 1000);
    }
}, { immediate: true });

function develop() {
    if (!justFinished.value || reducedMotion()) return;

    gsap.fromTo(image.value, { opacity: 0, scale: 1.06, filter: 'blur(14px) saturate(0.2)' }, { opacity: 1, scale: 1, filter: 'blur(0px) saturate(1)', duration: 0.9, ease: 'power3.out', clearProps: 'filter,transform' });
}

onUnmounted(() => {
    clearInterval(ticker);
    gsap.killTweensOf(image.value);
});
</script>

<template>
    <article class="grid gap-3">
        <button
            type="button"
            class="group relative block aspect-square overflow-hidden rounded-xl bg-ink-900 shadow-[inset_0_0_0_1px_var(--color-line)] transition-transform duration-200 ease-[var(--ease-out-strong)] active:scale-[0.985]"
            :aria-label="`Open details for: ${generation.prompt}`"
            @click="$emit('open')"
        >
            <template v-if="generation.status === 'succeeded' && !expired">
                <img
                    ref="image"
                    :src="generation.image"
                    :alt="generation.prompt"
                    loading="lazy"
                    decoding="async"
                    class="size-full object-contain transition-transform duration-500 ease-[var(--ease-out-strong)] group-hover:scale-[1.02]"
                    @load="develop"
                    @error="expired = true"
                />
            </template>

            <div v-else-if="generation.status === 'succeeded'" class="grid size-full place-content-center justify-items-center gap-2 p-6 text-center text-fg-subtle">
                <PhImageBroken :size="28" aria-hidden="true" />
                <span class="text-xs">The image link has expired.</span>
            </div>

            <div v-else-if="generation.status === 'failed'" class="grid size-full place-content-center justify-items-center gap-2 p-6 text-center">
                <PhWarningCircle :size="28" class="text-bad" aria-hidden="true" />
                <span class="line-clamp-3 text-xs text-fg-muted">{{ errorMessage(generation.error) }}</span>
            </div>

            <div v-else class="grid size-full place-content-center gap-1 text-center" :class="generation.status === 'processing' ? 'developing' : 'skeleton'">
                <span class="relative z-10 text-sm font-medium" :class="generation.status === 'processing' ? 'text-fg' : 'text-fg-muted'">
                    {{ generation.status === 'processing' ? 'Developing' : 'In the queue' }}
                </span>
                <span class="relative z-10 font-mono text-xs tabular-nums text-fg-subtle">{{ elapsed }}s</span>
            </div>
        </button>

        <div class="grid gap-2 px-0.5">
            <p class="line-clamp-2 text-[13px] leading-snug text-fg">{{ generation.prompt }}</p>
            <div class="flex items-center justify-between gap-2">
                <StatusBadge :status="generation.status" />
                <span class="text-right font-mono text-xs tabular-nums">
                    <template v-if="generation.status === 'succeeded'">
                        <span class="text-fg" :title="exactUsd(generation.cost)">{{ usd(generation.cost) }}</span>
                    </template>
                    <template v-else-if="generation.status === 'failed'">
                        <span class="text-fg-muted">Free</span>
                    </template>
                    <template v-else>
                        <span class="text-fg-subtle" :title="`Estimate, ${exactUsd(generation.estimated_cost)}`">≈ {{ usd(generation.estimated_cost) }}</span>
                    </template>
                </span>
            </div>
            <p class="hint">{{ sizeLabel(generation.size) }}, {{ generation.quality }} quality</p>
        </div>
    </article>
</template>
