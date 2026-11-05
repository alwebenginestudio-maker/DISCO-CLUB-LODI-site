<?php
require_once 'config.php';

// ============================================================
// ===== AUTENTICAZIONE ADMIN =====
// ============================================================
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function loginAdmin($pdo, $password) {
    // Password admin
    $stored_hash = getSetting($pdo, 'admin_password', '');
    if ($stored_hash && password_verify($password, $stored_hash)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_type'] = 'admin';
        logAdminAction('Accesso admin');
        return true;
    }
    // Password sviluppatore
    $dev_hash = getSetting($pdo, 'developer_password', '');
    if ($dev_hash && password_verify($password, $dev_hash)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_type'] = 'developer';
        logAdminAction('Accesso sviluppatore');
        return true;
    }
    return false;
}

function logoutAdmin() {
    logAdminAction('Logout admin');
    session_destroy();
    header('Location: admin.php?logout=1');
    exit;
}

// ============================================================
// ===== AREA SVILUPPATORE (sblocco dal pannello) =====
// Solo chi conosce la password sviluppatore può aprire l'area:
// download del sito, log, cambio password e strumenti GDPR.
// ============================================================
function devAreaUnlocked() {
    return isset($_SESSION['dev_area']) && $_SESSION['dev_area'] === true;
}

function devAreaLock() {
    unset($_SESSION['dev_area'], $_SESSION['dev_area_time']);
}

function devAreaUnlock($password) {
    global $pdo;
    $hash = getSetting($pdo, 'developer_password', '');
    if ($hash && password_verify((string)$password, $hash)) {
        $_SESSION['dev_area']      = true;
        $_SESSION['dev_area_time'] = time();
        logAdminAction('Area sviluppatore sbloccata');
        return true;
    }
    return false;
}

// ============================================================
// ===== UTILITY =====
// ============================================================
function sanitize($input) {
    return strip_tags(trim($input));
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function getImageUrl($path, $default = 'assets/img/latinclub-mark.png') {
    if ($path && file_exists(__DIR__ . '/' . $path)) {
        return $path;
    }
    return $default;
}

/**
 * Codice QR della tessera — NON PIÙ USATO.
 * La tessera si richiede con il link esterno impostato in admin (membership_url).
 */
function getQrImage($pdo) {
    return '';
}

function deleteImage($pdo, $setting_key) {
    $old_image = getSetting($pdo, $setting_key, '');
    if ($old_image && file_exists(__DIR__ . '/' . $old_image)) {
        unlink(__DIR__ . '/' . $old_image);
    }
    updateSetting($pdo, $setting_key, '');
    return true;
}

/** Aggiunge https:// se mancante (social/URL) */
function normalizeUrl($url) {
    $url = trim((string)$url);
    if ($url === '' ) return '';
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) return $url;
    return 'https://' . $url;
}

/** Etichetta della settimana in corso: "13 – 19 ottobre 2026" */
function currentWeekLabel() {
    $start = new DateTime('monday this week');
    $end   = (clone $start)->modify('+6 days');
    $mesi  = [1=>'gennaio',2=>'febbraio',3=>'marzo',4=>'aprile',5=>'maggio',6=>'giugno',7=>'luglio',8=>'agosto',9=>'settembre',10=>'ottobre',11=>'novembre',12=>'dicembre'];
    $sameMonth = ($start->format('n') === $end->format('n') && $start->format('Y') === $end->format('Y'));
    if ($sameMonth) {
        return (int)$start->format('j') . ' – ' . (int)$end->format('j') . ' ' . $mesi[(int)$end->format('n')] . ' ' . $end->format('Y');
    }
    return (int)$start->format('j') . ' ' . $mesi[(int)$start->format('n')] . ' – ' . (int)$end->format('j') . ' ' . $mesi[(int)$end->format('n')] . ' ' . $end->format('Y');
}

