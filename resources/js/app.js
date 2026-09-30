import "../css/app.css";

import "primeicons/primeicons.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { definePreset } from "@primeuix/themes";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "ziggy-js";
import { Ziggy } from "./ziggy";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import VCalendar from "v-calendar";
import "v-calendar/style.css";
const appName = import.meta.env.VITE_APP_NAME || "SIMS";
const MyPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: "{blue.50}",
            100: "{blue.100}",
            200: "{blue.200}",
            300: "{blue.300}",
            400: "{blue.400}",
            500: "{blue.500}",
            600: "{blue.600}",
            700: "{blue.700}",
            800: "{blue.800}",
            900: "{blue.900}",
            950: "{blue.950}",
        },
        colorScheme: {
            light: {
                semantic: {
                    highlight: {
                        background: "{primary.50}",
                        color: "{primary.700}",
                    },
                },
            },
            dark: {
                semantic: {
                    highlight: {
                        background: "{gray.800}",
                        color: "{primary.900}",
                    },
                },
            },
        },
    },
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    // resolve: (name) => {
    //     const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
    //     return pages[`./Pages/${name}.vue`];
    // },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(PrimeVue, {
                theme: {
                    preset: MyPreset,
                    options: {
                        darkModeSelector: ".dark",
                    },
                    ripple: true,
                },
            })
            .use(plugin)
            .use(ToastService)
            .use(ConfirmationService)
            .use(VCalendar, {})
            .use(ZiggyVue, Ziggy)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
