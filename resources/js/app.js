import './bootstrap';
import './pages/reading-timer';
import './pages/reading-progress';
import './pages/reading-challenges';
import './pages/welcome-upcoming-releases';

import { initDrawerNavigation } from './ui/navigation';
import { initThemeToggle } from './ui/theme';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

initThemeToggle();
initDrawerNavigation();
