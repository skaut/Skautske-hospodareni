document.addEventListener("DOMContentLoaded", () => {
    const mainCheckbox = document.getElementById("p-all") as HTMLInputElement;
    const checkboxes = document.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:not(#p-all)');

    if (!mainCheckbox) return;

    // Kliknutí na hlavní checkbox
    mainCheckbox.addEventListener("change", () => {
        checkboxes.forEach(checkbox => {
            checkbox.checked = mainCheckbox.checked;
        });
    });

    // Kliknutí na kterýkoliv jiný checkbox
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", () => {
            if (!checkbox.checked) {
                mainCheckbox.checked = false;
            } else if ([...checkboxes].every(ch => ch.checked)) {
                mainCheckbox.checked = true;
            }
        });
    });
});