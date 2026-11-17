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

function load_menu(): array
{
    $d = json_decode(@file_get_contents(MENU_FILE), true);
    if (!is_array($d)) $d = [];
    $d['restaurant'] = $d['restaurant'] ?? [];
    $d['categories'] = $d['categories'] ?? [];
    $d['items'] = $d['items'] ?? [];
    return $d;
}

function save_menu(array $d): void
{
    $tmp = MENU_FILE . '.tmp';
    file_put_contents($tmp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    rename($tmp, MENU_FILE);
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

function slugify(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['ə' => 'e', 'ı' => 'i', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c', 'ş' => 's', 'ğ' => 'g']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return $s !== '' ? $s : 'mehsul';
}

function find_item(array $items, int $id): ?array
{
    foreach ($items as $it) {
        if ((int)($it['id'] ?? 0) === $id) return $it;
    }
    return null;
}

function next_id(array $items): int
{
    $max = 0;
    foreach ($items as $it) $max = max($max, (int)($it['id'] ?? 0));
    return $max + 1;
}

function delete_media_if_unused(array $items, string $rel): void
{
    if ($rel === '') return;
    foreach ($items as $it) {
        if (($it['img'] ?? '') === $rel || ($it['video'] ?? '') === $rel || ($it['poster'] ?? '') === $rel) return;
    }
    @unlink(GLOMENU_ROOT . '/' . $rel);
}

/* Poster (videonun ilk kadrı) üçün clamp köməkçiləri — kart crop X/Y/Zoom */
function cover_clamp_xy($v, int $def = 50): int
{
    if ($v === '' || $v === null) return $def;
    $n = (int)$v;
    if ($n < 0) return 0;
    if ($n > 100) return 100;
    return $n;
}

function cover_clamp_zoom($v, int $def = 100): int
{
    if ($v === '' || $v === null) return $def;
    $n = (int)$v;
    if ($n < 100) return 100;
    if ($n > 250) return 250;
    return $n;
}

function handle_upload(string $field, array $allowedExt, array $allowedMime, int $maxBytes, string $destDir, string $prefix): array
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'name' => null];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Yükləmə xətası (kod ' . (int)$f['error'] . ')'];
    }
    if ($f['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'Fayl çox böyükdür (maks ' . round($maxBytes / 1048576) . 'MB)'];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'İcazəsiz fayl növü (.' . $ext . ')'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        return ['ok' => false, 'error' => 'Fayl tipi uyğun deyil (' . $mime . ')'];
    }
    if ($prefix === 'dish' && !@getimagesize($f['tmp_name'])) {
        return ['ok' => false, 'error' => 'Şəkil faylı oxunmadı'];
    }
    $name = $prefix . '-' . slugify(pathinfo((string)$f['name'], PATHINFO_FILENAME))
        . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!is_dir($destDir) && !@mkdir($destDir, 0777, true)) {
        return ['ok' => false, 'error' => 'Qovluq yaradılmadı.'];
    }
    if (!@move_uploaded_file($f['tmp_name'], rtrim($destDir, '/') . '/' . $name)) {
        return ['ok' => false, 'error' => 'Fayl saxlanmadı.'];
    }
    return ['ok' => true, 'name' => $name];
}

function admin_layout(string $title, string $active, string $body): void
{
    $menu = [
        ['index.php', 'Məhsullar', '◫'],
        ['categories.php', 'Kateqoriyalar', '☰'],
        ['settings.php', 'Tənzimləmələr', '⚙'],
        ['reviews.php', 'Rəylər', '★'],
        ['stats.php', 'Statistika', '◔'],
        ['backup.php', 'Yedək', '⇅'],
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

/* ============ MP4 sürətli başlanğıc (moov əvvələ) ============
 * Yemək videoları yüklənəndə tətbiq olunur — təkrar encode YOXDUR.
 */

/** moov içində mvex varsa fraqmentlidir — toxunmaq olmaz. */
function mp4_fs_has_mvex(string $moov): bool
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

function mp4_fs_is_container(string $t): bool
{
    static $c = ['moov' => 1, 'trak' => 1, 'mdia' => 1, 'minf' => 1, 'dinf' => 1, 'stbl' => 1, 'edts' => 1, 'udta' => 1, 'iprp' => 1, 'tref' => 1, 'meco' => 1];
    return isset($c[$t]);
}

/** stco/co64 ofsetlərinə $shift əlavə et (moov xam datası yerindəcə). */
function mp4_fs_patch(string &$moov, int $shift): void
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
            } elseif (mp4_fs_is_container($tp)) {
                $b = $off + $hdr + ($tp === 'meta' ? 4 : 0);
                if ($b < $off + $sz) $stack[] = [$b, $off + $sz];
            }
            $off += $sz;
        }
    }
}

function mp4_fs_copy($fin, $fout, int $bytes): bool
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
function mp4_faststart(string $abs): bool
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
    if (strlen($moov) !== $moovBox['size'] || mp4_fs_has_mvex($moov)) {
        fclose($fin);
        return false;
    }
    mp4_fs_patch($moov, $moovBox['size']);
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
        if (!mp4_fs_copy($fin, $fout, $b['size'] - $b['hdr'])) {
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
