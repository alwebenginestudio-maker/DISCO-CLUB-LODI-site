<?php
/**
 * chi-siamo.php — vecchio indirizzo della pagina "Chi siamo".
 * Ora la storia del club ha una pagina dedicata: redirect 301
 * per non perdere i link già indicizzati.
 */
require_once __DIR__ . '/config.php';
header('Location: storia.php', true, 301);
exit;
