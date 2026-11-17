<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$slug = (string)($_GET['r'] ?? ($_POST['r'] ?? ''));
$r = $slug !== '' ? find_restaurant($slug) : null;
if ($slug !== '' && ($r === null || !is_dir(GLOMENU_ROOT . '/' . $slug))) {
    flash('Restoran tapılmadı.', 'err');
    header('Location: menu.php');
    exit;
}

if ($r === null) {
    $list = registry();
    ob_start();
    ?>
    <div class="adm-card">
      <div class="adm-card-h"><h2>Restoran seçin</h2></div>
      <?php if (!$list): ?>
        <p class="adm-empty">Hələ restoran yoxdur. <a href="create.php">İlk restoranı yarat</a>.</p>
      <?php else: ?>
      <table class="adm-tbl">
        <thead><tr><th>Restoran</th><th>Məhsul</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $it):
          $s = (string)($it['slug'] ?? '');
          if (!is_dir(GLOMENU_ROOT . '/' . $s)) continue;
          $m = rest_load_menu($s);
        ?>
          <tr>
            <td><b><?= esc($it['name'] ?? $s) ?></b><br><span class="adm-muted">/<?= esc($s) ?>/</span></td>
            <td><span class="adm-badge"><?= count($m['items']) ?></span></td>
            <td class="adm-actions"><a class="adm-btn sm primary" href="menu.php?r=<?= rawurlencode($s) ?>">Menyunu aç</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php
    $body = ob_get_clean();
    admin_layout('Menyu', 'menu.php', $body);
    exit;
}

$menu = rest_load_menu($slug);
$errors = [];
$counts = [];
foreach ($menu['items'] as $it) {
    $cid = (string)($it['cat'] ?? '');
    $counts[$cid] = ($counts[$cid] ?? 0) + 1;
}
$catNames = [];
foreach ($menu['categories'] as $c) $catNames[$c['id']] = $c['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'cadd') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 40);
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 80);
        if ($name === '') {
            $errors[] = 'Kateqoriya adı boş ola bilməz.';
        } else {
            $id = dish_cat_id($menu['categories'], mb_substr(slugify($name), 0, 24));
            $cat = ['id' => $id, 'name' => $name];
            if ($note !== '') $cat['note'] = $note;
            $menu['categories'][] = $cat;
            rest_save_menu($slug, $menu);
            flash('“' . $name . '” əlavə edildi.');
            header('Location: menu.php?r=' . rawurlencode($slug) . '&tab=cats');
            exit;
        }
    } elseif ($action === 'crename') {
        $id = (string)($_POST['id'] ?? '');
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 40);
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 80);
        $found = false;
        foreach ($menu['categories'] as &$c) {
            if ((string)($c['id'] ?? '') === $id) {
                if ($name === '') {
                    $errors[] = 'Ad boş ola bilməz.';
                } else {
                    $c['name'] = $name;
                    if ($note !== '') $c['note'] = $note;
                    else unset($c['note']);
                    $found = true;
                }
                break;
            }
        }
        unset($c);
        if ($found && !$errors) {
            rest_save_menu($slug, $menu);
            flash('Yadda saxlanıldı.');
            header('Location: menu.php?r=' . rawurlencode($slug) . '&tab=cats');
            exit;
        } elseif (!$found) {
            $errors[] = 'Kateqoriya tapılmadı.';
        }
    } elseif ($action === 'cdelete') {
        $id = (string)($_POST['id'] ?? '');
        $n = $counts[$id] ?? 0;
        if ($n > 0) {
            $errors[] = 'Bu kateqoriyada ' . $n . ' məhsul var. Əvvəlcə məhsulları başqa kateqoriyaya keçirin və ya silin.';
        } else {
            $before = count($menu['categories']);
            $menu['categories'] = array_values(array_filter($menu['categories'], function ($c) use ($id) {
                return (string)($c['id'] ?? '') !== $id;
            }));
            if (count($menu['categories']) === $before) {
                $errors[] = 'Kateqoriya tapılmadı.';
            } else {
                rest_save_menu($slug, $menu);
                flash('Kateqoriya silindi.');
                header('Location: menu.php?r=' . rawurlencode($slug) . '&tab=cats');
                exit;
            }
        }
    } elseif ($action === 'pdelete') {
        $id = (int)($_POST['id'] ?? 0);
        $item = dish_find($menu['items'], $id);
        if ($item === null) {
            $errors[] = 'Məhsul tapılmadı.';
        } else {
            $oldImg = $item['img'] ?? '';
            $oldVideo = $item['video'] ?? '';
            $menu['items'] = array_values(array_filter($menu['items'], function ($it) use ($id) {
                return (int)($it['id'] ?? 0) !== $id;
            }));
            if ($oldImg !== '') dish_delete_media($slug, $menu['items'], $oldImg);
            if ($oldVideo !== '') dish_delete_media($slug, $menu['items'], $oldVideo);
            rest_save_menu($slug, $menu);
            flash('Məhsul silindi.');
            header('Location: menu.php?r=' . rawurlencode($slug));
            exit;
        }
    }
}

