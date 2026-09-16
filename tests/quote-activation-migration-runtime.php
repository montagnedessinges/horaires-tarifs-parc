<?php

define('ABSPATH', '/');
$GLOBALS['options'] = array();

function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['options']) ? $GLOBALS['options'][$key] : $default; }
function update_option($key, $value, $autoload = null) { unset($autoload); $GLOBALS['options'][$key] = $value; return true; }
function current_user_can($capability) { return $capability === 'manage_options'; }
function wp_date($format, $timestamp = null, $timezone = null) { unset($format, $timestamp, $timezone); return '2026'; }
function add_action($hook, $callback, $priority = 10) { unset($hook, $callback, $priority); }

class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }
class Parcs_HT_Group_Quotes {
    const OPTION = 'parcs_ht_group_quotes';
    public static function settings($public = true) { unset($public); return get_option(self::OPTION, array()); }
    public static function binding_for_year($year, $settings = null) {
        unset($settings);
        return $year === '2026' ? array('column_id'=>'tariff_col_000001') : ($year === '2027' ? array('column_id'=>'tariff_col_000002') : null);
    }
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root . '/includes/class-parcs-ht-quote-activation-migration.php';

function verify_migration($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$GLOBALS['options'][Parcs_HT_Defaults::OPTION] = array(
    'seasons'=>array(
        '2026'=>array('published'=>'1','group_quotes_enabled'=>'0'),
        '2027'=>array('published'=>'0','group_quotes_enabled'=>'0'),
    ),
);
$GLOBALS['options'][Parcs_HT_Group_Quotes::OPTION] = array(
    'tariff_bindings'=>array(
        '2026'=>array('column_id'=>'tariff_col_000001'),
        '2027'=>array('column_id'=>'tariff_col_000002'),
    ),
);

Parcs_HT_Quote_Activation_Migration::run();
$all = get_option(Parcs_HT_Defaults::OPTION, array());
verify_migration(($all['seasons']['2026']['group_quotes_enabled'] ?? '') === '1', 'historical 2026 quote activation is restored once');
verify_migration(($all['seasons']['2027']['group_quotes_enabled'] ?? '') === '0', 'future 2027 activation is never inferred by migration');
verify_migration(get_option(Parcs_HT_Quote_Activation_Migration::MARKER, '0') === '1', 'migration marker is persisted');

// After the one-time repair, a deliberate manual OFF must remain authoritative.
$GLOBALS['options'][Parcs_HT_Defaults::OPTION]['seasons']['2026']['group_quotes_enabled'] = '0';
Parcs_HT_Quote_Activation_Migration::run();
$all = get_option(Parcs_HT_Defaults::OPTION, array());
verify_migration(($all['seasons']['2026']['group_quotes_enabled'] ?? '') === '0', 'manual 2026 OFF remains authoritative after migration');

echo "Quote activation migration runtime: OK\n";
