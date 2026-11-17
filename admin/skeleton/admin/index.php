<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$menu = load_menu();
$catNames = [];
foreach ($menu['categories'] as $c) $catNames[$c['id']] = $c['name'];
$defPass = is_default_password();

$body = '<div class="adm-stats">';
$body .= '<div class="adm-stat"><b>' . count($menu['items']) . '</b><span>Məhsul</span></div>';
$body .= '<div class="adm-stat"><b>' . count($menu['categories']) . '</b><span>Kateqoriya</span></div>';
$body .= '<div class="adm-stat"><b>' . count($menu['items']) . '</b><span>Toplam</span></div>';
$body .= '</div>';

if ($defPass) {
    $body .= '<div class="adm-flash warn">Standart şifrə aktivdir. <a href="password.php">Şifrəni dəyiş</a></div>';
}

$body .= '<div class="adm-card"><div class="adm-card-h"><h2>Bütün məhsullar</h2><a class="adm-btn primary" href="product.php">+ Yeni məhsul</a></div>';
$body .= '<table class="adm-tbl"><thead><tr><th></th><th>Ad</th><th>Kateqoriya</th><th>Qiymət</th><th></th></tr></thead><tbody>';
foreach ($menu['items'] as $it) {
    $body .= '<tr>';
    $body .= '<td>';
    if (!empty($it['img'])) {
        $body .= '<img class="adm-thumb" src="../' . esc($it['img']) . '" alt="">';
    } elseif (!empty($it['poster'])) {
        $body .= '<img class="adm-thumb" src="../' . esc($it['poster']) . '" alt="">';
    } elseif (!empty($it['video'])) {
        $body .= '<video class="adm-thumb" src="../' . esc($it['video']) . '" muted playsinline preload="metadata" style="background:#000"></video>';
    } else {
        $body .= '<span class="adm-noimg">—</span>';
    }
    $body .= '</td>';
    $body .= '<td><b>' . esc($it['name']) . '</b>';
    if (!empty($it['weight'])) $body .= '<br><span class="adm-muted">' . esc($it['weight']) . '</span>';
    $body .= '</td>';
    $body .= '<td>' . esc($catNames[$it['cat']] ?? $it['cat']) . '</td>';
    $body .= '<td>' . esc(number_format((float)$it['price'], 2, ',', ' ')) . ' ₼</td>';
    $body .= '<td class="adm-actions"><a class="adm-btn sm" href="product.php?id=' . (int)$it['id'] . '">Redaktə</a></td>';
    $body .= '</tr>';
}
if (!$menu['items']) {
    $body .= '<tr><td colspan="5" class="adm-empty">Hələ məhsul yoxdur. <a href="product.php">İlk məhsulu əlavə et</a>.</td></tr>';
}
$body .= '</tbody></table></div>';

admin_layout('Məhsullar', 'index.php', $body);