$tab = ($_GET['tab'] ?? '') === 'cats' ? 'cats' : 'dishes';

ob_start();
$flash = take_flash();
if ($flash): ?><div class="adm-flash <?= esc($flash['type']) ?>"><?= esc($flash['msg']) ?></div><?php endif; ?>
<?php foreach ($errors as $e): ?><div class="adm-flash err"><?= esc($e) ?></div><?php endforeach; ?>
<div class="ds-topbar">
  <div><b>“<?= esc($r['name'] ?? $slug) ?>”</b> <span class="adm-muted">/<?= esc($slug) ?>/</span></div>
  <div class="ds-topactions">
    <a class="adm-btn sm" href="menu.php">← Restoranlar</a>
    <a class="adm-btn sm" href="../<?= rawurlencode($slug) ?>/" target="_blank">Sayta bax</a>
  </div>
</div>

<div class="ds-paneltabs" role="tablist" aria-label="Menyu bölmələri">
  <button type="button" class="ds-tabbtn<?= $tab === 'dishes' ? ' on' : '' ?>" data-tab="dishes" role="tab">Məhsullar (<?= count($menu['items']) ?>)</button>
  <button type="button" class="ds-tabbtn<?= $tab === 'cats' ? ' on' : '' ?>" data-tab="cats" role="tab">Kateqoriyalar (<?= count($menu['categories']) ?>)</button>
</div>

<section class="adm-card ds-panel<?= $tab === 'dishes' ? ' on' : '' ?>" data-panel="dishes">
  <div class="adm-card-h">
    <h2>Məhsullar</h2>
    <a class="adm-btn primary sm" href="product.php?r=<?= rawurlencode($slug) ?>">+ Yeni məhsul</a>
  </div>
  <table class="adm-tbl">
    <thead><tr><th></th><th>Ad</th><th>Kateqoriya</th><th>Qiymət</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($menu['items'] as $it): ?>
      <tr>
        <td><?php if (!empty($it['img'])): ?><img class="adm-thumb" src="../<?= rawurlencode($slug) ?>/<?= esc($it['img']) ?>" alt=""><?php elseif (!empty($it['video'])): ?><span class="adm-badge">▶ video</span><?php else: ?><span class="adm-noimg">—</span><?php endif; ?></td>
        <td><b><?= esc($it['name'] ?? '') ?></b><?php if (!empty($it['weight'])): ?><br><span class="adm-muted"><?= esc($it['weight']) ?></span><?php endif; ?></td>
        <td><?= esc($catNames[$it['cat']] ?? ($it['cat'] ?? '')) ?></td>
        <td><?= esc(number_format((float)($it['price'] ?? 0), 2, ',', ' ')) ?> ₼</td>
        <td class="adm-actions">
          <a class="adm-btn sm" href="product.php?r=<?= rawurlencode($slug) ?>&id=<?= (int)($it['id'] ?? 0) ?>">Redaktə</a>
          <form method="post" action="menu.php?r=<?= rawurlencode($slug) ?>" onsubmit="return confirm('“<?= esc($it['name'] ?? '') ?>” silinsin?');">
            <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
            <input type="hidden" name="r" value="<?= esc($slug) ?>">
            <input type="hidden" name="action" value="pdelete">
            <input type="hidden" name="id" value="<?= (int)($it['id'] ?? 0) ?>">
            <button class="adm-btn sm danger" type="submit">Sil</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$menu['items']): ?>
      <tr><td colspan="5" class="adm-empty">Hələ məhsul yoxdur. <a href="product.php?r=<?= rawurlencode($slug) ?>">İlk məhsulu əlavə et</a>.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</section>

