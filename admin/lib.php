<?php
declare(strict_types=1);

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        session_start();
    }
}

function esc($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ============ Giriş ============ */

function admin_data(): array
{
    if (!is_file(ADMIN_FILE)) {
        $d = ['user' => 'admin', 'password_hash' => password_hash('admin123', PASSWORD_DEFAULT)];
        @file_put_contents(ADMIN_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $d;
    }
    $d = json_decode(@file_get_contents(ADMIN_FILE), true);
    if (!is_array($d)) $d = [];
    return $d + ['user' => 'admin', 'password_hash' => ''];
}

function save_admin_data(array $d): void
{
    $tmp = ADMIN_FILE . '.tmp';
    file_put_contents($tmp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    rename($tmp, ADMIN_FILE);
}

function is_default_password(): bool
{
    $d = admin_data();
    return !empty($d['password_hash']) && password_verify('admin123', $d['password_hash']);
}

function is_logged_in(): bool
{
    admin_session_start();
    return !empty($_SESSION['admin_ok']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string
{
    admin_session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    admin_session_start();
    $ok = isset($_POST['csrf']) && isset($_SESSION['csrf'])
        && hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        exit('Sessiya xətası.');
    }
}

function flash(string $msg, string $type = 'ok'): void
{
    admin_session_start();
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function take_flash(): ?array
{
    admin_session_start();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ============ JSON köməkçiləri ============ */

function json_file(string $path, $default = [])
{
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return $default;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $default;
}

function write_json_file(string $path, array $data): void
{
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    rename($tmp, $path);
}

/* ============ Restoran reyestri ============ */

function registry(): array
{
    if (!is_file(REGISTRY_FILE)) {
        @file_put_contents(REGISTRY_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return [];
    }
    $d = json_file(REGISTRY_FILE, []);
    return is_array($d) ? array_values($d) : [];
}

function save_registry(array $list): void
{
    write_json_file(REGISTRY_FILE, array_values($list));
}

function find_restaurant(string $slug): ?array
{
    foreach (registry() as $r) {
        if (($r['slug'] ?? '') === $slug) return $r;
    }
    return null;
}

function valid_slug(string $s): bool
{
    return (bool)preg_match('/^[a-z0-9-]{2,30}$/', $s);
}

function reserved_slug(string $s): bool
{
    static $reserved = ['admin', 'api', 'assets', 'builder', 'data', 'owner'];
    return in_array($s, $reserved, true);
}

function slugify(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['ə' => 'e', 'ı' => 'i', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c', 'ş' => 's', 'ğ' => 'g']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return $s !== '' ? mb_substr($s, 0, 30) : '';
}

/** Qovluğu rekursiv kopyala (data/ xaric — təzə yazılır). */
function rcopy_template(string $src, string $dst): void
{
    @mkdir($dst, 0777, true);
    $dh = opendir($src);
    if (!$dh) throw new RuntimeException('Şablon oxunmadı.');
    while (($f = readdir($dh)) !== false) {
        if ($f === '.' || $f === '..' || $f === 'data') continue;
        $s = $src . '/' . $f;
        $d = $dst . '/' . $f;
        if (is_dir($s)) {
            rcopy_template($s, $d);
        } elseif (!@copy($s, $d)) {
            closedir($dh);
            throw new RuntimeException('Kopya alınmadı: ' . $f);
        }
    }
    closedir($dh);
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_dir($p)) rrmdir($p);
        else @unlink($p);
    }
    @rmdir($dir);
}

function default_design(): array
{
    return ['theme' => 1, 'loader' => 1, 'video' => 1, 'quick' => 1, 'search' => 1, 'cats' => 1, 'prices' => 1, 'showLang' => 1, 'patterns' => 0, 'photos' => 1, 'topbar' => 1, 'footer' => 1, 'custom' => 1, 'custom_bg' => '#000000', 'custom_accent' => '#B91C1C', 'custom_catbg' => '', 'custom_qicon' => ''];
}

/* ============ Restoran statistikası ============ */

function rest_load_menu(string $slug): array
{
    $d = json_file(GLOMENU_ROOT . '/' . $slug . '/data/menu.json', []);
    $d['restaurant'] = $d['restaurant'] ?? [];
    $d['categories'] = $d['categories'] ?? [];
    $d['items'] = $d['items'] ?? [];
    return $d;
}

function rest_save_menu(string $slug, array $d): void
{
    write_json_file(GLOMENU_ROOT . '/' . $slug . '/data/menu.json', $d);
}

function rest_visits(string $slug): int
{
    $v = json_file(GLOMENU_ROOT . '/' . $slug . '/data/visits.json', []);
    return (isset($v['total']) && is_numeric($v['total'])) ? (int)$v['total'] : 0;
}

function rest_rating_summary(string $slug): array
{
    $p = GLOMENU_ROOT . '/' . $slug . '/data/ratings.log';
    if (!is_file($p)) return ['count' => 0, 'avg' => 0];
    $n = 0;
    $sum = 0;
    $fh = @fopen($p, 'r');
    if ($fh) {
        while (($line = fgets($fh)) !== false) {
            $e = json_decode(trim($line), true);
            $s = is_array($e) ? (int)($e['stars'] ?? 0) : 0;
            if ($s >= 1 && $s <= 5) {
                $n++;
                $sum += $s;
            }
        }
        fclose($fh);
    }
    return ['count' => $n, 'avg' => $n ? $sum / $n : 0];
}

function fmt_num(int $n): string
{
    return number_format($n, 0, '', ' ');
}

/* ============ Yemək/kateqoriya idarəsi (restoran qovluğu daxilində) ============ */

define('DISH_MAX_IMG_BYTES', 8 * 1024 * 1024);

function dish_find(array $items, int $id): ?array
{
    foreach ($items as $it) {
        if ((int)($it['id'] ?? 0) === $id) return $it;
    }
    return null;
}

function dish_next_id(array $items): int
{
    $max = 0;
    foreach ($items as $it) $max = max($max, (int)($it['id'] ?? 0));
    return $max + 1;
}

function dish_cat_id(array $cats, string $base): string
{
    $ids = array_column($cats, 'id');
    $id = $base !== '' ? $base : 'kat';
    if (!in_array($id, $ids, true)) return $id;
    for ($i = 2; $i < 100; $i++) {
        if (!in_array($id . '-' . $i, $ids, true)) return $id . '-' . $i;
    }
    return $id . '-' . time();
}

/** Yemək şəkli yüklə → ['ok'=>bool,'rel'=>?,'error'=>?]. */
function dish_upload(string $slug): array
{
    if (!isset($_FILES['imgfile']) || $_FILES['imgfile']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'rel' => null, 'error' => null];
    }
    $f = $_FILES['imgfile'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'rel' => null, 'error' => 'Yükləmə xətası (kod ' . (int)$f['error'] . ')'];
    }
    if ($f['size'] > DISH_MAX_IMG_BYTES) {
        return ['ok' => false, 'rel' => null, 'error' => 'Şəkil çox böyükdür (maks 8MB).'];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'İcazəsiz fayl növü (.' . $ext . ')'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !@getimagesize($f['tmp_name'])) {
        return ['ok' => false, 'rel' => null, 'error' => 'Şəkil faylı oxunmadı (' . $mime . ').'];
    }
    $dir = GLOMENU_ROOT . '/' . $slug . '/assets/img/dishes';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Qovluq yaradılmadı.'];
    }
    $name = 'dish-' . slugify(pathinfo((string)$f['name'], PATHINFO_FILENAME))
        . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Fayl saxlanmadı.'];
    }
    return ['ok' => true, 'rel' => 'assets/img/dishes/' . $name, 'error' => null];
}

/** Şəkil/video/poster başqa məhsulda işlənmirsə sil. */
function dish_delete_media(string $slug, array $items, string $rel): void
{
    if ($rel === '') return;
    foreach ($items as $it) {
        if (($it['img'] ?? '') === $rel || ($it['video'] ?? '') === $rel || ($it['poster'] ?? '') === $rel) return;
    }
    @unlink(GLOMENU_ROOT . '/' . $slug . '/' . $rel);
}

define('DISH_MAX_VID_BYTES', 35 * 1024 * 1024);

/** Yemək videosu yüklə (mp4/webm) → ['ok'=>bool,'rel'=>?,'error'=>?]. Sürətli başlanğıc üçün moov düzəldilir. */
function dish_upload_video(string $slug): array
{
    if (!isset($_FILES['vidfile']) || $_FILES['vidfile']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'rel' => null, 'error' => null];
    }
    $f = $_FILES['vidfile'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'rel' => null, 'error' => 'Yükləmə xətası (kod ' . (int)$f['error'] . ')'];
    }
    if ($f['size'] > DISH_MAX_VID_BYTES) {
        return ['ok' => false, 'rel' => null, 'error' => 'Video çox böyükdür (maks 35MB).'];
    }
    if (($f['size'] ?? 0) <= 0) {
        return ['ok' => false, 'rel' => null, 'error' => 'Boş fayl göndərildi.'];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm'], true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Yalnız .mp4 və ya .webm formatı olar.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, ['video/mp4', 'application/mp4', 'video/webm'], true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Fayl video deyil (' . $mime . ').'];
    }
    $dir = GLOMENU_ROOT . '/' . $slug . '/assets/vid/dishes';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Qovluq yaradılmadı.'];
    }
    $name = 'dishvid-' . slugify(pathinfo((string)$f['name'], PATHINFO_FILENAME))
        . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Fayl saxlanmadı.'];
    }
    @hero_faststart($dir . '/' . $name);
    return ['ok' => true, 'rel' => 'assets/vid/dishes/' . $name, 'error' => null];
}

