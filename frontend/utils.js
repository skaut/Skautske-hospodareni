export function toggleAllCheckboxes(mainCheckboxElement, dependentCheckboxClass) {
    document.querySelectorAll("." + dependentCheckboxClass + " input[type='checkbox']")
        .forEach(checkbox => checkbox.checked = mainCheckboxElement.checked);
}
