const timerMinutesInput = document.getElementById('timer-minutes');
const timerDisplay = document.getElementById('reading-timer-display');
const timerStartButton = document.getElementById('timer-start');
const timerPauseButton = document.getElementById('timer-pause');
const timerResetButton = document.getElementById('timer-reset');
const timerElapsedSecondsInput = document.getElementById('timer-elapsed-seconds');
const timerStartedAtInput = document.getElementById('timer-started-at');
const timerEndedAtInput = document.getElementById('timer-ended-at');

if (timerMinutesInput && timerDisplay && timerStartButton && timerPauseButton && timerResetButton) {
    let timerInterval = null;
    let remainingSeconds = 0;
    let totalSeconds = 0;
    let isPaused = false;

    const formatTimer = (seconds) => {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;

        return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const updateTimerDisplay = () => {
        timerDisplay.textContent = formatTimer(remainingSeconds);
    };

    const stopTimer = () => {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    };

    const setFromInputMinutes = () => {
        const minutes = Number(timerMinutesInput.value || 0);
        totalSeconds = Math.max(0, minutes * 60);
        remainingSeconds = totalSeconds;
        updateTimerDisplay();
    };

    setFromInputMinutes();

    timerMinutesInput.addEventListener('change', () => {
        if (!timerInterval) {
            setFromInputMinutes();
        }
    });

    timerStartButton.addEventListener('click', () => {
        if (timerInterval) {
            return;
        }

        if (!isPaused) {
            setFromInputMinutes();
            if (timerStartedAtInput) {
                timerStartedAtInput.value = new Date().toISOString();
            }
        }

        isPaused = false;
        timerInterval = window.setInterval(() => {
            if (remainingSeconds > 0) {
                remainingSeconds -= 1;
                updateTimerDisplay();

                if (timerElapsedSecondsInput) {
                    timerElapsedSecondsInput.value = String(totalSeconds - remainingSeconds);
                }

                return;
            }

            stopTimer();

            if (timerEndedAtInput) {
                timerEndedAtInput.value = new Date().toISOString();
            }

            alert('Laiks beidzās!');
        }, 1000);
    });

    timerPauseButton.addEventListener('click', () => {
        if (timerInterval) {
            stopTimer();
            isPaused = true;
        }
    });

    timerResetButton.addEventListener('click', () => {
        stopTimer();
        isPaused = false;
        setFromInputMinutes();

        if (timerElapsedSecondsInput) {
            timerElapsedSecondsInput.value = '';
        }

        if (timerStartedAtInput) {
            timerStartedAtInput.value = '';
        }

        if (timerEndedAtInput) {
            timerEndedAtInput.value = '';
        }
    });
}
