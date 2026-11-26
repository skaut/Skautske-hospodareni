/**
 * Tells the server once per login whether the pages show dark, for the share of dark mode in the
 * administration. Only the browser knows it: the choice is kept in localStorage or taken from the
 * system settings. The server leaves the attribute out once it has the answer.
 */
export function initializeThemeReport(root: HTMLElement = document.body): void {
    const url = root.dataset.themeReportUrl;
    if (url === undefined || url === '' || typeof navigator.sendBeacon !== 'function') {
        return;
    }

    const body = new FormData();
    body.set('dark', document.documentElement.dataset.bsTheme === 'dark' ? '1' : '0');
    navigator.sendBeacon(url, body);
}
