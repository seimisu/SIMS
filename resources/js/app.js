import "../css/app.css";

import "primeicons/primeicons.css";

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";
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

function installRequestButtonState() {
    let buttonForNextVisit = null;
    const pendingButtons = new Map();

    const rememberButton = (button) => {
        if (!(button instanceof HTMLButtonElement)) return;

        buttonForNextVisit = button;
        queueMicrotask(() => {
            if (buttonForNextVisit === button) buttonForNextVisit = null;
        });
    };

    document.addEventListener(
        "click",
        (event) => {
            const target =
                event.target instanceof Element ? event.target : null;
            const button = target?.closest("button");

            if (button?.dataset.requestPending === "true") {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            rememberButton(button);
        },
        true,
    );

    document.addEventListener(
        "submit",
        (event) => {
            const form = event.target;
            const button =
                event.submitter ||
                form.querySelector(
                    'button[type="submit"], input[type="submit"]',
                );

            rememberButton(button);
        },
        true,
    );

    router.on("start", (event) => {
        const button = buttonForNextVisit;
        const visit = event.detail.visit;
        buttonForNextVisit = null;

        if (!button || !button.isConnected || button.disabled) return;

        pendingButtons.set(visit, {
            button,
            wasDisabled: button.disabled,
            previousAriaBusy: button.getAttribute("aria-busy"),
        });
        button.dataset.requestPending = "true";
        button.disabled = true;
        button.setAttribute("aria-busy", "true");
    });

    router.on("finish", (event) => {
        const pending = pendingButtons.get(event.detail.visit);
        if (!pending) return;

        const { button, wasDisabled, previousAriaBusy } = pending;
        delete button.dataset.requestPending;
        button.disabled = wasDisabled;

        if (previousAriaBusy === null) {
            button.removeAttribute("aria-busy");
        } else {
            button.setAttribute("aria-busy", previousAriaBusy);
        }

        pendingButtons.delete(event.detail.visit);
    });
}

installRequestButtonState();

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
