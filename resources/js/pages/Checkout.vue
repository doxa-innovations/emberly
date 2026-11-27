<script setup>
import { Head, Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { PhCheck, PhCircleNotch } from '@phosphor-icons/vue';
import { computed, reactive, ref, watch } from 'vue';
import CreditCard from '../components/CreditCard.vue';
import SegmentedControl from '../components/SegmentedControl.vue';
import SwitchField from '../components/SwitchField.vue';
import { usd } from '../lib/money';

const props = defineProps({
    packs: { type: Array, required: true },
    payment: { type: Object, default: null },
});

const page = usePage();
const demoCard = () => ({ number: '4242 4242 4242 4242', name: page.props.auth.user.name, expiry: '12/30', cvc: '123' });

const dollars = ref(props.packs[1]);
const useDemoCard = ref(true);
const card = reactive(demoCard());
const cvcFocused = ref(false);
const paying = ref(false);
const waitedLong = ref(false);

const confirmed = computed(() => Boolean(props.payment?.entry));
const cardComplete = computed(() => card.number.replace(/\D/g, '').length >= 13 && card.name.trim() && /^(0[1-9]|1[0-2])\/\d{2}$/.test(card.expiry) && /^\d{3,4}$/.test(card.cvc));
const cardState = computed(() => (confirmed.value ? 'paid' : props.payment || paying.value ? 'paying' : 'idle'));

watch(useDemoCard, (enabled) => Object.assign(card, enabled ? demoCard() : { number: '', name: '', expiry: '', cvc: '' }));

const { start, stop } = usePoll(1000, { only: ['payment', 'auth'] }, { autoStart: false });
let slowTimer;

watch(
    () => props.payment && !confirmed.value,
    (waiting) => {
        clearTimeout(slowTimer);
        waitedLong.value = false;

        if (waiting) {
            start();
            slowTimer = setTimeout(() => (waitedLong.value = true), 20000);
        } else {
            stop();
        }
    },
    { immediate: true },
);

function formatNumber(event) {
    card.number = event.target.value.replace(/\D/g, '').slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 ');
}

function formatExpiry(event) {
    const digits = event.target.value.replace(/\D/g, '').slice(0, 4);
    card.expiry = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
}

function pay() {
    if (!cardComplete.value || paying.value) return;

    router.post('/credits/checkout', { dollars: dollars.value }, {
        preserveScroll: true,
        onStart: () => (paying.value = true),
        onFinish: () => (paying.value = false),
    });
}
</script>

<template>
    <Head title="Buy credits" />

    <div class="mx-auto grid max-w-5xl items-start gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,440px)] lg:gap-16">
        <div class="grid gap-8 lg:sticky lg:top-24">
            <div class="grid gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">Buy credits</h1>
                <p class="max-w-md text-sm text-fg-muted">Credits are US dollars. They go to your balance the moment the payment processor confirms the charge by webhook.</p>
            </div>
            <CreditCard v-bind="card" :flipped="cvcFocused" :state="cardState" />
            <p class="max-w-md rounded-md bg-ink-900 px-4 py-3 text-xs leading-relaxed text-fg-muted shadow-[inset_0_0_0_1px_var(--color-line)]">
                This is a demo checkout. No real payment is taken, and card details never leave your browser: the server only receives the amount.
            </p>
        </div>

        <section v-if="payment" class="panel grid gap-6 p-6" aria-live="polite">
            <div class="grid gap-1">
                <h2 class="text-base font-semibold">{{ confirmed ? 'Credits added' : 'Confirming your payment' }}</h2>
                <p class="font-mono text-xs text-fg-subtle">{{ payment.id }}</p>
            </div>

            <ol class="grid gap-4 text-sm">
                <li class="flex items-center gap-3">
                    <span class="grid size-6 place-items-center rounded-full bg-ok/15 text-ok"><PhCheck :size="13" weight="bold" aria-hidden="true" /></span>
                    Card authorized by the demo processor
                </li>
                <li class="flex items-center gap-3">
                    <span v-if="confirmed" class="grid size-6 place-items-center rounded-full bg-ok/15 text-ok"><PhCheck :size="13" weight="bold" aria-hidden="true" /></span>
                    <span v-else class="grid size-6 place-items-center rounded-full bg-ember-glow text-ember">
                        <PhCircleNotch :size="13" weight="bold" class="animate-spin motion-reduce:animate-none" aria-hidden="true" />
                    </span>
                    Signed payment webhook received and verified
                </li>
                <li class="flex items-center gap-3" :class="confirmed ? '' : 'text-fg-subtle'">
                    <span class="grid size-6 place-items-center rounded-full" :class="confirmed ? 'bg-ok/15 text-ok' : 'bg-ink-850'">
                        <PhCheck v-if="confirmed" :size="13" weight="bold" aria-hidden="true" />
                    </span>
                    Recorded once in your ledger
                </li>
            </ol>

            <p v-if="!confirmed && waitedLong" class="hint">Still waiting. The processor retries its webhook automatically, and this page updates when it lands.</p>

            <div v-if="confirmed" class="grid gap-5 border-t border-line pt-5">
                <div class="flex items-baseline justify-between">
                    <span class="text-sm text-fg-muted">Added</span>
                    <span class="font-mono text-lg text-ok tabular-nums">+{{ usd(payment.entry.amount) }}</span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-sm text-fg-muted">New balance</span>
                    <span class="font-mono text-lg tabular-nums">{{ usd(payment.entry.balance_after) }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link href="/studio" class="btn btn-primary">Start generating</Link>
                    <Link href="/credits/checkout" class="btn btn-ghost">Buy more</Link>
                </div>
            </div>
        </section>

        <form v-else class="panel grid gap-6 p-6" novalidate @submit.prevent="pay">
            <SegmentedControl v-model="dollars" legend="Amount" :options="packs.map((value) => ({ value, label: `$${value}` }))" />

            <SwitchField v-model="useDemoCard" label="Use the demo card" description="Fills in test details so you can just press pay." />

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <label for="cc-number" class="label">Card number</label>
                    <input
                        id="cc-number"
                        :value="card.number"
                        class="field font-mono tracking-wide"
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-number"
                        maxlength="23"
                        enterkeyhint="next"
                        placeholder="1234 1234 1234 1234"
                        required
                        @input="formatNumber"
                    />
                </div>
                <div class="grid gap-2">
                    <label for="cc-name" class="label">Name on card</label>
                    <input id="cc-name" v-model="card.name" class="field" type="text" autocomplete="cc-name" maxlength="50" enterkeyhint="next" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <label for="cc-exp" class="label">Expiry</label>
                        <span id="exp-hint" class="hint -mt-1">MM/YY</span>
                        <input
                            id="cc-exp"
                            :value="card.expiry"
                            class="field font-mono"
                            type="text"
                            inputmode="numeric"
                            autocomplete="cc-exp"
                            maxlength="5"
                            enterkeyhint="next"
                            aria-describedby="exp-hint"
                            required
                            @input="formatExpiry"
                        />
                    </div>
                    <div class="grid gap-2">
                        <label for="cc-csc" class="label">Security code</label>
                        <span id="csc-hint" class="hint -mt-1">3 or 4 digits</span>
                        <input
                            id="cc-csc"
                            v-model="card.cvc"
                            class="field font-mono"
                            type="text"
                            inputmode="numeric"
                            autocomplete="cc-csc"
                            maxlength="4"
                            enterkeyhint="done"
                            aria-describedby="csc-hint"
                            required
                            @focus="cvcFocused = true"
                            @blur="cvcFocused = false"
                        />
                    </div>
                </div>
            </div>

            <div class="grid gap-3 border-t border-line pt-5">
                <div class="flex items-baseline justify-between text-sm">
                    <span class="text-fg-muted">You get</span>
                    <span><span class="font-mono tabular-nums">{{ usd(dollars * 1_000_000_000) }}</span> in credits</span>
                </div>
                <button type="submit" class="btn btn-primary h-11 text-[15px]" :disabled="!cardComplete || paying">
                    {{ paying ? 'Authorizing' : `Pay $${dollars}.00` }}
                </button>
            </div>
        </form>
    </div>
</template>
