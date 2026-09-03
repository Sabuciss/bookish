export function initDrawerNavigation() {
    function isDrawerOpen(drawerId) {
        const drawer = document.getElementById(drawerId);

        if (!drawer) {
            return false;
        }

        return !drawer.classList.contains('-translate-x-full');
    }

    function showDrawer(drawerId) {
        const drawer = document.getElementById(drawerId);
        const overlay = document.getElementById('drawer-overlay');

        if (!drawer) {
            return;
        }

        drawer.classList.remove('-translate-x-full');
        drawer.setAttribute('aria-hidden', 'false');

        if (overlay) {
            overlay.classList.remove('hidden');
        }
    }

    function hideDrawer(drawerId) {
        const drawer = document.getElementById(drawerId);
        const overlay = document.getElementById('drawer-overlay');

        if (!drawer) {
            return;
        }

        drawer.classList.add('-translate-x-full');
        drawer.setAttribute('aria-hidden', 'true');

        if (overlay) {
            overlay.classList.add('hidden');
        }
    }

    document.querySelectorAll('[data-drawer-show]').forEach((button) => {
        button.addEventListener('click', () => {
            const drawerId = button.getAttribute('data-drawer-show') || button.getAttribute('data-drawer-target');
            if (drawerId) {
                if (isDrawerOpen(drawerId)) {
                    hideDrawer(drawerId);
                } else {
                    showDrawer(drawerId);
                }
            }
        });
    });

    document.querySelectorAll('[data-drawer-hide]').forEach((button) => {
        button.addEventListener('click', () => {
            const drawerId = button.getAttribute('data-drawer-hide') || button.getAttribute('aria-controls');
            if (drawerId) {
                hideDrawer(drawerId);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[id][aria-labelledby="drawer-navigation-label"]').forEach((drawer) => {
            if (!drawer.classList.contains('-translate-x-full')) {
                hideDrawer(drawer.id);
            }
        });
    });
}
