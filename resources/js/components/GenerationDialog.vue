<script setup>
import { PhArrowSquareOut, PhArrowsClockwise, PhX } from '@phosphor-icons/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { errorMessage, isInFlight, sizeLabel, SIZES } from '../lib/generation';
import { exactUsd, usd } from '../lib/money';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({ generation: { type: Object, default: null } });
const emit = defineEmits(['close', 'reuse']);

const TOKEN_TYPES = [
    ['input_text_tokens', 'Prompt text'],
    ['input_image_tokens', 'Reference images'],
    ['output_tokens', 'Image output'],
    ['reasoning_tokens', 'Reasoning'],
];

const dialog = ref(null);
const time = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });

const receipt = computed(() => {
    const { usage, rates } = props.generation ?? {};

    return usage && rates
        ? TOKEN_TYPES.map(([type, label]) => ({ label, tokens: usage[type], rate: rates[type], cost: usage[type] * Math.round(Number(rates[type]) * 1000) }))
        : [];
});

const timeline = computed(() => {
    const generation = props.generation;
    const seen = new Set();
    const events = generation.events.map((event) => {
        const duplicate = seen.has(event.id);
        seen.add(event.id);

        return { at: event.received_at, label: `Webhook ${event.type}`, detail: `Attempt ${event.attempt}${duplicate ? ', duplicate ignored' : ''}`, muted: duplicate };
    });

    return [
        { at: generation.created_at, label: 'Queued in Emberly' },
        generation.started_at && { at: generation.started_at, label: 'Pixelforge started' },
        generation.completed_at && { at: generation.completed_at, label: generation.status === 'failed' ? 'Failed' : 'Finished' },
        ...events,
    ]
        .filter(Boolean)
        .sort((a, b) => Date.parse(a.at) - Date.parse(b.at));
});

watch(() => props.generation?.id, async (id) => {
    await nextTick();

    if (id && !dialog.value.open) dialog.value.showModal();
    if (!id && dialog.value.open) dialog.value.close();
});

let pressedOutside = false;
const outside = (event) => {
    const rect = dialog.value.getBoundingClientRect();

    return event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
};

function lightDismissFallback(event) {
    if ('closedBy' in HTMLDialogElement.prototype) return;
    if (event.type === 'pointerdown') pressedOutside = outside(event);
    else if (pressedOutside && outside(event)) dialog.value.close();
}

function reuse() {
    emit('reuse', props.generation);
    dialog.value.close();
}
</script>

