<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$err = '';
$ok = '';
$d = admin_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    $newPass = (string)($_POST['newpass'] ?? '');

    if ($user === '' || mb_strlen($newPass) < 6) {
        $err = 'İstifadəçi adı boş ola bilməz, yeni şifrə minimum 6 simvol olmalıdır.';
    } elseif (!password_verify($pass, $d['password_hash'] ?? '')) {
        $err = 'Hazırkı şifrə yanlışdır.';
    } else {
        $d['user'] = $user;
        $d['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
        save_admin_data($d);
        $ok = 'Şifrə yeniləndi.';
    }
}

$body = '';
if ($err) $body .= '<div class="adm-flash err">' . esc($err) . '</div>';
if ($ok) $body .= '<div class="adm-flash ok">' . esc($ok) . '</div>';

$body .= '<form class="adm-card" method="post" action="password.php">';
$body .= '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
$body .= '<label class="adm-fld"><span>Yeni istifadəçi adı</span><input type="text" name="user" value="' . esc($d['user'] ?? 'admin') . '" required></label>';
$body .= '<label class="adm-fld"><span>Hazırkı şifrə</span><input type="password" name="pass" required></label>';
$body .= '<label class="adm-fld"><span>Yeni şifrə (min 6)</span><input type="password" name="newpass" required minlength="6"></label>';
$body .= '<button class="adm-btn primary" type="submit">Yenilə</button>';
$body .= '</form>';

admin_layout('Şifrə', 'password.php', $body);
