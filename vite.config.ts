import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { resolve } from "node:path";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

// Assets are built into this package (public/build) and committed, because apps that require the installer have no
// Node toolchain. They are served by the package itself (see AssetController).
export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            publicDirectory: "public",
            buildDirectory: "build",
        }),
        tailwindcss(),
        vue(),
    ],
    build: {
        emptyOutDir: true,
    },
    resolve: {
        alias: {
            "@": resolve(import.meta.dirname, "./resources/js"),
        },
    },
});
