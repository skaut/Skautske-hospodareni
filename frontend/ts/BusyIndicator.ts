import Dropdown from 'bootstrap/js/dist/dropdown';
import type { CompleteEvent, Extension, InteractionEvent, Naja, Options, RedirectEvent, StartEvent } from 'naja';

const CONTROLS = 'a, button, input, select, textarea';

interface BusyState {
    requests: number;
    label: HTMLElement;
    restoreLabel: () => void;
    disabledControls: HTMLElement[];
    hadFocus: boolean;
}

/**
 * Shows that a long-running action is in progress and blocks repeated clicks until it finishes.
 *
 * `data-busy-text` on a link, submit button or form enables it. Controls inside the closest
 * `data-busy-scope` are blocked together and a spinner with the text replaces the content of the
 * scope's `data-busy-label` element, or of the trigger itself. An action that ends with a full
 * page load keeps the state until the page is left.
 */
export class BusyIndicator implements Extension {
    private readonly states = new Map<HTMLElement, BusyState>();

    public initialize(naja: Naja): void {
        naja.uiHandler.addEventListener('interaction', (event: InteractionEvent) => this.rememberTrigger(event));
        naja.addEventListener('start', (event: StartEvent) => this.startRequest(event.detail.options));
        naja.addEventListener('complete', (event: CompleteEvent) => this.completeRequest(event.detail.options));
        naja.redirectHandler.addEventListener('redirect', (event: RedirectEvent) => {
            if (event.detail.isHardRedirect) {
                event.detail.options.busyUntilUnload = true;
            }
        });

        document.addEventListener('click', (event) => this.blockWhileBusy(event), true);
        document.addEventListener('submit', (event) => this.blockWhileBusy(event), true);
        document.addEventListener('click', (event) => this.startLinkNavigation(event));
        document.addEventListener('submit', (event) => this.startFormNavigation(event));
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                [...this.states.keys()].forEach((scope) => this.finish(scope, true));
            }
        });
    }

    private rememberTrigger(event: InteractionEvent): void {
        // Nette forms cancel the interaction when the form is invalid; nothing is sent then.
        if (event.defaultPrevented) {
            return;
        }

        const trigger = event.detail.element.closest<HTMLElement>('[data-busy-text]');

        if (trigger !== null) {
            event.detail.options.busyTrigger = trigger;
        }
    }

    private startRequest(options: Options): void {
        if (options.busyTrigger instanceof HTMLElement) {
            options.busyScope = this.start(options.busyTrigger);
        }
    }

    private completeRequest(options: Options): void {
        if (options.busyScope instanceof HTMLElement && options.busyUntilUnload !== true) {
            this.finish(options.busyScope);
        }
    }

    // Plain links and forms reload the page, so their state ends together with it. A prevented
    // default means Naja took the request over, or a confirmation or form validation cancelled it.

    private startLinkNavigation(event: MouseEvent): void {
        const target = event.target;

        if (event.defaultPrevented || !(target instanceof Element)) {
            return;
        }

        const link = target.closest<HTMLAnchorElement>('a[href]');
        const opensHere = event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey;

        if (link !== null && link.matches('[data-busy-text]') && link.target === '' && opensHere) {
            this.start(link);
        }
    }

    private startFormNavigation(event: SubmitEvent): void {
        const form = event.target;

        if (event.defaultPrevented || !(form instanceof HTMLFormElement)) {
            return;
        }

        const trigger = (event.submitter ?? form).closest<HTMLElement>('[data-busy-text]');

        if (trigger !== null) {
            // The browser collects form values after this event and leaves out disabled fields.
            window.setTimeout(() => this.start(trigger));
        }
    }

    private blockWhileBusy(event: Event): void {
        if (event.target instanceof Element && event.target.closest('[data-busy-active]') !== null) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }

    private start(trigger: HTMLElement): HTMLElement {
        const scope = trigger.closest<HTMLElement>('[data-busy-scope]') ?? trigger;
        const state = this.states.get(scope);

        if (state !== undefined) {
            state.requests++;

            return scope;
        }

        const label = scope.querySelector<HTMLElement>('[data-busy-label]') ?? trigger;
        const controls = [scope, ...scope.querySelectorAll<HTMLElement>(CONTROLS)]
            .filter((control) => control.matches(CONTROLS) && !BusyIndicator.isDisabled(control));

        this.states.set(scope, {
            requests: 1,
            label,
            restoreLabel: BusyIndicator.showBusyLabel(label, trigger.dataset.busyText ?? ''),
            disabledControls: controls,
            hadFocus: scope.contains(document.activeElement),
        });

        scope.querySelectorAll<HTMLElement>('[data-bs-toggle="dropdown"]').forEach((toggle) => Dropdown.getInstance(toggle)?.hide());
        controls.forEach((control) => BusyIndicator.setDisabled(control, true));
        scope.setAttribute('data-busy-active', '');
        scope.setAttribute('aria-busy', 'true');

        return scope;
    }

    private finish(scope: HTMLElement, force = false): void {
        const state = this.states.get(scope);

        if (state === undefined || (--state.requests > 0 && !force)) {
            return;
        }

        this.states.delete(scope);
        state.restoreLabel();
        state.disabledControls.forEach((control) => BusyIndicator.setDisabled(control, false));
        scope.removeAttribute('data-busy-active');
        scope.removeAttribute('aria-busy');

        // Disabling the clicked control dropped the keyboard focus; return it to the visible control.
        if (state.hadFocus && state.label.isConnected && (document.activeElement === null || document.activeElement === document.body)) {
            state.label.focus();
        }
    }

    /** Returns a callback that puts the original label back. */
    private static showBusyLabel(label: HTMLElement, text: string): () => void {
        // Submit inputs have no content, only a value.
        if (label instanceof HTMLInputElement) {
            const value = label.value;
            label.value = text;

            return () => {
                label.value = value;
            };
        }

        const iconOnly = label.textContent?.trim() === '';
        const spinner = document.createElement('span');
        spinner.className = iconOnly ? 'spinner-border spinner-border-sm' : 'spinner-border spinner-border-sm me-1';
        spinner.setAttribute('aria-hidden', 'true');

        const content = document.createElement('span');
        content.textContent = text;
        if (iconOnly) {
            content.className = 'visually-hidden';
        }

        const originalContent = [...label.childNodes];
        label.replaceChildren(spinner, content);

        return () => label.replaceChildren(...originalContent);
    }

    private static isDisabled(control: HTMLElement): boolean {
        return control instanceof HTMLAnchorElement
            ? control.classList.contains('disabled')
            : (control as HTMLButtonElement).disabled;
    }

    private static setDisabled(control: HTMLElement, disabled: boolean): void {
        if (!(control instanceof HTMLAnchorElement)) {
            (control as HTMLButtonElement).disabled = disabled;

            return;
        }

        control.classList.toggle('disabled', disabled);
        if (disabled) {
            control.setAttribute('aria-disabled', 'true');
        } else {
            control.removeAttribute('aria-disabled');
        }
    }
}
