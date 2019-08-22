declare module 'naja' {
    type NajaListener<T> = { handleEvent(event: T): void } | { (event: T): void };

    export type InteractionEvent = { element: HTMLElement, originalEvent?: Event, options: object } & Event;

    export interface EventListenersMap {
        'init': { defaultOptions: object } & Event;
        'load': {} & Event;
        'interaction': InteractionEvent;
        'before': { xhr: XMLHttpRequest, method: string, url: string, data: any, options: object } & Event;
        'start': { request: Promise<object>, xhr: XMLHttpRequest } & Event;
        'abort': { xhr: XMLHttpRequest };
        'success': { xhr: XMLHttpRequest, response: object, options: object } & Event;
        'error': { error: Error, xhr: XMLHttpRequest, response?: object, options: object } & Event;
        'complete': { error?: Error, xhr: XMLHttpRequest, response?: object, options: object } & Event;
    }

    interface ExtensionConstructor<T extends Array<any>> {
        new(naja: Naja, ...args: T): any;
    }

    interface UIHandler {
        bindUI(element: Element): void;

        handleUI(evt: Event): void;

        clickElement(el: Element, options?: object, evt?: Event): void;

        submitForm(form: HTMLFormElement, options?: object, evt?: Event): void;

        isUrlAllowed(url: string): boolean;
    }

    interface FormsHandler {
        netteForms: object;

        initForms(element: Element): void;

        processForm(evt: Event): void;
    }

    interface Naja {
        uiHandler: UIHandler;
        formsHandler: FormsHandler;

        registerExtension<T extends Array<any>>(extensionClass: ExtensionConstructor<T>, ...args: T): void;

        makeRequest(method: string, url: string, data?: any, options?: object): Promise<object>;

        addEventListener<K extends keyof EventListenersMap>(
            type: K,
            listener: NajaListener<EventListenersMap[K]> | null,
            options?: boolean | AddEventListenerOptions,
        ): void;

        initialize(options?: object): void;
    }

    const naja: Naja;
    export default naja;
}
