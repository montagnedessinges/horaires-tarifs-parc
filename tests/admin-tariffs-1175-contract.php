<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);

function htp_1175_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$retail = file_get_contents($root . '/includes/class-parcs-ht-admin-retail-tariffs.php');
$payments = file_get_contents($root . '/includes/class-parcs-ht-payment-channels.php');
$public = file_get_contents($root . '/includes/class-parcs-ht-tariff-display.php');
$css = file_get_contents($root . '/assets/admin-tariffs-1175.css');

preg_match('/Version:\s*([0-9.]+)/', (string)$main, $version_match);
$version = $version_match[1] ?? '0.0.0';
htp_1175_assert(version_compare($version, '1.17.5', '>='), 'version 1.17.5 ou suivante absente');
htp_1175_assert(strpos($main, "class-parcs-ht-admin-retail-tariffs.php") !== false, 'écran Tarifs visiteurs non chargé');
htp_1175_assert(strpos($main, 'Parcs_HT_Admin_Retail_Tariffs::init();') !== false, 'écran Tarifs visiteurs non initialisé');

htp_1175_assert(is_string($retail) && strpos($retail, "const PAGE = 'parcs-ht-tariffs';") !== false, 'slug Tarifs visiteurs dédié absent');
htp_1175_assert(strpos($retail, "'Individuels'") !== false && strpos($retail, "'Tarifs réduits'") !== false, 'catégories visiteurs absentes');
htp_1175_assert(strpos($retail, "self::category('groups'") === false, 'l’écran visiteurs rend encore la catégorie Groupes');
htp_1175_assert(strpos($retail, 'render_year_context') !== false, 'contexte annuel commun absent');
htp_1175_assert(strpos($retail, 'parcs_ht_retail_workspace') !== false, 'marqueur de sauvegarde visiteurs absent');
htp_1175_assert(strpos($retail, 'preserve_group_tariffs') !== false && strpos($retail, "['groups']") !== false && strpos($retail, "['columns']['groups']") !== false, 'préservation explicite des tarifs Groupes absente');
htp_1175_assert(strpos($retail, "settings[_complete][tariffs]") !== false, 'sauvegarde canonique des tarifs absente');
htp_1175_assert(strpos($retail, "value=\"parcs_ht_save\"") !== false, 'action de sauvegarde canonique non réutilisée');
htp_1175_assert(strpos($retail, 'ParcsHTTariffSeasonAdmin') !== false, 'enrichissements saisonniers existants non réutilisés');
htp_1175_assert(strpos($retail, 'ParcsHTPaymentChannels') !== false, 'canaux de paiement visiteurs non réutilisés');
htp_1175_assert(strpos($retail, 'data-htp-tariff-column') !== false && strpos($retail, 'data-htp-tariff-row') !== false, 'colonnes ou lignes tarifaires non réutilisées');
htp_1175_assert(strpos($retail, '[valid_from]') !== false && strpos($retail, '[valid_to]') !== false && strpos($retail, '[display_from]') !== false && strpos($retail, '[display_to]') !== false, 'dates de validité/vente des offres absentes');
htp_1175_assert(strpos($retail, '[sale_channel]') !== false && strpos($retail, '[purchase_url]') !== false, 'canal ou lien de vente des offres absent');
htp_1175_assert(strpos($retail, 'Contenus & traductions') !== false, 'pont vers Contenus & traductions absent');
htp_1175_assert(strpos($retail, '<details class="htp-advanced"><summary>Apparence des tarifs visiteurs</summary>') !== false, 'apparence visiteurs non repliée');
htp_1175_assert(strpos($retail, 'groups_note_text_color') === false && strpos($retail, 'groups_booking_note') === false, 'réglages Groupes mélangés aux tarifs visiteurs');

htp_1175_assert(is_string($payments) && strpos($payments, "'onsite'") !== false && strpos($payments, "'online'") !== false, 'canaux Sur place / En ligne historiques absents');
htp_1175_assert(is_string($public) && strpos($public, "'individual'") !== false && strpos($public, "'reduced'") !== false && strpos($public, "'groups'") !== false, 'moteur public canonique modifié ou incomplet');
htp_1175_assert(is_string($css) && strpos($css, '.htp-1175-tariffs') !== false, 'styles dédiés Tarifs visiteurs absents');

fwrite(STDOUT, "OK admin-tariffs-1175-contract\n");
