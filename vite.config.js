import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";
export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
        Components({
            resolvers: [PrimeVueResolver()],
        }),
    ],
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        hmr: {
            host: "localhost",
        },
    },
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
        },
    },
});
