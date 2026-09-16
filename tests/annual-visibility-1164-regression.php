<?php
$root = dirname(__DIR__);
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');
$adminJs = file_get_contents($root . '/assets/public-visibility-admin.js');
$groupJs = file_get_contents($root . '/assets/group-portal-sync.js');

$checks = array(
    'La date de disparition coupe l’année dès le jour indiqué' => strpos($visibility, "$today >= $until") !== false,
    'La date d’apparition active l’année dès le jour indiqué' => strpos($visibility, "$today >= $from") !== false,
    'Les dates maîtresses pilotent le calendrier' => strpos($visibility, "module_visible($year, 'calendar_visible'") !== false,
    'Les dates maîtresses pilotent les tarifs visiteurs' => strpos($visibility, "module_visible($year, 'retail_tariffs_visible'") !== false,
    'Les dates maîtresses pilotent les horaires groupes' => strpos($visibility, "module_visible($year, 'groups_schedule_visible'") !== false,
    'Les dates maîtresses pilotent les tarifs groupes' => strpos($visibility, "module_visible($year, 'group_tariffs_visible'") !== false,
    'Les dates maîtresses pilotent les devis groupes' => strpos($visibility, "filter_quote_state") !== false && strpos($visibility, "group_quotes_enabled") !== false,
    'Le portail groupes utilise l’union des années horaires et tarifs' => strpos($composer, 'group_portal_years') !== false,
    'Le portail groupes réutilise le calendrier historique' => strpos($composer, "self::child('parc_calendrier'") !== false,
    'Les horaires groupes ont leur propre jeu de saisons' => strpos($composer, 'group_schedule_payload') !== false && strpos($composer, 'settings.seasons=') !== false,
    'Le sélecteur annuel groupes est commun' => strpos($composer, 'data-group-year=') !== false && strpos($composer, 'data-group-selected-year') !== false,
    'La synchronisation années tarifs/calendrier existe' => strpos($groupJs, 'selectTariffs') !== false && strpos($groupJs, 'selectCalendar') !== false,
    'Les deux dates automatiques sont visibles dans Activation de l’année' => strpos($adminJs, 'Activer automatiquement toute l’année à partir du') !== false && strpos($adminJs, 'Désactiver automatiquement toute l’année à partir du') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) if (!$ok) $failed[] = $label;
if ($failed) {
    fwrite(STDERR, "Échecs bascule annuelle 1.16.4 :\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Bascule annuelle et portail groupes 1.16.4 : OK\n";
