<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$general_path = $root . '/includes/class-parcs-ht-admin-general.php';
$park_path = $root . '/includes/class-parcs-ht-admin-general-park.php';
$appearance_admin_path = $root . '/includes/class-parcs-ht-global-appearance-admin.php';
$appearance_path = $root . '/includes/class-parcs-ht-global-appearance.php';
$stability_path = $root . '/includes/class-parcs-ht-stability-11511.php';

function htp_admin_1171_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$general = file_get_contents($general_path);
$park = file_get_contents($park_path);
$appearance_admin = file_get_contents($appearance_admin_path);
$appearance = file_get_contents($appearance_path);
$stability = file_get_contents($stability_path);
htp_admin_1171_assert(is_string($general) && $general !== '', 'classe Administration générale absente');
htp_admin_1171_assert(is_string($park) && $park !== '', 'bloc Parc absent');
htp_admin_1171_assert(is_string($appearance_admin) && $appearance_admin !== '', 'éditeur d’apparence globale absent');
htp_admin_1171_assert(is_string($appearance) && $appearance !== '', 'référentiel d’apparence globale absent');
htp_admin_1171_assert(is_string($stability) && $stability !== '', 'fichier de stabilisation absent');

foreach (array(
    "class-parcs-ht-global-appearance.php",
    "Parcs_HT_Global_Appearance::init();",
    "class-parcs-ht-admin-general.php",
    "Parcs_HT_Admin_General::init();",
    "class-parcs-ht-admin-general-park.php",
    "Parcs_HT_Admin_General_Park::init();",
    "class-parcs-ht-global-appearance-admin.php",
    "Parcs_HT_Global_Appearance_Admin::init();",
) as $needle) {
    htp_admin_1171_assert(strpos($stability, $needle) !== false, 'bootstrap 1.17.1 incomplet : ' . $needle);
}

htp_admin_1171_assert(strpos($general, "add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 10)") !== false, 'Administration générale ne prend pas la priorité sur l’ancienne vue d’ensemble');
htp_admin_1171_assert(strpos($general, "isset(\$_GET['tab'])") !== false, 'les URL détaillées avec onglet ne sont pas protégées de la redirection');
htp_admin_1171_assert(strpos($general, "Parcs_HT_Admin_General_Park::render") !== false, 'le bloc Parc n’est pas rendu dans Administration générale');
htp_admin_1171_assert(strpos($general, "admin_post_parcs_ht_save_general_publication") !== false, 'la sauvegarde annuelle dédiée est absente');
htp_admin_1171_assert(strpos($general, "check_admin_referer('parcs_ht_save_general_publication')") !== false, 'la sauvegarde annuelle n’est pas protégée par nonce');
htp_admin_1171_assert(strpos($general, "'published'] = \$all['seasons'][\$year]['calendar_visible']") !== false, 'le statut historique published ne suit pas le calendrier public');
htp_admin_1171_assert(strpos($general, "public_display_from") !== false, 'la date d’activation automatique n’est pas gérée');
htp_admin_1171_assert(strpos($general, "public_display_until") !== false, 'la date de désactivation automatique n’est pas gérée');
htp_admin_1171_assert(strpos($general, "Parcs_HT_Public_Visibility::scheduled_state") !== false, 'la logique annuelle automatique existante n’est pas réutilisée');

foreach (array('parcs_ht_add_season','parcs_ht_duplicate_season','parcs_ht_delete_season','Préparer l’année suivante','Ajouter une saison') as $needle) {
    htp_admin_1171_assert(strpos($general, $needle) !== false, 'gestion des saisons incomplète : ' . $needle);
}

foreach (array(
    'Administration générale', 'Saisons', 'Publication de l’année sélectionnée',
    'Afficher le calendrier public', 'Afficher les tarifs visiteurs', 'Afficher les horaires aux groupes',
    'Activer les devis groupes', 'Afficher les tarifs groupes sur le site',
    'Activer automatiquement toute l’année à partir du', 'Désactiver automatiquement toute l’année à partir du',
) as $label) {
    htp_admin_1171_assert(strpos($general, $label) !== false, 'élément manquant dans Administration générale : ' . $label);
}

htp_admin_1171_assert(strpos($park, "admin_post_parcs_ht_save_general_park") !== false, 'la sauvegarde du bloc Parc est absente');
htp_admin_1171_assert(strpos($park, "check_admin_referer('parcs_ht_save_general_park')") !== false, 'la sauvegarde Parc n’est pas protégée par nonce');
foreach (array('Nom du parc','Fuseau horaire','Lien billetterie','Lien groupes','E-mail groupes') as $label) {
    htp_admin_1171_assert(strpos($park, $label) !== false, 'réglage global du parc manquant : ' . $label);
}

htp_admin_1171_assert(strpos($appearance_admin, "admin_post_parcs_ht_save_global_appearance") !== false, 'la sauvegarde dédiée de l’apparence globale est absente');
htp_admin_1171_assert(strpos($appearance_admin, "check_admin_referer('parcs_ht_save_global_appearance')") !== false, 'la sauvegarde globale n’est pas protégée par nonce');
htp_admin_1171_assert(strpos($appearance_admin, "Parcs_HT_Global_Appearance::merge_general") !== false, 'l’éditeur ne sauvegarde pas via le référentiel commun');
foreach (array('Apparence globale','Utiliser l’apparence globale','Personnaliser ce module','Boutons','Cartes et blocs','Onglets et petits badges','Aperçu du socle global') as $label) {
    htp_admin_1171_assert(strpos($appearance_admin, $label) !== false, 'élément d’apparence manquant : ' . $label);
}

foreach (array($general, $park, $appearance_admin) as $source) {
    htp_admin_1171_assert(strpos($source, 'delete_option(') === false, 'Administration générale ne doit supprimer aucune option');
}

fwrite(STDOUT, "OK admin-general-1171-contract\n");
