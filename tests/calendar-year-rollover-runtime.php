<?php
// Exercise the real option filters, Defaults request cache and public JSON projection.
// Only the WordPress boundary is stubbed; no production settings are written.
define('ABSPATH', __DIR__);
$GLOBALS['rollover_filters'] = array();
$GLOBALS['rollover_admin'] = false;
function add_filter($hook, $callback, $priority = 10, $accepted = 1) {
    $GLOBALS['rollover_filters'][$hook][$priority][] = $callback;
}
function get_option($key, $default = false) {
    $value = $GLOBALS['rollover_options'][$key] ?? $default;
    $filters = $GLOBALS['rollover_filters']['option_' . $key] ?? array();
    ksort($filters);
    foreach ($filters as $callbacks) foreach ($callbacks as $callback) $value = call_user_func($callback, $value);
    return $value;
}
function is_admin() { return $GLOBALS['rollover_admin']; }
function wp_date($format, $timestamp = null, $timezone = null) {
    return (new DateTimeImmutable($GLOBALS['rollover_date'] . ' 12:00:00', $timezone ?: new DateTimeZone('Europe/Paris')))->format($format);
}
function home_url() { return $GLOBALS['rollover_host']; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_text_field($value) { return trim((string)$value); }
function wp_unslash($value) { return $value; }
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root . '/includes/class-parcs-ht-defaults.php';
require $root . '/includes/class-parcs-ht-schedule.php';
require $root . '/includes/class-parcs-ht-display-policy.php';
require $root . '/includes/class-parcs-ht-public-seasons.php';
require $root . '/includes/class-parcs-ht-public-visibility.php';
add_filter('option_' . Parcs_HT_Defaults::OPTION, array('Parcs_HT_Display_Policy', 'filter_main_option'), 4);
add_filter('option_' . Parcs_HT_Defaults::OPTION, array('Parcs_HT_Public_Seasons', 'filter_main_option'), 5);
add_filter('option_' . Parcs_HT_Defaults::OPTION, array('Parcs_HT_Public_Visibility', 'filter_calendar_window'), 8);
function rollover_check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function rollover_reset() {
    Parcs_HT_Display_Policy::invalidate_main_cache(Parcs_HT_Defaults::OPTION);
    $property = new ReflectionProperty('Parcs_HT_Public_Seasons', 'raw_main');
    $property->setAccessible(true);
    $property->setValue(null, null);
}
$cases = 0;
foreach (array('https://www.la-foret-des-singes.com', 'https://www.montagnedessinges.com') as $host) {
    $GLOBALS['rollover_host'] = $host;
    // Both stale manual switches and the already-correct manual configuration.
    foreach (array(false, true) as $manual_matches) {
        $saved = Parcs_HT_Defaults::get();
        $old = Parcs_HT_Defaults::empty_season('2026');
        $old['published'] = '1';
        $old['calendar_visible'] = $manual_matches ? '0' : '1';
        $old['public_display_until'] = '2026-11-12';
        $next = Parcs_HT_Defaults::empty_season('2027');
        $next['calendar_visible'] = $manual_matches ? '1' : '0';
        $next['public_display_from'] = '2026-11-12';
        $next['public_display_until'] = '2027-11-14';
        $saved['seasons'] = array('2026' => $old, '2027' => $next);
        foreach (array('2026-11-11','2026-11-12','2026-11-13','2027-11-14') as $date) {
            foreach (array(false, true) as $option_first) {
                $GLOBALS['rollover_date'] = $date;
                $GLOBALS['rollover_options'] = array(Parcs_HT_Defaults::OPTION => $saved);
                rollover_reset();
                if ($option_first) get_option(Parcs_HT_Defaults::OPTION);
                $expected = $date < '2026-11-12' ? ($manual_matches ? array('2027') : array('2026')) : ($date < '2027-11-14' ? array('2027') : array());
                for ($read = 0; $read < 2; $read++) {
                    $settings = Parcs_HT_Defaults::settings();
                    $payload = Parcs_HT_Schedule::public_settings($settings);
                    $years = array_map('strval', array_keys($payload['seasons']));
                    rollover_check($years === $expected, "$host $date manual=$manual_matches optionFirst=$option_first: calendar JSON " . json_encode($years) . ' expected ' . json_encode($expected));
                    rollover_check(Parcs_HT_Public_Visibility::calendar_years() === $expected, 'Calendar JSON differs from central policy');
                    if ($expected) rollover_check($payload['activeSeasonYear'] === $expected[0], 'Wrong initial calendar year');
                    rollover_check($settings['seasons']['2026']['public_display_until'] === '2026-11-12', 'Projection lost deactivation date');
                }
                rollover_check($GLOBALS['rollover_options'][Parcs_HT_Defaults::OPTION] === $saved, 'Read changed saved settings');
                $cases++;
            }
        }
    }
}
// Boundary rules without reloading options, including legacy publication flags.
foreach (array(
    array(array('published'=>'1'), '2026-11-13', '1'),
    array(array('published'=>'0'), '2026-11-13', '0'),
    array(array('published'=>'1','calendar_visible'=>'0'), '2026-11-13', '0'),
    array(array('published'=>'0','calendar_visible'=>'1','public_display_from'=>'2027-01-01'), '2026-11-13', '1'),
    array(array('published'=>'1','calendar_visible'=>'1','public_force_display'=>'1','public_display_from'=>'2026-11-12','public_display_until'=>'2026-11-12'), '2026-11-12', '0'),
) as $case) {
    $actual = Parcs_HT_Display_Policy::apply_season_visibility($case[0], $case[1]);
    rollover_check($actual['published'] === $case[2], 'Boundary/legacy visibility mismatch');
    $cases++;
}
$GLOBALS['rollover_admin'] = true;
rollover_reset();
rollover_check(get_option(Parcs_HT_Defaults::OPTION) === $saved, 'Admin option was projected');
echo "Calendar rollover: $cases cases passed (both parks, real filters, request cache and JSON).\n";
