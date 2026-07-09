import {sessionKeepAliveSucceededEvent} from './ts/sessionKeepAlive';

export class LogoutTimer {
    private readonly sessionLengthMinutes = 30;
    private minutesLeft: number = this.sessionLengthMinutes;
    private readonly timer: HTMLElement;
    private readonly minutesElement: HTMLElement;
    private intervalId: number|null = null;

    constructor(timerId: string, minutesElementId: string) {
        this.timer = document.getElementById(timerId) as HTMLElement;
        this.minutesElement = document.getElementById(minutesElementId) as HTMLElement;
        if (this.timer === null) {
            return;
        }

        this.resetTimer();
        window.addEventListener(sessionKeepAliveSucceededEvent, () => this.resetTimer());
        window.addEventListener('storage', (event) => {
            if (event.key === 'hskauting.sessionKeepAlive.lastPingAt') {
                this.resetTimer();
            }
        });
    }

    private decrementMinutes(): void {
        this.minutesLeft--;

        if (this.minutesLeft < 0) {
            this.updateTimer('Byl jsi odhlášen!');
            this.stopTimer();
            return;
        }

        if (this.minutesLeft === 10) {
            this.timer.classList.add('bg-danger');
        }

        this.updateTimer(this.minutesLeft + ' min', 'Do odhlášení zbývá ' + this.minutesLeft + ' min');
    };

    private resetTimer(): void {
        this.minutesLeft = this.sessionLengthMinutes;
        this.timer.classList.remove('bg-danger');
        this.updateTimer(
            this.minutesLeft + ' min',
            'Do odhlášení zbývá ' + this.minutesLeft + ' min',
        );
        this.restartTimer();
    }

    private restartTimer(): void {
        this.stopTimer();
        this.intervalId = window.setInterval(this.decrementMinutes.bind(this), 60 * 1000);
    }

    private stopTimer(): void {
        if (this.intervalId === null) {
            return;
        }

        window.clearInterval(this.intervalId);
        this.intervalId = null;
    }

    private updateTimer(text: string, title : string|null = null): void {
        this.minutesElement.title = title || text;
        this.minutesElement.innerText = text;
    }
}