/** Poster (videonun ilk kadrı, JPG) yüklə → ['ok'=>bool,'rel'=>?,'error'=>?].
 *  İlk kadrı brauzer canvas ilə tutub göndərir. */
function dish_upload_poster(string $slug): array
{
    if (!isset($_FILES['posterfile']) || $_FILES['posterfile']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'rel' => null, 'error' => null];
    }
    $f = $_FILES['posterfile'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'rel' => null, 'error' => 'Poster yükləmə xətası (kod ' . (int)$f['error'] . ')'];
    }
    if ($f['size'] > DISH_MAX_IMG_BYTES) {
        return ['ok' => false, 'rel' => null, 'error' => 'Poster çox böyükdür (maks 8MB).'];
    }
    if (($f['size'] ?? 0) <= 0) {
        return ['ok' => false, 'rel' => null, 'error' => 'Boş poster göndərildi.'];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg'], true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Poster yalnız JPG olar.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if ($mime !== 'image/jpeg' || !@getimagesize($f['tmp_name'])) {
        return ['ok' => false, 'rel' => null, 'error' => 'Poster JPG oxunmadı (' . $mime . ').'];
    }
    $dir = GLOMENU_ROOT . '/' . $slug . '/assets/vid/dishes';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Qovluq yaradılmadı.'];
    }
    $name = 'poster-' . slugify(pathinfo((string)$f['name'], PATHINFO_FILENAME))
        . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.jpg';
    if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'rel' => null, 'error' => 'Poster saxlanmadı.'];
    }
    return ['ok' => true, 'rel' => 'assets/vid/dishes/' . $name, 'error' => null];
}

