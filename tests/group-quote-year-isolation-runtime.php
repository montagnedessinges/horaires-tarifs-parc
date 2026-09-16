<?php

define('ABSPATH', __DIR__);
$GLOBALS['htp_quote_isolation_options'] = array();

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['htp_quote_isolation_options']) ? $GLOBALS['htp_quote_isolation_options'][$name] : $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['htp_quote_isolation_options'][$name] = $value; return true; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function sanitize_text_field($value) { return trim((string)$value); }
function wp_unslash($value) { return $value; }
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

function quote_isolation_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

function quote_isolation_rows($column, $prefix, $adult_price, $child_price, $disability_price) {
    return array(
        array('id'=>$prefix.'1','enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array($column=>array('value'=>$adult_price))),
        array('id'=>$prefix.'2','enabled'=>'1','label'=>array('fr'=>'Scolaire / extrascolaire'),'cells'=>array($column=>array('value'=>$child_price))),
        array('id'=>$prefix.'3','enabled'=>'1','label'=>array('fr'=>'Personne en situation de handicap et accompagnateur'),'cells'=>array($column=>array('value'=>$disability_price))),
    );
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-quotes.php';

$column_2026 = 'tariff_col_100001';
$column_2027 = 'tariff_col_200001';
$rows_2026 = quote_isolation_rows($column_2026, 'tariff_row_10000', '8,50 €', '6 €', '6 €');
$rows_2027 = quote_isolation_rows($column_2027, 'tariff_row_20000', '9 €', '6,50 €', '6,50 €');

$GLOBALS['htp_quote_isolation_options'][Parcs_HT_Defaults::OPTION] = array('seasons'=>array(
    '2026'=>array('group_quotes_enabled'=>'0','tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column_2026))), 'groups'=>$rows_2026)),
    '2027'=>array('group_quotes_enabled'=>'1','tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column_2027))), 'groups'=>$rows_2027)),
));
$GLOBALS['htp_quote_isolation_options'][Parcs_HT_Group_Quotes::OPTION] = array(
    // Preuve historique : 2026 fonctionnait avant l'introduction des interrupteurs annuels.
    'seasons'=>array('2026'=>array('child'=>'6','adult'=>'8.50','disability'=>'6','companion'=>'6')),
    'tariff_bindings'=>array('2027'=>array(
        'column_id'=>$column_2027,
        'adult_row_id'=>'tariff_row_200001','child_row_id'=>'tariff_row_200002',
        'disability_row_id'=>'tariff_row_200003','companion_row_id'=>'tariff_row_200003',
        'free_adult_children'=>'10','free_adult_round_threshold'=>'5',
    )),
);

// Migration unique : le 0 historique erroné de 2026 est réparé grâce à la preuve de l'ancien moteur,
// tandis que 2027 reste activé explicitement. Après cette migration, seul le nouvel état annuel compte.
$season_2026 = Parcs_HT_Group_Quotes::season_for_year('2026');
$season_2027 = Parcs_HT_Group_Quotes::season_for_year('2027');
quote_isolation_assert(is_array($season_2026), 'historical 2026 is restored during the one-time state migration');
quote_isolation_assert(is_array($season_2027), 'explicit 2027 remains independently enabled');
quote_isolation_assert((string)$season_2026['adult'] === '8.5', '2026 uses the 2026 adult price');
quote_isolation_assert((string)$season_2027['adult'] === '9', '2027 uses the 2027 adult price');

$state = get_option(Parcs_HT_Group_Quotes::STATE_OPTION, array());
quote_isolation_assert((string)($state['years']['2026']['enabled'] ?? '0') === '1', '2026 owns its own enabled state');
quote_isolation_assert((string)($state['years']['2027']['enabled'] ?? '0') === '1', '2027 owns its own enabled state');

// Désactiver 2027 dans le nouvel état ne doit jamais modifier 2026.
$state['years']['2027']['enabled'] = '0';
update_option(Parcs_HT_Group_Quotes::STATE_OPTION, $state, false);
quote_isolation_assert(is_array(Parcs_HT_Group_Quotes::season_for_year('2026')), 'turning 2027 off leaves 2026 available');
quote_isolation_assert(Parcs_HT_Group_Quotes::season_for_year('2027') === null, '2027 is unavailable when its own state is off');

// Et l'inverse doit être vrai.
$state['years']['2026']['enabled'] = '0';
$state['years']['2027']['enabled'] = '1';
update_option(Parcs_HT_Group_Quotes::STATE_OPTION, $state, false);
quote_isolation_assert(Parcs_HT_Group_Quotes::season_for_year('2026') === null, '2026 can be disabled without touching 2027');
quote_isolation_assert(is_array(Parcs_HT_Group_Quotes::season_for_year('2027')), '2027 remains available when only 2027 is enabled');

// Les liaisons restent également strictement séparées.
$binding_2026 = Parcs_HT_Group_Quotes::binding_for_year('2026', Parcs_HT_Group_Quotes::settings(false));
$binding_2027 = Parcs_HT_Group_Quotes::binding_for_year('2027', Parcs_HT_Group_Quotes::settings(false));
quote_isolation_assert(($binding_2026['column_id'] ?? '') === $column_2026, '2026 binding stays on the 2026 column');
quote_isolation_assert(($binding_2027['column_id'] ?? '') === $column_2027, '2027 binding stays on the 2027 column');

echo "Group quote year and activation isolation runtime: OK\n";
