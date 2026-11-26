import '@fontsource-variable/geist';
import '@fontsource-variable/geist-mono';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import AppLayout from './layouts/AppLayout.vue';

createInertiaApp({
    title: (title) => (title ? `${title} · Emberly` : 'Emberly'),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
    layout: (name) => (name === 'Welcome' ? null : AppLayout),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    progress: { color: 'oklch(0.75 0.17 52)', delay: 300 },
});
