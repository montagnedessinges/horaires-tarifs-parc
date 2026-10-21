<?php
/**
 * Régression 1.20.9 : le lien "Vérifier maintenant" doit aboutir à une action
 * WordPress réellement enregistrée. Fonctionne sans charger WordPress.
 */
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$src = file_get_contents($root . '/includes/class-parcs-ht-admin-overview.php');
$plugin = file_get_contents($root . '/horaires-tarifs-parc.php');

function check_1209($ok, $message) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $message . PHP_EOL;
    if (!$ok) exit(1);
}
check_1209(is_string($src) && is_string($plugin), 'source files readable');
check_1209(strpos($plugin, "define('PARCS_HT_VERSION', '1.20.9')") !== false, 'version is 1.20.9');

if (!defined('ABSPATH')) define('ABSPATH', '/');
$GLOBALS['htp_1209_actions'] = array();
function add_action($hook, $callback, $priority=10) {
    $GLOBALS['htp_1209_actions'][$hook] = $callback;
}
require_once $root . '/includes/class-parcs-ht-admin-overview.php';
Parcs_HT_Admin_Overview::init();

$action = 'parcs_ht_check_updates_1172';
$hook = 'admin_post_' . $action;
check_1209(isset($GLOBALS['htp_1209_actions'][$hook]), 'overview check URL has registered admin-post handler');
check_1209($GLOBALS['htp_1209_actions'][$hook] === array('Parcs_HT_Admin_Overview', 'check_updates_now'), 'handler matches registered action');
check_1209(strpos($src, "admin-post.php?action=" . $action) !== false, 'overview button points to registered action');
check_1209(strpos($src, "wp_nonce_url(admin_url('admin-post.php?action=" . $action . "'), '" . $action . "')") !== false, 'overview button supplies matching nonce');
check_1209(strpos($src, "check_admin_referer('" . $action . "');") !== false, 'update check validates nonce');
check_1209(strpos($src, "current_user_can('manage_options')") !== false && strpos($src, "current_user_can('update_plugins')") !== false, 'update action checks permissions');
check_1209(strpos($src, 'Parcs_HT_Updater::clear_cache();') !== false, 'update action refreshes GitHub cache');
check_1209(strpos($src, 'wp_update_plugins();') !== false, 'update action invokes WordPress update refresh');
check_1209(strpos($src, "'checked' => '1'") !== false, 'update action returns to overview with status');
echo "Update check 1.20.9 contract: OK\n";
