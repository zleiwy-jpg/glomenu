<?php
// gloMenu — Yedək (import/export) ortaq köməkçiləri (BOM-suz saxla)
declare(strict_types=1);

define('BACKUP_VERSION', 1);
define('BACKUP_MAX_FILES', 3000);
define('BACKUP_MAX_TOTAL_BYTES', 1073741824); // 1GB (açılmış halda)

/** Restoran adı → ZIP daxilində təhlükəsiz qovluq adı. */
function backup_safe_folder(string $name, string $slug): string
{
    $s = mb_strtolower(trim($name) !== '' ? $name : $slug, 'UTF-8');
    $s = strtr($s, ['ə' => 'e', 'ı' => 'i', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c', 'ş' => 's', 'ğ' => 'g']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    if ($s === '') $s = $slug;
    return mb_substr($s, 0, 40);
}

/** İnsan oxunaqlı ölçü. */
function backup_fmt_size($bytes): string
{
    $b = (float)$bytes;
    if ($b < 1024) return (int)$b . ' B';
    if ($b < 1048576) return number_format($b / 1024, 1, ',', ' ') . ' KB';
    if ($b < 1073741824) return number_format($b / 1048576, 1, ',', ' ') . ' MB';
    return number_format($b / 1073741824, 2, ',', ' ') . ' GB';
}

/** ZIP-ə əlavə ediləcək faylları topla: [diskYolu => zipDaxiliYol]. */
function backup_collect_files(string $slug, string $folder): array
{
    $root = GLOMENU_ROOT . '/' . $slug;
    $files = [];

    foreach (['data/menu.json', 'data/design.json', 'data/lang.json'] as $rel) {
        if (is_file($root . '/' . $rel)) $files[$root . '/' . $rel] = $folder . '/' . $rel;
    }

    // Bütün media ağacları (yemək şəkilləri/videoları, loqo, hero).
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

/** Yedək ZIP-i qur. ['ok'=>bool,'error'=>?,'counts'=>[...]] */
function backup_build_zip(string $slug, string $folder, string $tmpZip, array $files, array $manifest): array
{
    @unlink($tmpZip);
    $dir = dirname($tmpZip);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    // 1) ZipArchive (aktivdirsə — ən sürətli və yaddaşa qənaətli).
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString($folder . '/export.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            foreach ($files as $abs => $rel) {
                $zip->addFile($abs, $rel);
            }
            $ok = $zip->close();
            if ($ok && is_file($tmpZip)) return ['ok' => true, 'error' => null];
            @unlink($tmpZip);
            return ['ok' => false, 'error' => 'ZIP yazılmadı.'];
        }
    }

    // 2) Ehtiyat: PharData (php_zip söndürülüdürsə belə işləyir).
    try {
        $ph = new PharData($tmpZip);
        $ph->addFromString($folder . '/export.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        foreach ($files as $abs => $rel) {
            $ph->addFile($abs, $rel);
        }
        unset($ph);
        if (is_file($tmpZip)) return ['ok' => true, 'error' => null];
        return ['ok' => false, 'error' => 'ZIP yazılmadı (Phar).'];
    } catch (Throwable $e) {
        @unlink($tmpZip);
        return ['ok' => false, 'error' => 'ZIP yazılmadı: ' . $e->getMessage()];
    }
}

/** ZIP daxili yol təhlükəsizdirmi (zip-slip qoruması). */
function backup_entry_safe(string $entry): bool
{
    $e = str_replace('\\', '/', $entry);
    if ($e === '' || $e[0] === '/' || preg_match('/^[a-zA-Z]:/', $e)) return false;
    foreach (explode('/', $e) as $part) {
        if ($part === '..') return false;
    }
    return true;
}

/** Media/JSON faylına icazə varmı (icra edilə bilənləri kənarlaşdır). */
function backup_file_allowed(string $rel): bool
{
    $rel = strtolower(str_replace('\\', '/', $rel));
    if (substr($rel, -1) === '/') return false;
    if (strpos($rel, 'data/admin.json') !== false) return false;
    if (preg_match('/\.(php|phtml|phar|htaccess|ini|sh|exe|bat)$/', $rel)) return false;
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if ($ext === 'json') return (strpos($rel, 'data/') !== false || basename($rel) === 'export.json');
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'mp4'], true);
}

