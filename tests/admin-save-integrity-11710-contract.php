<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.10')) {
    echo "SKIP: contract 1.17.10 applies from 1.17.10.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$guard = file_get_contents($root . '/includes/class-parcs-ht-admin-save-guard-11710.php');
$js = file_get_contents($root . '/assets/admin-save-guard-11710.js');
$schedule = file_get_contents($root . '/includes/class-parcs-ht-admin-schedule.php');
$periods = file_get_contents($root . '/includes/class-parcs-ht-admin-periods.php');
$retail = file_get_contents($root . '/includes/class-parcs-ht-admin-retail-tariffs.php');
$groups = file_get_contents($root . '/includes/class-parcs-ht-admin-group-tariffs.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-admin-group-quotes-1177.php');
$guides = file_get_contents($root . '/includes/class-parcs-ht-admin-guides-1178.php');
$communication = file_get_contents($root . '/includes/class-parcs-ht-admin-communication-1179.php');

foreach (array('main'=>$main,'bootstrap'=>$bootstrap,'guard'=>$guard,'guard js'=>$js,'schedule'=>$schedule,'periods'=>$periods,'retail'=>$retail,'groups'=>$groups,'quotes'=>$quotes,'guides'=>$guides,'communication'=>$communication) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($main, array(
    'Version: 1.17.10',
    "define('PARCS_HT_VERSION', '1.17.10')",
), '1.17.10 plugin version');

release_contract_require_all($bootstrap, array(
    'class-parcs-ht-admin-save-guard-11710.php',
    'Parcs_HT_Admin_Save_Guard_11710::init();',
), '1.17.10 save guard bootstrap');

release_contract_require_all($guard, array(
    "const FIELD = 'parcs_ht_11710_complete'",
    "const SNAPSHOT_FIELD = 'parcs_ht_11710_snapshot'",
    'admin_init',
    'guard_admin_post',
    'request_complete',
    'restore_snapshot',
    'json_decode',
    'wp_slash($decoded)',
    "'parcs_ht_save'",
    "'parcs_ht_save_schedule_1173'",
    "'parcs_ht_save_general_publication'",
    "'parcs_ht_save_general_park'",
    "'parcs_ht_save_global_appearance'",
    "'parcs_ht_save_public_content'",
    "'parcs_ht_save_group_display_1176'",
    "'parcs_ht_save_quote_binding_1177'",
    "'parcs_ht_save_quote_forms_1177'",
    "'parcs_ht_save_quote_gate_1177'",
    "'parcs_ht_save_quote_engine_1177'",
    "'parcs_ht_save_pedagogical_guides'",
    "'parcs_ht_save_guide_appearance'",
    "'parcs_ht_advent_save_campaign'",
    "'parcs_ht_advent_save_content'",
    "'parcs_ht_advent_save_partner'",
    "'parcs_ht_advent_save_result'",
    "'parcs_ht_advent_import_csv'",
    "'parcs_ht_advent_apply_import'",
    "'parcs_ht_schedule_csv_import'",
    'Aucune donnée n’a été modifiée',
    'max_input_vars',
    'post_max_size',
), '1.17.10 destructive-save fail-safe coverage');

release_contract_require_all($js, array(
    "document.addEventListener('submit'",
    "document.addEventListener('formdata'",
    'payloadFrom(formData)',
    'clearFormData(formData)',
    "formData.append('action',action)",
    'formData.append(snapshotField,JSON.stringify(payload))',
    'filesFrom(formData)',
    'formData.append(field,action)',
    'form.appendChild(input)',
), '1.17.10 compact complete-payload transport');

// L’audit garde les protections spécialisées déjà introduites par chaque étape.
release_contract_require_all($schedule, array("['regular_periods']", 'clean_periods'), '1.17.10 schedule save audit');
release_contract_require_all($periods, array('settings[_complete][holidays]', 'settings[_complete][exceptions]', 'settings[_complete][domain_rules]', 'weekdays_touched'), '1.17.10 periods save audit');
release_contract_require_all($retail, array('parcs_ht_retail_workspace', 'preserve_group_tariffs'), '1.17.10 retail save audit');
release_contract_require_all($groups, array('parcs_ht_group_workspace', 'preserve_non_group_tariffs', 'save_display'), '1.17.10 group save audit');
release_contract_require_all($quotes, array('save_binding', 'save_forms', 'save_gate', 'save_engine'), '1.17.10 quote save audit');
release_contract_require_all($guides, array('parcs_ht_save_pedagogical_guides', 'parcs_ht_save_guide_appearance'), '1.17.10 guides save audit');
release_contract_require_all($communication, array('settings[_complete][alerts]', 'parcs_ht_save'), '1.17.10 popup save audit');

require __DIR__ . '/admin-save-guard-11710-runtime.php';

echo "OK: 1.17.10 corrective audit protects refactored admin saves against truncated POST data.\n";
