<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);

function htp_1174_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$periods = file_get_contents($root . '/includes/class-parcs-ht-admin-periods.php');
$routing = file_get_contents($root . '/includes/class-parcs-ht-admin-periods-routing.php');
$schedule = file_get_contents($root . '/includes/class-parcs-ht-schedule.php');
$alerts = file_get_contents($root . '/includes/class-parcs-ht-alerts.php');
$css = file_get_contents($root . '/assets/admin-periods-1174.css');

htp_1174_assert(is_string($main) && strpos($main, 'Version: 1.17.4') !== false, 'version 1.17.4 absente');
htp_1174_assert(strpos($main, 'class-parcs-ht-admin-periods.php') !== false && strpos($main, 'Parcs_HT_Admin_Periods::init();') !== false, 'écran Périodes 1.17.4 non chargé');
htp_1174_assert(strpos($main, 'class-parcs-ht-admin-periods-routing.php') !== false && strpos($main, 'Parcs_HT_Admin_Periods_Routing::init();') !== false, 'routage 1.17.4 non chargé');

htp_1174_assert(is_string($periods) && strpos($periods, "const PAGE = 'parcs-ht-periods';") !== false, 'slug Périodes dédié absent');
htp_1174_assert(strpos($periods, 'render_year_context') !== false, 'contexte annuel commun absent');
htp_1174_assert(strpos($periods, 'name="action" value="parcs_ht_save"') !== false, 'sauvegarde canonique parcs_ht_save non réutilisée');
htp_1174_assert(strpos($periods, 'parcs_ht_save_periods_1174') === false, 'second moteur de sauvegarde détecté');
htp_1174_assert(strpos($periods, "settings[_complete][holidays]") !== false && strpos($periods, "settings[_complete][exceptions]") !== false && strpos($periods, "settings[_complete][domain_rules]") !== false, 'sauvegarde ciblée des trois sections absente');

foreach (array('star','camera','calendar','gift','music','leaf','flag','heart','info') as $icon) {
    htp_1174_assert(strpos($periods, "'{$icon}'") !== false, 'pictogramme événement manquant : ' . $icon);
}
htp_1174_assert(strpos($periods, "[icon]") !== false && strpos($periods, "[color]") !== false, 'couleur ou pictogramme événement non éditable');
htp_1174_assert(strpos($periods, 'pre_update_option_parcs_ht_settings') !== false && strpos($periods, 'preserve_event_visuals') !== false, 'persistance des visuels événement absente');

htp_1174_assert(strpos($periods, "[open2]") !== false && strpos($periods, "[close2]") !== false, 'second créneau exceptionnel facultatif absent');
htp_1174_assert(strpos($periods, "[show_popup]") !== false && strpos($periods, 'data-htp-popup-settings') !== false, 'réglages conditionnels de pop-up absents');
htp_1174_assert(strpos($periods, "[popup_show_dates]") !== false && strpos($periods, "[popup_show_hours]") !== false, 'options indépendantes dates/horaires du pop-up absentes');
htp_1174_assert(strpos($periods, 'Source unique') !== false && strpos($periods, "[context]") !== false && strpos($periods, "[title]") !== false && strpos($periods, "[message]") !== false, 'source unique du pop-up exceptionnel non exposée');
htp_1174_assert(strpos($periods, "[domain_rules]") !== false && strpos($periods, 'Accès temporairement limité') !== false, 'module Accès limité absent');
htp_1174_assert(strpos($periods, 'Parcs_HT_Schedule_CSV::render_controls') !== false, 'moteur CSV commun non réutilisé');

htp_1174_assert(is_string($routing) && strpos($routing, "array('htp-holidays','htp-exceptions','htp-domain')") !== false, 'anciens onglets non redirigés');
htp_1174_assert(strpos($routing, "array('Parcs_HT_Admin_Periods', 'page')") !== false, 'sous-menu Périodes non branché sur le nouvel écran');

htp_1174_assert(is_string($schedule) && strpos($schedule, 'usort($exceptions') !== false, 'tri canonique des exceptions disparu');
htp_1174_assert(strpos($schedule, "$a_type === 'closed' ? -1 : 1") !== false, 'fermeture prioritaire à priorité égale disparue');
htp_1174_assert(strpos($schedule, "regular_periods") !== false, 'repli automatique vers horaires habituels absent');
htp_1174_assert(strpos($schedule, "domain_rules") !== false, 'moteur canonique Accès limité absent');

htp_1174_assert(is_string($alerts) && strpos($alerts, "isset($row['context'])") !== false && strpos($alerts, "isset($row['title'])") !== false && strpos($alerts, "isset($row['message'])") !== false, 'pop-up exception ne réutilise plus les champs publics');
htp_1174_assert(strpos($alerts, "popup_show_dates") !== false && strpos($alerts, "popup_show_hours") !== false, 'moteur pop-up ne respecte plus dates/horaires');
htp_1174_assert(is_string($css) && strpos($css, '.htp-1174-periods') !== false, 'styles dédiés 1.17.4 absents');

fwrite(STDOUT, "OK admin-periods-1174-contract\n");
