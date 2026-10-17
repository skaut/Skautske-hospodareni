import naja from "naja";

export function initializeSendMassForm(container: Element, checkboxPrefix: string): void {
    container.querySelectorAll<HTMLFormElement>('.formMass').forEach(form => {
        form.addEventListener('submit', (event: SubmitEvent) => {
            // "Otevřít v nové kartě" (formtarget="_blank") musí odejít nativně – okno otevřené až po
            // ajaxové odpovědi by prohlížeč zablokoval jako vyskakovací.
            if (event.submitter instanceof HTMLButtonElement && event.submitter.formTarget === '_blank') {
                appendCheckedChits(form, checkboxPrefix);
                return;
            }

            submitMassAddEvent(form, event, checkboxPrefix);
            event.preventDefault();
        });
    });
}

function findCheckedChits(form: HTMLFormElement, checkboxPrefix: string): NodeListOf<HTMLInputElement> | null {
    const chitList = form.closest('.chit-list');
    if(chitList === null) {
        console.error("Parent element 'chit-list' was not found!");
        return null;
    }

    return chitList.querySelectorAll<HTMLInputElement>(`input[type="checkbox"][name^="${checkboxPrefix}"]:checked`);
}

/** Zaškrtnuté doklady leží mimo formulář; pro nativní odeslání je dočasně přidá jako skrytá pole. */
function appendCheckedChits(form: HTMLFormElement, checkboxPrefix: string): void {
    const checkboxes = findCheckedChits(form, checkboxPrefix);
    if (checkboxes === null) {
        return;
    }

    const inputs = Array.from(checkboxes, checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = checkbox.name;
        input.value = checkbox.value;
        form.append(input);

        return input;
    });

    window.setTimeout(() => inputs.forEach(input => input.remove()));
}

function submitMassAddEvent(form: HTMLFormElement, event: SubmitEvent, checkboxPrefix: string):void {
    const formData = new FormData(form);
    const checkboxes = findCheckedChits(form, checkboxPrefix);
    if(checkboxes === null) {
        return;
    }

    const submitter = (event.submitter as HTMLInputElement);

    formData.append( submitter.name, submitter.value);
    checkboxes.forEach(checkbox => {
        formData.append(checkbox.name, checkbox.value) ;
    });
    console.log([...formData.entries()]);
    naja.makeRequest(form.method, form.action, formData);
}

export function initializeEditForm(container: Element): void {

    container.querySelectorAll<HTMLFormElement>('[data-bs-toggle="chitEdit"]').forEach(form => {
        form.addEventListener('click', () => {
            const chitForm = document.getElementById('chitFormHeader');
            if (chitForm) {
                chitForm.scrollIntoView({ behavior: "smooth" });
            }
        });
    });
}
