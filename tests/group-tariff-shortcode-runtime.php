<?php

define('ABSPATH', __DIR__);
define('PARCS_HT_URL', 'https://example.test/wp-content/plugins/horaires-tarifs-parc/');
define('PARCS_HT_VERSION', 'test');

$GLOBALS['parcs_ht_test_shortcodes'] = array();
$GLOBALS['parcs_ht_test_settings'] = array();
$GLOBALS['parcs_ht_test_group_display'] = array();
$GLOBALS['parcs_ht_tariff_selector_called'] = 0;

function add_shortcode($tag, $callback) { $GLOBALS['parcs_ht_test_shortcodes'][$tag] = $callback; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function sanitize_hex_color($value) { return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? (string)$value : null; }
function wp_style_is($handle, $state = 'enqueued') { return false; }
function wp_register_style($handle, $src, $deps = array(), $version = false) { return true; }
function wp_enqueue_style($handle) { return true; }
function did_action($hook) { return 0; }
function wp_print_styles($handles = false) { return array(); }
function wp_date($format, $timestamp = null, $timezone = null) { return $format === 'Y-m-d' ? '2026-09-07' : date($format, $timestamp ?: time()); }
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return (string)$value; }
function wp_kses($value, $allowed_html) { return (string)$value; }

final class Parcs_HT_Defaults {
    public static function settings() { return $GLOBALS['parcs_ht_test_settings']; }
    public static function all_settings() { return $GLOBALS['parcs_ht_test_settings']; }
    public static function svg_allowed_tags() { return array('svg'=>array(),'rect'=>array(),'path'=>array(),'circle'=>array()); }
}
final class Parcs_HT_Tariff_Seasons {
    public static function select_season_tariffs($settings, $public = false) { $GLOBALS['parcs_ht_tariff_selector_called']++; return $settings; }
}
final class Parcs_HT_Schedule {
    public static function language() { return 'fr'; }
    public static function timezone($settings = array()) { return 'Europe/Paris'; }
    public static function translation($value, $language, $fallback = '') {
        if (is_array($value) && isset($value[$language]) && (string)$value[$language] !== '') return (string)$value[$language];
        if (is_array($value)) foreach (array('fr','en','de') as $lang) if (!empty($value[$lang])) return (string)$value[$lang];
        return (string)$fallback;
    }
}
final class Parcs_HT_Group_Tariff_Settings {
    public static function settings($year) { return $GLOBALS['parcs_ht_test_group_display']; }
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-tariffs.php';

function group_runtime_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

Parcs_HT_Group_Tariffs::init();
foreach (array('parc_tarifs_groupes','parc_tarifs_groupes_fr','parc_tarifs_groupes_en','parc_tarifs_groupes_de') as $tag) group_runtime_assert(isset($GLOBALS['parcs_ht_test_shortcodes'][$tag]), 'registered shortcode ' . $tag);

$GLOBALS['parcs_ht_test_settings'] = array(
    'site_type'=>'fds',
    'general'=>array(
        'year'=>'2026',
        'groups_booking_note'=>array('fr'=>'Note générique de réservation.'),
        'groups_button_label'=>array('fr'=>'Bouton général'),
        'groups_url'=>array('fr'=>'https://example.test/general'),
        'primary_color'=>'#006757','payment_item_bg_color'=>'#006757','payment_item_text_color'=>'#ffffff','payment_icon_color'=>'#ffffff',
    ),
    'tariffs'=>array(
        'columns'=>array('groups'=>array(array('id'=>'tariff_col_000001','visible'=>'1','label'=>array('fr'=>'Tarif')))),
        'individual'=>array(array('enabled'=>'1','label'=>array('fr'=>'Individuel à ne pas afficher'),'cells'=>array('price'=>array('value'=>'99 €')))),
        'groups'=>array(
            array('id'=>'tariff_row_000001','enabled'=>'1','label'=>array('fr'=>'Senior'),'subtitle'=>array('fr'=>'Groupe senior'),'cells'=>array('tariff_col_000001'=>array('value'=>'9 €','old_value'=>''))),
            array('id'=>'tariff_row_000002','enabled'=>'0','label'=>array('fr'=>'Ligne masquée'),'cells'=>array('tariff_col_000001'=>array('value'=>'1 €','old_value'=>''))),
        ),
    ),
);
$GLOBALS['parcs_ht_test_group_display'] = array(
    'show_heading'=>'1','title'=>array('fr'=>'Tarifs groupes personnalisés'),'intro'=>array('fr'=>'Introduction personnalisable.'),
    'show_payment_methods'=>'1','payment_title'=>array('fr'=>'Comment régler ?'),
    'payment_methods'=>array(
        array('enabled'=>'1','icon'=>'card','label'=>array('fr'=>'Carte test')),
        array('enabled'=>'1','icon'=>'bank','label'=>array('fr'=>'Virement test')),
    ),
    'show_info_blocks'=>'1','info_blocks'=>array(
        array('enabled'=>'1','title'=>array('fr'=>'Conditions test'),'text'=>array('fr'=>'Texte entièrement configurable.')),
    ),
    'show_quote_button'=>'1','button_label'=>array('fr'=>'Demander maintenant'),'button_url'=>array('fr'=>'https://example.test/custom'),
);

$html = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert($GLOBALS['parcs_ht_tariff_selector_called'] > 0, 'public tariff season selector is used');
group_runtime_assert(strpos($html, 'Tarifs groupes personnalisés') !== false, 'custom heading is rendered');
group_runtime_assert(strpos($html, 'Introduction personnalisable.') !== false, 'custom intro is rendered');
group_runtime_assert(strpos($html, 'Senior') !== false && strpos($html, '9 €') !== false, 'canonical group row and price are rendered');
group_runtime_assert(strpos($html, 'Individuel à ne pas afficher') === false && strpos($html, 'Ligne masquée') === false, 'non-group and disabled rows stay hidden');
group_runtime_assert(strpos($html, 'Comment régler ?') !== false && strpos($html, 'Carte test') !== false && strpos($html, 'Virement test') !== false, 'configured payment methods are rendered');
group_runtime_assert(strpos($html, 'Conditions test') !== false && strpos($html, 'Texte entièrement configurable.') !== false, 'configured information block is rendered');
group_runtime_assert(strpos($html, 'https://example.test/custom') !== false && strpos($html, 'Demander maintenant') !== false, 'configured quote button is rendered');
$payment_pos = strpos($html, 'parcs-ht-group-payment-strip');$prices_pos = strpos($html, 'parcs-ht-price-list');$info_pos = strpos($html, 'Conditions test');
group_runtime_assert($payment_pos !== false && $prices_pos !== false && $payment_pos < $prices_pos && $info_pos > $prices_pos, 'visual order is payments then prices then information');

// Le site_type ne pilote plus ce contenu : chaque installation affiche uniquement ses propres réglages.
$GLOBALS['parcs_ht_test_settings']['site_type'] = 'other';
$GLOBALS['parcs_ht_test_group_display']['payment_methods'][0]['label']['fr'] = 'Paiement autre site';
$html_other = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_other, 'Paiement autre site') !== false, 'another site can use its own configured payment method');
group_runtime_assert(strpos($html_other, 'Carte test') === false, 'presentation updates are read directly from site settings');

