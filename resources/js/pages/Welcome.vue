<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { PhArrowsClockwise, PhFire, PhGoogleLogo, PhImages, PhReceipt, PhShieldCheck } from '@phosphor-icons/vue';
import { ref } from 'vue';
import BillingReceipt from '../components/BillingReceipt.vue';
import StatusBadge from '../components/StatusBadge.vue';

defineProps({ googleEnabled: { type: Boolean, required: true } });

const page = usePage();
const operator = useForm({ email: '', password: '' });
const showOperator = ref(false);

const steps = [
    {
        title: 'See the price first',
        body: 'Pick a size and quality and the estimate updates from Pixelforge’s live rates before you submit.',
        amount: '≈ $0.40245',
    },
    {
        title: 'Hold the maximum',
        body: 'Submitting holds the most it could cost, so a generation can never overdraw your balance.',
        amount: '- $0.45715',
    },
    {
        title: 'Pay what it used',
        body: 'When the image lands you pay the exact cost of its tokens plus 25%, and the hold is released.',
        amount: '- $0.3818875',
    },
];

const statuses = [
    { status: 'queued', body: 'Waiting for a slot at Pixelforge.' },
    { status: 'processing', body: 'Rendering. The amount stays on hold.' },
    { status: 'succeeded', body: 'Image saved. You pay what it used.' },
    { status: 'failed', body: 'Nothing charged. The hold is released.' },
];

const guarantees = [
    {
        icon: PhReceipt,
        title: 'An append-only ledger',
        body: 'Purchases, holds, charges and releases are written once and never edited. Every entry records the balance after it.',
    },
    {
        icon: PhShieldCheck,
        title: 'Verified webhooks',
        body: 'Each delivery is signed and checked. Duplicates are ignored, and a finished image never moves backwards.',
    },
    {
        icon: PhArrowsClockwise,
        title: 'Retries that never double-charge',
        body: 'Every submission carries an idempotency key. If Pixelforge is busy, Emberly waits and tries again.',
    },
    {
        icon: PhImages,
        title: 'Images that outlive the link',
        body: 'Finished images are copied to storage, so your history stays after Pixelforge’s 24-hour URLs expire.',
    },
];

const keywords = [
    { chip: '[succeed]', body: 'A normal, successful image.' },
    { chip: '[fail]', body: 'A failure: watch the hold come back.' },
    { chip: '[slow]', body: 'A long render that stays visible.' },
    { chip: '[duplicate]', body: 'A webhook sent twice, charged once.' },
    { chip: '[blocked]', body: 'A prompt the provider refuses.' },
    { chip: '[overloaded]', body: 'A busy provider, retried politely.' },
];

const questions = [
    {
        q: 'What does an image cost?',
        a: 'Pixelforge’s exact cost for the tokens it used, plus 25%. Amounts are kept to the nanodollar, so nothing is rounded away.',
    },
    { q: 'What if a generation fails?', a: 'You pay nothing. The amount held when you submitted goes straight back to your balance.' },
    {
        q: 'Why is more held than I end up paying?',
        a: 'The hold covers the most a generation could cost. Once it finishes, you pay the real amount and the difference is released.',
    },
    {
        q: 'Are the credits real money?',
        a: 'No. Buying credits is a demo checkout with a test card. Only the confirmed payment adds credit, and only once.',
    },
];
</script>

