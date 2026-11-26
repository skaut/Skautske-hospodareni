import type { Naja } from 'naja';

/**
 * Refreshes the panel of background batches (TaskQueueStatus) while one of them is active.
 *
 * Only the panel's snippet is reloaded, never the rest of the page. The queue moves once a minute,
 * so a slower interval is enough; requests stop while the tab is hidden and after an error.
 */
const REFRESH_INTERVAL_MS = 15_000;

let timer: number | undefined;
let waitingForVisibility = false;

function findPanel(): HTMLElement | null {
    return document.querySelector<HTMLElement>('[data-task-queue-status]');
}

function schedule(naja: Naja): void {
    window.clearTimeout(timer);
    timer = undefined;

    const panel = findPanel();

    if (panel === null || panel.dataset.active !== '1') {
        return;
    }

    timer = window.setTimeout(() => refresh(naja), REFRESH_INTERVAL_MS);
}

function refresh(naja: Naja): void {
    const panel = findPanel();
    const url = panel?.dataset.refreshUrl;

    if (url === undefined || url === '') {
        return;
    }

    if (document.visibilityState !== 'visible') {
        if (!waitingForVisibility) {
            waitingForVisibility = true;
            document.addEventListener('visibilitychange', () => {
                waitingForVisibility = false;
                refresh(naja);
            }, {once: true});
        }

        return;
    }

    // Not unique: a background refresh must never abort a request the user started.
    naja.makeRequest('GET', url, null, {history: false, background: true, unique: false})
        .then(() => schedule(naja))
        .catch(() => {
            // The panel stays as it is; reloading the page shows the current state.
        });
}

export function initializeTaskQueueStatus(root: ParentNode, naja: Naja): void {
    if (root.querySelector('[data-task-queue-status]') !== null || root === document.body) {
        schedule(naja);
    }
}
