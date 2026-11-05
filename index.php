<?php
require_once 'config.php';
require_once 'functions.php';

// --- IMPOSTAZIONI (modificabili dal pannello admin) ---
$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_desc       = getSetting($pdo, 'site_description', '');
$site_logo       = getSetting($pdo, 'site_logo', 'assets/img/latinclub-wordmark.png');
$hero_title      = getSetting($pdo, 'hero_title', '');
$hero_text       = getSetting($pdo, 'hero_text', '');
$hero_image      = getSetting($pdo, 'hero_image', '');
$serate_title    = getSetting($pdo, 'serate_title', 'Serate del weekend');
$soci_note       = getSetting($pdo, 'soci_note', '');
$membership_url  = getSetting($pdo, 'membership_url', 'https://4gest.4settori.net/it/domanda/danceclub.html');
$instagram_corsi = getSetting($pdo, 'instagram_corsi', '');
$founding_year   = getSetting($pdo, 'founding_year', '2001');

$countdown_enabled = getSetting($pdo, 'countdown_enabled', '1');
$countdown_day     = (int)getSetting($pdo, 'countdown_day', '5');
$countdown_time    = getSetting($pdo, 'countdown_time', '20:30');
$countdown_label   = getSetting($pdo, 'countdown_label', 'Serata Caraibiche');
$cd_days = [1=>'Lunedì',2=>'Martedì',3=>'Mercoledì',4=>'Giovedì',5=>'Venerdì',6=>'Sabato',7=>'Domenica'];

$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');

// --- DATI DINAMICI ---
$schedule     = getWeeklySchedule($pdo);
$todayIdx     = getTodayIndex();
$phone        = getSetting($pdo, 'phone', '');
$address      = getSetting($pdo, 'address', '');
$course_count = countDisciplines($pdo);

// Serate del weekend (venerdì → domenica)
$weekend_days = [];
for ($i = 4; $i <= 6; $i++) {
    $row = $schedule[$i] ?? null;
    if (!$row) $row = ['is_active' => 0, 'title' => '', 'start_time' => '', 'note' => ''];
    $weekend_days[] = $row + ['day_index' => $i];
}

// Corsi in evidenza
$disciplines = getDisciplines($pdo);
$featured = array_values(array_filter($disciplines, function ($d) { return !empty($d['featured']); }));
$featured = array_slice($featured ?: $disciplines, 0, 6);

// Titolo hero: la parte dopo la virgola va in corsivo oro
$hero_title = $hero_title ?: 'Il ritmo di Lodi, dal 2001';
$hero_parts = array_map('trim', explode(',', $hero_title, 2));
if (count($hero_parts) === 2) {
    $hero_html = e($hero_parts[0]) . ',<br><em>' . e($hero_parts[1]) . '</em>';
} else {
    $hero_html = e($hero_title);
}

$hero_bg = ($hero_image && file_exists(__DIR__ . '/' . $hero_image)) ? $hero_image : 'assets/img/hero-default.jpg';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo e($site_title); ?> — Scuola di ballo e serate a Lodi</title>
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

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero__bg">
        <img src="<?php echo e($hero_bg); ?>" alt="" width="1920" height="800">
    </div>
    <div class="hero__veil"></div>

    <div class="container hero__inner">
        <div class="hero__content">
            <span class="hero__eyebrow">Scuola di ballo e club · Lodi · dal <?php echo e($founding_year); ?></span>
            <h1 class="hero__title"><?php echo $hero_html; ?></h1>
            <p class="hero__text"><?php echo e($hero_text); ?></p>
            <div class="hero__actions">
                <a href="soci.php" class="btn btn--primary">Diventa socio</a>
                <a href="corsi.php" class="btn btn--ghost">Scopri i corsi</a>
            </div>
            <div class="hero__meta">
                <span>Corsi in settimana</span>
                <span>Serate venerdì e sabato</span>
                <span>Ballo liscio la domenica</span>
            </div>
        </div>
    </div>
</section>

