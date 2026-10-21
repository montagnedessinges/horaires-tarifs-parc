<?php
/** Régression 1.20.8 : semaine implicite, délais distincts et CSV aperçu. */
$root=getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
if (!defined('ABSPATH')) define('ABSPATH','/');
function sanitize_text_field($s) { return trim((string)$s); }
function sanitize_hex_color($s) { return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$s) ? (string)$s : null; }
function wp_parse_args($a,$b) { return array_merge((array)$b,(array)$a); }
function current_user_can($cap) { return false; }
function wp_json_encode($value,$flags=0) { return json_encode($value,$flags); }
require_once $root.'/includes/class-parcs-ht-admin-schedule.php';
require_once $root.'/includes/class-parcs-ht-schedule.php';
require_once $root.'/includes/class-parcs-ht-slot-last-entry.php';
require_once $root.'/includes/class-parcs-ht-schedule-csv.php';
function verify_1208($yes,$what) {
    echo ($yes?'[OK] ':'[FAIL] ').$what."\n";
    if (!$yes) exit(1);
}
$csv=new ReflectionClass('Parcs_HT_Schedule_CSV');
$days=$csv->getMethod('weekdays_value');$days->setAccessible(true);
$slot=$csv->getMethod('slot_values');$slot->setAccessible(true);
$all=array('1','2','3','4','5','6','7');
verify_1208($days->invoke(null,'')===$all,'CSV jours vides => lundi-dimanche');
verify_1208($days->invoke(null,'1,3,5')===array('1','3','5'),'CSV jours partiels respectés');
verify_1208($days->invoke(null,'1,2,abc')===false,'CSV jour inconnu rejeté');
verify_1208($days->invoke(null,'1,9')===false,'CSV jour hors plage rejeté');
verify_1208($slot->invoke(null,array('last_entry_minutes'=>'25'))===array('last_entry_minutes'=>'25','last_entry_minutes_slot1'=>'25','last_entry_minutes_slot2'=>'25'),'Ancien CSV à délai unique conservé');
verify_1208($slot->invoke(null,array('last_entry_minutes'=>'25','last_entry_minutes_slot1'=>'10','last_entry_minutes_slot2'=>'40'))===array('last_entry_minutes'=>'40','last_entry_minutes_slot1'=>'10','last_entry_minutes_slot2'=>'40'),'CSV deux délais indépendants');
verify_1208($slot->invoke(null,array('last_entry_minutes_slot1'=>'0','last_entry_minutes_slot2'=>''))===array('last_entry_minutes'=>'0','last_entry_minutes_slot1'=>'0','last_entry_minutes_slot2'=>''),'Zéro explicite distinct du champ vide');
verify_1208($slot->invoke(null,array('last_entry_minutes_slot1'=>'1441'))===false,'CSV délai au-delà de 1440 rejeté');
$admin=new ReflectionClass('Parcs_HT_Admin_Schedule');
$clean=$admin->getMethod('clean_periods');$clean->setAccessible(true);
$period=array('enabled'=>'1','label'=>'Tests','start'=>'2027-10-23','end'=>'2027-10-30','open'=>'10:00','close'=>'12:00','open2'=>'13:00','close2'=>'17:00','last_entry_minutes_slot1'=>'10','last_entry_minutes_slot2'=>'40');
$p=$clean->invoke(null,array($period))[0];
verify_1208($p['weekdays']===$all,'Admin : aucun jour coché => sept jours enregistrés');
verify_1208($p['last_entry_minutes_slot1']==='10' && $p['last_entry_minutes_slot2']==='40','Admin : deux délais restent distincts');
$q=$clean->invoke(null,array(array_merge($period,array('weekdays'=>array('1','5')))))[0];
verify_1208($q['weekdays']===array('1','5'),'Admin : jours cochés explicitement préservés');
$season=array('season_start'=>'2027-10-01','season_end'=>'2027-11-15','exceptions'=>array(),'regular_periods'=>array(array_merge($period,array('weekdays'=>array()))));
$d=Parcs_HT_Schedule::resolve_day($season,array(),'2027-10-25');
verify_1208(!empty($d['open']),'Moteur : anciennes semaines vides restent ouvertes');
$old=array('seasons'=>array('2027'=>array('regular_periods'=>array(array_merge($p,array('last_entry_minutes_slot1'=>'30','last_entry_minutes_slot2'=>'35'))))));
$new=array('seasons'=>array('2027'=>array('regular_periods'=>array($p))));
$kept=Parcs_HT_Slot_Last_Entry::preserve_and_save_slot_values($new,$old,'parcs_ht_settings');
verify_1208($kept['seasons']['2027']['regular_periods'][0]['last_entry_minutes_slot1']==='10','Filtre ancien : ne réécrit pas le délai du créneau 1');
verify_1208($kept['seasons']['2027']['regular_periods'][0]['last_entry_minutes_slot2']==='40','Filtre ancien : ne réécrit pas le délai du créneau 2');
$source=file_get_contents($root.'/includes/class-parcs-ht-schedule-csv.php');
$js=file_get_contents($root.'/assets/slot-last-entry-frontend.js');
verify_1208(strpos($source,"'last_entry_minutes_slot1','last_entry_minutes_slot2'")!==false,'Colonnes de délais présentes dans le CSV');
verify_1208(strpos($source,'public static function apply_csv()')!==false && strpos($source,'csv_preview')!==false,'CSV : prévisualisation puis validation distinctes');
verify_1208(strpos($js,"hasOwn=Object.prototype.hasOwnProperty.call(row,key)")!==false,'Frontend : champ vide indépendant utilise le délai général');
echo "Horaires / CSV 1.20.8 : OK\n";
