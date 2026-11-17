<?php
// gloMenu — Yedək: ixrac (ZIP yüklə) + idxal (ZIP-dən bərpa). (BOM-suz saxla)
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

define('BK_MAX_FILES', 3000);
define('BK_MAX_TOTAL_BYTES', 1073741824); // 1GB

function bk_slug(): string { return basename(GLOMENU_ROOT); }

function bk_safe_folder(string $name, string $slug): string
{
    $s = mb_strtolower(trim($name) !== '' ? $name : $slug, 'UTF-8');
    $s = strtr($s, ['ə' => 'e', 'ı' => 'i', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c', 'ş' => 's', 'ğ' => 'g']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    if ($s === '') $s = $slug;
    return mb_substr($s, 0, 40);
}

function bk_fmt_size($b): string
{
    $b = (float)$b;
    if ($b < 1024) return (int)$b . ' B';
    if ($b < 1048576) return number_format($b / 1024, 1, ',', ' ') . ' KB';
    if ($b < 1073741824) return number_format($b / 1048576, 1, ',', ' ') . ' MB';
    return number_format($b / 1073741824, 2, ',', ' ') . ' GB';
}

function bk_upload_limit(): int
{
    $toBytes = function ($v) {
        $v = trim((string)$v);
        $u = strtolower(substr($v, -1));
        $n = (int)$v;
        if ($u === 'g') $n *= 1073741824;
        elseif ($u === 'm') $n *= 1048576;
        elseif ($u === 'k') $n *= 1024;
        return $n;
    };
    $a = $toBytes(@ini_get('upload_max_filesize'));
    $b = $toBytes(@ini_get('post_max_size'));
    $m = min($a > 0 ? $a : PHP_INT_MAX, $b > 0 ? $b : PHP_INT_MAX);
    return $m === PHP_INT_MAX ? 41943040 : $m;
}

function bk_collect(string $folder): array
{
    $root = GLOMENU_ROOT;
    $files = [];
    foreach (['data/menu.json', 'data/design.json', 'data/lang.json'] as $rel) {
        if (is_file($root . '/' . $rel)) $files[$root . '/' . $rel] = $folder . '/' . $rel;
    }
    foreach (['assets/img', 'assets/vid'] as $tree) {
        $base = $root . '/' . $tree;
        if (!is_dir($base)) continue;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $abs = $f->getPathname();
            $rel = $tree . '/' . ltrim(str_replace('\\', '/', substr($abs, strlen($base))), '/');
            $files[$abs] = $folder . '/' . $rel;
        }
    }
    return $files;
}

function bk_entry_safe(string $e): bool
{
    $e = str_replace('\\', '/', $e);
    if ($e === '' || $e[0] === '/' || preg_match('/^[a-zA-Z]:/', $e)) return false;
    foreach (explode('/', $e) as $part) {
        if ($part === '..') return false;
    }
    return true;
}

function bk_file_allowed(string $rel): bool
{
    $rel = strtolower(str_replace('\\', '/', $rel));
    if (substr($rel, -1) === '/') return false;
    if (strpos($rel, 'data/admin.json') !== false) return false;
    if (preg_match('/\.(php|phtml|phar|htaccess|ini|sh|exe|bat)$/', $rel)) return false;
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if ($ext === 'json') return (strpos($rel, 'data/') !== false || basename($rel) === 'export.json');
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'mp4'], true);
}

function bk_copy_tree(string $src, string $dst): int
{
    $n = 0;
    if (!is_dir($src)) return 0;
    @mkdir($dst, 0777, true);
    $dh = @opendir($src);
    if (!$dh) return 0;
    while (($f = readdir($dh)) !== false) {
        if ($f === '.' || $f === '..') continue;
        $s = $src . '/' . $f;
        $d = $dst . '/' . $f;
        if (is_dir($s)) $n += bk_copy_tree($s, $d);
        elseif (is_file($s) && bk_file_allowed($f)) {
            if (@copy($s, $d)) $n++;
        }
    }
    closedir($dh);
    return $n;
}

function bk_rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (@scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_dir($p) && !is_link($p)) bk_rrmdir($p);
        else @unlink($p);
    }
    @rmdir($dir);
}

