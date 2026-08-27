<?php

$root = dirname(__DIR__);
$schedule = file_get_contents($root.'/includes/class-parcs-ht-schedule.php');
$alerts = file_get_contents($root.'/includes/class-parcs-ht-alerts.php');
$health = file_get_contents($root.'/includes/class-parcs-ht-health.php');

function check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ECHEC: $message\n");
        exit(1);
    }
}

check(strpos($schedule, "function_exists('qtranxf_getLanguage')") !== false, 'La langue doit utiliser qTranslate-XT lorsqu’il est disponible.');
check(strpos($schedule, 'navigator.language') === false, 'Le moteur PHP ne doit jamais dépendre de la langue du navigateur.');
check(strpos($alerts, "'currentLanguage' => Parcs_HT_Schedule::language()") !== false, 'Le pop-up doit recevoir la langue de page résolue par le moteur canonique.');
check(strpos($alerts, 'navigator.language') === false && strpos($alerts, 'navigator.languages') === false, 'Le pop-up public ne doit pas sélectionner sa langue depuis le navigateur.');
check(strpos($health, "wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily'") === false, 'Le contrôle quotidien ne doit plus être planifié.');

fwrite(STDOUT, "Contrat langue pop-up et supervision événementielle validé.\n");
