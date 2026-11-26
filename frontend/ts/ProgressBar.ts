import NProgress from 'nprogress';

import type { CompleteEvent, Extension, Naja, StartEvent } from 'naja';

/** Shows the page loading bar. Background requests (`background: true` option) do not show it. */
export class ProgressBar implements Extension {
    public initialize(naja: Naja): void {
        naja.addEventListener('start', (event: StartEvent) => {
            if (event.detail.options.background !== true) {
                NProgress.start();
            }
        });
        naja.addEventListener('complete', (event: CompleteEvent) => {
            if (event.detail.options.background !== true) {
                NProgress.done();
            }
        });
    }
}
