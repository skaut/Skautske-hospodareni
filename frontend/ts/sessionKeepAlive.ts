const storageKey = 'hskauting.sessionKeepAlive.lastPingAt';
const minimumInterval = 60_000;
const tabCoordinationTolerance = 60_000;

function parseInterval(value: string|undefined): number|null {
    if (value === undefined) {
        return null;
    }

    const interval = Number.parseInt(value, 10);

    return Number.isFinite(interval) && interval >= minimumInterval ? interval : null;
}

function shouldSkipForAnotherTab(now: number, interval: number): boolean {
    try {
        const lastPingAt = Number.parseInt(window.localStorage.getItem(storageKey) ?? '0', 10);
        if (Number.isFinite(lastPingAt) && lastPingAt > now - interval + tabCoordinationTolerance) {
            return true;
        }

        window.localStorage.setItem(storageKey, String(now));
    } catch {
        return false;
    }

    return false;
}

export function initializeSessionKeepAlive(root: HTMLElement = document.body): void {
    const url = root.dataset.sessionKeepAliveUrl;
    const interval = parseInterval(root.dataset.sessionKeepAliveInterval);
    if (url === undefined || url === '' || interval === null) {
        return;
    }

    let requestInProgress = false;
    const ping = (): void => {
        if (requestInProgress) {
            return;
        }

        const now = Date.now();
        if (shouldSkipForAnotherTab(now, interval)) {
            return;
        }

        requestInProgress = true;
        window.fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .catch(() => undefined)
            .finally(() => {
                requestInProgress = false;
            });
    };

    window.setInterval(ping, interval);
}
