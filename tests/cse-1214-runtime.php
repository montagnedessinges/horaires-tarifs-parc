<?php
/**
 * Contrats d'affichage du module CSE (sans installation WordPress).
 * Usage : php tests/cse-1214-runtime.php
 */
define('ABSPATH', __DIR__);
define('PARCS_HT_URL', 'https://example.test/wp-content/plugins/gestion-du-parc/');
define('PARCS_HT_VERSION', 'test');
$GLOBALS['cse_test_options'] = array();
$GLOBALS['cse_shortcodes'] = array();
$GLOBALS['cse_public_years'] = array('2026');
$GLOBALS['cse_public_window'] = true;
$GLOBALS['cse_test_language'] = 'fr';

function add_shortcode($tag, $callback) { $GLOBALS['cse_shortcodes'][$tag] = $callback; }
function is_admin() { return false; }
function get_option($name, $fallback = array()) { return $GLOBALS['cse_test_options'][$name] ?? $fallback; }
function wp_date($format, $timestamp = null) { return $format === 'Y' ? '2026' : date($format, $timestamp ?: time()); }
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return (string)$value; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function sanitize_text_field($value) { return trim((string)$value); }
function sanitize_email($value) { return filter_var((string)$value, FILTER_SANITIZE_EMAIL); }
function remove_accents($value) { return str_replace(array('é','è','ê','à'), array('e','e','e','a'), (string)$value); }
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false) {}
function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $footer = false) {}
function did_action($action) { return 0; }
function current_user_can($capability) { return $capability === 'manage_options'; }
function admin_url($path = '') { return 'https://example.test/wp-admin/' . $path; }
function add_query_arg($args, $url = '') { return $url . '?' . http_build_query($args); }
function checked($value, $target = true, $echo = true) { $result = (string)$value === (string)$target ? ' checked="checked"' : ''; if ($echo) echo $result; return $result; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function submit_button($label) { echo '<button type="submit">' . esc_html($label) . '</button>'; }
function esc_textarea($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

final class Parcs_HT_Schedule {
    public static function language() { return $GLOBALS['cse_test_language']; }
}
final class Parcs_HT_Defaults {
    public static function all_settings() { return $GLOBALS['cse_test_options']['base']; }
}
final class Parcs_HT_Public_Visibility {
    public static function retail_years() { return $GLOBALS['cse_public_years']; }
    public static function in_window($year) { return $GLOBALS['cse_public_window']; }
}

function check_cse($pass, $message) {
    if (!$pass) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$GLOBALS['cse_test_options']['base'] = array(
    'seasons'=>array(
        '2026'=>array(
            'tariffs'=>array(
                'columns'=>array('individual'=>array(
                    array('id'=>'tariff_col_000001','label'=>array('fr'=>'En ligne'),'visible'=>'1'),
                    array('id'=>'tariff_col_000002','label'=>array('fr'=>'Sur place'),'visible'=>'1'),
                )),
                'individual'=>array(
                    array('id'=>'tariff_row_000001','enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array(
                        'tariff_col_000001'=>array('value'=>'11 €'),
                        'tariff_col_000002'=>array('value'=>'12 €'),
                    )),
                    array('id'=>'tariff_row_000002','enabled'=>'1','label'=>array('fr'=>'Enfant'),'cells'=>array(
                        'tariff_col_000001'=>array('value'=>'7,50 €'),
                        'tariff_col_000002'=>array('value'=>'8 €'),
                    )),
                ),
            ),
        ),
        '2027'=>array('tariffs'=>array()),
    ),
);
$GLOBALS['cse_test_options']['parcs_ht_cse_settings'] = array(
    '2026'=>array(
        'enabled'=>'1','show_table'=>'1','show_public'=>'1','show_login'=>'1','show_signup'=>'1',
        'show_online'=>'1','show_pdf'=>'1','show_transition'=>'1','show_link'=>'1',
        'page_url'=>array('fr'=>'https://example.test/cse/'),
        'login_url'=>array('fr'=>'https://example.test/login/'),
        'signup_url'=>array('fr'=>'https://example.test/signup/'),
        'online_url'=>array('fr'=>'https://example.test/tickets/'),
        'pdf_url'=>array('fr'=>'https://example.test/bon-de-commande.pdf'),
        'contact_email'=>'cse@example.test',
        'rows'=>array(
            array('enabled'=>'1','label'=>array('fr'=>'Adulte','en'=>'Adult','de'=>'Erwachsene'),'cse_price'=>'10,50 €','public_row_id'=>'tariff_row_000001'),
            array('enabled'=>'1','label'=>array('fr'=>'Enfant','en'=>'Child','de'=>'Kind'),'cse_price'=>'7 €','public_row_id'=>'tariff_row_000002'),
            array('enabled'=>'0','label'=>array('fr'=>'Masqué'),'cse_price'=>'1 €','public_row_id'=>''),
        ),
    ),
    '2027'=>array('enabled'=>'0'),
);

require_once dirname(__DIR__) . '/includes/class-parcs-ht-shortcode-registry.php';
require_once dirname(__DIR__) . '/includes/class-parcs-ht-cse.php';
Parcs_HT_CSE::init();
foreach (array('parc_tarifs_cse','parc_tarifs_cse_fr','parc_tarifs_cse_en','parc_tarifs_cse_de','parcs_tarifs_cse','parc_lien_cse') as $tag) {
    check_cse(isset($GLOBALS['cse_shortcodes'][$tag]), 'shortcode ' . $tag);
}
ob_start();
Parcs_HT_CSE::admin_page();
$admin_html = ob_get_clean();
check_cse(strpos($admin_html, 'name="cse[rows][0][cse_price]"') !== false, 'champ de saisie manuelle en administration');
check_cse(strpos($admin_html, 'name="cse[rows][0][public_row_id]"') === false && strpos($admin_html, 'Tarif public (source)') === false, 'aucune sélection de tarif visiteur dans l’administration');
check_cse(strpos($admin_html, 'name="cse[show_public]"') === false, 'ancien interrupteur de comparaison retiré');
$html = Parcs_HT_CSE::render('fr');
check_cse(strpos($html, '<th scope="col">Billet</th>') !== false && strpos($html, '<th scope="col">Tarif CSE</th>') !== false, 'seules les colonnes billet et tarif CSE sont affichées');
check_cse(substr_count($html, '<th scope="col">') === 2, 'aucune troisième colonne de comparaison');
check_cse(strpos($html, '<strong>10,50 €</strong>') !== false && strpos($html, '<strong>7 €</strong>') !== false, 'prix saisis manuellement affichés');
check_cse(strpos($html, 'Tarif public') === false && strpos($html, '<del>') === false, 'les anciens tarifs publics ne sont jamais affichés');
check_cse(strpos($html, '2027') === false, 'année non publiée exclue');
check_cse(strpos($html, 'Masqué') === false, 'ligne désactivée absente');
check_cse(strpos($html, 'https://example.test/login/') !== false && strpos($html, 'https://example.test/signup/') !== false, 'connexion et inscription distinctes');
check_cse(strpos($html, 'https://example.test/bon-de-commande.pdf') !== false, 'bon de commande indépendant');
check_cse(strpos($html, 'PDF') !== false && strpos($html, 'codes') !== false, 'message de dématérialisation');
check_cse(strpos(Parcs_HT_CSE::render_link('fr', array('annee'=>'2026')), 'https://example.test/cse/') !== false, 'renvoi explicite CSE');

$english = Parcs_HT_CSE::render('en');
check_cse(strpos($english, 'https://example.test/login/') !== false, 'rendu CSE toujours français si demandé en anglais');
check_cse(strpos($english, '>Adult</th>') === false && strpos($english, '>Adulte</th>') !== false, 'aucun libellé anglais dans le rendu CSE');
$rows = Parcs_HT_Shortcode_Registry::public_rows();
$cse_rows = array_values(array_filter($rows, static function ($row) { return in_array($row['base'], array('parc_tarifs_cse','parc_lien_cse'), true); }));
check_cse(count($cse_rows) === 2, 'entrées CSE présentes au registre');
foreach ($cse_rows as $row) {
    check_cse($row['shortcodes']['auto'] !== '' && $row['shortcodes']['fr'] === '' && $row['shortcodes']['en'] === '' && $row['shortcodes']['de'] === '', 'registre CSE limité au shortcode canonique');
}
$GLOBALS['cse_test_language'] = 'de';
check_cse($GLOBALS['cse_shortcodes']['parc_tarifs_cse_de']() === '', 'alias CSE masqué sur une page allemande');
check_cse($GLOBALS['cse_shortcodes']['parc_lien_cse_de']() === '', 'renvoi CSE masqué sur une page allemande');
$GLOBALS['cse_test_language'] = 'fr';
check_cse(strpos($GLOBALS['cse_shortcodes']['parc_tarifs_cse_de'](), 'Adulte') !== false, 'alias historique _de affiche le français sur une page française');
$GLOBALS['cse_public_window'] = false;
check_cse(strpos(Parcs_HT_CSE::shortcode(), 'Adulte') !== false, 'publication CSE indépendante de la fenêtre annuelle générale');
$GLOBALS['cse_public_window'] = true;
$GLOBALS['cse_public_years'] = array();
$hidden_public = Parcs_HT_CSE::render('fr');
check_cse(strpos($hidden_public, '<strong>10,50 €</strong>') !== false, 'tarifs CSE indépendants de la publication des tarifs visiteurs');
$GLOBALS['cse_public_years'] = array('2026');
$GLOBALS['cse_test_options']['parcs_ht_cse_settings']['2026']['show_public'] = '0';
$no_public = Parcs_HT_CSE::render('fr');
check_cse(strpos($no_public, 'Tarif public') === false && substr_count($no_public, '<th scope="col">') === 2, 'ancien réglage comparatif ignoré sans effet sur les prix CSE');
$GLOBALS['cse_test_options']['parcs_ht_cse_settings']['2026']['show_public'] = '1';
$GLOBALS['cse_test_options']['parcs_ht_cse_settings']['2026']['show_link'] = '0';
check_cse(Parcs_HT_CSE::render_link('fr') === '', 'renvoi public désactivable');
$GLOBALS['cse_test_options']['parcs_ht_cse_settings']['2026']['enabled'] = '0';
check_cse(Parcs_HT_CSE::render('fr') === '', 'année CSE désactivée invisible');
echo "CSE checks passed.\n";
