<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$overview_path = $root . '/includes/class-parcs-ht-admin-overview.php';
$navigation_path = $root . '/includes/class-parcs-ht-admin-navigation.php';
$main_path = $root . '/horaires-tarifs-parc.php';

function htp_admin_1170_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$overview = file_get_contents($overview_path);
$navigation = file_exists($navigation_path) ? file_get_contents($navigation_path) : '';
$main = file_get_contents($main_path);
htp_admin_1170_assert(is_string($overview) && $overview !== '', 'classe Vue d’ensemble absente');
htp_admin_1170_assert(is_string($main) && $main !== '', 'fichier principal absent');

htp_admin_1170_assert(strpos($main, "class-parcs-ht-admin-overview.php") !== false, 'la Vue d’ensemble n’est pas chargée');
htp_admin_1170_assert(strpos($main, "Parcs_HT_Admin_Overview::init();") !== false, 'la Vue d’ensemble n’est pas initialisée');
htp_admin_1170_assert(strpos($overview, "redirect_default_entry") !== false, 'le menu principal n’ouvre pas l’entrée simplifiée');
htp_admin_1170_assert(strpos($overview, "Retour à la vue d’ensemble") !== false, 'le retour vers l’interface simple est absent des écrans détaillés');

foreach (array(
    'Administration générale',
    'Horaires & calendrier',
    'Périodes & événements',
    'Tarifs visiteurs',
    'Groupes',
    'Contenus & traductions',
    'Communication',
    'Pop-up',
    'Calendrier de l’Avent',
    'Aperçu',
    'Shortcodes',
    'Mises à jour',
) as $label) {
    htp_admin_1170_assert(strpos($overview, $label) !== false, 'élément manquant dans la Vue d’ensemble : ' . $label);
}

htp_admin_1170_assert(strpos($overview, "public_display_from") !== false, 'la date d’activation automatique n’est pas affichée');
htp_admin_1170_assert(strpos($overview, "public_display_until") !== false, 'la date de désactivation automatique n’est pas affichée');
htp_admin_1170_assert(strpos($overview, "Parcs_HT_Public_Visibility::scheduled_state") !== false, 'l’état automatique annuel n’est pas utilisé');
htp_admin_1170_assert(strpos($overview, "année activée automatiquement") !== false, 'l’état automatique actif n’est pas expliqué');
htp_admin_1170_assert(strpos($overview, "année désactivée automatiquement") !== false, 'l’état automatique inactif n’est pas expliqué');
htp_admin_1170_assert(strpos($overview, "interrupteurs manuels") !== false, 'le mode manuel n’est pas expliqué');

preg_match('/define\(\'PARCS_HT_VERSION\',\s*\'([^\']+)\'\)/', $main, $version_match);
$version = isset($version_match[1]) ? $version_match[1] : '0';
if (version_compare($version, '1.17.2', '>=')) {
    htp_admin_1170_assert(is_string($navigation) && $navigation !== '', 'navigation 1.17.2 absente');
    htp_admin_1170_assert(strpos($main, 'class-parcs-ht-admin-navigation.php') !== false, 'navigation 1.17.2 non chargée');
    htp_admin_1170_assert(strpos($main, 'Parcs_HT_Admin_Navigation::init();') !== false, 'navigation 1.17.2 non initialisée');
    foreach (array('parcs-ht-groups','parcs-ht-communication','parcs-ht-updates','parcs-ht-schedule','parcs-ht-tariffs') as $slug) {
        htp_admin_1170_assert(strpos($navigation, $slug) !== false, 'sous-menu 1.17.2 manquant : ' . $slug);
    }
    foreach (array('Tarifs groupes','Devis groupes','Guides pédagogiques') as $label) {
        htp_admin_1170_assert(strpos($navigation, $label) !== false, 'rubrique Groupes incomplète : ' . $label);
    }
    foreach (array('Communication','Pop-up','Calendrier de l’Avent') as $label) {
        htp_admin_1170_assert(strpos($navigation, $label) !== false, 'rubrique Communication incomplète : ' . $label);
    }
    htp_admin_1170_assert(strpos($navigation, "self::legacy_url('htp-advent', '')") !== false, 'le Calendrier de l’Avent ne doit pas hériter artificiellement du contexte annuel');
    htp_admin_1170_assert(strpos($overview, 'parcs_ht_save_general_publication') !== false, 'les activations du tableau de bord ne réutilisent pas l’action canonique');
    foreach (array('calendar_visible','retail_tariffs_visible','groups_schedule_visible','group_quotes_enabled','group_tariffs_visible') as $key) {
        htp_admin_1170_assert(strpos($overview, $key) !== false, 'activation annuelle absente du tableau de bord : ' . $key);
    }
    htp_admin_1170_assert(strpos($overview, 'Parcs_HT_Updater::latest_version') !== false, 'état des mises à jour absent du tableau de bord');
    htp_admin_1170_assert(strpos($overview, 'parcs_ht_check_updates_1172') !== false, 'vérification forcée absente du tableau de bord');
    htp_admin_1170_assert(strpos($navigation, '.htp-global-field>span:first-child') !== false, 'les libellés de l’apparence globale ne sont pas explicitement protégés');
}

fwrite(STDOUT, "OK admin-overview-1170-contract\n");

// Les contrats 1.17.1 restent chaînés afin de garantir la compatibilité des
// versions suivantes dans les deux passes CI (sources et paquet de production).
require __DIR__ . '/admin-general-1171-contract.php';
require __DIR__ . '/global-appearance-1171-contract.php';
