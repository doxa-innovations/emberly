<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { PhFire, PhGoogleLogo } from '@phosphor-icons/vue';
import { gsap } from 'gsap';
import { onMounted, onUnmounted, ref } from 'vue';
import { reducedMotion } from '../lib/motion';

defineProps({ googleEnabled: { type: Boolean, required: true } });

const page = usePage();
const operator = useForm({ email: '', password: '' });
const showOperator = ref(false);
const receipt = ref(null);
let context;

const lines = [
    { label: 'Estimate shown before you submit', amount: '$0.40245', tone: 'text-fg-muted' },
    { label: 'Held from your balance while it runs', amount: '- $0.45715', tone: 'text-fg-muted' },
    { label: 'Pixelforge cost from the tokens used', amount: '$0.30551', tone: 'text-fg-muted' },
    { label: 'Charged, cost plus 25%', amount: '- $0.3818875', tone: 'text-fg' },
    { label: 'Hold released', amount: '+ $0.45715', tone: 'text-ok' },
];

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
    <Head title="Sign in" />

    <div class="mx-auto grid min-h-dvh max-w-[1200px] items-center gap-12 px-6 py-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,460px)] lg:gap-20">
        <div class="grid max-w-xl gap-8">
            <div class="flex items-center gap-2 text-[15px] font-semibold tracking-tight">
                <PhFire :size="22" weight="fill" class="text-ember" aria-hidden="true" />
                Emberly
            </div>

            <div class="grid gap-5">
                <h1 class="text-4xl leading-[1.05] font-semibold tracking-tight sm:text-5xl">Images from a prompt, priced to the nanodollar.</h1>
                <p class="max-w-md text-lg leading-relaxed text-fg-muted">
                    See the cost before you generate. Pay Pixelforge's exact price plus 25%, and nothing when an image fails.
                </p>
            </div>

            <div class="grid max-w-sm gap-4">
                <p v-if="page.props.toast" class="text-sm text-bad" role="alert">{{ page.props.toast }}</p>

                <a v-if="googleEnabled" href="/auth/google" class="btn btn-primary h-11 text-[15px]">
                    <PhGoogleLogo :size="18" weight="bold" aria-hidden="true" /> Continue with Google
                </a>
                <p v-else class="text-sm text-fg-muted">Google sign-in isn't configured in this environment yet.</p>

                <button
                    type="button"
                    class="justify-self-start text-sm text-fg-muted underline-offset-4 hover:text-fg hover:underline"
                    :aria-expanded="showOperator"
                    aria-controls="operator-form"
                    @click="showOperator = !showOperator"
                >
                    Operator sign-in
                </button>

                <form v-if="showOperator" id="operator-form" class="panel grid gap-4 p-5" @submit.prevent="operator.post('/login')">
                    <div class="grid gap-2">
                        <label for="email" class="label">Email</label>
                        <input
                            id="email"
                            v-model="operator.email"
                            class="field"
                            type="email"
                            autocomplete="username"
                            required
                            :aria-invalid="operator.errors.email ? 'true' : undefined"
                            :aria-describedby="operator.errors.email ? 'email-error' : undefined"
                        />
                        <p v-if="operator.errors.email" id="email-error" class="text-xs text-bad">{{ operator.errors.email }}</p>
                    </div>
                    <div class="grid gap-2">
                        <label for="password" class="label">Password</label>
                        <input id="password" v-model="operator.password" class="field" type="password" autocomplete="current-password" required />
                    </div>
                    <button type="submit" class="btn btn-secondary" :disabled="operator.processing">Sign in as operator</button>
                </form>
            </div>
        </div>

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
    </div>
</template>
