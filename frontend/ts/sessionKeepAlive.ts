const storageKey = 'hskauting.sessionKeepAlive.lastPingAt';
const lockStorageKey = 'hskauting.sessionKeepAlive.lockedUntil';
export const sessionKeepAliveSucceededEvent = 'hskauting:session-keep-alive-succeeded';
const minimumInterval = 60_000;
const tabCoordinationTolerance = 60_000;
const lockTimeout = 30_000;

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

        const lockedUntil = Number.parseInt(window.localStorage.getItem(lockStorageKey) ?? '0', 10);
        if (Number.isFinite(lockedUntil) && lockedUntil > now) {
            return true;
        }

        window.localStorage.setItem(lockStorageKey, String(now + lockTimeout));
    } catch {
        return false;
    }

    return false;
}

function markPingSucceeded(): void {
    try {
        window.localStorage.setItem(storageKey, String(Date.now()));
    } catch {
        // Ignore unavailable localStorage; the current tab still refreshed the session.
    }

    window.dispatchEvent(new CustomEvent(sessionKeepAliveSucceededEvent));
}

function releasePingLock(): void {
    try {
        window.localStorage.removeItem(lockStorageKey);
    } catch {
        // Ignore unavailable localStorage.
    }
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
            .then((response) => {
                if (response.ok) {
                    markPingSucceeded();
                }
            })
            .catch(() => undefined)
            .finally(() => {
                releasePingLock();
                requestInProgress = false;
            });
    };

    void ping();
    window.setInterval(ping, interval);
    window.addEventListener('pageshow', () => ping());
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            ping();
        }
    });
}
