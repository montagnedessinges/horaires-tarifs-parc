<?php

define('ABSPATH', __DIR__);

$GLOBALS['htp_quote_options'] = array();

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['htp_quote_options']) ? $GLOBALS['htp_quote_options'][$name] : $default; }
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

function quote_future_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-quotes.php';

$column = 'tariff_col_000001';
$child = 'tariff_row_000001';
$adult = 'tariff_row_000002';
$disability = 'tariff_row_000003';

$rows_2026 = array(
    array('id'=>$child,'enabled'=>'1','cells'=>array($column=>array('value'=>'6 €'))),
    array('id'=>$adult,'enabled'=>'1','cells'=>array($column=>array('value'=>'8,50 €'))),
    array('id'=>$disability,'enabled'=>'1','cells'=>array($column=>array('value'=>'6 €'))),
);
$rows_2027 = $rows_2026;
$rows_2027[0]['cells'][$column]['value'] = '6,50 €';
$rows_2027[1]['cells'][$column]['value'] = '9 €';
$rows_2027[2]['cells'][$column]['value'] = '6,50 €';

$GLOBALS['htp_quote_options'][Parcs_HT_Defaults::OPTION] = array(
    'seasons'=>array(
        '2026'=>array('tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column))), 'groups'=>$rows_2026)),
        '2027'=>array('tariffs'=>array('columns'=>array('groups'=>array(array('id'=>$column))), 'groups'=>$rows_2027)),
    ),
);
$GLOBALS['htp_quote_options'][Parcs_HT_Group_Quotes::OPTION] = array(
    'tariff_bindings'=>array(
        '2026'=>array(
            'column_id'=>$column,
            'child_row_id'=>$child,
            'adult_row_id'=>$adult,
            'disability_row_id'=>$disability,
            'companion_row_id'=>$disability,
            'free_adult_children'=>'10',
            'free_adult_round_threshold'=>'5',
        ),
    ),
    // Reproduit l'installation historique : l'ancien cache du devis ne contient que 2026.
    'seasons'=>array('2026'=>array('child'=>'6','adult'=>'8.50','disability'=>'6','companion'=>'6')),
);

$binding_2027 = Parcs_HT_Group_Quotes::binding_for_year('2027', Parcs_HT_Group_Quotes::settings(false));
quote_future_assert(is_array($binding_2027), '2027 inherits a stable quote binding when the duplicated tariff IDs still exist');
quote_future_assert(($binding_2027['adult_row_id'] ?? '') === $adult, 'inherited 2027 binding keeps the adult business identity');

$public = Parcs_HT_Group_Quotes::settings(true);
quote_future_assert(isset($public['seasons']['2027']), 'public quote settings discover 2027 from canonical seasons even when legacy quote storage only knew 2026');
quote_future_assert((string)($public['seasons']['2027']['published'] ?? '0') === '1', 'published 2027 group rates are exposed to the online quote gate');
quote_future_assert((string)($public['seasons']['2027']['child'] ?? '') === '6.5', '2027 child price comes from the canonical 2027 grid');
quote_future_assert((string)($public['seasons']['2027']['adult'] ?? '') === '9', '2027 adult price comes from the canonical 2027 grid');
quote_future_assert((string)($public['seasons']['2027']['disability'] ?? '') === '6.5', '2027 disability price comes from the canonical 2027 grid');
quote_future_assert((string)($public['seasons']['2027']['companion'] ?? '') === '6.5', '2027 companion price comes from the canonical 2027 grid');

echo "Future group quote season runtime: OK\n";
