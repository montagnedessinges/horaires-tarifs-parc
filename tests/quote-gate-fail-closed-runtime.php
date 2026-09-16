<?php

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

$GLOBALS['htp_quote_gate_runtime_options'] = array();
function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['htp_quote_gate_runtime_options']) ? $GLOBALS['htp_quote_gate_runtime_options'][$name] : $default;
}

final class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
}

final class Parcs_HT_Group_Quotes {
    public static function settings() {
        return array('seasons'=>array(
            '2027'=>array('published'=>'1','child'=>6.5,'adult'=>9,'disability'=>6.5,'companion'=>6.5),
            '2028'=>array('published'=>'1','child'=>7,'adult'=>9.5,'disability'=>7,'companion'=>7),
        ));
    }
}

function quote_gate_runtime_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-schedule.php';
require_once $root . '/includes/class-parcs-ht-quote-gate.php';

$GLOBALS['htp_quote_gate_runtime_options'][Parcs_HT_Defaults::OPTION] = array(
    'general'=>array('timezone'=>'Europe/Paris'),
    'seasons'=>array(
        '2027'=>array(
            'season_start'=>'2027-03-20',
            'season_end'=>'2027-11-14',
            'regular_periods'=>array(),
            'exceptions'=>array(),
        ),
    ),
);

$missing_schedule = Parcs_HT_Quote_Gate::status_for_date('2027-05-10');
quote_gate_runtime_assert($missing_schedule['valid'] === true, '2027 date is syntactically valid');
quote_gate_runtime_assert($missing_schedule['tariffs'] === true, '2027 group tariffs remain available');
quote_gate_runtime_assert($missing_schedule['closed'] === true, '2027 with no configured schedule fails closed');

$missing_season = Parcs_HT_Quote_Gate::status_for_date('2028-05-10');
quote_gate_runtime_assert($missing_season['tariffs'] === true, '2028 quote tariffs can exist before schedules are configured');
quote_gate_runtime_assert($missing_season['closed'] === true, 'missing schedule season fails closed instead of silently opening');

$GLOBALS['htp_quote_gate_runtime_options'][Parcs_HT_Defaults::OPTION]['seasons']['2027']['regular_periods'][] = array(
    'enabled'=>'1','start'=>'2027-03-20','end'=>'2027-08-31','weekdays'=>array('1','2','3','4','5','6','7'),
    'open'=>'09:30','close'=>'17:30','open2'=>'','close2'=>'','color'=>'#9AAA8B',
);
$open_day = Parcs_HT_Quote_Gate::status_for_date('2027-05-10');
quote_gate_runtime_assert($open_day['closed'] === false, 'a real configured opening schedule explicitly opens the date');

$impossible = Parcs_HT_Quote_Gate::status_for_date('2027-02-30');
quote_gate_runtime_assert($impossible['valid'] === false && $impossible['closed'] === true, 'impossible calendar dates are rejected and never treated as open');

echo "Quote gate fail-closed runtime: OK\n";
