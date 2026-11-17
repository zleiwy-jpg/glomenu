<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$menu = load_menu();
$errors = [];
$counts = [];
foreach ($menu['items'] as $it) {
    $cid = (string)($it['cat'] ?? '');
    $counts[$cid] = ($counts[$cid] ?? 0) + 1;
}

function cat_id_unique(array $cats, string $base): string
{
    $ids = array_column($cats, 'id');
    $id = $base !== '' ? $base : 'kat';
    if (!in_array($id, $ids, true)) return $id;
    for ($i = 2; $i < 100; $i++) {
        if (!in_array($id . '-' . $i, $ids, true)) return $id . '-' . $i;
    }
    return $id . '-' . time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 40);
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 80);
        if ($name === '') {
            $errors[] = 'Kateqoriya adı boş ola bilməz.';
        } else {
            $id = cat_id_unique($menu['categories'], mb_substr(slugify($name), 0, 24));
            $cat = ['id' => $id, 'name' => $name];
            if ($note !== '') $cat['note'] = $note;
            $menu['categories'][] = $cat;
            save_menu($menu);
            flash('"' . $name . '" əlavə edildi.');
            header('Location: categories.php');
            exit;
        }
    } elseif ($action === 'rename') {
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
            save_menu($menu);
            flash('Yadda saxlanıldı.');
            header('Location: categories.php');
            exit;
        } elseif (!$found) {
            $errors[] = 'Kateqoriya tapılmadı.';
        }
    } elseif ($action === 'delete') {
        $id = (string)($_POST['id'] ?? '');
        $n = $counts[$id] ?? 0;
        if ($n > 0) {
            $errors[] = 'Bu kateqoriyada ' . $n . ' məhsul var. Əvvəlcə məhsulları başqa kateqoriyaya keçirin və ya silin.';
        } else {
            $before = count($menu['categories']);
            $menu['categories'] = array_values(array_filter($menu['categories'], function($c) use ($id) { return (string)($c['id'] ?? '') !== $id; }));
            if (count($menu['categories']) === $before) {
                $errors[] = 'Kateqoriya tapılmadı.';
            } else {
                save_menu($menu);
                flash('Kateqoriya silindi.');
                header('Location: categories.php');
                exit;
            }
        }
    }
}

$flash = take_flash();
$body = '';
if ($flash) $body .= '<div class="adm-flash ' . esc($flash['type']) . '">' . esc($flash['msg']) . '</div>';
foreach ($errors as $e) $body .= '<div class="adm-flash err">' . esc($e) . '</div>';

$body .= '<form class="adm-card" method="post" action="categories.php">';
$body .= '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
$body .= '<input type="hidden" name="action" value="add">';
$body .= '<div class="adm-grid">';
$body .= '<label class="adm-fld"><span>Yeni kateqoriya adı *</span><input type="text" name="name" required maxlength="40"></label>';
$body .= '<label class="adm-fld"><span>Qeyd (istəyə bağlı)</span><input type="text" name="note" maxlength="80"></label>';
$body .= '</div>';
$body .= '<button class="adm-btn primary" type="submit">+ Əlavə et</button>';
$body .= '</form>';

$body .= '<div class="adm-card"><table class="adm-tbl"><thead><tr><th>Kateqoriya</th><th>Məhsul</th><th>Qeyd</th><th></th></tr></thead><tbody>';
foreach ($menu['categories'] as $c):
    $cid = (string)($c['id'] ?? '');
    $cnt = $counts[$cid] ?? 0;
?>
    <tr>
      <td><b><?= esc($c['name']) ?></b><br><span class="adm-muted">id: <?= esc($cid) ?></span></td>
      <td><span class="adm-badge"><?= (int)$cnt ?></span></td>
      <td class="adm-muted"><?= esc($c['note'] ?? '—') ?></td>
      <td class="adm-actions">
        <form method="post" action="categories.php" class="adm-inline">
          <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="id" value="<?= esc($cid) ?>">
          <input type="text" name="name" value="<?= esc($c['name']) ?>" maxlength="40" required placeholder="Ad">
          <input type="text" name="note" value="<?= esc($c['note'] ?? '') ?>" maxlength="80" placeholder="Qeyd">
          <button class="adm-btn sm" type="submit">Saxla</button>
        </form>
        <form method="post" action="categories.php" onsubmit="return confirm('Silinsin?');">
          <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= esc($cid) ?>">
          <button class="adm-btn sm danger" type="submit" <?= $cnt ? 'disabled' : '' ?>>Sil</button>
        </form>
      </td>
    </tr>
<?php endforeach;
if (!$menu['categories']) {
    $body .= '<tr><td colspan="4" class="adm-empty">Hələ kateqoriya yoxdur.</td></tr>';
}
$body .= '</tbody></table></div>';

admin_layout('Kateqoriyalar', 'categories.php', $body);
