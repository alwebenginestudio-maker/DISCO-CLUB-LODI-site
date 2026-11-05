<?php
// config.php - Dance Club Lodi (Dance Club SSD)
// NB: NON includere functions.php qui (evita dipendenza circolare)

// ============================================================
// SESSIONE SICURA
// ============================================================
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
         || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $is_https,     // cookie inviato solo su HTTPS
    'httponly' => true,          // non leggibile da JavaScript
    'samesite' => 'Strict'       // non inviato da siti esterni
]);
session_start();

// Token CSRF (protezione dei form admin/download)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// ============================================================
// COSTANTI
// ============================================================
define('DB_PATH', __DIR__ . '/database/data.db');
define('COOKIE_CONSENT_NAME', 'cookie_consent');
define('COOKIE_EXPIRE_DAYS', 365);
define('SITE_URL', ($is_https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');

// ============================================================
// HTTPS FORZATO (con supporto proxy/Cloudflare)
// ============================================================
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_development = (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    strpos($host, 'localhost:') === 0 ||
    strpos($host, '127.0.0.1:') === 0 ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 3000)
);

if (!$is_https && !$is_development) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// ============================================================
// DATABASE SQLITE (WAL mode per performance)
// ============================================================
try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("Connessione al database fallita: " . $e->getMessage());
}

// ============================================================
// SCHEMA AUTO-AGGIORNANTE
// La tabella degli orari corsi viene creata se manca, così il sito
// continua a funzionare anche su database già esistenti.
// ============================================================
try {
    $hasCourseSlots = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'course_slots'")->fetchColumn();
    if (!$hasCourseSlots) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS course_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            discipline_id INTEGER,
            day_index INTEGER NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT DEFAULT '',
            title TEXT NOT NULL,
            level TEXT DEFAULT '',
            venue TEXT DEFAULT '',
            teacher TEXT DEFAULT '',
            note TEXT DEFAULT '',
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0
        )");
    }
} catch (PDOException $e) {
    // Nessuna azione: la tabella esiste già
}

// ============================================================
// FUNZIONI BASE (definite qui per essere sempre disponibili)
// ============================================================
function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute([':key' => $key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function updateSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (:key, :value)");
    return $stmt->execute([':key' => $key, ':value' => $value]);
}

/**
 * Restituisce l'HTML del tag favicon.
 * Preferisce il logo caricato dal pannello (Impostazioni -> Logo); se non
 * c'e', usa la favicon dedicata (quadrato LATIN CLUB nero/oro).
 */
function renderFavicon($site_logo) {
    $fallback = 'assets/img/favicon.png';
    if ($site_logo && file_exists(__DIR__ . '/' . $site_logo)) {
        $ext = strtolower(pathinfo($site_logo, PATHINFO_EXTENSION));
        $type = 'image/x-icon';
        if ($ext === 'png') $type = 'image/png';
        elseif (in_array($ext, ['jpg','jpeg'])) $type = 'image/jpeg';
        elseif ($ext === 'svg') $type = 'image/svg+xml';
        elseif ($ext === 'webp') $type = 'image/webp';
        return '<link rel="icon" type="' . $type . '" href="' . htmlspecialchars($site_logo, ENT_QUOTES, 'UTF-8') . '">';
    }
    return '<link rel="icon" type="image/png" href="' . $fallback . '">';
}