/**
 * ZIP-i $dest qovluğuna təhlükəsiz aç.
 * ['ok'=>bool,'error'=>?,'files'=>int,'bytes'=>int]
 */
function backup_extract_zip(string $zipPath, string $dest): array
{
    @mkdir($dest, 0777, true);
    $count = 0;
    $bytes = 0;

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) return ['ok' => false, 'error' => 'ZIP açıla bilmədi.', 'files' => 0, 'bytes' => 0];
        $n = $zip->numFiles;
        if ($n > BACKUP_MAX_FILES) {
            $zip->close();
            return ['ok' => false, 'error' => 'ZIP-də fayl sayı həddən çoxdur (' . $n . ').', 'files' => 0, 'bytes' => 0];
        }
        for ($i = 0; $i < $n; $i++) {
            $st = $zip->statIndex($i);
            if ($st === false) continue;
            $name = (string)($st['name'] ?? '');
            if ($name === '' || substr($name, -1) === '/') continue;
            if (!backup_entry_safe($name) || !backup_file_allowed($name)) continue;
            $size = (int)($st['size'] ?? 0);
            $bytes += $size;
            if ($bytes > BACKUP_MAX_TOTAL_BYTES) {
                $zip->close();
                return ['ok' => false, 'error' => 'Arxiv həddən böyükdür (1GB+).', 'files' => 0, 'bytes' => 0];
            }
            $target = $dest . '/' . $name;
            $tdir = dirname($target);
            if (!is_dir($tdir)) @mkdir($tdir, 0777, true);
            $src = $zip->getStream($name);
            if (!$src) continue;
            $out = @fopen($target, 'wb');
            if (!$out) {
                fclose($src);
                continue;
            }
            while (!feof($src)) {
                $chunk = fread($src, 1048576);
                if ($chunk === false || $chunk === '') break;
                fwrite($out, $chunk);
            }
            fclose($src);
            fclose($out);
            $count++;
        }
        $zip->close();
        return ['ok' => $count > 0, 'error' => $count > 0 ? null : 'ZIP-də uyğun fayl tapılmadı.', 'files' => $count, 'bytes' => $bytes];
    }

    // Ehtiyat: PharData ilə aç.
    try {
        $ph = new PharData($zipPath);
        foreach (new RecursiveIteratorIterator($ph) as $f) {
            if ($f->isDir()) continue;
            $inner = str_replace('\\', '/', substr($f->getPathname(), strlen('phar://' . $zipPath . '/')));
            if (!backup_entry_safe($inner) || !backup_file_allowed($inner)) continue;
            $bytes += $f->getSize();
            if ($bytes > BACKUP_MAX_TOTAL_BYTES) {
                return ['ok' => false, 'error' => 'Arxiv həddən böyükdür (1GB+).', 'files' => 0, 'bytes' => 0];
            }
            $count++;
            if ($count > BACKUP_MAX_FILES) {
                return ['ok' => false, 'error' => 'ZIP-də fayl sayı həddən çoxdur.', 'files' => 0, 'bytes' => 0];
            }
            $target = $dest . '/' . $inner;
            $tdir = dirname($target);
            if (!is_dir($tdir)) @mkdir($tdir, 0777, true);
            @copy($f->getPathname(), $target);
        }
        unset($ph);
        return ['ok' => $count > 0, 'error' => $count > 0 ? null : 'ZIP-də uyğun fayl tapılmadı.', 'files' => $count, 'bytes' => $bytes];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'ZIP açıla bilmədi: ' . $e->getMessage(), 'files' => 0, 'bytes' => 0];
    }
}

