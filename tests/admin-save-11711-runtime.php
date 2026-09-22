<?php

/** Régressions 1.17.11 : POST natif complet et retour direct vers l'écran métier. */

define('ABSPATH', __DIR__ . '/');

class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }
class Parcs_HT_Admin_Periods { const PAGE = 'parcs-ht-periods'; }
class Parcs_HT_Admin_Retail_Tariffs { const PAGE = 'parcs-ht-tariffs'; }
class Parcs_HT_Admin_Group_Tariffs { const PAGE = 'parcs-ht-groups'; }
class Parcs_HT_Admin_Communication_1179 { const POPUP_PAGE = 'parcs-ht-popup-1179'; }

function current_user_can($capability) { return $capability === 'manage_options'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim((string)$value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function check_admin_referer($action) { if ($action !== 'parcs_ht_save') throw new RuntimeException('bad nonce action'); return 1; }
function get_option($key, $default = false) {
    if ($key !== Parcs_HT_Defaults::OPTION) return $default;
    return array('seasons'=>array('2026'=>array('exceptions'=>array()),'2027'=>array('exceptions'=>array())));
}
function wp_die($message, $title = '', $args = array()) { throw new RuntimeException((string)$message); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function admin_url($path = '') { return 'https://example.test/wp-admin/' . ltrim($path, '/'); }
function add_query_arg($args, $url) {
    $parts = parse_url($url);
    $query = array();
    if (!empty($parts['query'])) parse_str($parts['query'], $query);
    foreach ((array)$args as $key=>$value) $query[$key] = $value;
    $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'example.test') . ($parts['path'] ?? '');
    return $base . ($query ? '?' . http_build_query($query) : '');
}

require_once dirname(__DIR__) . '/includes/class-parcs-ht-admin-save-11711.php';

$_POST = array(
    'action'=>'parcs_ht_save',
    '_wpnonce'=>'valid',
    'season_year'=>'2027',
    'settings'=>array('exceptions'=>array(array('enabled'=>'1','title'=>array('fr'=>'Domaine fermé')))),
    'parcs_ht_11711_complete'=>'1',
    'parcs_ht_11711_workspace'=>'periods',
);
Parcs_HT_Admin_Save_11711::guard_native_post();

$redirect = Parcs_HT_Admin_Save_11711::rewrite_legacy_workspace_redirect(
    'https://example.test/wp-admin/admin.php?page=parcs-horaires-tarifs&season=2027&tab=htp-holidays&updated=1',
    302
);
parse_str((string)parse_url($redirect, PHP_URL_QUERY), $args);
if (($args['page'] ?? '') !== 'parcs-ht-periods' || ($args['season'] ?? '') !== '2027' || ($args['updated'] ?? '') !== '1' || isset($args['tab'])) {
    fwrite(STDERR, "FAIL: periods save did not return directly to the 2027 business screen.\n");
    exit(1);
}

$_POST['parcs_ht_11711_workspace'] = 'popup';
$redirect = Parcs_HT_Admin_Save_11711::rewrite_legacy_workspace_redirect(
    'https://example.test/wp-admin/admin.php?page=parcs-horaires-tarifs&tab=htp-alerts&updated=1',
    302
);
parse_str((string)parse_url($redirect, PHP_URL_QUERY), $args);
if (($args['page'] ?? '') !== 'parcs-ht-popup-1179' || isset($args['season'])) {
    fwrite(STDERR, "FAIL: popup save did not return directly to its non-seasonal screen.\n");
    exit(1);
}

$blocked = false;
unset($_POST['parcs_ht_11711_complete']);
try {
    Parcs_HT_Admin_Save_11711::guard_native_post();
} catch (RuntimeException $e) {
    $blocked = strpos($e->getMessage(), 'Aucune donnée n’a été modifiée') !== false;
}
if (!$blocked) {
    fwrite(STDERR, "FAIL: truncated POST was not blocked before the canonical writer.\n");
    exit(1);
}

echo "OK: 1.17.11 keeps native POST saves complete and returns directly to the current business screen.\n";
