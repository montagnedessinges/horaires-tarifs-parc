<?php

$root = getenv('PLUGIN_ROOT');
if (!is_string($root) || $root === '') $root = dirname(__DIR__);

function verify_1168_groups($condition, $message) {
    if (!$condition) { fwrite(STDERR, "[FAIL] {$message}\n"); exit(1); }
    echo "[OK] {$message}\n";
}

$shared = file_get_contents($root . '/includes/class-parcs-ht-tariff-shared-1168.php');
$bootstrap = file_get_contents($root . '/horaires-tarifs-parc.php');
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');
$fixes = file_get_contents($root . '/includes/class-parcs-ht-tariff-public-fixes.php');

verify_1168_groups(is_string($shared) && $shared !== '', 'Le module 1.16.8 du tableau Groupes partagé existe');
verify_1168_groups(strpos($bootstrap, "Version: 1.16.8") !== false && strpos($bootstrap, "PARCS_HT_VERSION', '1.16.8") !== false, 'La version 1.16.8 est cohérente dans le bootstrap');
verify_1168_groups(strpos($bootstrap, "class-parcs-ht-tariff-shared-1168.php") !== false, 'Le module 1.16.8 est chargé');
verify_1168_groups(strpos($bootstrap, 'Parcs_HT_Tariff_Public_Fixes::init();') < strpos($bootstrap, 'Parcs_HT_Tariff_Shared_1168::init();'), 'Le renderer partagé est enregistré après les correctifs publics existants');

verify_1168_groups(strpos($shared, 'Parcs_HT_Public_Visibility::tariff_years()') !== false, 'Le sélecteur public conserve l’union des années visiteurs et groupes');
verify_1168_groups(strpos($shared, 'Parcs_HT_Public_Visibility::retail_years()') !== false && strpos($shared, 'Parcs_HT_Public_Visibility::group_tariff_years()') !== false, 'La visibilité visiteurs et la visibilité groupes restent indépendantes');
verify_1168_groups(strpos($visibility, 'array_merge(self::retail_years(), self::group_tariff_years())') !== false, 'Une année uniquement Groupes peut rester présente dans le sélecteur public');

verify_1168_groups(strpos($shared, "fixes_call('render_group_year'") !== false, 'Le tableau public réutilise le renderer canonique des tarifs groupes');
verify_1168_groups(substr_count($shared, "fixes_call('render_group_year'") === 1, 'Le module ne recrée pas un second renderer de prix groupes');
verify_1168_groups(strpos($fixes, 'group_payment_strip') !== false && strpos($fixes, 'group_info') !== false, 'Le renderer canonique conserve moyens de paiement et informations Groupes');
verify_1168_groups(strpos($shared, "elseif (\$retail_visible)") !== false && strpos($shared, 'self::render_group_year($language, $year)') !== false, 'Quand visiteurs et groupes sont publiés, le vrai tableau Groupes partagé est affiché');
verify_1168_groups(strpos($shared, 'self::group_redirect($language, $year, $general)') !== false, 'Une année Groupes sans tarifs visiteurs utilise le renvoi dédié');
verify_1168_groups(strpos($shared, 'Les tarifs individuels et réduits ') !== false, 'Le renvoi précise que les tarifs visiteurs ne sont pas encore disponibles');
verify_1168_groups(strpos($shared, 'Voir les tarifs et horaires groupes') !== false && strpos($shared, 'View group rates and opening hours') !== false && strpos($shared, 'Gruppentarife und Öffnungszeiten ansehen') !== false, 'Le bouton de renvoi est traduit FR / EN / DE');
verify_1168_groups(strpos($shared, "has_shortcode") !== false && strpos($shared, "parc_groupes_horaires_tarifs") !== false, 'Le lien vers l’espace Groupes est détecté sans slug codé en dur');
verify_1168_groups(strpos($shared, "\$general['groups_url']") !== false, 'Le lien Groupes historique reste un repli sûr');

verify_1168_groups(strpos($shared, "if (\$retail_visible) \$html .= self::display_call('export_actions'") !== false, 'Impression et PDF visiteurs ne sont pas exposés pour une année uniquement Groupes');
verify_1168_groups(strpos($shared, "visitor_payment_strip(\$tariffs, \$language, 'groups'") === false, 'Aucun moyen de paiement visiteurs n’est injecté dans Groupes');
verify_1168_groups(strpos($shared, "Acheter vos billets") !== false && substr_count($shared, "Acheter vos billets") === 1, 'Le bouton Acheter vos billets reste limité à la branche Individuels');

verify_1168_groups(strpos($shared, 'parc_calendrier') === false && strpos($shared, 'schedule_payload') === false, 'La 1.16.8 ne crée aucun second moteur de calendrier');
verify_1168_groups(strpos($composer, "self::child('parc_calendrier', \$language)") !== false, 'Le portail Groupes continue de réutiliser le calendrier canonique');
verify_1168_groups(strpos($shared, 'Group_Quotes') === false && strpos($shared, 'quote_binding') === false, 'Le moteur de devis n’est pas modifié par le nouveau rendu public');
verify_1168_groups(strpos($shared, '2026') === false && strpos($shared, '2027') === false && strpos($shared, '2028') === false, 'Aucune année métier n’est codée en dur dans la logique 1.16.8');

echo "Public tariff shared groups 1.16.8 contract: OK\n";
