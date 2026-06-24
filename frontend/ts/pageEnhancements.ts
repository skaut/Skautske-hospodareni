const initializedAttribute = 'data-ui-initialized';
let pageHelpId = 0;

function markInitialized(element: HTMLElement): boolean {
    if (element.hasAttribute(initializedAttribute)) {
        return false;
    }

    element.setAttribute(initializedAttribute, 'true');
    return true;
}

function getFieldWrapper(field: Element): HTMLElement|null {
    return field.closest<HTMLElement>('.mb-3, .form-group, .form-check, div[id]');
}

function setFieldsEnabled(fields: NodeListOf<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>, enabled: boolean): void {
    fields.forEach((field) => {
        field.disabled = !enabled;
        getFieldWrapper(field)?.classList.toggle('form-field-disabled', !enabled);
    });
}

function moveAlertBeforeField(alert: HTMLElement|null, field: Element): void {
    const wrapper = getFieldWrapper(field);
    if (alert !== null && wrapper !== null && wrapper.parentNode !== null) {
        wrapper.parentNode.insertBefore(alert, wrapper);
    }
}

export function initializePageHelp(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('.page-heading').forEach((heading) => {
        if (heading.dataset.pageHelpInitialized === 'true') {
            return;
        }

        const cardBody = heading.querySelector<HTMLElement>(':scope > .card-body');
        const help = cardBody?.querySelector<HTMLElement>(':scope > .page-lead');
        const icon = help?.querySelector<HTMLElement>(':scope > i');
        const content = help?.querySelector<HTMLElement>(':scope > span');
        if (
            cardBody === null || cardBody === undefined
            || help === null || help === undefined
            || icon === null || icon === undefined
            || content === null || content === undefined
        ) {
            return;
        }

        heading.dataset.pageHelpInitialized = 'true';

        const defaultExpanded = document.body.dataset.pageHelpExpanded !== 'false';
        const helpContentId = content.id || `page-help-${pageHelpId++}`;
        content.id = helpContentId;
        content.dataset.pageHelpContent = '';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'page-help-toggle';
        toggle.dataset.pageHelpToggle = '';
        toggle.setAttribute('aria-controls', helpContentId);
        toggle.appendChild(icon);
        toggle.insertAdjacentHTML('beforeend', '<span class="visually-hidden">Přepnout nápovědu</span>');
        help.appendChild(toggle);

        const setExpanded = (expanded: boolean): void => {
            heading.dataset.pageHelpExpanded = String(expanded);
            help.dataset.pageHelpExpanded = String(expanded);
            content.hidden = !expanded;
            toggle.setAttribute('aria-expanded', String(expanded));
            toggle.setAttribute('title', expanded ? 'Skrýt nápovědu' : 'Zobrazit nápovědu');
            toggle.setAttribute('aria-label', expanded ? 'Skrýt nápovědu' : 'Zobrazit nápovědu');
        };

        setExpanded(defaultExpanded);
        toggle.addEventListener('click', () => {
            setExpanded(heading.dataset.pageHelpExpanded !== 'true');
        });
    });
}

export function initializeHelpLayouts(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-help-layout]').forEach((layout) => {
        if (!markInitialized(layout)) {
            return;
        }

        const toggle = layout.querySelector<HTMLElement>('[data-help-toggle]');
        const setCollapsed = (collapsed: boolean): void => {
            layout.dataset.helpCollapsed = String(collapsed);
            toggle?.setAttribute('aria-expanded', String(!collapsed));
            toggle?.setAttribute('title', collapsed ? 'Zobrazit nápovědu' : 'Skrýt nápovědu');
            toggle?.setAttribute('aria-label', collapsed ? 'Zobrazit nápovědu' : 'Skrýt nápovědu');
        };

        setCollapsed(document.body.dataset.pageHelpExpanded === 'false');
        toggle?.addEventListener('click', () => {
            const isCollapsed = layout.dataset.helpCollapsed === 'true';
            setCollapsed(!isCollapsed);
        });
    });
}

