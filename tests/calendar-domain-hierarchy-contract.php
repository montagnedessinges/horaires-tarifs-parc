<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$defaults = file_get_contents($root . '/includes/class-parcs-ht-defaults.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
$schedule = file_get_contents($root . '/includes/class-parcs-ht-schedule.php');
$frontend = file_get_contents($root . '/assets/frontend.js');
$css = file_get_contents($root . '/assets/frontend.css');

function calendar_domain_check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

calendar_domain_check(strpos($main, 'Version: 1.15.6') !== false, 'plugin version is 1.15.6');
calendar_domain_check(strpos($defaults, 'const SCHEMA_VERSION = 27;') !== false, 'settings schema is upgraded non-destructively');
calendar_domain_check(strpos($defaults, 'upgrade_v156_structures') !== false, '1.15.6 migration exists');
calendar_domain_check(strpos($schedule, "'calendar_hours_title'") !== false, 'editable park-hours title is exposed publicly');
calendar_domain_check(strpos($admin, 'Couleur du bloc d’accès') !== false, 'domain block color is editable');
calendar_domain_check(strpos($admin, '[access_message]') !== false && strpos($admin, '[details_message]') !== false, 'domain messages are editable');
calendar_domain_check(strpos($frontend, "box.appendChild(parkTitle);\n    box.appendChild(hours);") !== false, 'park title is inserted after the date and before the hours');
calendar_domain_check(strpos($frontend, 'rule.pause_start') !== false && strpos($frontend, 'rule.resume') !== false && strpos($frontend, 'rule.last_entry') !== false, 'domain times come from editable rule fields');
calendar_domain_check(strpos($frontend, 'translated(rule.info,language)') !== false, 'existing complementary text remains rendered');
calendar_domain_check(strpos($frontend, 'createDomainTooltip(rule,language)') !== false, 'existing domain tooltip remains rendered');
calendar_domain_check(strpos($css, '--htp-domain-color') !== false, 'domain block has its own visual color variable');
calendar_domain_check(strpos($frontend, "'12:00'") === false && strpos($frontend, "'13:00'") === false && strpos($frontend, "'11:30'") === false, 'MDS domain times are not hardcoded in the frontend');

echo "Calendar/domain hierarchy contract: OK\n";
