import { defineConfig } from "oxfmt";

export default defineConfig({
    printWidth: 120,
    tabWidth: 4,
    semi: true,
    singleQuote: false,
    sortTailwindcss: {
        stylesheet: "resources/css/app.css",
        functions: ["cn", "clsx", "cva"],
    },
    ignorePatterns: [
        "node_modules",
        "public",
        "vendor",
        "src",
        "changelog",
        "dev",
        "composer.json",
        "**/*.md",
        "pnpm-lock.yaml",
    ],
});
