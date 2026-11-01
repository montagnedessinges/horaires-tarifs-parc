<?php
/** Non-regression test: hub Tarifs visible, legacy URL routes registered. */
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
define('ABSPATH', __DIR__);
$GLOBALS['submenu'] = array();
function add_action($name, $callback, $priority = 10) {}
function add_submenu_page($parent, $title, $label, $capability, $slug, $callback) {
    $GLOBALS['submenu'][$parent][] = array($label, $capability, $slug, $title);
    return $parent . '_page_' . $slug;
}
function sanitize_key($text) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$text)); }
function wp_unslash($text) { return $text; }
function menu_fail($msg) { fwrite(STDERR, "FAIL menu Tarifs: {$msg}\n"); exit(1); }
class Parcs_HT_Admin { const PAGE = 'parcs-ht'; }
require_once $root . '/includes/class-parcs-ht-admin-navigation.php';
Parcs_HT_Admin_Navigation::menu();
Parcs_HT_Admin_Navigation::order_menu();
$entries = $GLOBALS['submenu'][Parcs_HT_Admin::PAGE] ?? array();
$slugs = array_column($entries, 2);
if (!in_array(Parcs_HT_Admin_Navigation::TARIFFS_PAGE, $slugs, true)) menu_fail('hub Tarifs non enregistré');
if (!in_array('parcs-ht-tariffs', $slugs, true)) menu_fail('ancienne route Visiteurs perdue');
$hub = array_filter($entries, static function($entry) {
    return $entry[2] === Parcs_HT_Admin_Navigation::TARIFFS_PAGE && $entry[0] === 'Tarifs';
});
if (count($hub) !== 1) menu_fail('libellé Tarifs absent ou dupliqué');
ob_start();
Parcs_HT_Admin_Navigation::admin_head();
$css = ob_get_clean();
if (strpos($css, 'a[href*="page=parcs-ht-tariffs"]') !== false) menu_fail('le sélecteur CSS masque aussi le hub');
foreach (array('parcs-ht-tariffs', 'parcs-ht-cse') as $slug) {
    if (strpos($css, 'a[href$="page=' . $slug . '"]') === false ||
        strpos($css, 'a[href*="page=' . $slug . '&"]') === false) {
        menu_fail('ancienne route CSS non ciblée exactement : ' . $slug);
    }
}
echo "OK : le menu Tarifs reste visible, les routes Visiteurs et CSE sont conservées.\n";
