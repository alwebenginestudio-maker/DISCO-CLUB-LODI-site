/* ============================================================
   countdown.js — countdown fisso della prossima serata
   Configurato da admin: giorno (1=lun ... 7=dom), ora, titolo.
   Nessuna data: si ripete ogni settimana.
   ============================================================ */
(function () {
    'use strict';

    var root = document.querySelector('[data-countdown]');
    if (!root) return;

    var day  = parseInt(root.getAttribute('data-day') || '5', 10);
    var time = (root.getAttribute('data-time') || '20:30').split(':');
    var hour = parseInt(time[0] || '20', 10);
    var min  = parseInt(time[1] || '30', 10);

    if (!(day >= 1 && day <= 7)) day = 5;
    if (isNaN(hour)) hour = 20;
    if (isNaN(min)) min = 30;

    var els = {
        days:  root.querySelector('[data-cd-days]'),
        hours: root.querySelector('[data-cd-hours]'),
        mins:  root.querySelector('[data-cd-mins]'),
        secs:  root.querySelector('[data-cd-secs]')
    };
    var titleEl = root.querySelector('[data-cd-title]');
    var baseTitle = titleEl ? titleEl.textContent : '';

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    /* Occorrenza della serata nella settimana corrente (offset 0)
       o nella successiva (offset 1). */
    function occurrence(now, weekOffset) {
        var jsDay = day % 7; // 1..7 -> 1..6, domenica 7 -> 0
        var d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hour, min, 0, 0);
        var delta = (jsDay - d.getDay() + 7) % 7;
        d.setDate(d.getDate() + delta + weekOffset * 7);
        return d;
    }

    function tick() {
        var now = new Date();
        var thisWeek = occurrence(now, 0);
        var endOfNight = new Date(thisWeek.getTime() + 5 * 60 * 60 * 1000);

        // Dalle 20:30 a circa le 01:30: la serata è in corso.
        if (now >= thisWeek && now < endOfNight) {
            root.classList.add('is-live');
            if (titleEl) titleEl.textContent = 'Stasera si balla';
            if (els.days)  els.days.textContent  = '00';
            if (els.hours) els.hours.textContent = '00';
            if (els.mins)  els.mins.textContent  = '00';
            if (els.secs)  els.secs.textContent  = '00';
            return;
        }

        root.classList.remove('is-live');
        if (titleEl) titleEl.textContent = baseTitle;

        var target = occurrence(now, 0);
        if (target <= now) target = occurrence(now, 1);

        var diff = Math.floor((target.getTime() - now.getTime()) / 1000);
        if (diff < 0) diff = 0;

        var days  = Math.floor(diff / 86400);
        var hours = Math.floor((diff % 86400) / 3600);
        var mins  = Math.floor((diff % 3600) / 60);
        var secs  = diff % 60;

        if (els.days)  els.days.textContent  = pad(days);
        if (els.hours) els.hours.textContent = pad(hours);
        if (els.mins)  els.mins.textContent  = pad(mins);
        if (els.secs)  els.secs.textContent  = pad(secs);
    }

    tick();
    setInterval(tick, 1000);
})();