function bk_clear_media(): void
{
    foreach ([IMG_DIR, VID_DIR] as $d) {
        if (!is_dir($d)) {
            @mkdir($d, 0777, true);
            continue;
        }
        foreach (@scandir($d) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $d . '/' . $f;
            if (is_dir($p) && !is_link($p)) bk_rrmdir($p);
            else @unlink($p);
        }
    }
    @unlink(GLOMENU_ROOT . '/assets/vid/hero.mp4');
    @unlink(GLOMENU_ROOT . '/assets/vid/hero.hevc.mp4');
    $m = load_menu();
    $logo = trim((string)($m['restaurant']['logo'] ?? ''));
    if ($logo !== '') @unlink(GLOMENU_ROOT . '/' . $logo);
}

/* ================= İXRAC (ZIP yüklə) ================= */
if (isset($_GET['dl'])) {
    @set_time_limit(0);
    $menu = load_menu();
    $slug = bk_slug();
    $name = trim((string)($menu['restaurant']['name'] ?? ''));
    if ($name === '') $name = $slug;
    $folder = bk_safe_folder($name, $slug);
    $files = bk_collect($folder);

    $imgs = [];
    $vids = [];
    foreach ($menu['items'] as $it) {
        if (trim((string)($it['img'] ?? '')) !== '') $imgs[] = $it['img'];
        if (trim((string)($it['video'] ?? '')) !== '') $vids[] = $it['video'];
    }
    $manifest = [
        'format' => 'glomenu-backup',
        'version' => 1,
        'slug' => $slug,
        'name' => $name,
        'exported_at' => date('c'),
        'counts' => [
            'items' => count($menu['items']),
            'categories' => count($menu['categories']),
            'images' => count(array_unique($imgs)),
            'videos' => count(array_unique($vids)) + (is_file(GLOMENU_ROOT . '/assets/vid/hero.mp4') ? 1 : 0),
            'files' => count($files),
        ],
    ];
    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $tmpZip = sys_get_temp_dir() . '/glomenu-' . $slug . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.zip';
    @unlink($tmpZip);
    $built = false;
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString($folder . '/export.json', $manifestJson);
            foreach ($files as $abs => $rel) $zip->addFile($abs, $rel);
            $built = $zip->close() && is_file($tmpZip);
        }
    }
    if (!$built) {
        try {
            $ph = new PharData($tmpZip);
            $ph->addFromString($folder . '/export.json', $manifestJson);
            foreach ($files as $abs => $rel) $ph->addFile($abs, $rel);
            unset($ph);
            $built = is_file($tmpZip);
        } catch (Throwable $e) {
            $built = false;
        }
    }
    if (!$built || !is_file($tmpZip)) {
        flash('İxrac alınmadı.', 'err');
        header('Location: backup.php');
        exit;
    }

    $zipName = preg_replace('/[^a-z0-9._-]+/i', '-', $folder . '-' . date('Ymd-Hi') . '.zip');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Cache-Control: no-store');
    $fh = fopen($tmpZip, 'rb');
    if ($fh) {
        while (!feof($fh)) {
            echo fread($fh, 1048576);
            @flush();
        }
        fclose($fh);
    }
    @unlink($tmpZip);
    exit;
}

