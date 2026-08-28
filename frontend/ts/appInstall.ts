const dismissedUntilStorageKey = 'hskauting.appInstall.dismissedUntil';
const dismissalPeriod = 30 * 24 * 60 * 60 * 1000;

interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    readonly userChoice: Promise<{outcome: 'accepted'|'dismissed'}>;
}

function isAndroid(): boolean {
    return /android/i.test(window.navigator.userAgent);
}

function isIos(): boolean {
    // An iPad reports itself as a Mac since iPadOS 13, only the touch support tells them apart.
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent)
        || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
}

function isRunningAsInstalledApp(): boolean {
    if ((window.navigator as {standalone?: boolean}).standalone === true) {
        return true;
    }

    return ['standalone', 'fullscreen', 'minimal-ui']
        .some((mode) => window.matchMedia(`(display-mode: ${mode})`).matches);
}

function isDismissed(): boolean {
    try {
        const dismissedUntil = Number.parseInt(window.localStorage.getItem(dismissedUntilStorageKey) ?? '0', 10);

        return Number.isFinite(dismissedUntil) && dismissedUntil > Date.now();
    } catch {
        return false;
    }
}

function rememberDismissal(): void {
    try {
        window.localStorage.setItem(dismissedUntilStorageKey, String(Date.now() + dismissalPeriod));
    } catch {
        // Ignore unavailable localStorage; the offer simply shows up again.
    }
}

function registerServiceWorker(): void {
    // A service worker needs a secure context, so this stays a no-op over plain HTTP.
    if (!('serviceWorker' in window.navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        window.navigator.serviceWorker.register('/sw.js').catch(() => undefined);
    });
}

/**
 * Offers the installation of the application on a phone. Android browsers install it
 * from the offer itself, iOS only knows the manual way through its share menu. Inside
 * the installed application there is nothing left to install, so the offer stays hidden.
 */
export function initializeAppInstall(): void {
    registerServiceWorker();

    const banner = document.getElementById('appInstallBanner');
    const installButton = document.getElementById('appInstallButton');
    const dismissButton = document.getElementById('appInstallDismiss');
    const manualHint = document.getElementById('appInstallManualHint');

    if (banner === null || installButton === null || dismissButton === null || manualHint === null) {
        return;
    }

    if (isRunningAsInstalledApp() || isDismissed()) {
        return;
    }

    let installPrompt: BeforeInstallPromptEvent|null = null;

    const hide = (): void => {
        banner.hidden = true;
        installPrompt = null;
    };

    dismissButton.addEventListener('click', () => {
        hide();
        rememberDismissal();
    });

    window.addEventListener('appinstalled', () => {
        hide();
        rememberDismissal();
    });

    if (isIos()) {
        installButton.hidden = true;
        manualHint.hidden = false;
        banner.hidden = false;

        return;
    }

    if (!isAndroid()) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        // Without this the browser shows its own mini-infobar instead of the offer.
        event.preventDefault();
        installPrompt = event as BeforeInstallPromptEvent;
        banner.hidden = false;
    });

    installButton.addEventListener('click', () => {
        const prompt = installPrompt;
        if (prompt === null) {
            return;
        }

        hide();
        prompt.prompt()
            .then(() => prompt.userChoice)
            .then(({outcome}) => {
                if (outcome !== 'dismissed') {
                    return;
                }

                rememberDismissal();
            })
            .catch(() => undefined);
    });
}
