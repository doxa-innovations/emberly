<script setup>
import { Head, usePoll } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Composer from '../components/Composer.vue';
import GenerationCard from '../components/GenerationCard.vue';
import GenerationDialog from '../components/GenerationDialog.vue';
import Pagination from '../components/Pagination.vue';
import { isInFlight } from '../lib/generation';

const props = defineProps({ generations: { type: Object, required: true } });

const composer = ref(null);
const selectedId = ref(null);
const selected = computed(() => props.generations.data.find((generation) => generation.id === selectedId.value) ?? null);
const inFlight = computed(() => props.generations.data.filter(isInFlight).length);

const { start, stop } = usePoll(2000, { only: ['generations', 'auth'] }, { autoStart: false });

watch(inFlight, (count) => (count ? start() : stop()), { immediate: true });
</script>

<template>
    <Head title="Studio" />

    <div class="grid gap-8 lg:grid-cols-[400px_minmax(0,1fr)] xl:gap-10">
        <aside class="lg:sticky lg:top-20 lg:max-h-[calc(100dvh-6rem)] lg:self-start lg:overflow-y-auto lg:[scrollbar-width:thin]">
            <Composer ref="composer" />
        </aside>

        <section aria-labelledby="history-title" class="min-w-0">
            <div class="mb-5 flex items-baseline justify-between gap-4">
                <h1 id="history-title" class="text-lg font-semibold tracking-tight">Your images</h1>
                <p class="hint" aria-live="polite">
                    <template v-if="inFlight">{{ inFlight }} in progress, updating live</template>
                    <template v-else>{{ generations.meta.total.toLocaleString() }} total</template>
                </p>
            </div>

            <div v-if="generations.data.length" class="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-x-4 gap-y-6">
                <GenerationCard v-for="generation in generations.data" :key="generation.id" :generation="generation" @open="selectedId = generation.id" />
            </div>

            <div v-else class="grid min-h-[420px] place-content-center justify-items-center gap-3 rounded-xl border border-dashed border-line-strong p-10 text-center">
                <h2 class="text-base font-medium">Nothing developed yet</h2>
                <p class="max-w-sm text-sm text-fg-muted">
                    Write a prompt, pick a size and quality, and the estimate shows what it will cost before you spend anything.
                </p>
            </div>

            <Pagination :prev="generations.links.prev" :next="generations.links.next" :current="generations.meta.current_page" :last="generations.meta.last_page" />
        </section>
    </div>

    <GenerationDialog :generation="selected" @close="selectedId = null" @reuse="composer.reuse($event)" />
</template>
