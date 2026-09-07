<?php

// Test d'exécution du shortcode Tarifs groupes : il doit lire exclusivement
// la même grille tariffs.groups de la saison publique que le tableau principal.
define('ABSPATH', __DIR__);
define('PARCS_HT_URL', 'https://example.test/wp-content/plugins/horaires-tarifs-parc/');
define('PARCS_HT_VERSION', 'test');

$GLOBALS['parcs_ht_test_shortcodes'] = array();
$GLOBALS['parcs_ht_test_settings'] = array();
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
    public static function svg_allowed_tags() {
        return array(
            'svg'=>array('viewBox'=>true,'viewbox'=>true,'width'=>true,'height'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'stroke-linecap'=>true,'stroke-linejoin'=>true,'focusable'=>true,'aria-hidden'=>true),
            'rect'=>array('x'=>true,'y'=>true,'width'=>true,'height'=>true,'rx'=>true,'fill'=>true,'stroke'=>true),
            'path'=>array('d'=>true,'fill'=>true,'stroke'=>true),
            'circle'=>array('cx'=>true,'cy'=>true,'r'=>true,'fill'=>true,'stroke'=>true),
        );
    }
}

final class Parcs_HT_Tariff_Seasons {
    public static function select_season_tariffs($settings, $public = false) {
        $GLOBALS['parcs_ht_tariff_selector_called']++;
        return $settings;
    }
}

final class Parcs_HT_Schedule {
    public static function language() { return 'fr'; }
    public static function timezone($settings = array()) { return 'Europe/Paris'; }
    public static function translation($value, $language, $fallback = '') {
        if (is_array($value) && isset($value[$language]) && (string)$value[$language] !== '') return (string)$value[$language];
        if (is_array($value)) {
            foreach (array('fr','en','de') as $lang) if (!empty($value[$lang])) return (string)$value[$lang];
        }
        return (string)$fallback;
    }
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

$GLOBALS['parcs_ht_test_settings'] = array(
    'site_type'=>'mds',
    'general'=>array(
        'year'=>'2026',
        'groups_booking_note'=>array('fr'=>'Réservation groupe recommandée.','en'=>'Group booking recommended.','de'=>'Gruppenreservierung empfohlen.'),
        'groups_button_label'=>array('fr'=>'Faire une demande de devis','en'=>'Request a quote','de'=>'Angebot anfordern'),
        'groups_url'=>array('fr'=>'https://example.test/devis','en'=>'https://example.test/en/quote','de'=>'https://example.test/de/angebot'),
        'primary_color'=>'#006757',
        'payment_item_bg_color'=>'#006757',
        'payment_item_text_color'=>'#ffffff',
        'payment_icon_color'=>'#ffffff',
    ),
    'tariffs'=>array(
        'columns'=>array(
            'groups'=>array(
                array('id'=>'tariff_col_000001','visible'=>'1','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')),
            ),
        ),
        'individual'=>array(
            array('enabled'=>'1','label'=>array('fr'=>'Individuel à ne pas afficher'),'cells'=>array('price'=>array('value'=>'99 €'))),
        ),
        'groups'=>array(
            array(
                'id'=>'tariff_row_000001',
                'enabled'=>'1',
                'label'=>array('fr'=>'Senior','en'=>'Senior','de'=>'Senior'),
                'subtitle'=>array('fr'=>'Groupe senior','en'=>'Senior group','de'=>'Seniorengruppe'),
                'cells'=>array('tariff_col_000001'=>array('value'=>'9 €','old_value'=>'')),
            ),
            array(
                'id'=>'tariff_row_000002',
                'enabled'=>'0',
                'label'=>array('fr'=>'Ligne masquée'),
                'cells'=>array('tariff_col_000001'=>array('value'=>'1 €','old_value'=>'')),
            ),
        ),
    ),
);

$html = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert($GLOBALS['parcs_ht_tariff_selector_called'] > 0, 'public tariff season selector is used');
group_runtime_assert(strpos($html, 'Tarifs groupes 2026') !== false, 'standalone group heading is rendered');
group_runtime_assert(strpos($html, 'Senior') !== false, 'canonical group row is rendered');
group_runtime_assert(strpos($html, '9 €') !== false, 'canonical group price is rendered');
group_runtime_assert(strpos($html, 'Individuel à ne pas afficher') === false, 'individual tariffs are not rendered');
group_runtime_assert(strpos($html, 'Ligne masquée') === false, 'disabled group row stays hidden');
group_runtime_assert(strpos($html, 'https://example.test/devis') !== false, 'canonical group URL is rendered');

group_runtime_assert(strpos($html, 'Moyens de paiement') !== false, 'MDS group payment strip is rendered');
foreach (array('Carte bancaire','Espèces','Chèque','Bon de commande / voucher','Chorus Pro') as $payment_label) {
    group_runtime_assert(strpos($html, $payment_label) !== false, 'MDS group payment method rendered: ' . $payment_label);
}
$payment_pos = strpos($html, 'parcs-ht-group-payment-strip');
$prices_pos = strpos($html, 'parcs-ht-price-list');
group_runtime_assert($payment_pos !== false && $prices_pos !== false && $payment_pos < $prices_pos, 'group payment strip is displayed before group prices like the classic tariff block');
group_runtime_assert(strpos($html, 'Paiement et facturation') !== false, 'MDS payment and invoicing guide is rendered');
group_runtime_assert(strpos($html, 'nombre réel de participants présents') !== false, 'actual attendance invoicing rule is rendered');
group_runtime_assert(strpos($html, 'Devis et réservation') !== false, 'MDS quote and booking guide is rendered');
group_runtime_assert(strpos($html, 'généré automatiquement') !== false, 'automatic quote generation is explained');
group_runtime_assert(strpos($html, 'Bon pour accord') !== false, 'signed quote confirmation rule is rendered');
group_runtime_assert(strpos($html, 'Réservation groupe recommandée.') === false, 'legacy generic MDS booking note does not contradict the structured mandatory booking guide');

// Le contenu MDS ne doit jamais être appliqué automatiquement à la Forêt des Singes.
$GLOBALS['parcs_ht_test_settings']['site_type'] = 'fds';
$html_fds = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_fds, 'parcs-ht-group-payment-strip') === false, 'MDS payment strip does not leak to FDS');
group_runtime_assert(strpos($html_fds, 'Paiement et facturation') === false, 'MDS payment guide does not leak to FDS');
group_runtime_assert(strpos($html_fds, 'Réservation groupe recommandée.') !== false, 'non-MDS generic booking note remains unchanged');

// Si la source canonique change, le shortcode doit changer immédiatement,
// sans copie de prix ni option tarifaire parallèle.
$GLOBALS['parcs_ht_test_settings']['site_type'] = 'mds';
$GLOBALS['parcs_ht_test_settings']['tariffs']['groups'][0]['cells']['tariff_col_000001']['value'] = '10 €';
$html_updated = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_updated, '10 €') !== false, 'updated canonical group price is read directly');
group_runtime_assert(strpos($html_updated, '9 €') === false, 'no duplicated stale group price remains');

$source = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
group_runtime_assert(strpos($source, 'Parcs_HT_Group_Tariff_Settings') === false, 'shortcode has no separate group tariff settings dependency');
group_runtime_assert(strpos($source, 'Parcs_HT_Defaults::settings()') !== false, 'shortcode reads canonical settings');
group_runtime_assert(strpos($source, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') !== false, 'shortcode uses the same public season selection as the main tariff table');

echo "Group tariff shortcode runtime: OK\n";
