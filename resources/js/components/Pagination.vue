<script setup>
import { Link } from '@inertiajs/vue3';
import { PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue';

defineProps({
    prev: { type: String, default: null },
    next: { type: String, default: null },
    current: { type: Number, required: true },
    last: { type: Number, required: true },
});
</script>

<template>
    <nav v-if="last > 1" aria-label="Pagination" class="flex items-center justify-between gap-4 pt-6">
        <component :is="prev ? Link : 'span'" :href="prev" preserve-scroll class="btn btn-secondary" :aria-disabled="!prev" :class="{ 'pointer-events-none opacity-40': !prev }">
            <PhCaretLeft :size="16" aria-hidden="true" /> Newer
        </component>
        <span class="hint font-mono tabular-nums">Page {{ current }} of {{ last }}</span>
        <component :is="next ? Link : 'span'" :href="next" preserve-scroll class="btn btn-secondary" :aria-disabled="!next" :class="{ 'pointer-events-none opacity-40': !next }">
            Older <PhCaretRight :size="16" aria-hidden="true" />
        </component>
    </nav>
</template>
