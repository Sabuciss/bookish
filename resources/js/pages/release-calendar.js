const releaseCalendarData = document.getElementById('release-calendar-reminders');
const releaseCalendarTitle = document.getElementById('release-calendar-month-title');
const releaseCalendarDays = document.getElementById('release-calendar-days');

if (releaseCalendarData && releaseCalendarTitle && releaseCalendarDays) {
    let reminders = [];

    try {
        reminders = JSON.parse(releaseCalendarData.textContent || '[]');
    } catch {
        reminders = [];
    }

    const today = new Date();
    let visibleMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const releaseDates = reminders.reduce((dates, reminder) => {
        dates[reminder.date] = [...(dates[reminder.date] || []), reminder.title];
        return dates;
    }, {});
    const monthFormatter = new Intl.DateTimeFormat('lv-LV', {
        month: 'long',
        year: 'numeric',
    });

    const escapeHtml = (text) => String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const toDateKey = (year, month, day) => `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

    const renderCalendar = () => {
        const year = visibleMonth.getFullYear();
        const month = visibleMonth.getMonth();
        const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        releaseCalendarTitle.textContent = monthFormatter.format(visibleMonth);
        releaseCalendarDays.innerHTML = '';

        for (let index = 0; index < firstWeekday; index += 1) {
            releaseCalendarDays.insertAdjacentHTML('beforeend', '<span class="release-calendar-day is-empty"></span>');
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const dateKey = toDateKey(year, month, day);
            const titles = releaseDates[dateKey] || [];
            const isToday = dateKey === toDateKey(today.getFullYear(), today.getMonth(), today.getDate());
            const classes = ['release-calendar-day'];

            if (titles.length) {
                classes.push('has-release');
            }
            if (isToday) {
                classes.push('is-today');
            }

            const label = titles.length
                ? `${day}. datums: ${titles.join(', ')}`
                : `${day}. datums`;
            releaseCalendarDays.insertAdjacentHTML(
                'beforeend',
                `<span class="${classes.join(' ')}" aria-label="${escapeHtml(label)}" title="${escapeHtml(titles.join(', '))}"><span>${day}</span>${titles.length ? `<small>${titles.length}</small>` : ''}</span>`,
            );
        }
    };

    document.querySelector('[data-release-calendar-previous]')?.addEventListener('click', () => {
        visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
        renderCalendar();
    });

    document.querySelector('[data-release-calendar-next]')?.addEventListener('click', () => {
        visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
        renderCalendar();
    });

    renderCalendar();
}