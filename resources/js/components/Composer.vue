<script setup>
import { Link, useForm, useHttp, usePage } from '@inertiajs/vue3';
import { PhPlus, PhX } from '@phosphor-icons/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { QUALITIES, SIZES, TEST_KEYWORDS } from '../lib/generation';
import { usd } from '../lib/money';
import AnimatedMoney from './AnimatedMoney.vue';
import SegmentedControl from './SegmentedControl.vue';
import SwitchField from './SwitchField.vue';

const PROMPT_LIMIT = 4000;
const MAX_REFERENCES = 4;

const page = usePage();
const form = useForm({ prompt: '', size: SIZES[0].value, quality: 'medium', reference_images: [] });
const quote = useHttp({ characters: 0, size: form.size, quality: form.quality, references: 0 });
const withReferences = ref(false);
const quoteFailed = ref(false);

const characters = computed(() => [...form.prompt].length);
const references = computed(() => form.reference_images.filter((url) => url.trim() !== ''));
const balance = computed(() => page.props.auth.user.balance);
const maximum = computed(() => quote.response?.maximum ?? null);
const affordable = computed(() => maximum.value === null || balance.value >= maximum.value);

watch(withReferences, (enabled) => {
    if (enabled !== form.reference_images.length > 0) {
        form.reference_images = enabled ? [''] : [];
    }
});

let quoteTimer;

watch(
    [characters, () => form.size, () => form.quality, () => references.value.length],
    ([chars, size, quality, refs], previous) => {
        Object.assign(quote, { characters: Math.min(chars, PROMPT_LIMIT), size, quality, references: Math.min(refs, MAX_REFERENCES) });
        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(refreshQuote, previous && previous[0] !== chars ? 250 : 0);
    },
    { immediate: true },
);

async function refreshQuote() {
    quote.cancel();

    try {
        await quote.get('/quote');
        quoteFailed.value = false;
    } catch {
        quoteFailed.value = true;
    }
}

function toggleKeyword(keyword) {
    form.prompt = form.prompt.includes(keyword)
        ? form.prompt.replace(keyword, '').replace(/\s{2,}/g, ' ').trim()
        : `${form.prompt.trim()} ${keyword}`.trim();
}

function submitOnShortcut(event) {
    if ((event.metaKey || event.ctrlKey) && !event.isComposing) {
        event.preventDefault();
        submit();
    }
}

function submit() {
    if (form.processing || !form.prompt.trim() || !affordable.value) return;

    form.transform((data) => ({ ...data, reference_images: references.value })).post('/generations', { preserveScroll: true });
}

async function reuse(generation) {
    form.clearErrors();
    Object.assign(form, { prompt: generation.prompt, size: generation.size, quality: generation.quality });
    form.reference_images = [...generation.reference_images];
    withReferences.value = generation.reference_images.length > 0;
    await nextTick();
    document.getElementById('prompt')?.focus();
}

defineExpose({ reuse });
</script>