/* ================= İDXAL (ZIP-dən bərpa) ================= */
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    @set_time_limit(0);
    if (empty($_POST['confirm'])) {
        $err = 'Təsdiq üçün qutunu işarələyin — cari menyu tam əvəz olunacaq.';
    } else {
        $f = $_FILES['zipfile'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $err = ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST))
                ? 'Fayl server limitini keçir (maks ' . bk_fmt_size(bk_upload_limit()) . ').'
                : 'ZIP fayl seçilməyib.';
        } elseif (($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
            $err = 'Yükləmə xətası (kod ' . (int)$f['error'] . ').';
        } elseif (strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION)) !== 'zip') {
            $err = 'Yalnız .zip faylı olar.';
        } else {
            $tmp = sys_get_temp_dir() . '/glomenu-rimp-' . time() . '-' . bin2hex(random_bytes(4));
            @mkdir($tmp . '/x', 0777, true);
            $count = 0;
            $bytes = 0;
            $exErr = null;
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($f['tmp_name']) !== true) {
                    $exErr = 'ZIP açıla bilmədi.';
                } else {
                    $n = $zip->numFiles;
                    if ($n > BK_MAX_FILES) $exErr = 'ZIP-də fayl sayı həddən çoxdur.';
                    else {
                        for ($i = 0; $i < $n; $i++) {
                            $st = $zip->statIndex($i);
                            if ($st === false) continue;
                            $nm = (string)($st['name'] ?? '');
                            if ($nm === '' || substr($nm, -1) === '/') continue;
                            if (!bk_entry_safe($nm) || !bk_file_allowed($nm)) continue;
                            $bytes += (int)($st['size'] ?? 0);
                            if ($bytes > BK_MAX_TOTAL_BYTES) {
                                $exErr = 'Arxiv həddən böyükdür (1GB+).';
                                break;
                            }
                            $target = $tmp . '/x/' . $nm;
                            $tdir = dirname($target);
                            if (!is_dir($tdir)) @mkdir($tdir, 0777, true);
                            $src = $zip->getStream($nm);
                            if (!$src) continue;
                            $out = @fopen($target, 'wb');
                            if (!$out) {
                                fclose($src);
                                continue;
                            }
                            while (!feof($src)) {
                                $ch = fread($src, 1048576);
                                if ($ch === false || $ch === '') break;
                                fwrite($out, $ch);
                            }
                            fclose($src);
                            fclose($out);
                            $count++;
                        }
                    }
                    $zip->close();
                }
            } else {
                try {
                    $ph = new PharData($f['tmp_name']);
                    foreach (new RecursiveIteratorIterator($ph) as $pf) {
                        if ($pf->isDir()) continue;
                        $inner = str_replace('\\', '/', substr($pf->getPathname(), strlen('phar://' . $f['tmp_name'] . '/')));
                        if (!bk_entry_safe($inner) || !bk_file_allowed($inner)) continue;
                        $bytes += $pf->getSize();
                        if ($bytes > BK_MAX_TOTAL_BYTES) {
                            $exErr = 'Arxiv həddən böyükdür (1GB+).';
                            break;
                        }
                        $count++;
                        if ($count > BK_MAX_FILES) {
                            $exErr = 'ZIP-də fayl sayı həddən çoxdur.';
                            break;
                        }
                        $target = $tmp . '/x/' . $inner;
                        $tdir = dirname($target);
                        if (!is_dir($tdir)) @mkdir($tdir, 0777, true);
                        @copy($pf->getPathname(), $target);
                    }
                    unset($ph);
                } catch (Throwable $e) {
                    $exErr = 'ZIP açıla bilmədi: ' . $e->getMessage();
                }
            }

            if ($exErr !== null) {
                $err = $exErr;
                bk_rrmdir($tmp);
            } elseif ($count === 0) {
                $err = 'ZIP-də uyğun fayl tapılmadı.';
                bk_rrmdir($tmp);
            } else {
                // Kökü tap.
                $dest = $tmp . '/x';
                $root = null;
                if (is_file($dest . '/data/menu.json')) $root = $dest;
                elseif (is_file($dest . '/menu.json')) $root = $dest;
                else {
                    foreach (@scandir($dest) ?: [] as $sf) {
                        if ($sf === '.' || $sf === '..') continue;
                        $p = $dest . '/' . $sf;
                        if (!is_dir($p)) continue;
                        if (is_file($p . '/data/menu.json') || is_file($p . '/menu.json')) {
                            $root = $p;
                            break;
                        }
                    }
                }
                if ($root === null) {
                    $err = 'Bu arxiv gloMenu yedəyinə oxşamır (menu.json tapılmadı).';
                    bk_rrmdir($tmp);
                } else {
                    $mp = is_file($root . '/data/menu.json') ? $root . '/data/menu.json' : $root . '/menu.json';
                    $menu = json_decode(@file_get_contents($mp), true);
                    if (!is_array($menu)) {
                        $err = 'menu.json oxunmadı (zədəlidir).';
                        bk_rrmdir($tmp);
                    } else {
                        $menu += ['restaurant' => [], 'categories' => [], 'items' => []];
                        if (!is_array($menu['restaurant']) || !is_array($menu['categories']) || !is_array($menu['items'])) {
                            $err = 'menu.json strukturu yanlışdır.';
                            bk_rrmdir($tmp);
                        } else {
                            // Tətbiq et: menyu + dizayn/dil + media.
                            save_menu($menu);
                            foreach (['design.json', 'lang.json'] as $jf) {
                                $sp = $root . '/data/' . $jf;
                                if (is_file($sp)) {
                                    $d = json_decode(@file_get_contents($sp), true);
                                    if (is_array($d)) @file_put_contents(GLOMENU_ROOT . '/data/' . $jf, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                                }
                            }
                            bk_clear_media();
                            $copied = 0;
                            foreach (['assets/img', 'assets/vid'] as $tree) {
                                if (is_dir($root . '/' . $tree)) $copied += bk_copy_tree($root . '/' . $tree, GLOMENU_ROOT . '/' . $tree);
                            }
                            bk_rrmdir($tmp);
                            flash('Yedək bərpa edildi: ' . count($menu['items']) . ' məhsul, ' . $copied . ' media faylı.');
                            header('Location: index.php');
                            exit;
                        }
                    }
                }
            }
        }
    }
}

