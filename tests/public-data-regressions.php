<?php
// Isolated WordPress API fixtures: no database, HTTP request or email is sent.
define('ABSPATH', '/');
define('PARCS_HT_URL', '/plugin/');
define('PARCS_HT_VERSION', 'test');
$GLOBALS['admin_context'] = false;
$GLOBALS['can_manage'] = false;
$GLOBALS['options'] = array();
$GLOBALS['inline'] = array();
function is_admin() { return $GLOBALS['admin_context']; }
function current_user_can($capability) { return $GLOBALS['can_manage'] && $capability === 'manage_options'; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid-test-nonce' && $action === 'parcs_ht_save'; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function wp_date($format, $timestamp = null, $timezone = null) { return (new DateTimeImmutable('2026-08-31 12:00:00'))->format($format); }
function wp_unslash($value) { return $value; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)); }
function sanitize_text_field($value) { return strip_tags($value); }
function sanitize_textarea_field($value) { return strip_tags($value); }
function esc_url_raw($value) { return (string)$value; }
function wp_enqueue_script() {}
function wp_script_is() { return false; }
function wp_add_inline_script($handle, $code) { $GLOBALS['inline'][$handle] = $code; }
function wp_json_encode($value) { return json_encode($value); }
class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
    public static function all_settings() { return $GLOBALS['public_settings']; }
}
class WPCF7_Submission {
    public static function get_instance() { return new self(); }
    public function get_posted_data() { return $GLOBALS['submission']; }
}
class Quote_Validation_Result {
    public $invalid = array();
    public function invalidate($tag, $message) { $this->invalid[$tag->name] = $message; }
}
function verify($condition, $message) { if (!$condition) throw new Exception($message); echo '[OK] ' . $message . PHP_EOL; }
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root . '/includes/class-parcs-ht-tariff-identities.php';
require $root . '/includes/class-parcs-ht-group-tariff-settings.php';
require $root . '/includes/class-parcs-ht-group-quotes.php';
require $root . '/includes/class-parcs-ht-tariff-seasons.php';
require $root . '/includes/class-parcs-ht-shortcodes.php';
require $root . '/includes/class-parcs-ht-schedule.php';
require $root . '/includes/class-parcs-ht-quote-gate.php';

