<?php

define('ABSPATH', '/');
function remove_accents($value) { return strtr((string)$value, array('é'=>'e','è'=>'e','ê'=>'e','à'=>'a','ù'=>'u')); }
function verify_1167($condition, $message) {
    if (!$condition) { fwrite(STDERR, "[FAIL] {$message}\n"); exit(1); }
    echo "[OK] {$message}\n";
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require $root . '/includes/class-parcs-ht-payment-channels.php';

$both = Parcs_HT_Payment_Channels::channels_for_item(array('channels'=>array('onsite'=>'1','online'=>'1')));
verify_1167($both['onsite'] && $both['online'], 'Un moyen peut être activé Sur place et En ligne simultanément');
$onsite = Parcs_HT_Payment_Channels::channels_for_item(array('channels'=>array('onsite'=>'1','online'=>'0')));
verify_1167($onsite['onsite'] && !$onsite['online'], 'Un moyen peut rester exclusivement Sur place');
$legacy_card = Parcs_HT_Payment_Channels::channels_for_item(array('icon'=>'card','label'=>array('fr'=>'Carte bancaire')));
verify_1167($legacy_card['onsite'] && $legacy_card['online'], 'Une carte bancaire historique conserve un canal en ligne compatible');
$legacy_cash = Parcs_HT_Payment_Channels::channels_for_item(array('icon'=>'cash','label'=>array('fr'=>'Espèces')));
verify_1167($legacy_cash['onsite'] && !$legacy_cash['online'], 'Les espèces historiques restent uniquement Sur place');
$legacy_connect = Parcs_HT_Payment_Channels::channels_for_item(array('icon'=>'custom','label'=>array('fr'=>'Chèques-vacances Connect')));
verify_1167($legacy_connect['onsite'] && !$legacy_connect['online'], 'Chèques-vacances Connect reste Sur place tant que le réglage En ligne n’est pas activé');

$fixes = file_get_contents($root . '/includes/class-parcs-ht-tariff-public-fixes.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$admin_js = file_get_contents($root . '/assets/payment-channels-admin.js');
$plugin = file_get_contents($root . '/horaires-tarifs-parc.php');

verify_1167(strpos($fixes, 'Parcs_HT_Public_Visibility::tariff_years()') !== false, 'Le sélecteur public conserve l’union des années visiteurs et groupes');
verify_1167(strpos($fixes, 'group_redirect($language, $year, $general)') !== false, 'Une année groupes sans tarifs visiteurs utilise le renvoi vers l’espace Groupes');
verify_1167(strpos($fixes, 'render_group_body($language, $year)') !== false, 'Le tableau public réutilise le corps canonique des tarifs groupes');
verify_1167(strpos($portal, 'Parcs_HT_Tariff_Public_Fixes::render_group_year($language, $year)') !== false, 'Le portail Groupes réutilise le même renderer groupes');
verify_1167(strpos($fixes, "if (\$key === 'individual')") !== false && strpos($fixes, 'Acheter vos billets') !== false, 'Le bouton d’achat est rattaché au panneau Individuels');
verify_1167(strpos($fixes, "visitor_payment_strip(\$tariffs, \$language, 'reduced'") !== false, 'Les tarifs réduits disposent de leur paiement de catégorie');
verify_1167(strpos($fixes, "\$category === 'individual'") !== false, 'Le canal En ligne des paiements visiteurs est réservé à Individuels');
verify_1167(strpos($fixes, 'Moyens de paiement') !== false && strpos($fixes, "' — '") !== false, 'Le titre des moyens de paiement répète la catégorie');
verify_1167(strpos($admin_js, '[channels][onsite]') !== false && strpos($admin_js, '[channels][online]') !== false, 'L’administration expose deux cases indépendantes Sur place / En ligne');
verify_1167(strpos($plugin, 'Version: 1.16.7') !== false && strpos($plugin, "PARCS_HT_VERSION', '1.16.7") !== false, 'La version 1.16.7 est cohérente dans le bootstrap');

echo "Public tariff 1.16.7 contract: OK\n";
