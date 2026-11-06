export class DarkModeToggle {
    private readonly element: HTMLElement|null;

    constructor(elementId: string) {
        this.element = document.getElementById(elementId);
        if (this.element === null) {
            return;
        }

        this.element.onclick = () => {
            const theme = document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark";
            this.setTheme(theme);
            this.report(theme);
        };

        const currentTheme = document.documentElement.dataset.bsTheme;
        this.setTheme(currentTheme === "dark" ? "dark" : "light");
    }

    private setTheme(theme: "dark"|"light"): void {
        if (this.element === null) {
            return;
        }

        document.documentElement.setAttribute("data-bs-theme", theme);
        const label = theme === "dark" ? "Přepnout na světlý režim" : "Přepnout na tmavý režim";
        this.element.setAttribute("aria-label", label);
        this.element.setAttribute("title", label);
        localStorage.setItem("theme", theme);
    }

    /** Tells the server which theme a signed-in user switched to, for the usage statistics. */
    private report(theme: "dark"|"light"): void {
        const url = this.element?.dataset.reportUrl;
        if (url === undefined || url === "") {
            return;
        }

        const body = new URLSearchParams();
        body.set("theme", theme);

        window.fetch(url, {
            method: "POST",
            credentials: "same-origin",
            cache: "no-store",
            headers: {"X-Requested-With": "XMLHttpRequest"},
            body,
        }).catch(() => undefined);
    }
}
