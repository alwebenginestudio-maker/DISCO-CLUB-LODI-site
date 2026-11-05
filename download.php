<?php
/**
 * download.php - Download del sito completo in ZIP (accesso sviluppatore)
 * Dance Club Lodi
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Solo POST con area sviluppatore sbloccata (o login sviluppatore)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php#area-sviluppatore');
    exit;
}
if (!isAdminLoggedIn() || (!devAreaUnlocked() && ($_SESSION['login_type'] ?? '') !== 'developer')) {
    logAdminAction('Download sito rifiutato', 'area non sbloccata');
    header('Location: admin.php#area-sviluppatore');
    exit;
}
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
    header('Location: admin.php#area-sviluppatore');
    exit;
}
if (!class_exists('ZipArchive')) {
    header('Location: admin.php#area-sviluppatore');
    exit;
}

logAdminAction('Download sito completo');

$excludeFiles = ['download.zip', 'admin_log.txt', '.gitignore'];
$excludeDirs  = ['.git', 'backup', 'logs', 'database'];

$base = realpath(__DIR__);
$tmp  = sys_get_temp_dir() . '/lc_download_' . bin2hex(random_bytes(6)) . '.zip';

$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    header('Location: admin.php?section=sviluppatore&msg=' . rawurlencode('Impossibile creare il file ZIP.'));
    exit;
}

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $real = $file->getRealPath();
    $rel  = str_replace('\\', '/', substr($real, strlen($base) + 1));

    $skip = false;
    foreach ($excludeFiles as $ex) if ($rel === $ex) { $skip = true; break; }
    if (!$skip) {
        foreach ($excludeDirs as $exd) {
            if (strpos($rel, $exd . '/') === 0 || $rel === $exd) { $skip = true; break; }
        }
    }
    // Non includere il file corrente né eventuali zip già presenti
    if (!$skip && preg_match('/\.zip$/i', $rel)) $skip = true;
    if ($rel === 'download.php') $skip = true;

    if (!$skip) $zip->addFile($real, $rel);
}
$zip->close();

if (!is_file($tmp)) {
    header('Location: admin.php?section=sviluppatore&msg=' . rawurlencode('Creazione ZIP fallita.'));
    exit;
}

$filename = 'latinclublodi_sito_' . date('Y-m-d_H-i-s') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($tmp);
@unlink($tmp);
exit;
