import { defineConfig } from "oxlint";

export default defineConfig({
    plugins: ["typescript", "vue", "oxc"],
    categories: {
        correctness: "error",
    },
    env: {
        browser: true,
        es2022: true,
    },
    ignorePatterns: ["node_modules", "public", "vendor", "src/_Views"],
});
