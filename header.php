<?php
// header.php - Dance Club Lodi (Dance Club SSD)
if (!isset($site_title)) $site_title = getSetting($pdo, 'site_title', 'Dance Club Lodi');

$current_page = basename($_SERVER['PHP_SELF']);

$nav_items = [
    'index.php'    => 'Home',
    'storia.php'   => 'Storia',
    'corsi.php'    => 'Corsi',
    'serate.php'   => 'Serate',
    'soci.php'     => 'Soci',
    'contatti.php' => 'Contatti',
];
?>
<header class="site-header">
    <div class="site-header__inner">
        <a class="brand" href="index.php" aria-label="<?php echo e($site_title); ?> — home">
            <span class="brand__name"><?php echo e($site_title); ?></span>
            <span class="brand__tag">Scuola di ballo · dal 2001</span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Apri menu" aria-expanded="false" aria-controls="main-nav">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav" id="main-nav" aria-label="Navigazione principale">
            <?php foreach ($nav_items as $file => $label): ?>
                <a href="<?php echo $file; ?>" class="<?php echo $current_page === $file ? 'active' : ''; ?>"><?php echo e($label); ?></a>
            <?php endforeach; ?>
            <a href="soci.php" class="nav__cta">Diventa socio</a>
        </nav>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('.nav-toggle');
        var nav = document.getElementById('main-nav');
        if (!toggle || !nav) return;

        function setOpen(open) {
            nav.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Chiudi menu' : 'Apri menu');
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(!nav.classList.contains('open'));
        });

        document.addEventListener('click', function (e) {
            if (nav.classList.contains('open') && !nav.contains(e.target) && e.target !== toggle) setOpen(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });

        nav.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { setOpen(false); });
        });

        var mq = window.matchMedia('(min-width: 901px)');
        var onChange = function () { setOpen(false); };
        if (mq.addEventListener) mq.addEventListener('change', onChange);
        else if (mq.addListener) mq.addListener(onChange);
    });
</script>