export function initializePaymentForms(root: ParentNode = document): void {
    root.querySelectorAll<HTMLFormElement>('[data-help-form] form, #leftPanel form').forEach((form) => {
        if (!markInitialized(form)) {
            return;
        }

        const bankAccountSelect = form.querySelector<HTMLSelectElement>('select[name$="bankAccount"]');
        if (bankAccountSelect !== null) {
            const originalValue = bankAccountSelect.dataset.originalValue;
            const confirmationMessage = bankAccountSelect.dataset.bankAccountChangeMessage;
            if (originalValue !== undefined && confirmationMessage !== undefined) {
                form.addEventListener('submit', (event) => {
                    if (bankAccountSelect.value !== originalValue && !window.confirm(confirmationMessage)) {
                        event.preventDefault();
                    }
                });
            }

            const bankAlert = document.getElementById('bankAccountUnavailableAlert');
            const pairingFields = form.querySelectorAll<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>('[data-bank-pairing-field]');
            moveAlertBeforeField(bankAlert, bankAccountSelect);

            const synchronizeBankFields = (): void => {
                const enabled = bankAccountSelect.value !== '';
                bankAlert?.classList.toggle('d-none', enabled);
                setFieldsEnabled(pairingFields, enabled);
            };

            synchronizeBankFields();
            bankAccountSelect.addEventListener('change', synchronizeBankFields);
        }

        const emailSelect = form.querySelector<HTMLSelectElement>('.ui--emailSelectbox');
        if (emailSelect === null) {
            return;
        }

        const emailAlert = document.getElementById('emailUnavailableAlert');
        const emailFields = form.querySelectorAll<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>('[data-email-field]');
        moveAlertBeforeField(emailAlert, emailSelect);

        const synchronizeEmailFields = (): void => {
            const enabled = emailSelect.value !== '';
            emailAlert?.classList.toggle('d-none', enabled);
            setFieldsEnabled(emailFields, enabled);
        };

        synchronizeEmailFields();
        emailSelect.addEventListener('change', synchronizeEmailFields);
    });
}

export function initializeCustomerTypeFields(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-customer-section]').forEach((section) => {
        if (!markInitialized(section)) {
            return;
        }

        const radios = section.querySelectorAll<HTMLInputElement>('[data-customer-type-radio]');
        const scopedBlocks = section.querySelectorAll<HTMLElement>('[data-customer-scope]');

        const synchronizeCustomerType = (): void => {
            const selectedType = Array.from(radios).find((radio) => radio.checked)?.value ?? 'company';
            scopedBlocks.forEach((block) => {
                const scope = block.dataset.customerScope;
                const visible = scope === 'company'
                    ? selectedType === 'company'
                    : selectedType === 'company' || selectedType === 'person';

                block.hidden = !visible;
                block.querySelectorAll<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement|HTMLButtonElement>('input, select, textarea, button')
                    .forEach((field) => {
                        if (field.type !== 'radio' && field.name !== '') {
                            field.disabled = !visible;
                        }
                    });
            });
        };

        synchronizeCustomerType();
        radios.forEach((radio) => radio.addEventListener('change', synchronizeCustomerType));
    });
}

export function initializeBugReportDiagnostics(root: ParentNode = document): void {
    root.querySelectorAll<HTMLInputElement>('[data-bug-report-diagnostics]').forEach((field) => {
        if (!markInitialized(field)) {
            return;
        }

        const navigatorWithDiagnostics = navigator as Navigator & {
            deviceMemory?: number;
            connection?: {
                effectiveType?: string;
                downlink?: number;
                rtt?: number;
                saveData?: boolean;
            };
        };

        field.value = JSON.stringify({
            capturedAt: new Date().toISOString(),
            page: {
                url: window.location.href,
                referrer: document.referrer || null,
                title: document.title,
            },
            navigator: {
                userAgent: navigator.userAgent,
                language: navigator.language,
                languages: navigator.languages,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                online: navigator.onLine,
                hardwareConcurrency: navigator.hardwareConcurrency,
                maxTouchPoints: navigator.maxTouchPoints,
                deviceMemory: navigatorWithDiagnostics.deviceMemory ?? null,
            },
            screen: {
                width: window.screen.width,
                height: window.screen.height,
                availableWidth: window.screen.availWidth,
                availableHeight: window.screen.availHeight,
                colorDepth: window.screen.colorDepth,
                pixelDepth: window.screen.pixelDepth,
                orientation: window.screen.orientation?.type ?? null,
            },
            viewport: {
                innerWidth: window.innerWidth,
                innerHeight: window.innerHeight,
                outerWidth: window.outerWidth,
                outerHeight: window.outerHeight,
                devicePixelRatio: window.devicePixelRatio,
                visualViewportWidth: window.visualViewport?.width ?? null,
                visualViewportHeight: window.visualViewport?.height ?? null,
                visualViewportScale: window.visualViewport?.scale ?? null,
            },
            locale: {
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                timezoneOffsetMinutes: new Date().getTimezoneOffset(),
            },
            connection: navigatorWithDiagnostics.connection ?? null,
        });
    });
}

export function initializePageEnhancements(root: ParentNode = document): void {
    initializePageHelp(root);
    initializeHelpLayouts(root);
    initializePaymentForms(root);
    initializeCustomerTypeFields(root);
    initializeBugReportDiagnostics(root);
}
