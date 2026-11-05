<?php
require_once 'config.php';
require_once 'functions.php';

$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_desc       = getSetting($pdo, 'site_description', '');
$site_logo       = getSetting($pdo, 'site_logo', '');
$contatti_title  = getSetting($pdo, 'contatti_title', 'Parliamone');
$contatti_text   = getSetting($pdo, 'contatti_text', '');
$phone           = getSetting($pdo, 'phone', '');
$phone2          = getSetting($pdo, 'phone2', '');
$whatsapp        = getSetting($pdo, 'whatsapp', '');
$address         = getSetting($pdo, 'address', '');
$address2        = getSetting($pdo, 'address2', '');
$contact_email   = getSetting($pdo, 'contact_email', '');
$facebook        = getSetting($pdo, 'facebook', '');
$instagram_serate = getSetting($pdo, 'instagram_serate', '');
$instagram_corsi  = getSetting($pdo, 'instagram_corsi', '');
$tiktok          = getSetting($pdo, 'tiktok', '');
$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');

$wa_link = $whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';

// Stato di oggi: corsi dal nuovo palinsesto + eventuale serata del weekend
$todayIdx    = getTodayIndex();
$todaySlots  = getCourseSlotsByDay($pdo, true)[$todayIdx] ?? [];
$todayRow    = getScheduleByDay($pdo, $todayIdx);
$todayEvening = $todayRow && !empty($todayRow['is_active']) && trim((string)($todayRow['title'] ?? '')) !== '';
$today_lines = [];
foreach ($todaySlots as $slot) {
    $line = substr((string)$slot['start_time'], 0, 5) . ' · ' . $slot['title'];
    if (!empty($slot['venue'])) $line .= ' · ' . $slot['venue'];
    $today_lines[] = $line;
}
if ($todayEvening) {
    $line = formatScheduleRow($todayRow);
    if ($line !== '') $today_lines[] = $line;
}
$todayActive = !empty($today_lines);

// Tipo pre-selezionato (?type=tessera / iscrizione / info)
$req_types   = getRequestTypes();
$sel_type    = isset($_GET['type']) && isset($req_types[$_GET['type']]) ? $_GET['type'] : 'tessera';
$success_msg = null;
$error_msg   = null;

