export class HelpPanelModule {
    // Privátní vlastnosti pro uložení referencí na DOM prvky.
    // Budou nastaveny v konstruktoru.
    private readonly toggleHelpButton: HTMLButtonElement | null;
    private readonly leftPanel: HTMLDivElement | null;
    private readonly rightPanel: HTMLDivElement | null;

    /**
     * Konstruktor vyhledá všechny potřebné prvky na stránce a uloží si je.
     */
    constructor() {
        this.toggleHelpButton = document.getElementById('toggleHelpButton') as HTMLButtonElement;
        this.leftPanel = document.getElementById('leftPanel') as HTMLDivElement;
        this.rightPanel = document.getElementById('rightPanel') as HTMLDivElement;
    }

    /**
     * Inicializuje funkcionalitu modulu.
     * Zkontroluje, zda byly všechny prvky nalezeny, a připojí event listener.
     */
    public initialize(): void {
        // Bezpečnostní kontrola, zda všechny prvky existují.
        if (!this.toggleHelpButton || !this.leftPanel || !this.rightPanel) {
            console.error('HelpPanelModule: Nepodařilo se najít všechny potřebné prvky (toggleHelpButton, leftPanel, rightPanel).');
            return;
        }

        // Přidá posluchač události na kliknutí, který volá naši privátní metodu.
        // Použití arrow funkce `() => ...` zajistí správný kontext `this`.
        this.toggleHelpButton.addEventListener('click', () => this.toggleVisibility());
    }

    /**
     * Privátní metoda, která se stará o samotnou logiku skrytí/zobrazení panelů.
     */
    private toggleVisibility(): void {
        // Tato kontrola je zde pro typovou jistotu TypeScriptu, aby věděl,
        // že v této metodě nepracujeme s `null` hodnotami.
        if (!this.rightPanel || !this.leftPanel || !this.toggleHelpButton) {
            return;
        }

        // Přepne viditelnost pravého panelu.
        this.rightPanel.classList.toggle('d-none');

        // Přepne šířku levého panelu.
        this.leftPanel.classList.toggle('col-sm-6');
        this.leftPanel.classList.toggle('col-sm-12');

        // Aktualizuje text na tlačítku.
        const isHidden = this.rightPanel.classList.contains('d-none');
        this.toggleHelpButton.textContent = isHidden ? 'Zobrazit nápovědu' : 'Skrýt nápovědu';
    }
}