function dish_cover_xy($v, int $def = 50): int
{
    if ($v === '' || $v === null) return $def;
    $n = (int)$v;
    if ($n < 0) return 0;
    if ($n > 100) return 100;
    return $n;
}

function dish_cover_zoom($v, int $def = 100): int
{
    if ($v === '' || $v === null) return $def;
    $n = (int)$v;
    if ($n < 100) return 100;
    if ($n > 250) return 250;
    return $n;
}

/* ============ Hero videosu (restoran arxa fonu) ============
 * Yüklənən mp4 birbaşa hero.mp4 olur — vitrin onu səssiz, loop ilə
 * avtomatik oynadır. Sürətli başlanğıc üçün moov bloku səssizcə
 * əvvələ keçirilir (təkrar encode YOXDUR, keyfiyyət dəyişmir). */

define('HERO_MAX_BYTES', 35 * 1024 * 1024);

function hero_abs(string $slug): string
{
    return GLOMENU_ROOT . '/' . $slug . '/assets/vid/hero.mp4';
}

/** Hazırda hero videosu varsa məlumat, yoxdursa null. */
function hero_info(string $slug): ?array
{
    $abs = hero_abs($slug);
    if (!is_file($abs)) return null;
    return ['size' => filesize($abs), 'mtime' => filemtime($abs)];
}

