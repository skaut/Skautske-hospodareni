export function toggleAllCheckboxes(mainCheckboxElement: HTMLInputElement, dependentCheckboxClass: string) {
    document.querySelectorAll<HTMLInputElement>("." + dependentCheckboxClass + " input[type='checkbox']")
        .forEach(checkbox => checkbox.checked = mainCheckboxElement.checked);
}

export function toggleMainCheckbox(mainCheckboxId: string, dependentCheckboxClass: string) {
    const main = document.getElementById(mainCheckboxId) as HTMLInputElement;
    const dependent = Array.from(document.querySelectorAll<HTMLInputElement>("." + dependentCheckboxClass + " input[type='checkbox']"));
    main.checked = dependent.every(checkbox => checkbox.checked);
}
