<script setup>
import { onUnmounted, ref, watch } from 'vue';
import { exactUsd, usd } from '../lib/money';
import { tweenNumber } from '../lib/motion';

const props = defineProps({ value: { type: Number, required: true } });

const shown = ref(props.value);
let tween = null;

watch(() => props.value, (to) => {
    tween?.kill();
    tween = tweenNumber(shown.value, to, (value) => (shown.value = value));
});

onUnmounted(() => tween?.kill());
</script>

<template>
    <span class="font-mono tabular-nums" :title="exactUsd(value)">{{ usd(shown) }}</span>
</template>
