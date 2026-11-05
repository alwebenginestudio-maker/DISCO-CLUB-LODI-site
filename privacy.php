<?php
require_once 'config.php';
require_once 'functions.php';

$site_title      = getSetting($pdo, 'site_title', 'Dance Club Lodi');
$site_logo       = getSetting($pdo, 'site_logo', '');
$primary_color   = getSetting($pdo, 'primary_color', '#6D4AA8');
$secondary_color = getSetting($pdo, 'secondary_color', '#1C1626');
$accent_color    = getSetting($pdo, 'accent_color', '#7E5BB3');
$bg_color        = getSetting($pdo, 'bg_color', '#FAF7FE');
$text_color      = getSetting($pdo, 'text_color', '#241E2E');
$footer_bg       = getSetting($pdo, 'footer_bg', '#1C1626');
$contact_email   = getSetting($pdo, 'contact_email', '');
$phone           = getSetting($pdo, 'phone', '');
$address         = getSetting($pdo, 'address', '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Privacy Policy — <?php echo e($site_title); ?></title>
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
        <span class="eyebrow">Informativa</span>
        <h1>Privacy Policy</h1>
        <p>Tutela dei tuoi dati personali.</p>
    </div>
</section>

<section class="page-content">
    <div class="container-narrow">
        <div class="content-box">
            <p><strong>Data di entrata in vigore:</strong> 1 gennaio 2026</p>
            <p>Questa informativa descrive come vengono raccolti, utilizzati e protetti i dati personali degli utenti che visitano il presente sito web o ci contattano compilando il modulo online.</p>

            <h2>Titolare del trattamento</h2>
            <p>
                <strong>Dance Club Società Sportiva Dilettantistica a r.l. — Dance Club Lodi</strong><br>
                <?php echo e($address); ?><br>
                Email: <?php echo e($contact_email); ?><br>
                Telefono: <?php echo e($phone); ?>
            </p>

            <h2>Dati raccolti</h2>
            <ul>
                <li><strong>Dati forniti dall'utente:</strong> nome, telefono, email, motivo della richiesta e messaggio, trasmessi tramite il modulo di contatto del sito.</li>
                <li><strong>Dati di navigazione:</strong> indirizzo IP, tipo di browser e pagine visitate, trattati esclusivamente tramite cookie tecnici (e, previo consenso, cookie di profilazione).</li>
            </ul>

            <h2>Finalità del trattamento</h2>
            <ul>
                <li>Gestione delle richieste di tessera associativa, iscrizioni ai corsi e informazioni generali.</li>
                <li>Comunicazione con l'utente (risposta alla richiesta inviata).</li>
                <li>Adempimento di obblighi di legge.</li>
                <li>Miglioramento del servizio tramite statistiche aggregate, solo con consenso.</li>
            </ul>

            <h2>Base giuridica</h2>
            <p>Il trattamento si fonda sull'esecuzione di misure precontrattuali (art. 6, par. 1, lett. b GDPR) e sull'adempimento di obblighi di legge (lett. c); i cookie non tecnici sono trattati sulla base del consenso (lett. a).</p>

            <h2>Conservazione dei dati</h2>
            <p>I dati delle richieste vengono conservati per il tempo strettamente necessario a gestire la richiesta e, in ogni caso, non oltre 24 mesi, salvo obblighi di legge.</p>

            <h2>Diritti dell'utente</h2>
            <p>L'utente ha diritto di accesso, rettifica, cancellazione, limitazione, portabilità e opposizione al trattamento. Può esercitare i propri diritti scrivendo a <strong><?php echo e($contact_email); ?></strong>.</p>

            <h2>Cookie</h2>
            <p>Il sito utilizza cookie tecnici necessari al suo funzionamento. Eventuali cookie di profilazione vengono attivati solo dopo aver dato il consenso tramite il banner in basso, che può essere revocato in qualsiasi momento cancellando i cookie dal browser.</p>

            <h2>Reclami</h2>
            <p>L'utente ha diritto di proporre reclamo al Garante per la Protezione dei Dati Personali (www.garanteprivacy.it).</p>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/cookie-consent.js"></script>

</body>
</html>
