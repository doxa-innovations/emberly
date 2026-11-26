<script setup>
import { useId } from 'vue';

const model = defineModel({ type: Boolean, required: true });
defineProps({
    label: { type: String, required: true },
    description: { type: String, default: null },
});

const id = useId();
</script>

<template>
    <div class="flex items-start justify-between gap-4">
        <label :for="id" class="grid cursor-pointer gap-0.5">
            <span class="label">{{ label }}</span>
            <span v-if="description" :id="`${id}-description`" class="hint">{{ description }}</span>
        </label>
        <input
            :id="id"
            v-model="model"
            class="switch mt-0.5"
            type="checkbox"
            switch
            role="switch"
            :aria-describedby="description ? `${id}-description` : undefined"
        />
    </div>
</template>
