<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { PhChartBar, PhFire, PhImages, PhPlus, PhSignOut, PhWallet } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import AnimatedMoney from '../components/AnimatedMoney.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const nav = computed(() => [
    { href: '/studio', label: 'Studio', icon: PhImages, pages: ['Studio'] },
    { href: '/credits', label: 'Credits', icon: PhWallet, pages: ['Credits', 'Checkout'] },
    ...(user.value.is_operator ? [{ href: '/admin', label: 'Report', icon: PhChartBar, pages: ['Admin'] }] : []),
]);

const toast = ref(null);
let toastTimer;

watch(() => page.props.toast, (message) => {
    if (!message) return;
    toast.value = message;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 5000);
}, { immediate: true });
</script>

<template>
    <div class="min-h-dvh">
        <header class="sticky top-0 z-30 border-b border-line bg-ink-950/80 backdrop-blur-md">
            <div class="mx-auto flex h-14 max-w-[1400px] items-center gap-3 px-4 sm:gap-6 sm:px-6">
                <Link href="/studio" class="flex items-center gap-2 rounded-md text-[15px] font-semibold tracking-tight">
                    <PhFire :size="20" weight="fill" class="text-ember" aria-hidden="true" />
                    <span class="max-sm:sr-only">Emberly</span>
                </Link>

                <nav aria-label="Primary" class="flex items-center gap-1">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="flex h-8 items-center gap-2 rounded-md px-2.5 text-sm font-medium transition-colors"
                        :class="item.pages.includes(page.component) ? 'bg-ink-850 text-fg' : 'text-fg-muted hover:text-fg'"
                        :aria-current="item.pages.includes(page.component) ? 'page' : undefined"
                    >
                        <component :is="item.icon" :size="16" aria-hidden="true" />
                        <span class="max-sm:sr-only">{{ item.label }}</span>
                    </Link>
                </nav>

                <div class="ms-auto flex items-center gap-2">
                    <Link
                        href="/credits/checkout"
                        class="flex h-8 items-center gap-2 rounded-full bg-ink-850 ps-3 pe-1.5 text-sm shadow-[inset_0_0_0_1px_var(--color-line-strong)] transition-colors hover:bg-ink-800"
                        aria-label="Balance, buy credits"
                    >
                        <AnimatedMoney :value="user.balance" />
                        <span class="grid size-5 place-items-center rounded-full bg-ember text-ember-ink">
                            <PhPlus :size="12" weight="bold" aria-hidden="true" />
                        </span>
                    </Link>

                    <img v-if="user.avatar" :src="user.avatar" alt="" class="size-8 rounded-full" referrerpolicy="no-referrer" />
                    <span v-else class="grid size-8 place-items-center rounded-full bg-ink-800 text-xs font-semibold" aria-hidden="true">
                        {{ user.name.slice(0, 1) }}
                    </span>

                    <Link href="/logout" method="post" as="button" class="btn btn-ghost size-8 px-0" aria-label="Sign out" title="Sign out">
                        <PhSignOut :size="16" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:py-8">
            <slot />
        </main>

        <div class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center px-4" role="status" aria-live="polite">
            <p
                v-if="toast"
                class="toast pointer-events-auto rounded-md border border-line-strong bg-ink-850 px-4 py-2.5 text-sm shadow-[0_12px_40px_oklch(0_0_0/0.5)]"
            >
                {{ toast }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.toast {
    transition: opacity 200ms var(--ease-out-strong), transform 200ms var(--ease-out-strong);

    @starting-style {
        opacity: 0;
        transform: translateY(8px);
    }
}
</style>
