export function toggleAllCheckboxes(mainCheckboxElement, dependentCheckboxClass) {
    document.querySelectorAll("." + dependentCheckboxClass + " input[type='checkbox']")
        .forEach(checkbox => checkbox.checked = mainCheckboxElement.checked);
}

export function toggleMainCheckbox(mainCheckboxId, dependentCheckboxClass) {
    const main = document.getElementById(mainCheckboxId);
    const dependent = Array.from(document.querySelectorAll("." + dependentCheckboxClass + " input[type='checkbox']"));
    main.checked = dependent.every(checkbox => checkbox.checked);
}
