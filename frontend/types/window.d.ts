declare global {
    interface Window {
        BSN: {
            initCallback(element: Element): void,
        };
    }
}

export {};
