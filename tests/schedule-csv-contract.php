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
    'import endpoint exists' => strpos($csv, 'parcs_ht_schedule_csv_import') !== false,
    'regular periods supported' => strpos($csv, "'regular'") !== false,
    'exception hours supported' => strpos($csv, "'exception_hours'") !== false,
    'closed exceptions supported' => strpos($csv, "'exception_closed'") !== false,
    'holidays supported' => strpos($csv, "'holiday'") !== false,
    'school holidays supported' => strpos($csv, "'school_holiday'") !== false,
    'events supported' => strpos($csv, "'event'") !== false,
    'security revision created before import' => strpos($csv, 'Avant import CSV horaires/calendrier') !== false,
    'CSV import is administrator-only' => strpos($csv, "current_user_can('manage_options')") !== false,
    'CSV import uses nonce' => strpos($csv, "check_admin_referer('parcs_ht_schedule_csv_import_'") !== false,
    'CSV size is limited' => strpos($csv, 'MAX_BYTES') !== false,
);

$failed = false;
foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . "\n";
    if (!$ok) $failed = true;
}
if ($failed) exit(1);
echo "Schedule CSV contract: OK\n";
