/**
 * Apstiprinājums neatgriezeniskām darbībām.
 * Lietošana: <form data-confirm="Vai tiešām dzēst?"> … </form>
 * (bez inline onsubmit JavaScript skatos).
 */
export function initConfirmForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.dataset.confirm;

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
}
