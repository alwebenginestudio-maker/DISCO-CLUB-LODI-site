<?php
/**
 * admin-helpers.php — funzioni condivise del pannello di controllo.
 *
 * Tenute separate da admin.php così il file principale resta leggibile:
 *  - CSRF e filtro HTML
 *  - Upload delle immagini di impostazione (logo, hero)
 *  - Ordinamento (spostamento su/giù) di una tabella con sort_order
 *
 * Require: functions.php
 */
require_once __DIR__ . '/functions.php';

/* ---------- CSRF ---------- */
function csrf_ok(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf'] ?? '') . '">';
}

/* ---------- Filtro HTML ---------- */
function e2($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- Numeri e colori ---------- */
function isHexColor($value): bool {
    return (bool)preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$value);
}

/**
 * Salva un'immagine caricata (logo, sfondo hero).
 *
 * @param array  $file   una voce di $_FILES
 * @param int    $maxKB  dimensione massima in KB
 * @param string $prefix prefisso del nome file generato
 * @return array ['ok'=>bool, 'path'=>string, 'error'=>string]
 */
function uploadImageSetting(array $file, int $maxKB, string $prefix): array {
    $out = ['ok' => false, 'path' => '', 'error' => ''];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $out;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $out['error'] = 'Upload non riuscito: riprova con un file più piccolo.';
        return $out;
    }
    if (($file['size'] ?? 0) > $maxKB * 1024) {
        $out['error'] = 'Immagine troppo grande (massimo ' . $maxKB . ' KB).';
        return $out;
    }
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        $out['error'] = 'Formato non consentito: usa JPG, PNG o WebP.';
        return $out;
    }
    if (!@getimagesize($file['tmp_name']) && $ext !== 'svg') {
        $out['error'] = 'Il file caricato non è un\'immagine valida.';
        return $out;
    }

    $dir = __DIR__ . '/assets/img/uploads/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $name = $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $out['error'] = 'Impossibile salvare il file sul server.';
        return $out;
    }

    $out['ok']   = true;
    $out['path'] = 'assets/img/uploads/' . $name;
    return $out;
}

/**
 * Elimina il file di una vecchia immagine di impostazione.
 * Cancella solo file dentro assets/img/uploads/ (mai altrove).
 */
function deleteImageSetting($path) {
    if (!$path || strpos($path, 'assets/img/uploads/') !== 0) return false;
    $full = __DIR__ . '/' . $path;
    return is_file($full) ? @unlink($full) : false;
}

/**
 * Sposta un elemento di una tabella con colonna sort_order.
 * Usato per corsi e foto.
 */
function moveRowBySortOrder($pdo, string $table, int $id, string $direction): bool {
    $stmt = $pdo->prepare("SELECT sort_order FROM $table WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) return false;

    $order = (int)$row['sort_order'];
    if ($direction === 'up') {
        $sql = "SELECT id, sort_order FROM $table WHERE sort_order < :o ORDER BY sort_order DESC, id DESC LIMIT 1";
    } else {
        $sql = "SELECT id, sort_order FROM $table WHERE sort_order > :o ORDER BY sort_order ASC, id ASC LIMIT 1";
    }
    $q = $pdo->prepare($sql);
    $q->execute([':o' => $order]);
    $swap = $q->fetch();
    if (!$swap) return false;

    $pdo->prepare("UPDATE $table SET sort_order = :o WHERE id = :id")->execute([':o' => (int)$swap['sort_order'], ':id' => $id]);
    $pdo->prepare("UPDATE $table SET sort_order = :o WHERE id = :id")->execute([':o' => $order, ':id' => (int)$swap['id']]);
    return true;
}
