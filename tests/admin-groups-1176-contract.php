<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);

function htp_1176_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$groups = file_get_contents($root . '/includes/class-parcs-ht-admin-group-tariffs.php');
$settings = file_get_contents($root . '/includes/class-parcs-ht-group-tariff-settings.php');
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$shared = file_get_contents($root . '/includes/class-parcs-ht-tariff-shared-1168.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$css = file_get_contents($root . '/assets/admin-groups-1176.css');

preg_match('/Version:\s*([0-9.]+)/', (string)$main, $version_match);
$version = $version_match[1] ?? '0.0.0';
htp_1176_assert(version_compare($version, '1.17.6', '>='), 'version 1.17.6 ou suivante absente');
htp_1176_assert(strpos($main, 'class-parcs-ht-admin-group-tariffs.php') !== false, 'écran Groupes 1.17.6 non chargé');
htp_1176_assert(strpos($main, 'Parcs_HT_Admin_Group_Tariffs::init();') !== false, 'écran Groupes 1.17.6 non initialisé');

htp_1176_assert(is_string($groups) && strpos($groups, "const PAGE = 'parcs-ht-groups';") !== false, 'slug Groupes dédié absent');
htp_1176_assert(strpos($groups, "'htp-tariffs-groups'") !== false, 'ancien lien Tarifs groupes non redirigé');
htp_1176_assert(strpos($groups, 'render_year_context') !== false, 'contexte annuel commun absent');
htp_1176_assert(strpos($groups, 'parcs_ht_group_workspace') !== false, 'marqueur de sauvegarde Groupes absent');
htp_1176_assert(strpos($groups, 'settings[_complete][tariffs]') !== false, 'sauvegarde tarifaire canonique absente');
htp_1176_assert(strpos($groups, 'value="parcs_ht_save"') !== false, 'action canonique parcs_ht_save non réutilisée');
htp_1176_assert(strpos($groups, 'preserve_non_group_tariffs') !== false && strpos($groups, "['groups']") !== false && strpos($groups, "['columns']['groups']") !== false, 'isolation des données visiteurs absente');
htp_1176_assert(strpos($groups, 'Aucune donnée Individuels / Réduits') !== false, 'garantie visuelle de séparation visiteurs/Groupes absente');

htp_1176_assert(strpos($groups, 'Parcs_HT_Group_Tariff_Settings::settings') !== false && strpos($groups, 'Parcs_HT_Group_Tariff_Settings::save') !== false, 'réglages publics Groupes existants non réutilisés');
htp_1176_assert(strpos($groups, 'Moyens de paiement Groupes') !== false, 'moyens de paiement Groupes absents');
htp_1176_assert(strpos($groups, 'Aucun moyen de paiement visiteurs') !== false, 'séparation des moyens de paiement visiteurs non explicitée');
htp_1176_assert(strpos($groups, 'Acheter vos billets') !== false, 'protection du CTA visiteurs non documentée dans l’écran Groupes');
htp_1176_assert(strpos($groups, 'Devis groupes') !== false && strpos($groups, 'Guides pédagogiques') !== false, 'famille Groupes incomplète');

htp_1176_assert(strpos($groups, 'Parcs_HT_Public_Visibility::module_visible') !== false, 'état effectif de publication non réutilisé');
htp_1176_assert(strpos($groups, 'group_tariff_grid_ready') !== false, 'diagnostic de grille Groupes absent');
htp_1176_assert(strpos($groups, 'quote_binding_valid') !== false, 'diagnostic de liaison devis absent');
htp_1176_assert(strpos($groups, 'Gérer l’activation annuelle') !== false, 'pont vers l’activation annuelle canonique absent');
htp_1176_assert(strpos($groups, 'name="group_display[published]"') === false, 'un second interrupteur annuel de publication a été créé');

htp_1176_assert(is_string($settings) && strpos($settings, "const OPTION = 'parcs_ht_group_tariff_settings';") !== false, 'stockage historique Groupes disparu');
htp_1176_assert(is_string($visibility) && strpos($visibility, 'group_tariff_years') !== false, 'visibilité annuelle Groupes canonique absente');
htp_1176_assert(is_string($shared) && strpos($shared, 'Parcs_HT_Group_Tariffs') !== false, 'renderer Groupes partagé 1.16.8 non conservé');
htp_1176_assert(is_string($portal) && strpos($portal, 'calendar') !== false, 'portail Groupes ou calendrier canonique non conservé');
htp_1176_assert(is_string($css) && strpos($css, '.htp-1176-groups') !== false, 'styles dédiés Groupes absents');

fwrite(STDOUT, "OK admin-groups-1176-contract\n");
