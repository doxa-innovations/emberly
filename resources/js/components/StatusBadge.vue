<script setup>
defineProps({ status: { type: String, required: true } });

const styles = {
    queued: 'text-fg-muted bg-ink-800',
    processing: 'text-ember bg-ember-glow',
    succeeded: 'text-ok bg-ok/10',
    failed: 'text-bad bg-bad/10',
};
</script>

<template>
    <span class="inline-flex h-6 items-center gap-1.5 rounded-full px-2.5 text-xs font-medium capitalize" :class="styles[status]">
        <span v-if="status === 'processing'" class="size-1.5 animate-pulse rounded-full bg-ember motion-reduce:animate-none" aria-hidden="true" />
        {{ status }}
    </span>
</template>