/** Açılmış qovluqda yedək kökünü tap (içində data/menu.json və ya menu.json olan). */
function backup_find_root(string $dest): ?string
{
    if (is_file($dest . '/data/menu.json')) return $dest;
    if (is_file($dest . '/menu.json')) return $dest;
    foreach (@scandir($dest) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dest . '/' . $f;
        if (!is_dir($p)) continue;
        if (is_file($p . '/data/menu.json')) return $p;
        if (is_file($p . '/menu.json')) return $p;
        // Bir qat daha dərin (bəzi arxivləyicilər əlavə qovluq qoyur).
        foreach (@scandir($p) ?: [] as $g) {
            if ($g === '.' || $g === '..') continue;
            $q = $p . '/' . $g;
            if (is_dir($q) && (is_file($q . '/data/menu.json') || is_file($q . '/menu.json'))) return $q;
        }
    }
    return null;
}

/** Yedək kökündən menyu/dizayn/dil və media siyahısını oxu və yoxla. */
function backup_read(string $root): array
{
    $menuPath = is_file($root . '/data/menu.json') ? $root . '/data/menu.json' : (is_file($root . '/menu.json') ? $root . '/menu.json' : null);
    if ($menuPath === null) return ['ok' => false, 'error' => 'Arxivdə menu.json tapılmadı.'];
    $menu = json_decode(@file_get_contents($menuPath), true);
    if (!is_array($menu)) return ['ok' => false, 'error' => 'menu.json oxunmadı (zədəlidir).'];

    $menu += ['restaurant' => [], 'categories' => [], 'items' => []];
    if (!is_array($menu['restaurant']) || !is_array($menu['categories']) || !is_array($menu['items'])) {
        return ['ok' => false, 'error' => 'menu.json strukturu yanlışdır.'];
    }
    if (count($menu['items']) > 5000 || count($menu['categories']) > 500) {
        return ['ok' => false, 'error' => 'Menyu həddən böyükdür.'];
    }

    $manifest = [];
    foreach (['export.json', 'data/export.json'] as $m) {
        if (is_file($root . '/' . $m)) {
            $d = json_decode(@file_get_contents($root . '/' . $m), true);
            if (is_array($d)) {
                $manifest = $d;
                break;
            }
        }
    }

    $design = null;
    foreach ([$root . '/data/design.json'] as $dp) {
        if (is_file($dp)) {
            $d = json_decode(@file_get_contents($dp), true);
            if (is_array($d)) $design = $d;
        }
    }
    $lang = null;
    foreach ([$root . '/data/lang.json'] as $lp) {
        if (is_file($lp)) {
            $d = json_decode(@file_get_contents($lp), true);
            if (is_array($d)) $lang = $d;
        }
    }

    // Media faylları (icazəli uzantılar, mövcudluq yoxlanılır).
    $media = [];
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
            if (backup_file_allowed($rel)) $media[] = [$abs, $rel];
        }
    }

    // Köhnə formatla uyğunluq: menyuda img/video yolu var, amma fayl yoxdursa — xəbərdarlıq say.
    $missing = 0;
    foreach ($menu['items'] as $it) {
        foreach (['img', 'video'] as $k) {
            $rel = trim((string)($it[$k] ?? ''));
            if ($rel !== '' && !is_file($root . '/' . $rel)) $missing++;
        }
    }

    return [
        'ok' => true, 'error' => null,
        'manifest' => $manifest,
        'menu' => $menu,
        'design' => $design,
        'lang' => $lang,
        'media' => $media,
        'missing_media' => $missing,
    ];
}

/** Ağacı təhlükəsiz kopyala (yalnız icazəli fayllar). Köçürülən say. */
function backup_copy_tree(string $src, string $dst): int
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
        if (is_dir($s)) {
            $n += backup_copy_tree($s, $d);
        } elseif (is_file($s) && backup_file_allowed($f)) {
            if (@copy($s, $d)) $n++;
        }
    }
    closedir($dh);
    return $n;
}

/** Effektiv yükləmə limiti (bayt). */
function backup_upload_limit(): int
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

/** Müvəqqəti yedək qovluğunu sil. */
function backup_rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (@scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_dir($p) && !is_link($p)) backup_rrmdir($p);
        else @unlink($p);
    }
    @rmdir($dir);
}
