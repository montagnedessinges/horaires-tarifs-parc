<?php

define('ABSPATH', __DIR__);
$GLOBALS['htp_quote_options'] = array();

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['htp_quote_options']) ? $GLOBALS['htp_quote_options'][$name] : $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['htp_quote_options'][$name] = $value; return true; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function wp_date($format, $timestamp = null, $timezone = null) { return $format === 'Y' ? '2026' : date($format, $timestamp ?: time()); }
function remove_accents($value) { return strtr((string)$value, array('é'=>'e','è'=>'e','ê'=>'e','à'=>'a','ù'=>'u','ô'=>'o','î'=>'i')); }
function is_admin() { return false; }
function current_user_can($capability) { return false; }

final class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
    public static function all_settings() { return get_option(self::OPTION, array()); }
}

final class Parcs_HT_Tariff_Identities {
    public static function is_row_id($id) { return (bool)preg_match('/^tariff_row_\d{6,}$/', (string)$id); }
    public static function is_column_id($id) { return (bool)preg_match('/^tariff_col_\d{6,}$/', (string)$id); }
    public static function column_exists($columns, $id) { foreach ((array)$columns as $column) if (is_array($column) && (string)($column['id'] ?? '') === (string)$id) return true; return false; }
    public static function row_by_id($rows, $id) { foreach ((array)$rows as $row) if (is_array($row) && (string)($row['id'] ?? '') === (string)$id) return $row; return null; }
}

function quote_future_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

function quote_future_rows($column, $prefix, $adult, $child, $disability) {
    return array(
        array('id'=>$prefix.'1','enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array($column=>array('value'=>$adult))),
        array('id'=>$prefix.'2','enabled'=>'1','label'=>array('fr'=>'Scolaire / extrascolaire'),'cells'=>array($column=>array('value'=>$child))),
        array('id'=>$prefix.'3','enabled'=>'1','label'=>array('fr'=>'Personne en situation de handicap et accompagnateur'),'cells'=>array($column=>array('value'=>$disability))),
    );
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-quotes.php';

$column_2026 = 'tariff_col_100001';
$column_2027 = 'tariff_col_200001';
$rows_2026 = quote_future_rows($column_2026, 'tariff_row_10000', '8,50 €', '6 €', '6 €');
$rows_2027 = quote_future_rows($column_2027, 'tariff_row_20000', '9 €', '6,50 €', '6,50 €');

$GLOBALS['htp_quote_options'][Parcs_HT_Defaults::OPTION] = array('seasons'=>array(
    '2026'=>array('group_quotes_enabled'=>'1','tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column_2026))), 'groups'=>$rows_2026)),
    '2027'=>array('group_quotes_enabled'=>'1','tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column_2027))), 'groups'=>$rows_2027)),
));
// L'ancien stockage ne connaît volontairement que 2026. Le nouveau moteur ne doit rien hériter de cette année pour 2027.
$GLOBALS['htp_quote_options'][Parcs_HT_Group_Quotes::OPTION] = array(
    'tariff_bindings'=>array('2026'=>array(
        'column_id'=>$column_2026,
        'adult_row_id'=>'tariff_row_100001','child_row_id'=>'tariff_row_100002',
        'disability_row_id'=>'tariff_row_100003','companion_row_id'=>'tariff_row_100003',
        'free_adult_children'=>'10','free_adult_round_threshold'=>'5',
    )),
    'seasons'=>array('2026'=>array('child'=>'6','adult'=>'8.50','disability'=>'6','companion'=>'6')),
);

$binding_2027 = Parcs_HT_Group_Quotes::binding_for_year('2027', Parcs_HT_Group_Quotes::settings(false));
quote_future_assert(is_array($binding_2027), '2027 resolves a binding from its own canonical grid');
quote_future_assert(($binding_2027['column_id'] ?? '') === $column_2027, '2027 never inherits the 2026 column');
quote_future_assert(($binding_2027['adult_row_id'] ?? '') === 'tariff_row_200001', '2027 uses its own adult row identity');
quote_future_assert(($binding_2027['child_row_id'] ?? '') === 'tariff_row_200002', '2027 uses its own child row identity');

$public = Parcs_HT_Group_Quotes::settings(true);
quote_future_assert((string)($public['seasons']['2026']['published'] ?? '0') === '1', '2026 remains available');
quote_future_assert((string)($public['seasons']['2027']['published'] ?? '0') === '1', '2027 is independently available');
quote_future_assert((string)($public['seasons']['2027']['child'] ?? '') === '6.5', '2027 child price comes only from the 2027 grid');
quote_future_assert((string)($public['seasons']['2027']['adult'] ?? '') === '9', '2027 adult price comes only from the 2027 grid');

echo "Future group quote season runtime: OK\n";
