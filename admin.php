<?php
/**
 * admin.php - Pannello di controllo (Dance Club Lodi)
 * Struttura: 01 Sito · 02 Testi · 03 Corsi · 04 Eventi/serate · 05 Richieste · 06 Sistema
 * Le funzioni tecniche stanno in admin-helpers.php e functions.php:
 * qui ci sono solo le richieste POST e la pagina.
 */
if (!defined('DB_PATH')) require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin-helpers.php';

/* ---------- Logout ---------- */
if (isset($_GET['logout'])) {
    if (isAdminLoggedIn()) logAdminAction('Logout');
    session_destroy();
    header('Location: admin.php?logout=done');
    exit;
}

/* ---------- Login ---------- */
$login_error = '';
if (!isAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!csrf_ok()) {
        $login_error = 'Sessione scaduta: ricarica la pagina e riprova.';
    } elseif (loginAdmin($pdo, (string)($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Password non valida.';
    }
}
$isAdmin = isAdminLoggedIn();

/* ---------- Chiavi settings esposte nel pannello ---------- */
$keys = [
    // 01 Sito
    'site_title','site_description','site_logo','hero_image','hero_title','hero_text',
    'membership_url',
    'primary_color','secondary_color','accent_color','bg_color','text_color','footer_bg',
    // 02 Testi & contatti
    'home_text','soci_note','chi_siamo_title','chi_siamo_text',
    'corsi_title','corsi_text','serate_title','serate_text','contatti_title','contatti_text',
    'staff_title','staff_intro','staff_list','footer_desc',
    'contact_email','phone','phone2','whatsapp','address','address2','website',
    'facebook','instagram_serate','instagram_corsi','tiktok','footer_text','founding_year',
    // 04 Eventi
    'countdown_enabled','countdown_day','countdown_time','countdown_label',
];
$color_keys = ['primary_color','secondary_color','accent_color','bg_color','text_color','footer_bg'];
$cur = [];
foreach ($keys as $k) $cur[$k] = getSetting($pdo, $k, '');

/* ---------- Gestione POST ---------- */
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && csrf_ok() && !isset($_POST['login'])) {

    /* --- Area sviluppatore: sblocco e uscita --- */
    if (isset($_POST['unlock_dev_area'])) {
        if (devAreaUnlock((string)($_POST['dev_unlock_password'] ?? ''))) {
            $success_msg = '✅ Area sviluppatore sbloccata.';
        } else {
            $error_msg = '❌ Password sviluppatore errata.';
        }
    }
    if (isset($_POST['dev_logout'])) {
        devAreaLock();
        $success_msg = "Uscito dall'area sviluppatore.";
    }

    /* --- Cambio password (solo area sviluppatore sbloccata) --- */
    if (isset($_POST['change_passwords']) && devAreaUnlocked()) {
        $changed = [];
        foreach (['admin_password' => 'admin', 'developer_password' => 'sviluppatore'] as $field => $label) {
            $new = (string)($_POST['new_' . ($field === 'admin_password' ? 'admin' : 'dev') . '_password'] ?? '');
            if ($new === '') continue;
            if (strlen($new) < 8) { $error_msg = 'La password ' . $label . ' deve avere almeno 8 caratteri.'; continue; }
            updateSetting($pdo, $field, password_hash($new, PASSWORD_DEFAULT));
            $changed[] = $label;
        }
        if ($changed) {
            logAdminAction('Cambio password', implode(', ', $changed));
            $success_msg = 'Password ' . implode(' e ', $changed) . ' aggiornata/e.';
            if (in_array('sviluppatore', $changed, true)) devAreaLock();
        } elseif (!$error_msg) {
            $error_msg = 'Nessuna nuova password inserita.';
        }
    }

    /* --- Strumenti GDPR (solo area sviluppatore sbloccata) --- */
    if (isset($_POST['anonymize_requests']) && devAreaUnlocked()) {
        anonymizeRequests($pdo);
        logAdminAction('Richieste anonimizzate (GDPR)');
        $success_msg = 'Dati personali delle richieste anonimizzati.';
    }

    /* --- Log (solo area sviluppatore sbloccata) --- */
    if (($_POST['action'] ?? '') === 'clear_logs') {
        if (!devAreaUnlocked()) {
            $error_msg = 'Funzione riservata allo sviluppatore: sblocca l\'area sviluppatore.';
        } else {
            clearAdminLogs();
            $success_msg = 'Log azioni svuotati.';
        }
    }

    /* --- Impostazioni (gruppi 01-02) --- */
    if (isset($_POST['save_settings'])) {
        $saved = 0;
        foreach (($_POST['s'] ?? []) as $k => $v) {
            if (!in_array($k, $keys, true)) continue;
            $value = trim((string)$v);
            // I colori devono essere del tipo #6D4AA8: altrimenti restano invariati
            if (in_array($k, $color_keys, true) && !isHexColor($value)) continue;
            updateSetting($pdo, $k, $value);
            $cur[$k] = $value;
            $saved++;
        }

        // Immagini: la nuova sostituisce la vecchia, che viene cancellata dal disco
        $img_cfg = [
            'site_logo'  => ['prefix' => 'logo', 'max' => 1024],
            'hero_image' => ['prefix' => 'hero', 'max' => 3072],
        ];
        foreach ($img_cfg as $field => $cfg) {
            if (!empty($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $up = uploadImageSetting($_FILES[$field], $cfg['max'], $cfg['prefix']);
                if ($up['ok']) {
                    deleteImageSetting($cur[$field] ?? '');
                    updateSetting($pdo, $field, $up['path']);
                    $cur[$field] = $up['path'];
                    $saved++;
                } elseif ($up['error'] !== '') {
                    $error_msg = $up['error'];
                }
            }
        }

        logAdminAction('Impostazioni aggiornate', "$saved campi");
        $success_msg = $error_msg ? '' : "Impostazioni salvate ($saved campi aggiornati).";
    }

    /* --- Rimozione immagini (logo, hero) --- */
    foreach (['site_logo', 'hero_image'] as $field) {
        if (!empty($_POST['remove_' . $field]) && !empty($cur[$field])) {
            deleteImageSetting($cur[$field]);
            updateSetting($pdo, $field, '');
            $cur[$field] = '';
            logAdminAction('Immagine rimossa', $field);
            $success_msg = 'Immagine rimossa.';
        }
    }

    /* --- Cambio password admin (quella sviluppatore è nell'area riservata) --- */
    if (isset($_POST['change_password'])) {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $conf    = (string)($_POST['confirm_password'] ?? '');
        $stored  = getSetting($pdo, 'admin_password', '');
        if (!$stored || !password_verify($current, $stored)) {
            $error_msg = 'Password attuale errata.';
        } elseif (strlen($new) < 8) {
            $error_msg = 'La nuova password deve avere almeno 8 caratteri.';
        } elseif ($new !== $conf) {
            $error_msg = 'Le due password non coincidono.';
        } else {
            updateSetting($pdo, 'admin_password', password_hash($new, PASSWORD_DEFAULT));
            logAdminAction('Cambio password admin');
            $success_msg = 'Password admin aggiornata con successo.';
        }
    }

    /* --- Corsi --- */
    if (($_POST['action'] ?? '') === 'add_discipline') {
        addDiscipline($pdo, sanitize($_POST['name'] ?? ''), $_POST['category'] ?? 'caraibiche',
            trim($_POST['description'] ?? ''), sanitize($_POST['teacher'] ?? ''),
            sanitize($_POST['icon'] ?? 'fas fa-music'), isset($_POST['featured']));
        logAdminAction('Corso aggiunto', $_POST['name'] ?? '');
        $success_msg = 'Corso aggiunto.';
    }
    if (($_POST['action'] ?? '') === 'update_discipline') {
        updateDiscipline($pdo, (int)$_POST['id'], sanitize($_POST['name'] ?? ''), $_POST['category'] ?? 'caraibiche',
            trim($_POST['description'] ?? ''), sanitize($_POST['teacher'] ?? ''),
            sanitize($_POST['icon'] ?? 'fas fa-music'), isset($_POST['featured']));
        $success_msg = 'Corso aggiornato.';
    }
    if (($_POST['action'] ?? '') === 'delete_discipline') {
        deleteDiscipline($pdo, (int)$_POST['id']);
        logAdminAction('Corso eliminato', '#' . $_POST['id']);
        $success_msg = 'Corso eliminato.';
    }
    if (($_POST['action'] ?? '') === 'move_discipline') {
        moveDiscipline($pdo, (int)$_POST['id'], ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down');
        $success_msg = 'Ordine corsi aggiornato.';
    }

    /* --- Orari dei corsi (più appuntamenti per giorno) --- */
    if (($_POST['action'] ?? '') === 'add_course_slot') {
        $day       = (int)($_POST['day_index'] ?? 0);
        $start     = trim((string)($_POST['start_time'] ?? ''));
        $end       = trim((string)($_POST['end_time'] ?? ''));
        $title     = trim((string)($_POST['title'] ?? ''));
        $level     = trim((string)($_POST['level'] ?? ''));
        $venue     = trim((string)($_POST['venue'] ?? ''));
        $teacher   = trim((string)($_POST['teacher'] ?? ''));
        $discipline = (int)($_POST['discipline_id'] ?? 0);
        $note      = trim((string)($_POST['note'] ?? ''));
        $active    = ($_POST['is_active'] ?? '1') === '1';

        if ($day < 0 || $day > 6 || !preg_match('/^\d{2}:\d{2}$/', $start) || $title === '') {
            $error_msg = 'Inserisci giorno, ora di inizio e nome del corso.';
        } else {
            addCourseSlot($pdo, $day, $start, $end, $title, $level, $venue, $teacher, $discipline, $note, $active);
            logAdminAction('Orario corso aggiunto', $title);
            $success_msg = 'Orario aggiunto.';
        }
    }
    if (($_POST['action'] ?? '') === 'delete_course_slot') {
        deleteCourseSlot($pdo, (int)($_POST['id'] ?? 0));
        logAdminAction('Orario corso eliminato', '#' . ($_POST['id'] ?? ''));
        $success_msg = 'Orario eliminato.';
    }

    /* --- Locandine della settimana --- */
    if (($_POST['action'] ?? '') === 'add_locandine') {
        $n = saveWeeklyPosters($_FILES['locandine'] ?? []);
        logAdminAction('Locandine caricate', (string)$n);
        $success_msg = $n
            ? "$n locandina/e caricate: sono già online nella pagina Serate."
            : 'Nessuna immagine valida (JPG, PNG, WebP o GIF, max 8 MB ciascuna).';
    }
    if (($_POST['action'] ?? '') === 'delete_locandina') {
        $file = basename((string)($_POST['file'] ?? ''));
        $ok = deleteWeeklyPoster($file);
        logAdminAction('Locandina eliminata', $file);
        $success_msg = $ok ? 'Locandina eliminata.' : 'File non trovato o non valido.';
    }
    if (($_POST['action'] ?? '') === 'clear_locandine') {
        $n = clearWeeklyPosters();
        logAdminAction('Locandine svuotate', (string)$n);
        $success_msg = 'Locandine rimosse: ora il sito mostra il placeholder della settimana.';
    }

    /* --- Palinsesto --- */
    if (($_POST['action'] ?? '') === 'save_schedule') {
        $data = [];
        foreach (($_POST['schedule'] ?? []) as $idx => $row) {
            $data[(int)$idx] = [
                'is_active'  => !empty($row['is_active']) ? 1 : 0,
                'title'      => trim($row['title'] ?? ''),
                'start_time' => trim($row['start_time'] ?? ''),
                'note'       => trim($row['note'] ?? ''),
            ];
        }
        updateWeeklySchedule($pdo, $data);
        logAdminAction('Palinsesto aggiornato');
        $success_msg = 'Palinsesto salvato.';
    }

    /* --- Richieste --- */
    if (($_POST['action'] ?? '') === 'update_request') {
        $ok = updateRequestStatus($pdo, (int)$_POST['id'], $_POST['status'] ?? 'nuovo');
        $success_msg = $ok ? 'Stato richiesta aggiornato.' : 'Stato non valido.';
    }
    if (($_POST['action'] ?? '') === 'delete_request') {
        deleteRequest($pdo, (int)$_POST['id']);
        $success_msg = 'Richiesta eliminata.';
    }
    if (($_POST['action'] ?? '') === 'clear_requests') {
        clearRequests($pdo);
        logAdminAction('Archivio richieste svuotato');
        $success_msg = 'Archivio richieste svuotato.';
    }
}

/* ---------- Dati per la vista ---------- */
$devUnlocked   = devAreaUnlocked();
$stats = $requests = $schedule = $courses = $posters = $course_slots = [];
if ($isAdmin) {
    $stats = [
        'new_requests' => countNewRequests($pdo),
        'requests'     => countRequests($pdo),
        'courses'      => countDisciplines($pdo),
        'posters'      => countWeeklyPosters(),
    ];
    $courses      = getDisciplines($pdo);
    $posters      = getWeeklyPosters(0);
    $schedule     = getWeeklySchedule($pdo);
    $course_slots = getCourseSlots($pdo, false);

    $reqFilter = isset($_GET['rfilter']) && in_array($_GET['rfilter'], ['','nuovo','letto','gestito'], true) ? $_GET['rfilter'] : '';
    $requests  = getRequests($pdo, $reqFilter, 300);
}

$cats = getCourseCategories();
$iconList = ['fas fa-music','fas fa-heart','fas fa-umbrella-beach','fas fa-record-vinyl','fas fa-star',
             'fas fa-fire','fas fa-people-group','fas fa-hat-cowboy','fas fa-user','fas fa-drum','fas fa-bolt','fas fa-guitar'];
$typeLabels = getRequestTypes();
$statusMap  = ['nuovo' => 'new', 'letto' => 'read', 'gestito' => 'done'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin - AL WebEngine Lite Panel</title>
    <?php if ($isAdmin) echo renderFavicon($cur['site_logo']); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #1A1A1A; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #e0e0e0; }

        /* ===== HEADER ===== */
        .admin-header { background: linear-gradient(135deg, #1A1A1A 0%, #2A2A2A 100%); border-bottom: 4px solid #D4AF37; padding: 20px 0; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
        .admin-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .admin-header .brand { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .admin-header .brand .logo-icon { font-size: 32px; color: #D4AF37; }
        .admin-header .brand .logo-text h1 { color: #FFFFFF; font-size: 24px; font-weight: 300; letter-spacing: 1px; }
        .admin-header .brand .logo-text h1 span { color: #D4AF37; font-weight: 700; }
        .admin-header .brand .logo-text .subtitle { color: #888; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; border-left: 2px solid #D4AF37; padding-left: 12px; margin-top: 2px; }
        .admin-header .brand .logo-image img { height: 60px; width: auto; display: block; border-radius: 4px; }
        .admin-header .user-menu { display: flex; align-items: center; gap: 20px; }
        .admin-header .user-menu a, .admin-header .user-menu span { color: #D4AF37; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .admin-header .user-menu a:hover { color: #FFFFFF; }

        .admin-body { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        /* ===== LOGIN ===== */
        .login-box { max-width: 420px; margin: 80px auto; background: #2A2A2A; padding: 40px; border-radius: 12px; border: 1px solid #D4AF37; box-shadow: 0 8px 40px rgba(212,175,55,0.15); }
        .login-box .login-logo { text-align: center; margin-bottom: 20px; }
        .login-box .login-logo img { height: 80px; width: auto; }
        .login-box h2 { color: #D4AF37; text-align: center; margin-bottom: 10px; }
        .login-box .login-sub { color: #888; text-align: center; font-size: 13px; margin-bottom: 30px; }
        .login-box .form-group { margin-bottom: 20px; }
        .login-box .form-group label { color: #ccc; display: block; margin-bottom: 6px; font-size: 14px; }
        .login-box .form-group input { width: 100%; padding: 12px 16px; background: #1A1A1A; border: 1px solid #444; border-radius: 6px; color: #FFFFFF; font-size: 16px; }
        .login-box .form-group input:focus { outline: none; border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.2); }
        .login-box .btn-login { width: 100%; padding: 14px; background: #D4AF37; color: #1A1A1A; border: none; border-radius: 6px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s; }
        .login-box .btn-login:hover { opacity: 0.85; transform: translateY(-2px); }
        .btn-back-site { display: inline-block; background: transparent; color: #D4AF37; border: 1px solid #D4AF37; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease; width: 100%; text-align: center; box-sizing: border-box; }
        .btn-back-site:hover { background: #D4AF37; color: #1A1A1A; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(212,175,55,0.3); }

        /* ===== DASHBOARD ===== */
        .dashboard { background: #2A2A2A; border-radius: 12px; border: 1px solid #D4AF37; padding: 35px; box-shadow: 0 8px 40px rgba(0,0,0,0.3); }
        .dashboard-header { display: flex; align-items: center; gap: 15px; border-bottom: 2px solid #D4AF37; padding-bottom: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .dashboard-header h2 { color: #D4AF37; font-size: 26px; font-weight: 300; margin: 0; }
        .dashboard-header i { color: #D4AF37; font-size: 28px; }
        .dashboard-header .spacer { flex: 1; }
        .dashboard-header .mini-stat { background: #1A1A1A; border: 1px solid #444; border-radius: 8px; padding: 8px 14px; text-align: center; }
        .dashboard-header .mini-stat b { display: block; color: #D4AF37; font-size: 20px; }
        .dashboard-header .mini-stat span { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        .dashboard-header .btn-header-site { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border: 1px solid #D4AF37; border-radius: 6px; color: #D4AF37; text-decoration: none; font-size: 13px; font-weight: 600; transition: all .3s ease; }
        .dashboard-header .btn-header-site:hover { background: #D4AF37; color: #1A1A1A; }

        /* ===== MESSAGGI ===== */
        .success-msg { background: rgba(46,204,113,0.15); border: 1px solid #2ecc71; color: #2ecc71; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; }
        .error-msg { background: rgba(255,68,68,0.15); border: 1px solid #ff4444; color: #ff6b6b; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; }

        /* ===== LAYOUT A DUE COLONNE + SEZIONI ===== */
        .admin-layout { display: grid; grid-template-columns: 248px minmax(0, 1fr); gap: 24px; align-items: start; }
        .admin-side { position: sticky; top: 16px; }
        .side-card { background: #1E1E1E; border: 1px solid #333; border-radius: 10px; padding: 16px; }
        .side-title { color: #D4AF37; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px; }
        .side-link { display: flex; align-items: center; gap: 10px; padding: 11px 10px; border-radius: 8px; color: #ccc; text-decoration: none; font-size: 14px; margin-bottom: 3px; transition: .18s; }
        .side-link:hover { background: #242424; color: #fff; }
        .side-link .n { color: #D4AF37; font-weight: 700; font-size: 12px; width: 14px; }
        .side-link.is-active { background: rgba(212,175,55,.12); color: #fff; }
        .side-help { display: block; width: 100%; margin-top: 14px; padding: 11px; background: rgba(212,175,55,.12); border: 1px solid #D4AF37; color: #D4AF37; border-radius: 8px; cursor: pointer; font-family: inherit; font-size: 13.5px; font-weight: 600; }
        .side-help:hover { background: #D4AF37; color: #1A1A1A; }
        .side-stats { margin-top: 14px; border-top: 1px solid #333; padding-top: 12px; font-size: 12.5px; color: #888; line-height: 2; }
        .side-stats b { color: #D4AF37; }

        /* ===== AZIONI RAPIDE ===== */
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); gap: 13px; margin-bottom: 24px; }
        .qa-card { display: block; background: linear-gradient(160deg, #262626, #1A1A1A); border: 1px solid #3a3a3a; border-left: 3px solid #D4AF37; border-radius: 10px; padding: 16px 18px; text-decoration: none; color: #ccc; transition: .2s; }
        .qa-card:hover { transform: translateY(-3px); border-color: #D4AF37; }
        .qa-card i { font-size: 20px; color: #D4AF37; }
        .qa-card b { display: block; color: #fff; margin: 9px 0 5px; font-size: 14.5px; }
        .qa-card span { font-size: 12.5px; color: #888; line-height: 1.55; display: block; }

        /* ===== SEZIONI (a fisarmonica) ===== */
        details.group { background: #1E1E1E; border: 1px solid #333; border-left: 4px solid #D4AF37; border-radius: 10px; margin-bottom: 16px; overflow: hidden; }
        details.group > summary { list-style: none; cursor: pointer; padding: 16px 20px; display: flex; align-items: center; gap: 13px; flex-wrap: wrap; }
        details.group > summary::-webkit-details-marker { display: none; }
        details.group > summary:hover { background: #242424; }
        details.group[open] > summary { border-bottom: 1px solid #333; }
        .group__num { color: #888; font-weight: 700; font-size: 13px; }
        .group__title { color: #D4AF37; font-size: 16px; font-weight: 600; }
        .group__hint { color: #888; font-size: 12.5px; }
        .group__chev { margin-left: auto; color: #888; transition: transform .2s; }
        details.group[open] .group__chev { transform: rotate(180deg); }
        .group-body { padding: 22px; }
        .group-tools { display: flex; gap: 10px; justify-content: flex-end; margin: 0 0 16px; flex-wrap: wrap; }
        .btn-tiny { background: #111; border: 1px solid #444; color: #ccc; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-size: 12.5px; font-family: inherit; }
        .btn-tiny:hover { border-color: #D4AF37; color: #fff; }

        /* ===== GUIDA (finestra di aiuto) ===== */
        .help-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.82); z-index: 10000; padding: 20px; overflow-y: auto; }
        .help-overlay.open { display: flex; align-items: flex-start; justify-content: center; }
        .help-box { background: #1E1E1E; border: 1px solid #D4AF37; border-radius: 12px; max-width: 780px; width: 100%; margin: auto; padding: 30px; box-shadow: 0 20px 60px rgba(0,0,0,.6); }
        .help-box h3 { color: #D4AF37; font-size: 21px; margin-bottom: 4px; }
        .help-box .help-sub { color: #888; font-size: 13.5px; margin-bottom: 18px; }
        .help-box h4 { color: #fff; font-size: 15px; margin: 24px 0 8px; padding-left: 10px; border-left: 3px solid #D4AF37; }
        .help-box p, .help-box li { color: #bbb; font-size: 13.5px; line-height: 1.8; }
        .help-box ul, .help-box ol { padding-left: 20px; margin: 8px 0; }
        .help-box b { color: #fff; }
        .help-box .help-close { float: right; background: #111; border: 1px solid #444; color: #ccc; padding: 7px 14px; border-radius: 6px; cursor: pointer; font-family: inherit; font-size: 13px; }
        .help-box .help-close:hover { border-color: #D4AF37; color: #fff; }

        /* ===== GRUPPI ===== */
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .content-group-label { color: #D4AF37; font-size: 16px; font-weight: 600; margin: 38px 0 18px 0; padding: 12px 18px; background: rgba(212,175,55,0.06); border-left: 4px solid #D4AF37; border-radius: 6px; }
        .content-group-label .num { color: #888; font-size: 13px; font-weight: 400; margin-right: 12px; }
        .section-block { background: #1E1E1E; border: 1px solid #333; border-left: 4px solid #D4AF37; border-radius: 8px; padding: 26px; margin-bottom: 22px; }
        .section-title { color: #D4AF37; font-size: 15px; font-weight: 600; margin-bottom: 14px; }
        .section-desc { color: #888; font-size: 13px; margin-bottom: 18px; line-height: 1.7; }

        /* ===== FORM ===== */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { color: #ccc; display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .form-group label .required { color: #D4AF37; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 10px 14px; background: #111; border: 1px solid #333; border-radius: 6px;
            color: #FFFFFF; font-size: 14px; transition: 0.3s; font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-group .field-hint { color: #666; font-size: 11px; margin-top: 3px; }
        .form-group input[type="color"] { height: 44px; padding: 4px; cursor: pointer; }

        .image-upload-wrapper { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .image-upload-wrapper .current-image { max-width: 150px; max-height: 70px; border-radius: 6px; border: 2px solid #333; object-fit: contain; }
        .image-upload-wrapper .image-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .image-upload-wrapper .image-actions input[type="file"] { background: #111; color: #ccc; padding: 6px 10px; border: 1px solid #333; border-radius: 4px; font-size: 12px; }
        .image-upload-wrapper .image-actions .btn-remove { background: #ff4444; color: #FFFFFF; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: 0.3s; }
        .image-upload-wrapper .image-actions .btn-remove:hover { background: #cc0000; }

        .btn-save-general { background: #D4AF37; color: #1A1A1A; padding: 16px 50px; border: none; border-radius: 8px; font-weight: 700; font-size: 18px; cursor: pointer; transition: all 0.3s ease; margin: 20px 0 10px 0; width: 100%; text-align: center; box-shadow: 0 4px 15px rgba(212,175,55,0.3); }
        .btn-save-general:hover { opacity: 0.85; transform: translateY(-2px); }

        .btn-small { background: #3498db; color: #fff; padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; transition: 0.3s; text-decoration: none; display: inline-block; font-family: inherit; }
        .btn-small:hover { background: #2980b9; transform: translateY(-2px); }
        .btn-danger-small { background: #e74c3c; color: #fff; padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; transition: 0.3s; text-decoration: none; display: inline-block; font-family: inherit; }
        .btn-danger-small:hover { background: #c0392b; transform: translateY(-2px); }
        .btn-move { background: #555; color: #fff; padding: 3px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; transition: 0.3s; text-decoration: none; display: inline-block; font-family: inherit; }
        .btn-move:hover { background: #777; }
        .btn-clear { background: #e67e22; color: #fff; padding: 6px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: 0.3s; text-decoration: none; display: inline-block; font-weight: 600; font-family: inherit; }
        .btn-clear:hover { background: #d35400; transform: translateY(-2px); }
        .btn-gold { background: #D4AF37; color: #1A1A1A; padding: 8px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 700; transition: 0.3s; display: inline-block; text-decoration: none; font-family: inherit; }
        .btn-gold:hover { opacity: 0.85; transform: translateY(-2px); }

        /* ===== TABELLE ===== */
        .admin-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .admin-table th { text-align: left; color: #D4AF37; border-bottom: 2px solid #D4AF37; padding: 8px 6px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .admin-table td { padding: 10px 6px; border-bottom: 1px solid #333; vertical-align: middle; color: #ccc; }
        .admin-table tr:hover td { background: #222; }
        .admin-table input[type="text"], .admin-table input[type="time"], .admin-table input[type="date"],
        .admin-table input[type="number"], .admin-table select, .admin-table textarea {
            background: #111; border: 1px solid #333; color: #fff; padding: 6px 8px; border-radius: 4px; font-size: 13px; font-family: inherit; width: 100%;
        }
        .admin-table input:focus, .admin-table select:focus, .admin-table textarea:focus { outline: none; border-color: #D4AF37; }
        .admin-table .day-name { font-weight: 600; color: #fff; white-space: nowrap; }
        .admin-table tr.is-inactive { opacity: 0.5; }

        .pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
        .pill.new { background: rgba(212,175,55,0.18); color: #D4AF37; border: 1px solid #D4AF37; }
        .pill.read { background: rgba(52,152,219,0.15); color: #85c1e9; border: 1px solid #3498db; }
        .pill.done { background: rgba(46,204,113,0.15); color: #2ecc71; border: 1px solid #2ecc71; }
        .pill.off { background: rgba(150,150,160,0.15); color: #999; border: 1px solid #555; }

        .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .filter-bar .tab { background: #111; border: 1px solid #333; color: #ccc; padding: 7px 16px; border-radius: 20px; font-size: 13px; cursor: pointer; text-decoration: none; font-family: inherit; transition: 0.2s; }
        .filter-bar .tab.active, .filter-bar .tab:hover { background: rgba(212,175,55,0.15); border-color: #D4AF37; color: #fff; }

        .empty-state { text-align: center; padding: 40px 20px; color: #777; }
        .empty-state i { font-size: 38px; color: #444; display: block; margin-bottom: 12px; }

        /* ===== RICHIESTE ===== */
        .req-card { background: #111; border: 1px solid #333; border-radius: 8px; padding: 18px; margin-bottom: 14px; }
        .req-card.unread { border-left: 4px solid #D4AF37; }
        .req-card .req-head { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 6px; }
        .req-card .req-who { color: #fff; font-weight: 600; font-size: 15px; }
        .req-card .req-meta { color: #777; font-size: 12.5px; line-height: 1.7; }
        .req-card .req-msg { background: #1A1A1A; border-radius: 6px; padding: 12px 14px; color: #ccc; font-size: 13.5px; line-height: 1.6; white-space: pre-line; margin: 10px 0; }
        .req-card .req-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .req-card .req-actions select { background: #111; border: 1px solid #333; color: #fff; padding: 5px 8px; border-radius: 4px; font-size: 12px; font-family: inherit; }
        .req-card .req-actions select:focus { outline: none; border-color: #D4AF37; }

        /* ===== GALLERIA ===== */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
        .gallery-card { background: #111; border: 1px solid #333; border-radius: 6px; overflow: hidden; }
        .gallery-card__img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; background: #0a0a0a; }
        .gallery-card__body { padding: 10px; display: flex; flex-direction: column; gap: 8px; }
        .gallery-card__caption-input { width: 100%; padding: 6px 8px; background: #0a0a0a; border: 1px solid #333; border-radius: 4px; color: #fff; font-size: 12px; font-family: inherit; }
        .gallery-card__caption-input:focus { outline: none; border-color: #D4AF37; }
        .gallery-card__actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .gallery-card__order { color: #666; font-size: 11px; margin-left: auto; }

        /* ===== MODALE ===== */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #2A2A2A; padding: 35px; border-radius: 12px; max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto; border: 2px solid #D4AF37; box-shadow: 0 8px 40px rgba(0,0,0,0.5); }
        .modal-box h3 { color: #D4AF37; margin-bottom: 6px; font-size: 22px; font-weight: 400; }
        .modal-box .modal-desc { color: #ccc; font-size: 13px; margin-bottom: 20px; line-height: 1.6; }
        .modal-box .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .modal-box .modal-actions .btn-cancel { background: #555; color: #fff; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: inherit; }
        .modal-box .modal-actions .btn-cancel:hover { background: #666; }
        .modal-box .modal-actions .btn-confirm { background: #D4AF37; color: #1A1A1A; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-family: inherit; }
        .modal-box .modal-actions .btn-confirm:hover { opacity: 0.85; }
        .check-line { display: flex; align-items: center; gap: 8px; color: #ccc; font-size: 14px; margin-bottom: 12px; cursor: pointer; }
        .check-line input { width: 16px; height: 16px; accent-color: #D4AF37; }

        /* ===== LOG / SISTEMA ===== */
        .log-container { background: #111; border-radius: 6px; padding: 15px; max-height: 340px; overflow-y: auto; font-family: monospace; font-size: 12px; color: #9fd49f; white-space: pre-wrap; word-break: break-word; border: 1px solid #333; }
        .info-list { font-size: 13.5px; color: #aaa; line-height: 2; }
        .info-list b { color: #D4AF37; font-weight: 600; }
        .info-list .ok { color: #2ecc71; }
        .info-list .ko { color: #ff6b6b; font-weight: 700; }

        /* ===== COLORI DEL TEMA ===== */
        .color-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px 18px; }
        .color-row { display: flex; align-items: center; gap: 10px; }
        .color-row .color-picker { width: 52px !important; min-width: 52px; height: 44px; padding: 3px !important; border: 1px solid #333 !important; border-radius: 6px; background: #111; cursor: pointer; }
        .color-row .color-hex { font-family: monospace !important; text-transform: uppercase; }

        /* ===== EVENTI TIMER ===== */
        .timer-list { display: flex; flex-direction: column; gap: 10px; }
        .timer-row { display: flex; align-items: center; gap: 14px; background: #111; border: 1px solid #333; border-radius: 8px; padding: 12px 14px; }
        .timer-row.is-past { opacity: .5; }
        .timer-row__date { text-align: center; min-width: 74px; padding-right: 14px; border-right: 1px solid #333; }
        .timer-row__date b { display: block; color: #D4AF37; font-size: 15px; }
        .timer-row__date span { color: #888; font-size: 12.5px; }
        .timer-row__body { flex: 1; min-width: 0; }
        .timer-row__body b { display: block; color: #fff; font-size: 14.5px; }
        .timer-row__body small { display: block; color: #888; font-size: 12.5px; margin-top: 2px; }

        /* ===== AREA SVILUPPATORE ===== */
        .dev-area { border-left-color: #555 !important; }
        .dev-area--open { border-left-color: #2ecc71 !important; border-color: rgba(46,204,113,0.45); }
        .dev-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .dev-box { background: #111; border: 1px solid #333; border-radius: 8px; padding: 18px; }
        .dev-box__title { color: #2ecc71; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .dev-box__note { color: #777; font-size: 12.5px; line-height: 1.6; margin-bottom: 10px; }

        /* ===== TABELLE SU MOBILE ===== */
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0 -6px; }
        .table-scroll .admin-table { min-width: 600px; }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .grid-2col { grid-template-columns: 1fr; }
            .admin-header .brand { flex-direction: column; align-items: flex-start; gap: 8px; }
            .admin-header .brand .logo-text .subtitle { border-left: none; padding-left: 0; }
            .admin-header .brand .logo-image img { height: 48px; }
            .login-box .login-logo img { height: 65px; }
            .btn-save-general { font-size: 16px; padding: 14px 30px; }
            .dashboard { padding: 20px 16px; }
            .admin-table { font-size: 12px; }
            .dashboard-header { flex-wrap: wrap; }
            .dashboard-header h2 { font-size: 21px; }
            .section-block { padding: 18px 14px; }
            .color-grid { grid-template-columns: 1fr; }
            .dev-grid { grid-template-columns: 1fr; }
            .timer-row { flex-wrap: wrap; }
            .timer-row__date { min-width: auto; width: 100%; text-align: left; border-right: none; border-bottom: 1px solid #333; padding: 0 0 8px; display: flex; gap: 10px; align-items: baseline; }
            .timer-row__date b, .timer-row__date span { display: inline; }
            .filter-bar { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 6px; }
            .filter-bar .tab { white-space: nowrap; }
            .form-group input, .form-group textarea, .form-group select { font-size: 16px; }
            .modal-box { padding: 22px 16px; }
            .admin-layout { grid-template-columns: 1fr; }
            .admin-side { position: static; }
            .side-card { padding: 12px; }
            .side-link { display: inline-flex; margin: 0 4px 4px 0; padding: 9px 12px; border: 1px solid #333; }
            .side-help { width: auto; display: inline-block; margin-top: 8px; }
            .side-stats { display: none; }
            .group-body { padding: 16px 14px; }
            .group__hint { display: none; }
            details.group > summary { padding: 14px 16px; }
            .help-box { padding: 22px 16px; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ADMIN ===== -->
<div class="admin-header">
    <div class="container">
        <div class="brand">
            <i class="fas fa-cog logo-icon"></i>
            <div class="logo-text">
                <h1><span>AL</span> WebEngine <span style="font-weight:300;color:#888;">|</span> <span style="color:#D4AF37;font-weight:400;">Lite Panel</span></h1>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:2px;">
                    <span class="subtitle">Dance Club Lodi — Pannello di Controllo</span>
                </div>
            </div>
            <div class="logo-image">
                <img src="assets/img/ALwebengine-logo.jpg" alt="AL WebEngine Logo">
            </div>
        </div>
        <div class="user-menu">
            <?php if ($isAdmin): ?>
                <span style="color:#666;font-size:13px;"><i class="fas fa-user"></i> <?php echo e2($_SESSION['login_type'] ?? 'admin'); ?></span>
                <a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Sito</a>
                <a href="admin.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== BODY ===== -->
<div class="admin-body">

<?php if (!$isAdmin): ?>
    <!-- ===== LOGIN ===== -->
    <div class="login-box">
        <div class="login-logo">
            <img src="assets/img/ALwebengine-logo.jpg" alt="AL WebEngine Logo">
        </div>
        <h2><i class="fas fa-lock"></i> Accesso</h2>
        <p class="login-sub">Inserisci la password per accedere al pannello di controllo</p>
        <?php if ($login_error): ?>
            <div class="error-msg"><?php echo e2($login_error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['logout'])): ?>
            <div class="success-msg">✅ Logout effettuato con successo.</div>
        <?php endif; ?>
        <?php if (isset($_GET['msg'])): ?>
            <div class="success-msg"><?php echo e2($_GET['msg']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <div class="form-group">
                <label>Password Admin</label>
                <input type="password" name="password" placeholder="Inserisci la password" required autofocus>
            </div>
            <button type="submit" name="login" value="1" class="btn-login"><i class="fas fa-sign-in-alt"></i> Entra</button>
        </form>
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #333;">
            <a href="index.php" class="btn-back-site">
                <i class="fas fa-arrow-left"></i> Torna al sito
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- ===== DASHBOARD ===== -->
    <div class="dashboard">
        <div class="dashboard-header">
            <i class="fas fa-edit"></i>
            <h2>Gestione Contenuti</h2>
            <div class="spacer"></div>
            <div class="mini-stat"><b><?php echo (int)$stats['new_requests']; ?></b><span>Nuove richieste</span></div>
            <div class="mini-stat"><b><?php echo (int)$stats['posters']; ?></b><span>Locandine online</span></div>
            <div class="mini-stat"><b><?php echo (int)$stats['courses']; ?></b><span>Corsi</span></div>
            <a href="index.php" target="_blank" class="btn-header-site"><i class="fas fa-arrow-left"></i> Torna al sito</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="success-msg">✅ <?php echo e2($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="error-msg">❌ <?php echo e2($error_msg); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg'])): ?>
            <div class="success-msg">ℹ️ <?php echo e2($_GET['msg']); ?></div>
        <?php endif; ?>

        <div class="admin-layout">

        <!-- ===== MENÙ DELLE SEZIONI (colonna sinistra) ===== -->
        <aside class="admin-side">
            <div class="side-card">
                <div class="side-title">Sezioni</div>
                <a class="side-link" href="#sec-1"><span class="n">1</span> Il sito</a>
                <a class="side-link" href="#sec-2"><span class="n">2</span> I testi</a>
                <a class="side-link" href="#sec-3"><span class="n">3</span> I corsi</a>
                <a class="side-link" href="#sec-4"><span class="n">4</span> Eventi e locandine</a>
                <a class="side-link" href="#sec-5"><span class="n">5</span> Le richieste</a>
                <a class="side-link" href="#sec-6"><span class="n">6</span> Sistema</a>
                <button type="button" class="side-help" id="helpBtn"><i class="fas fa-question-circle"></i> Come si usa il pannello</button>
                <div class="side-stats">
                    <b><?php echo (int)$stats['new_requests']; ?></b> richieste da leggere<br>
                    <b><?php echo (int)$stats['posters']; ?></b> locandine online<br>
                    <b><?php echo (int)$stats['courses']; ?></b> corsi inseriti
                </div>
            </div>
        </aside>

        <div class="admin-main">

        <!-- ===== AZIONI RAPIDE ===== -->
        <div class="quick-actions">
            <a class="qa-card" href="#sec-4" data-open="sec-4">
                <i class="fas fa-images"></i>
                <b>Carica le locandine</b>
                <span>Le locandine della settimana da mettere in home e nella pagina Serate.</span>
            </a>
            <a class="qa-card" href="#sec-4" data-open="sec-4">
                <i class="fas fa-stopwatch"></i>
                <b>Imposta il countdown</b>
                <span>Giorno, ora e titolo della prossima serata.</span>
            </a>
            <a class="qa-card" href="#sec-1" data-open="sec-1">
                <i class="fas fa-id-card"></i>
                <b>Imposta il link tessera</b>
                <span>Il link alla domanda di tesseramento online (4gest).</span>
            </a>
            <a class="qa-card" href="#sec-5" data-open="sec-5">
                <i class="fas fa-inbox"></i>
                <b>Leggi le richieste</b>
                <span>Le richieste di tessera e iscrizione arrivate dal sito.</span>
            </a>
        </div>

        <div class="group-tools">
            <button type="button" class="btn-tiny" id="expandAll"><i class="fas fa-angle-double-down"></i> Apri tutte le sezioni</button>
            <button type="button" class="btn-tiny" id="collapseAll"><i class="fas fa-angle-double-up"></i> Chiudi tutte</button>
        </div>

<!-- ===== GRUPPO 01: IL SITO ===== -->
<details class="group" id="sec-1" open>
    <summary>
        <span class="group__num">01</span>
        <i class="fas fa-sliders-h"></i>
        <span class="group__title">Il sito</span>
        <span class="group__hint">Nome, logo, sfondo della home, link tessera, colori</span>
        <i class="fas fa-chevron-down group__chev"></i>
    </summary>
    <div class="group-body">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

    <div class="section-block">
        <div class="section-title"><i class="fas fa-info-circle"></i> Generali</div>
        <div class="form-row">
            <div class="form-group">
                <label>Titolo del sito <span class="required">*</span></label>
                <input type="text" name="s[site_title]" value="<?php echo e2($cur['site_title']); ?>" required>
            </div>
            <div class="form-group">
                <label>Anno di fondazione</label>
                <input type="text" name="s[founding_year]" value="<?php echo e2($cur['founding_year']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Meta descrizione (SEO)</label>
            <textarea name="s[site_description]" rows="2"><?php echo e2($cur['site_description']); ?></textarea>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-image"></i> Logo e foto hero</div>
        <div class="form-group">
            <label>Logo</label>
            <div class="image-upload-wrapper">
                <?php if ($cur['site_logo'] && file_exists(__DIR__ . '/' . $cur['site_logo'])): ?>
                    <img src="<?php echo e2($cur['site_logo']); ?>" class="current-image" alt="Logo">
                <?php else: ?>
                    <span style="color:#666;">Nessun logo.</span>
                <?php endif; ?>
                <div class="image-actions">
                    <input type="file" name="site_logo" accept="image/*">
                    <?php if ($cur['site_logo']): ?>
                        <button type="submit" name="remove_site_logo" value="1" class="btn-remove" onclick="return confirm('Rimuovere il logo?')"><i class="fas fa-trash"></i> Rimuovi</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="field-hint">PNG, JPG, SVG, WebP. Max 1 MB.</div>
        </div>
        <div class="form-group">
            <label>Immagine hero (sfondo home)</label>
            <div class="image-upload-wrapper">
                <?php if ($cur['hero_image'] && file_exists(__DIR__ . '/' . $cur['hero_image'])): ?>
                    <img src="<?php echo e2($cur['hero_image']); ?>" class="current-image" alt="Hero">
                <?php elseif (is_file(__DIR__ . '/assets/img/hero-default.jpg')): ?>
                    <img src="assets/img/hero-default.jpg" class="current-image" alt="Sfondo di default">
                    <span style="color:#888;font-size:12.5px;">In uso: foto di default del giardino estivo.</span>
                <?php else: ?>
                    <span style="color:#666;">Sfondo predefinito del tema.</span>
                <?php endif; ?>
                <div class="image-actions">
                    <input type="file" name="hero_image" accept="image/*">
                    <?php if ($cur['hero_image']): ?>
                        <button type="submit" name="remove_hero_image" value="1" class="btn-remove" onclick="return confirm('Eliminare questa immagine?')"><i class="fas fa-trash"></i> Rimuovi</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="field-hint">JPG, PNG, WebP. Consigliato 1920x800px. Max 3 MB. Senza immagine viene usata la foto di default.</div>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-id-card"></i> Tessera associativa</div>
        <div class="section-desc"><p>Il pulsante "Richiedi la tessera online" apre la domanda di tesseramento.</p></div>
        <div class="form-group">
            <label>URL richiesta tessera</label>
            <input type="url" name="s[membership_url]" value="<?php echo e2($cur['membership_url']); ?>" placeholder="https://4gest.4settori.net/it/domanda/danceclub.html">
            <div class="field-hint">Link esterno alla domanda online (4gest). Si apre in una nuova scheda.</div>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-palette"></i> Colori del tema</div>
        <div class="section-desc"><p>Cambia i colori del sito: si aggiornano subito, senza toccare il codice.</p></div>
        <div class="color-grid">
            <?php
            $color_fields = [
                'primary_color'   => 'Colore principale (bottoni e accenti)',
                'secondary_color' => 'Colore scuro (testi e sezioni)',
                'accent_color'    => 'Colore accento (lilla)',
                'bg_color'        => 'Colore di sfondo',
                'text_color'      => 'Colore del testo',
                'footer_bg'       => 'Colore del footer',
            ];
            foreach ($color_fields as $ckey => $clabel):
                $cval = (string)($cur[$ckey] ?? '');
                if (!isHexColor($cval)) $cval = '';
                ?>
                <div class="form-group">
                    <label><?php echo e2($clabel); ?></label>
                    <div class="color-row">
                        <input type="color" class="color-picker" value="<?php echo e2($cval ?: '#6D4AA8'); ?>" data-target="s[<?php echo e2($ckey); ?>]" aria-label="Scegli <?php echo e2($clabel); ?>">
                        <input type="text" class="color-hex" name="s[<?php echo e2($ckey); ?>]" value="<?php echo e2($cval); ?>" placeholder="#6D4AA8" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="field-hint">Scegli il colore con il quadratino oppure scrivi il codice esadecimale (es. #6D4AA8). Un colore non valido viene ignorato.</div>
        <script>
        document.querySelectorAll('.color-picker').forEach(function(pick) {
            var hex = document.querySelector('[name="' + pick.getAttribute('data-target') + '"]');
            if (!hex) return;
            pick.addEventListener('input', function() { hex.value = pick.value.toUpperCase(); });
            hex.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) pick.value = hex.value;
            });
        });
        </script>
    </div>

    <button type="submit" name="save_settings" value="1" class="btn-save-general"><i class="fas fa-save"></i> Salva impostazioni sito</button>
</form>
    </div>
</details>

<!-- ===== GRUPPO 02: I TESTI ===== -->
<details class="group" id="sec-2" open>
    <summary>
        <span class="group__num">02</span>
        <i class="fas fa-file-alt"></i>
        <span class="group__title">I testi</span>
        <span class="group__hint">Tutto quello che si legge sul sito, i contatti e i social</span>
        <i class="fas fa-chevron-down group__chev"></i>
    </summary>
    <div class="group-body">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

    <div class="section-block">
        <div class="section-title"><i class="fas fa-home"></i> Testi della home</div>
        <div class="form-group">
            <label>Titolo hero (il titolo grande in cima alla pagina)</label>
            <input type="text" name="s[hero_title]" value="<?php echo e2($cur['hero_title']); ?>">
        </div>
        <div class="form-group">
            <label>Testo hero (due righe sotto il titolo)</label>
            <textarea name="s[hero_text]" rows="2"><?php echo e2($cur['hero_text']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Nota ai soci (sotto il palinsesto)</label>
            <textarea name="s[soci_note]" rows="2"><?php echo e2($cur['soci_note']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Testo introduttivo (pagina Soci, sotto "Come si diventa socio")</label>
            <textarea name="s[home_text]" rows="4"><?php echo e2($cur['home_text']); ?></textarea>
            <div class="field-hint">Lascia il campo vuoto per non mostrare nessun testo introduttivo.</div>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-file-lines"></i> Titoli e testi delle pagine</div>
        <div class="section-desc"><p>Titoli e introduzioni delle pagine Corsi, Serate, Contatti e Storia. Se un campo resta vuoto viene usato il testo predefinito.</p></div>
        <div class="form-row">
            <div class="form-group">
                <label>Titolo pagina Corsi</label>
                <input type="text" name="s[corsi_title]" value="<?php echo e2($cur['corsi_title']); ?>">
            </div>
            <div class="form-group">
                <label>Titolo pagina Serate</label>
                <input type="text" name="s[serate_title]" value="<?php echo e2($cur['serate_title']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Testo pagina Corsi</label>
            <textarea name="s[corsi_text]" rows="3"><?php echo e2($cur['corsi_text']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Testo pagina Serate</label>
            <textarea name="s[serate_text]" rows="3"><?php echo e2($cur['serate_text']); ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Titolo pagina Contatti</label>
                <input type="text" name="s[contatti_title]" value="<?php echo e2($cur['contatti_title']); ?>">
            </div>
            <div class="form-group">
                <label>Titolo "La nostra storia" (pagina Storia)</label>
                <input type="text" name="s[chi_siamo_title]" value="<?php echo e2($cur['chi_siamo_title']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Testo pagina Contatti</label>
            <textarea name="s[contatti_text]" rows="3"><?php echo e2($cur['contatti_text']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Descrizione nel footer</label>
            <textarea name="s[footer_desc]" rows="2"><?php echo e2($cur['footer_desc']); ?></textarea>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-users"></i> Storia &amp; corpo docente (pagina "Storia")</div>
        <div class="form-group">
            <label>Storia del club</label>
            <textarea name="s[chi_siamo_text]" rows="6"><?php echo e2($cur['chi_siamo_text']); ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Titolo corpo docente</label>
                <input type="text" name="s[staff_title]" value="<?php echo e2($cur['staff_title']); ?>">
            </div>
            <div class="form-group">
                <label>Introduzione docenti</label>
                <input type="text" name="s[staff_intro]" value="<?php echo e2($cur['staff_intro']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Docenti (Nome — Ruolo, uno per riga)</label>
            <textarea name="s[staff_list]" rows="6"><?php echo e2($cur['staff_list']); ?></textarea>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-phone"></i> Recapiti</div>
        <div class="form-row">
            <div class="form-group">
                <label>Telefono 1 (info &amp; tessera)</label>
                <input type="text" name="s[phone]" value="<?php echo e2($cur['phone']); ?>">
            </div>
            <div class="form-group">
                <label>Telefono 2 (info &amp; corsi)</label>
                <input type="text" name="s[phone2]" value="<?php echo e2($cur['phone2']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="s[contact_email]" value="<?php echo e2($cur['contact_email']); ?>">
            </div>
            <div class="form-group">
                <label>WhatsApp (solo cifre)</label>
                <input type="text" name="s[whatsapp]" value="<?php echo e2($cur['whatsapp']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Sede principale (eventi e corsi)</label>
            <input type="text" name="s[address]" value="<?php echo e2($cur['address']); ?>">
        </div>
        <div class="form-group">
            <label>Seconda sede (corsi invernali)</label>
            <input type="text" name="s[address2]" value="<?php echo e2($cur['address2']); ?>">
        </div>
        <div class="form-group">
            <label>Sito affiliato</label>
            <input type="url" name="s[website]" value="<?php echo e2($cur['website']); ?>">
            <div class="field-hint">Es: https://www.danceclubssd.it</div>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-share-alt"></i> Social e footer</div>
        <div class="form-row">
            <div class="form-group">
                <label>Facebook</label>
                <input type="text" name="s[facebook]" value="<?php echo e2($cur['facebook']); ?>">
            </div>
            <div class="form-group">
                <label>Instagram · Serate</label>
                <input type="text" name="s[instagram_serate]" value="<?php echo e2($cur['instagram_serate']); ?>" placeholder="https://www.instagram.com/pergo_latino_danceclub">
            </div>
            <div class="form-group">
                <label>Instagram · Corsi</label>
                <input type="text" name="s[instagram_corsi]" value="<?php echo e2($cur['instagram_corsi']); ?>" placeholder="https://www.instagram.com/dance_club_ssd">
            </div>
        </div>
        <div class="form-group">
            <label>Testo copyright</label>
            <input type="text" name="s[footer_text]" value="<?php echo e2($cur['footer_text']); ?>">
        </div>
        <div class="field-hint">I due profili Instagram sono mostrati separatamente: uno per le serate, uno per i corsi.</div>
    </div>

    <button type="submit" name="save_settings" value="1" class="btn-save-general"><i class="fas fa-save"></i> Salva testi e contatti</button>
</form>
    </div>
</details>

<!-- ===== GRUPPO 03: I CORSI ===== -->
<details class="group" id="sec-3" open>
    <summary>
        <span class="group__num">03</span>
        <i class="fas fa-music"></i>
        <span class="group__title">I corsi</span>
        <span class="group__hint">Schede e orari dei corsi: più corsi per giorno</span>
        <i class="fas fa-chevron-down group__chev"></i>
    </summary>
    <div class="group-body">

<div class="section-block">
    <div class="section-title"><i class="fas fa-list"></i> Elenco corsi <span style="color:#666;font-size:13px;font-weight:400;">(<?php echo count($courses); ?> totali)</span></div>
    <div class="section-desc">Le prime 6 schede in evidenza compaiono nella home. Le frecce cambiano l'ordinamento.</div>

    <?php if (!$courses): ?>
        <div class="empty-state"><i class="fas fa-music"></i><p>Nessun corso inserito. Clicca "Nuovo corso" per iniziare.</p></div>
    <?php else: ?>
    <div class="table-scroll">
    <table class="admin-table">
        <thead><tr><th>#</th><th>Corso</th><th>Categoria</th><th>Maestro</th><th>Evidenza</th><th style="text-align:right;">Azioni</th></tr></thead>
        <tbody>
        <?php foreach ($courses as $i => $c): ?>
            <tr>
                <td style="color:#666;"><?php echo $i + 1; ?></td>
                <td>
                    <strong style="color:#fff;"><?php echo e2($c['name']); ?></strong>
                    <?php if ($c['description']): ?><div style="color:#777;font-size:12px;margin-top:3px;max-width:340px;"><?php echo e2(mb_substr($c['description'],0,90)); ?><?php echo mb_strlen($c['description'])>90?'…':''; ?></div><?php endif; ?>
                </td>
                <td><span class="pill off"><?php echo e2($cats[$c['category']] ?? $c['category']); ?></span></td>
                <td><?php echo $c['teacher'] ? e2($c['teacher']) : '<span style="color:#555;">—</span>'; ?></td>
                <td><?php if ($c['featured']): ?><span class="pill new">Evidenza</span><?php else: ?><span class="pill off">no</span><?php endif; ?></td>
                <td style="text-align:right;white-space:nowrap;">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                        <input type="hidden" name="action" value="move_discipline"><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                        <button type="submit" name="dir" value="up" class="btn-move" title="Su"><i class="fas fa-arrow-up"></i></button>
                        <button type="submit" name="dir" value="down" class="btn-move" title="Giù"><i class="fas fa-arrow-down"></i></button>
                    </form>
                    <button type="button" class="btn-small" onclick='editCourse(<?php echo json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG); ?>)'>Modifica</button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questo corso?')">
                        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                        <input type="hidden" name="action" value="delete_discipline"><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                        <button type="submit" class="btn-danger-small"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    <div style="margin-top:16px;">
        <button type="button" class="btn-gold" onclick="openCourseModal()"><i class="fas fa-plus"></i> Nuovo corso</button>
    </div>
</div>

<div class="section-block">
    <div class="section-title"><i class="fas fa-clock"></i> Orari dei corsi <span style="color:#666;font-size:13px;font-weight:400;">(<?php echo count($course_slots); ?> appuntamenti)</span></div>
    <div class="section-desc">
        Qui gestisci il palinsesto reale dei corsi: puoi avere <strong>più corsi nello stesso giorno</strong>.
        Gli orari attivi compaiono nella pagina Corsi, nella home (sotto le card) e nella settimana dei Soci.
    </div>

    <form method="POST" style="margin-bottom:24px;">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
        <input type="hidden" name="action" value="add_course_slot">
        <div class="form-row">
            <div class="form-group">
                <label>Giorno</label>
                <select name="day_index">
                    <?php for ($d = 0; $d < 7; $d++): ?>
                        <option value="<?php echo $d; ?>"><?php echo e2(getItalianDayName($d)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ora inizio <span class="required">*</span></label>
                <input type="time" name="start_time" value="20:30" required>
            </div>
            <div class="form-group">
                <label>Ora fine</label>
                <input type="time" name="end_time">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nome del corso <span class="required">*</span></label>
                <input type="text" name="title" required placeholder="es. Salsa Intermedio / Avanzato">
            </div>
            <div class="form-group">
                <label>Livello</label>
                <input type="text" name="level" placeholder="es. Principianti / Intermedio">
            </div>
            <div class="form-group">
                <label>Disciplina collegata</label>
                <select name="discipline_id">
                    <option value="0">— Nessuna —</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo e2($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Sede</label>
                <input type="text" name="venue" placeholder="es. Hotel Lodi">
            </div>
            <div class="form-group">
                <label>Maestro</label>
                <input type="text" name="teacher" placeholder="es. Pepe Espino & Jessica">
            </div>
            <div class="form-group">
                <label>Attivo</label>
                <select name="is_active">
                    <option value="1">Sì, visibile</option>
                    <option value="0">No, nascosto</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Nota interna (non pubblicata)</label>
            <input type="text" name="note" placeholder="es. la locandina indica 20:30 base e 21:30 intermedio">
        </div>
        <button type="submit" class="btn-gold"><i class="fas fa-plus"></i> Aggiungi orario</button>
    </form>

    <?php if (!$course_slots): ?>
        <div class="empty-state" style="padding:26px;">
            <i class="fas fa-clock"></i>
            <p>Nessun orario inserito. Aggiungi il primo corso qui sopra.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
        <table class="admin-table">
            <thead><tr><th>Giorno</th><th>Ora</th><th>Corso</th><th>Sede</th><th>Maestro</th><th>Stato</th><th style="text-align:right;">Azioni</th></tr></thead>
            <tbody>
            <?php foreach ($course_slots as $slot): ?>
                <tr class="<?php echo !empty($slot['is_active']) ? '' : 'is-inactive'; ?>">
                    <td class="day-name"><?php echo e2(getItalianDayName((int)$slot['day_index'])); ?></td>
                    <td style="white-space:nowrap;"><?php echo e2(substr((string)$slot['start_time'], 0, 5)); ?><?php if (!empty($slot['end_time'])): ?>–<?php echo e2(substr((string)$slot['end_time'], 0, 5)); ?><?php endif; ?></td>
                    <td>
                        <strong style="color:#fff;"><?php echo e2($slot['title']); ?></strong>
                        <?php if (!empty($slot['level'])): ?><div style="color:#777;font-size:12px;margin-top:2px;"><?php echo e2($slot['level']); ?></div><?php endif; ?>
                    </td>
                    <td><?php echo $slot['venue'] ? e2($slot['venue']) : '<span style="color:#555;">—</span>'; ?></td>
                    <td><?php echo $slot['teacher'] ? e2($slot['teacher']) : '<span style="color:#555;">—</span>'; ?></td>
                    <td><?php if (!empty($slot['is_active'])): ?><span class="pill new">attivo</span><?php else: ?><span class="pill off">nascosto</span><?php endif; ?></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questo orario?')">
                            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                            <input type="hidden" name="action" value="delete_course_slot">
                            <input type="hidden" name="id" value="<?php echo (int)$slot['id']; ?>">
                            <button type="submit" class="btn-danger-small"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<!-- ===== GRUPPO 04: LOCANDINE & PALINSETTO ===== -->
<!-- GRUPPO 04: EVENTI E LOCANDINE -->
<details class="group" id="sec-4" open>
    <summary>
        <span class="group__num">04</span>
        <i class="fas fa-calendar-star"></i>
        <span class="group__title">Eventi e locandine</span>
        <span class="group__hint">Countdown fisso, locandine, palinsesto serate</span>
        <i class="fas fa-chevron-down group__chev"></i>
    </summary>
    <div class="group-body">

<?php
$cd_enabled = ($cur['countdown_enabled'] !== '' ? $cur['countdown_enabled'] : '1');
$cd_day     = (int)($cur['countdown_day'] ?: 5);
$cd_time    = $cur['countdown_time'] ?: '20:30';
$cd_label   = $cur['countdown_label'] ?: 'Serata Caraibiche';
$cd_days    = [1=>'Lunedì',2=>'Martedì',3=>'Mercoledì',4=>'Giovedì',5=>'Venerdì',6=>'Sabato',7=>'Domenica'];
?>
<div class="section-block">
    <div class="section-title"><i class="fas fa-stopwatch"></i> Countdown della prossima serata</div>
    <div class="section-desc">
        Countdown fisso, senza data: si ripete ogni settimana nel giorno e nell'ora scelti.
        Puoi nasconderlo quando vuoi, senza toccare il codice.
    </div>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Mostra countdown</label>
                <select name="s[countdown_enabled]">
                    <option value="1" <?php echo $cd_enabled === '1' ? 'selected' : ''; ?>>Sì, visibile in home</option>
                    <option value="0" <?php echo $cd_enabled === '0' ? 'selected' : ''; ?>>No, nascosto</option>
                </select>
            </div>
            <div class="form-group">
                <label>Giorno della serata</label>
                <select name="s[countdown_day]">
                    <?php foreach ($cd_days as $dnum => $dlabel): ?>
                        <option value="<?php echo $dnum; ?>" <?php echo $cd_day === $dnum ? 'selected' : ''; ?>><?php echo e2($dlabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ora di inizio</label>
                <input type="time" name="s[countdown_time]" value="<?php echo e2($cd_time); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Titolo mostrato</label>
            <input type="text" name="s[countdown_label]" value="<?php echo e2($cd_label); ?>" placeholder="es. Serata Caraibiche">
        </div>
        <button type="submit" name="save_settings" value="1" class="btn-save-general"><i class="fas fa-save"></i> Salva countdown</button>
    </form>
</div>

<div class="section-block">
    <div class="section-title"><i class="fas fa-images"></i> Locandine della settimana <span style="color:#666;font-size:13px;font-weight:400;">(<?php echo count($posters); ?> online)</span></div>
    <div class="section-desc">
        Ogni settimana carichi qui le locandine delle serate: compaiono subito nella home e nella pagina Serate.<br>
        <strong>Se qui non c'è nessuna immagine, il sito mostra da solo il placeholder</strong> con il palinsesto fisso.
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
        <input type="hidden" name="action" value="add_locandine">
        <div class="form-group">
            <label>Carica le locandine di questa settimana (una o più)</label>
            <input type="file" name="locandine[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
            <div class="field-hint">JPG, PNG, WebP o GIF — max 8 MB per immagine. Alla nuova settimana premi "Svuota" e carichi le nuove.</div>
        </div>
        <button type="submit" class="btn-gold"><i class="fas fa-upload"></i> Carica locandine</button>
    </form>

    <?php if ($posters): ?>
        <div class="gallery-grid" style="margin-top:24px;">
            <?php foreach ($posters as $p): ?>
                <div class="gallery-card">
                    <img class="gallery-card__img" src="<?php echo e2($p['path']); ?>" alt="" style="object-fit:contain; aspect-ratio:3/4; background:#0a0a0a;">
                    <div class="gallery-card__body">
                        <div style="color:#888;font-size:11.5px;"><?php echo e2(date('d/m/Y H:i', $p['mtime'])); ?> · <?php echo round($p['size'] / 1024); ?> KB</div>
                        <div class="gallery-card__actions">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questa locandina?')">
                                <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                                <input type="hidden" name="action" value="delete_locandina">
                                <input type="hidden" name="file" value="<?php echo e2($p['file']); ?>">
                                <button type="submit" class="btn-danger-small"><i class="fas fa-trash"></i> Elimina</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" style="margin-top:18px;" onsubmit="return confirm('Eliminare TUTTE le locandine? Il sito mostrerà il placeholder.')">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <input type="hidden" name="action" value="clear_locandine">
            <button type="submit" class="btn-clear"><i class="fas fa-broom"></i> Svuota per la nuova settimana</button>
        </form>
    <?php else: ?>
        <div class="empty-state" style="padding:30px;">
            <i class="fas fa-calendar-star"></i>
            <p>Nessuna locandina online: il sito sta mostrando il placeholder.</p>
        </div>
    <?php endif; ?>
</div>

<form method="POST">
    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
    <input type="hidden" name="action" value="save_schedule">
    <div class="section-block">
        <div class="section-title"><i class="fas fa-clock"></i> Palinsesto serate</div>
        <div class="section-desc">
            Serate caraibiche venerdì e sabato, liscio la domenica pomeriggio.
            I <strong>corsi del lunedì–giovedì</strong> si gestiscono nella sezione <strong>3 · I corsi → Orari dei corsi</strong>.
        </div>
        <div class="table-scroll">
        <table class="admin-table">
            <thead><tr><th style="width:70px;">Attivo</th><th style="width:100px;">Giorno</th><th>Titolo attività</th><th style="width:100px;">Ora</th><th>Nota</th></tr></thead>
            <tbody>
            <?php for ($i = 4; $i <= 6; $i++):
                $row = $schedule[$i] ?? ['is_active' => 0, 'title' => '', 'start_time' => '', 'note' => ''];
                $on = !empty($row['is_active']); ?>
                <tr class="<?php echo $on ? '' : 'is-inactive'; ?>">
                    <td><input type="checkbox" name="schedule[<?php echo $i; ?>][is_active]" value="1" <?php echo $on ? 'checked' : ''; ?> onchange="this.closest('tr').classList.toggle('is-inactive',!this.checked)" style="width:17px;height:17px;accent-color:#D4AF37;"></td>
                    <td class="day-name"><?php echo e2(getItalianDayName($i)); ?></td>
                    <td><input type="text" name="schedule[<?php echo $i; ?>][title]" value="<?php echo e2($row['title']); ?>"></td>
                    <td><input type="time" name="schedule[<?php echo $i; ?>][start_time]" value="<?php echo e2($row['start_time']); ?>"></td>
                    <td><input type="text" name="schedule[<?php echo $i; ?>][note]" value="<?php echo e2($row['note']); ?>"></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn-save-general" style="margin-bottom:0;"><i class="fas fa-save"></i> Salva palinsesto serate</button>
    </div>
</form>
    </div>
</details>

<!-- ===== GRUPPO 05: RICHIESTE ===== -->
<!-- GRUPPO 05: LE RICHIESTE -->
<details class="group" id="sec-5">
    <summary>
        <span class="group__num">05</span>
        <i class="fas fa-inbox"></i>
        <span class="group__title">Le richieste</span>
        <span class="group__hint">Tessere e iscrizioni arrivate dal sito</span>
        <i class="fas fa-chevron-down group__chev"></i>
    </summary>
    <div class="group-body">

<div class="section-block">
    <div class="section-title"><i class="fas fa-envelope-open-text"></i> Moduli ricevuti <span style="color:#666;font-size:13px;font-weight:400;">(<?php echo count($requests); ?> in elenco)</span></div>
    <div class="section-desc">Richieste ricevute dal sito: tessera, iscrizione a un corso, informazioni.</div>

    <div class="filter-bar">
        <a class="tab <?php echo $reqFilter === '' ? 'active' : ''; ?>" href="?rfilter=">Tutte</a>
        <a class="tab <?php echo $reqFilter === 'nuovo' ? 'active' : ''; ?>" href="?rfilter=nuovo">Nuove (<?php echo countRequests($pdo, 'nuovo'); ?>)</a>
        <a class="tab <?php echo $reqFilter === 'letto' ? 'active' : ''; ?>" href="?rfilter=letto">Lette</a>
        <a class="tab <?php echo $reqFilter === 'gestito' ? 'active' : ''; ?>" href="?rfilter=gestito">Gestite</a>
        <div style="margin-left:auto;">
            <button type="button" class="btn-danger-small" style="padding:7px 16px;font-size:13px;" onclick="if(confirm('Eliminare DEFINITIVAMENTE tutte le richieste? Non è reversibile.')){document.getElementById('clearForm').submit();}">
                <i class="fas fa-trash"></i> Svuota archivio
            </button>
        </div>
    </div>

    <form id="clearForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
        <input type="hidden" name="action" value="clear_requests">
    </form>

    <?php if (!$requests): ?>
        <div class="empty-state"><i class="fas fa-inbox"></i><p>Nessuna richiesta <?php echo $reqFilter !== '' ? 'con questo stato' : 'in archivio'; ?>.</p></div>
    <?php else: foreach ($requests as $r): ?>
        <div class="req-card <?php echo $r['status'] === 'nuovo' ? 'unread' : ''; ?>">
            <div class="req-head">
                <div>
                    <span class="req-who"><?php echo e2($r['name'] ?: '—'); ?></span>
                    <span class="pill new" style="margin-left:8px;"><?php echo e2($typeLabels[$r['type']] ?? $r['type']); ?></span>
                </div>
                <div style="font-size:12.5px;color:#777;"><?php echo e2($r['created_at']); ?><?php if ($r['people']): ?> · <?php echo (int)$r['people']; ?> persone<?php endif; ?><?php if ($r['event_date']): ?> · <?php echo e2(date('d/m/Y', strtotime($r['event_date']))); ?><?php endif; ?></div>
            </div>
            <div class="req-meta">
                <?php if ($r['email']): ?><i class="fas fa-envelope" style="opacity:.5;"></i> <?php echo e2($r['email']); ?><?php endif; ?>
                <?php if ($r['phone']): ?> &nbsp;·&nbsp; <i class="fas fa-phone" style="opacity:.5;"></i> <?php echo e2($r['phone']); ?><?php endif; ?>
            </div>
            <div class="req-msg"><?php echo e2($r['message'] ?: '(nessun messaggio)'); ?></div>
            <div class="req-actions">
                <span class="pill <?php echo $statusMap[$r['status']] ?? 'off'; ?>"><?php echo e2($r['status']); ?></span>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                    <input type="hidden" name="action" value="update_request"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="nuovo" <?php echo $r['status'] === 'nuovo' ? 'selected' : ''; ?>>nuovo</option>
                        <option value="letto" <?php echo $r['status'] === 'letto' ? 'selected' : ''; ?>>letto</option>
                        <option value="gestito" <?php echo $r['status'] === 'gestito' ? 'selected' : ''; ?>>gestito</option>
                    </select>
                </form>
                <?php if ($r['email']): ?><a class="btn-small" href="mailto:<?php echo e2($r['email']); ?>?subject=Latin%20Club%20Lodi"><i class="fas fa-envelope"></i> Rispondi</a><?php endif; ?>
                <?php if ($r['phone']): ?>
                    <a class="btn-small" href="tel:<?php echo e2(preg_replace('/\s+/', '', $r['phone'])); ?>"><i class="fas fa-phone"></i> Chiama</a>
                    <?php $wa = preg_replace('/\D/', '', $r['phone']); if ($wa): ?>
                        <a class="btn-small" style="background:#25D366;color:#1A1A1A;font-weight:700;" target="_blank" rel="noopener" href="https://wa.me/<?php echo e2(str_starts_with($wa, '39') ? $wa : '39' . $wa); ?>"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    <?php endif; ?>
                <?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questa richiesta?')">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                    <input type="hidden" name="action" value="delete_request"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button type="submit" class="btn-danger-small"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
</details>

<!-- GRUPPO 06: SISTEMA -->
<details class="group" id="sec-6">
    <summary>
        <span class="group__num">06</span>
        <i class="fas fa-shield-alt"></i>
        <span class="group__title">Sistema</span>
        <span class="group__hint">Password, stato del sito e area riservata allo sviluppatore</span>
        <i class="fas fa-chevron-down group__chev"></i>
    </summary>
    <div class="group-body">

<!-- ===== GRUPPO 06: SISTEMA & AREA SVILUPPATORE ===== -->
<div class="content-group-label"><span class="num">06</span> <i class="fas fa-shield-alt"></i> Sistema &amp; area sviluppatore</div>

<div class="grid-2col">
    <div class="section-block">
        <div class="section-title"><i class="fas fa-user-shield"></i> Password admin</div>
        <div class="section-desc"><p>Gestisce contenuti, corsi, serate e richieste.</p></div>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <input type="hidden" name="change_password" value="1">
            <div class="form-row">
                <div class="form-group"><label>Password attuale</label><input type="password" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label>Nuova (min 8)</label><input type="password" name="new_password" required minlength="8" autocomplete="new-password"></div>
            </div>
            <div class="form-group"><label>Conferma nuova</label><input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></div>
            <button type="submit" class="btn-small" style="padding:9px 20px;font-size:13px;"><i class="fas fa-key"></i> Aggiorna</button>
        </form>
    </div>

    <div class="section-block">
        <div class="section-title"><i class="fas fa-server"></i> Stato sistema</div>
        <div class="info-list">
            <b>Sito:</b> <?php echo e2(SITE_URL); ?><br>
            <b>PHP:</b> <?php echo e2(PHP_VERSION); ?><br>
            <b>Database:</b> SQLite — <?php echo is_file(DB_PATH) ? round(filesize(DB_PATH) / 1024, 1) : 0; ?> KB<br>
            <b>Corsi:</b> <?php echo (int)$stats['courses']; ?> · <b>Locandine online:</b> <?php echo (int)$stats['posters']; ?> · <b>Richieste:</b> <?php echo (int)$stats['requests']; ?><br>
            <b>Countdown serata:</b> <?php echo ($cur['countdown_enabled'] ?? '1') === '1' ? 'attivo' : 'nascosto'; ?><br>
            <b>install.php:</b> <?php if (is_file(__DIR__ . '/install.php')): ?><span class="ko">ANCORA PRESENTE — da eliminare in produzione</span><?php else: ?><span class="ok">rimosso</span><?php endif; ?><br>
            <b>Sessione:</b> cookie HttpOnly + SameSite Strict, CSRF, honeypot anti-bot<br>
            <b>Admin:</b> noindex, nofollow — HTTPS forzato in produzione
        </div>
        <?php if (is_file(__DIR__ . '/install.php')): ?>
            <div style="margin-top:16px; padding:14px 18px; background:rgba(212,175,55,0.08); border:1px solid rgba(212,175,55,0.4); border-radius:8px; color:#D4AF37; font-size:13.5px;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Prima della pubblicazione:</strong> eliminare il file <strong>install.php</strong>.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== AREA SVILUPPATORE (riservata) ===== -->
<div class="section-block dev-area <?php echo $devUnlocked ? 'dev-area--open' : ''; ?>" id="area-sviluppatore">
    <div class="section-title" style="<?php echo $devUnlocked ? 'color:#2ecc71;' : 'color:#888;'; ?>">
        <i class="fas fa-code"></i> Area Sviluppatore
    </div>
    <div class="section-desc" style="border-left-color:<?php echo $devUnlocked ? '#2ecc71' : '#E2DAF0'; ?>;">
        <p>
            <?php if ($devUnlocked): ?>
                ✅ Area sbloccata: download del sito, cambio password, strumenti GDPR e log.
            <?php else: ?>
                🔐 Sezione riservata allo sviluppatore. Serve la password dello sviluppatore per sbloccarla.
            <?php endif; ?>
        </p>
    </div>

    <?php if (!$devUnlocked): ?>
        <form method="POST" style="max-width:420px;">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <input type="hidden" name="unlock_dev_area" value="1">
            <div class="form-group">
                <label>Password sviluppatore</label>
                <input type="password" name="dev_unlock_password" required autocomplete="off" placeholder="••••••••••••">
            </div>
            <button type="submit" class="btn-small" style="padding:10px 22px;font-size:13px;">
                <i class="fas fa-unlock"></i> Sblocca area
            </button>
        </form>
    <?php else: ?>

        <div class="dev-grid">
            <!-- Download -->
            <div class="dev-box">
                <div class="dev-box__title"><i class="fas fa-box-archive"></i> Download sito (ZIP)</div>
                <?php if (!class_exists('ZipArchive')): ?>
                    <p class="dev-box__note">Non disponibile qui: manca l'estensione PHP <strong>zip</strong>.</p>
                <?php else: ?>
                    <form method="POST" action="download.php" onsubmit="return confirm('Generare il file ZIP del sito?')">
                        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                        <button type="submit" class="btn-gold" style="width:100%;"><i class="fas fa-download"></i> Scarica ZIP</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Cambio password -->
            <div class="dev-box">
                <div class="dev-box__title"><i class="fas fa-key"></i> Cambio password</div>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                    <input type="hidden" name="change_passwords" value="1">
                    <div class="form-group"><label>Password admin (nuova)</label><input type="password" name="new_admin_password" minlength="8" autocomplete="new-password"></div>
                    <div class="form-group"><label>Password sviluppatore (nuova)</label><input type="password" name="new_dev_password" minlength="8" autocomplete="new-password"></div>
                    <p class="dev-box__note">Lascia vuoti i campi che non vuoi cambiare (min 8 caratteri).</p>
                    <button type="submit" class="btn-small" style="padding:9px 18px;font-size:13px;"><i class="fas fa-save"></i> Salva</button>
                </form>
            </div>

            <!-- GDPR -->
            <div class="dev-box">
                <div class="dev-box__title"><i class="fas fa-user-secret"></i> Strumenti GDPR</div>
                <p class="dev-box__note">Anonimizza i dati personali (nome, telefono, email) nelle richieste ricevute, mantenendo date e note.</p>
                <form method="POST" onsubmit="return confirm('Anonimizzare i dati personali di tutte le richieste? Non è reversibile.')">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                    <input type="hidden" name="anonymize_requests" value="1">
                    <button type="submit" class="btn-small" style="padding:9px 18px;font-size:13px;"><i class="fas fa-user-secret"></i> Anonimizza richieste</button>
                </form>
            </div>

            <!-- Log -->
            <div class="dev-box">
                <div class="dev-box__title"><i class="fas fa-scroll"></i> Log azioni</div>
                <?php $logs = getAdminLogs(25); ?>
                <?php if ($logs): ?>
                    <div class="log-container" style="max-height:190px;"><?php echo e2(implode("\n", $logs)); ?></div>
                    <form method="POST" style="margin-top:10px;" onsubmit="return confirm('Svuotare il log?')">
                        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="btn-clear"><i class="fas fa-eraser"></i> Svuota log</button>
                    </form>
                <?php else: ?>
                    <p class="dev-box__note">Nessuna azione registrata: il file si crea al primo accesso.</p>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" style="margin-top:18px;">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <input type="hidden" name="dev_logout" value="1">
            <button type="submit" class="btn-clear"><i class="fas fa-lock"></i> Chiudi area sviluppatore</button>
        </form>
    <?php endif; ?>
</div>
    </div><!-- /group-body -->
</details>

        </div><!-- /admin-main -->
        </div><!-- /admin-layout -->
    </div><!-- /dashboard -->
<?php endif; ?>
</div><!-- /admin-body -->

<!-- ===== GUIDA IN PANEL (finestra di aiuto) ===== -->
<?php if ($isAdmin): ?>
<div class="help-overlay" id="helpOverlay" role="dialog" aria-modal="true" aria-label="Guida al pannello">
    <div class="help-box">
        <button type="button" class="help-close" id="helpClose"><i class="fas fa-times"></i> Chiudi</button>
        <h3><i class="fas fa-graduation-cap"></i> Come si usa questo pannello</h3>
        <p class="help-sub">Guida rapida per chi gestisce il sito. Nulla di tecnico: tutto si fa da qui.</p>

        <h4>Le 6 sezioni</h4>
        <ul>
            <li><b>1 · Il sito</b> — nome del sito, logo, sfondo della home, link per la tessera online e colori.</li>
            <li><b>2 · I testi</b> — tutto quello che si legge sul sito, i telefoni, gli indirizzi, i social.</li>
            <li><b>3 · I corsi</b> — le schede dei corsi e gli orari settimanali (più corsi nello stesso giorno). Le prime 6 in evidenza compaiono in home.</li>
            <li><b>4 · Eventi e locandine</b> — il countdown fisso della serata, le locandine della settimana e il palinsesto delle serate.</li>
            <li><b>5 · Le richieste</b> — i moduli compilati dai visitatori (tessera, iscrizione a un corso, informazioni).</li>
            <li><b>6 · Sistema</b> — password di amministrazione e area riservata allo sviluppatore.</li>
        </ul>
        <p>Ogni sezione si apre e si chiude cliccando sul titolo. I pulsanti in alto a destra aprono o chiudono tutte insieme.</p>

        <h4>Ogni settimana (5 minuti)</h4>
        <ol>
            <li>Apri <b>4 · Eventi e locandine</b>.</li>
            <li>Nel blocco <b>Locandine della settimana</b> clicca <b>"Svuota per la nuova settimana"</b> e carica le locandine nuove (JPG o PNG, anche più file insieme).</li>
            <li>Se il programma dei <b>corsi</b> è cambiato, aggiorna <b>3 · I corsi → Orari dei corsi</b>.</li>
            <li>Per le <b>serate</b> usa <b>4 · Eventi e locandine → Palinsesto serate</b> e premi <b>Salva palinsesto serate</b>.</li>
        </ol>
        <p>Se non carichi locandine il sito continua a funzionare: mostra il palinsesto serate.</p>

        <h4>Il countdown della serata</h4>
        <p>In <b>4 · Eventi e locandine</b> puoi impostare il countdown fisso: <b>giorno</b>, <b>ora</b> e <b>titolo</b>. Si ripete ogni settimana, non serve inserire una data.</p>
        <ul>
            <li>Puoi <b>nasconderlo</b> quando vuoi con l'opzione "Mostra countdown".</li>
            <li>Non serve aggiornarlo ogni settimana: il conteggio riparte da solo.</li>
        </ul>

        <h4>La tessera online</h4>
        <p>In <b>1 · Il sito</b> trovi il link alla domanda di tesseramento online (4gest). I pulsanti "Richiedi la tessera online" aprono quel link in una nuova scheda.</p>

        <h4>I colori</h4>
        <p>In <b>1 · Il sito → Colori del tema</b> scegli il colore con il quadratino (oppure scrivi il codice esadecimale, es. <code>#6D4AA8</code>). Un colore non valido viene ignorato, non si rompe nulla.</p>

        <h4>Le richieste</h4>
        <p>Arrivano in <b>5 · Le richieste</b>. Segna ogni richiesta come <b>letta</b> o <b>gestita</b> e rispondi per telefono o WhatsApp.</p>
        <p><b>Nota:</b> il sito <b>non manda email</b>: le richieste restano solo in questo pannello.</p>

        <h4>Area riservata allo sviluppatore</h4>
        <p>In <b>6 · Sistema</b> c'è il riquadro <b>Area Sviluppatore</b>, chiuso. Serve la password dello sviluppatore per aprirlo: lì ci sono il download del sito in ZIP, il cambio delle password, gli strumenti GDPR e il log.</p>

        <h4>Consigli</h4>
        <ul>
            <li>Premi sempre il pulsante <b>Salva</b> in fondo al gruppo: le modifiche non sono automatiche.</li>
            <li>I campi con l'asterisco sono obbligatori.</li>
            <li>Le immagini (logo, locandine) si caricano in JPG o PNG, meglio sotto 2–3 MB.</li>
            <li>Prima di pubblicare il sito per la prima volta, lo sviluppatore elimina il file <code>install.php</code>.</li>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<!-- ===== MODALE CORSO ===== -->
<div class="modal-overlay" id="courseModal">
    <div class="modal-box">
        <h3 id="courseModalTitle">Nuovo corso</h3>
        <p class="modal-desc">Scheda del corso mostrata in home e nella pagina Corsi.</p>
        <form method="POST" id="courseForm">
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
            <input type="hidden" name="action" id="courseAction" value="add_discipline">
            <input type="hidden" name="id" id="courseId" value="">
            <div class="form-row">
                <div class="form-group">
                    <label>Nome corso <span class="required">*</span></label>
                    <input type="text" name="name" id="c_name" required placeholder="es. Salsa Cubana">
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category" id="c_category">
                        <?php foreach ($cats as $ck => $cl): ?><option value="<?php echo e2($ck); ?>"><?php echo e2($cl); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Maestro</label>
                    <input type="text" name="teacher" id="c_teacher" placeholder="es. Pepe Espino &amp; Jessica">
                </div>
                <div class="form-group">
                    <label>Icona Font Awesome</label>
                    <select name="icon" id="c_icon">
                        <?php foreach ($iconList as $ic): ?><option value="<?php echo e2($ic); ?>"><?php echo e2($ic); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descrizione</label>
                <textarea name="description" id="c_desc" rows="4" placeholder="Breve descrizione del corso..."></textarea>
            </div>
            <label class="check-line"><input type="checkbox" name="featured" id="c_featured"> In evidenza (mostrato nella home)</label>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('courseModal')">Annulla</button>
                <button type="submit" class="btn-confirm"><i class="fas fa-save"></i> Salva corso</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.querySelectorAll('.modal-overlay').forEach(function(m){
    m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); });
});
document.addEventListener('keydown',function(e){ if(e.key==='Escape') document.querySelectorAll('.modal-overlay.active').forEach(function(m){m.classList.remove('active');}); });

/* ---- Corsi ---- */
function openCourseModal(){
    document.getElementById('courseAction').value='add_discipline';
    document.getElementById('courseModalTitle').textContent='Nuovo corso';
    document.getElementById('courseId').value='';
    document.getElementById('c_name').value='';
    document.getElementById('c_category').value='caraibiche';
    document.getElementById('c_teacher').value='';
    document.getElementById('c_icon').value='fas fa-music';
    document.getElementById('c_desc').value='';
    document.getElementById('c_featured').checked=false;
    document.getElementById('courseModal').classList.add('active');
    setTimeout(function(){document.getElementById('c_name').focus();},100);
}
function editCourse(c){
    document.getElementById('courseAction').value='update_discipline';
    document.getElementById('courseModalTitle').textContent='Modifica corso';
    document.getElementById('courseId').value=c.id;
    document.getElementById('c_name').value=c.name;
    document.getElementById('c_category').value=c.category;
    document.getElementById('c_teacher').value=c.teacher||'';
    var sel=document.getElementById('c_icon');
    if(c.icon && Array.prototype.some.call(sel.options,function(o){return o.value===c.icon;})) sel.value=c.icon;
    document.getElementById('c_desc').value=c.description||'';
    document.getElementById('c_featured').checked=!!c.featured;
    document.getElementById('courseModal').classList.add('active');
}

/* ---- Sezioni a fisarmonica + guida ---- */
(function () {
    var groups = Array.prototype.slice.call(document.querySelectorAll('details.group'));
    if (!groups.length) return;

    /* stato (aperto/chiuso) ricordato da una sessione all'altra */
    var KEY = 'lcl_admin_groups';
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(KEY) || '{}'); } catch (e) { saved = {}; }
    groups.forEach(function (g) {
        if (typeof saved[g.id] === 'boolean') g.open = saved[g.id];
        g.addEventListener('toggle', function () {
            saved[g.id] = g.open;
            try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) {}
        });
    });

    function openGroup(id) {
        var g = document.getElementById(id);
        if (g) { g.open = true; }
    }

    /* menu laterale: apre la sezione e la porta in evidenza */
    document.querySelectorAll('.side-link, .qa-card[data-open]').forEach(function (link) {
        link.addEventListener('click', function () {
            var id = link.getAttribute('data-open') || (link.getAttribute('href') || '').replace('#', '');
            openGroup(id);
            var target = document.getElementById(id);
            if (target) {
                setTimeout(function () {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 60);
            }
        });
    });

    /* sezione evidenziata mentre si scorre */
    var links = Array.prototype.slice.call(document.querySelectorAll('.side-link'));
    function markActive() {
        var best = null;
        groups.forEach(function (g) {
            if (g.getBoundingClientRect().top <= 120) best = g.id;
        });
        links.forEach(function (l) {
            l.classList.toggle('is-active', best && l.getAttribute('href') === '#' + best);
        });
    }
    window.addEventListener('scroll', markActive, { passive: true });
    markActive();

    var expandAll = document.getElementById('expandAll');
    var collapseAll = document.getElementById('collapseAll');
    if (expandAll) expandAll.addEventListener('click', function () { groups.forEach(function (g) { g.open = true; }); });
    if (collapseAll) collapseAll.addEventListener('click', function () { groups.forEach(function (g) { g.open = false; }); });

    /* guida */
    var help = document.getElementById('helpOverlay');
    var helpBtn = document.getElementById('helpBtn');
    var helpClose = document.getElementById('helpClose');
    function setHelp(open) {
        if (!help) return;
        help.classList.toggle('open', open);
        if (open) window.scrollTo(0, 0);
    }
    if (helpBtn) helpBtn.addEventListener('click', function () { setHelp(true); });
    if (helpClose) helpClose.addEventListener('click', function () { setHelp(false); });
    if (help) {
        help.addEventListener('click', function (e) { if (e.target === help) setHelp(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && help.classList.contains('open')) setHelp(false);
        });
    }
})();
</script>
<?php endif; ?>
</body>
</html>
