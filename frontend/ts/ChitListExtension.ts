import naja from "naja";

export function initializeSendMassForm(container: Element, checkboxPrefix: string): void {
    container.querySelectorAll<HTMLFormElement>('.formMass').forEach(form => {
        form.addEventListener('submit', (event: SubmitEvent) => {
            submitMassAddEvent(form, event, checkboxPrefix);
            event.preventDefault();
        });
    });
}

function submitMassAddEvent(form: HTMLFormElement, event: SubmitEvent, checkboxPrefix: string):void {
    const formData = new FormData(form);
    const chitList = form.closest('.chit-list');
    if(chitList === null) {
        console.error("Parent element 'chit-list' was not found!");
        return;
    }
    const checkboxes = chitList.querySelectorAll<HTMLInputElement>(`input[type="checkbox"][name^="${checkboxPrefix}"]:checked`);

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
