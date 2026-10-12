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
function get_post_meta($id,$key,$single) {
 $map=array('date_debut'=>'start','date_fin'=>'end','_parcs_ht_event_calendar_enabled'=>'visible','_parcs_ht_event_calendar_color'=>'color','_parcs_ht_event_calendar_marker'=>'marker');
 verify(isset($map[$key]),'Unexpected WordPress meta read: '.$key);
 return $GLOBALS['posts'][$id][$map[$key]] ?? '';
}
function get_post_field($field,$id,$context) { verify(in_array($field,array('post_title','post_password'),true),'Article content read'); return $GLOBALS['posts'][$id][$field==='post_title'?'title':'password'] ?? ''; }
function get_permalink($id) { return 'https://example.test/event/'.$id; }
function has_filter($name) { return false; }
function sanitize_text_field($value) { return strip_tags($value); }
function esc_url_raw($value) { return $value; }
class WP_Query {
 public $posts;
 public function __construct($args) {
  verify($args['suppress_filters']===false, 'WordPress query filters disabled');
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
verify($rows[0]['display_mode']==='long' && $rows[0]['color']==='#e7c55b','Multi-day event should show dots by default');
verify($rows[2]['display_mode']==='spot','Single-day event should show a star by default');
$GLOBALS['posts'][1]['marker']='star';
$GLOBALS['posts'][1]['color']='#418a6b';
Parcs_HT_WordPress_Events::invalidate(1);
$rows=Parcs_HT_WordPress_Events::rows('2026','fr');
verify($rows[0]['display_mode']==='spot' && $rows[0]['color']==='#418a6b','Individual star/color override ignored');
$GLOBALS['posts'][1]['marker']='dot';
$GLOBALS['posts'][1]['color']='not-a-color';
Parcs_HT_WordPress_Events::invalidate(1);
$rows=Parcs_HT_WordPress_Events::rows('2026','fr');
verify($rows[0]['display_mode']==='long' && $rows[0]['color']==='#e7c55b','Dot/invalid color fallback ignored');
$GLOBALS['posts'][1]['visible']='0';
Parcs_HT_WordPress_Events::invalidate(1);
verify(count(Parcs_HT_WordPress_Events::rows('2026','fr'))===2,'Hidden event leaked into public projections');
verify(count(Parcs_HT_WordPress_Events::season(array(),'2026')['special_periods'])===2,'Hidden event leaked into HTML/calendar season');
$GLOBALS['posts'][1]['visible']='1';
Parcs_HT_WordPress_Events::invalidate(1);
$rows=Parcs_HT_WordPress_Events::rows('2026','fr');
verify($rows[2]['start']===$rows[2]['end'],'Missing end not normalized');
verify(count(Parcs_HT_WordPress_Events::rows('2027','fr'))===1,'Cross-year event lost');
verify(count(Parcs_HT_WordPress_Events::rows('2028','fr'))===0,'Wrong year exposed');
$manual=array('special_periods'=>array(array('enabled'=>'1','kind'=>'other','title'=>array('fr'=>'Période')),array('enabled'=>'1','kind'=>'event','title'=>array('fr'=>'Ancien événement'))),'regular_periods'=>array('unchanged'));
$projected=Parcs_HT_WordPress_Events::season($manual,'2026');
verify(count($manual['special_periods'])===2, 'Source history modified');
verify(count($projected['special_periods'])===4 && $projected['regular_periods']===$manual['regular_periods'],'Reference period altered or historical event displayed');
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
verify(count(Parcs_HT_WordPress_Events::season($manual,'2026')['special_periods'])===1,'Disabling WordPress revived manual events');
foreach (array('includes/class-parcs-ht-alerts.php','includes/class-parcs-ht-popup-1200.php') as $file) {
 $source=file_get_contents($root.'/'.$file);
 verify(strpos($source,'special_periods')===false && strpos($source,'show_popup')===false,'Event popup path survived');
}
foreach (array('includes/class-parcs-ht-admin.php','includes/class-parcs-ht-admin-periods.php') as $file) verify(strpos(file_get_contents($root.'/'.$file),'data-htp-popup-block')===false,'Event popup controls survived');
verify(strpos(file_get_contents($root.'/assets/admin.js'), 'exceptionAutoMessage')===false, 'Legacy event popup preview survived');
verify(strpos(file_get_contents($root.'/includes/class-parcs-ht-admin-communication-1179.php'), 'popup_source_counts')===false, 'Legacy popup source screen survived');
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
 const SCHEMA_VERSION = 'test';
 public static function get() { return array('site_type'=>$GLOBALS['test_park'] ?? 'montagne'); }
 public static function select_season_year($all) { return '2026'; }
}
require $root.'/includes/class-parcs-ht-schedule.php';
$public=Parcs_HT_Schedule::public_settings(array('seasons'=>array('2026'=>array('published'=>'1'),'2027'=>array('published'=>'0'))));
verify(isset($public['seasons']['2026']) && !isset($public['seasons']['2027']),'WordPress adapter published a hidden season');
verify(count($public['seasons']['2026']['specialPeriods'])===1,'Public calendar did not receive WordPress event');
echo "OK: exact translations, shared language projection and published-year boundary.\n";

// Exercise the canonical save sanitizer: editing periods must retain immutable event history.
function wp_unslash($v) { return $v; }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($v)); }
function sanitize_textarea_field($v) { return strip_tags($v); }
function sanitize_hex_color($v) { return preg_match('/^#[a-fA-F0-9]{6}$/', $v) ? $v : ''; }
function sanitize_email($v) { return $v; }
function get_bloginfo($key) { return $key==='charset' ? 'UTF-8' : 'Parc'; }
function wp_strip_all_tags($v) { return strip_tags($v); }
require $root.'/includes/class-parcs-ht-admin.php';
$sanitize = new ReflectionMethod('Parcs_HT_Admin', 'sanitize');
$sanitize->setAccessible(true);
$history = array('kind'=>'event','enabled'=>'1','title'=>array('fr'=>'Historique'),'color'=>'#123456','extra'=>'keep');
$other_year = array('special_periods'=>array($history));
$current = $other_year;
$period = array('kind'=>'other','enabled'=>'1','start'=>'2026-10-01','end'=>'2026-10-31','title'=>array('fr'=>'Période'));
$raw = array('_complete'=>array('holidays'=>'1'),'special_periods'=>array($period,array('kind'=>'event','title'=>array('fr'=>'Injection'))));
foreach (array('montagne','foret') as $park) {
 $GLOBALS['test_park'] = $park;
 $current = $other_year;
 foreach (array('1','0') as $enabled) {
  $raw['special_periods'][0]['enabled'] = $enabled;
  $current = $sanitize->invoke(null, $raw, $current);
  verify(count($current['special_periods'])===2 && $current['special_periods'][1]===$history, 'Save altered history or accepted manual event');
  verify($current['special_periods'][0]['enabled']===$enabled, 'Reference period save lost');
 }
 $current = $sanitize->invoke(null, array('_complete'=>array('holidays'=>'1')), $current);
 verify($current['special_periods']===array($history), 'Deleting a reference period deleted historical events');
 verify($other_year===array('special_periods'=>array($history)), 'Other season mutated');
}
echo "OK: reference period saves/deletion preserve history and reject manual event creation.\n";

