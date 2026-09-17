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
verify_1167($legacy_card['onsite'] && $legacy_card['online'], 'Une carte bancaire historique reste compatible Sur place et En ligne');

$legacy_cash = Parcs_HT_Payment_Channels::channels_for_item(array('icon'=>'cash','label'=>array('fr'=>'Espèces')));
verify_1167($legacy_cash['onsite'] && !$legacy_cash['online'], 'Les espèces historiques restent Sur place uniquement');

$legacy_paper = Parcs_HT_Payment_Channels::channels_for_item(array('icon'=>'custom','label'=>array('fr'=>'Chèques-Vacances papier')));
verify_1167($legacy_paper['onsite'] && !$legacy_paper['online'], 'Les Chèques-Vacances papier historiques restent Sur place uniquement');

$legacy_connect = Parcs_HT_Payment_Channels::channels_for_item(array('icon'=>'custom','label'=>array('fr'=>'Chèques-Vacances Connect')));
verify_1167($legacy_connect['onsite'] && !$legacy_connect['online'], 'Chèques-Vacances Connect reste Sur place tant que En ligne n’est pas activé');

$fixes = file_get_contents($root . '/includes/class-parcs-ht-tariff-public-fixes.php');
$admin_js = file_get_contents($root . '/assets/payment-channels-admin.js');
$plugin = file_get_contents($root . '/horaires-tarifs-parc.php');

verify_1167(strpos($fixes, "visitor_payment_strip(\$tariffs, \$language, 'individual'") !== false, 'Individuels possède son bloc de moyens de paiement');
verify_1167(strpos($fixes, "visitor_payment_strip(\$tariffs, \$language, 'reduced'") !== false, 'Tarifs réduits possède son bloc de moyens de paiement');
verify_1167(strpos($fixes, "\$category === 'individual'") !== false, 'Le canal En ligne des moyens de paiement est réservé à Individuels');
verify_1167(strpos($fixes, "if (\$key === 'individual')") !== false && strpos($fixes, 'Acheter vos billets') !== false, 'Le bouton Acheter vos billets est rendu dans Individuels');
verify_1167(strpos($fixes, "price_table', array(\$tariffs, 'reduced', \$language, array())") !== false, 'Tarifs réduits n’utilise aucun lien de billetterie visiteurs');
verify_1167(strpos($fixes, "price_table', array(\$tariffs, 'groups', \$language, array())") !== false, 'Groupes n’utilise aucun lien de billetterie visiteurs');
verify_1167(strpos($fixes, "'Moyens de paiement', 'Payment methods', 'Zahlungsmöglichkeiten'") !== false && strpos($fixes, "' — ' . \$category_label") !== false, 'Le titre des moyens de paiement rappelle la catégorie');
verify_1167(strpos($admin_js, "base+'[onsite]'") !== false && strpos($admin_js, "base+'[online]'") !== false, 'L’administration expose deux cases indépendantes Sur place / En ligne');
verify_1167(strpos($plugin, "class-parcs-ht-payment-channels.php") !== false && strpos($plugin, 'Parcs_HT_Payment_Channels::init();') !== false, 'Le module de canaux visiteurs est chargé et initialisé');
verify_1167(strpos($plugin, 'Version: 1.16.7') !== false && strpos($plugin, "PARCS_HT_VERSION', '1.16.7") !== false, 'La version 1.16.7 est cohérente dans le bootstrap');

echo "Public tariff payment channels 1.16.7 contract: OK\n";
