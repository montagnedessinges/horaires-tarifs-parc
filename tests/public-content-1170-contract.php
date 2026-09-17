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
htp_1170_assert(isset($defaults['texts']['schedule.notAvailable']['fr']), 'texte calendrier indisponible absent');
htp_1170_assert(isset($defaults['urls']['groups.redirect.url']['fr']), 'URL de renvoi groupes absente');

$GLOBALS['parcs_ht_test_options'][Parcs_HT_Public_Content::OPTION] = array(
    'version'=>1,
    'texts'=>array(
        'groups.portal.tariffs_unavailable'=>array('fr'=>'Tarifs bientôt disponibles','en'=>'','de'=>''),
        'groups.redirect.text'=>array('fr'=>'Tarifs groupes {year} : consultez notre espace dédié.','en'=>'','de'=>''),
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

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
htp_1170_assert(strpos($main, "Version: 1.17.0") !== false, 'version 1.17.0 absente du plugin');
htp_1170_assert(strpos($main, "class-parcs-ht-public-content.php") !== false, 'classe de contenus non chargée');
htp_1170_assert(strpos($main, "Parcs_HT_Public_Content::init();") !== false, 'classe de contenus non initialisée');

$class = file_get_contents($root . '/includes/class-parcs-ht-public-content.php');
htp_1170_assert(strpos($class, 'parcs-ht-group-year-unavailable[hidden]') !== false, 'garde-fou hidden du portail groupes absent');
htp_1170_assert(strpos($class, "Contenus & traductions") !== false, 'écran de contenus et traductions absent');
htp_1170_assert(strpos($class, "do_shortcode_tag") !== false, 'intégration des textes dans les shortcodes absente');

fwrite(STDOUT, "OK public-content-1170-contract\n");
