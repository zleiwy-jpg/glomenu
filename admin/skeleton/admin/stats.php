<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$visitsFile = GLOMENU_ROOT . '/data/visits.json';
$visits = ['total' => 0];
if (is_file($visitsFile)) {
    $v = json_decode(file_get_contents($visitsFile), true);
    if (is_array($v)) $visits = $v;
}

$ratingsFile = GLOMENU_ROOT . '/data/ratings.log';
$ratingCount = 0;
$avgStars = 0;
if (is_file($ratingsFile)) {
    $lines = array_filter(array_map('trim', file($ratingsFile)));
    $total = 0;
    foreach ($lines as $line) {
        $d = json_decode($line, true);
        if (is_array($d) && isset($d['stars'])) {
            $ratingCount++;
            $total += (int)$d['stars'];
        }
    }
    if ($ratingCount > 0) $avgStars = round($total / $ratingCount, 1);
}

$callsFile = GLOMENU_ROOT . '/data/calls.log';
$callCount = 0;
if (is_file($callsFile)) {
    $callCount = count(array_filter(array_map('trim', file($callsFile))));
}

$body = '<div class="adm-stats">';
$body .= '<div class="adm-stat"><b>' . (int)$visits['total'] . '</b><span>Ziyarət</span></div>';
$body .= '<div class="adm-stat"><b>' . $ratingCount . '</b><span>Rəy</span></div>';
$body .= '<div class="adm-stat"><b>' . $avgStars . ' ★</b><span>Orta bal</span></div>';
$body .= '<div class="adm-stat"><b>' . $callCount . '</b><span>Ofisiant çağırışı</span></div>';
$body .= '</div>';

admin_layout('Statistika', 'stats.php', $body);
