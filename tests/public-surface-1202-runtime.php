<?php
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('PARCS_HT_URL')) define('PARCS_HT_URL', 'https://example.test/wp-content/plugins/horaires-tarifs-parc/');
if (!defined('PARCS_HT_VERSION')) define('PARCS_HT_VERSION', '1.20.2-test');

$GLOBALS['surface1202_filters'] = array();
$GLOBALS['surface1202_inline'] = array();
$GLOBALS['surface1202_schedule_years'] = array('2026','2027');
$GLOBALS['surface1202_tariff_years'] = array('2026','2027');
$GLOBALS['surface1202_calendar_years'] = array('2026');

function esc_html($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc_url($v) { return (string)$v; }
function esc_url_raw($v) { return (string)$v; }
function sanitize_text_field($v) { return trim((string)$v); }
function wp_unslash($v) { return $v; }
function wp_json_encode($v) { return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
function wp_kses($v, $allowed) { unset($allowed); return (string)$v; }
function wp_script_is($handle, $state = 'enqueued') { unset($handle, $state); return true; }
function wp_add_inline_script($handle, $data, $position = 'after') { unset($handle, $position); $GLOBALS['surface1202_inline'][] = (string)$data; return true; }
function add_shortcode($tag, $callback) { unset($tag, $callback); }
function is_admin() { return false; }
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['surface1202_filters'][$hook][$priority][] = array($callback, $accepted_args);
    return true;
}
function remove_filter($hook, $callback, $priority = 10) {
    if (empty($GLOBALS['surface1202_filters'][$hook][$priority])) return false;
    foreach ($GLOBALS['surface1202_filters'][$hook][$priority] as $i => $item) {
        if ($item[0] === $callback) unset($GLOBALS['surface1202_filters'][$hook][$priority][$i]);
    }
    return true;
}
function apply_filters($hook, $value, ...$args) {
    if (empty($GLOBALS['surface1202_filters'][$hook])) return $value;
    ksort($GLOBALS['surface1202_filters'][$hook]);
    foreach ($GLOBALS['surface1202_filters'][$hook] as $rows) {
        foreach ($rows as $item) {
            $all = array_merge(array($value), $args);
            $value = call_user_func_array($item[0], array_slice($all, 0, max(1, (int)$item[1])));
        }
    }
    return $value;
}
function remove_query_arg($key, $url) {
    $parts = parse_url((string)$url);
    $query = array();
    if (!empty($parts['query'])) parse_str($parts['query'], $query);
    unset($query[$key]);
    $path = $parts['path'] ?? '/';
    return $path . ($query ? '?' . http_build_query($query) : '');
}
function add_query_arg($key, $value = null, $url = '') {
    $parts = parse_url((string)$url);
    $query = array();
    if (!empty($parts['query'])) parse_str($parts['query'], $query);
    if (is_array($key)) foreach ($key as $k => $v) $query[$k] = $v; else $query[$key] = $value;
    $path = $parts['path'] ?? '/';
    return $path . ($query ? '?' . http_build_query($query) : '');
}

final class Parcs_HT_Defaults {
    public static function all_settings() {
        return array(
            'general'=>array('show_public_holidays'=>'0'),
            'seasons'=>array(
                '2026'=>array(
                    'published'=>'1','calendar_visible'=>'1','season_start'=>'2026-03-21','season_end'=>'2026-11-08',
                    'regular_periods'=>array(array('enabled'=>'1','start'=>'2026-03-21','end'=>'2026-11-08','weekdays'=>array('1','2','3','4','5','6','7'),'open'=>'10:00','close'=>'17:00')),
                    'special_periods'=>array(),'school_holidays'=>array(),'public_holidays'=>array(),'domain_rules'=>array(),'exceptions'=>array(),
                ),
                '2027'=>array(
                    'published'=>'0','calendar_visible'=>'0','groups_schedule_visible'=>'1','season_start'=>'2027-03-20','season_end'=>'2027-11-07',
                    'regular_periods'=>array(array('enabled'=>'1','start'=>'2027-03-20','end'=>'2027-08-31','weekdays'=>array('1','2','3','4','5','6','7'),'open'=>'09:30','close'=>'17:30')),
                    'special_periods'=>array(
                        array('enabled'=>'1','start'=>'2027-06-01','end'=>'2027-06-02','kind'=>'event','show_on_calendar'=>'0','internal_label'=>'SECRET INTERNE','title'=>array('fr'=>'Ne doit pas être envoyé')),
                    ),
                    'school_holidays'=>array(),'public_holidays'=>array(),'domain_rules'=>array(),'exceptions'=>array(),
                ),
            ),
        );
    }
}
final class Parcs_HT_Schedule { public static function language() { return 'fr'; } }
final class Parcs_HT_Public_Visibility {
    public static function raw_season($year) { $all=Parcs_HT_Defaults::all_settings(); return $all['seasons'][(string)$year] ?? array(); }
    public static function group_schedule_years() { return $GLOBALS['surface1202_schedule_years']; }
    public static function group_tariff_years() { return $GLOBALS['surface1202_tariff_years']; }
    public static function calendar_years() { return $GLOBALS['surface1202_calendar_years']; }
    public static function order_years($years) { $years=array_values(array_unique(array_map('strval',(array)$years))); sort($years,SORT_NUMERIC); return $years; }
    public static function default_year($years, $requested='') { $years=self::order_years($years); return $requested!==''&&in_array((string)$requested,$years,true)?(string)$requested:($years?(string)$years[0]:''); }
    public static function scheduled_state($year) { return in_array((string)$year, self::calendar_years(), true) ? 'on' : 'off'; }
}
final class Parcs_HT_Tariff_Display {
    public static function render_group($language, $atts=array(), $year='') { unset($language,$atts); return '<p>Tarif groupe ' . esc_html($year) . '</p>'; }
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-calendar-semantic.php';
require_once $root . '/includes/class-parcs-ht-group-portal.php';

function do_shortcode($shortcode) {
    $language = strpos($shortcode, '_en]') !== false ? 'en' : (strpos($shortcode, '_de]') !== false ? 'de' : 'fr');
    $years = apply_filters('parcs_ht_calendar_semantic_years', null, 'parc_calendrier_' . $language, array());
    return '<div data-htp-component="calendar"></div>' . Parcs_HT_Calendar_Semantic::render($language, $years);
}
function surface1202_assert($ok, $message) { if (!$ok) { fwrite(STDERR, "[FAIL] {$message}\n"); exit(1); } echo "[OK] {$message}\n"; }

$visitor = Parcs_HT_Calendar_Semantic::render('fr');
surface1202_assert(strpos($visitor, 'Calendrier 2026') !== false, 'visitor semantic HTML contains the public visitor season');
surface1202_assert(strpos($visitor, 'Calendrier 2027') === false, 'visitor semantic HTML does not leak a group-only season');

$groupOnly = Parcs_HT_Calendar_Semantic::render('fr', array('2027'));
surface1202_assert(strpos($groupOnly, 'Calendrier 2027') !== false && strpos($groupOnly, '09:30–17:30') !== false, 'explicit group schedule scope renders 2027 server-side');
surface1202_assert(strpos($groupOnly, 'SECRET INTERNE') === false, 'semantic HTML does not expose internal labels');

$_SERVER['REQUEST_URI'] = '/groupes/';
$_GET = array();
$GLOBALS['surface1202_inline'] = array();
$html = Parcs_HT_Group_Portal::render('fr');
$text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
surface1202_assert(strpos($html, 'htp_group_year=2027') !== false, 'group year selector provides a real server URL');
surface1202_assert(strpos($html, 'Tarif groupe 2026') !== false && strpos($html, 'Tarif groupe 2027') !== false, 'public group tariff years are rendered server-side');
surface1202_assert(strpos($html, 'Calendrier 2027') !== false && strpos($html, '09:30–17:30') !== false, 'group page initial HTML contains public 2027 opening hours');
surface1202_assert(strpos($text, 'Les horaires d’ouverture ne sont pas disponibles pour cette année.') === false, 'false unavailable-hours message is absent from extracted text when active year has hours');
$inline = implode("\n", $GLOBALS['surface1202_inline']);
surface1202_assert(strpos($inline, 'SECRET INTERNE') === false, 'group JavaScript payload does not expose internal labels');

$GLOBALS['surface1202_schedule_years'] = array('2026');
$_GET = array('htp_group_year'=>'2027');
$htmlMissing = Parcs_HT_Group_Portal::render('fr');
$textMissing = html_entity_decode(strip_tags($htmlMissing), ENT_QUOTES, 'UTF-8');
surface1202_assert(strpos($htmlMissing, 'data-group-active-year="2027"') !== false, 'requested public tariff year remains the active server-rendered year');
surface1202_assert(strpos($textMissing, 'Les horaires d’ouverture ne sont pas disponibles pour cette année.') !== false, 'unavailable-hours message is emitted when it is actually true');

echo "Public surface 1.20.2 runtime OK.\n";
