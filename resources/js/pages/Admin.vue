<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { PhCheckCircle, PhWarning, PhX } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import SegmentedControl from '../components/SegmentedControl.vue';
import { exactUsd, usd } from '../lib/money';

const props = defineProps({
    ranges: { type: Array, required: true },
    days: { type: Number, required: true },
    user: { type: Object, default: null },
    report: { type: Object, required: true },
});

const range = ref(props.days);
const dateFormat = new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'UTC' });

const mismatches = computed(() => props.report.days.filter((day) => day.pixelforge !== null && day.pixelforge !== day.provider_cost).length);
const totals = computed(() => [
    { label: 'Charged to users', value: props.report.totals.charged },
    { label: 'Pixelforge cost', value: props.report.totals.provider_cost },
    { label: 'Profit', value: props.report.totals.profit },
]);

watch(range, (days) => router.get('/admin', { days, user: props.user?.id }, { preserveState: true, preserveScroll: true, replace: true }));
</script>

<template>
    <Head title="Report" />

    <div class="grid gap-10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="grid gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">Spend and profit</h1>
                <p class="text-sm text-fg-muted">Days are UTC and grouped by completion time, the way Pixelforge bills.</p>
            </div>
            <div class="w-72">
                <SegmentedControl v-model="range" legend="Period" :options="ranges.map((value) => ({ value, label: `${value} days` }))" />
            </div>
        </div>

        <div v-if="user" class="flex flex-wrap items-center gap-3 text-sm">
            <span class="text-fg-muted">Showing</span>
            <span class="font-medium">{{ user.name }}</span>
            <span class="text-fg-subtle">{{ user.email }}</span>
            <Link :href="`/admin?days=${days}`" class="btn btn-ghost h-8 px-2 text-[13px]" preserve-scroll>
                <PhX :size="14" aria-hidden="true" /> Everyone
            </Link>
        </div>

        <section class="grid gap-6 sm:grid-cols-3" aria-label="Totals">
            <div v-for="total in totals" :key="total.label" class="grid gap-1 border-l border-line ps-4">
                <span class="text-sm text-fg-muted">{{ total.label }}</span>
                <span class="font-mono text-2xl tracking-tight tabular-nums" :title="exactUsd(total.value)">{{ usd(total.value) }}</span>
                <span class="font-mono text-xs text-fg-subtle tabular-nums">{{ exactUsd(total.value) }}</span>
            </div>
        </section>

        <p v-if="report.reconciled" class="flex items-center gap-2 text-sm" :class="mismatches ? 'text-bad' : 'text-ok'">
            <component :is="mismatches ? PhWarning : PhCheckCircle" :size="18" weight="fill" aria-hidden="true" />
            <template v-if="mismatches">{{ mismatches }} {{ mismatches === 1 ? 'day differs' : 'days differ' }} from Pixelforge's usage report.</template>
            <template v-else>Provider costs match Pixelforge's usage report for every day in this period.</template>
        </p>
        <p v-else-if="!user" class="flex items-center gap-2 text-sm text-fg-muted">
            <PhWarning :size="18" aria-hidden="true" /> Pixelforge's usage report is unavailable right now, so costs could not be compared.
        </p>

        <section aria-labelledby="days-title" class="grid gap-3">
            <h2 id="days-title" class="text-base font-semibold">By day</h2>
            <div class="panel overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="text-left text-xs text-fg-subtle">
                        <tr class="border-b border-line">
                            <th scope="col" class="px-4 py-3 font-medium">Day</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Images</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Charged</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Pixelforge cost</th>
                            <th v-if="report.reconciled" scope="col" class="px-4 py-3 text-right font-medium">Pixelforge reports</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line font-mono text-[13px] tabular-nums">
                        <tr v-for="day in report.days" :key="day.day">
                            <th scope="row" class="px-4 py-3 text-left font-sans font-normal">{{ dateFormat.format(new Date(day.day)) }}</th>
                            <td class="px-4 py-3 text-right">{{ day.generations }}</td>
                            <td class="px-4 py-3 text-right">{{ exactUsd(day.charged) }}</td>
                            <td class="px-4 py-3 text-right">{{ exactUsd(day.provider_cost) }}</td>
                            <td v-if="report.reconciled" class="px-4 py-3 text-right" :class="day.pixelforge === day.provider_cost ? 'text-ok' : 'text-bad'">
                                {{ day.pixelforge === null ? 'Not reported' : exactUsd(day.pixelforge) }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ exactUsd(day.profit) }}</td>
                        </tr>
                        <tr v-if="!report.days.length">
                            <td :colspan="report.reconciled ? 6 : 5" class="px-4 py-10 text-center font-sans text-fg-muted">No completed generations in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="!user" aria-labelledby="users-title" class="grid gap-3">
            <h2 id="users-title" class="text-base font-semibold">By user</h2>
            <div class="panel overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="text-left text-xs text-fg-subtle">
                        <tr class="border-b border-line">
                            <th scope="col" class="px-4 py-3 font-medium">User</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Images</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Charged</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Pixelforge cost</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Profit</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line font-mono text-[13px] tabular-nums">
                        <tr v-for="row in report.users" :key="row.user.id" class="transition-colors hover:bg-ink-850">
                            <th scope="row" class="px-4 py-3 text-left font-sans font-normal">
                                <Link :href="`/admin?days=${days}&user=${row.user.id}`" class="grid rounded-sm" preserve-scroll>
                                    <span>{{ row.user.name }}</span>
                                    <span class="text-xs text-fg-subtle">{{ row.user.email }}</span>
                                </Link>
                            </th>
                            <td class="px-4 py-3 text-right">{{ row.generations }}</td>
                            <td class="px-4 py-3 text-right">{{ exactUsd(row.charged) }}</td>
                            <td class="px-4 py-3 text-right">{{ exactUsd(row.provider_cost) }}</td>
                            <td class="px-4 py-3 text-right">{{ exactUsd(row.profit) }}</td>
                            <td class="px-4 py-3 text-right text-fg-muted">{{ usd(row.user.balance) }}</td>
                        </tr>
                        <tr v-if="!report.users.length">
                            <td colspan="6" class="px-4 py-10 text-center font-sans text-fg-muted">No user spent credits in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
