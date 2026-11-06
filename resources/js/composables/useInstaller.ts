export type InstallStatus = {
    releaseDetails?: { version?: string };
    downloadStats?: string;
    installSize?: string;
    installComplete?: boolean;
    [key: string]: unknown;
};

type StreamEvent =
    | { type: "log"; line: string }
    | { type: "done"; status: InstallStatus }
    | { type: "error"; message: string };

/**
 * POST to an install step. Quick steps answer with JSON ({ status }); long steps stream newline-delimited
 * JSON events (see StreamsStepOutput.php) so console output reaches onLog while the step is still running.
 * Resolves with the latest install status, or throws with a readable message.
 */
export async function runStep(
    url: string,
    body: Record<string, unknown> = {},
    onLog: (line: string) => void = () => {},
): Promise<InstallStatus> {
    const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify(body),
    });

    const contentType = response.headers.get("Content-Type") ?? "";

    if (!contentType.includes("ndjson")) {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(
                data?.errors
                    ? (Object.values(data.errors) as string[][])[0][0]
                    : (data?.message ?? response.statusText ?? "Request failed"),
            );
        }
        return data.status ?? {};
    }

    if (!response.body) {
        throw new Error("Streaming is not supported by this browser.");
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = "";
    let status: InstallStatus | undefined;

    const handle = (line: string) => {
        if (!line.trim()) return;
        const event = JSON.parse(line) as StreamEvent;
        if (event.type === "log") onLog(event.line);
        else if (event.type === "done") status = event.status;
        else if (event.type === "error") throw new Error(event.message);
    };

    try {
        for (;;) {
            const { value, done } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            let newline: number;
            while ((newline = buffer.indexOf("\n")) >= 0) {
                handle(buffer.slice(0, newline));
                buffer = buffer.slice(newline + 1);
            }
        }
        handle(buffer);
    } finally {
        reader.cancel().catch(() => {});
    }

    if (!status) {
        throw new Error("The connection closed before this step finished.");
    }

    return status;
}
