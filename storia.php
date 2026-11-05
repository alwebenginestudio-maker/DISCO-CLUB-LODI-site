<?php
require_once 'config.php';
require_once 'functions.php';

$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_desc       = getSetting($pdo, 'site_description', '');
$site_logo       = getSetting($pdo, 'site_logo', '');
$storia_title    = getSetting($pdo, 'chi_siamo_title', 'La nostra storia');
$storia_text     = getSetting($pdo, 'chi_siamo_text', '');
$staff_title     = getSetting($pdo, 'staff_title', 'Direzione artistica e corpo docente');
$staff_intro     = getSetting($pdo, 'staff_intro', '');
$staff_list      = getSetting($pdo, 'staff_list', '');
$founding_year   = getSetting($pdo, 'founding_year', '2001');
$address         = getSetting($pdo, 'address', '');
$address2        = getSetting($pdo, 'address2', '');
$website         = getSetting($pdo, 'website', '');
$phone           = getSetting($pdo, 'phone', '');
$phone2          = getSetting($pdo, 'phone2', '');
$membership_url  = getSetting($pdo, 'membership_url', 'https://4gest.4settori.net/it/domanda/danceclub.html');
$instagram_corsi = getSetting($pdo, 'instagram_corsi', '');
$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');

$course_count = countDisciplines($pdo);

// Staff: una riga per persona → "Nome — Ruolo"
$staff = [];
foreach (array_filter(array_map('trim', explode("\n", (string)$staff_list))) as $line) {
    $parts = preg_split('/\s+[—–-]\s+/u', $line, 2);
    $staff[] = count($parts) === 2 ? ['name' => $parts[0], 'role' => $parts[1]] : ['name' => $line, 'role' => ''];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo e($storia_title); ?> — <?php echo e($site_title); ?></title>
    <meta name="description" content="<?php echo e($site_desc); ?>">
    <?php echo renderFavicon($site_logo); ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        :root {
            --primary-color: <?php echo e($primary_color); ?>;
            --secondary-color: <?php echo e($secondary_color); ?>;
            --accent-color: <?php echo e($accent_color); ?>;
            --bg-color: <?php echo e($bg_color); ?>;
            --text-color: <?php echo e($text_color); ?>;
            --footer-bg: <?php echo e($footer_bg); ?>;
        }
    </style>
</head>
<body>

<?php include 'cookie-banner.php'; ?>
<?php include 'header.php'; ?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Chi siamo · dal <?php echo e($founding_year); ?></span>
        <h1><?php echo e($storia_title); ?></h1>
        <p>Una scuola di ballo e un club: dal <?php echo e($founding_year); ?> portiamo a Lodi la cultura del ballo latino e della danza da sala.</p>
    </div>
</section>

<section class="page-content">
    <div class="container-narrow">
        <?php if ($storia_text): ?>
            <p class="long-copy"><?php echo e($storia_text); ?></p>
        <?php endif; ?>

        <div class="stats mt-40">
            <div>
                <span class="stat__num"><?php echo e($founding_year); ?></span>
                <span class="stat__label">Anno di fondazione</span>
            </div>
            <div>
                <span class="stat__num"><?php echo $course_count; ?></span>
                <span class="stat__label">Discipline</span>
            </div>
            <div>
                <span class="stat__num">2</span>
                <span class="stat__label">Sedi a Lodi</span>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($staff)): ?>
<section class="section section--dark">
    <div class="container">
        <div class="section-head section-head--row">
            <div>
                <span class="eyebrow">Chi vi insegna</span>
                <h2><?php echo e($staff_title); ?></h2>
                <?php if ($staff_intro): ?><p class="lead"><?php echo e($staff_intro); ?></p><?php endif; ?>
            </div>
        </div>

        <div class="staff-grid">
            <?php foreach ($staff as $s): ?>
                <div class="staff-item">
                    <b><?php echo e($s['name']); ?></b>
                    <?php if ($s['role']): ?><span><?php echo e($s['role']); ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--paper" id="sedi">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Dove si balla</span>
            <h2>Le nostre sedi</h2>
            <p class="lead">Serate ed eventi al Centro Sportivo La Pergola, corsi a Lodi presso l'Hotel Lodi.</p>
        </div>

        <div class="venue-grid">
            <?php if ($address): ?>
                <div class="venue-card">
                    <?php if (is_file(__DIR__ . '/assets/img/venue/pergola-giardino-estivo.jpg')): ?>
                        <img src="assets/img/venue/pergola-giardino-estivo.jpg" alt="Giardino estivo del Centro Sportivo La Pergola" loading="lazy">
                    <?php endif; ?>
                    <div class="venue-card__body">
                        <span class="venue-card__tag">Sede principale · Eventi e corsi</span>
                        <h3>Centro Sportivo La Pergola</h3>
                        <p><?php echo e($address); ?></p>
                        <a class="link-arrow" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($address); ?>" target="_blank" rel="noopener">Apri in Google Maps</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($address2): ?>
                <div class="venue-card">
                    <div class="venue-card__body">
                        <span class="venue-card__tag">Seconda sede · Corsi</span>
                        <h3>Hotel Lodi</h3>
                        <p><?php echo e($address2); ?></p>
                        <a class="link-arrow" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($address2); ?>" target="_blank" rel="noopener">Apri in Google Maps</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="data-box" style="grid-column:1 / -1;">
                <h3>Dati societari</h3>
                <p>
                    <strong>Ragione sociale:</strong> Dance Club Società Sportiva Dilettantistica a responsabilità limitata<br>
                    <strong>Sede:</strong> Via per Ca' de Bolli 11 — 26817 San Martino in Strada (LO)<br>
                    <strong>P. IVA / C.F.:</strong> 14300410967<br>
                    <?php if ($website): ?><strong>Sito ufficiale:</strong> <a href="<?php echo e($website); ?>" target="_blank" rel="noopener">danceclubssd.it</a><br><?php endif; ?>
                    <strong>Info e contatti:</strong> <?php echo e(trim($phone . ' · ' . $phone2, ' ·')); ?>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2>La pista ti aspetta.<br><em>Vieni a ballare.</em></h2>
        <p>Corsi in settimana, serate nel weekend. Scopri le discipline o richiedi la tessera.</p>
        <div class="btn-row">
            <a href="corsi.php" class="btn btn--primary">Scopri i corsi</a>
            <a href="<?php echo e($membership_url); ?>" target="_blank" rel="noopener" class="btn btn--secondary">Richiedi la tessera online</a>
            <?php if ($instagram_corsi): ?>
                <a href="<?php echo e(normalizeUrl($instagram_corsi)); ?>" target="_blank" rel="noopener" class="btn btn--secondary"><i class="fab fa-instagram"></i> Instagram corsi</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/cookie-consent.js"></script>

</body>
</html>
