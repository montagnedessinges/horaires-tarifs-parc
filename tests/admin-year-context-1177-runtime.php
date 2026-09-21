<?php

/** Régression 1.17.7 : une page annuelle doit lire la grille exacte de son année. */

define('ABSPATH', __DIR__ . '/');

function is_admin() { return true; }
function current_user_can($capability) { return $capability === 'manage_options'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim((string)$value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }

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

$_GET = array('page'=>'parcs-ht-groups', 'season'=>'2027');
$_POST = array();
$out = Parcs_HT_Admin_Year_Context::select_exact_year_tariffs($settings);
if (($out['tariffs']['marker'] ?? '') !== 'season-2027' || ($out['general']['year'] ?? '') !== '2027' || ($out['active_season_year'] ?? '') !== '2027') {
    fwrite(STDERR, "FAIL: dedicated admin page did not select exact 2027 tariffs.\n");
    exit(1);
}

$_GET = array('page'=>'unrelated-screen', 'season'=>'2027');
$_POST = array();
$out = Parcs_HT_Admin_Year_Context::select_exact_year_tariffs($settings);
if (($out['tariffs']['marker'] ?? '') !== 'legacy-2026') {
    fwrite(STDERR, "FAIL: unrelated admin page was modified.\n");
    exit(1);
}

$_GET = array();
$_POST = array('action'=>'parcs_ht_save_quote_tariff_binding', 'year'=>'2027');
$out = Parcs_HT_Admin_Year_Context::select_exact_year_tariffs($settings);
if (($out['tariffs']['marker'] ?? '') !== 'season-2027') {
    fwrite(STDERR, "FAIL: AJAX quote binding did not select exact 2027 tariffs.\n");
    exit(1);
}

$_POST = array('action'=>'parcs_ht_save', 'season_year'=>'2026');
$out = Parcs_HT_Admin_Year_Context::select_exact_year_tariffs($settings);
if (($out['tariffs']['marker'] ?? '') !== 'season-2026') {
    fwrite(STDERR, "FAIL: annual admin save did not select exact 2026 tariffs.\n");
    exit(1);
}

echo "OK: exact annual tariff context is isolated for pages and admin writes.\n";
