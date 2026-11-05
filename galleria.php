<?php
/**
 * galleria.php — sezione accorpata a "Serate" (le foto non sono più gestite).
 * Redirect 301 per non perdere i link già indicizzati.
 */
require_once __DIR__ . '/config.php';
header('Location: serate.php', true, 301);
exit;
