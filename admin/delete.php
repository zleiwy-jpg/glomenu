<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$slug = (string)($_GET['r'] ?? ($_POST['r'] ?? ''));
$r = find_restaurant($slug);
if ($r === null) {
    flash('Restoran tapılmadı.', 'err');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (trim((string)($_POST['confirm'] ?? '')) === $slug) {
        rrmdir(GLOMENU_ROOT . '/' . $slug);
        save_registry(array_values(array_filter(registry(), function ($it) use ($slug) {
            return ($it['slug'] ?? '') !== $slug;
        })));
        flash('“' . ($r['name'] ?? $slug) . '” silindi.');
        header('Location: index.php');
        exit;
    }
    flash('Təsdiq üçün slug-u düzgün yazın.', 'err');
    header('Location: delete.php?r=' . rawurlencode($slug));
    exit;
}

ob_start();
?>
  <div class="adm-card">
    <h2>“<?= esc($r['name'] ?? $slug) ?>” silinsin?</h2>
    <p class="adm-muted" style="margin-bottom:16px">/<b><?= esc($slug) ?></b>/ qovluğu və bütün məlumatlar silinəcək. Bu əməliyyat geri qaytarılmır.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <input type="hidden" name="r" value="<?= esc($slug) ?>">
      <label class="adm-fld"><span>Təsdiq üçün slug-u yazın: <b><?= esc($slug) ?></b></span><input name="confirm" required maxlength="30" autocomplete="off"></label>
      <div class="adm-form-btns">
        <button class="adm-btn danger" type="submit">Tam sil</button>
        <a class="adm-btn" href="index.php">Ləğv et</a>
      </div>
    </form>
  </div>
<?php
$body = ob_get_clean();
admin_layout('Restoranı sil', 'index.php', $body);
