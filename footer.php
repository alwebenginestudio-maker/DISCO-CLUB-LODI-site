<?php
// footer.php - Dance Club Lodi (Dance Club SSD)
if (!isset($footer_text))    $footer_text    = getSetting($pdo, 'footer_text', '');
if (!isset($phone))          $phone          = getSetting($pdo, 'phone', '');
if (!isset($phone2))         $phone2         = getSetting($pdo, 'phone2', '');
if (!isset($whatsapp))       $whatsapp       = getSetting($pdo, 'whatsapp', '');
if (!isset($address))        $address        = getSetting($pdo, 'address', '');
if (!isset($contact_email))  $contact_email  = getSetting($pdo, 'contact_email', '');
if (!isset($site_title))     $site_title     = getSetting($pdo, 'site_title', 'Dance Club Lodi');
if (!isset($footer_desc))    $footer_desc    = getSetting($pdo, 'footer_desc', '');
if (!isset($facebook))       $facebook       = getSetting($pdo, 'facebook', '');
if (!isset($instagram_serate)) $instagram_serate = getSetting($pdo, 'instagram_serate', '');
if (!isset($instagram_corsi))  $instagram_corsi  = getSetting($pdo, 'instagram_corsi', '');
if (!isset($tiktok))         $tiktok         = getSetting($pdo, 'tiktok', '');

if ($footer_desc === '') {
    $footer_desc = 'Scuola di ballo e club a Lodi dal 2001: corsi in settimana, serate caraibiche nel weekend e ballo liscio la domenica pomeriggio.';
}
$footer_text = $footer_text !== '' ? $footer_text : '© ' . date('Y') . ' Dance Club SSD — Dance Club Lodi';
?>
<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <a class="brand" href="index.php" aria-label="<?php echo e($site_title); ?> — home">
                <span class="brand__name"><?php echo e($site_title); ?></span>
                <span class="brand__tag">Scuola di ballo · dal 2001</span>
            </a>
            <p><?php echo e($footer_desc); ?></p>
            <?php if ($facebook || $instagram_serate || $instagram_corsi || $tiktok): ?>
                <div class="social-links social-links--labeled">
                    <?php if ($facebook): ?>
                        <a href="<?php echo e(normalizeUrl($facebook)); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if ($instagram_serate): ?>
                        <a href="<?php echo e(normalizeUrl($instagram_serate)); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>Serate</span></a>
                    <?php endif; ?>
                    <?php if ($instagram_corsi): ?>
                        <a href="<?php echo e(normalizeUrl($instagram_corsi)); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>Corsi</span></a>
                    <?php endif; ?>
                    <?php if ($tiktok): ?>
                        <a href="<?php echo e(normalizeUrl($tiktok)); ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h4 class="footer-title">Contatti</h4>
            <ul class="footer-list">
                <?php if ($phone): ?>
                    <li><a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone)); ?>"><?php echo e($phone); ?></a></li>
                <?php endif; ?>
                <?php if ($phone2): ?>
                    <li><a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone2)); ?>"><?php echo e($phone2); ?></a></li>
                <?php endif; ?>
                <?php if ($contact_email): ?>
                    <li><a href="mailto:<?php echo e($contact_email); ?>"><?php echo e($contact_email); ?></a></li>
                <?php endif; ?>
                <?php if ($address): ?>
                    <li><?php echo e($address); ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div>
            <h4 class="footer-title">Informazioni</h4>
            <ul class="footer-list">
                <li><a href="storia.php">La nostra storia</a></li>
                <li><a href="corsi.php">Corsi</a></li>
                <li><a href="serate.php">Serate e locandine</a></li>
                <li><a href="soci.php">Soci e tessera</a></li>
                <li><a href="contatti.php">Contatti</a></li>
                <li><a href="privacy.php">Privacy Policy</a></li>
                <li><a href="termini.php">Termini e condizioni</a></li>
            </ul>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- ===== PARTE FINALE — STRUTTURA STANDARD AL WEBENGINE ======== -->
    <!-- ============================================================= -->
    <div class="footer-brandbar">
        <p><?php echo e($footer_text); ?></p>
        <div class="footer-logo-container">
            <a href="https://www.alwebengine.it" target="_blank" rel="noopener" aria-label="AL WebEngine — visita alwebengine.it" title="Powered by AL WebEngine">
                <img src="assets/img/footer-logo.png" alt="AL WebEngine" class="footer-logo-img">
            </a>
        </div>
        <p class="footer-admin">
            <a href="admin.php" title="Area di amministrazione">
                <i class="fas fa-lock"></i> Admin
            </a>
        </p>
    </div>
</footer>