function hero_delete(string $slug): void
{
    @unlink(hero_abs($slug));
    @unlink(GLOMENU_ROOT . '/' . $slug . '/assets/vid/hero.hevc.mp4');
}

/** MP4-ü yoxlayıb hero.mp4 kimi saxla. ['ok'=>bool,'error'=>?] */
function hero_upload(string $slug): array
{
    if (!isset($_FILES['herofile'])) {
        return ['ok' => false, 'error' => 'Fayl göndərilmədi.'];
    }
    $f = $_FILES['herofile'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST)) {
        return ['ok' => false, 'error' => 'Fayl server limitini keçir (maks 35MB). Daha kiçik video seçin.'];
    }
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        if (($f['error'] ?? 0) === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'error' => 'Video seçilməyib.'];
        return ['ok' => false, 'error' => 'Yükləmə xətası (kod ' . (int)$f['error'] . ')'];
    }
    if (($f['size'] ?? 0) > HERO_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Video çox böyükdür (maks 35MB).'];
    }
    if (($f['size'] ?? 0) <= 0) return ['ok' => false, 'error' => 'Boş fayl göndərildi.'];
    $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext !== 'mp4') return ['ok' => false, 'error' => 'Yalnız .mp4 formatı olar.'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, ['video/mp4', 'application/mp4'], true)) {
        return ['ok' => false, 'error' => 'Fayl MP4 videosu deyil (' . $mime . ').'];
    }
    $dir = GLOMENU_ROOT . '/' . $slug . '/assets/vid';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        return ['ok' => false, 'error' => 'Qovluq yaradılmadı.'];
    }
    $dest = hero_abs($slug);
    @unlink($dest);
    @unlink($dir . '/hero.hevc.mp4');
    if (!@move_uploaded_file($f['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Fayl saxlanmadı.'];
    }
    @hero_faststart($dest);
    return ['ok' => true, 'error' => null];
}

/** moov içində mvex varsa fraqmentlidir — toxunmaq olmaz. */
function hero_fs_has_mvex(string $moov): bool
{
    $len = strlen($moov);
    $off = 8;
    while ($off + 8 <= $len) {
        $u = unpack('N', substr($moov, $off, 4));
        $sz = $u[1];
        $tp = substr($moov, $off + 4, 4);
        if ($sz === 1) {
            if ($off + 16 > $len) break;
            $hi = unpack('N', substr($moov, $off + 8, 4));
            $lo = unpack('N', substr($moov, $off + 12, 4));
            $sz = $hi[1] * 4294967296 + $lo[1];
        } elseif ($sz === 0) {
            $sz = $len - $off;
        }
        if ($sz < 8 || $off + $sz > $len) break;
        if ($tp === 'mvex') return true;
        $off += $sz;
    }
    return false;
}

function hero_fs_is_container(string $t): bool
{
    static $c = ['moov' => 1, 'trak' => 1, 'mdia' => 1, 'minf' => 1, 'dinf' => 1, 'stbl' => 1, 'edts' => 1, 'udta' => 1, 'iprp' => 1, 'tref' => 1, 'meco' => 1];
    return isset($c[$t]);
}

