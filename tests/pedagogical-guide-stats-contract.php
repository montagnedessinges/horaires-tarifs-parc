<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$stats_php = file_get_contents($root . '/includes/class-parcs-ht-guide-stats.php');
$guides_php = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$integrity_php = file_get_contents($root . '/includes/class-parcs-ht-save-integrity.php');
$main_php = file_get_contents($root . '/horaires-tarifs-parc.php');

function guide_stats_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

guide_stats_contract(strpos($stats_php, "const ID_OPTION = 'parcs_ht_pedagogical_guide_ids'") !== false, 'Guide identifiers have a permanent dedicated registry');
guide_stats_contract(strpos($stats_php, "'guide_' . str_pad") !== false && strpos($stats_php, "while (isset(\$state['used'][\$id]))") !== false, 'New guide identifiers are monotonic and never recycle an issued id');
guide_stats_contract(strpos($stats_php, 'clone_library_with_new_ids') !== false, 'A duplicated season receives new guide identifiers');
guide_stats_contract(strpos($stats_php, "'deleted_at' => 0") !== false && strpos($stats_php, "['deleted_at'] = \$now") !== false, 'Deleted guides remain archived in statistical metadata');
guide_stats_contract(strpos($stats_php, "admin_post_nopriv_parcs_ht_track_guide_click") !== false, 'Anonymous public guide clicks have a dedicated endpoint');
guide_stats_contract(strpos($stats_php, 'ON DUPLICATE KEY UPDATE clicks = clicks + 1') !== false, 'Click increments are atomic in the dedicated statistics table');
guide_stats_contract(strpos($stats_php, 'UNIQUE KEY guide_event') !== false, 'Click aggregates are unique per guide season date action and language');
guide_stats_contract(strpos($stats_php, "array('view','download')") !== false, 'Only Consult and Download actions are counted');
guide_stats_contract(strpos($stats_php, "array('fr','de','en')") !== false, 'Click language is limited to FR DE EN');
guide_stats_contract(strpos($stats_php, 'guide_stats_range') !== false && strpos($stats_php, "'7'=>'7 jours'") !== false && strpos($stats_php, "'30'=>'30 jours'") !== false, 'Admin statistics provide useful period filters');
guide_stats_contract(strpos($stats_php, 'adresse IP') !== false && stripos($stats_php, 'REMOTE_ADDR') === false && stripos($stats_php, 'setcookie') === false, 'Analytics explicitly avoid storing IP addresses or cookies');
guide_stats_contract(strpos($guides_php, "name=\"<?php echo esc_attr(\$base.'[id]');?>\"") !== false, 'Saved guides carry their permanent id through the admin form');
guide_stats_contract(strpos($guides_php, 'data-guide-action="view"') !== false && strpos($guides_php, 'data-guide-action="download"') !== false, 'Both public guide buttons expose their distinct tracking action');
guide_stats_contract(strpos($guides_php, 'navigator.sendBeacon') !== false && strpos($guides_php, 'keepalive:true') !== false, 'Tracking does not block navigation away from the guide page');
guide_stats_contract(strpos($guides_php, "!is_admin() && class_exists('Parcs_HT_Guide_Stats')") !== false, 'Admin shortcode previews do not pollute public click statistics');
guide_stats_contract(strpos($integrity_php, "Parcs_HT_Guide_Stats::claim_id(\$item['id'] ?? '')") !== false, 'Verified guide saves preserve or allocate a permanent id before persistence comparison');
guide_stats_contract(strpos($main_php, 'class-parcs-ht-guide-stats.php') !== false && strpos($main_php, 'Parcs_HT_Guide_Stats::init()') !== false, 'Guide statistics module is bootstrapped');

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$GLOBALS['guide_stats_options'] = array();
if (!function_exists('get_option')) { function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['guide_stats_options']) ? $GLOBALS['guide_stats_options'][$name] : $default; } }
if (!function_exists('update_option')) { function update_option($name, $value, $autoload = null) { unset($autoload); $GLOBALS['guide_stats_options'][$name] = $value; return true; } }
if (!function_exists('sanitize_key')) { function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($value) { return trim(strip_tags((string)$value)); } }
if (!function_exists('wp_salt')) { function wp_salt($scheme = 'auth') { return 'test-salt-' . $scheme; } }
if (!function_exists('wp_date')) { function wp_date($format, $timestamp = null) { return gmdate($format, $timestamp === null ? time() : $timestamp); } }
if (!class_exists('Parcs_HT_Pedagogical_Guides')) { class Parcs_HT_Pedagogical_Guides { const OPTION = 'parcs_ht_pedagogical_guides'; } }

require_once $root . '/includes/class-parcs-ht-guide-stats.php';

$id1 = Parcs_HT_Guide_Stats::allocate_id();
$id2 = Parcs_HT_Guide_Stats::allocate_id();
guide_stats_contract($id1 === 'guide_000001' && $id2 === 'guide_000002', 'First two created guides receive deterministic unique ids');

$store = array('version'=>3,'seasons'=>array('2026'=>array('guides'=>array(
    array('id'=>$id1,'cycle'=>'cycle1','title'=>array('fr'=>'Guide A')),
    array('id'=>$id2,'cycle'=>'cycle2','title'=>array('fr'=>'Guide B')),
))));
Parcs_HT_Guide_Stats::sync_metadata_from_store($store);
$store['seasons']['2026']['guides'] = array($store['seasons']['2026']['guides'][1]);
Parcs_HT_Guide_Stats::sync_metadata_from_store($store);
$meta = get_option(Parcs_HT_Guide_Stats::META_OPTION, array());
guide_stats_contract(!empty($meta['guides'][$id1]['deleted_at']), 'Deleting a guide archives its id instead of erasing statistical memory');

$id3 = Parcs_HT_Guide_Stats::allocate_id();
guide_stats_contract($id3 === 'guide_000003', 'A guide created after deletion never reuses the deleted id');
guide_stats_contract(Parcs_HT_Guide_Stats::claim_id($id1) === $id1, 'Restoring the same historical guide may reclaim its own reserved id');
$id4 = Parcs_HT_Guide_Stats::allocate_id();
guide_stats_contract($id4 === 'guide_000004', 'Reclaiming an old reserved id never moves the new-id counter backwards');

$clone = Parcs_HT_Guide_Stats::clone_library_with_new_ids(array('guides'=>array(array('id'=>$id2,'title'=>array('fr'=>'Copie')))));
guide_stats_contract($clone['guides'][0]['id'] === 'guide_000005' && $clone['guides'][0]['id'] !== $id2, 'Season duplication creates a fresh id instead of copying the source id');

guide_stats_contract(
    Parcs_HT_Guide_Stats::tracking_token($id2, 'view', 'fr', '2026') !== Parcs_HT_Guide_Stats::tracking_token($id2, 'download', 'fr', '2026'),
    'Consult and Download events use different signed tracking tokens'
);

echo "Pedagogical guide statistics contract: OK\n";
