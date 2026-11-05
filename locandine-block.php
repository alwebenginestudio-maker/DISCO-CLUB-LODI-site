<?php
/**
 * locandine-block.php — "Locandine della settimana"
 * Usato da index.php e serate.php.
 *
 * Le immagini si caricano dal pannello admin nella cartella
 * assets/img/locandine/. Se la cartella è vuota viene mostrato
 * un avviso semplice con il palinsesto fisso.
 */
$posters    = getWeeklyPosters(6);
$week_label = currentWeekLabel();
?>
<?php if (!empty($posters)): ?>
    <div class="poster-grid">
        <?php foreach ($posters as $p): ?>
            <figure class="poster">
                <a href="<?php echo e($p['path']); ?>" target="_blank" rel="noopener" aria-label="Apri la locandina della settimana del <?php echo e($week_label); ?>">
                    <img src="<?php echo e($p['path']); ?>"
                         alt="Locandina delle serate — settimana del <?php echo e($week_label); ?>"
                         loading="lazy">
                </a>
                <figcaption>Locandina · settimana del <?php echo e($week_label); ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="poster-empty">
        <h3>La locandina di questa settimana arriva a breve</h3>
        <p>Intanto il palinsesto non cambia: venerdì e sabato serate caraibiche, domenica pomeriggio ballo liscio.</p>
        <p>Ingresso riservato ai soci con tessera: la richiesta si fa online dalla pagina Soci.</p>
        <span class="poster-empty__week">Settimana del <?php echo e($week_label); ?></span>
    </div>
<?php endif; ?>
