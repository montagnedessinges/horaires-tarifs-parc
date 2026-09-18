<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$path = $root . '/includes/class-parcs-ht-global-appearance.php';

function htp_global_appearance_1171_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$code = file_get_contents($path);
htp_global_appearance_1171_assert(is_string($code) && $code !== '', 'référentiel global absent');

foreach (array(
    "Parcs_HT_Defaults::OPTION",
    "pre_update_option_",
    "preserve_new_fields",
    "primary_color",
    "secondary_color",
    "accent_color",
    "highlight_color",
    "button_primary_bg_color",
    "button_secondary_bg_color",
    "card_bg_color",
    "tab_active_bg_color",
    "chip_bg_color",
    "--htp-button-primary-bg",
    "--htp-card-bg",
    "--htp-tab-active-bg",
    "--htp-chip-bg",
) as $needle) {
    htp_global_appearance_1171_assert(strpos($code, $needle) !== false, 'contrat global incomplet : ' . $needle);
}

htp_global_appearance_1171_assert(strpos($code, 'public static function resolve') !== false, 'la cascade global/module/élément est absente');
htp_global_appearance_1171_assert(strpos($code, '$element_overrides') !== false, 'la priorité élément n’est pas prévue');
htp_global_appearance_1171_assert(strpos($code, '$module_overrides') !== false, 'la priorité module n’est pas prévue');
htp_global_appearance_1171_assert(strpos($code, "appearance_mode") !== false, 'le mode global/personnalisé n’est pas prévu');
htp_global_appearance_1171_assert(strpos($code, "!== 'custom'") !== false, 'un module sans choix explicite doit hériter du global');
htp_global_appearance_1171_assert(strpos($code, 'update_option(') === false, 'le référentiel ne doit pas créer un second moteur de sauvegarde');
htp_global_appearance_1171_assert(strpos($code, 'delete_option(') === false, 'le référentiel ne doit supprimer aucune donnée');

fwrite(STDOUT, "OK global-appearance-1171-contract\n");