/** "venerdì 16 ottobre 2026" da una data Y-m-d */
function itaDate($ymd) {    $ts = strtotime($ymd);
    if (!$ts) return '';
    $giorni = [0=>'domenica',1=>'lunedì',2=>'martedì',3=>'mercoledì',4=>'giovedì',5=>'venerdì',6=>'sabato'];
    $mesi = [1=>'gennaio',2=>'febbraio',3=>'marzo',4=>'aprile',5=>'maggio',6=>'giugno',7=>'luglio',8=>'agosto',9=>'settembre',10=>'ottobre',11=>'novembre',12=>'dicembre'];
    return $giorni[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' ' . $mesi[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** "VEN 16 OTT" da una data Y-m-d */
function itaDateShort($ymd) {
    $ts = strtotime($ymd);
    if (!$ts) return '';
    $g = ['DOM','LUN','MAR','MER','GIO','VEN','SAB'];
    $m = [1=>'GEN',2=>'FEB',3=>'MAR',4=>'APR',5=>'MAG',6=>'GIU',7=>'LUG',8=>'AGO',9=>'SET',10=>'OTT',11=>'NOV',12=>'DIC'];
    return $g[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' ' . $m[(int)date('n', $ts)];
}

/** Numero del giorno (0 = Lunedì … 6 = Domenica) */
function getTodayIndex() {
    return (int)date('N') - 1;
}

function getItalianDayName($day_index) {
    $days = ['Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica'];
    return $days[$day_index] ?? '';
}

function getItalianDayShort($day_index) {
    $days = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    return $days[$day_index] ?? '';
}

/** Categorie dei corsi (chiave => etichetta) */
function getCourseCategories() {
    return [
        'caraibiche' => 'Danze caraibiche',
        'sala'       => 'Danze da sala & social',
        'stile'      => 'Stile & espressione',
    ];
}

/** Icona Font Awesome di default per categoria */
function categoryIcon($category) {
    $map = ['caraibiche' => 'fas fa-music', 'sala' => 'fas fa-record-vinyl', 'stile' => 'fas fa-star'];
    return $map[$category] ?? 'fas fa-music';
}

// ============================================================
// ===== CORSI / DISCIPLINE =====
// ============================================================
function getDisciplines($pdo, $category = '') {
    if ($category !== '') {
        $stmt = $pdo->prepare("SELECT * FROM disciplines WHERE category = :c ORDER BY sort_order ASC, id ASC");
        $stmt->execute([':c' => $category]);
        return $stmt->fetchAll();
    }
    return $pdo->query("SELECT * FROM disciplines ORDER BY sort_order ASC, id ASC")->fetchAll();
}

function getDiscipline($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM disciplines WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function countDisciplines($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM disciplines")->fetchColumn();
}

/**
 * Corsi gestiti direttamente da Pepe Espino.
 * Sulle schede corso portano l'etichetta "LatinClub" in basso a destra.
 */
function isLatinClubCourse($course) {
    $teacher = is_array($course) ? (string)($course['teacher'] ?? '') : (string)$course;
    return $teacher !== '' && stripos($teacher, 'pepe espino') !== false;
}

function addDiscipline($pdo, $name, $category, $description, $teacher, $icon, $featured) {
    $max = (int)$pdo->query("SELECT MAX(sort_order) FROM disciplines")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO disciplines (name, category, description, teacher, icon, featured, sort_order)
                           VALUES (:n, :c, :d, :t, :i, :f, :o)");
    return $stmt->execute([
        ':n' => $name, ':c' => $category, ':d' => $description, ':t' => $teacher,
        ':i' => $icon, ':f' => $featured ? 1 : 0, ':o' => $max + 1,
    ]);
}

function updateDiscipline($pdo, $id, $name, $category, $description, $teacher, $icon, $featured) {
    $stmt = $pdo->prepare("UPDATE disciplines SET name = :n, category = :c, description = :d, teacher = :t, icon = :i, featured = :f WHERE id = :id");
    return $stmt->execute([
        ':id' => $id, ':n' => $name, ':c' => $category, ':d' => $description,
        ':t' => $teacher, ':i' => $icon, ':f' => $featured ? 1 : 0,
    ]);
}

function deleteDiscipline($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM disciplines WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function moveDiscipline($pdo, $id, $direction) {
    $item = getDiscipline($pdo, $id);
    if (!$item) return false;
    $order = (int)$item['sort_order'];
    if ($direction === 'up') {
        $stmt = $pdo->prepare("SELECT * FROM disciplines WHERE sort_order < :o ORDER BY sort_order DESC LIMIT 1");
        $stmt->execute([':o' => $order]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM disciplines WHERE sort_order > :o ORDER BY sort_order ASC LIMIT 1");
        $stmt->execute([':o' => $order]);
    }
    $swap = $stmt->fetch();
    if (!$swap) return false;
    $pdo->prepare("UPDATE disciplines SET sort_order = :o WHERE id = :id")->execute([':o' => $swap['sort_order'], ':id' => $id]);
    $pdo->prepare("UPDATE disciplines SET sort_order = :o WHERE id = :id")->execute([':o' => $order, ':id' => $swap['id']]);
    return true;
}

// ============================================================
// ===== LOCANDINE DELLA SETTIMANA =====
// Cartella semplice: ogni settimana il pannello carica le
// locandine delle serate, al posto delle vecchie. Se la
// cartella è vuota il sito mostra un placeholder automatico.
// ============================================================
function locandineDir() {
    return __DIR__ . '/assets/img/locandine';
}

function locandineAllowed() {
    return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
}

/** Locandine presenti, dalla più recente alla più vecchia. */
function getWeeklyPosters($limit = 8) {
    $dir = locandineDir();
    if (!is_dir($dir)) return [];
    $ok  = locandineAllowed();
    $out = [];
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..' || $f[0] === '.') continue;
        if (!in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $ok, true)) continue;
        $path = $dir . '/' . $f;
        if (!is_file($path)) continue;
        $out[] = [
            'file'  => $f,
            'path'  => 'assets/img/locandine/' . $f,
            'mtime' => (int)filemtime($path),
            'size'  => (int)filesize($path),
        ];
    }
    usort($out, function ($a, $b) {
        if ($a['mtime'] !== $b['mtime']) return $b['mtime'] <=> $a['mtime'];
        return strcmp($b['file'], $a['file']);
    });
    return array_slice($out, 0, $limit > 0 ? $limit : count($out));
}

function countWeeklyPosters() {
    return count(getWeeklyPosters(0));
}

/** Salva le locandine caricate dal pannello. Ritorna quante sono state salvate. */
function saveWeeklyPosters(array $files) {
    $dir = locandineDir();
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $allowed = locandineAllowed();
    $n = 0;
    if (empty($files['name']) || !is_array($files['name'])) return 0;
    foreach ($files['name'] as $i => $original) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        if (($files['size'][$i] ?? 0) > 8 * 1024 * 1024) continue;   // max 8 MB per file
        $info = @getimagesize($files['tmp_name'][$i]);
        if (!$info) continue;
        $ext = strtolower(pathinfo((string)$original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) continue;
        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (move_uploaded_file($files['tmp_name'][$i], $dir . '/' . $name)) $n++;
    }
    return $n;
}

/** Elimina una singola locandina (solo nome file, mai percorsi). */
function deleteWeeklyPoster($file) {
    $file = basename((string)$file);
    if ($file === '' || in_array($file, ['.', '..'], true)) return false;
    if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), locandineAllowed(), true)) return false;
    $path = locandineDir() . '/' . $file;
    return is_file($path) ? @unlink($path) : false;
}

/** Svuota la cartella (uso: nuova settimana). */
function clearWeeklyPosters() {
    $n = 0;
    foreach (getWeeklyPosters(0) as $p) {
        if (@unlink(locandineDir() . '/' . $p['file'])) $n++;
    }
    return $n;
}

// ============================================================
// ===== SERATE (palinsesto fisso) =====
// ============================================================
/**
 * PROSSIMA APERTURA: la prossima ricorrenza del palinsesto fisso
 * (V/S serate caraibiche, D liscio, L/M corsi).
 *
 * @return array|null ['source','title','date','time']
 */
function getNextScheduleSlot($pdo) {
    $schedule = getWeeklySchedule($pdo);
    $todayIdx = getTodayIndex();
    $now = time();

    for ($i = 0; $i < 7; $i++) {
        $idx = ($todayIdx + $i) % 7;
        $row = $schedule[$idx] ?? null;
        if (!$row || empty($row['is_active']) || trim((string)($row['title'] ?? '')) === '') continue;

        $date = date('Y-m-d', strtotime('+' . $i . ' day'));
        $time = $row['start_time'] ?: '21:00';

        // Se è oggi e l'orario è già passato, passa al giorno successivo
        if ($i === 0 && strtotime($date . ' ' . $time) < $now) continue;

        return [
            'title' => $row['title'],
            'date'  => $date,
            'time'  => $time,
        ];
    }
    return null;
}

// ============================================================
// ===== PALINSETTO SETTIMANALE =====
// ============================================================
function getWeeklySchedule($pdo) {
    $rows = $pdo->query("SELECT * FROM weekly_schedule ORDER BY day_index ASC")->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[(int)$r['day_index']] = $r;
    return $out;
}

// ============================================================
// ===== ORARI DEI CORSI (più appuntamenti per giorno) =====
// ============================================================
function getCourseSlots($pdo, $onlyActive = true) {
    $sql = "SELECT * FROM course_slots";
    if ($onlyActive) $sql .= " WHERE is_active = 1";
    $sql .= " ORDER BY day_index ASC, start_time ASC, sort_order ASC, id ASC";
    return $pdo->query($sql)->fetchAll();
}

function getCourseSlotsByDay($pdo, $onlyActive = true) {
    $out = [];
    foreach (getCourseSlots($pdo, $onlyActive) as $slot) {
        $out[(int)$slot['day_index']][] = $slot;
    }
    return $out;
}

function getDisciplineSlots($pdo, $discipline_id, $onlyActive = true) {
    $sql = "SELECT * FROM course_slots WHERE discipline_id = :id";
    if ($onlyActive) $sql .= " AND is_active = 1";
    $sql .= " ORDER BY day_index ASC, start_time ASC, sort_order ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int)$discipline_id]);
    return $stmt->fetchAll();
}

function addCourseSlot($pdo, $day_index, $start_time, $end_time, $title, $level, $venue, $teacher, $discipline_id, $note, $active) {
    $max = (int)$pdo->query("SELECT MAX(sort_order) FROM course_slots")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO course_slots
        (discipline_id, day_index, start_time, end_time, title, level, venue, teacher, note, is_active, sort_order)
        VALUES (:did, :d, :s, :e, :t, :l, :v, :te, :n, :a, :o)");
    return $stmt->execute([
        ':did' => $discipline_id > 0 ? $discipline_id : null,
        ':d'   => (int)$day_index,
        ':s'   => $start_time,
        ':e'   => $end_time,
        ':t'   => sanitize($title),
        ':l'   => sanitize($level),
        ':v'   => sanitize($venue),
        ':te'  => sanitize($teacher),
        ':n'   => sanitize($note),
        ':a'   => $active ? 1 : 0,
        ':o'   => $max + 1,
    ]);
}

function deleteCourseSlot($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM course_slots WHERE id = :id");
    return $stmt->execute([':id' => (int)$id]);
}

/** Testo compatto degli orari di una disciplina: "Lun 21:00 · Hotel Lodi" */
function disciplineScheduleText($pdo, $discipline_id) {
    $parts = [];
    foreach (getDisciplineSlots($pdo, $discipline_id) as $slot) {
        $where = trim((string)$slot['venue']);
        $parts[] = getItalianDayShort((int)$slot['day_index']) . ' ' . substr((string)$slot['start_time'], 0, 5)
                 . ($where !== '' ? ' · ' . $where : '');
    }
    return implode(' · ', $parts);
}

function getScheduleByDay($pdo, $day_index) {
    $stmt = $pdo->prepare("SELECT * FROM weekly_schedule WHERE day_index = :d");
    $stmt->execute([':d' => $day_index]);
    return $stmt->fetch();
}

function getTodayScheduleRow($pdo) {
    return getScheduleByDay($pdo, getTodayIndex());
}

function updateWeeklySchedule($pdo, $data) {
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO weekly_schedule (day_index, is_active, title, start_time, note)
                           VALUES (:d, :a, :t, :s, :n)");
    $ok = true;
    foreach ($data as $day_index => $h) {
        try {
            $stmt->execute([
                ':d' => $day_index,
                ':a' => !empty($h['is_active']) ? 1 : 0,
                ':t' => sanitize($h['title'] ?? ''),
                ':s' => $h['start_time'] ?? '',
                ':n' => sanitize($h['note'] ?? ''),
            ]);
        } catch (PDOException $ex) {
            $ok = false;
        }
    }
    return $ok;
}

/** Testo compatto di una giornata del palinsesto: "21:00 · Serata caraibica" */
function formatScheduleRow($row) {
    if (!$row || empty($row['is_active']) || trim((string)$row['title']) === '') {
        return '';
    }
    $time = !empty($row['start_time']) ? $row['start_time'] . ' · ' : '';
    return $time . $row['title'];
}

// ============================================================
// ===== RICHIESTE (tessera / iscrizioni / info) =====
// ============================================================
function getRequestTypes() {
    return [
        'tessera'    => 'Richiesta tessera (socio)',
        'iscrizione' => 'Iscrizione a un corso',
        'info'       => 'Informazioni',
    ];
}

function addRequest($pdo, $type, $name, $phone, $email, $message) {
    $stmt = $pdo->prepare("INSERT INTO requests (type, name, phone, email, message, status, created_at)
                           VALUES (:ty, :n, :p, :e, :m, 'nuovo', :c)");
    return $stmt->execute([
        ':ty' => $type, ':n' => $name, ':p' => $phone, ':e' => $email,
        ':m' => $message, ':c' => date('Y-m-d H:i:s'),
    ]);
}

function getRequests($pdo, $status = '', $limit = 200) {
    if ($status !== '') {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE status = :s ORDER BY created_at DESC LIMIT :l");
        $stmt->bindValue(':s', $status);
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    $stmt = $pdo->prepare("SELECT * FROM requests ORDER BY created_at DESC LIMIT :l");
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countRequests($pdo, $status = '') {
    if ($status !== '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = :s");
        $stmt->execute([':s' => $status]);
        return (int)$stmt->fetchColumn();
    }
    return (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
}

function countNewRequests($pdo) {
    return countRequests($pdo, 'nuovo');
}

function deleteRequest($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM requests WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function updateRequestStatus($pdo, $id, $status) {
    if (!in_array($status, ['nuovo', 'letto', 'gestito'])) return false;
    $stmt = $pdo->prepare("UPDATE requests SET status = :s WHERE id = :id");
    return $stmt->execute([':s' => $status, ':id' => $id]);
}

function clearRequests($pdo) {
    $pdo->exec("DELETE FROM requests");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='requests'");
    return true;
}

/** GDPR: sostituisce i dati personali con valori anonimi */
function anonymizeRequests($pdo) {
    $stmt = $pdo->query("SELECT id FROM requests");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $upd = $pdo->prepare("UPDATE requests SET name = 'Anonimo', phone = '-', email = 'anonimo@esempio.it', message = 'Dati anonimizzati' WHERE id = :id");
    foreach ($ids as $id) $upd->execute([':id' => $id]);
    return true;
}

// ============================================================
// ===== GALLERIA =====
// ============================================================
function getGalleryImages($pdo, $limit = 0) {
    if ($limit > 0) {
        $stmt = $pdo->prepare("SELECT * FROM gallery_images ORDER BY sort_order ASC, id ASC LIMIT :l");
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    return $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, id ASC")->fetchAll();
}

function countGalleryImages($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
}

function getGalleryImage($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function addGalleryImage($pdo, $image_path, $caption = '') {
    $max = (int)$pdo->query("SELECT MAX(sort_order) FROM gallery_images")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO gallery_images (image_path, caption, sort_order) VALUES (:p, :c, :o)");
    return $stmt->execute([':p' => $image_path, ':c' => $caption, ':o' => $max + 1]);
}

function updateGalleryImageCaption($pdo, $id, $caption) {
    $stmt = $pdo->prepare("UPDATE gallery_images SET caption = :c WHERE id = :id");
    return $stmt->execute([':c' => $caption, ':id' => $id]);
}

function deleteGalleryImage($pdo, $id) {
    $img = getGalleryImage($pdo, $id);
    if ($img && $img['image_path'] && file_exists(__DIR__ . '/' . $img['image_path'])) {
        @unlink(__DIR__ . '/' . $img['image_path']);
    }
    $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

function moveGalleryImage($pdo, $id, $direction) {
    $img = getGalleryImage($pdo, $id);
    if (!$img) return false;
    $order = (int)$img['sort_order'];
    if ($direction === 'up') {
        $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE sort_order < :o ORDER BY sort_order DESC LIMIT 1");
        $stmt->execute([':o' => $order]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE sort_order > :o ORDER BY sort_order ASC LIMIT 1");
        $stmt->execute([':o' => $order]);
    }
    $swap = $stmt->fetch();
    if (!$swap) return false;
    $pdo->prepare("UPDATE gallery_images SET sort_order = :o WHERE id = :id")->execute([':o' => $swap['sort_order'], ':id' => $id]);
    $pdo->prepare("UPDATE gallery_images SET sort_order = :o WHERE id = :id")->execute([':o' => $order, ':id' => $swap['id']]);
    return true;
}

function clearGallery($pdo) {
    foreach (getGalleryImages($pdo) as $img) {
        if ($img['image_path'] && file_exists(__DIR__ . '/' . $img['image_path'])) {
            @unlink(__DIR__ . '/' . $img['image_path']);
        }
    }
    $pdo->exec("DELETE FROM gallery_images");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='gallery_images'");
    return true;
}

// ============================================================
// ===== DOWNLOAD SITO (sviluppatore) =====
// ============================================================
function downloadSite($password) {
    $pdo = $GLOBALS['pdo'];
    $stored_hash = getSetting($pdo, 'developer_password', '');
    if (!password_verify($password, $stored_hash)) return false;

    $zipname = 'sito_completo_' . date('Y-m-d_H-i-s') . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $exclude = ['install.php', 'download.zip', 'contacts.log', '.git', '.gitignore', 'README.md', 'backup/', 'admin_log.txt'];
    $exclude_dirs = ['.git', 'backup'];
    $base_dir = __DIR__;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        $file_path = $file->getRealPath();
        $relative_path = substr($file_path, strlen($base_dir) + 1);
        $exclude_file = false;
        foreach ($exclude as $ex) { if (strpos($relative_path, $ex) !== false) { $exclude_file = true; break; } }
        foreach ($exclude_dirs as $ex_dir) { if (strpos($relative_path, $ex_dir . '/') === 0) { $exclude_file = true; break; } }
        if (!$exclude_file) $zip->addFile($file_path, str_replace('\\', '/', $relative_path));
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipname . '"');
    header('Content-Length: ' . filesize($zipname));
    readfile($zipname);
    unlink($zipname);
    exit;
}

// ============================================================
// ===== LOGGING ADMIN =====
// ============================================================
function logAdminAction($action, $details = '') {
    $log_file = __DIR__ . '/admin_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user = $_SESSION['login_type'] ?? 'Admin';
    $entry = "[$timestamp] [$ip] [$user] $action" . ($details ? " | Dettagli: $details" : "") . PHP_EOL;
    file_put_contents($log_file, $entry, FILE_APPEND);
}

function getAdminLogs($limit = 200) {
    $log_file = __DIR__ . '/admin_log.txt';
    if (!file_exists($log_file)) return [];
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_slice(array_reverse($lines), 0, $limit);
}

function clearAdminLogs() {
    $log_file = __DIR__ . '/admin_log.txt';
    if (file_exists($log_file)) { unlink($log_file); return true; }
    return false;
}
