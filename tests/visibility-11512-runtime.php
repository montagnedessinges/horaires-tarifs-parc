<?php

define('ABSPATH', __DIR__);
$GLOBALS['options'] = array();
$GLOBALS['admin'] = false;
function get_option($key, $fallback = array()) { return $GLOBALS['options'][$key] ?? $fallback; }
function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; return true; }
function map_deep($value, $callback) { return is_array($value) ? array_map(static function ($v) use ($callback) { return map_deep($v, $callback); }, $value) : $callback($value); }
function is_admin() { return $GLOBALS['admin']; }
function current_user_can($cap) { return true; }
function wp_verify_nonce($value, $action) { return $value === 'valid'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim((string)$value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function wp_date($format, $stamp = null, $zone = null) { return $format === 'Y' ? '2026' : '2026-09-15'; }
function remove_accents($value) { return strtr((string)$value, array('é'=>'e','è'=>'e','ê'=>'e','à'=>'a','ù'=>'u','ô'=>'o','î'=>'i')); }
function esc_url_raw($value) { return $value; }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES); }
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES); }
final class Parcs_HT_Defaults { const OPTION = 'main'; public static function all_settings() { return get_option(self::OPTION); } }
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-display-policy.php';
require_once $root . '/includes/class-parcs-ht-group-tariff-settings.php';
require_once $root . '/includes/class-parcs-ht-group-quotes.php';
require_once $root . '/includes/class-parcs-ht-tariff-identities.php';
function check11512($ok, $message) { if (!$ok) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } echo "OK: $message\n"; }
$legacy = array('columns'=>array('individual'=>array(array('id'=>'old_price','label'=>array('fr'=>'Sur place')))), 'individual'=>array(array('enabled'=>'1','cells'=>array('old_price'=>array('value'=>'12 €')))));
$fixed = Parcs_HT_Display_Policy::normalize_tariffs($legacy);
check11512($fixed['individual'][0]['cells']['onsite']['value'] === '12 €', 'legacy 2026 visitor value survives column normalization');
check11512($fixed['columns']['individual'][0]['id'] === 'onsite' && $fixed['columns']['individual'][1]['visible'] === '0', '2026 onsite only: no empty online column');
check11512($fixed === Parcs_HT_Display_Policy::normalize_tariffs($fixed), 'normalization is idempotent');
$legacy['columns']['individual'][] = array('id'=>'web','label'=>array('fr'=>'En ligne'));
$legacy['individual'][0]['cells']['web'] = array('value'=>'11 €','old_value'=>'12 €');
$fixed = Parcs_HT_Display_Policy::normalize_tariffs($legacy);
check11512($fixed['individual'][0]['cells']['online']['value'] === '11 €' && $fixed['columns']['individual'][1]['visible'] === '1', 'two real channels: onsite first, online second');
$fixed['individual'][0]['cells']['online']['value'] = '';
check11512(Parcs_HT_Display_Policy::normalize_tariffs($fixed)['individual'][0]['cells']['online']['value'] === '', 'intentional empty value is not resurrected from legacy cells');
$rows = array(
 array('id'=>'tariff_row_000001','enabled'=>'1','label'=>array('fr'=>'Scolaire / extrascolaire'),'cells'=>array('tariff_col_000001'=>array('value'=>'6,50 €'))),
 array('id'=>'tariff_row_000002','enabled'=>'1','label'=>array('fr'=>'Adulte'),'cells'=>array('tariff_col_000001'=>array('value'=>'9 €'))),
 array('id'=>'tariff_row_000003','enabled'=>'1','label'=>array('fr'=>'Personne en situation de handicap et accompagnateur'),'cells'=>array('tariff_col_000001'=>array('value'=>'6,50 €'))),
);
$season = array('published'=>'0','tariffs'=>array('groups'=>$rows,'columns'=>array('groups'=>array(array('id'=>'tariff_col_000001')))));
$GLOBALS['options'][Parcs_HT_Group_Tariff_Settings::OPTION] = array('version'=>4,'seasons'=>array('2027'=>array('published'=>'0','display_from'=>'2099-01-01')));
$GLOBALS['options'][Parcs_HT_Group_Quotes::OPTION] = array('tariff_bindings'=>array('2027'=>array('column_id'=>'tariff_col_000001','child_row_id'=>'tariff_row_000001','adult_row_id'=>'tariff_row_000002','disability_row_id'=>'tariff_row_000003','companion_row_id'=>'tariff_row_000003')));
foreach (range(0,15) as $mask) {
 $v=$season;
 foreach (array('calendar_visible','retail_tariffs_visible','group_quotes_enabled','group_tariffs_visible') as $i=>$key) $v[$key]=($mask & (1 << $i)) ? '1' : '0';
 $GLOBALS['options']['main']=array('seasons'=>array('2027'=>$v));
 // Le nouveau moteur devis possède son propre état annuel ; le test le synchronise comme le ferait une sauvegarde admin réelle.
 $GLOBALS['options'][Parcs_HT_Group_Quotes::STATE_OPTION]=array('version'=>Parcs_HT_Group_Quotes::STATE_VERSION,'years'=>array('2027'=>array('enabled'=>($mask&4)?'1':'0')));
 $filtered=Parcs_HT_Display_Policy::filter_main_option($GLOBALS['options']['main']);
 check11512(($filtered['seasons']['2027']['published']==='1') === (bool)($mask&1), "calendar independent mask $mask");
 check11512(in_array('2027',Parcs_HT_Display_Policy::retail_years(),true) === (bool)($mask&2), "retail independent mask $mask");
 check11512(Parcs_HT_Group_Tariff_Settings::quote_enabled('2027') === (bool)($mask&4), "quote control independent mask $mask");
 check11512(in_array('2027',Parcs_HT_Group_Tariff_Settings::public_years(),true) === (bool)($mask&8), "group site independent mask $mask");
 $quote=Parcs_HT_Group_Quotes::settings(true);
 check11512(((string)($quote['seasons']['2027']['published']??'0')==='1') === (bool)($mask&4), "real quote engine permission mask $mask");
 if($mask&4) check11512((string)$quote['seasons']['2027']['adult']==='9', 'quote reads 2027 canonical adult price');
}
$GLOBALS['admin']=true;
$old=array('seasons'=>array('2026'=>array('published'=>'1','retail_tariffs_visible'=>'1'),'2027'=>$v));
$_POST=array('action'=>'parcs_ht_save','_wpnonce'=>'valid','season_year'=>'2027','settings'=>array('general'=>array('group_quotes_enabled'=>'0')));
$saved=Parcs_HT_Display_Policy::save_controls($old,$old,'main');
check11512($saved['seasons']['2026']===$old['seasons']['2026'], 'saving 2027 does not change 2026');
check11512($saved['seasons']['2027']['retail_tariffs_visible']==='1', 'partial save preserves absent controls');
check11512($saved['seasons']['2027']['group_quotes_enabled']==='0', 'explicit OFF is persisted');
$_POST['_wpnonce']='invalid';
check11512(Parcs_HT_Display_Policy::save_controls($old,$old,'main')===$old,'invalid nonce never writes');
echo "Visibility and prices runtime OK\n";