$priceColumn = 'tariff_col_000001';
$hiddenColumn = 'tariff_col_000002';
$adultRow = 'tariff_row_000001';
$childRow = 'tariff_row_000002';
$schoolRow = 'tariff_row_000003';
$disabilityRow = 'tariff_row_000004';
$groupRows = array(
    array('id'=>$adultRow,'enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array($priceColumn=>array('value'=>'8,50 €'))),
    array('id'=>$childRow,'enabled'=>'1','label'=>array('fr'=>'Enfant'),'cells'=>array($priceColumn=>array('value'=>'6 €'))),
    array('id'=>$schoolRow,'enabled'=>'1','label'=>array('fr'=>'Scolaire / extrascolaire'),'cells'=>array($priceColumn=>array('value'=>'6 €'))),
    array('id'=>$disabilityRow,'enabled'=>'1','label'=>array('fr'=>'Personne en situation de handicap et accompagnateur'),'cells'=>array($priceColumn=>array('value'=>'6 €'))),
);
$tariffs = array(
    'columns'=>array('groups'=>array(
        array('id'=>$priceColumn,'label'=>array('fr'=>'Public'),'visible'=>'1'),
        array('id'=>$hiddenColumn,'label'=>array('fr'=>'Private'),'visible'=>'0'),
    )),
    'groups'=>$groupRows,
);
$settings = array('timezone'=>'Europe/Paris','seasons'=>array(
    '2026'=>array('published'=>'1','season_start'=>'2026-03-01','season_end'=>'2026-11-30','tariffs'=>$tariffs),
    '2027'=>array('published'=>'0','season_start'=>'2027-03-01','season_end'=>'2027-11-30','tariffs'=>$tariffs),
));
$GLOBALS['public_settings'] = $settings;
$GLOBALS['options'][Parcs_HT_Defaults::OPTION] = $settings;
$GLOBALS['options'][Parcs_HT_Group_Tariff_Settings::OPTION] = array(
    'version'=>1,
    'seasons'=>array(
        '2026'=>array('published'=>'1'),
        '2027'=>array('published'=>'0'),
    ),
);
$binding = array(
    'column_id'=>$priceColumn,
    'child_row_id'=>$schoolRow,
    'adult_row_id'=>$adultRow,
    'disability_row_id'=>$disabilityRow,
    'companion_row_id'=>$disabilityRow,
    'free_adult_children'=>'10',
    'free_adult_round_threshold'=>'5',
);
$GLOBALS['options'][Parcs_HT_Group_Quotes::OPTION] = array(
    'binding_version'=>2,
    'tariff_bindings'=>array('2026'=>$binding,'2027'=>$binding),
    'tariff_binding'=>Parcs_HT_Group_Quotes::legacy_binding_defaults(),
    'seasons'=>array('2027'=>array('published'=>'1','child'=>'99','adult'=>'99','disability'=>'99','companion'=>'99','private_note'=>'PRIVATE_NOTE')),
);
$saved = $GLOBALS['options'];
$quotes = Parcs_HT_Group_Quotes::settings();
verify($quotes['seasons']['2027']['published'] === '0', 'Draft season and unpublished group rates disable quote availability');
verify($quotes['seasons']['2026']['published'] === '1', 'Published season with published group rates remains available');
verify(Parcs_HT_Group_Quotes::settings(false)['seasons']['2027']['published'] === '1', 'Legacy quote metadata remains stored for rollback only');
verify($GLOBALS['options'] === $saved, 'Reading public settings does not modify saved options');
Parcs_HT_Group_Quotes::assets();
$payload = $GLOBALS['inline']['parcs-ht-group-quotes'];
verify(strpos($payload, '2027') === false && strpos($payload, 'PRIVATE_NOTE') === false && strpos($payload, '99') === false, 'Draft and legacy quote rates stay out of JavaScript');
verify(strpos($payload, '2026') !== false && strpos($payload, '8.5') !== false, 'Published shared group tariffs feed JavaScript');

$GLOBALS['public_settings']['seasons']['2027']['published'] = '1';
$GLOBALS['options'][Parcs_HT_Defaults::OPTION]['seasons']['2027']['published'] = '1';
verify(Parcs_HT_Group_Quotes::settings()['seasons']['2027']['published'] === '0', 'Publishing the season alone does not publish group tariffs');
$GLOBALS['options'][Parcs_HT_Group_Tariff_Settings::OPTION]['seasons']['2027']['published'] = '1';
verify(Parcs_HT_Group_Quotes::settings()['seasons']['2027']['published'] === '1', 'Group tariffs require their own explicit publication status');
$GLOBALS['public_settings'] = $settings;
$GLOBALS['options'][Parcs_HT_Defaults::OPTION] = $settings;
$GLOBALS['options'][Parcs_HT_Group_Tariff_Settings::OPTION]['seasons']['2027']['published'] = '0';

$input = array('visite'=>'2027-09-01','groupedevis'=>'Groupe','nbrenfants'=>'20','nbradultes'=>'3','totalprixscolaire'=>'1,00 €');
verify(Parcs_HT_Group_Quotes::canonicalize_posted_data($input)['totalprixscolaire'] === '', 'Server refuses a year whose group tariffs are not published');
$GLOBALS['submission'] = $input;
$validation = new Quote_Validation_Result();
Parcs_HT_Group_Quotes::validate_quote($validation, array((object)array('name'=>'visite')));
verify(isset($validation->invalid['visite']), 'CF7 validation blocks sending a quote without published group tariffs');
$gate = new ReflectionMethod('Parcs_HT_Quote_Gate', 'tariff_available');
$gate->setAccessible(true);
verify(!$gate->invoke(null, '2027') && $gate->invoke(null, '2026'), 'Date gate shares season and group publication rules');
$input['visite'] = '2026-09-01';
verify(Parcs_HT_Group_Quotes::canonicalize_posted_data($input)['totalprixscolaire'] === '128,50 €', 'Published quote recalculates from Groupes → Tarifs through permanent IDs');
$GLOBALS['options'][Parcs_HT_Defaults::OPTION]['seasons']['2026']['tariffs']['groups'][0]['cells'][$priceColumn]['value'] = '9 €';
verify(Parcs_HT_Group_Quotes::canonicalize_posted_data($input)['totalprixscolaire'] === '129,00 €', 'Changing the shared group tariff changes the quote calculation without changing its ID');
$GLOBALS['options'][Parcs_HT_Defaults::OPTION]['seasons']['2026']['tariffs']['groups'] = array();
verify(Parcs_HT_Group_Quotes::canonicalize_posted_data($input)['totalprixscolaire'] === '', 'No hidden legacy quote rate is used when the linked group tariff is unavailable');
$GLOBALS['options'][Parcs_HT_Defaults::OPTION] = $settings;

$columns = new ReflectionMethod('Parcs_HT_Shortcodes', 'tariff_columns');
$columns->setAccessible(true);
foreach (array(false, true) as $admin) {
    $GLOBALS['admin_context'] = $admin;
    $selected = Parcs_HT_Tariff_Seasons::select_season_tariffs($settings);
    verify(count($columns->invoke(null, $selected['tariffs'], 'groups')) === 1, 'Hidden column absent from page and admin-post export');
    verify(count($selected['tariffs']['columns']['groups']) === 2, 'Raw settings retain hidden columns for later saves');
}
$hidden = array('columns'=>array('groups'=>array(array('id'=>$priceColumn,'visible'=>'0'))));
verify($columns->invoke(null, $hidden, 'groups') === array(), 'All-hidden columns do not activate a legacy price fallback');
verify(count($columns->invoke(null, array(), 'groups')) === 1, 'Legacy configurations without columns retain a temporary compatibility price column');
$GLOBALS['pagenow'] = 'admin-post.php';
$_GET = array('page'=>'parcs-horaires-tarifs','season'=>'2027');
$_POST = array('action'=>'parcs_ht_tariffs_print','season_year'=>'2027');
foreach (array(false, true) as $admin_user) {
    $GLOBALS['can_manage'] = $admin_user;
    $selected = Parcs_HT_Tariff_Seasons::select_season_tariffs($settings);
    verify(!isset($selected['tariffs']['marker']), 'Public export cannot request draft, even when logged in as administrator');
}
$GLOBALS['pagenow'] = 'admin.php';
$settings['seasons']['2027']['tariffs']['marker'] = 'DRAFT_PRIVATE';
$selected = Parcs_HT_Tariff_Seasons::select_season_tariffs($settings);
verify(($selected['tariffs']['marker'] ?? '') === 'DRAFT_PRIVATE', 'Authorized editor can still preview a draft');
$GLOBALS['pagenow'] = 'admin-post.php';
$_GET = array();
$_POST = array('action'=>'parcs_ht_save','season_year'=>'2027','_wpnonce'=>'bad');
verify(!isset(Parcs_HT_Tariff_Seasons::select_season_tariffs($settings)['tariffs']['marker']), 'Invalid nonce cannot select draft for saving');
$_POST['_wpnonce'] = 'valid-test-nonce';
verify(isset(Parcs_HT_Tariff_Seasons::select_season_tariffs($settings)['tariffs']['marker']), 'Authorized save retains selected draft tariffs');
$_POST = array();
$settings['seasons']['2026']['published'] = '0';
$settings['tariffs'] = array('groups'=>array(array('enabled'=>'1','cells'=>array($priceColumn=>array('value'=>'PRIVATE_LEGACY_RATE')))));
verify(Parcs_HT_Tariff_Seasons::select_season_tariffs($settings, true)['tariffs']['groups'] === array(), 'No published season means no legacy price fallback at public render');
verify(Parcs_HT_Tariff_Seasons::select_season_tariffs($settings)['tariffs'] === $settings['tariffs'], 'Raw unpublished tariffs remain intact for migrations and editor saves');


// 1.15.6 — Le titre des horaires reste configurable et les heures d’accès limité
// continuent de provenir des champs de la règle, sans valeur horaire figée dans le rendu.
$schedule_source = file_get_contents($root . '/includes/class-parcs-ht-schedule.php');
$frontend_source = file_get_contents($root . '/assets/frontend.js');
if (strpos($schedule_source, "'calendar_hours_title'") === false) {
    fwrite(STDERR, "[FAIL] calendar_hours_title absent du payload public.\n");
    exit(1);
}
foreach (array('access_message', 'details_message', 'rule.pause_start', 'rule.resume', 'rule.last_entry') as $needle) {
    if (strpos($frontend_source, $needle) === false) {
        fwrite(STDERR, "[FAIL] Rendu accès limité incomplet : {$needle}.\n");
        exit(1);
    }
}
if (strpos($frontend_source, 'parcs-ht-park-hours-title') === false || strpos($frontend_source, 'parcs-ht-domain-access') === false || strpos($frontend_source, 'parcs-ht-domain-times') === false) {
    fwrite(STDERR, "[FAIL] Hiérarchie publique horaires / accès limité absente.\n");
    exit(1);
}

echo "Public data regressions: OK\n";
