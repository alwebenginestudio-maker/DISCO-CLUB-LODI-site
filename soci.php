<?php
require_once 'config.php';
require_once 'functions.php';

$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_desc       = getSetting($pdo, 'site_description', '');
$site_logo       = getSetting($pdo, 'site_logo', '');
$soci_note       = getSetting($pdo, 'soci_note', '');
$founding_year   = getSetting($pdo, 'founding_year', '2001');
$storia_title    = getSetting($pdo, 'chi_siamo_title', 'La nostra storia');
$phone           = getSetting($pdo, 'phone', '');
$membership_url  = getSetting($pdo, 'membership_url', 'https://4gest.4settori.net/it/domanda/danceclub.html');
$instagram_corsi = getSetting($pdo, 'instagram_corsi', '');
$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');

$schedule     = getWeeklySchedule($pdo);
$slots_by_day = getCourseSlotsByDay($pdo, true);
$todayIdx     = getTodayIndex();
$course_count = countDisciplines($pdo);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Soci e tessera — <?php echo e($site_title); ?></title>
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
        <span class="eyebrow">Dal <?php echo e($founding_year); ?></span>
        <h1>Soci e tessera</h1>
        <p>L'ingresso a serate, eventi danzanti e corsi è riservato ai soci con tessera associativa in corso di validità.</p>
    </div>
</section>

<section class="page-content">
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
                <span class="eyebrow">Tessera associativa</span>
                <h2>Come si diventa soci</h2>
                <?php $intro_text = trim((string)getSetting($pdo, 'home_text', '')); ?>
                <?php if ($intro_text !== ''): ?>
                    <p class="long-copy"><?php echo e($intro_text); ?></p>
                <?php endif; ?>

                <ol class="steps">
                    <li>
                        <span class="steps__num">1</span>
                        <span><b>Apri la domanda online</b> e compila i tuoi dati. Il link è sempre disponibile in questa pagina.</span>
                    </li>
                    <li>
                        <span class="steps__num">2</span>
                        <span><b>Attendi la conferma</b>: il tesseramento viene validato dallo staff e resta valido per tutta la stagione.</span>
                    </li>
                    <li>
                        <span class="steps__num">3</span>
                        <span><b>Entra e balla</b>: con la tessera in corso di validità accedi a serate, eventi e corsi.</span>
                    </li>
                </ol>

                <?php if ($soci_note): ?>
                    <div class="note mt-24">
                        <i class="fas fa-info-circle"></i>
                        <p><?php echo e($soci_note); ?></p>
                    </div>
                <?php endif; ?>

                <div class="btn-row mt-24">
                    <a href="<?php echo e($membership_url); ?>" target="_blank" rel="noopener" class="btn btn--primary">Richiedi la tessera online</a>
                    <?php if ($phone): ?>
                        <a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone)); ?>" class="btn btn--secondary"><?php echo e($phone); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--white">
    <div class="container-narrow">
        <div class="section-head section-head--center">
            <span class="eyebrow">Chi siamo</span>
            <h2><?php echo e($storia_title); ?></h2>
            <p class="lead">Dance Club SSD porta a Lodi la cultura del ballo latino e della danza da sala dal <?php echo e($founding_year); ?>.</p>
        </div>

        <div class="stats">
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

        <div class="text-center mt-32">
            <a href="storia.php" class="link-arrow">Leggi la nostra storia</a>
        </div>
    </div>
</section>

<section class="section section--paper">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Programmazione fissa</span>
            <h2>La settimana al Dance Club</h2>
            <p class="lead">In settimana i corsi, nel weekend le serate, la domenica pomeriggio il ballo liscio. La programmazione vale tutto l'anno.</p>
        </div>

        <div class="timetable">
            <?php for ($i = 0; $i < 7; $i++):
                $slots = $slots_by_day[$i] ?? [];
                $row = $schedule[$i] ?? null;
                $evening = $row && !empty($row['is_active']) && trim((string)($row['title'] ?? '')) !== '';
                $isToday = ($i === $todayIdx);
            ?>
                <div class="timetable__day <?php echo $isToday ? 'is-today' : ''; ?>">
                    <div class="timetable__day-head">
                        <span class="timetable__day-name"><?php echo e(getItalianDayName($i)); ?></span>
                        <?php if ($isToday): ?><span class="today-tag">Oggi</span><?php endif; ?>
                    </div>
                    <div class="timetable__list">
                        <?php if (!empty($slots)): ?>
                            <?php foreach ($slots as $slot): ?>
                                <div class="timetable__slot">
                                    <span class="timetable__time"><?php echo e(substr((string)$slot['start_time'], 0, 5)); ?></span>
                                    <div class="timetable__body">
                                        <b><?php echo e($slot['title']); ?></b>
                                        <?php if (!empty($slot['level'])): ?><span class="timetable__level"><?php echo e($slot['level']); ?></span><?php endif; ?>
                                        <small>
                                            <?php if (!empty($slot['venue'])): ?><i class="fas fa-location-dot"></i> <?php echo e($slot['venue']); ?><?php endif; ?>
                                            <?php if (!empty($slot['teacher'])): ?><span class="timetable__teacher"><?php echo e($slot['teacher']); ?></span><?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($evening): ?>
                            <div class="timetable__slot">
                                <span class="timetable__time"><?php echo e(substr((string)$row['start_time'], 0, 5)); ?></span>
                                <div class="timetable__body">
                                    <b><?php echo e($row['title']); ?></b>
                                    <?php if (!empty($row['note'])): ?><small><?php echo e($row['note']); ?></small><?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($slots) && !$evening): ?>
                            <span class="timetable__empty">Nessun corso in programma</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="mt-40">
            <a href="serate.php" class="link-arrow">Locandine e serate</a>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2>La tessera, poi si balla</h2>
        <p>Richiedi la tessera online o chiamaci: ti diciamo tutto noi.</p>
        <div class="btn-row">
            <a href="<?php echo e($membership_url); ?>" target="_blank" rel="noopener" class="btn btn--primary">Richiedi la tessera online</a>
            <a href="corsi.php" class="btn btn--secondary">Vedi i corsi</a>
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
