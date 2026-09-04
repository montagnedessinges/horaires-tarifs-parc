<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$php = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$appearance = file_get_contents($root . '/includes/class-parcs-ht-guide-appearance.php');
$integrity = file_get_contents($root . '/includes/class-parcs-ht-save-integrity.php');
$save_js = file_get_contents($root . '/assets/admin-shortcodes-guides.js');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$css = file_get_contents($root . '/assets/pedagogical-guides.css');
$admin_css = file_get_contents($root . '/assets/pedagogical-guides-admin.css');

function guide_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

guide_contract(strpos($php, 'const STORE_VERSION = 3') !== false, 'Guide storage contract is version 3');
guide_contract(strpos($php, 'private static function cycle_catalog()') !== false, 'Cycles are a fixed reusable catalog');
guide_contract(strpos($php, "'cycle4'=>array(") !== false, 'Cycle 4 is supported');
guide_contract(strpos($php, "'fr'=>'Cycle 1'") !== false && strpos($php, "'fr'=>'Maternelle – 3 à 6 ans'") !== false, 'French filter keeps Cycle 1 and exposes its detail');
guide_contract(strpos($php, "'fr'=>'CP au CE2 – 6 à 9 ans'") !== false, 'French Cycle 2 wording uses CP au CE2');
guide_contract(strpos($php, "'fr'=>'CM1 à la 6e – 9 à 12 ans'") !== false, 'French Cycle 3 wording uses CM1 à la 6e');
guide_contract(strpos($php, "'fr'=>'5e à la 3e – 12 à 15 ans'") !== false, 'French Cycle 4 wording uses 5e à la 3e');
guide_contract(strpos($php, "'en'=>'Ages 6–9'") !== false && strpos($php, "'en'=>'Primary School – Ages 6–9'") !== false, 'English filter uses ages and exposes school detail');
guide_contract(strpos($php, "'de'=>'9–12 Jahre'") !== false && strpos($php, "'de'=>'Grundschule / Sekundarstufe I – 9–12 Jahre'") !== false, 'German filter uses ages and exposes school detail');
guide_contract(strpos($php, "'de'=>'Sekundarstufe I – 12–15 Jahre'") !== false, 'German Cycle 4 wording uses Jahre');
guide_contract(strpos($php, "add_shortcode('parc_guides_pedagogiques_' . \$language") !== false, 'Dedicated FR EN DE guide shortcodes remain registered');
guide_contract(strpos($php, 'data-guide-cycle-filters') !== false, 'Public cycle filters are rendered');
guide_contract(strpos($php, 'data-guide-info') !== false && strpos($php, 'aria-expanded') !== false, 'Each cycle filter has accessible touch-friendly information');
guide_contract(strpos($php, 'data-guide-language-filters') !== false, 'Public language filters are rendered');
guide_contract(strpos($php, "title=\"<?php echo esc_attr(\$m[\$language]);?>\"") !== false, 'Language filter flags retain accessible language names');
guide_contract(strpos($php, 'count($used_cycles)>1') !== false, 'Native cycle filters remain compact for multiple categories');
guide_contract(strpos($php, 'count($used_languages)>1') !== false, 'Language filters are hidden when only one language is available');
guide_contract(strpos($php, 'data-languages=') !== false && strpos($php, 'data-cycle=') !== false, 'Guide cards expose cycle and language filter data');
guide_contract(strpos($php, "cycle==='all'") !== false && strpos($php, "lang==='all'") !== false, 'Default public state displays all guides');
guide_contract(strpos($php, 'cm&&lm') !== false, 'Cycle and language filters combine');
guide_contract(strpos($php, 'Ajouter une catégorie') === false, 'Admin no longer exposes free category management');
guide_contract(strpos($php, 'Cycle / niveau') !== false && strpos($php, 'Langue(s) du document') !== false, 'Each guide directly owns its cycle and languages');
guide_contract(strpos($php, "'resources'=>'Dossiers pédagogiques'") !== false, 'French public guide title is Dossiers pédagogiques');
guide_contract(strpos($php, 'parcs-ht-guide-card-top') !== false && strpos($php, 'parcs-ht-guide-description') !== false, 'Guide card separates compact top metadata from full-width description and actions');
guide_contract(strpos($php, 'Taille recommandée : 900 × 1200 px') !== false && strpos($php, 'format vertical 3:4') !== false, 'Admin shows the recommended vertical source image format before upload');
guide_contract(strpos($php, '144 × 192 px sur ordinateur') !== false && strpos($php, '96 × 128 px sur mobile') !== false, 'Admin shows the new compact desktop and mobile display sizes');
guide_contract(strpos($admin_css, '.htp-guide-image-recommendation') !== false, 'Image recommendation has dedicated admin styling');
guide_contract(strpos($css, '.parcs-ht-guide-info-pop') !== false && strpos($css, '@media(max-width:800px)') !== false, 'Cycle information and public filters have responsive styles');
guide_contract(strpos($css, '.parcs-ht-guides-head{display:block') !== false, 'Public shortcode displays its own section title');
guide_contract(strpos($css, 'width:min(1400px,calc(100vw - 48px))') !== false && strpos($css, 'transform:translateX(-50%)') !== false, 'Desktop guide block can escape a narrow theme container while remaining viewport-safe');
guide_contract(strpos($css, 'grid-template-columns:repeat(auto-fit,minmax(520px,1fr))') !== false, 'Guide grid uses two columns only when enough width is available');
guide_contract(strpos($css, 'grid-template-columns:144px minmax(0,1fr)') !== false && strpos($css, 'width:144px;height:192px') !== false, 'Desktop guide cover uses the compact 144 by 192 display frame');
guide_contract(strpos($css, 'grid-template-columns:96px minmax(0,1fr)') !== false && strpos($css, 'width:96px;height:128px') !== false, 'Mobile guide cover uses the compact 96 by 128 display frame');
guide_contract(strpos($css, 'padding:0 2px 48px') === false && strpos($css, 'overflow-x:auto') === false && strpos($css, '.parcs-ht-guide-filters{overflow:visible;flex-wrap:wrap;padding:0}') !== false, 'Mobile filters use only their real wrapped height without reserved empty space');
guide_contract(strpos($css, '.parcs-ht-guide-description{') !== false && strpos($css, '.parcs-ht-guide-actions{') !== false, 'Description and actions have full-card layout rules below the compact top row');
guide_contract(strpos($css, '--htp-guide-mobile-image-height') === false, 'Guide cover size no longer depends on a mutable mobile height variable');
guide_contract(strpos($css, 'object-fit:contain') !== false && strpos($css, 'object-position:center center') !== false, 'Guide photos stay fully visible and centered across image ratios');
guide_contract(strpos($appearance, "const OPTION = 'parcs_ht_guide_appearance'") !== false, 'Guide appearance has independent saved settings');
guide_contract(strpos($appearance, 'Hauteur image mobile') === false && strpos($appearance, 'image_mobile_height') === false, 'Admin no longer exposes a conflicting mobile image height control');
guide_contract(strpos($appearance, 'format fixe 3:4') !== false, 'Appearance panel documents the fixed cover format');
guide_contract(strpos($appearance, 'card_background') !== false && strpos($appearance, 'primary_button_background') !== false && strpos($appearance, 'category_color') !== false, 'Guide colors remain configurable');
guide_contract(strpos($appearance, 'parcs-ht-guide-single-category') !== false, 'A single available category remains visibly identified');
guide_contract(strpos($appearance, 'Aperçu mobile') === false && strpos($appearance, 'Aperçu complet du shortcode') === false, 'Groups guide settings contain no embedded previews');
guide_contract(strpos($appearance, 'utilisez l’onglet Aperçu') !== false, 'Guide settings direct rendering checks to the central Preview tab');
guide_contract(strpos($appearance, 'admin_enqueue_scripts') === false, 'Guide settings no longer load public preview assets in admin');
guide_contract(strpos($main, "class-parcs-ht-guide-appearance.php") !== false && strpos($main, 'Parcs_HT_Guide_Appearance::init()') !== false, 'Guide appearance module is bootstrapped');

