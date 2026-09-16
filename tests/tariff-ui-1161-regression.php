<?php
$root = dirname(__DIR__);
$display = file_get_contents($root . '/includes/class-parcs-ht-tariff-display.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$css = file_get_contents($root . '/assets/tariffs-ui.css');

$checks = array(
    'Le shortcode complet conserve le vrai calendrier' => strpos($display, "Parcs_HT_Shortcodes::render('calendar'") !== false,
    'Le titre tarifs n’est plus un heading sémantique' => strpos($display, 'role=\\"heading\\"') === false && strpos($display, "role=\"heading\"") === false,
    'La normalisation publique protège les cellules online vides' => strpos($display, 'normalize_for_display') !== false && strpos($display, 'explicitement vide reste vide') !== false,
    'Le portail groupes réutilise le calendrier commun' => strpos($portal, "Parcs_HT_Shortcodes::render('calendar'") !== false,
    'Le portail groupes ne recrée plus un tableau horaires' => strpos($portal, 'group-schedule-table') === false && strpos($portal, 'schedule_year(') === false,
    'Les années groupes sont synchronisées' => strpos($portal, 'data-group-year=') !== false && strpos($portal, 'syncCalendar') !== false,
    'La visibilité utilise une fenêtre début/fin commune' => strpos($visibility, 'public_display_from') !== false && strpos($visibility, 'public_display_until') !== false,
    'L’année courante est prioritaire' => strpos($visibility, 'current_year') !== false && strpos($visibility, 'default_year') !== false,
    'Le mobile conserve une taille de texte lisible' => strpos($css, 'font-size:14px') !== false && strpos($css, 'min-height:44px') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    if (!$ok) $failed[] = $label;
}
if ($failed) {
    fwrite(STDERR, "Échecs correctif tarifs/groupes 1.16.1 :\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Correctif tarifs/groupes 1.16.1 : OK\n";
