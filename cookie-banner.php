<!-- cookie-banner.php - da includere dopo <body> in ogni pagina -->
<?php
// Se il consenso non è stato dato, mostriamo il banner
$consent = $_COOKIE[COOKIE_CONSENT_NAME] ?? '';
if ($consent !== 'accepted' && $consent !== 'refused'):
?>
<div id="cookie-consent-banner" role="dialog" aria-live="polite" aria-label="Consenso cookie">
    <div class="cookie-text">
        Questo sito utilizza cookie tecnici e, previo consenso, cookie di profilazione.
        Leggi la <a href="privacy.php">Privacy Policy</a>.
    </div>
    <div class="cookie-actions">
        <button onclick="setCookieConsent('refused')" class="btn-cookie-refuse" type="button">Solo tecnici</button>
        <button onclick="setCookieConsent('accepted')" class="btn-cookie-accept" type="button">Accetta tutti</button>
    </div>
</div>

<script>
    // Passa le costanti PHP al JS esterno (assets/js/cookie-consent.js)
    window.COOKIE_CONSENT_NAME = '<?php echo COOKIE_CONSENT_NAME; ?>';
    window.COOKIE_EXPIRE_DAYS  = <?php echo (int) COOKIE_EXPIRE_DAYS; ?>;
</script>
<?php endif; ?>
