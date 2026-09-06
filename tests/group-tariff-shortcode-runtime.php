<?php

// Test d'exécution réel du shortcode Tarifs groupes avec données historiques et actuelles.
define('ABSPATH', __DIR__);
define('PARCS_HT_URL', 'https://example.test/wp-content/plugins/horaires-tarifs-parc/');
define('PARCS_HT_VERSION', 'test');

$GLOBALS['parcs_ht_test_shortcodes'] = array();
$GLOBALS['parcs_ht_test_options'] = array();

function add_shortcode($tag, $callback) { $GLOBALS['parcs_ht_test_shortcodes'][$tag] = $callback; }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['parcs_ht_test_options']) ? $GLOBALS['parcs_ht_test_options'][$key] : $default; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function sanitize_hex_color($value) { return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? (string)$value : null; }
function wp_style_is($handle, $state = 'enqueued') { return false; }
function wp_register_style($handle, $src, $deps = array(), $version = false) { return true; }
function wp_enqueue_style($handle) { return true; }
function did_action($hook) { return 0; }
function wp_print_styles($handles = false) { return array(); }
function wp_date($format, $timestamp = null, $timezone = null) { return $format === 'Y-m-d' ? '2026-09-06' : date($format, $timestamp ?: time()); }
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return (string)$value; }
function wp_json_encode($value) { return json_encode($value); }

final class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
}

final class Parcs_HT_Schedule {
    public static function language() { return 'fr'; }
    public static function translation($value, $language, $fallback = '') {
        if (is_array($value) && isset($value[$language]) && (string)$value[$language] !== '') return (string)$value[$language];
        if (is_array($value)) {
            foreach (array('fr','en','de') as $lang) if (!empty($value[$lang])) return (string)$value[$lang];
        }
        return (string)$fallback;
    }
}

final class Parcs_HT_Group_Tariff_Settings {
    public static function published_years() { return array('2026'); }
    public static function public_year() { return '2026'; }
    public static function settings($year) {
        return array(
            'published'=>'1',
            'show_heading'=>'1',
            'title'=>array('fr'=>'Tarifs groupes','en'=>'Group rates','de'=>'Gruppentarife'),
            'intro'=>array('fr'=>'','en'=>'','de'=>''),
            'show_future_notice'=>'0',
            'future_year'=>'2027',
            'future_notice'=>array('fr'=>'','en'=>'','de'=>''),
            'show_quote_button'=>'0',
            'button_label'=>array('fr'=>'','en'=>'','de'=>''),
            'button_url'=>array('fr'=>'','en'=>'','de'=>''),
        );
    }
    public static function default_title($language, $year) { return 'Tarifs groupes ' . $year; }
    public static function default_future_notice($language, $year) { return ''; }
    public static function is_published($year) { return $year === '2026'; }
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-tariffs.php';

function group_runtime_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

Parcs_HT_Group_Tariffs::init();
foreach (array('parc_tarifs_groupes','parc_tarifs_groupes_fr','parc_tarifs_groupes_en','parc_tarifs_groupes_de') as $tag) {
    group_runtime_assert(isset($GLOBALS['parcs_ht_test_shortcodes'][$tag]), 'registered shortcode ' . $tag);
}

// Cas réellement problématique : ancienne colonne "price", ligne sans enabled/cells,
// tarif encore stocké directement dans row[price]. Le shortcode doit l'afficher.
$GLOBALS['parcs_ht_test_options'][Parcs_HT_Defaults::OPTION] = array(
    'general'=>array(),
    'seasons'=>array(
        '2026'=>array(
            'published'=>'1',
            'tariffs'=>array(
                'columns'=>array(
                    'groups'=>array(
                        array('id'=>'price','visible'=>'1','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')),
                    ),
                ),
                'groups'=>array(
                    array(
                        'label'=>array('fr'=>'Senior','en'=>'Senior','de'=>'Senior'),
                        'detail'=>array('fr'=>'Groupe senior','en'=>'Senior group','de'=>'Seniorengruppe'),
                        'price'=>'9 €',
                    ),
                ),
            ),
        ),
    ),
);

$html = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html, 'Senior') !== false, 'legacy group row is rendered');
group_runtime_assert(strpos($html, '9 €') !== false, 'legacy row price is rendered');
group_runtime_assert(strpos($html, 'ne sont pas disponibles') === false, 'legacy data does not fall back to unavailable message');

// Format actuel avec IDs permanents : la compatibilité descendante ne doit rien casser.
$GLOBALS['parcs_ht_test_options'][Parcs_HT_Defaults::OPTION]['seasons']['2026']['tariffs'] = array(
    'columns'=>array(
        'groups'=>array(
            array('id'=>'tariff_col_000001','visible'=>'1','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')),
        ),
    ),
    'groups'=>array(
        array(
            'id'=>'tariff_row_000001',
            'enabled'=>'1',
            'label'=>array('fr'=>'Adulte groupe','en'=>'Group adult','de'=>'Gruppenerwachsene'),
            'cells'=>array(
                'tariff_col_000001'=>array('value'=>'8,50 €','old_value'=>''),
            ),
        ),
    ),
);

$html = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html, 'Adulte groupe') !== false, 'current permanent-ID row is rendered');
group_runtime_assert(strpos($html, '8,50 €') !== false, 'current permanent-ID value is rendered');

echo "Group tariff shortcode runtime: OK\n";
