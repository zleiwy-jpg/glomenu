<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$logFile = GLOMENU_ROOT . '/data/ratings.log';
$ratings = [];
if (is_file($logFile)) {
    $lines = array_filter(array_map('trim', file($logFile)));
    foreach (array_reverse($lines) as $line) {
        $d = json_decode($line, true);
        if (is_array($d)) $ratings[] = $d;
    }
}

$body = '<div class="adm-card"><h2>Rəylər (' . count($ratings) . ')</h2>';
if (!$ratings) {
    $body .= '<p class="adm-empty">Hələ rəy yoxdur.</p>';
} else {
    $body .= '<table class="adm-tbl"><thead><tr><th>Məhsul</th><th>Ulduz</th><th>Masa</th><th>Tarix</th></tr></thead><tbody>';
    foreach ($ratings as $r) {
        $body .= '<tr>';
        $body .= '<td><b>' . esc($r['name'] ?? '—') . '</b></td>';
        $body .= '<td><span class="adm-stars">' . str_repeat('★', (int)($r['stars'] ?? 0)) . '</span></td>';
        $body .= '<td>' . esc($r['table'] ?? '—') . '</td>';
        $body .= '<td class="adm-muted">' . esc($r['date'] ?? '—') . '</td>';
        $body .= '</tr>';
    }
    $body .= '</tbody></table>';
}
$body .= '</div>';

admin_layout('Rəylər', 'reviews.php', $body);
