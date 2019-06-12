import * as NProgress from 'nprogress';

export class ProgressBar {
    constructor(naja: any) {
        naja.addEventListener('start', () => NProgress.start());
        naja.addEventListener('complete', () => NProgress.done());
    }
}