<template>
    <Head title="Images priced to the nanodollar" />

    <div class="mx-auto grid max-w-[1200px] px-6">
        <header class="flex h-16 items-center justify-between border-b border-line">
            <a href="/" class="flex items-center gap-2 text-[15px] font-semibold tracking-tight">
                <PhFire :size="22" weight="fill" class="text-ember" aria-hidden="true" />
                Emberly
            </a>
            <nav aria-label="Page sections">
                <ul class="flex items-center gap-1 text-sm">
                    <li class="hidden sm:block"><a href="#how" class="btn btn-ghost h-9 px-3">How it works</a></li>
                    <li class="hidden sm:block"><a href="#questions" class="btn btn-ghost h-9 px-3">Questions</a></li>
                    <li><a href="#sign-in" class="btn btn-secondary h-9 px-3">Sign in</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section
                id="sign-in"
                aria-labelledby="hero-title"
                class="grid scroll-mt-6 items-center gap-12 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,460px)] lg:gap-20 lg:py-24"
            >
                <div class="grid max-w-xl gap-8">
                    <div class="grid gap-5">
                        <h1 id="hero-title" class="text-[clamp(2.25rem,1.6rem+2.6vw,3.75rem)] leading-[1.05] font-semibold tracking-tight">
                            Images from a prompt, priced to the nanodollar.
                        </h1>
                        <p class="max-w-md text-lg leading-relaxed text-fg-muted">
                            See the cost before you generate. Pay Pixelforge’s exact price plus 25%, and nothing when an image fails.
                        </p>
                    </div>

                    <div class="grid max-w-sm gap-4">
                        <p v-if="page.props.toast" class="text-sm text-bad" role="alert">{{ page.props.toast }}</p>

                        <a v-if="googleEnabled" href="/auth/google" class="btn btn-primary h-11 text-[15px]">
                            <PhGoogleLogo :size="18" weight="bold" aria-hidden="true" /> Continue with Google
                        </a>
                        <p v-else class="text-sm text-fg-muted">Google sign-in isn’t configured in this environment yet.</p>

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
                                    inputmode="email"
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

                <BillingReceipt />
            </section>

            <section id="how" aria-labelledby="how-title" class="grid scroll-mt-6 gap-10 border-t border-line py-20">
                <div class="grid max-w-xl gap-3">
                    <h2 id="how-title" class="text-3xl font-semibold tracking-tight">Three steps, every amount shown</h2>
                    <p class="text-fg-muted">The money moves the same way for every image, and you can follow each step in your history.</p>
                </div>
                <ol class="grid gap-4 md:grid-cols-3">
                    <li v-for="(step, index) in steps" :key="step.title" class="panel grid content-start gap-4 p-6">
                        <span class="font-mono text-xs text-fg-subtle tabular-nums">0{{ index + 1 }}</span>
                        <h3 class="text-lg font-medium tracking-tight">{{ step.title }}</h3>
                        <p class="text-sm leading-relaxed text-fg-muted">{{ step.body }}</p>
                        <p class="font-mono text-sm text-fg tabular-nums">{{ step.amount }}</p>
                    </li>
                </ol>
            </section>

            <section aria-labelledby="status-title" class="grid gap-10 border-t border-line py-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] lg:gap-20">
                <div class="grid content-start gap-3">
                    <h2 id="status-title" class="text-3xl font-semibold tracking-tight">Nothing waits in silence</h2>
                    <p class="text-fg-muted">Every generation shows where it is. While one is running, the studio checks for news every two seconds.</p>
                </div>
                <ul class="panel divide-y divide-line">
                    <li v-for="item in statuses" :key="item.status" class="flex items-center gap-4 px-6 py-4">
                        <span class="w-28 shrink-0"><StatusBadge :status="item.status" /></span>
                        <span class="text-sm text-fg-muted">{{ item.body }}</span>
                    </li>
                </ul>
            </section>

            <section aria-labelledby="trust-title" class="grid gap-10 border-t border-line py-20">
                <div class="grid max-w-xl gap-3">
                    <h2 id="trust-title" class="text-3xl font-semibold tracking-tight">Correct money comes first</h2>
                    <p class="text-fg-muted">Payments and provider events arrive late, twice or not at all. Emberly is built so none of that changes what you pay.</p>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2">
                    <li v-for="item in guarantees" :key="item.title" class="panel grid content-start gap-3 p-6">
                        <component :is="item.icon" :size="20" class="text-fg-muted" aria-hidden="true" />
                        <h3 class="font-medium tracking-tight">{{ item.title }}</h3>
                        <p class="text-sm leading-relaxed text-fg-muted">{{ item.body }}</p>
                    </li>
                </ul>
            </section>

            <section aria-labelledby="keywords-title" class="grid gap-10 border-t border-line py-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] lg:gap-20">
                <div class="grid content-start gap-3">
                    <h2 id="keywords-title" class="text-3xl font-semibold tracking-tight">Try the hard cases</h2>
                    <p class="text-fg-muted">Add a keyword to a prompt to make Pixelforge misbehave on purpose, then check the receipt.</p>
                </div>
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div v-for="item in keywords" :key="item.chip" class="grid gap-1.5">
                        <dt>
                            <code class="inline-flex h-7 items-center rounded-full bg-ink-850 px-3 font-mono text-xs text-fg shadow-[inset_0_0_0_1px_var(--color-line-strong)]">
                                {{ item.chip }}
                            </code>
                        </dt>
                        <dd class="text-sm text-fg-muted">{{ item.body }}</dd>
                    </div>
                </dl>
            </section>

            <section id="questions" aria-labelledby="questions-title" class="grid scroll-mt-6 gap-10 border-t border-line py-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] lg:gap-20">
                <h2 id="questions-title" class="text-3xl font-semibold tracking-tight">Questions</h2>
                <div class="panel divide-y divide-line">
                    <details v-for="item in questions" :key="item.q" name="faq" class="group px-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 font-medium [&::-webkit-details-marker]:hidden">
                            {{ item.q }}
                            <span class="font-mono text-fg-subtle transition-transform duration-200 group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="pb-5 text-sm leading-relaxed text-fg-muted">{{ item.a }}</p>
                    </details>
                </div>
            </section>

            <section aria-labelledby="cta-title" class="panel my-20 grid justify-items-start gap-6 p-8 sm:p-12">
                <h2 id="cta-title" class="max-w-lg text-3xl font-semibold tracking-tight">Know the price before the first pixel.</h2>
                <a :href="googleEnabled ? '/auth/google' : '#sign-in'" class="btn btn-primary h-11 text-[15px]">
                    <PhGoogleLogo v-if="googleEnabled" :size="18" weight="bold" aria-hidden="true" />
                    {{ googleEnabled ? 'Continue with Google' : 'Sign in' }}
                </a>
            </section>
        </main>

        <footer class="flex flex-wrap items-center justify-between gap-4 border-t border-line py-8 text-sm text-fg-subtle">
            <span class="flex items-center gap-2">
                <PhFire :size="16" weight="fill" class="text-ember" aria-hidden="true" />
                Emberly
            </span>
            <span>Built on the <a href="https://pixelforge.promptedit.dev/docs" class="underline underline-offset-4 hover:text-fg">Pixelforge API</a></span>
        </footer>
    </div>
</template>
