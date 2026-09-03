export function initThemeToggle() {
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
    const themeToggleBtn = document.getElementById('theme-toggle');

    function animateThemeIcon(icon) {
        if (!icon) {
            return;
        }

        icon.classList.remove('theme-toggle-icon-pop');
        void icon.offsetWidth;
        icon.classList.add('theme-toggle-icon-pop');
    }

    function syncThemeIcons(shouldAnimate) {
        if (!themeToggleDarkIcon || !themeToggleLightIcon) {
            return;
        }

        themeToggleDarkIcon.classList.add('hidden');
        themeToggleLightIcon.classList.add('hidden');

        const isDarkMode = document.documentElement.classList.contains('dark');

        if (themeToggleBtn) {
            themeToggleBtn.setAttribute('aria-label', isDarkMode ? 'Pārslēgt uz gaišo tēmu' : 'Pārslēgt uz tumšo tēmu');
        }

        if (isDarkMode) {
            themeToggleLightIcon.classList.remove('hidden');
            if (shouldAnimate) {
                animateThemeIcon(themeToggleLightIcon);
            }
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
            if (shouldAnimate) {
                animateThemeIcon(themeToggleDarkIcon);
            }
        }
    }

    syncThemeIcons(false);

    if (!themeToggleBtn) {
        return;
    }

    themeToggleBtn.addEventListener('click', () => {
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
}
