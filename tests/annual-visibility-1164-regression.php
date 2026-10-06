<?php
$root = dirname(__DIR__);
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$adminJs = file_get_contents($root . '/assets/public-visibility-admin.js');

$checks = array(
    'La date de disparition coupe l’année dès le jour indiqué' => strpos($visibility, '$today >= $until') !== false,
    'La date d’apparition active l’année dès le jour indiqué' => strpos($visibility, '$today >= $from') !== false,
    'Les dates maîtresses pilotent le calendrier' => strpos($visibility, "'calendar_visible'") !== false && strpos($visibility, 'calendar_years') !== false,
    'Les dates maîtresses pilotent les tarifs visiteurs' => strpos($visibility, "'retail_tariffs_visible'") !== false && strpos($visibility, 'retail_years') !== false,
    'Les dates maîtresses pilotent les horaires groupes' => strpos($visibility, "'groups_schedule_visible'") !== false && strpos($visibility, 'group_schedule_years') !== false,
    'Les dates maîtresses pilotent les tarifs groupes' => strpos($visibility, "'group_tariffs_visible'") !== false && strpos($visibility, 'group_tariff_years') !== false,
    'Les dates maîtresses pilotent les devis groupes' => strpos($visibility, 'filter_quote_state') !== false && strpos($visibility, "'group_quotes_enabled'") !== false,
    'Le portail groupes lit les années horaires et tarifs depuis la politique centrale' => strpos($portal, 'Parcs_HT_Public_Visibility::group_schedule_years()') !== false && strpos($portal, 'Parcs_HT_Public_Visibility::group_tariff_years()') !== false,
    'Le portail groupes réutilise le calendrier historique' => strpos($portal, "do_shortcode('[' . $tag . ']')") !== false,
    'Les horaires groupes injectent uniquement leur projection publique' => strpos($portal, 'schedule_payload') !== false && strpos($portal, 'public_row') !== false && strpos($portal, 'Object.assign(window.ParcsHTPData.settings.seasons') !== false,
    'Le calendrier sémantique est limité aux années horaires groupes' => strpos($portal, "add_filter('parcs_ht_calendar_semantic_years'") !== false,
    'Le sélecteur annuel groupes possède une URL de repli serveur' => strpos($portal, 'htp_group_year') !== false && strpos($portal, 'data-group-year=') !== false,
    'L’assembleur historique groupes délègue au portail canonique' => strpos($composer, 'Parcs_HT_Group_Portal::render') !== false && strpos($composer, "add_shortcode('parc_groupes_horaires_tarifs'") === false,
    'Les deux dates automatiques sont visibles dans Activation de l’année' => strpos($adminJs, 'Activer automatiquement toute l’année à partir du') !== false && strpos($adminJs, 'Désactiver automatiquement toute l’année à partir du') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) if (!$ok) $failed[] = $label;
if ($failed) {
    fwrite(STDERR, "Échecs bascule annuelle 1.16.4 :\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Bascule annuelle et portail groupes 1.16.4 : OK\n";
