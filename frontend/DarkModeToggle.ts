export class DarkModeToggle {
    private readonly element: HTMLElement;

    constructor(elementId: string) {
        this.element = document.getElementById(elementId) as HTMLElement;
        
        this.element.onclick = () => {
            this.setTheme(document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark");
        };

        this.setTheme(document.documentElement.dataset.bsTheme as "dark"|"light"|undefined ?? "light");
    }

    private setTheme(theme: "dark"|"light"): void {
        document.documentElement.setAttribute("data-bs-theme", theme);
        this.element.innerHTML = "<i class=\"fas fa-" + (theme === "dark" ? "moon" : "sun") + "\"></i>"
        localStorage.setItem("theme", theme);
    }
}
