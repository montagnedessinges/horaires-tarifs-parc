<?php

$root = dirname(__DIR__);
$schedule = file_get_contents($root.'/includes/class-parcs-ht-schedule.php');
$popup = file_get_contents($root.'/includes/class-parcs-ht-popup-1200.php');
$popupJs = file_get_contents($root.'/assets/popup-1200.js');
$health = file_get_contents($root.'/includes/class-parcs-ht-health.php');

function check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ECHEC: $message\n");
        exit(1);
    }
}

check(strpos($schedule, "function_exists('qtranxf_getLanguage')") !== false, 'La langue doit utiliser qTranslate-XT lorsqu’il est disponible.');
check(strpos($schedule, "function_exists('pll_current_language')") !== false, 'La langue doit reconnaître Polylang lorsqu’il est disponible.');
check(strpos($schedule, "has_filter('wpml_current_language')") !== false, 'La langue doit reconnaître WPML lorsqu’il est disponible.');
check(strpos($schedule, 'determine_locale()') !== false, 'Le locale WordPress doit rester le repli canonique.');
check(strpos($schedule, 'navigator.language') === false, 'Le moteur PHP ne doit jamais dépendre de la langue du navigateur.');

check(strpos($popup, 'Parcs_HT_Schedule::language()') !== false, 'Le pop-up doit utiliser la langue de page résolue par le moteur canonique.');
check(strpos($popup, "in_array(\$lang, array('fr','en','de')") !== false, 'Le endpoint doit limiter explicitement les langues publiques à FR/EN/DE.');
check(strpos($popup, "if (\$image_url === '') continue;") !== false, 'Un pop-up sans visuel dans la langue courante ne doit pas être affiché.');
check(strpos($popupJs, 'navigator.language') === false && strpos($popupJs, 'navigator.languages') === false, 'Le pop-up public ne doit pas sélectionner sa langue depuis le navigateur.');
check(strpos($health, "wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily'") === false, 'Le contrôle quotidien ne doit plus être planifié.');

fwrite(STDOUT, "Contrat langue pop-up 1.20.0 validé.\n");