<section class="adm-card ds-panel<?= $tab === 'cats' ? ' on' : '' ?>" data-panel="cats">
  <h2>Yeni kateqoriya</h2>
  <form method="post" action="menu.php?r=<?= rawurlencode($slug) ?>&tab=cats">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <input type="hidden" name="r" value="<?= esc($slug) ?>">
    <input type="hidden" name="action" value="cadd">
    <div class="adm-grid">
      <label class="adm-fld"><span>Ad *</span><input type="text" name="name" required maxlength="40"></label>
      <label class="adm-fld"><span>Qeyd (istəyə bağlı)</span><input type="text" name="note" maxlength="80"></label>
    </div>
    <button class="adm-btn primary sm" type="submit">+ Əlavə et</button>
  </form>
  <table class="adm-tbl" style="margin-top:18px">
    <thead><tr><th>Kateqoriya</th><th>Məhsul</th><th>Qeyd</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($menu['categories'] as $c):
      $cid = (string)($c['id'] ?? '');
      $cnt = $counts[$cid] ?? 0;
    ?>
      <tr>
        <td><b><?= esc($c['name']) ?></b><br><span class="adm-muted">id: <?= esc($cid) ?></span></td>
        <td><span class="adm-badge"><?= (int)$cnt ?></span></td>
        <td class="adm-muted"><?= esc($c['note'] ?? '—') ?></td>
        <td class="adm-actions">
          <form method="post" action="menu.php?r=<?= rawurlencode($slug) ?>&tab=cats" class="adm-inline">
            <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
            <input type="hidden" name="r" value="<?= esc($slug) ?>">
            <input type="hidden" name="action" value="crename">
            <input type="hidden" name="id" value="<?= esc($cid) ?>">
            <input type="text" name="name" value="<?= esc($c['name']) ?>" maxlength="40" required placeholder="Ad" aria-label="Yeni ad">
            <input type="text" name="note" value="<?= esc($c['note'] ?? '') ?>" maxlength="80" placeholder="Qeyd" aria-label="Qeyd">
            <button class="adm-btn sm" type="submit">Saxla</button>
          </form>
          <form method="post" action="menu.php?r=<?= rawurlencode($slug) ?>&tab=cats" onsubmit="return confirm('“<?= esc($c['name']) ?>” silinsin?');">
            <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
            <input type="hidden" name="r" value="<?= esc($slug) ?>">
            <input type="hidden" name="action" value="cdelete">
            <input type="hidden" name="id" value="<?= esc($cid) ?>">
            <button class="adm-btn sm danger" type="submit" <?= $cnt ? 'disabled title="Əvvəlcə məhsulları köçürün"' : '' ?>>Sil</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$menu['categories']): ?>
      <tr><td colspan="4" class="adm-empty">Hələ kateqoriya yoxdur.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</section>

<script>
(function(){
  var btns = Array.prototype.slice.call(document.querySelectorAll('.ds-tabbtn'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('.ds-panel'));
  function openTab(name, push){
    btns.forEach(function(b){ b.classList.toggle('on', b.getAttribute('data-tab') === name); });
    panels.forEach(function(p){ p.classList.toggle('on', p.getAttribute('data-panel') === name); });
    if(push){ try{ history.replaceState(null, '', 'menu.php?r=<?= rawurlencode($slug) ?>&tab=' + name); }catch(e){} }
  }
  btns.forEach(function(b){ b.addEventListener('click', function(){ openTab(b.getAttribute('data-tab'), true); }); });
})();
</script>
<?php
$body = ob_get_clean();
admin_layout('Menyu — ' . ($r['name'] ?? $slug), 'menu.php', $body);