guide_contract(strpos($main, 'class-parcs-ht-save-integrity.php') !== false && strpos($main, 'Parcs_HT_Save_Integrity::init()') !== false, 'Central save integrity module is bootstrapped');
guide_contract(strpos($main, 'parcs-ht-admin-save-guard') === false, 'Duplicate standalone admin save guard is no longer enqueued');
guide_contract(strpos($save_js, 'protectGuideVisibilitySave') === false && strpos($save_js, 'data-htp-guide-enabled-fallback') === false, 'Guide save no longer mutates checkbox values in JavaScript');
guide_contract(strpos($integrity, "admin_post_parcs_ht_save_pedagogical_guides") !== false && strpos($integrity, "admin_post_parcs_ht_save_guide_appearance") !== false, 'Guide data and appearance use the verified save path');
guide_contract(strpos($integrity, 'persist_guides_value') !== false && strpos($integrity, 'persist_appearance_value') !== false, 'Save module exposes deterministic persistence functions used by handlers and tests');
guide_contract(strpos($integrity, 'same_value($clean, $stored_year)') !== false && strpos($integrity, 'same_value($clean, $stored)') !== false, 'Both guide saves reread WordPress and compare persisted values before success');
guide_contract(substr_count($integrity, "wp_unslash(\$_POST['guides'])") === 1, 'Guide POST data is unslashed exactly once in the verified handler');
guide_contract(substr_count($integrity, "wp_unslash(\$_POST['appearance'])") === 1, 'Appearance POST data is unslashed exactly once in the verified handler');
guide_contract(strpos($integrity, 'const MAX_DAILY_BACKUPS = 10') !== false && strpos($integrity, "wp_date('Y-m-d'") !== false, 'Safety history keeps one daily snapshot for up to ten days');
guide_contract(strpos($integrity, "Parcs_HT_Pedagogical_Guides::OPTION") !== false && strpos($integrity, "Parcs_HT_Guide_Appearance::OPTION") !== false, 'Daily snapshot includes guide data and guide appearance in addition to main settings');
guide_contract(strpos($integrity, 'restore_complete_revision') !== false && strpos($integrity, "'options'=>\$options") !== false, 'Daily backup can restore the complete tracked plugin configuration');

