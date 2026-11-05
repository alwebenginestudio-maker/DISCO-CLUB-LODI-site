/* ============================================================
   assets/js/cookie-consent.js — Lavanderia Nord Servizi
   Gestione consenso cookie (banner + salvataggio scelta)
   ============================================================ */
(function () {
    'use strict';

    /**
     * Salva la scelta dell'utente e nasconde il banner.
     * @param {string} choice - 'accepted' oppure 'refused'
     */
    window.setCookieConsent = function (choice) {
        if (choice !== 'accepted' && choice !== 'refused') return;

        var cookieName = window.COOKIE_CONSENT_NAME || 'cookie_consent';
        var days       = parseInt(window.COOKIE_EXPIRE_DAYS, 10) || 365;

        var expires = new Date();
        expires.setFullYear(expires.getFullYear() + days);

        // Flag Secure solo se il sito è servito via HTTPS
        var secure = (location.protocol === 'https:') ? '; Secure' : '';

        document.cookie = cookieName + '=' + choice
            + '; expires=' + expires.toUTCString()
            + '; path=/; SameSite=Lax' + secure;

        var banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            // Su desktop il banner è centrato con translateX(-50%);
            // su mobile è ancorato con left/right, quindi niente translateX.
            var isMobile = window.matchMedia('(max-width: 600px)').matches;
            var hideTransform = isMobile
                ? 'translateY(30px)'
                : 'translateX(-50%) translateY(30px)';

            banner.style.opacity = '0';
            banner.style.transform = hideTransform;

            setTimeout(function () {
                banner.style.display = 'none';
            }, 400);
        }

        // Hook per eventuali script di analytics / tracking futuro
        if (choice === 'accepted') {
            document.dispatchEvent(new CustomEvent('cookieConsentAccepted'));
        } else {
            document.dispatchEvent(new CustomEvent('cookieConsentRefused'));
        }
    };
})();