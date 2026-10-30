<?php
/**
 * Contrat métier des devis sur journées entièrement fermées.
 * Exécution autonome sans WordPress, avec le véritable moteur de calendrier.
 */
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$GLOBALS['quote_test_options'] = array(
    'parcs_ht_settings' => array(
        'general'=>array('timezone'=>'Europe/Paris'),
        'seasons'=>array(
            '2026'=>array(
                'season_start'=>'2026-01-01','season_end'=>'2026-12-31',
                'regular_periods'=>array(
                    array('enabled'=>'1','start'=>'2026-01-01','end'=>'2026-12-31','weekdays'=>array('1','2','3','4','5','6','7'),'open'=>'13:00','close'=>'17:00')
                ),
                'exceptions'=>array(
                    array('enabled'=>'1','type'=>'closed','start'=>'2026-12-11','end'=>'2026-12-12','priority'=>'100'),
                    array('enabled'=>'1','type'=>'closed','start'=>'2026-12-24','end'=>'2026-12-31','priority'=>'100')
                ),
            ),
        ),
    ),
    'parcs_ht_group_quote_years_v2'=>array('version'=>1,'years'=>array('2026'=>array('enabled'=>'0'))),
    'parcs_ht_group_quotes'=>array(),
    'parcs_ht_quote_gate'=>array(),
);
function get_option($key, $default = false) { return isset($GLOBALS['quote_test_options'][$key]) ? $GLOBALS['quote_test_options'][$key] : $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['quote_test_options'][$key]=$value; return true; }
function wp_date($format) { if ($format==='Y') return '2026'; return '2026-10-27'; }
function has_filter($name) { unset($name); return false; }
function determine_locale() { return 'fr_FR'; }
function assert_group($condition,$message) { if (!$condition) {fwrite(STDERR, "FAIL: {$message}\n"); exit(1);} }

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root.'/includes/class-parcs-ht-schedule.php';
final class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }
require_once $root.'/includes/class-parcs-ht-group-quotes.php';
require_once $root.'/includes/class-parcs-ht-quote-gate.php';

$gate = 'Parcs_HT_Quote_Gate';
$open = $gate::status_for_date('2026-12-10');
assert_group($open['valid'] && !$open['closed'] && $open['allowed'],'une ouverture uniquement l’après-midi autorise les groupes');
assert_group(!$gate::status_for_date('2026-12-11')['closed']===false,'la fermeture réelle est vérifiée'); 
$closed = $gate::status_for_date('2026-12-11');
assert_group($closed['closed'] && $closed['allowed'],'par défaut les anciens sites conservent les devis les jours fermés');

$invalid = $gate::sanitize_closed_date_exceptions(array(
    array('start'=>'2026-02-30','end'=>'2026-03-01'),
    array('start'=>'2026-12-24','end'=>'2026-12-23'),
    array('start'=>'2026-12-11','end'=>''),
    array('start'=>'2026-12-24','end'=>'2026-12-31'),
));
assert_group(count($invalid)===2 && $invalid[0]['end']==='2026-12-11','dates impossibles ignorées, jour unique normalisé');

$GLOBALS['quote_test_options']['parcs_ht_quote_gate'] = array(
    'allow_closed_group_quotes'=>'1',
    'closed_date_exceptions'=>array(array('start'=>'2026-12-11','end'=>'2026-12-11')),
);
assert_group(!$gate::status_for_date('2026-12-11')['allowed'],'Oui : exception de fermeture refuse le devis');
assert_group($gate::status_for_date('2026-12-12')['allowed'],'Oui : les autres jours fermés restent autorisés');
assert_group($gate::status_for_date('2026-12-10')['allowed'],'Oui : journée ouverte reste autorisée');

$GLOBALS['quote_test_options']['parcs_ht_quote_gate'] = array(
    'allow_closed_group_quotes'=>'0',
    'closed_date_exceptions'=>array(array('start'=>'2026-12-24','end'=>'2026-12-31')),
);
assert_group(!$gate::status_for_date('2026-12-11')['allowed'],'Non : un jour fermé est refusé');
assert_group($gate::status_for_date('2026-12-24')['allowed'],'Non : premier jour inclus dans l’exception');
assert_group($gate::status_for_date('2026-12-31')['allowed'],'Non : dernier jour inclus dans l’exception');
assert_group($gate::status_for_date('2026-12-10')['allowed'],'Non : journée ouverte uniquement après-midi autorisée');
assert_group(!$gate::status_for_date('2026-12-23')['allowed']===false,'le jour ouvert reste ouvert');
assert_group(!$gate::status_for_date('2026-02-30')['valid'],'date invalide refusée avant vérification');

final class WPCF7_Submission {
    public static $posted = array();
    public static function get_instance() { return new self(); }
    public function get_posted_data() { return self::$posted; }
}
final class Quote_Test_Validation {
    public $messages = array();
    public function invalidate($tag, $message) { $this->messages[(string)$tag->name] = $message; }
}
WPCF7_Submission::$posted=array('visite'=>'2026-12-11','groupedevis'=>'Groupe');
$validation = Parcs_HT_Group_Quotes::validate_quote(new Quote_Test_Validation(), array((object)array('name'=>'visite')));
assert_group(isset($validation->messages['visite']),'validation serveur CF7 interdit la date même sans JS');
assert_group(strpos($validation->messages['visite'], 'n’accueillons pas de groupes') !== false,'message de blocage explicite');
WPCF7_Submission::$posted=array('visite'=>'2026-12-10','unrelated'=>'yes');
$other = Parcs_HT_Group_Quotes::validate_quote(new Quote_Test_Validation(), array((object)array('name'=>'visite')));
assert_group(!$other->messages,'formulaire CF7 non lié au devis épargné');

echo "OK : jours ouverts, fermés, règle Oui/Non, exceptions inclusives, dates et validation CF7.\n";
