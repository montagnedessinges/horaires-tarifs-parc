<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$overview_path = $root . '/includes/class-parcs-ht-admin-overview.php';
$main_path = $root . '/horaires-tarifs-parc.php';

function htp_admin_1170_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$overview = file_get_contents($overview_path);
$main = file_get_contents($main_path);
htp_admin_1170_assert(is_string($overview) && $overview !== '', 'classe Vue d’ensemble absente');
htp_admin_1170_assert(is_string($main) && $main !== '', 'fichier principal absent');

htp_admin_1170_assert(strpos($main, "class-parcs-ht-admin-overview.php") !== false, 'la Vue d’ensemble n’est pas chargée');
htp_admin_1170_assert(strpos($main, "Parcs_HT_Admin_Overview::init();") !== false, 'la Vue d’ensemble n’est pas initialisée');
htp_admin_1170_assert(strpos($overview, "redirect_default_entry") !== false, 'le menu principal n’ouvre pas l’entrée simplifiée');
htp_admin_1170_assert(strpos($overview, "isset(\$_GET['tab'])") !== false, 'la redirection ne protège pas les onglets détaillés');
htp_admin_1170_assert(strpos($overview, "isset(\$_GET['season'])") !== false, 'la redirection ne protège pas la navigation annuelle détaillée');
htp_admin_1170_assert(strpos($overview, "Retour à la vue d’ensemble") !== false, 'le retour vers l’interface simple est absent des écrans détaillés');

foreach (array(
    'Année & publication',
    'Horaires & calendrier',
    'Périodes & événements',
    'Exceptions',
    'Accès limité',
    'Tarifs visiteurs',
    'Tarifs groupes',
    'Devis groupes',
    'Guides pédagogiques',
    'Contenus & traductions',
    'Pop-up',
    'Calendrier de l’Avent',
    'Aperçu',
    'Shortcodes',
    'Mises à jour',
) as $label) {
    htp_admin_1170_assert(strpos($overview, $label) !== false, 'carte manquante dans la Vue d’ensemble : ' . $label);
}

htp_admin_1170_assert(strpos($overview, "public_display_from") !== false, 'la date d’activation automatique n’est pas affichée');
htp_admin_1170_assert(strpos($overview, "public_display_until") !== false, 'la date de désactivation automatique n’est pas affichée');
htp_admin_1170_assert(strpos($overview, "Parcs_HT_Public_Visibility::scheduled_state") !== false, 'l’état automatique annuel n’est pas utilisé');
htp_admin_1170_assert(strpos($overview, "année activée automatiquement") !== false, 'l’état automatique actif n’est pas expliqué');
htp_admin_1170_assert(strpos($overview, "année désactivée automatiquement") !== false, 'l’état automatique inactif n’est pas expliqué');
htp_admin_1170_assert(strpos($overview, "interrupteurs manuels") !== false, 'le mode manuel n’est pas expliqué');

fwrite(STDOUT, "OK admin-overview-1170-contract\n");
