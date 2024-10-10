import NProgress from 'nprogress';

import type { Extension, Naja } from 'naja/dist/Naja';

export class ProgressBar implements Extension {
    public initialize(naja: Naja): void {
        naja.addEventListener('start', () => {
            NProgress.start();
        });
        naja.addEventListener('complete', () => {
            NProgress.done();
        });
    }
}