function wp_enqueue_script($name) {}
function sanitize_email($value) { return $value; }
function esc_url($value) { return $value; }
function wp_kses_post($value) { return $value; }
function wp_kses($value, $allowed) { return $value; }
require_once $root . '/includes/class-parcs-ht-schedule.php';
require_once $root . '/includes/class-parcs-ht-public-seasons.php';
require_once $root . '/includes/class-parcs-ht-shortcodes.php';
$GLOBALS['admin']=false;
$visitor = $legacy;
$visitor['individual'][0]['label']=array('fr'=>'Adulte');
unset($visitor['individual'][0]['cells']['web']);
$visitor['groups']=array(); $visitor['notes']=array();
$visitor['print']=array('pdf_enabled'=>'0');
$a=array('published'=>'1','retail_tariffs_visible'=>'1','group_tariffs_visible'=>'0','tariffs'=>$visitor);
$b=$a;$b['published']='0';$b['tariffs']['individual'][0]['cells']['web']=array('value'=>'11 €');
$GLOBALS['options']['main']=array('seasons'=>array('2026'=>$a,'2027'=>$b));
Parcs_HT_Display_Policy::filter_main_option($GLOBALS['options']['main']);
$method=new ReflectionMethod('Parcs_HT_Shortcodes','tariffs'); $method->setAccessible(true);
$html=$method->invoke(null,'test','fr','',array('general'=>array()));
check11512(strpos($html,'data-htp-year-panel="2026"')!==false && strpos($html,'data-htp-year-panel="2027"')!==false,'real renderer includes authorized years');
preg_match('/data-htp-year-panel="2026".*?(?=data-htp-year-panel="2027")/s',$html,$match);
check11512(strpos($match[0],'12 €')!==false && strpos($match[0],'data-htp-channel="online"')===false,'real 2026 HTML has onsite price and no online cell');
check11512(strpos($html,'data-htp-channel="onsite"')<strpos($html,'data-htp-channel="online"'),'real HTML uses onsite before online');
if (getenv('HTP_RENDER_PATH')) file_put_contents(getenv('HTP_RENDER_PATH'),$html);
