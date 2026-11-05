<?php
require_once 'config.php';
require_once 'functions.php';

$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_desc       = getSetting($pdo, 'site_description', '');
$site_logo       = getSetting($pdo, 'site_logo', '');
$corsi_title     = getSetting($pdo, 'corsi_title', 'I corsi della scuola');
$corsi_text      = getSetting($pdo, 'corsi_text', '');
$soci_note       = getSetting($pdo, 'soci_note', '');
$instagram_corsi = getSetting($pdo, 'instagram_corsi', '');
$footer_text     = getSetting($pdo, 'footer_text', '');
$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');

$disciplines = getDisciplines($pdo);
$schedule    = getWeeklySchedule($pdo);
$categories  = getCourseCategories();
$slots_by_day = getCourseSlotsByDay($pdo, true);
$todayIdx     = getTodayIndex();

// Raggruppa i corsi per categoria (niente filtri o ricerca: poche discipline, si leggono tutte)
$grouped = [];
foreach ($categories as $key => $label) {
    $items = array_values(array_filter($disciplines, function ($d) use ($key) {
        return $d['category'] === $key;
    }));
    if ($items) $grouped[$key] = ['label' => $label, 'items' => $items];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo e($corsi_title); ?> — <?php echo e($site_title); ?></title>
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
        <span class="eyebrow">Scuola di ballo</span>
        <h1><?php echo e($corsi_title); ?></h1>
        <p><?php echo e($corsi_text); ?></p>
    </div>
</section>

<section class="page-content">
    <div class="container">

        <?php if (!empty($slots_by_day)): ?>
            <div class="section-head">
                <span class="eyebrow">Orari della settimana</span>
                <h2>Quando si balla</h2>
                <p class="lead">Le lezioni si tengono dal lunedì al giovedì. Nel weekend la pista passa alle serate caraibiche e al ballo liscio della domenica.</p>
            </div>

            <div class="timetable">
                <?php foreach ($slots_by_day as $day => $slots): ?>
                    <div class="timetable__day <?php echo $day === $todayIdx ? 'is-today' : ''; ?>">
                        <div class="timetable__day-head">
                            <span class="timetable__day-name"><?php echo e(getItalianDayName($day)); ?></span>
                            <?php if ($day === $todayIdx): ?><span class="today-tag">Oggi</span><?php endif; ?>
                        </div>
                        <div class="timetable__list">
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
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($grouped)): ?>
            <?php foreach ($grouped as $group): ?>
                <div class="course-group">
                    <div class="course-group__head">
                        <h2><?php echo e($group['label']); ?></h2>
                        <span><?php echo count($group['items']); ?> <?php echo count($group['items']) === 1 ? 'corso' : 'corsi'; ?></span>
                    </div>
                    <div class="course-grid">
                        <?php foreach ($group['items'] as $d): ?>
                            <?php $slot_text = disciplineScheduleText($pdo, (int)$d['id']); ?>
                            <article class="course-card">
                                <h3><?php echo e($d['name']); ?></h3>
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
            <?php endforeach; ?>

            <?php if ($soci_note): ?>
                <div class="note mt-40">
                    <i class="fas fa-id-card"></i>
                    <p><?php echo e($soci_note); ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="lead">I corsi saranno pubblicati a breve. Contattaci per informazioni.</p>
        <?php endif; ?>

    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2>Vieni a ballare con noi</h2>
        <p>Scegli il corso che ti interessa e scrivici: ti spieghiamo come funziona la tessera e come partecipare.</p>
        <div class="btn-row">
            <a href="contatti.php?type=iscrizione" class="btn btn--primary">Richiedi informazioni</a>
            <a href="serate.php" class="btn btn--secondary">Scopri le serate</a>
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
