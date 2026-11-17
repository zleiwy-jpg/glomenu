<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/design-lib.php';
require_login();

$DS_DEF = ['theme' => 1, 'loader' => 1, 'video' => 1, 'quick' => 1, 'search' => 1, 'cats' => 1, 'prices' => 1, 'showLang' => 1, 'patterns' => 0, 'photos' => 1, 'topbar' => 1, 'footer' => 1, 'custom' => 0, 'custom_bg' => '', 'custom_accent' => '', 'custom_catbg' => '', 'custom_qicon' => ''];

$slug = (string)($_GET['r'] ?? ($_POST['r'] ?? ''));
$r = $slug !== '' ? find_restaurant($slug) : null;
if ($slug !== '' && ($r === null || !is_dir(GLOMENU_ROOT . '/' . $slug))) {
    flash('Restoran tapılmadı.', 'err');
    header('Location: design.php');
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
        <thead><tr><th>Restoran</th><th>Hero videosu</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $it):
          $s = (string)($it['slug'] ?? '');
          if (!is_dir(GLOMENU_ROOT . '/' . $s)) continue;
          $hi = hero_info($s);
        ?>
          <tr>
            <td><b><?= esc($it['name'] ?? $s) ?></b><br><span class="adm-muted">/<?= esc($s) ?>/</span></td>
            <td><?= $hi ? '<span class="adm-badge">✓ ' . esc(round($hi['size'] / 1048576, 1)) . ' MB</span>' : '<span class="adm-muted">yoxdur</span>' ?></td>
            <td class="adm-actions"><a class="adm-btn sm primary" href="design.php?r=<?= rawurlencode($s) ?>">Dizaynı aç</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php
    $body = ob_get_clean();
    admin_layout('Dizayn', 'design.php', $body);
    exit;
}

