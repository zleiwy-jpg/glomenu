<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$menu = load_menu();
$id = (int)($_GET['id'] ?? 0);
$item = $id ? find_item($menu['items'], $id) : null;
if (!$item) {
    flash('Məhsul tapılmadı.', 'err');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oldImg = $item['img'] ?? '';
    $oldVideo = $item['video'] ?? '';
    $items = array_values(array_filter($menu['items'], function($it) use ($id) { return (int)($it['id'] ?? 0) !== $id; }));
    $menu['items'] = $items;
    if ($oldImg !== '') delete_media_if_unused($items, $oldImg);
    if ($oldVideo !== '') delete_media_if_unused($items, $oldVideo);
    save_menu($menu);
    flash('Məhsul silindi.');
    header('Location: index.php');
    exit;
}

$body = '<div class="adm-card">';
$body .= '<p><b>' . esc($item['name']) . '</b> silinsin?</p>';
$body .= '<form method="post" action="delete.php?id=' . $id . '">';
$body .= '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
$body .= '<div class="adm-form-btns"><button class="adm-btn danger" type="submit">Sil</button><a class="adm-btn" href="index.php">Ləğv et</a></div>';
$body .= '</form></div>';

admin_layout('Məhsulu sil', 'index.php', $body);
