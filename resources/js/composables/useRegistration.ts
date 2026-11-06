import { reactive, ref } from "vue";

export interface RegistrationOptions {
    registerUrl: string;
    /** Where to send the user after registering. When empty, no redirect happens. */
    adminUrl?: string;
    /** Seconds to count down before redirecting */
    redirectSeconds?: number;
    // Injectable so the flow can be tested without a browser
    fetchFn?: typeof fetch;
    wait?: (ms: number) => Promise<void>;
    redirect?: (url: string) => void;
}

const firstMessages = (errors: Record<string, string | string[]>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(errors).map(([field, value]) => [field, Array.isArray(value) ? (value[0] ?? "") : value]),
    );

export function useRegistration(options: RegistrationOptions) {
    const {
        registerUrl,
        adminUrl = "",
        redirectSeconds = 3,
        fetchFn = (...args: Parameters<typeof fetch>) => fetch(...args),
        wait = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms)),
        redirect = (url: string) => window.location.assign(url),
    } = options;

    const form = reactive({
        first_name: "",
        last_name: "",
        company: "",
        email: "",
        password: "",
        password_confirmation: "",
    });

    // Field => first validation message from the server
    const errors = ref<Record<string, string>>({});
    const isLoading = ref(false);
    const isRegistered = ref(false);
    const message = ref("");
    const hasError = ref(false);

    const fail = (text: string) => {
        message.value = text;
        hasError.value = true;
    };

    async function submit() {
        // Ignore a second click while a request is running, or once the account exists
        if (isLoading.value || isRegistered.value) return;

        isLoading.value = true;
        errors.value = {};
        message.value = "";
        hasError.value = false;

        try {
            const response = await fetchFn(registerUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
                body: JSON.stringify(form),
            });
            const body = await response.json().catch(() => ({}));

            if (response.status === 422 && body.errors) {
                errors.value = firstMessages(body.errors);
                return;
            }

            if (!response.ok) {
                // e.g. 403 when an admin already exists, 409 when another registration is in progress
                fail(body.message ?? `Registration failed (${response.status}).`);
                return;
            }

            isRegistered.value = true;

            if (!adminUrl) {
                message.value = "Registration complete.";
                return;
            }

            for (let seconds = redirectSeconds; seconds > 0; seconds--) {
                message.value = `Registration complete. Redirecting to AdminUI in ${seconds}`;
                await wait(1000);
            }
            redirect(adminUrl);
        } catch {
            fail("Could not reach the server. Check your connection and try again.");
        } finally {
            isLoading.value = false;
        }
    }

    return { form, errors, isLoading, isRegistered, message, hasError, submit };
}
