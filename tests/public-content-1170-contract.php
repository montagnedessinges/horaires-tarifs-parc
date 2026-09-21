<?php

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);

$GLOBALS['parcs_ht_test_options'] = array();
if (!function_exists('get_option')) {
    function get_option($name, $default = false) {
        return array_key_exists($name, $GLOBALS['parcs_ht_test_options']) ? $GLOBALS['parcs_ht_test_options'][$name] : $default;
    }
}
if (!function_exists('esc_html')) {
    function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

require_once $root . '/includes/class-parcs-ht-public-content.php';

function htp_1170_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$defaults = Parcs_HT_Public_Content::defaults();
htp_1170_assert(isset($defaults['texts']['groups.portal.tariffs_unavailable']['fr']), 'message groupes indisponibles absent');
htp_1170_assert(isset($defaults['texts']['groups.redirect.text']['en']), 'texte de renvoi groupes EN absent');
htp_1170_assert(isset($defaults['texts']['guides.download']['de']), 'texte guides DE absent');
htp_1170_assert(isset($defaults['texts']['guides.cycle1.label']['en']), 'libellés de cycles des guides absents');
htp_1170_assert(isset($defaults['texts']['guides.language.de']['fr']), 'noms de langues des guides absents');
htp_1170_assert(isset($defaults['texts']['schedule.notAvailable']['fr']), 'texte calendrier indisponible absent');
htp_1170_assert(isset($defaults['texts']['schedule.prev_month']['fr']) && isset($defaults['texts']['schedule.next_month']['de']), 'navigation mensuelle modifiable absente');
htp_1170_assert(isset($defaults['texts']['schedule.download_pdf']['en']), 'bouton PDF horaires modifiable absent');
htp_1170_assert(isset($defaults['texts']['tariffs.download_pdf']['de']), 'bouton PDF tarifs modifiable absent');
htp_1170_assert(isset($defaults['texts']['groups.special.buy']['fr']), 'microcopies des offres groupes absentes');
htp_1170_assert(isset($defaults['texts']['groups.special.valid_between']['fr']), 'texte dynamique de validité groupe absent');
htp_1170_assert(isset($defaults['urls']['groups.redirect.url']['fr']), 'URL de renvoi groupes absente');

$GLOBALS['parcs_ht_test_options'][Parcs_HT_Public_Content::OPTION] = array(
    'version'=>3,
    'texts'=>array(
        'groups.portal.tariffs_unavailable'=>array('fr'=>'Tarifs bientôt disponibles','en'=>'','de'=>''),
        'groups.redirect.text'=>array('fr'=>'Tarifs groupes {year} : consultez notre espace dédié.','en'=>'','de'=>''),
        'schedule.prev_month'=>array('fr'=>'Mois avant','en'=>'','de'=>''),
        'schedule.download_pdf'=>array('fr'=>'Planning PDF','en'=>'','de'=>''),
        'tariffs.download_pdf'=>array('fr'=>'Tarifs PDF','en'=>'','de'=>''),
        'groups.special.buy'=>array('fr'=>'Réserver','en'=>'','de'=>''),
        'groups.special.valid_between'=>array('fr'=>'Offre valable du {from} au {to}','en'=>'','de'=>''),
        'groups.special.valid_from'=>array('fr'=>'Offre valable dès le {from}','en'=>'','de'=>''),
        'groups.special.valid_until'=>array('fr'=>'Offre valable jusqu’au {to}','en'=>'','de'=>''),
        'guides.all_cycles'=>array('fr'=>'Tous les niveaux','en'=>'','de'=>''),
        'guides.all_languages'=>array('fr'=>'Toutes les langues','en'=>'','de'=>''),
        'guides.cycle1.label'=>array('fr'=>'Petite enfance','en'=>'','de'=>''),
        'guides.language.de'=>array('fr'=>'Allemand / Deutsch','en'=>'','de'=>''),
    ),
    'urls'=>array(
        'groups.redirect.url'=>array('fr'=>'https://example.test/groupes','en'=>'','de'=>''),
    ),
);

htp_1170_assert(
    Parcs_HT_Public_Content::text('groups.portal.tariffs_unavailable', 'fr') === 'Tarifs bientôt disponibles',
    'la surcharge FR n’est pas utilisée'
);
htp_1170_assert(
    Parcs_HT_Public_Content::text('groups.portal.tariffs_unavailable', 'en') === 'Tarifs bientôt disponibles',
    'le repli FR d’une surcharge vide n’est pas utilisé'
);
htp_1170_assert(
    Parcs_HT_Public_Content::url('groups.redirect.url', 'fr') === 'https://example.test/groupes',
    'l’URL de renvoi personnalisée n’est pas utilisée'
);
htp_1170_assert(
    Parcs_HT_Public_Content::format('Tarifs groupes {year}', array('year'=>'2027')) === 'Tarifs groupes 2027',
    'le remplacement des variables ne fonctionne pas'
);

$calendar_sample = '<button aria-label="Mois précédent">‹</button><a>Télécharger le planning des horaires</a>';
$calendar_filtered = Parcs_HT_Public_Content::filter_shortcode_output($calendar_sample, 'parc_calendrier_fr', array(), null);
htp_1170_assert(strpos($calendar_filtered, 'aria-label="Mois avant"') !== false, 'la navigation calendrier historique n’utilise pas la surcharge');
htp_1170_assert(strpos($calendar_filtered, '>Planning PDF<') !== false, 'le bouton PDF horaires historique n’utilise pas la surcharge');

$tariff_sample = '<a>Télécharger les tarifs en PDF</a><a>Acheter</a><span>Valable du 1 septembre 2026 au 30 septembre 2026</span><span>Valable à partir du 1 octobre 2026</span><span>Valable jusqu’au 15 octobre 2026</span>';
$tariff_filtered = Parcs_HT_Public_Content::filter_shortcode_output($tariff_sample, 'parc_tarifs_groupes_fr', array(), null);
htp_1170_assert(strpos($tariff_filtered, '>Tarifs PDF<') !== false, 'le bouton PDF tarifs historique n’utilise pas la surcharge');
htp_1170_assert(strpos($tariff_filtered, '>Réserver<') !== false, 'le bouton offre historique n’utilise pas la surcharge');
htp_1170_assert(strpos($tariff_filtered, 'Offre valable du 1 septembre 2026 au 30 septembre 2026') !== false, 'la validité entre deux dates n’utilise pas le texte modifiable');
htp_1170_assert(strpos($tariff_filtered, 'Offre valable dès le 1 octobre 2026') !== false, 'la validité à partir d’une date n’utilise pas le texte modifiable');
htp_1170_assert(strpos($tariff_filtered, 'Offre valable jusqu’au 15 octobre 2026') !== false, 'la validité jusqu’à une date n’utilise pas le texte modifiable');

$guide_sample = '<button data-guide-cycle="all">Tous</button><button data-guide-language="all">Toutes</button><button data-guide-cycle="cycle1">Cycle 1</button><span>Allemand</span>';
$guide_filtered = Parcs_HT_Public_Content::filter_shortcode_output($guide_sample, 'parc_guides_pedagogiques_fr', array(), null);
htp_1170_assert(strpos($guide_filtered, '>Tous les niveaux<') !== false, 'le libellé Tous les cycles n’est pas indépendant');
htp_1170_assert(strpos($guide_filtered, '>Toutes les langues<') !== false, 'le libellé Toutes les langues n’est pas indépendant');
htp_1170_assert(strpos($guide_filtered, '>Petite enfance<') !== false, 'le libellé de cycle n’est pas modifiable');
htp_1170_assert(strpos($guide_filtered, '>Allemand / Deutsch<') !== false, 'le nom public de langue n’est pas modifiable');

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$plugin_version = '';
if (preg_match('/Version:\s*([0-9.]+)/', $main, $matches)) {
    $plugin_version = isset($matches[1]) ? (string)$matches[1] : '';
}
htp_1170_assert($plugin_version !== '' && version_compare($plugin_version, '1.17.0', '>='), 'la fonctionnalité 1.17.0 doit rester protégée dans les versions suivantes');
htp_1170_assert(strpos($main, "class-parcs-ht-public-content.php") !== false, 'classe de contenus non chargée');
htp_1170_assert(strpos($main, "Parcs_HT_Public_Content::init();") !== false, 'classe de contenus non initialisée');

$class = file_get_contents($root . '/includes/class-parcs-ht-public-content.php');
htp_1170_assert(strpos($class, "Contenus & traductions") !== false, 'écran de contenus et traductions absent');
htp_1170_assert(strpos($class, "Rechercher un texte") !== false, 'recherche de simplification des contenus absente');
htp_1170_assert(strpos($class, "do_shortcode_tag") !== false, 'pont de compatibilité des anciens shortcodes absent');
htp_1170_assert(strpos($class, "private static function catalog()") !== false, 'le catalogue éditorial unique est absent');
htp_1170_assert(strpos($class, "replace_dynamic_group_specials") !== false, 'les textes dynamiques des offres groupes ne sont pas couverts');
htp_1170_assert(strpos($class, "replace_guide_content") !== false, 'les contenus structurels des guides ne sont pas couverts');
htp_1170_assert(strpos($class, "frontend_guard") === false, 'l’ancien garde-fou JavaScript Groupes n’a pas été retiré');
htp_1170_assert(strpos($class, "'parc_calendrier'") !== false, 'les libellés statiques du calendrier ne sont pas couverts');

$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
htp_1170_assert(strpos($portal, "Parcs_HT_Public_Content::text") !== false, 'le portail Groupes ne lit pas directement les contenus éditables');
if (version_compare($plugin_version, '1.17.7', '<')) {
    htp_1170_assert(strpos($portal, "groups.portal.tariffs_unavailable") !== false, 'le message Groupes indisponible n’est pas branché sur le référentiel');
    htp_1170_assert(strpos($portal, "\$tariff_missing_possible") !== false, 'le message tarifs indisponibles n’est pas conditionné à une vraie année manquante');
} else {
    // Depuis 1.17.7 ce message a été retiré du renderer du portail : il pouvait
    // rester visible sous des tarifs valides via un ancien HTML mis en cache.
    htp_1170_assert(strpos($portal, "groups.portal.tariffs_unavailable") === false, 'le message Groupes indisponible obsolète est encore branché au portail');
    htp_1170_assert(strpos($portal, "data-group-tariff-unavailable") === false, 'le nœud DOM du faux message Groupes indisponible existe encore');
}
htp_1170_assert(strpos($portal, "[hidden]{display:none!important}") !== false, 'la règle hidden locale du portail Groupes est absente');

$shared = file_get_contents($root . '/includes/class-parcs-ht-tariff-shared-1168.php');
htp_1170_assert(strpos($shared, "Parcs_HT_Public_Content::text") !== false, 'le tableau public ne lit pas directement les contenus éditables');
htp_1170_assert(strpos($shared, "groups.redirect.visitor_notice") !== false, 'la mention visiteurs indisponibles n’est pas branchée sur le référentiel');
htp_1170_assert(strpos($shared, "Parcs_HT_Public_Content::url('groups.redirect.url'") !== false, 'le lien Groupes personnalisable n’est pas lu directement');

fwrite(STDOUT, "OK public-content-1170-contract\n");