/** stco/co64 ofsetlərinə $shift əlavə et (moov xam datası yerindəcə). */
function hero_fs_patch(string &$moov, int $shift): void
{
    $len = strlen($moov);
    $stack = [[8, $len]];
    while ($stack) {
        $node = array_pop($stack);
        $off = $node[0];
        $end = $node[1];
        while ($off + 8 <= $end) {
            $u = unpack('N', substr($moov, $off, 4));
            $sz = $u[1];
            $tp = substr($moov, $off + 4, 4);
            $hdr = 8;
            if ($sz === 1) {
                if ($off + 16 > $end) break;
                $hi = unpack('N', substr($moov, $off + 8, 4));
                $lo = unpack('N', substr($moov, $off + 12, 4));
                $sz = $hi[1] * 4294967296 + $lo[1];
                $hdr = 16;
            } elseif ($sz === 0) {
                $sz = $end - $off;
            }
            if ($sz < $hdr || $off + $sz > $end) break;
            if ($tp === 'stco') {
                if ($off + 16 <= $off + $sz) {
                    $c = unpack('N', substr($moov, $off + 12, 4));
                    $c = $c[1];
                    for ($i = 0; $i < $c; $i++) {
                        $p = $off + 16 + $i * 4;
                        if ($p + 4 > $off + $sz) break;
                        $v = unpack('N', substr($moov, $p, 4));
                        $v = ($v[1] + $shift) & 0xFFFFFFFF;
                        $moov[$p] = chr(($v >> 24) & 255);
                        $moov[$p + 1] = chr(($v >> 16) & 255);
                        $moov[$p + 2] = chr(($v >> 8) & 255);
                        $moov[$p + 3] = chr($v & 255);
                    }
                }
            } elseif ($tp === 'co64') {
                if ($off + 16 <= $off + $sz) {
                    $c = unpack('N', substr($moov, $off + 12, 4));
                    $c = $c[1];
                    for ($i = 0; $i < $c; $i++) {
                        $p = $off + 16 + $i * 8;
                        if ($p + 8 > $off + $sz) break;
                        $hi = unpack('N', substr($moov, $p, 4));
                        $hi = $hi[1];
                        $lo = unpack('N', substr($moov, $p + 4, 4));
                        $lo = $lo[1];
                        $lo += $shift;
                        if ($lo >= 4294967296) {
                            $hi++;
                            $lo -= 4294967296;
                        }
                        $moov[$p] = chr(($hi >> 24) & 255);
                        $moov[$p + 1] = chr(($hi >> 16) & 255);
                        $moov[$p + 2] = chr(($hi >> 8) & 255);
                        $moov[$p + 3] = chr($hi & 255);
                        $moov[$p + 4] = chr(($lo >> 24) & 255);
                        $moov[$p + 5] = chr(($lo >> 16) & 255);
                        $moov[$p + 6] = chr(($lo >> 8) & 255);
                        $moov[$p + 7] = chr($lo & 255);
                    }
                }
            } elseif (hero_fs_is_container($tp)) {
                $b = $off + $hdr + ($tp === 'meta' ? 4 : 0);
                if ($b < $off + $sz) $stack[] = [$b, $off + $sz];
            }
            $off += $sz;
        }
    }
}

function hero_fs_copy($fin, $fout, int $bytes): bool
{
    $chunk = 1048576;
    while ($bytes > 0) {
        $n = $bytes > $chunk ? $chunk : $bytes;
        $d = fread($fin, $n);
        if ($d === false || $d === '') return false;
        if (fwrite($fout, $d) === false) return false;
        $bytes -= strlen($d);
    }
    return true;
}

/**
 * moov-u faylın əvvəlinə keçir (artıq öndədirsə əl vurmur).
 * Uğurda true, atlandı/xətada false (video onsuz da işləyir).
 */
