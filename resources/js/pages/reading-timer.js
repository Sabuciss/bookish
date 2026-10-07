const timerForm = document.getElementById('reading-timer-form');
const timerMinutesInput = document.getElementById('timer-minutes');
const timerDisplay = document.getElementById('reading-timer-display');
const timerStartButton = document.getElementById('timer-start');
const timerPauseButton = document.getElementById('timer-pause');
const timerResetButton = document.getElementById('timer-reset');
const timerSaveButton = document.getElementById('timer-save');
const timerError = document.getElementById('timer-error');

if (timerForm && timerMinutesInput && timerDisplay && timerStartButton && timerPauseButton && timerResetButton) {
    let sessionId = null;
    let elapsedSeconds = 0;
    let activeSince = null;
    let timerInterval = null;

    const sessionUrl = (template) => template.replace('__SESSION__', String(sessionId));
    const csrfToken = timerForm.querySelector('input[name="_token"]').value;

    const currentElapsedSeconds = () => elapsedSeconds + (activeSince ? Math.floor((Date.now() - activeSince) / 1000) : 0);

    const updateTimerDisplay = () => {
        const plannedSeconds = Math.max(0, Number(timerMinutesInput.value || 0) * 60);
        const remainingSeconds = Math.max(0, plannedSeconds - currentElapsedSeconds());
        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    };

    const setRunningState = (running) => {
        timerStartButton.textContent = running ? 'Darbojas' : (sessionId ? 'Turpināt' : 'Starts');
        timerStartButton.disabled = running;
        timerPauseButton.disabled = !running;
        timerResetButton.disabled = Boolean(sessionId);
        timerMinutesInput.disabled = Boolean(sessionId);
        timerForm.querySelector('#timer-challenge').disabled = Boolean(sessionId);
        timerSaveButton.disabled = !sessionId;
    };

    const send = async (url, data = {}) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });
        const result = await response.json();

        if (!response.ok) {
            const messages = Object.values(result.errors || {}).flat();
            throw new Error(messages[0] || result.message || 'Taimeri darbību neizdevās saglabāt.');
        }

        return result;
    };

    const showError = (error) => {
        timerError.textContent = error.message;
        timerError.hidden = false;
    };

    const startDisplayInterval = () => {
        window.clearInterval(timerInterval);
        timerInterval = window.setInterval(updateTimerDisplay, 1000);
        updateTimerDisplay();
    };

    timerStartButton.addEventListener('click', async () => {
        timerError.hidden = true;

        try {
            if (!sessionId) {
                const result = await send(timerForm.action, {
                    planned_minutes: Number(timerMinutesInput.value),
                    challenge_id: timerForm.querySelector('#timer-challenge').value || null,
                });
                sessionId = result.id;
                elapsedSeconds = result.elapsedSeconds;
            } else {
                const result = await send(sessionUrl(timerForm.dataset.resumeUrl));
                elapsedSeconds = result.elapsedSeconds;
            }

            activeSince = Date.now();
            setRunningState(true);
            startDisplayInterval();
        } catch (error) {
            showError(error);
        }
    });

    timerPauseButton.addEventListener('click', async () => {
        timerError.hidden = true;

        try {
            const result = await send(sessionUrl(timerForm.dataset.pauseUrl));
            elapsedSeconds = result.elapsedSeconds;
            activeSince = null;
            window.clearInterval(timerInterval);
            setRunningState(false);
            updateTimerDisplay();
        } catch (error) {
            showError(error);
        }
    });

    timerResetButton.addEventListener('click', () => {
        window.clearInterval(timerInterval);
        elapsedSeconds = 0;
        activeSince = null;
        updateTimerDisplay();
    });

    timerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        timerError.hidden = true;

        if (!sessionId) {
            showError(new Error('Vispirms palaid taimeri.'));
            return;
        }

        try {
            const result = await send(sessionUrl(timerForm.dataset.completeUrl), {
                pages_read: Number(timerForm.querySelector('[name="pages_read"]').value),
                notes: timerForm.querySelector('[name="notes"]').value,
                is_public: timerForm.querySelector('#session-is-public').checked,
            });
            window.location.assign(result.redirect);
        } catch (error) {
            showError(error);
        }
    });

    if (timerForm.dataset.activeSessionId) {
        sessionId = Number(timerForm.dataset.activeSessionId);
        elapsedSeconds = Number(timerForm.dataset.activeElapsed || 0);
        const isRunning = timerForm.dataset.activeStatus === 'running';
        activeSince = isRunning ? Date.now() : null;
        setRunningState(isRunning);

        if (isRunning) {
            startDisplayInterval();
        } else {
            updateTimerDisplay();
        }
    } else {
        timerPauseButton.disabled = true;
        timerSaveButton.disabled = true;
        updateTimerDisplay();
    }
}