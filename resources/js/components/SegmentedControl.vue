<script setup>
import { computed, useId } from 'vue';

const model = defineModel({ required: true });
const props = defineProps({
    legend: { type: String, required: true },
    options: { type: Array, required: true },
});

const name = useId();
const index = computed(() => Math.max(0, props.options.findIndex((option) => option.value === model.value)));
</script>

<template>
    <fieldset class="grid gap-2">
        <legend class="label mb-2">{{ legend }}</legend>
        <div class="segmented" :style="{ '--count': options.length, '--index': index }">
            <span data-thumb aria-hidden="true" />
            <label v-for="option in options" :key="option.value">
                <input v-model="model" class="sr-only" type="radio" :name="name" :value="option.value" />
                <slot :option="option" :selected="option.value === model">{{ option.label }}</slot>
            </label>
        </div>
    </fieldset>
</template>