// ============================================================
// RICHIESTA DAL SITO (tessera, iscrizione, info)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {

    // Honeypot anti-bot: il campo deve restare vuoto
    if (!empty($_POST['website_hp'])) {
        $error_msg = 'Invio non riuscito. Riprova più tardi.';
    } else {
        $type    = isset($req_types[$_POST['type'] ?? '']) ? $_POST['type'] : 'info';
        $name    = sanitize($_POST['name'] ?? '');
        $phone_v = sanitize($_POST['phone'] ?? '');
        $email_v = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $message = trim($_POST['message'] ?? '');
        if (strlen($message) > 2000) $message = substr($message, 0, 2000);

        if ($name === '') {
            $error_msg = 'Inserisci il tuo nome.';
        } elseif ($phone_v === '' && !filter_var($email_v, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Indicaci un telefono o un\'email per poterti ricontattare.';
        } elseif (addRequest($pdo, $type, $name, $phone_v, $email_v, $message)) {
            $success_msg = $type === 'tessera'
                ? 'Grazie ' . $name . '! La richiesta di tessera è registrata: ti ricontattiamo per confermarti il tesseramento.'
                : 'Grazie ' . $name . '! La tua richiesta è registrata: ti ricontattiamo al più presto.';
            $sel_type = $type;
        } else {
            $error_msg = 'Non è stato possibile inviare la richiesta. Riprova o chiamaci.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Contatti — <?php echo e($site_title); ?></title>
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
        <span class="eyebrow">Contatti</span>
        <h1><?php echo e($contatti_title); ?></h1>
        <p><?php echo e($contatti_text); ?></p>
    </div>
</section>

<section class="page-content">
    <div class="container">
        <div class="contact-layout">

            <!-- Colonna contatti -->
            <div>
                <div class="contact-panel">

                    <?php if ($phone): ?>
                        <a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone)); ?>" class="contact-row">
                            <span class="contact-row__icon"><i class="fas fa-phone"></i></span>
                            <span>
                                <span class="contact-row__label">Telefono · Info</span>
                                <span class="contact-row__value"><?php echo e($phone); ?></span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($phone2): ?>
                        <a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone2)); ?>" class="contact-row">
                            <span class="contact-row__icon"><i class="fas fa-phone"></i></span>
                            <span>
                                <span class="contact-row__label">Seconda linea</span>
                                <span class="contact-row__value"><?php echo e($phone2); ?></span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($contact_email): ?>
                        <a href="mailto:<?php echo e($contact_email); ?>" class="contact-row">
                            <span class="contact-row__icon"><i class="fas fa-envelope"></i></span>
                            <span>
                                <span class="contact-row__label">Email</span>
                                <span class="contact-row__value"><?php echo e($contact_email); ?></span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($wa_link): ?>
                        <a href="<?php echo e($wa_link); ?>" target="_blank" rel="noopener" class="contact-row">
                            <span class="contact-row__icon"><i class="fab fa-whatsapp"></i></span>
                            <span>
                                <span class="contact-row__label">WhatsApp</span>
                                <span class="contact-row__value">Scrivici ora</span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <div class="contact-row contact-row--today">
                        <span class="contact-row__icon"><i class="fas fa-calendar-day"></i></span>
                        <span>
                            <span class="contact-row__label">Oggi · <?php echo e(getItalianDayName(getTodayIndex())); ?></span>
                            <span class="contact-row__value">
                                <?php if ($todayActive): ?>
                                    <?php echo implode('<br>', array_map('e', $today_lines)); ?>
                                <?php else: ?>
                                    Nessuna pista programmata
                                    <small>Scrivici per lezioni e appuntamenti fuori calendario.</small>
                                <?php endif; ?>
                            </span>
                            <?php if ($todayActive): ?>
                                <span class="status-pill open">Oggi si balla</span>
                            <?php else: ?>
                                <span class="status-pill closed">Oggi pista chiusa</span>
                            <?php endif; ?>
                        </span>
                    </div>

                </div>

                <?php if ($address || $address2): ?>
                    <div class="note mt-24">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>
                            <?php if ($address): ?><strong>Sede principale:</strong> <?php echo e($address); ?><br><?php endif; ?>
                            <?php if ($address2): ?><strong>Seconda sede (corsi):</strong> <?php echo e($address2); ?><br><?php endif; ?>
                            <strong>Ingresso riservato ai soci:</strong> <a href="soci.php">richiedi la tessera</a>.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="note mt-16">
                    <i class="fas fa-building"></i>
                    <p><strong>Dance Club SSD a r.l.</strong> — Via per Ca' de Bolli 11, 26817 San Martino in Strada (LO) · P. IVA / C.F. 14300410967</p>
                </div>

                <?php if ($facebook || $instagram_serate || $instagram_corsi || $tiktok): ?>
                    <div class="mt-32">
                        <h3 style="font-size:1.35rem;">Seguici</h3>
                        <p class="small">Due profili Instagram: uno per le serate, uno per i corsi.</p>
                        <div class="social-links social-links--labeled">
                            <?php if ($facebook): ?><a href="<?php echo e(normalizeUrl($facebook)); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                            <?php if ($instagram_serate): ?><a href="<?php echo e(normalizeUrl($instagram_serate)); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>Serate</span></a><?php endif; ?>
                            <?php if ($instagram_corsi): ?><a href="<?php echo e(normalizeUrl($instagram_corsi)); ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i><span>Corsi</span></a><?php endif; ?>
                            <?php if ($tiktok): ?><a href="<?php echo e(normalizeUrl($tiktok)); ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fab fa-tiktok"></i></a><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Colonna modulo -->
            <div class="request-box" id="richiedi">
                <h3>Scrivici</h3>
                <p class="sub">Tessera soci, iscrizione a un corso o informazioni: un unico modulo, rispondiamo noi.</p>

                <?php if ($success_msg): ?>
                    <div class="success-msg"><?php echo e($success_msg); ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="error-msg"><?php echo e($error_msg); ?></div>
                <?php endif; ?>

                <form method="POST" class="request-form" action="contatti.php#richiedi">
                    <input type="hidden" name="action" value="request">

                    <!-- Honeypot -->
                    <div class="hp">
                        <label>Website</label>
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label for="req-type">Motivo della richiesta</label>
                            <select name="type" id="req-type">
                                <?php foreach ($req_types as $key => $label): ?>
                                    <option value="<?php echo e($key); ?>" <?php echo $sel_type === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-row">
                            <div class="field">
                                <label for="req-name">Nome e cognome *</label>
                                <input type="text" name="name" id="req-name" required maxlength="80" placeholder="Il tuo nome">
                            </div>
                            <div class="field">
                                <label for="req-phone">Telefono</label>
                                <input type="tel" name="phone" id="req-phone" maxlength="30" placeholder="+39 …">
                            </div>
                        </div>

                        <div class="field">
                            <label for="req-email">Email</label>
                            <input type="email" name="email" id="req-email" maxlength="80" placeholder="nome@email.it">
                        </div>

                        <div class="field">
                            <label for="req-message">Messaggio</label>
                            <textarea name="message" id="req-message" rows="4" maxlength="2000" placeholder="Raccontaci cosa ti serve: corso di interesse, richiesta tessera, informazioni…"></textarea>
                        </div>

                        <button type="submit" class="btn btn--primary">Invia la richiesta</button>
                        <p class="form-note">
                            I dati sono usati solo per rispondere alla tua richiesta (vedi <a href="privacy.php">Privacy Policy</a>).
                            Per risposte immediate: <?php if ($phone): ?><a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone)); ?>"><?php echo e($phone); ?></a><?php endif; ?>
                        </p>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/cookie-consent.js"></script>

</body>
</html>
