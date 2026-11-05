<?php
/**
 * sitemap.php - Sitemap XML dinamica
 * Dance Club Lodi — dominio determinato automaticamente dall'host della richiesta.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$base = SITE_URL;

$pages = [
    'index.php'     => ['pri' => '1.0', 'freq' => 'weekly'],
    'storia.php'    => ['pri' => '0.8', 'freq' => 'yearly'],
    'corsi.php'     => ['pri' => '0.9', 'freq' => 'monthly'],
    'serate.php'    => ['pri' => '0.9', 'freq' => 'weekly'],
    'soci.php'      => ['pri' => '0.9', 'freq' => 'monthly'],
    'contatti.php'  => ['pri' => '0.8', 'freq' => 'yearly'],
    'privacy.php'   => ['pri' => '0.3', 'freq' => 'yearly'],
    'termini.php'   => ['pri' => '0.3', 'freq' => 'yearly'],
];

// Ultima modifica: più recente tra eventi futuri e oggi
$lastmod = date('Y-m-d');
try {
    $evDate = $pdo->query("SELECT MAX(event_date) AS d FROM events WHERE event_date >= date('now')")->fetchColumn();
    if ($evDate && $evDate > $lastmod) $lastmod = $evDate;
} catch (PDOException $e) { /* tabella assente: ignora */ }

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $path => $meta) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo '    <lastmod>' . $lastmod . "</lastmod>\n";
    echo '    <changefreq>' . $meta['freq'] . "</changefreq>\n";
    echo '    <priority>' . $meta['pri'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
