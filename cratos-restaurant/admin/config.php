<?php
// gloMenu admin — mərkəzi sabitlər (çıxış/ofset yoxdur, BOM-suz saxla)
define('GLOMENU_ROOT', dirname(__DIR__));
define('MENU_FILE', GLOMENU_ROOT . '/data/menu.json');
define('ADMIN_FILE', GLOMENU_ROOT . '/data/admin.json');
define('IMG_DIR', GLOMENU_ROOT . '/assets/img/dishes');
define('VID_DIR', GLOMENU_ROOT . '/assets/vid/dishes');
define('MAX_IMG_BYTES', 8 * 1024 * 1024);
define('MAX_VID_BYTES', 35 * 1024 * 1024);
