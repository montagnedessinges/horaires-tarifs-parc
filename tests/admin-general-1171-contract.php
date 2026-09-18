<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$general_path = $root . '/includes/class-parcs-ht-admin-general.php';
$appearance_path = $root . '/includes/class-parcs-ht-global-appearance.php';
$stability_path = $root . '/includes/class-parcs-ht-stability-11511.php';

function htp_admin_1171_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$general = file_get_contents($general_path);
$appearance = file_get_contents($appearance_path);
$stability = file_get_contents($stability_path);
htp_admin_1171_assert(is_string($general) && $general !== '', 'classe Administration générale absente');
htp_admin_1171_assert(is_string($appearance) && $appearance !== '', 'référentiel d’apparence globale absent');
htp_admin_1171_assert(is_string($stability) && $stability !== '', 'fichier de stabilisation absent');

htp_admin_1171_assert(strpos($stability, "class-parcs-ht-global-appearance.php") !== false, 'le référentiel global n’est pas chargé');
htp_admin_1171_assert(strpos($stability, "Parcs_HT_Global_Appearance::init();") !== false, 'le référentiel global n’est pas initialisé');
htp_admin_1171_assert(strpos($stability, "class-parcs-ht-admin-general.php") !== false, 'Administration générale n’est pas chargée');
htp_admin_1171_assert(strpos($stability, "Parcs_HT_Admin_General::init();") !== false, 'Administration générale n’est pas initialisée');
htp_admin_1171_assert(strpos($general, "add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 10)") !== false, 'Administration générale ne prend pas la priorité sur l’ancienne vue d’ensemble');
htp_admin_1171_assert(strpos($general, "admin_post_parcs_ht_save_global_appearance") !== false, 'la sauvegarde dédiée de l’apparence globale est absente');
htp_admin_1171_assert(strpos($general, "check_admin_referer('parcs_ht_save_global_appearance')") !== false, 'la sauvegarde globale n’est pas protégée par nonce');
htp_admin_1171_assert(strpos($general, "Parcs_HT_Global_Appearance::merge_general") !== false, 'Administration générale ne sauvegarde pas via le référentiel commun');

foreach (array(
    'Administration générale', 'Parc', 'Saisons', 'Publication de l’année sélectionnée', 'Apparence globale',
    'Calendrier public', 'Tarifs visiteurs', 'Horaires groupes', 'Devis groupes', 'Tarifs groupes',
    'Activation automatique', 'Désactivation automatique', 'Utiliser l’apparence globale', 'Personnaliser ce module',
    'Boutons', 'Cartes et blocs', 'Onglets et petits badges', 'Aperçu du socle global'
) as $label) {
    htp_admin_1171_assert(strpos($general, $label) !== false, 'élément manquant dans Administration générale : ' . $label);
}

htp_admin_1171_assert(strpos($general, "Parcs_HT_Public_Visibility::scheduled_state") !== false, 'la logique annuelle automatique existante n’est pas réutilisée');
htp_admin_1171_assert(strpos($general, "public_display_from") !== false, 'la date d’activation automatique n’est pas affichée');
htp_admin_1171_assert(strpos($general, "public_display_until") !== false, 'la date de désactivation automatique n’est pas affichée');
htp_admin_1171_assert(strpos($general, "primary_color") !== false, 'la couleur principale existante n’est pas réutilisée');
htp_admin_1171_assert(strpos($general, "button_primary_bg_color") !== false, 'le socle bouton principal est absent');
htp_admin_1171_assert(strpos($general, "card_bg_color") !== false, 'le socle cartes est absent');
htp_admin_1171_assert(strpos($general, "tab_active_bg_color") !== false, 'le socle onglets est absent');

// Aucune donnée n’est supprimée par la nouvelle administration.
htp_admin_1171_assert(strpos($general, 'delete_option(') === false, 'Administration générale ne doit supprimer aucune donnée');

fwrite(STDOUT, "OK admin-general-1171-contract\n");
