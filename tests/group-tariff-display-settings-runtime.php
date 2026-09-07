<?php

define('ABSPATH', __DIR__);
$GLOBALS['opts'] = array();
function add_action($hook, $callback, $priority = 10) {}
function current_user_can($cap) { return true; }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['opts']) ? $GLOBALS['opts'][$key] : $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['opts'][$key] = $value; return true; }
function wp_date($format) { return $format === 'Y' ? '2026' : '2026'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function sanitize_textarea_field($value) { return trim(strip_tags((string)$value)); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function esc_url_raw($value) { return (string)$value; }
function wp_json_encode($value) { return json_encode($value); }
final class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-tariff-settings.php';
function setting_assert($condition, $message) { if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); } echo '[OK] ' . $message . PHP_EOL; }

$GLOBALS['opts'][Parcs_HT_Defaults::OPTION] = array('site_type'=>'mds','general'=>array(),'seasons'=>array('2026'=>array('published'=>'1','tariffs'=>array('groups'=>array(array('enabled'=>'1'))))));
$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION] = array('version'=>1,'seasons'=>array('2026'=>array('published'=>'1','show_heading'=>'1','show_quote_button'=>'1')));
$mds = Parcs_HT_Group_Tariff_Settings::settings('2026');
setting_assert((int)$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION]['version'] === 1, 'public settings read does not rewrite the stored option');
setting_assert($mds['show_payment_methods'] === '1' && count($mds['payment_methods']) === 5, 'existing MDS 1.13.6 payment content is preserved by in-memory migration');
setting_assert($mds['show_info_blocks'] === '1' && count($mds['info_blocks']) === 2, 'existing MDS 1.13.6 information content is preserved by in-memory migration');
Parcs_HT_Group_Tariff_Settings::ensure_store();
setting_assert((int)$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION]['version'] === 2, 'administration persists the migration to version 2');

$GLOBALS['opts'][Parcs_HT_Defaults::OPTION] = array('site_type'=>'fds','general'=>array(),'seasons'=>array('2026'=>array('published'=>'1','tariffs'=>array('groups'=>array(array('enabled'=>'1'))))));
$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION] = array('version'=>1,'seasons'=>array('2026'=>array('published'=>'1','show_heading'=>'1','show_quote_button'=>'1')));
$fds = Parcs_HT_Group_Tariff_Settings::settings('2026');
setting_assert($fds['show_payment_methods'] === '0' && $fds['payment_methods'] === array(), 'FDS does not inherit MDS payment rules');
setting_assert($fds['show_info_blocks'] === '0' && $fds['info_blocks'] === array(), 'FDS does not inherit MDS information rules');

$custom = $fds;
$custom['show_payment_methods'] = '1';
$custom['payment_title'] = array('fr'=>'Règlements FDS','en'=>'FDS payments','de'=>'FDS Zahlung');
$custom['payment_methods'] = array(array('enabled'=>'1','icon'=>'bank','label'=>array('fr'=>'Virement FDS','en'=>'FDS transfer','de'=>'FDS Überweisung')));
$custom['show_info_blocks'] = '1';
$custom['info_blocks'] = array(array('enabled'=>'1','title'=>array('fr'=>'Règle FDS'),'text'=>array('fr'=>'Texte FDS')));
setting_assert(Parcs_HT_Group_Tariff_Settings::save('2026', $custom), 'custom site presentation settings save successfully');
$saved = Parcs_HT_Group_Tariff_Settings::settings('2026');
setting_assert($saved['payment_methods'][0]['label']['fr'] === 'Virement FDS', 'custom payment method is stored per installation');
setting_assert($saved['info_blocks'][0]['text']['fr'] === 'Texte FDS', 'custom information block is stored per installation');

echo "Group tariff display settings runtime: OK\n";