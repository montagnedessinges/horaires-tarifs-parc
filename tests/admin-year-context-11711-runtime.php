<?php

/** Régression 1.17.11 : l'année administrée mémorisée reste isolée du public. */

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

$GLOBALS['htp_user_meta'] = array(7=>array('parcs_ht_admin_year'=>'2027'));

if (!function_exists('is_admin')) { function is_admin() { return true; } }
if (!function_exists('current_user_can')) { function current_user_can($capability) { return $capability === 'manage_options'; } }
if (!function_exists('get_current_user_id')) { function get_current_user_id() { return 7; } }
if (!function_exists('get_user_meta')) { function get_user_meta($user_id, $key, $single = false) { return $GLOBALS['htp_user_meta'][$user_id][$key] ?? ''; } }
if (!function_exists('wp_unslash')) { function wp_unslash($value) { return $value; } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($value) { return trim((string)$value); } }
if (!function_exists('sanitize_key')) { function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); } }

if (!class_exists('Parcs_HT_Defaults')) { class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; } }

require_once dirname(__DIR__) . '/includes/class-parcs-ht-admin-year-context.php';

$settings = array(
    'general'=>array('year'=>'2026'),
    'active_season_year'=>'2026',
    'tariffs'=>array('marker'=>'legacy-2026'),
    'seasons'=>array(
        '2026'=>array('tariffs'=>array('marker'=>'season-2026')),
        '2027'=>array('tariffs'=>array('marker'=>'season-2027')),
    ),
);

$_GET = array('page'=>'parcs-ht-groups');
$_POST = array();
$out = Parcs_HT_Admin_Year_Context::select_exact_year_tariffs($settings);
if (($out['tariffs']['marker'] ?? '') !== 'season-2027' || ($out['general']['year'] ?? '') !== '2027') {
    fwrite(STDERR, "FAIL: remembered administered year 2027 was not restored on an annual screen.\n");
    exit(1);
}

$_GET = array('page'=>'parcs-ht-popup-1179');
$_POST = array();
$out = Parcs_HT_Admin_Year_Context::select_exact_year_tariffs($settings);
if (($out['tariffs']['marker'] ?? '') !== 'legacy-2026') {
    fwrite(STDERR, "FAIL: remembered annual context leaked into the non-seasonal popup screen.\n");
    exit(1);
}

echo "OK: 1.17.11 remembers the administered year without changing non-seasonal screens.\n";
