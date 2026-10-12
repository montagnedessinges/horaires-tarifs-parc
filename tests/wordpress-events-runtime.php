<?php

define('ABSPATH', __DIR__);
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root.'/includes/class-parcs-ht-wordpress-events.php';
function verify($ok, $message) { if (!$ok) { fwrite(STDERR, $message."\n"); exit(1); } }
$GLOBALS['options'] = array();
$GLOBALS['posts'] = array(
  1=>array('start'=>'20261020','end'=>'20261022','status'=>'publish','title'=>'Halloween'),
  2=>array('start'=>'20261231','end'=>'20270102','status'=>'publish','title'=>'Nouvel an'),
  3=>array('start'=>'20261023','end'=>'','status'=>'publish','title'=>'Un jour'),
  4=>array('start'=>'20260230','end'=>'','status'=>'publish','title'=>'Invalide'),
  5=>array('start'=>'20261023','end'=>'20261020','status'=>'publish','title'=>'Inversé'),
  6=>array('start'=>'20261023','end'=>'','status'=>'draft','title'=>'Brouillon'),
  7=>array('start'=>'20261023','end'=>'','status'=>'publish','title'=>'Protégé','password'=>'secret'),
);
function get_option($key,$default=array()) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key,$value,$autoload=false) { $GLOBALS['options'][$key]=$value; }
function do_action($name) {}
function get_post_type($id) { return 'evenement'; }
function get_post_type_object($name) { return $name==='evenement' ? (object)array('publicly_queryable'=>true) : false; }
function get_post_status($id) { return $GLOBALS['posts'][$id]['status']; }
function get_post_meta($id,$key,$single) { verify(in_array($key,array('date_debut','date_fin'),true),'Unexpected ACF read'); return $GLOBALS['posts'][$id][$key==='date_debut'?'start':'end']; }
function get_post_field($field,$id,$context) { verify(in_array($field,array('post_title','post_password'),true),'Article content read'); return $GLOBALS['posts'][$id][$field==='post_title'?'title':'password'] ?? ''; }
function get_permalink($id) { return 'https://example.test/event/'.$id; }
function has_filter($name) { return false; }
function sanitize_text_field($value) { return strip_tags($value); }
function esc_url_raw($value) { return $value; }
class WP_Query {
 public $posts;
 public function __construct($args) {
  verify($args['fields']==='ids' && $args['post_status']==='publish' && $args['has_password']===false,'Unsafe query');
  $this->posts=array_keys($GLOBALS['posts']);
 }
}
verify(Parcs_HT_WordPress_Events::date('20240229')==='2024-02-29','Leap date');
verify(Parcs_HT_WordPress_Events::date('20230229')==='','Invalid leap date');
verify(Parcs_HT_WordPress_Events::date('2026-10-12')==='2026-10-12','ISO date');
verify(Parcs_HT_WordPress_Events::date('12/10/2026')==='','Ambiguous date accepted');
$rows=Parcs_HT_WordPress_Events::rows('2026','fr');
verify(count($rows)===3,'Invalid, private or draft rows exposed');
verify($rows[2]['start']===$rows[2]['end'],'Missing end not normalized');
verify(count(Parcs_HT_WordPress_Events::rows('2027','fr'))===1,'Cross-year event lost');
verify(count(Parcs_HT_WordPress_Events::rows('2028','fr'))===0,'Wrong year exposed');
$manual=array('special_periods'=>array(array('enabled'=>'1','title'=>array('fr'=>'Manuel'))),'regular_periods'=>array('unchanged'));
$projected=Parcs_HT_WordPress_Events::season($manual,'2026');
verify(count($projected['special_periods'])===4 && $projected['regular_periods']===$manual['regular_periods'],'Manual data altered');
verify(Parcs_HT_WordPress_Events::season($projected,'2026')===$projected,'Projection duplicated events');
verify(isset($projected['special_periods'][1]['button_url']['de']),'Explicit shortcode language missing');
$GLOBALS['posts'][1]['status']='draft';
Parcs_HT_WordPress_Events::invalidate(1);
verify(count(Parcs_HT_WordPress_Events::rows('2026','fr'))===2,'Unpublished event cached');
$GLOBALS['posts'][3]['start']='20271023';
Parcs_HT_WordPress_Events::meta_changed(1,3,'date_debut','20271023');
verify(count(Parcs_HT_WordPress_Events::rows('2026','fr'))===1,'Date change not reflected');
$GLOBALS['options'][Parcs_HT_WordPress_Events::OPTION]=array('enabled'=>'0');
verify(Parcs_HT_WordPress_Events::rows('2026','fr')===array(),'Disabled adapter active');
foreach (array('includes/class-parcs-ht-alerts.php','includes/class-parcs-ht-popup-1200.php') as $file) {
 $source=file_get_contents($root.'/'.$file);
 verify(strpos($source,'special_periods')===false && strpos($source,'show_popup')===false,'Event popup path survived');
}
foreach (array('includes/class-parcs-ht-admin.php','includes/class-parcs-ht-admin-periods.php') as $file) verify(strpos(file_get_contents($root.'/'.$file),'data-htp-popup-block')===false,'Event popup controls survived');
echo "OK: WordPress event dates, publication, projection, languages, cache and popup isolation.\n";

// qTranslate: absent translation must stay absent, and the URL follows the requested language.
if (true) {
 function qtranxf_use($lang,$title,$available=false,$empty=false) {
  verify($empty===true && $available===false,'qTranslate fallback could expose the wrong language');
  if (strpos($title,'[:')===false) return $title;
  return preg_match('/\[:'.preg_quote($lang,'/').'\](.*?)(?=\[:)/s',$title,$m) ? $m[1] : '';
 }
 function qtranxf_convertURL($url,$lang) { return $url.'?lang='.$lang; }
}
$GLOBALS['options'][Parcs_HT_WordPress_Events::OPTION]=array('enabled'=>'1');
$GLOBALS['posts']=array(1=>array('start'=>'20261020','end'=>'','status'=>'publish','title'=>'[:fr]Fête[:en]Festival[:]'));
Parcs_HT_WordPress_Events::invalidate(1);
verify(Parcs_HT_WordPress_Events::rows('2026','de')===array(),'Missing translation exposed');
$english=Parcs_HT_WordPress_Events::rows('2026','en');
verify($english[0]['title']['en']==='Festival' && substr($english[0]['button_url']['en'],-7)==='lang=en','Translated title/permalink mismatch');
verify(count(Parcs_HT_WordPress_Events::season(array(),'2026')['special_periods'])===1,'Multilingual record duplicated');

// The existing publication gate remains authoritative before any WordPress projection.
class Parcs_HT_Defaults {
 public static function select_season_year($all) { return '2026'; }
}
require $root.'/includes/class-parcs-ht-schedule.php';
$public=Parcs_HT_Schedule::public_settings(array('seasons'=>array('2026'=>array('published'=>'1'),'2027'=>array('published'=>'0'))));
verify(isset($public['seasons']['2026']) && !isset($public['seasons']['2027']),'WordPress adapter published a hidden season');
verify(count($public['seasons']['2026']['specialPeriods'])===1,'Public calendar did not receive WordPress event');
echo "OK: exact translations, shared language projection and published-year boundary.\n";
