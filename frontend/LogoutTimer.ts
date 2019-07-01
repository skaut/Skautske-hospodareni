export class LogoutTimer {
    private minutesLeft: number = 30;
    private readonly timer: HTMLElement;
    private readonly minutesElement: HTMLElement;
    private readonly intervalId: number;

    constructor(timerId: string, minutesElementId: string) {
        this.timer = document.getElementById(timerId) as HTMLElement;
        this.minutesElement = document.getElementById(minutesElementId) as HTMLElement;

        this.decrementMinutes();
        this.intervalId = setInterval(this.decrementMinutes.bind(this), 60 * 1000);
    }

    private decrementMinutes(): void {
        this.minutesLeft--;

        if (this.minutesLeft < 0) {
            this.updateTimer('Byl jsi odhlášen!');
            clearInterval(this.intervalId);
            return;
        }

        if (this.minutesLeft === 10) {
            this.timer.classList.add('bg-danger');
        }

        this.updateTimer(this.minutesLeft + ' min', 'Do odhlášení zbývá ' + this.minutesLeft + ' min');
    };

    private updateTimer(text: string, title : string|null = null): void {
        this.minutesElement.title = title || text;
        this.minutesElement.innerText = text;
    }
}
