<?php
// gloMenu — Restoranı ZIP kimi ixrac et (ad, qiymət, video, şəkil — hamısı birgə).
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/backup-lib.php';
require_login();

$slug = (string)($_GET['r'] ?? '');
$r = $slug !== '' ? find_restaurant($slug) : null;
if ($r === null || !is_dir(GLOMENU_ROOT . '/' . $slug)) {
    flash('Restoran tapılmadı.', 'err');
    header('Location: index.php');
    exit;
}

@set_time_limit(0);

$menu = rest_load_menu($slug);
$name = (string)($r['name'] ?? ($menu['restaurant']['name'] ?? $slug));
$folder = backup_safe_folder($name, $slug);
$files = backup_collect_files($slug, $folder);

// Sayğaclar (məlumat üçün).
$imgs = [];
$vids = [];
foreach ($menu['items'] as $it) {
    if (trim((string)($it['img'] ?? '')) !== '') $imgs[] = $it['img'];
    if (trim((string)($it['video'] ?? '')) !== '') $vids[] = $it['video'];
}
$imgs = array_values(array_unique($imgs));
$vids = array_values(array_unique($vids));
$hero = is_file(GLOMENU_ROOT . '/' . $slug . '/assets/vid/hero.mp4');

$manifest = [
    'format' => 'glomenu-backup',
    'version' => BACKUP_VERSION,
    'slug' => $slug,
    'name' => $name,
    'exported_at' => date('c'),
    'counts' => [
        'items' => count($menu['items']),
        'categories' => count($menu['categories']),
        'images' => count($imgs),
        'videos' => count($vids) + ($hero ? 1 : 0),
        'files' => count($files),
    ],
];

$tmpZip = sys_get_temp_dir() . '/glomenu-' . $slug . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.zip';
$res = backup_build_zip($slug, $folder, $tmpZip, $files, $manifest);
if (!$res['ok'] || !is_file($tmpZip)) {
    flash('İxrac alınmadı: ' . ($res['error'] ?? 'naməlum xəta'), 'err');
    header('Location: index.php');
    exit;
}

$zipName = $folder . '-' . date('Ymd-Hi') . '.zip';
$size = filesize($tmpZip);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9._-]+/i', '-', $zipName) . '"');
header('Content-Length: ' . $size);
header('Cache-Control: no-store');
header('X-Backup-Restaurant: ' . $slug);

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
