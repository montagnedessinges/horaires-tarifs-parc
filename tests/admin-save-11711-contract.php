<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.11')) {
    echo "SKIP: contract 1.17.11 applies from 1.17.11.\n";
    exit(0);
}

$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$save = file_get_contents($root . '/includes/class-parcs-ht-admin-save-11711.php');
$js = file_get_contents($root . '/assets/admin-save-11711.js');
$legacyJs = file_get_contents($root . '/assets/admin.js');
$year = file_get_contents($root . '/includes/class-parcs-ht-admin-year-context.php');
$navigation = file_get_contents($root . '/includes/class-parcs-ht-admin-navigation.php');
$periods = file_get_contents($root . '/includes/class-parcs-ht-admin-periods.php');

foreach (array('bootstrap'=>$bootstrap,'save'=>$save,'save js'=>$js,'legacy admin js'=>$legacyJs,'year context'=>$year,'navigation'=>$navigation,'periods'=>$periods) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($bootstrap, array(
    'class-parcs-ht-admin-save-11711.php',
    'Parcs_HT_Admin_Save_11711::init();',
), '1.17.11 save bootstrap');

if (strpos($bootstrap, 'class-parcs-ht-admin-save-guard-11710.php') !== false || strpos($bootstrap, 'Parcs_HT_Admin_Save_Guard_11710::init()') !== false) {
    fwrite(STDERR, "1.17.10 JSON save guard is still active in bootstrap.\n");
    exit(1);
}
if (file_exists($root . '/includes/class-parcs-ht-admin-save-guard-11710.php') || file_exists($root . '/assets/admin-save-guard-11710.js')) {
    fwrite(STDERR, "Obsolete 1.17.10 POST reconstruction files are still shipped.\n");
    exit(1);
}

release_contract_require_all($save, array(
    "const COMPLETE_FIELD = 'parcs_ht_11711_complete'",
    "const WORKSPACE_FIELD = 'parcs_ht_11711_workspace'",
    "add_action('admin_post_parcs_ht_save'",
    'guard_native_post',
    'rewrite_legacy_workspace_redirect',
    'Aucune donnée n’a été modifiée',
    "array('periods','retail','groups','popup')",
), '1.17.11 native POST integrity');

foreach (array('json_decode','wp_slash($decoded)','parcs_ht_11710_snapshot') as $forbidden) {
    if (strpos($save, $forbidden) !== false) {
        fwrite(STDERR, "Forbidden global POST reconstruction remains in 1.17.11 save layer: {$forbidden}\n");
        exit(1);
    }
}

release_contract_require_all($js, array(
    "document.addEventListener('submit'",
    "parcs_ht_11711_complete",
    "parcs_ht_11711_workspace",
    "input[name=\"action\"]",
    "parcs_ht_save",
    "form[data-htp-1174-form]",
    "form[data-htp-retail-tariffs-form]",
    "form[data-htp-group-tariffs-form]",
    "form[data-htp-popup-1179]",
    'neutralizeLegacyScopedSave',
    '[data-htp-active-tab-input]',
    "submitter.name='submit'",
    "appendHidden(form,'htp_save_active','1')",
), '1.17.11 end-of-form marker and legacy scoped-save isolation');
foreach (array('formdata','JSON.stringify','clearFormData','new FormData') as $forbidden) {
    if (strpos($js, $forbidden) !== false) {
        fwrite(STDERR, "1.17.11 JS must not rebuild the native form payload: {$forbidden}\n");
        exit(1);
    }
}

// Cause racine historique : admin.js désactive les autres sections quand le
// bouton submit s'appelle htp_save_active. L'écran Périodes 1.17.4 utilise ce
// nom sans le marqueur d'onglet actif ; la couche 1.17.11 doit donc neutraliser
// ce comportement avant que le formulaire natif soit sérialisé.
release_contract_require_all($legacyJs, array(
    "submitter.name!=='htp_save_active'",
    "form.querySelector('[data-htp-active-tab-input]')",
    'control.disabled=true',
), 'legacy scoped-save root cause remains documented');
release_contract_require_all($periods, array(
    'data-htp-1174-form',
    'name="htp_save_active" value="1"',
    'settings[_complete][exceptions]',
    'settings[_complete][domain_rules]',
), '1.17.4 form shape that triggered the regression');

release_contract_require_all($year, array(
    "const USER_META = 'parcs_ht_admin_year'",
    'sync_navigation_year',
    'update_user_meta',
    'get_user_meta',
    "wp_date('Y')",
    "'parcs-ht-popup-1179'",
    "'parcs-ht-advent-1179'",
), '1.17.11 administered year context');

if (strpos($navigation, " · brouillon") !== false) {
    fwrite(STDERR, "Obsolete global draft label is still present in annual navigation.\n");
    exit(1);
}
release_contract_require_all($navigation, array('Année administrée :'), '1.17.11 annual navigation wording');

require __DIR__ . '/admin-save-11711-runtime.php';
require __DIR__ . '/admin-year-context-11711-runtime.php';

echo "OK: 1.17.11 restores native saves, blocks the legacy scoped-save wipe, returns directly and persists the administered-year context.\n";
