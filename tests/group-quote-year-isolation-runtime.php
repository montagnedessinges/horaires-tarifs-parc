<?php

define('ABSPATH', __DIR__);

$GLOBALS['htp_quote_isolation_options'] = array();

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['htp_quote_isolation_options']) ? $GLOBALS['htp_quote_isolation_options'][$name] : $default; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }

final class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
    public static function all_settings() { return get_option(self::OPTION, array()); }
}

final class Parcs_HT_Group_Tariff_Settings {
    public static function quote_enabled($year) { return in_array((string)$year, array('2026','2027'), true); }
}

final class Parcs_HT_Tariff_Identities {
    public static function is_row_id($id) { return (bool)preg_match('/^tariff_row_\d{6,}$/', (string)$id); }
    public static function is_column_id($id) { return (bool)preg_match('/^tariff_col_\d{6,}$/', (string)$id); }
    public static function column_exists($columns, $id) {
        foreach ((array)$columns as $column) if (is_array($column) && (string)($column['id'] ?? '') === (string)$id) return true;
        return false;
    }
    public static function row_by_id($rows, $id) {
        foreach ((array)$rows as $row) if (is_array($row) && (string)($row['id'] ?? '') === (string)$id) return $row;
        return null;
    }
}

function quote_isolation_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

function quote_isolation_rows($column, $prefix, $adult_price, $child_price, $disability_price) {
    return array(
        array('id'=>$prefix . '1','enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array($column=>array('value'=>$adult_price))),
        array('id'=>$prefix . '2','enabled'=>'1','label'=>array('fr'=>'Enfant'),'cells'=>array($column=>array('value'=>'0 €'))),
        array('id'=>$prefix . '3','enabled'=>'1','label'=>array('fr'=>'Scolaire / extrascolaire'),'cells'=>array($column=>array('value'=>$child_price))),
        array('id'=>$prefix . '4','enabled'=>'1','label'=>array('fr'=>'Personne en situation de handicap et accompagnateur'),'cells'=>array($column=>array('value'=>$disability_price))),
    );
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-quotes.php';

$column_2026 = 'tariff_col_100001';
$column_2027 = 'tariff_col_200001';
$rows_2026 = quote_isolation_rows($column_2026, 'tariff_row_10000', '8,50 €', '6 €', '6 €');
$rows_2027 = quote_isolation_rows($column_2027, 'tariff_row_20000', '9 €', '6,50 €', '6,50 €');

$GLOBALS['htp_quote_isolation_options'][Parcs_HT_Defaults::OPTION] = array(
    'seasons'=>array(
        '2026'=>array('tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column_2026))), 'groups'=>$rows_2026)),
        '2027'=>array('tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column_2027))), 'groups'=>$rows_2027)),
    ),
);

$GLOBALS['htp_quote_isolation_options'][Parcs_HT_Group_Quotes::OPTION] = array(
    // Reproduit le cas problématique : seule la liaison stable 2027 est présente.
    // Elle ne doit jamais être appliquée à la grille 2026 si ses IDs sont différents.
    'tariff_bindings'=>array(
        '2027'=>array(
            'column_id'=>$column_2027,
            'adult_row_id'=>'tariff_row_200001',
            'child_row_id'=>'tariff_row_200003',
            'disability_row_id'=>'tariff_row_200004',
            'companion_row_id'=>'tariff_row_200004',
            'free_adult_children'=>'10',
            'free_adult_round_threshold'=>'5',
        ),
    ),
    'tariff_binding'=>Parcs_HT_Group_Quotes::legacy_binding_defaults(),
    'seasons'=>array('2026'=>array('child'=>'6','adult'=>'8.50','disability'=>'6','companion'=>'6')),
);

$binding_2026 = Parcs_HT_Group_Quotes::binding_for_year('2026', Parcs_HT_Group_Quotes::settings(false));
quote_isolation_assert(is_array($binding_2026), '2026 rebuilds a target-year binding when only an incompatible 2027 stable binding exists');
quote_isolation_assert(($binding_2026['column_id'] ?? '') === $column_2026, '2026 keeps its own tariff column');
quote_isolation_assert(($binding_2026['adult_row_id'] ?? '') === 'tariff_row_100001', '2026 keeps its own adult row identity');
quote_isolation_assert(($binding_2026['child_row_id'] ?? '') === 'tariff_row_100003', '2026 keeps its own child row identity');

$season_2026 = Parcs_HT_Group_Quotes::season_for_year('2026');
quote_isolation_assert(is_array($season_2026), '2026 remains available to the quote engine after 2027 is configured');
quote_isolation_assert((string)($season_2026['adult'] ?? '') === '8.5', '2026 quote keeps the 2026 adult price');
quote_isolation_assert((string)($season_2026['child'] ?? '') === '6', '2026 quote keeps the 2026 child price');

$season_2027 = Parcs_HT_Group_Quotes::season_for_year('2027');
quote_isolation_assert(is_array($season_2027), '2027 remains independently available to the quote engine');
quote_isolation_assert((string)($season_2027['adult'] ?? '') === '9', '2027 quote keeps the 2027 adult price');
quote_isolation_assert((string)($season_2027['child'] ?? '') === '6.5', '2027 quote keeps the 2027 child price');

$public = Parcs_HT_Group_Quotes::settings(true);
quote_isolation_assert((string)($public['seasons']['2026']['published'] ?? '0') === '1', 'public quote payload exposes 2026');
quote_isolation_assert((string)($public['seasons']['2027']['published'] ?? '0') === '1', 'public quote payload exposes 2027 without blocking 2026');

echo "Group quote year isolation runtime: OK\n";