$GLOBALS['parcs_ht_test_group_display']['show_payment_methods'] = '0';
$GLOBALS['parcs_ht_test_group_display']['show_info_blocks'] = '0';
$html_minimal = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_minimal, 'parcs-ht-group-payment-strip') === false, 'payment section can be disabled');
group_runtime_assert(strpos($html_minimal, 'Conditions test') === false, 'information section can be disabled');
group_runtime_assert(strpos($html_minimal, 'Note générique de réservation.') !== false, 'generic booking note remains as fallback when custom information is disabled');

$GLOBALS['parcs_ht_test_settings']['tariffs']['groups'][0]['cells']['tariff_col_000001']['value'] = '10 €';
$html_updated = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_updated, '10 €') !== false && strpos($html_updated, '9 €') === false, 'updated canonical group price is read directly');

$source = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
group_runtime_assert(strpos($source, 'Parcs_HT_Defaults::settings()') !== false, 'shortcode reads canonical tariff settings');
group_runtime_assert(strpos($source, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') !== false, 'shortcode uses the same public season selection as the main tariff table');
group_runtime_assert(strpos($source, 'Parcs_HT_Group_Tariff_Settings::settings') !== false, 'shortcode reads separate presentation settings only');
group_runtime_assert(strpos($source, 'is_mds(') === false && strpos($source, 'Bon de commande / voucher') === false, 'renderer contains no MDS-specific display rule or payment text');

echo "Group tariff shortcode runtime: OK\n";