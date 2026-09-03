const challengeTypeSelect = document.getElementById('challenge-type');
const challengeTargetInput = document.getElementById('challenge-target-value');
const challengeTargetLabel = document.getElementById('challenge-target-label');

if (challengeTypeSelect && challengeTargetInput && challengeTargetLabel) {
    const applyTargetMeta = () => {
        const type = challengeTypeSelect.value === 'time' ? 'time' : 'pages';

        if (type === 'time') {
            challengeTargetLabel.firstChild.textContent = 'Mērķa vērtība (minūtes)';
            challengeTargetInput.placeholder = 'Piemēram: 600 minūtes';
            challengeTargetInput.setAttribute('aria-label', 'Mērķa vērtība minūtēs');
            return;
        }

        challengeTargetLabel.firstChild.textContent = 'Mērķa vērtība (lapas)';
        challengeTargetInput.placeholder = 'Piemēram: 200 lapas';
        challengeTargetInput.setAttribute('aria-label', 'Mērķa vērtība lapās');
    };

    applyTargetMeta();
    challengeTypeSelect.addEventListener('change', applyTargetMeta);
}
