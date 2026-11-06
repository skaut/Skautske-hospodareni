export class DarkModeToggle {
    private readonly element: HTMLElement|null;

    constructor(elementId: string) {
        this.element = document.getElementById(elementId);
        if (this.element === null) {
            return;
        }

        this.element.onclick = () => {
            this.setTheme(document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark");
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
}