<template>
    <form class="panel grid gap-6 p-5" novalidate @submit.prevent="submit">
        <div class="grid gap-2">
            <div class="flex items-baseline justify-between gap-3">
                <label for="prompt" class="label">Prompt</label>
                <span class="hint font-mono tabular-nums" :class="{ 'text-bad': characters > PROMPT_LIMIT }">
                    {{ characters.toLocaleString() }} / {{ PROMPT_LIMIT.toLocaleString() }}
                </span>
            </div>
            <textarea
                id="prompt"
                v-model="form.prompt"
                class="field"
                placeholder="A lighthouse on a cliff at dawn, oil painting"
                enterkeyhint="send"
                :aria-invalid="form.errors.prompt ? 'true' : undefined"
                :aria-describedby="form.errors.prompt ? 'prompt-error' : 'prompt-hint'"
                @keydown.enter="submitOnShortcut"
            />
            <p v-if="form.errors.prompt" id="prompt-error" class="text-xs text-bad">{{ form.errors.prompt }}</p>
            <p v-else id="prompt-hint" class="hint">Press <kbd class="font-mono">Ctrl Enter</kbd> to generate.</p>

            <div class="mt-1 grid gap-2">
                <span id="keywords-label" class="hint">Test how the app copes with Pixelforge</span>
                <div class="flex flex-wrap gap-1.5" role="group" aria-labelledby="keywords-label">
                    <button
                        v-for="{ keyword, hint } in TEST_KEYWORDS"
                        :key="keyword"
                        type="button"
                        class="h-7 rounded-full px-2.5 font-mono text-xs transition-colors"
                        :class="form.prompt.includes(keyword)
                            ? 'bg-ember-glow text-ember shadow-[inset_0_0_0_1px_var(--color-ember)]'
                            : 'bg-ink-850 text-fg-muted shadow-[inset_0_0_0_1px_var(--color-line-strong)] hover:text-fg'"
                        :aria-pressed="form.prompt.includes(keyword)"
                        :title="hint"
                        @click="toggleKeyword(keyword)"
                    >
                        {{ keyword }}
                    </button>
                </div>
            </div>
        </div>

        <SegmentedControl v-model="form.size" legend="Size" :options="SIZES">
            <template #default="{ option }">
                <span class="block h-3.5 rounded-[2px] border-[1.5px] border-current" :style="{ aspectRatio: option.ratio }" aria-hidden="true" />
                <span>{{ option.label }}</span>
            </template>
        </SegmentedControl>

        <SegmentedControl v-model="form.quality" legend="Quality" :options="QUALITIES" />

        <div class="grid gap-3">
            <SwitchField v-model="withReferences" label="Reference images" description="Up to 4 image URLs to guide the style. Each adds 765 input tokens." />

            <div v-if="withReferences" class="grid gap-2">
                <div v-for="(url, index) in form.reference_images" :key="index" class="grid gap-1">
                    <div class="flex gap-2">
                        <label :for="`reference-${index}`" class="sr-only">Reference image {{ index + 1 }}</label>
                        <input
                            :id="`reference-${index}`"
                            v-model="form.reference_images[index]"
                            class="field font-mono text-[13px]"
                            type="url"
                            inputmode="url"
                            autocomplete="url"
                            placeholder="https://example.com/style.png"
                            :aria-invalid="form.errors[`reference_images.${index}`] ? 'true' : undefined"
                        />
                        <button type="button" class="btn btn-secondary w-10 shrink-0 px-0" :aria-label="`Remove reference image ${index + 1}`" @click="form.reference_images.splice(index, 1)">
                            <PhX :size="14" aria-hidden="true" />
                        </button>
                    </div>
                    <p v-if="form.errors[`reference_images.${index}`]" class="text-xs text-bad">{{ form.errors[`reference_images.${index}`] }}</p>
                </div>
                <p v-if="form.errors.reference_images" class="text-xs text-bad">{{ form.errors.reference_images }}</p>
                <button
                    v-if="form.reference_images.length < MAX_REFERENCES"
                    type="button"
                    class="btn btn-ghost h-8 justify-self-start px-2 text-[13px]"
                    @click="form.reference_images.push('')"
                >
                    <PhPlus :size="14" aria-hidden="true" /> Add another
                </button>
            </div>
        </div>

        <div class="grid min-h-[6.5rem] content-start gap-2 rounded-md bg-ink-950 p-4 shadow-[inset_0_0_0_1px_var(--color-line)]" aria-live="polite">
            <div class="flex items-baseline justify-between gap-3">
                <span class="label">Estimated cost</span>
                <AnimatedMoney v-if="quote.response" :value="quote.response.estimate" class="text-xl font-medium" />
                <span v-else-if="!quoteFailed" class="skeleton h-6 w-24 rounded" aria-hidden="true" />
            </div>
            <p v-if="quoteFailed" class="text-xs text-bad">Pixelforge's pricing is unavailable right now. Try again in a moment.</p>
            <p v-else-if="maximum !== null" class="hint">
                Pixelforge's price plus 25%. We hold up to <span class="font-mono text-fg-muted">{{ usd(maximum) }}</span> while it runs and charge only the exact final cost.
            </p>
            <p v-if="!affordable" class="mt-1 text-[13px] text-fg">
                You have <span class="font-mono">{{ usd(balance) }}</span>.
                <Link href="/credits/checkout" class="font-medium text-ember underline-offset-4 hover:underline">Buy credits</Link> to generate.
            </p>
        </div>

        <button type="submit" class="btn btn-primary h-11 w-full text-[15px]" :disabled="form.processing || !form.prompt.trim() || !affordable || characters > PROMPT_LIMIT">
            {{ form.processing ? 'Queuing' : 'Generate' }}
        </button>
    </form>
</template>