$DESIGN_FILE = GLOMENU_ROOT . '/' . $slug . '/data/design.json';
$cur = array_merge($DS_DEF, json_decode(@file_get_contents($DESIGN_FILE), true) ?: []);
if (!is_array($cur)) $cur = $DS_DEF;

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'hero_upload') {
        $res = hero_upload($slug);
        if ($res['ok']) {
            flash('Hero videosu yükləndi — saytda səssiz və loop ilə oynayacaq.');
        } else {
            flash($res['error'] ?? 'Xəta oldu.', 'err');
        }
        header('Location: design.php?r=' . rawurlencode($slug) . '#ds-hero');
        exit;
    }
    if ($action === 'hero_delete') {
        hero_delete($slug);
        flash('Hero videosu silindi — posterdən istifadə olunacaq.');
        header('Location: design.php?r=' . rawurlencode($slug) . '#ds-hero');
        exit;
    }
    if ($action === 'logo_upload') {
        $res = logo_upload($slug);
        if ($res['ok']) {
            flash('Loqo yükləndi — hero mərkəzində story-halqa ilə görünəcək.');
        } else {
            flash($res['error'] ?? 'Xəta oldu.', 'err');
        }
        header('Location: design.php?r=' . rawurlencode($slug) . '#ds-logo');
        exit;
    }
    if ($action === 'logo_delete') {
        logo_delete($slug);
        flash('Loqo silindi.');
        header('Location: design.php?r=' . rawurlencode($slug) . '#ds-logo');
        exit;
    }
    if ($action === 'reset') {
        $tmp = $DESIGN_FILE . '.tmp';
        file_put_contents($tmp, json_encode($DS_DEF, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($tmp, $DESIGN_FILE);
        flash('Dizayn standarta qaytarıldı.');
        header('Location: design.php?r=' . rawurlencode($slug));
        exit;
    }
    $theme = (int)($_POST['theme'] ?? 1);
    if ($theme < 1 || $theme > 8) $theme = 1;
    $flags = ['loader', 'video', 'quick', 'search', 'cats', 'prices', 'showLang', 'patterns', 'photos', 'topbar', 'footer'];
    $new = $cur;
    $new['theme'] = $theme;
    foreach ($flags as $f) $new[$f] = isset($_POST['flag_' . $f]) ? 1 : 0;
    $custom = isset($_POST['custom']) ? 1 : 0;
    $new['custom'] = $custom;
    $colorKeys = array_merge(design_full_keys(), ['custom_catbg', 'custom_qicon']);
    $colors = [];
    foreach ($colorKeys as $k) {
        $v = trim((string)($_POST[$k] ?? ''));
        if ($k === 'custom_catbg' && $v === '') {
            $colors[$k] = '';
            continue;
        }
        if (!design_hex_ok($v)) {
            $err = 'Rəng düzgün deyil: ' . $k . ' (məs. #B91C1C).';
            break;
        }
        $colors[$k] = strtoupper($v);
    }
    if ($err === '') {
        if ($custom) {
            foreach ($colors as $k => $v) $new[$k] = $v;
            $new['custom_bg'] = $colors['c_paper'];
            $new['custom_accent'] = $colors['c_accent'];
        } else {
            foreach (design_full_keys() as $k) unset($new[$k]);
            $new['custom_bg'] = '';
            $new['custom_accent'] = '';
            $new['custom_catbg'] = $colors['custom_catbg'];
            $new['custom_qicon'] = $colors['custom_qicon'] !== '' ? $colors['custom_qicon'] : '#0C0B09';
        }
        $tmp = $DESIGN_FILE . '.tmp';
        file_put_contents($tmp, json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($tmp, $DESIGN_FILE);
        flash('“' . ($r['name'] ?? $slug) . '” dizaynı yadda saxlanıldı.');
        header('Location: design.php?r=' . rawurlencode($slug));
        exit;
    }
}

$eff = design_resolve($cur);
$themes = design_themes();
$themeNames = design_theme_names();
$fullLabels = [
    'c_paper' => 'Arxa fon', 'c_card' => 'Kart fonu', 'c_card2' => 'İkinci kart fonu',
    'c_line' => 'Xətlər', 'c_lineSoft' => 'Yumşaq xətt', 'c_accent' => 'Əsas rəng (düymələr)',
    'c_accentDark' => 'Tünd vurğu', 'c_dark' => 'Tünd fon', 'c_ink' => 'Əsas mətn',
    'c_muted' => 'İkinci mətn', 'c_muted2' => 'Solğun mətn',
];
$flagLabels = [
    'loader' => 'Açılış ekranı', 'video' => 'Hero videosu', 'quick' => 'Tez keçidlər (Wi-Fi/kontakt)',
    'search' => 'Axtarış', 'cats' => 'Kateqoriya menyusu', 'prices' => 'Qiymətlər',
    'showLang' => 'Dil seçimi', 'photos' => 'Yemək şəkilləri', 'topbar' => 'Üst zolaq',
    'footer' => 'Alt bilgi', 'patterns' => 'Nöqtəli fon naxışı',
];
$hi = hero_info($slug);
$heroVer = $hi ? $hi['mtime'] : 0;
$logoRel = logo_rel($slug);
$logoAbs = $logoRel !== '' ? GLOMENU_ROOT . '/' . $slug . '/' . $logoRel : '';
$logoOk = $logoRel !== '' && is_file($logoAbs);
$logoVer = $logoOk ? filemtime($logoAbs) : 0;

ob_start();
if ($err !== ''): ?><div class="adm-flash err"><?= esc($err) ?></div><?php endif; ?>
<div class="ds-topbar">
  <div><b>“<?= esc($r['name'] ?? $slug) ?>”</b> <span class="adm-muted">/<?= esc($slug) ?>/</span></div>
  <div class="ds-topactions">
    <a class="adm-btn sm" href="design.php">← Restoranlar</a>
    <a class="adm-btn sm" href="../<?= rawurlencode($slug) ?>/" target="_blank">Sayta bax</a>
  </div>
</div>

<div class="ds-paneltabs" role="tablist" aria-label="Dizayn bölmələri">
  <button type="button" class="ds-tabbtn on" data-tab="tema" role="tab">Tema</button>
  <button type="button" class="ds-tabbtn" data-tab="renkler" role="tab">Rənglər</button>
  <button type="button" class="ds-tabbtn" data-tab="logo" role="tab">Loqo</button>
  <button type="button" class="ds-tabbtn" data-tab="hero" role="tab">Hero<?= $hi ? ' <span class="ds-tabdot">•</span>' : '' ?></button>
  <button type="button" class="ds-tabbtn" data-tab="bolumler" role="tab">Bölmələr</button>
</div>

<form method="post" action="design.php?r=<?= rawurlencode($slug) ?>" id="dsMainForm">
  <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
  <input type="hidden" name="r" value="<?= esc($slug) ?>">

  <section class="adm-card ds-panel on" data-panel="tema" id="ds-tema">
    <div class="ds-themes">
      <?php foreach ($themes as $tid => $tp): ?>
      <label class="ds-theme<?= (int)($cur['theme'] ?? 1) === $tid ? ' on' : '' ?>">
        <input type="radio" name="theme" value="<?= $tid ?>"<?= (int)($cur['theme'] ?? 1) === $tid ? ' checked' : '' ?>>
        <span class="ds-dots" style="background:<?= esc($tp['paper']) ?>">
          <i style="background:<?= esc($tp['card']) ?>"></i><i style="background:<?= esc($tp['accent']) ?>"></i>
        </span>
        <span class="ds-tname"><?= esc($themeNames[$tid] ?? ('Tema ' . $tid)) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="adm-card ds-panel" data-panel="renkler" id="ds-renkler">
    <label class="adm-check" style="margin-bottom:10px"><input type="checkbox" name="custom" id="dsCustom" value="1"<?= !empty($cur['custom']) ? ' checked' : '' ?>> Bütün rəngləri özüm seçirəm</label>
    <div id="dsColors">
      <?php foreach ($fullLabels as $k => $lbl): $ek = substr($k, 2); ?>
      <div class="ds-color">
        <input type="color" id="pick_<?= $k ?>" value="<?= esc($eff[$ek] ?? '#000000') ?>" data-var="<?= esc($k) ?>">
        <div class="ds-color-meta"><span><?= esc($lbl) ?></span><input type="text" id="hex_<?= $k ?>" name="<?= $k ?>" value="<?= esc($eff[$ek] ?? '#000000') ?>" maxlength="7" spellcheck="false"></div>
      </div>
      <?php endforeach; ?>
      <div class="ds-color">
        <input type="color" id="pick_custom_catbg" value="<?= esc($eff['catbg'] !== 'transparent' ? $eff['catbg'] : '#000000') ?>" data-var="custom_catbg">
        <div class="ds-color-meta"><span>Kateqoriya fonu</span>
          <span class="ds-hexrow"><input type="text" id="hex_custom_catbg" name="custom_catbg" value="<?= esc($eff['catbg'] !== 'transparent' ? $eff['catbg'] : '') ?>" maxlength="7" placeholder="şəffaf" spellcheck="false">
          <button type="button" class="adm-btn sm" id="catbgClear">Şəffaf</button></span>
        </div>
      </div>
      <div class="ds-color">
        <input type="color" id="pick_custom_qicon" value="<?= esc($eff['qiconbg']) ?>" data-var="custom_qicon">
        <div class="ds-color-meta"><span>İkon fonu</span><input type="text" id="hex_custom_qicon" name="custom_qicon" value="<?= esc($eff['qiconbg']) ?>" maxlength="7" spellcheck="false"></div>
      </div>
    </div>
  </section>

  <section class="adm-card ds-panel" data-panel="bolumler" id="ds-bolumler">
    <div class="ds-flags">
      <?php foreach ($flagLabels as $f => $lbl): ?>
      <label class="adm-check"><input type="checkbox" name="flag_<?= $f ?>" value="1"<?= !empty($cur[$f]) ? ' checked' : '' ?>> <?= esc($lbl) ?></label>
      <?php endforeach; ?>
    </div>
  </section>
</form>

<section class="adm-card ds-panel" data-panel="logo" id="ds-logo">
  <?php if ($logoOk): ?>
    <div class="ds-logo-row">
      <img src="../<?= rawurlencode($slug) ?>/<?= esc($logoRel) ?>?v=<?= $logoVer ?>" alt="Loqo" class="ds-logo-prev">
      <p class="adm-muted">Hero mərkəzində story-halqa ilə görünür.</p>
      <span class="ds-spacer"></span>
      <form method="post" action="design.php?r=<?= rawurlencode($slug) ?>#ds-logo" onsubmit="return confirm('Loqo silinsin?');">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <input type="hidden" name="r" value="<?= esc($slug) ?>">
        <input type="hidden" name="action" value="logo_delete">
        <button class="adm-btn sm danger" type="submit">Sil</button>
      </form>
    </div>
  <?php else: ?>
    <p class="adm-empty" style="padding:16px">Loqo yoxdur.</p>
  <?php endif; ?>
  <form method="post" action="design.php?r=<?= rawurlencode($slug) ?>#ds-logo" enctype="multipart/form-data" class="ds-uploadrow">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <input type="hidden" name="r" value="<?= esc($slug) ?>">
    <input type="hidden" name="action" value="logo_upload">
    <input type="file" name="logofile" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required aria-label="Loqo şəkli">
    <button class="adm-btn primary sm" type="submit"><?= $logoOk ? 'Dəyiş' : 'Yüklə' ?></button>
  </form>
  <p class="adm-muted" style="margin-top:8px">JPG/PNG/WEBP, maks 2MB · kvadrat, şəffaf fonlu PNG tövsiyə olunur.</p>
</section>

<section class="adm-card ds-panel" data-panel="hero" id="ds-hero">
  <?php if ($hi): ?>
    <video src="../<?= rawurlencode($slug) ?>/assets/vid/hero.mp4?v=<?= $heroVer ?>" muted loop playsinline controls preload="metadata" class="ds-hero-prev"></video>
    <div class="ds-logo-row" style="margin-top:10px">
      <p class="adm-muted"><b><?= esc(round($hi['size'] / 1048576, 2)) ?> MB</b> · səssiz + loop ilə avtomatik oynayır.</p>
      <span class="ds-spacer"></span>
      <form method="post" action="design.php?r=<?= rawurlencode($slug) ?>#ds-hero" onsubmit="return confirm('Hero videosu silinsin? Saytda poster göstəriləcək.');">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <input type="hidden" name="r" value="<?= esc($slug) ?>">
        <input type="hidden" name="action" value="hero_delete">
        <button class="adm-btn sm danger" type="submit">Videonu sil</button>
      </form>
    </div>
  <?php else: ?>
    <p class="adm-empty" style="padding:16px">Hero videosu yoxdur — saytda poster göstərilir.</p>
  <?php endif; ?>
  <form method="post" action="design.php?r=<?= rawurlencode($slug) ?>#ds-hero" enctype="multipart/form-data" class="ds-uploadrow">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <input type="hidden" name="r" value="<?= esc($slug) ?>">
    <input type="hidden" name="action" value="hero_upload">
    <input type="file" name="herofile" accept=".mp4,video/mp4" required aria-label="Hero videosu">
    <button class="adm-btn primary sm" type="submit"><?= $hi ? 'Dəyiş' : 'Yüklə' ?></button>
  </form>
  <p class="adm-muted" style="margin-top:8px">MP4, maks 35MB · qısa (5–15 san), üfüqi, 1080p tövsiyə olunur.</p>
</section>

<div class="ds-savebar">
  <button class="adm-btn primary" type="submit" form="dsMainForm">Yadda saxla</button>
  <form method="post" action="design.php?r=<?= rawurlencode($slug) ?>" onsubmit="return confirm('Dizayn standarta qayıtsın?');">
    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
    <input type="hidden" name="r" value="<?= esc($slug) ?>">
    <input type="hidden" name="action" value="reset">
    <button class="adm-btn sm danger" type="submit">Standarta qaytar</button>
  </form>
  <span class="ds-spacer"></span>
  <a class="adm-btn sm" href="../<?= rawurlencode($slug) ?>/" target="_blank">Sayta bax</a>
</div>

<script>
(function(){
  var btns = Array.prototype.slice.call(document.querySelectorAll('.ds-tabbtn'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('.ds-panel'));
  var ids = ['tema','renkler','logo','hero','bolumler'];
  function openTab(name, push){
    btns.forEach(function(b){ b.classList.toggle('on', b.getAttribute('data-tab') === name); });
    panels.forEach(function(p){ p.classList.toggle('on', p.getAttribute('data-panel') === name); });
    if(push){ try{ history.replaceState(null, '', '#ds-' + name); }catch(e){} }
  }
  btns.forEach(function(b){ b.addEventListener('click', function(){ openTab(b.getAttribute('data-tab'), true); }); });
  var h = (location.hash || '').replace('#ds-', '');
  openTab(ids.indexOf(h) !== -1 ? h : 'tema', false);
  var customBox = document.getElementById('dsCustom');
  var themeRadios = document.querySelectorAll('input[name="theme"]');
  document.querySelectorAll('#dsColors input[type="color"]').forEach(function(p){
    p.addEventListener('input', function(){
      var key = p.getAttribute('data-var');
      var hex = document.getElementById('hex_'+key);
      if(hex) hex.value = p.value.toUpperCase();
      if(!customBox.checked){ customBox.checked = true; }
    });
  });
  document.querySelectorAll('#dsColors input[type="text"]').forEach(function(h){
    h.addEventListener('input', function(){
      var pick = document.getElementById('pick_'+h.name);
      if(pick && /^#[0-9a-fA-F]{6}$/.test(h.value)) pick.value = h.value;
    });
  });
  document.getElementById('catbgClear').addEventListener('click', function(){
    document.getElementById('hex_custom_catbg').value = '';
  });
  themeRadios.forEach(function(r){ r.addEventListener('change', function(){
    document.querySelectorAll('.ds-theme').forEach(function(l){ l.classList.remove('on'); });
    r.closest('.ds-theme').classList.add('on');
  }); });
})();
</script>
<?php
$body = ob_get_clean();
admin_layout('Dizayn — ' . ($r['name'] ?? $slug), 'design.php', $body);
