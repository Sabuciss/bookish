export function initThemeToggle() {
    function animateThemeIcon(icon) {
        if (!icon) {
            return;
        }

        icon.classList.remove('theme-toggle-icon-pop');
        void icon.offsetWidth;
        icon.classList.add('theme-toggle-icon-pop');
    }

    function syncThemeIcons(shouldAnimate) {
        const isDarkMode = document.documentElement.classList.contains('dark');
        const buttons = document.querySelectorAll('#theme-toggle, .theme-toggle-btn, .theme-toggle-nav');

        buttons.forEach((btn) => {
            btn.setAttribute('aria-label', isDarkMode ? 'Pārslēgt uz gaišo tēmu' : 'Pārslēgt uz tumšo tēmu');

            const darkIcon = btn.querySelector('#theme-toggle-dark-icon, .theme-dark-icon');
            const lightIcon = btn.querySelector('#theme-toggle-light-icon, .theme-light-icon');

            if (darkIcon) {
                darkIcon.classList.add('hidden');
            }
            if (lightIcon) {
                lightIcon.classList.add('hidden');
            }

            if (isDarkMode) {
                if (lightIcon) {
                    lightIcon.classList.remove('hidden');
                    if (shouldAnimate) {
                        animateThemeIcon(lightIcon);
                    }
                }
            } else {
                if (darkIcon) {
                    darkIcon.classList.remove('hidden');
                    if (shouldAnimate) {
                        animateThemeIcon(darkIcon);
                    }
                }
            }
        });
    }

    syncThemeIcons(false);

    const buttons = document.querySelectorAll('#theme-toggle, .theme-toggle-btn, .theme-toggle-nav');
    if (!buttons.length) {
        return;
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const currentTheme = localStorage.getItem('color-theme');
            const isDarkMode = document.documentElement.classList.contains('dark');

            if (currentTheme) {
                if (currentTheme === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else if (isDarkMode) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }

            syncThemeIcons(true);
        });
    });
}
