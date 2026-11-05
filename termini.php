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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Termini e Condizioni — <?php echo e($site_title); ?></title>
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
        <span class="eyebrow">Regolamento</span>
        <h1>Termini e Condizioni</h1>
        <p>Le regole per frequentare corsi e serate.</p>
    </div>
</section>

<section class="page-content">
    <div class="container-narrow">
        <div class="content-box">
            <p><strong>Ultimo aggiornamento:</strong> 1 gennaio 2026</p>
            <p>I presenti termini regolano l'uso del sito web e l'accesso ai servizi offerti da <strong>Dance Club Società Sportiva Dilettantistica a r.l.</strong>, titolare del marchio Dance Club Lodi.</p>

            <h2>1. Accesso ai servizi</h2>
            <ul>
                <li>L'accesso alle serate e la partecipazione ai corsi è riservato esclusivamente ai <strong>soci provvisti di tessera associativa in corso di validità</strong>.</li>
                <li>Le condizioni di frequenza (iscrizioni, prove, numero di lezioni) sono comunicate dallo staff al momento della richiesta.</li>
                <li>Le informazioni pubblicate sul sito (orari, palinsesto, sedi) sono indicative e possono subire variazioni: fa fede la comunicazione ufficiale dello staff.</li>
            </ul>

            <h2>2. Tessera associativa e accesso</h2>
            <ul>
                <li>La richiesta di tessera inviata tramite il sito è una <strong>richiesta gratuita</strong>: il tesseramento si intende completato dopo la conferma dello staff.</li>
                <li>Senza tessera in corso di validità non è consentito l'accesso a serate, eventi danzanti e corsi.</li>
                <li>Per modifiche o annullamenti basta scrivere o telefonare con ragionevole preavviso.</li>
            </ul>

            <h2>3. Iscrizioni ai corsi</h2>
            <ul>
                <li>L'iscrizione si perfeziona con il pagamento della quota e il tesseramento, secondo le modalità comunicate dallo staff.</li>
                <li>Le lezioni non frequentate non sono rimborsabili, salvo quanto previsto dallo statuto e dal regolamento della società.</li>
                <li>I docenti possono essere sostituiti con altro membro del corpo docente in caso di impedimento.</li>
            </ul>

            <h2>4. Condotta in struttura</h2>
            <ul>
                <li>È richiesto il rispetto di staff, altri soci e della struttura ospitante.</li>
                <li>Comportamenti pericolosi o offensivi possono comportare la sospensione dell'accesso alle serate e ai corsi.</li>
                <li>Nelle strutture sportive si applicano i regolamenti interni e il codice di condotta della società.</li>
            </ul>

            <h2>5. Proprietà intellettuale e immagini</h2>
            <p>Testi, marchi, logo e materiali presenti sul sito sono tutelati. È vietata la riproduzione non autorizzata. Le fotografie scattate durante serate e corsi possono essere pubblicate sui canali della società; chi non desidera essere ripreso può segnalarlo scrivendo a <?php echo e($contact_email); ?>.</p>

            <h2>6. Responsabilità</h2>
            <p>La società non risponde di oggetti lasciati senza custodia né di danni derivanti da un uso non conforme delle strutture. La partecipazione ad attività fisiche avviene nella piena consapevolezza dello stato di salute del partecipante.</p>

            <h2>7. Contatti e legge applicabile</h2>
            <p>
                Per qualsiasi segnalazione: <strong><?php echo e($contact_email); ?></strong><?php echo $phone ? ' — ' . e($phone) : ''; ?>.<br>
                Ai presenti termini si applica la legge italiana. Per ogni controversia è competente il foro di Lodi.
            </p>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="assets/js/cookie-consent.js"></script>

</body>
</html>
