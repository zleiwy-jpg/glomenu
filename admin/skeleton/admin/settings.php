<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$menu = load_menu();
$r = $menu['restaurant'] ?? [];
$err = '';
$ok = '';
$val = [
    'name'     => $_POST['name']     ?? $r['name'] ?? '',
    'phone'    => $_POST['phone']    ?? $r['phone'] ?? '',
    'address'  => $_POST['address']  ?? $r['address'] ?? '',
    'hours'    => $_POST['hours']    ?? $r['hours'] ?? '',
    'wifi'     => $_POST['wifi']     ?? $r['wifi'] ?? '',
    'currency' => $_POST['currency'] ?? $r['currency'] ?? '₼',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $val['name']     = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 60);
    $val['phone']    = mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 30);
    $val['address']  = mb_substr(trim((string)($_POST['address'] ?? '')), 0, 120);
    $val['hours']    = mb_substr(trim((string)($_POST['hours'] ?? '')), 0, 60);
    $val['wifi']     = mb_substr(trim((string)($_POST['wifi'] ?? '')), 0, 60);
    $val['currency'] = mb_substr(trim((string)($_POST['currency'] ?? '')), 0, 5);
    if ($val['currency'] === '') $val['currency'] = '₼';

    if ($val['name'] === '') {
        $err = 'Restoran adı boş ola bilməz.';
    } else {
        $menu['restaurant'] = array_merge($r, [
            'name'     => $val['name'],
            'phone'    => $val['phone'],
            'address'  => $val['address'],
            'hours'    => $val['hours'],
            'wifi'     => $val['wifi'],
            'currency' => $val['currency'],
        ]);
        if (isset($r['powered_by'])) $menu['restaurant']['powered_by'] = $r['powered_by'];
        save_menu($menu);
        $ok = 'Yadda saxlanıldı.';
    }
}

$body = '';
if ($err) $body .= '<div class="adm-flash err">' . esc($err) . '</div>';
if ($ok) $body .= '<div class="adm-flash ok">' . esc($ok) . '</div>';

$body .= '<form class="adm-card" method="post" action="settings.php">';
$body .= '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
$body .= '<div class="adm-grid">';
$body .= '<label class="adm-fld"><span>Restoran adı *</span><input type="text" name="name" value="' . esc($val['name']) . '" required maxlength="60"></label>';
$body .= '<label class="adm-fld"><span>Valyuta</span><input type="text" name="currency" value="' . esc($val['currency']) . '" maxlength="5"></label>';
$body .= '</div>';
$body .= '<div class="adm-grid">';
$body .= '<label class="adm-fld"><span>Telefon</span><input type="text" name="phone" value="' . esc($val['phone']) . '" maxlength="30"></label>';
$body .= '<label class="adm-fld"><span>Wi-Fi</span><input type="text" name="wifi" value="' . esc($val['wifi']) . '" maxlength="60"></label>';
$body .= '</div>';
$body .= '<label class="adm-fld"><span>Ünvan</span><input type="text" name="address" value="' . esc($val['address']) . '" maxlength="120"></label>';
$body .= '<label class="adm-fld"><span>İş saatları</span><input type="text" name="hours" value="' . esc($val['hours']) . '" maxlength="60"></label>';
$body .= '<button class="adm-btn primary" type="submit">Yadda saxla</button>';
$body .= '</form>';

admin_layout('Tənzimləmələr', 'settings.php', $body);
