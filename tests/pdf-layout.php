<?php

define('ABSPATH', __DIR__.'/');
define('PARCS_HT_DIR', dirname(__DIR__).'/');
function wp_strip_all_tags($text) { return strip_tags((string)$text); }
require_once dirname(__DIR__).'/includes/class-parcs-ht-schedule.php';
require_once dirname(__DIR__).'/includes/class-parcs-ht-shortcodes.php';

$colors = array('#2f855a','#3182ce','#805ad5','#d69e2e','#dd6b20','#c53030','#2c7a7b','#6b46c1','#4a5568','#38a169','#b7791f','#2b6cb0');
$periods = array();
for ($month=1; $month<=12; $month++) {
    $last = (int)date('t', strtotime('2026-'.sprintf('%02d',$month).'-01'));
    $periods[] = array(
        'enabled'=>'1','start'=>sprintf('2026-%02d-01',$month),'end'=>sprintf('2026-%02d-%02d',$month,$last),
        'weekdays'=>array('1','2','3','4','5','6','7'),'open'=>sprintf('%02d:00',8+($month%3)),'close'=>sprintf('%02d:30',17+($month%3)),
        'open2'=>'','close2'=>'','color'=>$colors[$month-1],
    );
}
$events = array();
for ($i=1; $i<=16; $i++) {
    $events[] = array(
        'enabled'=>'1','kind'=>$i%3===0?'other':'event','start'=>'2026-10-01','end'=>'2026-10-31','show_on_calendar'=>'1',
        'title'=>array('fr'=>'Événement ou période numéro '.$i,'en'=>'Event or period number '.$i,'de'=>'Veranstaltung oder Zeitraum Nummer '.$i),
        'color'=>'#e7c55b',
    );
}
$ctx = array(
    'language'=>'fr','park'=>'Parc de démonstration','website'=>'https://example.org','generated_on'=>'25/08/2026','year'=>'2026',
    'start'=>'2026-01-01','end'=>'2026-12-31','timezone'=>'Europe/Paris',
    'general'=>array('accent_color'=>'#ef7b5b','show_public_holidays'=>'1','holiday_border_color'=>'#e7c55b','holiday_border_width'=>'3'),
    'regular_periods'=>$periods,
    'exceptions'=>array(
        array('enabled'=>'1','type'=>'closed','start'=>'2026-11-02','end'=>'2026-11-06','priority'=>'50','show_public_marker'=>'1'),
        array('enabled'=>'1','type'=>'hours','start'=>'2026-07-14','end'=>'2026-07-14','open'=>'09:00','close'=>'12:00','open2'=>'14:00','close2'=>'20:00','priority'=>'50','show_public_marker'=>'1'),
    ),
    'special_periods'=>$events,'school_holidays'=>array(),
    'public_holidays'=>array(array('enabled'=>'1','date'=>'2026-07-14')),
    'domain_rules'=>array(array('enabled'=>'1','start'=>'2026-04-01','end'=>'2026-09-30','weekdays'=>array('1','2','3','4','5'),'exclude_weekends'=>'1','exclude_school_holidays'=>'0','exclude_public_holidays'=>'1','pause_start'=>'12:00','resume'=>'13:00','last_entry'=>'11:30','label'=>'Accès au domaine limité')),
);

$method = new ReflectionMethod('Parcs_HT_Shortcodes', 'build_schedule_pdf');
if (PHP_VERSION_ID < 80100) $method->setAccessible(true);
$pdf = $method->invoke(null, $ctx);
if (strpos($pdf, '%PDF-') !== 0 || strlen($pdf) < 5000) {
    fwrite(STDERR, "ÉCHEC : le planning PDF de contrôle est invalide.\n");
    exit(1);
}
$target = isset($argv[1]) ? $argv[1] : sys_get_temp_dir().'/horaires-tarifs-parc-qa.pdf';
file_put_contents($target, $pdf);
fwrite(STDOUT, $target."\n");
