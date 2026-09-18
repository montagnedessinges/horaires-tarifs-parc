<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$general_path = $root . '/includes/class-parcs-ht-admin-general.php';
$stability_path = $root . '/includes/class-parcs-ht-stability-11511.php';

function htp_admin_1171_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$general = file_get_contents($general_path);
$stability = file_get_contents($stability_path);
htp_admin_1171_assert(is_string($general) && $general !== '', 'classe Administration générale absente');
htp_admin_1171_assert(is_string($stability) && $stability !== '', 'fichier de stabilisation absent');

htp_admin_1171_assert(strpos($stability, "class-parcs-ht-admin-general.php") !== false, 'Administration générale n’est pas chargée');
htp_admin_1171_assert(strpos($stability, "Parcs_HT_Admin_General::init();") !== false, 'Administration générale n’est pas initialisée');
htp_admin_1171_assert(strpos($general, "add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 10)") !== false, 'Administration générale ne prend pas la priorité sur l’ancienne vue d’ensemble');
htp_admin_1171_assert(strpos($general, "isset(\$_GET['tab'])") !== false, 'les liens directs vers les onglets détaillés ne sont pas protégés');
htp_admin_1171_assert(strpos($general, "isset(\$_GET['season'])") !== false, 'les liens directs vers une saison détaillée ne sont pas protégés');

foreach (array(
    'Administration générale',
    'Parc',
    'Saisons',
    'Publication de l’année sélectionnée',
    'Apparence globale',
    'Calendrier public',
    'Tarifs visiteurs',
    'Horaires groupes',
    'Devis groupes',
    'Tarifs groupes',
    'Activation automatique',
    'Désactivation automatique',
) as $label) {
    htp_admin_1171_assert(strpos($general, $label) !== false, 'élément manquant dans Administration générale : ' . $label);
}

htp_admin_1171_assert(strpos($general, "Parcs_HT_Public_Visibility::scheduled_state") !== false, 'la logique annuelle automatique existante n’est pas réutilisée');
htp_admin_1171_assert(strpos($general, "public_display_from") !== false, 'la date d’activation automatique n’est pas affichée');
htp_admin_1171_assert(strpos($general, "public_display_until") !== false, 'la date de désactivation automatique n’est pas affichée');
htp_admin_1171_assert(strpos($general, "primary_color") !== false, 'la couleur principale existante n’est pas réutilisée');
htp_admin_1171_assert(strpos($general, "secondary_color") !== false, 'la couleur secondaire existante n’est pas réutilisée');
htp_admin_1171_assert(strpos($general, "highlight_color") !== false, 'la mise en valeur existante n’est pas réutilisée');

// Première étape volontairement en lecture : aucune migration ou écriture parallèle.
htp_admin_1171_assert(strpos($general, 'update_option(') === false, 'Administration générale ne doit pas créer un second moteur d’enregistrement');
htp_admin_1171_assert(strpos($general, 'delete_option(') === false, 'Administration générale ne doit supprimer aucune donnée');

fwrite(STDOUT, "OK admin-general-1171-contract\n");