<!-- ===== COUNTDOWN SERATA ===== -->
<?php if ($countdown_enabled === '1'): ?>
    <div class="countdown-bar" data-countdown data-day="<?php echo (int)$countdown_day; ?>" data-time="<?php echo e($countdown_time); ?>">
        <div class="container countdown-bar__inner">
            <div class="countdown-bar__intro">
                <span class="countdown-bar__label">Prossima serata</span>
                <span class="countdown-bar__title" data-cd-title><?php echo e($countdown_label); ?></span>
                <span class="countdown-bar__meta"><?php echo e($cd_days[$countdown_day] ?? 'Venerdì'); ?> · dalle <?php echo e($countdown_time); ?> · La Pergola</span>
            </div>

            <div class="countdown" role="timer" aria-label="Countdown alla prossima serata">
                <div class="countdown__box"><b data-cd-days>00</b><span>Giorni</span></div>
                <div class="countdown__box"><b data-cd-hours>00</b><span>Ore</span></div>
                <div class="countdown__box"><b data-cd-mins>00</b><span>Min</span></div>
                <div class="countdown__box"><b data-cd-secs>00</b><span>Sec</span></div>
            </div>

            <a href="serate.php" class="btn btn--crimson btn--sm">Tutte le serate</a>
        </div>
    </div>
<?php endif; ?>

<!-- ===== IL CLUB ===== -->
<section class="section section--cream">
    <div class="container">
        <div class="intro">
            <div class="intro__copy">
                <span class="eyebrow">01 — Il club</span>
                <h2>Una scuola di ballo,<br><em>una pista</em> che vive tutto l'anno.</h2>
                <p class="lead">Dal 2001 la Dance Club SSD porta a Lodi la cultura del ballo latino e della danza da sala: corsi in settimana, serate nel weekend e una comunità che cresce ogni stagione.</p>
                <div class="intro__stats">
                    <div class="intro__stat">
                        <b><?php echo e($founding_year); ?></b>
                        <span>Anno di fondazione</span>
                    </div>
                    <div class="intro__stat">
                        <b><?php echo $course_count; ?></b>
                        <span>Discipline</span>
                    </div>
                    <div class="intro__stat">
                        <b>2</b>
                        <span>Sedi a Lodi</span>
                    </div>
                </div>
                <div class="mt-32">
                    <a href="storia.php" class="link-arrow">La nostra storia</a>
                </div>
            </div>

            <div class="intro__media">
                <img class="intro__img--a" src="assets/img/venue/pergola-ristorante.jpg" alt="La cena al ristorante del complesso La Pergola" loading="lazy">
                <img class="intro__img--b" src="assets/img/venue/pergola-cortile.jpg" alt="Il cortile del Centro Sportivo La Pergola" loading="lazy">
                <span class="intro__caption">La Pergola · San Martino in Strada</span>
            </div>
        </div>
    </div>
</section>

<!-- ===== I CORSI ===== -->
<section class="section section--white">
    <div class="container">
        <div class="section-head section-head--row">
            <div>
                <span class="eyebrow">02 — In settimana</span>
                <h2>I corsi</h2>
                <p class="lead">Salsa, bachata, country, balli di gruppo, folklore cubano e tango argentino. Lezioni per ogni livello con maestri certificati, dal lunedì al giovedì.</p>
            </div>
            <a href="corsi.php" class="link-arrow">Tutti i corsi</a>
        </div>

        <div class="course-grid">
            <?php foreach ($featured as $i => $d): ?>
                <article class="course-card">
                    <div class="course-card__top">
                        <span class="course-card__cat"><?php echo e(getCourseCategories()[$d['category']] ?? 'Corso'); ?></span>
                        <span class="course-card__num"><?php echo str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <h3><?php echo e($d['name']); ?></h3>
                    <?php $slot_text = disciplineScheduleText($pdo, (int)$d['id']); ?>
                    <?php if ($slot_text): ?>
                        <div class="course-card__when"><i class="far fa-clock"></i> <?php echo e($slot_text); ?></div>
                    <?php endif; ?>
                    <p><?php echo e($d['description']); ?></p>
                    <div class="course-card__teacher">
                        <span><?php echo $d['teacher'] ? 'Maestro: <b>' . e($d['teacher']) . '</b>' : '<b>Corpo docente Dance Club</b>'; ?></span>
                        <?php if (isLatinClubCourse($d)): ?>
                            <span class="course-card__brand">LatinClub</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== IL WEEKEND ===== -->