/* Behavioural regression: repeated writes must replace the previous value every time. */
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$GLOBALS['htp_test_options'] = array();
if (!function_exists('add_action')) { function add_action() {} }
if (!function_exists('add_filter')) { function add_filter() {} }
if (!function_exists('sanitize_key')) { function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); } }
if (!function_exists('sanitize_text_field')) { function sanitize_text_field($value) { return trim(strip_tags((string)$value)); } }
if (!function_exists('sanitize_textarea_field')) { function sanitize_textarea_field($value) { return trim(strip_tags((string)$value)); } }
if (!function_exists('sanitize_hex_color')) { function sanitize_hex_color($value) { return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? strtolower((string)$value) : null; } }
if (!function_exists('esc_url_raw')) { function esc_url_raw($value) { return trim((string)$value); } }
if (!function_exists('wp_json_encode')) { function wp_json_encode($value) { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); } }
if (!function_exists('wp_date')) { function wp_date($format, $timestamp = null) { return gmdate($format, $timestamp === null ? time() : $timestamp); } }
if (!function_exists('get_current_user_id')) { function get_current_user_id() { return 1; } }
if (!function_exists('get_option')) { function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['htp_test_options']) ? $GLOBALS['htp_test_options'][$name] : $default; } }
if (!function_exists('update_option')) { function update_option($name, $value, $autoload = null) { unset($autoload); $GLOBALS['htp_test_options'][$name] = $value; return true; } }

if (!class_exists('Parcs_HT_Defaults')) {
    class Parcs_HT_Defaults {
        const OPTION = 'parcs_ht_settings';
        public static function all_settings() { return get_option(self::OPTION, array()); }
    }
}
if (!class_exists('Parcs_HT_Pedagogical_Guides')) {
    class Parcs_HT_Pedagogical_Guides {
        const OPTION = 'parcs_ht_pedagogical_guides';
        public static function settings($year = '') {
            $saved = get_option(self::OPTION, array());
            return isset($saved['seasons'][$year]) && is_array($saved['seasons'][$year]) ? $saved['seasons'][$year] : array('guides'=>array());
        }
    }
}
if (!class_exists('Parcs_HT_Guide_Appearance')) {
    class Parcs_HT_Guide_Appearance { const OPTION = 'parcs_ht_guide_appearance'; }
}
if (!class_exists('Parcs_HT_Quote_Languages')) {
    class Parcs_HT_Quote_Languages { const OPTION = 'parcs_ht_quote_language_shortcodes'; }
}

require_once $root . '/includes/class-parcs-ht-save-integrity.php';

$GLOBALS['htp_test_options'][Parcs_HT_Defaults::OPTION] = array(
    'active_season_year'=>'2026',
    'seasons'=>array('2026'=>array('published'=>'1')),
);
$GLOBALS['htp_test_options'][Parcs_HT_Pedagogical_Guides::OPTION] = array('version'=>3, 'seasons'=>array('2026'=>array('guides'=>array())));
$GLOBALS['htp_test_options'][Parcs_HT_Guide_Appearance::OPTION] = array();

