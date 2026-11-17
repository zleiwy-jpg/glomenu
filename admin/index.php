<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$list = registry();
$totalDishes = 0;
$totalVisits = 0;
$counts = [];
$visits = [];
$rates = [];
foreach ($list as $r) {
    $slug = (string)($r['slug'] ?? '');
    if (!is_dir(GLOMENU_ROOT . '/' . $slug)) {
        $counts[$slug] = ['dishes' => 0, 'cats' => 0];
        $visits[$slug] = 0;
        $rates[$slug] = ['count' => 0, 'avg' => 0];
        continue;
    }
    $m = rest_load_menu($slug);
    $counts[$slug] = ['dishes' => count($m['items']), 'cats' => count($m['categories'])];
    $totalDishes += count($m['items']);
    $v = rest_visits($slug);
    $visits[$slug] = $v;
    $totalVisits += $v;
    $rates[$slug] = rest_rating_summary($slug);
}

$defPass = is_default_password();

ob_start();
if ($defPass): ?>
  <div class="adm-flash warn">Standart şifrə aktivdir. <a href="password.php">Şifrəni dəyiş</a></div>
<?php endif; ?>
  <div class="adm-stats">
    <div class="adm-stat"><b><?= count($list) ?></b><span>Restoran</span></div>
    <div class="adm-stat"><b><?= fmt_num($totalVisits) ?></b><span>Ümumi ziyarət</span></div>
    <div class="adm-stat"><b><?= $totalDishes ?></b><span>Ümumi məhsul</span></div>
  </div>

  <div class="adm-card">
    <div class="adm-card-h">
      <h2>Restoranlar</h2>
      <div style="display:flex;gap:10px;align-items:center">
        <input type="search" id="q" placeholder="Axtar…" autocomplete="off" aria-label="Restoran axtar" style="width:180px">
        <a class="adm-btn" href="import.php">⇅ İdxal</a>
        <a class="adm-btn primary" href="create.php">+ Yeni restoran</a>
      </div>
    </div>
    <table class="adm-tbl">
      <thead><tr><th>Restoran</th><th>Məhsul</th><th>Reytinq</th><th>Ziyarət</th><th></th></tr></thead>
      <tbody id="rows">
      <?php foreach ($list as $r):
        $slug = (string)($r['slug'] ?? '');
        $nm = (string)($r['name'] ?? $slug);
        $rc = $rates[$slug] ?? ['count' => 0, 'avg' => 0];
        $missing = !is_dir(GLOMENU_ROOT . '/' . $slug);
      ?>
        <tr data-search="<?= esc(mb_strtolower($nm . ' ' . $slug, 'UTF-8')) ?>">
          <td>
            <b><?= esc($nm) ?></b><br>
            <span class="adm-muted">/<?= esc($slug) ?>/ · <?= esc($r['created'] ?? '') ?></span>
            <?php if ($missing): ?><br><span class="adm-flash err" style="display:inline-block;margin:6px 0 0;padding:4px 10px">Qovluq tapılmadı</span><?php endif; ?>
          </td>
          <td><span class="adm-badge"><?= (int)($counts[$slug]['dishes'] ?? 0) ?></span></td>
          <td><?php if (!empty($rc['count'])): ?><span class="adm-stars"><?= esc(number_format((float)$rc['avg'], 1, ',', '')) ?> ★ (<?= (int)$rc['count'] ?>)</span><?php else: ?><span class="adm-muted">—</span><?php endif; ?></td>
          <td><?= fmt_num((int)($visits[$slug] ?? 0)) ?></td>
          <td class="adm-actions">
            <?php if (!$missing): ?>
              <a class="adm-btn sm" href="../<?= rawurlencode($slug) ?>/" target="_blank">Sayt</a>
              <a class="adm-btn sm" href="../<?= rawurlencode($slug) ?>/admin/" target="_blank">Admin</a>
              <a class="adm-btn sm" href="design.php?r=<?= rawurlencode($slug) ?>">Dizayn</a>
              <a class="adm-btn sm primary" href="menu.php?r=<?= rawurlencode($slug) ?>">Menyu</a>
              <a class="adm-btn sm" href="export.php?r=<?= rawurlencode($slug) ?>" title="Menyu, şəkil və videoları ZIP kimi yüklə">⇩ Export</a>
            <?php endif; ?>
            <a class="adm-btn sm danger" href="delete.php?r=<?= rawurlencode($slug) ?>">Sil</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$list): ?>
        <tr><td colspan="5" class="adm-empty">Hələ restoran yoxdur. <a href="create.php">İlk restoranı yarat</a>.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    <div class="adm-empty" id="emptyRow" hidden>Heç nə tapılmadı.</div>
  </div>
  <script>
  (function(){
    var q = document.getElementById('q'), empty = document.getElementById('emptyRow');
    if(!q) return;
    q.addEventListener('input', function(){
      var s = q.value.toLowerCase().trim(), n = 0;
      document.querySelectorAll('#rows tr').forEach(function(tr){
        var hit = !s || (tr.getAttribute('data-search') || '').indexOf(s) !== -1;
        tr.style.display = hit ? '' : 'none';
        if(hit) n++;
      });
      if(empty) empty.hidden = n > 0;
    });
  })();
  </script>
<?php
$body = ob_get_clean();
admin_layout('Restoranlar', 'index.php', $body);
