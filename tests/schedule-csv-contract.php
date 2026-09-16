<?php

$root = getenv('PLUGIN_ROOT');
if (!is_string($root) || $root === '') $root = dirname(__DIR__);

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$csv = file_get_contents($root . '/includes/class-parcs-ht-schedule-csv.php');
if (!is_string($main) || !is_string($csv)) {
    fwrite(STDERR, "Unable to read schedule CSV sources.\n");
    exit(1);
}

$checks = array(
    'CSV module is loaded' => strpos($main, "class-parcs-ht-schedule-csv.php") !== false,
    'CSV module is initialized in admin' => strpos($main, 'Parcs_HT_Schedule_CSV::init();') !== false,
    'template endpoint exists' => strpos($csv, 'parcs_ht_schedule_csv_template') !== false,
    'export endpoint exists' => strpos($csv, 'parcs_ht_schedule_csv_export') !== false && strpos($csv, 'public static function export_csv()') !== false,
    'import endpoint exists' => strpos($csv, 'parcs_ht_schedule_csv_import') !== false,
    'regular periods supported' => strpos($csv, "'regular'") !== false,
    'exception hours supported' => strpos($csv, "'exception_hours'") !== false,
    'closed exceptions supported' => strpos($csv, "'exception_closed'") !== false,
    'holidays supported' => strpos($csv, "'holiday'") !== false,
    'school holidays supported' => strpos($csv, "'school_holiday'") !== false,
    'events supported' => strpos($csv, "'event'") !== false,
    'limited access supported' => strpos($csv, "'limited_access'") !== false && strpos($csv, "['domain_rules'] = \$domain") !== false,
    'limited access translations supported' => strpos($csv, "'public_title_fr'") !== false && strpos($csv, "'access_message_fr'") !== false && strpos($csv, "'tooltip_text_fr'") !== false,
    'CSV export is reimportable by canonical headers' => strpos($csv, 'self::output_row($out, $row)') !== false && strpos($csv, 'self::headers()') !== false,
    'missing CSV categories stay untouched' => strpos($csv, "if (isset(\$seen['limited_access'])) \$season['domain_rules'] = \$domain;") !== false && strpos($csv, "if (isset(\$seen['regular'])) \$season['regular_periods'] = \$regular;") !== false,
    'security revision created before import' => strpos($csv, 'Avant import CSV horaires/calendrier') !== false,
    'CSV import is administrator-only' => strpos($csv, "current_user_can('manage_options')") !== false,
    'CSV import uses nonce' => strpos($csv, "check_admin_referer('parcs_ht_schedule_csv_import_'") !== false,
    'CSV export uses nonce' => strpos($csv, "check_admin_referer('parcs_ht_schedule_csv_export_'") !== false,
    'CSV size is limited' => strpos($csv, 'MAX_BYTES') !== false,
    'admin list search is present' => strpos($csv, 'data-htp-admin-list-search') !== false,
    'search covers periods events access and exceptions' => strpos($csv, "#htp-holidays .htp-subsection") !== false && strpos($csv, "#htp-domain") !== false && strpos($csv, "#htp-exceptions") !== false,
    'search is visual only' => strpos($csv, 'row.hidden=!visible') !== false && strpos($csv, 'removeChild') === false,
    'search reset keeps new rows visible' => strpos($csv, "if(input.value){input.value='';window.setTimeout(filter,0);}") !== false,
    'CSV links are exposed in covered sections' => strpos($csv, 'cette section est couverte par l’import/export') !== false,
);

$failed = false;
foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . "\n";
    if (!$ok) $failed = true;
}
if ($failed) exit(1);
echo "Schedule CSV and admin search contract: OK\n";
