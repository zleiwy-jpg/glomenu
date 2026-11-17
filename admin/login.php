<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
admin_session_start();
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = admin_data();
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    if ($user !== '' && hash_equals($d['user'] ?? 'admin', $user)
        && !empty($d['password_hash']) && password_verify($pass, $d['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        header('Location: index.php');
        exit;
    }
    $err = 'İstifadəçi adı və ya şifrə yanlışdır.';
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin giriş — gloMenu</title>
<link rel="stylesheet" href="assets/admin.css?v=4">
</head>
<body class="adm-login">
<div class="adm-login-box">
  <div class="adm-brand lg"><span class="adm-g">g</span><span class="adm-dot">.</span></div>
  <h1>gloMenu</h1>
  <p class="adm-sub">Restoran admin paneli</p>
  <?php if ($err): ?><div class="adm-flash err"><?= esc($err) ?></div><?php endif; ?>
  <form method="post" action="login.php">
    <label class="adm-fld"><span>İstifadəçi adı</span>
      <input type="text" name="user" autocomplete="username" required autofocus>
    </label>
    <label class="adm-fld"><span>Şifrə</span>
      <input type="password" name="pass" autocomplete="current-password" required>
    </label>
    <button class="adm-btn primary block" type="submit">Daxil ol</button>
  </form>
  <p class="adm-back"><a href="../">← Sayta qayıt</a></p>
</div>
</body>
</html>