function hero_faststart(string $abs): bool
{
    if (strtolower(pathinfo($abs, PATHINFO_EXTENSION)) !== 'mp4' || !is_file($abs)) return false;
    $size = filesize($abs);
    if ($size === false || $size < 16 || $size > 500 * 1024 * 1024) return false;
    $fin = fopen($abs, 'rb');
    if (!$fin) return false;
    $boxes = [];
    $off = 0;
    $guard = 0;
    fseek($fin, 4);
    if (fread($fin, 4) !== 'ftyp') {
        fclose($fin);
        return false;
    }
    while ($off + 8 <= $size && $guard < 128) {
        $guard++;
        fseek($fin, $off);
        $hdr = fread($fin, 8);
        if (strlen($hdr) < 8) break;
        $u = unpack('N', substr($hdr, 0, 4));
        $sz = $u[1];
        $type = substr($hdr, 4, 4);
        $hdrLen = 8;
        if ($sz === 1) {
            $ext = fread($fin, 8);
            if (strlen($ext) < 8) break;
            $hi = unpack('N', substr($ext, 0, 4));
            $lo = unpack('N', substr($ext, 4, 4));
            $sz = $hi[1] * 4294967296 + $lo[1];
            $hdrLen = 16;
        } elseif ($sz === 0) {
            $sz = $size - $off;
        }
        if ($sz < $hdrLen || $off + $sz > $size + 1) break;
        if (!preg_match('/^[a-zA-Z0-9]{4}$/', $type)) break;
        $boxes[] = ['type' => $type, 'off' => $off, 'size' => $sz, 'hdr' => $hdrLen];
        if ($sz <= 0) break;
        $off += $sz;
    }
    if (count($boxes) < 2) {
        fclose($fin);
        return false;
    }
    $moovIdx = -1;
    $mdatIdx = -1;
    foreach ($boxes as $i => $b) {
        if ($b['type'] === 'moov' && $moovIdx === -1) $moovIdx = $i;
        if ($b['type'] === 'mdat' && $mdatIdx === -1) $mdatIdx = $i;
        if ($b['type'] === 'moof' || $b['type'] === 'sidx') {
            fclose($fin);
            return false;
        }
    }
    if ($moovIdx === -1 || $mdatIdx === -1) {
        fclose($fin);
        return false;
    }
    if ($moovIdx < $mdatIdx) {
        fclose($fin);
        return true;
    }
    $moovBox = $boxes[$moovIdx];
    if ($moovBox['size'] > 100 * 1024 * 1024) {
        fclose($fin);
        return false;
    }
    fseek($fin, $moovBox['off']);
    $moov = fread($fin, $moovBox['size']);
    if (strlen($moov) !== $moovBox['size'] || hero_fs_has_mvex($moov)) {
        fclose($fin);
        return false;
    }
    hero_fs_patch($moov, $moovBox['size']);
    $tmp = $abs . '.faststart.tmp';
    $fout = fopen($tmp, 'wb');
    if (!$fout) {
        fclose($fin);
        return false;
    }
    $ok = true;
    $wroteMoov = false;
    foreach ($boxes as $i => $b) {
        if ($i === $moovIdx) continue;
        if (!$wroteMoov && $b['type'] === 'mdat') {
            if (fwrite($fout, $moov) === false) {
                $ok = false;
                break;
            }
            $wroteMoov = true;
        }
        fseek($fin, $b['off']);
        $head = fread($fin, $b['hdr']);
        if (strlen($head) !== $b['hdr'] || fwrite($fout, $head) === false) {
            $ok = false;
            break;
        }
        if (!hero_fs_copy($fin, $fout, $b['size'] - $b['hdr'])) {
            $ok = false;
            break;
        }
    }
    if ($ok && !$wroteMoov && fwrite($fout, $moov) === false) $ok = false;
    fclose($fin);
    fflush($fout);
    fclose($fout);
    if (!$ok) {
        @unlink($tmp);
        return false;
    }
    clearstatcache(true, $tmp);
    if (filesize($tmp) !== $size) {
        @unlink($tmp);
        return false;
    }
    if (!rename($tmp, $abs)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/* ============ Logo (restoran loqosu, hero mərkəzində) ============
 * assets/img/logo.<ext> kimi saxlanılır, yolu menyunun
 * restaurant.logo sahəsinə yazılır — vitrin story-halqa ilə göstərir. */

define('LOGO_MAX_BYTES', 2 * 1024 * 1024);

function logo_rel(string $slug): string
{
    $m = rest_load_menu($slug);
    return trim((string)($m['restaurant']['logo'] ?? ''));
}

function logo_abs(string $slug): ?string
{
    $rel = logo_rel($slug);
    if ($rel === '') return null;
    $abs = GLOMENU_ROOT . '/' . $slug . '/' . $rel;
    return is_file($abs) ? $abs : null;
}

/** Loqo şəklini yoxlayıb saxla + menyuya yaz. ['ok'=>bool,'error'=>?] */
function logo_upload(string $slug): array
{
    if (!isset($_FILES['logofile'])) {
        return ['ok' => false, 'error' => 'Fayl göndərilmədi.'];
    }
    $f = $_FILES['logofile'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST)) {
        return ['ok' => false, 'error' => 'Fayl server limitini keçir (maks 2MB). Daha kiçik şəkil seçin.'];
    }
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        if (($f['error'] ?? 0) === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'error' => 'Şəkil seçilməyib.'];
        return ['ok' => false, 'error' => 'Yükləmə xətası (kod ' . (int)$f['error'] . ')'];
    }
    if (($f['size'] ?? 0) > LOGO_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Şəkil çox böyükdür (maks 2MB).'];
    }
    if (($f['size'] ?? 0) <= 0) return ['ok' => false, 'error' => 'Boş fayl göndərildi.'];
    $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'error' => 'Yalnız JPG, PNG və ya WEBP olar.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !@getimagesize($f['tmp_name'])) {
        return ['ok' => false, 'error' => 'Fayl şəkil deyil (' . $mime . ').'];
    }
    $dir = GLOMENU_ROOT . '/' . $slug . '/assets/img';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        return ['ok' => false, 'error' => 'Qovluq yaradılmadı.'];
    }
    $oldRel = logo_rel($slug);
    $newRel = 'assets/img/logo.' . $ext;
    $dest = $dir . '/logo.' . $ext;
    if ($oldRel !== '' && $oldRel !== $newRel) @unlink(GLOMENU_ROOT . '/' . $slug . '/' . $oldRel);
    if (!@move_uploaded_file($f['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Fayl saxlanmadı.'];
    }
    $m = rest_load_menu($slug);
    $m['restaurant']['logo'] = $newRel;
    rest_save_menu($slug, $m);
    return ['ok' => true, 'error' => null];
}

