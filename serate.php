<?php
require_once 'config.php';
require_once 'functions.php';

$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_desc       = getSetting($pdo, 'site_description', '');
$site_logo       = getSetting($pdo, 'site_logo', '');
$serate_title    = getSetting($pdo, 'serate_title', 'Serate e locandine');
$serate_text     = getSetting($pdo, 'serate_text', '');
$soci_note       = getSetting($pdo, 'soci_note', '');
$whatsapp        = getSetting($pdo, 'whatsapp', '');
$instagram_serate = getSetting($pdo, 'instagram_serate', '');
$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');

$schedule  = getWeeklySchedule($pdo);
$todayIdx  = getTodayIndex();
$wa_link   = $whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';
$dd_phone  = getSetting($pdo, 'phone', '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo e($serate_title); ?> — <?php echo e($site_title); ?></title>
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
        <span class="eyebrow">Il club</span>
        <h1><?php echo e($serate_title); ?></h1>
        <p><?php echo e($serate_text); ?></p>
    </div>
</section>

<section class="page-content">
    <div class="container">
        <div class="section-head section-head--row">
            <div>
                <span class="eyebrow">Aggiornate ogni settimana</span>
                <h2>Le locandine di questa settimana</h2>
                <p class="lead">Clicca sulla locandina per aprirla. Se non è ancora pubblicata trovi qui sotto il palinsesto fisso.</p>
            </div>
        </div>

        <?php include __DIR__ . '/locandine-block.php'; ?>

        <div class="note mt-32">
            <i class="fas fa-id-card"></i>
            <p><strong>Ingresso riservato ai soci.</strong> L'accesso alle serate è riservato a chi ha la tessera associativa in corso di validità: puoi richiederla online dalla pagina <a href="soci.php">Soci</a> oppure chiamando le linee info del club.</p>
        </div>
    </div>
</section>

<section class="section section--white">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Tutto l'anno</span>
            <h2>Palinsesto fisso</h2>
            <p class="lead">Serate caraibiche venerdì e sabato, ballo liscio la domenica pomeriggio. I corsi della settimana sono nella pagina Corsi.</p>
        </div>

        <div class="schedule">
            <?php for ($i = 4; $i <= 6; $i++):
                $row = $schedule[$i] ?? null;
                $isToday = ($i === $todayIdx);
                $active = $row && !empty($row['is_active']) && trim((string)($row['title'] ?? '')) !== '';
            ?>
                <div class="schedule__row <?php echo $isToday ? 'is-today' : ''; ?> <?php echo $active ? '' : 'is-inactive'; ?>">
                    <span class="schedule__day">
                        <?php echo e(getItalianDayName($i)); ?><?php echo $isToday ? '<span class="today-tag">Oggi</span>' : ''; ?>
                    </span>
                    <span class="schedule__title">
                        <?php echo $active ? e($row['title']) : 'Riposo / corsi su calendario'; ?>
                        <?php if ($active && !empty($row['note'])): ?><small><?php echo e($row['note']); ?></small><?php endif; ?>
                    </span>
                    <span class="schedule__time">
                        <?php echo ($active && !empty($row['start_time'])) ? e($row['start_time']) : '—'; ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>

        <?php if ($soci_note): ?>
            <div class="note mt-32">
                <i class="fas fa-info-circle"></i>
                <p><?php echo e($soci_note); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (is_file(__DIR__ . '/assets/img/venue/pergola-ristorante.jpg')): ?>
<section class="section section--paper">
    <div class="container">
        <div class="feature feature--reverse">
            <div class="feature__body">
                <span class="eyebrow">Cena e ballo</span>
                <h2>Formula Dinner &amp; Dance</h2>
                <p>Venerdì e sabato, insieme alle serate caraibiche, è possibile cenare presso il ristorante del complesso La Pergola e accedere al ballo nello stesso complesso.</p>
                <p class="small">Per informazioni e disponibilità chiama le linee info del club oppure invia una richiesta dal modulo dei contatti.</p>
                <div class="btn-row">
                    <?php if ($dd_phone): ?>
                        <a href="tel:<?php echo e(preg_replace('/\s+/', '', $dd_phone)); ?>" class="btn btn--primary"><i class="fas fa-phone"></i> <?php echo e($dd_phone); ?></a>
                    <?php endif; ?>
                    <a href="contatti.php" class="btn btn--secondary">Scrivici</a>
                </div>
            </div>
            <div class="feature__media">
                <img src="assets/img/venue/pergola-ristorante.jpg" alt="Cena al ristorante del complesso La Pergola" loading="lazy">
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-band">
    <div class="container">
        <h2>Il weekend si balla da noi</h2>
        <p>Serate riservate ai soci: richiedi la tessera e preparati alla pista.</p>
        <div class="btn-row">
            <a href="soci.php" class="btn btn--primary">Diventa socio</a>
            <?php if ($instagram_serate): ?>
                <a href="<?php echo e(normalizeUrl($instagram_serate)); ?>" target="_blank" rel="noopener" class="btn btn--secondary"><i class="fab fa-instagram"></i> Instagram serate</a>
            <?php endif; ?>
            <?php if ($wa_link): ?>
                <a href="<?php echo e($wa_link); ?>" target="_blank" rel="noopener" class="btn btn--secondary"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/cookie-consent.js"></script>

</body>
</html>