// Retired sources must not produce admin validation errors or audit counts.
require $root.'/includes/class-parcs-ht-verifier.php';
$legacy = array('special_periods'=>array(array('enabled'=>'1','kind'=>'event','show_popup'=>'1')), 'exceptions'=>array(array('enabled'=>'1','show_popup'=>'1')));
$errors = array(); $warnings = array();
$method = new ReflectionMethod('Parcs_HT_Verifier', 'check_popups'); $method->setAccessible(true);
$method->invokeArgs(null, array($legacy, &$errors, &$warnings));
verify(!$errors, 'Archived event/exception still triggers popup validation');
$method = new ReflectionMethod('Parcs_HT_Verifier', 'check_internal_contexts'); $method->setAccessible(true);
$method->invokeArgs(null, array(array('special_periods'=>$legacy['special_periods']), &$errors));
verify(!$errors, 'Archived event still requires an internal label');
$legacy['season_start']='2026-10-20'; $legacy['season_end']='2026-10-20'; $legacy['exceptions']=array();
$GLOBALS['options'][Parcs_HT_WordPress_Events::OPTION]=array('enabled'=>'0');
$audit=Parcs_HT_Schedule::audit_season($legacy);
verify($audit['summary']['events']===0, 'Admin audit counted an archived event');
echo "OK: retired sources excluded from admin diagnostics.\n";


// Theme contract from the Montagne des Singes ZIP (2026-10-12):
// the homepage queries CPT evenement and only ACF date_debut/date_fin.
$adapter=file_get_contents($root.'/includes/class-parcs-ht-wordpress-events.php');
verify(strpos($adapter, '_parcs_ht_event_calendar_color')!==false && strpos($adapter, '_parcs_ht_event_calendar_marker')!==false,'Dedicated post meta missing');
verify(strpos($adapter, "update_post_meta(") !== false,'Calendar preferences not saved');
$frontend=file_get_contents($root.'/assets/frontend.js');
$css=file_get_contents($root.'/assets/frontend.css');
verify(strpos($frontend, 'parcs-ht-calendar-marks')!==false && strpos($frontend, 'eventRows.slice(0,3)')!==false,'Multiple visible event markers missing');
verify(strpos($css, 'var(--htp-event-color,#e7c55b)')!==false,'Custom event colors unsupported');
echo "OK: event markers, visibility, contrast and theme integration contract.\n";