<template>
    <dialog
        ref="dialog"
        class="modal w-[min(1000px,calc(100vw-2rem))] p-0"
        closedby="any"
        aria-labelledby="generation-title"
        @close="emit('close')"
        @pointerdown="lightDismissFallback"
        @pointerup="lightDismissFallback"
    >
        <div v-if="generation" class="grid max-h-[calc(100dvh-2rem)] md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
            <div class="grid place-items-center bg-ink-950 p-4 md:p-6">
                <img
                    v-if="generation.status === 'succeeded'"
                    :src="generation.image"
                    :alt="generation.prompt"
                    class="max-h-[70dvh] w-full rounded-md object-contain"
                    :style="{ aspectRatio: SIZES.find((size) => size.value === generation.size)?.ratio }"
                />
                <div
                    v-else
                    class="grid w-full place-content-center rounded-md text-sm text-fg-muted"
                    :class="generation.status === 'processing' ? 'developing' : generation.status === 'queued' ? 'skeleton' : 'bg-ink-900'"
                    :style="{ aspectRatio: SIZES.find((size) => size.value === generation.size)?.ratio }"
                >
                    <span class="relative z-10 px-6 text-center">{{ generation.status === 'failed' ? errorMessage(generation.error) : 'Still developing. This view updates live.' }}</span>
                </div>
            </div>

            <div class="grid content-start gap-6 overflow-y-auto p-5 md:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="grid gap-2">
                        <h1 id="generation-title" class="text-base font-semibold">Generation</h1>
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :status="generation.status" />
                            <span class="hint">{{ sizeLabel(generation.size) }} {{ generation.size }}, {{ generation.quality }} quality</span>
                        </div>
                    </div>
                    <form method="dialog">
                        <button class="btn btn-ghost size-8 px-0" aria-label="Close">
                            <PhX :size="16" aria-hidden="true" />
                        </button>
                    </form>
                </div>

                <p class="text-[15px] leading-relaxed whitespace-pre-wrap">{{ generation.prompt }}</p>

                <div v-if="generation.reference_images.length" class="grid gap-1.5">
                    <h2 class="label">Reference images</h2>
                    <a
                        v-for="url in generation.reference_images"
                        :key="url"
                        :href="url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="truncate font-mono text-xs text-fg-muted underline-offset-4 hover:text-fg hover:underline"
                    >{{ url }}</a>
                </div>

                <section class="grid gap-3" aria-labelledby="cost-title">
                    <h2 id="cost-title" class="label">Cost</h2>
                    <div v-if="generation.status === 'succeeded'" class="grid gap-1.5 rounded-md bg-ink-950 p-4 font-mono text-xs tabular-nums shadow-[inset_0_0_0_1px_var(--color-line)]">
                        <div v-for="line in receipt" :key="line.label" class="flex justify-between gap-3 text-fg-muted">
                            <span>{{ line.label }} <span class="text-fg-subtle">{{ line.tokens.toLocaleString() }} × ${{ line.rate }}/1M</span></span>
                            <span>{{ exactUsd(line.cost) }}</span>
                        </div>
                        <div class="mt-1.5 flex justify-between gap-3 border-t border-line pt-2.5 text-fg-muted">
                            <span>Pixelforge cost</span><span>{{ exactUsd(generation.provider_cost) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-fg-muted">
                            <span>Emberly fee, 25%</span><span>{{ exactUsd(generation.cost - generation.provider_cost) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-sm text-fg">
                            <span>Charged</span><span>{{ exactUsd(generation.cost) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-fg-subtle">
                            <span>Estimated before submitting</span><span>{{ exactUsd(generation.estimated_cost) }}</span>
                        </div>
                    </div>
                    <p v-else-if="generation.status === 'failed'" class="text-[13px] text-fg-muted">
                        Failed generations are free. The amount held for it went straight back to your balance.
                        <span class="mt-1 block font-mono text-xs text-fg-subtle">{{ generation.error?.code }}: {{ generation.error?.message }}</span>
                    </p>
                    <p v-else class="text-[13px] text-fg-muted">
                        Estimated <span class="font-mono text-fg">{{ usd(generation.estimated_cost) }}</span>. The exact price is known once Pixelforge reports the reasoning tokens it used.
                    </p>
                </section>

                <section class="grid gap-3" aria-labelledby="timeline-title">
                    <h2 id="timeline-title" class="label">Timeline</h2>
                    <ol class="grid gap-2.5">
                        <li v-for="(entry, index) in timeline" :key="index" class="grid grid-cols-[4.5rem_minmax(0,1fr)] gap-3 text-xs" :class="entry.muted ? 'text-fg-subtle' : 'text-fg-muted'">
                            <time :datetime="entry.at" class="font-mono tabular-nums">{{ time.format(new Date(entry.at)) }}</time>
                            <span class="grid gap-0.5">
                                <span :class="entry.muted ? '' : 'text-fg'">{{ entry.label }}</span>
                                <span v-if="entry.detail" class="text-fg-subtle">{{ entry.detail }}</span>
                            </span>
                        </li>
                    </ol>
                    <p v-if="isInFlight(generation)" class="hint">Updates arrive by webhook. If they stop, Emberly asks Pixelforge directly.</p>
                </section>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-secondary" @click="reuse">
                        <PhArrowsClockwise :size="16" aria-hidden="true" /> Use these settings
                    </button>
                    <a v-if="generation.status === 'succeeded'" :href="generation.image" target="_blank" class="btn btn-ghost">
                        <PhArrowSquareOut :size="16" aria-hidden="true" /> Open image
                    </a>
                </div>
            </div>
        </div>
    </dialog>
</template>
