import NProgress from 'nprogress';

export function ProgressBar(naja) {
    naja.addEventListener('start', () => NProgress.start());
    naja.addEventListener('complete', () => NProgress.done());
}
