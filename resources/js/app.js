import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

createInertiaApp({
    title: (title) => (title ? `${title} - KoperasiHub` : 'KoperasiHub'),
    resolve: async (name) => {
        const pages = import.meta.glob('./**/Pages/**/*.vue');
        const page = pages[`./${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return page();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#0f766e',
    },
});
