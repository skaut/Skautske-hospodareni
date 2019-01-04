(function () {
    initializeLogoutTimer();
})();

function initializeLogoutTimer() {
    let minutesLeft = 30;

    const timer = document.getElementById('timer');
    const minutesElement = document.getElementById('timer-minutes');

    const decrementMinutes = () => {
        minutesLeft--;

        if (minutesLeft < 0) {
            minutesElement.title = minutesElement.innerText = 'Byl jsi odhlášen!';
            clearInterval(interval);
            return;
        }

        if (minutesLeft === 10) {
            timer.classList.add('bg-danger');
        }

        minutesElement.innerText = minutesLeft + ' min';
        minutesElement.title = 'Do odhlášení zbývá ' + minutesLeft + ' min';
    };

    decrementMinutes();
    const interval = setInterval(decrementMinutes, 60 * 1000);
}

function toggleAllCheckboxes(mainCheckboxElement, dependentCheckboxClass) {
    document.querySelectorAll("." + dependentCheckboxClass + " input[type='checkbox']")
        .forEach(checkbox => checkbox.checked = mainCheckboxElement.checked);
}
