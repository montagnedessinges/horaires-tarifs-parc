<?php

define('ABSPATH', __DIR__ . '/');

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        $key = strtolower((string)$key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return $value; }
}

require_once dirname(__DIR__) . '/includes/class-parcs-ht-admin-save-guard-11710.php';

function guard_assert($condition, $message) {
    if ($condition) {
        echo "[OK] {$message}\n";
        return;
    }
    fwrite(STDERR, "[FAIL] {$message}\n");
    exit(1);
}

$field = Parcs_HT_Admin_Save_Guard_11710::FIELD;
$action = 'parcs_ht_save_schedule_1173';

guard_assert(
    Parcs_HT_Admin_Save_Guard_11710::request_complete($action, array($field=>$action)),
    'un marqueur de fin correspondant autorise la sauvegarde'
);
guard_assert(
    !Parcs_HT_Admin_Save_Guard_11710::request_complete($action, array()),
    'un POST tronqué sans marqueur de fin est refusé'
);
guard_assert(
    !Parcs_HT_Admin_Save_Guard_11710::request_complete($action, array($field=>'parcs_ht_save')),
    'le marqueur d’une autre action ne peut pas valider la sauvegarde'
);
guard_assert(
    !Parcs_HT_Admin_Save_Guard_11710::request_complete($action, array($field=>array($action))),
    'un marqueur de type inattendu est refusé'
);

$protected = Parcs_HT_Admin_Save_Guard_11710::protected_actions();
foreach (array(
    'parcs_ht_save',
    'parcs_ht_save_schedule_1173',
    'parcs_ht_save_general_publication',
    'parcs_ht_save_group_display_1176',
    'parcs_ht_save_quote_forms_1177',
    'parcs_ht_save_pedagogical_guides',
    'parcs_ht_advent_save_campaign',
    'parcs_ht_schedule_csv_import',
) as $required) {
    guard_assert(in_array($required, $protected, true), 'action protégée : ' . $required);
}

echo "OK: 1.17.10 save guard rejects incomplete admin POST payloads before destructive handlers.\n";
