<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);

function htp_1173_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$nav = file_get_contents($root . '/includes/class-parcs-ht-admin-navigation.php');
$schedule = file_get_contents($root . '/includes/class-parcs-ht-admin-schedule.php');
$js = file_get_contents($root . '/assets/admin-schedule-1173.js');
$css = file_get_contents($root . '/assets/admin-schedule-1173.css');

htp_1173_assert(is_string($main) && strpos($main, "Version: 1.17.3") !== false, 'version 1.17.3 absente');
htp_1173_assert(strpos($main, "class-parcs-ht-admin-schedule.php") !== false, 'nouvel écran Horaires non chargé');
htp_1173_assert(strpos($main, "Parcs_HT_Admin_Schedule::init();") !== false, 'nouvel écran Horaires non initialisé');

htp_1173_assert(is_string($nav) && strpos($nav, "'parcs-ht-schedule'   => 'htp-regular'") === false, 'Horaires reste routé vers l’ancien pont');
htp_1173_assert(strpos($nav, "Parcs_HT_Admin_Schedule::PAGE") !== false, 'ancien lien Horaires non redirigé vers le nouvel écran');
htp_1173_assert(strpos($nav, "array('Parcs_HT_Admin_Schedule', 'page')") !== false, 'sous-menu Horaires non branché sur l’écran dédié');
htp_1173_assert(strpos($nav, 'render_year_context') !== false, 'contexte annuel commun absent');

htp_1173_assert(is_string($schedule) && strpos($schedule, "const PAGE = 'parcs-ht-schedule';") !== false, 'slug Horaires dédié absent');
htp_1173_assert(strpos($schedule, "parcs_ht_save_schedule_1173") !== false, 'action de sauvegarde dédiée absente');
htp_1173_assert(strpos($schedule, "regular_periods") !== false, 'horaires habituels absents');
htp_1173_assert(strpos($schedule, "open2") !== false && strpos($schedule, "close2") !== false, 'second créneau facultatif absent');
htp_1173_assert(strpos($schedule, "Options avancées de cette période") !== false, 'options avancées non repliées');
htp_1173_assert(strpos($schedule, "Parcs_HT_Schedule_CSV::render_controls") !== false, 'moteur CSV existant non réutilisé');
htp_1173_assert(strpos($schedule, "csv_imported") !== false, 'confirmation d’import CSV absente du nouvel écran');
htp_1173_assert(strpos($schedule, "Contenus & traductions") !== false, 'pont vers les contenus FR/EN/DE absent');
foreach (array(
    'today_title_color','today_title_bg_color','today_title_bg_transparent','today_status_color','today_closed_color','today_detail_color',
    'calendar_title_color','calendar_title_bg_color','calendar_title_bg_transparent','calendar_weekday_color','calendar_day_bg_color','calendar_day_bg_transparent',
    'calendar_nav_bg_color','calendar_nav_text_color','calendar_nav_active_bg_color','calendar_nav_active_text_color',
    'calendar_detail_text_color','calendar_detail_border_color','calendar_closed_bg_color','calendar_closed_text_color','calendar_selected_color',
) as $appearance_key) {
    htp_1173_assert(strpos($schedule, $appearance_key) !== false, 'réglage visuel historique absent : ' . $appearance_key);
}
htp_1173_assert(strpos($schedule, 'update_option(Parcs_HT_Defaults::OPTION, $all, false)') !== false, 'sauvegarde canonique non conservée');
htp_1173_assert(strpos($schedule, "special_periods") === false && strpos($schedule, "exceptions]") === false, 'l’écran Horaires embarque des moteurs réservés à 1.17.4');

htp_1173_assert(is_string($js) && strpos($js, 'data-htp-add-period') !== false, 'ajout de période JS absent');
htp_1173_assert(strpos($js, 'data-htp-remove-period') !== false, 'suppression de période JS absente');
htp_1173_assert(is_string($css) && strpos($css, '.htp-1173-schedule') !== false, 'styles dédiés absents');

fwrite(STDOUT, "OK admin-schedule-1173-contract\n");