/* ================= SƏHİFƏ ================= */
$menu = load_menu();
$slug = bk_slug();
$name = trim((string)($menu['restaurant']['name'] ?? ''));
if ($name === '') $name = $slug;

$imgCount = 0;
$vidCount = 0;
foreach ($menu['items'] as $it) {
    if (trim((string)($it['img'] ?? '')) !== '') $imgCount++;
    if (trim((string)($it['video'] ?? '')) !== '') $vidCount++;
}
$totalBytes = 0;
foreach ([IMG_DIR, VID_DIR] as $d) {
    if (!is_dir($d)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($it as $fl) {
        if ($fl->isFile()) $totalBytes += $fl->getSize();
    }
}
if (is_file(GLOMENU_ROOT . '/assets/vid/hero.mp4')) {
    $totalBytes += (int)filesize(GLOMENU_ROOT . '/assets/vid/hero.mp4');
    $vidCount++;
}

ob_start();
if ($err !== ''): ?><div class="adm-flash err"><?= esc($err) ?></div><?php endif; ?>
<div class="adm-card">
  <div class="adm-card-h">
    <h2>İxrac — ZIP yüklə</h2>
  </div>
  <p class="adm-muted" style="margin:0 0 12px">
    Bütün menyu <b>restoran adı ilə qovluqda</b> gəlir: adlar, qiymətlər, təsvirlər, kateqoriyalar,
    yemək şəkilləri və videoları, loqo və hero videosu — hamısı bir ZIP-də.
  </p>
  <div class="adm-grid">
    <div><span class="adm-muted">Məhsul</span><br><b><?= count($menu['items']) ?></b></div>
    <div><span class="adm-muted">Kateqoriya</span><br><b><?= count($menu['categories']) ?></b></div>
    <div><span class="adm-muted">Şəkilli / videolu</span><br><b><?= (int)$imgCount ?> / <?= (int)$vidCount ?></b></div>
    <div><span class="adm-muted">Media həcmi (təxmini)</span><br><b><?= esc(bk_fmt_size($totalBytes)) ?></b></div>
  </div>
  <div class="adm-form-btns" style="margin-top:14px">
    <a class="adm-btn primary" href="backup.php?dl=1">⇩ “<?= esc($name) ?>” ZIP-ini yüklə</a>
  </div>
</div>

<div class="adm-card">
  <div class="adm-card-h">
    <h2>Idxal — ZIP-dən bərpa</h2>
    <span class="adm-muted">maks <?= esc(bk_fmt_size(bk_upload_limit())) ?></span>
  </div>
  <p class="adm-muted" style="margin:0 0 12px">Əvvəlcədən endirilmiş yedək ZIP-i seçin. <b>Cari menyu tam əvəz olunur</b> (giriş şifrəsi dəyişmir).</p>
  <form method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <label class="adm-fld"><span>Yedək ZIP *</span><input type="file" name="zipfile" accept=".zip" required></label>
    <label style="display:flex;gap:8px;align-items:center;margin:12px 0;font-size:14px">
      <input type="checkbox" name="confirm" value="1" required> Başa düşdüm — cari menyu və media silinib yedəkdəki ilə əvəz olunacaq
    </label>
    <div class="adm-form-btns">
      <button class="adm-btn primary" type="submit">Yedəyi bərpa et</button>
    </div>
  </form>
</div>
<?php
$body = ob_get_clean();
admin_layout('Yedək — İdxal / İxrac', 'backup.php', $body);
