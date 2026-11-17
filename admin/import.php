<?php
// gloMenu — Yedəkdən restoran idxal et (ZIP → yeni restoran).
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/backup-lib.php';
require_login();

$err = '';
$v = ['name' => '', 'slug' => '', 'user' => 'admin', 'overwrite' => ''];
$maxUp = backup_upload_limit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    @set_time_limit(0);
    foreach (['name', 'slug', 'user'] as $k) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $v['overwrite'] = !empty($_POST['overwrite']) ? '1' : '';
    $pass = (string)($_POST['pass'] ?? '');

    $f = $_FILES['zipfile'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST)) {
            $err = 'Fayl server limitini keçir (maks ' . backup_fmt_size($maxUp) . '). Daha kiçik arxiv seçin və ya server limitini artırın.';
        } else {
            $err = 'ZIP fayl seçilməyib.';
        }
    } elseif (($f['error'] ?? 1) !== UPLOAD_ERR_OK) {
        $err = 'Yükləmə xətası (kod ' . (int)$f['error'] . ').';
    } elseif (strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION)) !== 'zip') {
        $err = 'Yalnız .zip faylı olar.';
    } else {
        // 1) ZIP-i müvəqqəti yerə aç.
        $tmp = sys_get_temp_dir() . '/glomenu-imp-' . time() . '-' . bin2hex(random_bytes(4));
        @mkdir($tmp, 0777, true);
        $ex = backup_extract_zip($f['tmp_name'], $tmp . '/x');
        if (!$ex['ok']) {
            $err = $ex['error'] ?? 'ZIP açıla bilmədi.';
            backup_rrmdir($tmp);
        } else {
            $root = backup_find_root($tmp . '/x');
            if ($root === null) {
                $err = 'Bu arxiv gloMenu yedəyinə oxşamır (menu.json tapılmadı).';
                backup_rrmdir($tmp);
            } else {
                $rd = backup_read($root);
                if (!$rd['ok']) {
                    $err = $rd['error'] ?? 'Yedək oxunmadı.';
                    backup_rrmdir($tmp);
                } else {
                    // 2) Ad/slug-u müəyyənləşdir (boşdursa yedəkdən götür).
                    $bkName = trim((string)($rd['manifest']['name'] ?? ($rd['menu']['restaurant']['name'] ?? '')));
                    $bkSlug = trim((string)($rd['manifest']['slug'] ?? ''));
                    if ($v['name'] === '') $v['name'] = $bkName;
                    $slug = $v['slug'] !== '' ? $v['slug'] : slugify($v['name'] !== '' ? $v['name'] : $bkSlug);
                    $v['slug'] = $slug;

                    $existsDir = is_dir(GLOMENU_ROOT . '/' . $slug);
                    $existsReg = find_restaurant($slug) !== null;
                    $overwrite = $v['overwrite'] === '1' && ($existsDir || $existsReg);

                    if ($v['name'] === '') $err = 'Restoran adı mütləqdir.';
                    elseif (!valid_slug($slug)) $err = 'Slug yanlışdır (a-z, 0-9, tire, 2–30 simvol).';
                    elseif (reserved_slug($slug)) $err = 'Bu ad sistem tərəfindən qorunur.';
                    elseif (($existsDir || $existsReg) && !$overwrite) $err = 'Bu slug artıq məşğuldur. Başqa slug yazın və ya “üzərinə yaz”ı işarələyin.';
                    elseif (!$overwrite && mb_strlen($pass) < 6) $err = 'Admin şifrəsi minimum 6 simvol olmalıdır.';
                    else {
                        try {
                            $dst = GLOMENU_ROOT . '/' . $slug;
                            if ($overwrite) {
                                // Üzərinə yaz: menyu + dizayn/dil + media yenilənir, giriş şifrəsi saxlanılır.
                                $menu = $rd['menu'];
                                if ($v['name'] !== '') $menu['restaurant']['name'] = $v['name'];
                                rest_save_menu($slug, $menu);
                                if (is_array($rd['design'])) write_json_file($dst . '/data/design.json', $rd['design']);
                                if (is_array($rd['lang'])) write_json_file($dst . '/data/lang.json', $rd['lang']);
                                foreach (['assets/img', 'assets/vid'] as $tree) {
                                    $sBase = $root . '/' . $tree;
                                    if (is_dir($sBase)) backup_copy_tree($sBase, $dst . '/' . $tree);
                                }
                                // Reyestrdə ad yenilənsin.
                                $list = registry();
                                foreach ($list as &$it) {
                                    if (($it['slug'] ?? '') === $slug) $it['name'] = $v['name'] !== '' ? $v['name'] : ($it['name'] ?? $slug);
                                }
                                unset($it);
                                save_registry($list);
                            } else {
                                // Yeni restoran: şablonu köçür, sonra yedəkdəki ilə əvəz et.
                                rcopy_template(TEMPLATE_DIR, $dst);
                                @mkdir($dst . '/data', 0777, true);
                                @file_put_contents($dst . '/data/.htaccess', "Require all denied\n");
                                $menu = $rd['menu'];
                                $menu['restaurant']['name'] = $v['name'];
                                if (empty($menu['restaurant']['currency'])) $menu['restaurant']['currency'] = '₼';
                                if (empty($menu['restaurant']['powered_by'])) $menu['restaurant']['powered_by'] = 'gloMenu';
                                write_json_file($dst . '/data/menu.json', $menu);
                                if (is_array($rd['design'])) write_json_file($dst . '/data/design.json', $rd['design']);
                                else write_json_file($dst . '/data/design.json', default_design());
                                $langSrc = TEMPLATE_DIR . '/data/lang.json';
                                if (is_array($rd['lang'])) write_json_file($dst . '/data/lang.json', $rd['lang']);
                                elseif (is_file($langSrc)) @copy($langSrc, $dst . '/data/lang.json');
                                write_json_file($dst . '/data/admin.json', [
                                    'user' => ($v['user'] !== '' ? $v['user'] : 'admin'),
                                    'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                                ]);
                                foreach (['assets/img', 'assets/vid'] as $tree) {
                                    $sBase = $root . '/' . $tree;
                                    if (is_dir($sBase)) backup_copy_tree($sBase, $dst . '/' . $tree);
                                }
                                $list = registry();
                                $list[] = ['slug' => $slug, 'name' => $v['name'], 'created' => date('Y-m-d')];
                                save_registry($list);
                            }
                            backup_rrmdir($tmp);
                            $warn = '';
                            if (!empty($rd['missing_media'])) $warn = ' Diqqət: ' . (int)$rd['missing_media'] . ' şəkil/video faylı arxivdə yox idi (yolu menyuda qaldı).';
                            $mediaCount = count($rd['media']);
                            flash('“' . $v['name'] . '” idxal edildi: /' . $slug . '/ — ' . count($rd['menu']['items']) . ' məhsul, ' . $mediaCount . ' media faylı.' . $warn);
                            header('Location: index.php');
                            exit;
                        } catch (Throwable $e) {
                            if (!$overwrite && isset($dst) && is_dir($dst)) rrmdir($dst);
                            $err = 'Idxal alınmadı: ' . $e->getMessage();
                            backup_rrmdir($tmp);
                        }
                    }
                }
            }
        }
    }
}

ob_start();
if ($err !== ''): ?><div class="adm-flash err"><?= esc($err) ?></div><?php endif; ?>
  <div class="adm-card">
    <div class="adm-card-h">
      <h2>ZIP-dən idxal</h2>
      <span class="adm-muted">maks <?= esc(backup_fmt_size($maxUp)) ?></span>
    </div>
    <p class="adm-muted" style="margin:0 0 16px">Export edilmiş yedək ZIP-i seçin — menyular, adlar, qiymətlər, şəkillər və videolar hamısı birlikdə gəlir. ZIP-in içində <b>restoran adı ilə qovluq</b> olur, seçməyinizə ehtiyac yoxdur — sistem özü tapır.</p>
    <form method="post" enctype="multipart/form-data" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <label class="adm-fld"><span>Yedək ZIP *</span><input type="file" name="zipfile" accept=".zip" required></label>
      <div class="adm-grid" style="margin-top:12px">
        <label class="adm-fld"><span>Restoran adı (boşdursa yedəkdən götürülür)</span><input name="name" value="<?= esc($v['name']) ?>" maxlength="60" placeholder="Məs. Sahil Restoran"></label>
        <label class="adm-fld"><span>Slug (boşdursa addan yaradılır)</span><input name="slug" value="<?= esc($v['slug']) ?>" maxlength="30" placeholder="sahil-restoran"></label>
      </div>
      <div class="adm-grid">
        <label class="adm-fld"><span>Admin istifadəçisi (yeni restoran üçün)</span><input name="user" value="<?= esc($v['user']) ?>" maxlength="40"></label>
        <label class="adm-fld"><span>Admin şifrəsi (yeni restoran üçün, min 6)</span><input name="pass" type="password"></label>
      </div>
      <label style="display:flex;gap:8px;align-items:center;margin:12px 0;font-size:14px">
        <input type="checkbox" name="overwrite" value="1" <?= $v['overwrite'] === '1' ? 'checked' : '' ?>> Slug mövcuddursa üzərinə yaz (giriş şifrəsi saxlanılır)
      </label>
      <div class="adm-form-btns">
        <button class="adm-btn primary" type="submit">Idxal et</button>
        <a class="adm-btn" href="index.php">Ləğv et</a>
      </div>
    </form>
  </div>
<?php
$body = ob_get_clean();
admin_layout('Idxal', 'import.php', $body);
