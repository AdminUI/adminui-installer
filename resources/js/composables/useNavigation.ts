import { computed, onBeforeUnmount, onMounted, ref, type Component } from "vue";

type PageComponent = Component & { title?: string };

/**
 * Every .vue file in resources/js/pages is registered automatically, keyed by its kebab-cased file name:
 *   pages/Install.vue        -> "install"
 *   pages/NotInstalled.vue   -> "not-installed"
 * To add a screen, drop a file in pages/ and call navigate("your-page").
 */
const modules = import.meta.glob<{ default: PageComponent }>("../pages/*.vue", {
    eager: true,
});

const toName = (path: string) =>
    (path.split("/").pop() ?? "")
        .replace(/\.vue$/, "")
        .replace(/([a-z0-9])([A-Z])/g, "$1-$2")
        .toLowerCase();

export const pages: Record<string, PageComponent> = Object.fromEntries(
    Object.entries(modules).map(([path, module]) => [toName(path), module.default]),
);

export const DEFAULT_PAGE = "install";

// The server picks the default screen with <div id="app" data-page="already-installed">.
const serverPage = document.getElementById("app")?.dataset.page;
const fallbackPage = serverPage && serverPage in pages ? serverPage : DEFAULT_PAGE;
// Once an admin exists, anything that would have opened the register form lands on the explanation instead
const registerFallback =
    document.getElementById("app")?.dataset.registered === "1" ? "already-registered" : fallbackPage;

// Screens the server's state rules out, so a link or the back button can't open them:
//  - no database: nothing but the "no database" screen makes sense
//  - already installed: the install screen must not be run again
//  - not installed (on the register route): there is nothing to register an admin on yet
//  - already registered: an admin exists, so neither registering nor installing makes sense
// The server enforces the same rules on the endpoints themselves, this only keeps the UI honest.
const adminExists = document.getElementById("app")?.dataset.registered === "1";

const isBlocked = (name: string) => {
    if (adminExists && name === "register") return true;
    if (serverPage === "maintenance") return name !== "maintenance";
    if (serverPage === "no-database") return name !== "no-database";
    if (serverPage === "already-installed") return name === "install";
    if (serverPage === "not-installed") return name === "register";
    if (serverPage === "already-registered") return name === "register" || name === "install";
    return false;
};

// An unknown or blocked name falls back to the server's screen
const resolveName = (name?: string | null) => {
    if (name === "register" && adminExists) return registerFallback;
    return name && name in pages && !isBlocked(name) ? name : fallbackPage;
};

const readHash = () => window.location.hash.replace(/^#\/?/, "");

// A valid URL hash (e.g. /install-adminui#/register) wins over the server's default screen
const current = ref(resolveName(readHash()));

export function navigate(name: string) {
    current.value = resolveName(name);
    window.location.hash = `/${current.value}`;
}

export function useNavigation() {
    const onHashChange = () => {
        current.value = resolveName(readHash());
    };

    onMounted(() => window.addEventListener("hashchange", onHashChange));
    onBeforeUnmount(() => window.removeEventListener("hashchange", onHashChange));

    return {
        current,
        component: computed(() => pages[current.value]),
        navigate,
        pages,
    };
}
