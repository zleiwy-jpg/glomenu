<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$err = '';
$v = ['name' => '', 'slug' => '', 'phone' => '', 'address' => '', 'hours' => '10:00 – 23:00, hər gün', 'wifi' => '', 'user' => 'admin'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (array_keys($v) as $k) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    $slug = $v['slug'] !== '' ? $v['slug'] : slugify($v['name']);
    if ($v['name'] === '') $err = 'Restoran adı mütləqdir.';
    elseif (!valid_slug($slug)) $err = 'Slug yanlışdır (a-z, 0-9, tire, 2–30 simvol).';
    elseif (reserved_slug($slug) || is_dir(GLOMENU_ROOT . '/' . $slug)) $err = 'Bu ad artıq məşğuldur.';
    elseif (find_restaurant($slug) !== null) $err = 'Bu slug artıq qeydiyyatdadır.';
    elseif (mb_strlen($pass) < 6) $err = 'Admin şifrəsi minimum 6 simvol olmalıdır.';
    else {
        try {
            $dst = GLOMENU_ROOT . '/' . $slug;
            rcopy_template(TEMPLATE_DIR, $dst);
            @mkdir($dst . '/data', 0777, true);
            @file_put_contents($dst . '/data/.htaccess', "Require all denied\n");
            write_json_file($dst . '/data/menu.json', [
                'restaurant' => [
                    'name' => $v['name'],
                    'powered_by' => 'gloMenu',
                    'phone' => $v['phone'],
                    'address' => $v['address'],
                    'hours' => $v['hours'],
                    'wifi' => $v['wifi'],
                    'currency' => '₼',
                ],
                'categories' => [],
                'items' => [],
            ]);
            $langSrc = TEMPLATE_DIR . '/data/lang.json';
            if (is_file($langSrc)) @copy($langSrc, $dst . '/data/lang.json');
            write_json_file($dst . '/data/design.json', default_design());
            write_json_file($dst . '/data/admin.json', [
                'user' => ($v['user'] !== '' ? $v['user'] : 'admin'),
                'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            ]);
            $list = registry();
            $list[] = ['slug' => $slug, 'name' => $v['name'], 'created' => date('Y-m-d')];
            save_registry($list);
            flash('“' . $v['name'] . '” yaradıldı: /' . $slug . '/');
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            if (isset($dst) && is_dir($dst)) rrmdir($dst);
            $err = 'Yaradılmadı: ' . $e->getMessage();
        }
    }
    $v['slug'] = $slug ?? '';
}

ob_start();
if ($err !== ''): ?><div class="adm-flash err"><?= esc($err) ?></div><?php endif; ?>
  <form class="adm-card" method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <h2>Restoran məlumatları</h2>
    <div class="adm-grid">
      <label class="adm-fld"><span>Restoran adı *</span><input name="name" id="f_name" value="<?= esc($v['name']) ?>" required maxlength="60" placeholder="Məs. Sahil Restoran"></label>
      <label class="adm-fld"><span>Slug (avtomatik)</span><input name="slug" id="f_slug" value="<?= esc($v['slug']) ?>" maxlength="30" placeholder="sahil-restoran"><span class="adm-muted" style="font-weight:400">Qovluq adı: /<span id="slugPrev"><?= esc($v['slug'] !== '' ? $v['slug'] : '…') ?></span>/ — boş buraxsanız addan yaradılır</span></label>
    </div>
    <div class="adm-grid">
      <label class="adm-fld"><span>Telefon</span><input name="phone" value="<?= esc($v['phone']) ?>" maxlength="30"></label>
      <label class="adm-fld"><span>Ünvan</span><input name="address" value="<?= esc($v['address']) ?>" maxlength="80"></label>
    </div>
    <div class="adm-grid">
      <label class="adm-fld"><span>İş saatları</span><input name="hours" value="<?= esc($v['hours']) ?>" maxlength="40"></label>
      <label class="adm-fld"><span>Wi-Fi</span><input name="wifi" value="<?= esc($v['wifi']) ?>" maxlength="60"></label>
    </div>
    <h2 style="margin-top:8px">Restoran admin girişi</h2>
    <div class="adm-grid">
      <label class="adm-fld"><span>İstifadəçi</span><input name="user" value="<?= esc($v['user']) ?>" maxlength="40"></label>
      <label class="adm-fld"><span>Şifrə * (min 6)</span><input name="pass" type="password" required></label>
    </div>
    <div class="adm-form-btns">
      <button class="adm-btn primary" type="submit">Yarat</button>
      <a class="adm-btn" href="index.php">Ləğv et</a>
    </div>
  </form>
  <script>
  (function(){
    var n = document.getElementById('f_name'), s = document.getElementById('f_slug'), p = document.getElementById('slugPrev'), touched = s.value !== '';
    s.addEventListener('input', function(){ touched = true; p.textContent = s.value || '…'; });
    n.addEventListener('input', function(){
      if (touched) return;
      var v = n.value.toLowerCase()
        .replace(/ə/g,'e').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ü/g,'u').replace(/ç/g,'c').replace(/ş/g,'s').replace(/ğ/g,'g')
        .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,30);
      s.value = v; p.textContent = v || '…';
    });
  })();
  </script>
<?php
$body = ob_get_clean();
admin_layout('Yeni restoran', 'create.php', $body);
