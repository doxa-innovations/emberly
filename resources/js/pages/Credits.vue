<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { PhArrowDownLeft, PhArrowUpRight, PhLockSimple, PhLockSimpleOpen } from '@phosphor-icons/vue';
import { computed } from 'vue';
import AnimatedMoney from '../components/AnimatedMoney.vue';
import Pagination from '../components/Pagination.vue';
import { exactUsd, usd } from '../lib/money';

defineProps({
    reserved: { type: Number, required: true },
    entries: { type: Object, required: true },
});

const page = usePage();
const balance = computed(() => page.props.auth.user.balance);
const when = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

const kinds = {
    purchase: { label: 'Bought credits', icon: PhArrowDownLeft, tone: 'text-ok' },
    hold: { label: 'Held while generating', icon: PhLockSimple, tone: 'text-fg-muted' },
    release: { label: 'Hold released', icon: PhLockSimpleOpen, tone: 'text-fg-muted' },
    charge: { label: 'Charged', icon: PhArrowUpRight, tone: 'text-fg' },
};
</script>

<template>
    <Head title="Credits" />

    <div class="mx-auto grid max-w-3xl gap-10">
        <section class="flex flex-wrap items-end justify-between gap-6">
            <div class="grid gap-2">
                <h1 class="text-sm font-medium text-fg-muted">Available balance</h1>
                <AnimatedMoney :value="balance" class="text-4xl font-medium tracking-tight sm:text-5xl" />
                <p class="hint">
                    <template v-if="reserved">
                        <span class="font-mono text-fg-muted">{{ usd(reserved) }}</span> is held for generations in progress.
                    </template>
                    <template v-else>Credits are US dollars. Nothing is held right now.</template>
                </p>
            </div>
            <Link href="/credits/checkout" class="btn btn-primary h-11 px-5 text-[15px]">Buy credits</Link>
        </section>

        <section aria-labelledby="activity-title" class="grid gap-4">
            <div class="grid gap-1">
                <h2 id="activity-title" class="text-base font-semibold">Activity</h2>
                <p class="hint">An append-only ledger. Each generation holds its maximum cost, then releases the hold and charges the exact amount.</p>
            </div>

            <ol v-if="entries.data.length" class="panel divide-y divide-line">
                <li v-for="entry in entries.data" :key="entry.id" class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-4 px-4 py-3.5">
                    <span class="grid size-8 place-items-center rounded-full bg-ink-850" :class="kinds[entry.type].tone">
                        <component :is="kinds[entry.type].icon" :size="15" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm">{{ kinds[entry.type].label }}</p>
                        <p class="truncate text-xs text-fg-subtle">
                            <time :datetime="entry.created_at">{{ when.format(new Date(entry.created_at)) }}</time>
                            <template v-if="entry.generation">, {{ entry.generation.prompt }}</template>
                            <template v-else-if="entry.reference">, <span class="font-mono">{{ entry.reference }}</span></template>
                        </p>
                    </div>
                    <div class="text-right font-mono text-sm tabular-nums">
                        <p :class="entry.amount > 0 ? 'text-ok' : 'text-fg'" :title="exactUsd(entry.amount)">{{ entry.amount > 0 ? '+' : '' }}{{ usd(entry.amount) }}</p>
                        <p class="text-xs text-fg-subtle" :title="exactUsd(entry.balance_after)">{{ usd(entry.balance_after) }}</p>
                    </div>
                </li>
            </ol>

            <div v-else class="grid place-content-center justify-items-center gap-3 rounded-xl border border-dashed border-line-strong p-10 text-center">
                <p class="text-sm text-fg-muted">No activity yet. Buy credits to start generating.</p>
                <Link href="/credits/checkout" class="btn btn-secondary">Buy credits</Link>
            </div>

            <Pagination :prev="entries.prev_page_url" :next="entries.next_page_url" :current="entries.current_page" :last="entries.last_page" />
        </section>
    </div>
</template>
