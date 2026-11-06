/**
 * URLs and state the server passes to the SPA as data attributes on <div id="app"> (see Views/index.blade.php).
 */
export function getInstallerConfig() {
    const data = document.getElementById("app")?.dataset ?? {};
    const baseUrl = data.baseUrl ?? "";

    return {
        baseUrl,
        // Falls back to the conventional path if the server didn't provide it
        registerUrl: data.registerUrl || `${baseUrl}/register`,
        // Where to send the new admin once registered. Empty when AdminUI's own routes aren't available.
        adminUrl: data.adminUrl ?? "",
        // The admin login page. Empty when AdminUI's own routes aren't available.
        loginUrl: data.loginUrl ?? "",
    };
}