$guide = array('items'=>array(array(
    'enabled'=>'1', 'cycle'=>'cycle1', 'languages'=>array('fr'=>'1'), 'status'=>'available',
    'title'=>array('fr'=>'Version A'), 'description'=>array('fr'=>'Texte A'),
    'pdf_url'=>'https://example.test/a.pdf', 'cover_url'=>'https://example.test/a.jpg', 'order'=>'10',
)));
guide_contract(Parcs_HT_Save_Integrity::persist_guides_value('2026', $guide), 'First guide save is persisted and verified');
$stored = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
guide_contract($stored['seasons']['2026']['guides'][0]['title']['fr'] === 'Version A' && $stored['seasons']['2026']['guides'][0]['enabled'] === '1', 'First save stores title and checked visibility');

$guide['items'][0]['title']['fr'] = 'Version B';
unset($guide['items'][0]['enabled']);
guide_contract(Parcs_HT_Save_Integrity::persist_guides_value('2026', $guide), 'Second guide save is persisted and verified');
$stored = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
guide_contract($stored['seasons']['2026']['guides'][0]['title']['fr'] === 'Version B' && $stored['seasons']['2026']['guides'][0]['enabled'] === '0', 'Second save replaces title and stores unchecked visibility without JavaScript');

$guide['items'][0]['enabled'] = '1';
$guide['items'][0]['title']['fr'] = 'Version C';
$guide['items'][0]['cycle'] = 'cycle3';
$guide['items'][0]['pdf_url'] = 'https://example.test/c.pdf';
$guide['items'][0]['cover_url'] = 'https://example.test/c.jpg';
$guide['items'][0]['order'] = '30';
guide_contract(Parcs_HT_Save_Integrity::persist_guides_value('2026', $guide), 'Third guide save is persisted and verified');
$stored = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
$current = $stored['seasons']['2026']['guides'][0];
guide_contract($current['title']['fr'] === 'Version C' && $current['enabled'] === '1' && $current['cycle'] === 'cycle3' && $current['order'] === 30, 'Third save replaces all edited guide fields');

$guide['items'][0]['title']['fr'] = 'Version D';
$guide['items'][0]['languages'] = array('fr'=>'1','de'=>'1');
$guide['items'][0]['description']['fr'] = 'Texte D';
guide_contract(Parcs_HT_Save_Integrity::persist_guides_value('2026', $guide), 'Fourth guide save is persisted and verified');
$stored = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
$current = $stored['seasons']['2026']['guides'][0];
guide_contract($current['title']['fr'] === 'Version D' && $current['description']['fr'] === 'Texte D' && $current['languages'] === array('fr','de'), 'Fourth save replaces the third state instead of restoring stale values');

$appearance_a = array('card_background'=>'transparent','text_color'=>'inherit','title_color'=>'inherit','primary_button_background'=>'#176b57','primary_button_text'=>'#ffffff','secondary_button_color'=>'inherit','category_color'=>'inherit');
$appearance_b = $appearance_a;
$appearance_b['primary_button_background'] = '#123456';
guide_contract(Parcs_HT_Save_Integrity::persist_appearance_value($appearance_a), 'First appearance save is persisted and verified');
guide_contract(Parcs_HT_Save_Integrity::persist_appearance_value($appearance_b), 'Second appearance save is persisted and verified');
guide_contract(get_option(Parcs_HT_Guide_Appearance::OPTION, array())['primary_button_background'] === '#123456', 'Second appearance save replaces the first value');

$base = strtotime('2026-09-04 12:00:00 UTC');
$revisions = array();
for ($i = 0; $i < 12; $i++) {
    $revisions[] = array('created_at'=>$base - ($i * 86400), 'year'=>'2026', 'settings'=>array('n'=>$i));
}
$revisions[] = array('created_at'=>$base - 3600, 'year'=>'2026', 'settings'=>array('n'=>'older-same-day'));
$normalized = Parcs_HT_Save_Integrity::normalize_revisions($revisions);
guide_contract(count($normalized) === 10, 'Daily history keeps at most ten different days');
guide_contract(wp_date('Y-m-d', $normalized[0]['created_at']) === '2026-09-04', 'Newest day remains first in daily history');
guide_contract($normalized[0]['settings']['n'] === 0, 'Only the latest snapshot of a day is kept');

echo "Pedagogical guides contract: OK\n";