<section class="section section--dark">
    <div class="container">
        <div class="section-head section-head--row">
            <div>
                <span class="eyebrow">03 — Il weekend</span>
                <h2><?php echo e($serate_title); ?></h2>
                <p class="lead">Venerdì e sabato serata caraibica, domenica pomeriggio ballo liscio. Le locandine della settimana sono pubblicate qui sotto.</p>
            </div>
            <a href="serate.php" class="link-arrow">Tutte le serate</a>
        </div>

        <div class="serate-split">
            <div class="serate-split__media">
                <img src="assets/img/hero-default.jpg" alt="Il giardino estivo del Centro Sportivo La Pergola" loading="lazy">
                <span class="serate-split__caption">Giardino estivo · La Pergola</span>
            </div>

            <div class="serate-split__body">
                <div class="schedule">
                    <?php foreach ($weekend_days as $row):
                        $i = (int)$row['day_index'];
                        $isToday = ($i === $todayIdx);
                        $active = !empty($row['is_active']) && trim((string)($row['title'] ?? '')) !== '';
                    ?>
                        <div class="schedule__row <?php echo $isToday ? 'is-today' : ''; ?> <?php echo $active ? '' : 'is-inactive'; ?>">
                            <span class="schedule__day">
                                <?php echo e(getItalianDayName($i)); ?><?php echo $isToday ? '<span class="today-tag">Oggi</span>' : ''; ?>
                            </span>
                            <span class="schedule__title">
                                <?php echo $active ? e($row['title']) : 'Nessuna serata programmata'; ?>
                                <?php if ($active && !empty($row['note'])): ?><small><?php echo e($row['note']); ?></small><?php endif; ?>
                            </span>
                            <span class="schedule__time">
                                <?php echo ($active && !empty($row['start_time'])) ? e($row['start_time']) : '—'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="serate-split__actions">
                    <a href="serate.php" class="btn btn--soft">Tutte le serate</a>
                    <a href="soci.php" class="link-arrow">Ingresso riservato ai soci</a>
                </div>
            </div>
        </div>

        <?php $home_posters = getWeeklyPosters(6); ?>
        <?php if (!empty($home_posters)): ?>
            <div class="mt-48">
                <?php include __DIR__ . '/locandine-block.php'; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== TESSERA ===== -->
<section class="section section--cream">
    <div class="container">
        <div class="feature">
            <div class="feature__media">
                <div class="membership-card">
                    <div class="membership-card__top">
                        <span class="membership-card__brand">Dance Club</span>
                        <span class="membership-card__label">Tessera socio</span>
                    </div>
                    <div class="membership-card__bottom">
                        <span class="membership-card__season">Stagione <?php echo date('Y'); ?> / <?php echo date('Y') + 1; ?></span>
                        <span class="membership-card__note">Dance Club SSD · Lodi</span>
                    </div>
                </div>
            </div>
            <div class="feature__body">
                <span class="eyebrow">04 — Tessera</span>
                <h2>Ingresso riservato ai soci</h2>
                <p>L'accesso a serate, eventi danzanti e corsi è riservato a chi è in possesso della <strong>tessera associativa in corso di validità</strong>.</p>
                <ol class="steps">
                    <li><span class="steps__num">1</span><span>Apri la domanda di tesseramento online e compila i tuoi dati.</span></li>
                    <li><span class="steps__num">2</span><span>Invia la richiesta: lo staff la verifica e conferma il tesseramento.</span></li>
                    <li><span class="steps__num">3</span><span>Con la tessera attiva entri a serate, eventi e corsi.</span></li>
                </ol>
                <div class="btn-row">
                    <a href="<?php echo e($membership_url); ?>" target="_blank" rel="noopener" class="btn btn--crimson">Richiedi la tessera online</a>
                    <a href="soci.php" class="btn btn--secondary">Come funziona</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="cta-band">
    <div class="container">
        <h2>La pista ti aspetta.<br><em>Vieni a ballare.</em></h2>
        <p>Corsi in settimana, serate nel weekend. Chiamaci o scrivici per qualsiasi informazione.</p>
        <div class="btn-row">
            <?php if ($phone): ?>
                <a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone)); ?>" class="btn btn--primary"><i class="fas fa-phone"></i> <?php echo e($phone); ?></a>
            <?php endif; ?>
            <a href="contatti.php" class="btn btn--secondary">Scrivici</a>
            <?php if ($instagram_corsi): ?>
                <a href="<?php echo e(normalizeUrl($instagram_corsi)); ?>" target="_blank" rel="noopener" class="btn btn--secondary"><i class="fab fa-instagram"></i> Instagram corsi</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script src="assets/js/countdown.js"></script>
<script src="assets/js/cookie-consent.js"></script>

</body>
</html>