function logo_delete(string $slug): void
{
    $oldRel = logo_rel($slug);
    if ($oldRel !== '') @unlink(GLOMENU_ROOT . '/' . $slug . '/' . $oldRel);
    $m = rest_load_menu($slug);
    unset($m['restaurant']['logo']);
    rest_save_menu($slug, $m);
}

/* ============ Layout ============ */

function admin_layout(string $title, string $active, string $body): void
{
    $menu = [
        ['index.php', 'Restoranlar', '◫'],
        ['create.php', 'Yeni restoran', '+'],
        ['menu.php', 'Menyu', '☰'],
        ['design.php', 'Dizayn', '◐'],
        ['import.php', 'İdxal', '⇅'],
        ['password.php', 'Şifrə', '⚙'],
    ];
    $flash = take_flash();
    ?>
<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title) ?> — gloMenu</title>
<link rel="stylesheet" href="assets/admin.css?v=4">
<meta name="robots" content="noindex,nofollow">
</head>
<body>
<div class="adm-shell">
  <aside class="adm-side">
    <div class="adm-brand"><span class="adm-g">g</span><span class="adm-dot">.</span></div>
    <nav class="adm-nav">
      <?php foreach ($menu as $m): ?>
        <a href="<?= $m[0] ?>" class="adm-link<?= $active === $m[0] ? ' on' : '' ?>">
          <span class="adm-ic"><?= $m[2] ?></span><?= $m[1] ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side-b">
      <a href="../" class="adm-link" target="_blank">Sayta bax</a>
      <a href="logout.php" class="adm-link dim">Çıxış</a>
    </div>
  </aside>
  <main class="adm-main">
    <header class="adm-head">
      <h1><?= esc($title) ?></h1>
      <?php if ($flash): ?><div class="adm-flash <?= esc($flash['type']) ?>"><?= esc($flash['msg']) ?></div><?php endif; ?>
    </header>
    <?= $body ?>
  </main>
</div>
</body>
</html>
<?php
}
