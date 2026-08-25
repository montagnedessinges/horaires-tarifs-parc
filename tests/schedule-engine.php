<?php

define('ABSPATH', __DIR__.'/');
require_once dirname(__DIR__).'/includes/class-parcs-ht-schedule.php';

function htp_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : {$message}\n");
        exit(1);
    }
}

$general = array('accent_color'=>'#ef7b5b');
$season = array(
    'season_start'=>'2026-03-01',
    'season_end'=>'2026-11-30',
    'regular_periods'=>array(array(
        'enabled'=>'1','start'=>'2026-03-01','end'=>'2026-11-30',
        'weekdays'=>array('1','2','3','4','5','6','7'),
        'open'=>'10:00','close'=>'18:00','open2'=>'','close2'=>'','color'=>'#9AAA8B',
    )),
    'exceptions'=>array(
        array('enabled'=>'1','type'=>'hours','start'=>'2026-07-14','end'=>'2026-07-14','open'=>'09:00','close'=>'19:00','priority'=>'20','apply_domain_rules'=>'1'),
        array('enabled'=>'1','type'=>'closed','start'=>'2026-11-02','end'=>'2026-11-06','priority'=>'30'),
        array('enabled'=>'1','type'=>'hours','start'=>'2026-11-02','end'=>'2026-11-02','open'=>'08:00','close'=>'20:00','priority'=>'30'),
    ),
    'special_periods'=>array(
        array('enabled'=>'1','kind'=>'event','start'=>'2026-10-20','end'=>'2026-10-31','show_on_calendar'=>'1','title'=>array('fr'=>'Toussaint','en'=>'Halloween','de'=>'Halloween')),
        array('enabled'=>'1','kind'=>'other','start'=>'2026-08-01','end'=>'2026-08-31','show_on_calendar'=>'0','title'=>array('fr'=>'Interne')),
    ),
    'school_holidays'=>array(),
    'public_holidays'=>array(),
    'domain_rules'=>array(array(
        'enabled'=>'1','start'=>'2026-03-01','end'=>'2026-11-30','weekdays'=>array('1','2','3','4','5','6','7'),
        'exclude_weekends'=>'0','exclude_school_holidays'=>'0','exclude_public_holidays'=>'0','label'=>'Pause',
    )),
);

$regular = Parcs_HT_Schedule::resolve_day($season, $general, '2026-06-15');
htp_assert($regular['open'] && $regular['type'] === 'regular', 'un jour habituel doit être ouvert');

$hours = Parcs_HT_Schedule::resolve_day($season, $general, '2026-07-14');
htp_assert($hours['open'] && $hours['exceptional'] && $hours['openTime'] === '09:00', 'l’horaire exceptionnel doit remplacer l’horaire habituel');

$closed = Parcs_HT_Schedule::resolve_day($season, $general, '2026-11-02');
htp_assert(!$closed['open'] && $closed['exceptional'], 'une fermeture doit gagner à priorité égale');
htp_assert(Parcs_HT_Schedule::domain_rule($season, '2026-11-02', $closed) === false, 'l’accès limité ne doit jamais apparaître un jour fermé');

$outside = Parcs_HT_Schedule::resolve_day($season, $general, '2026-12-15');
htp_assert(!$outside['open'] && $outside['type'] === 'outside', 'un jour hors saison doit être fermé');

$items = Parcs_HT_Schedule::calendar_items($season, '2026-10-25', 'fr');
htp_assert(count($items) === 1 && $items[0]['title'] === 'Toussaint', 'seules les périodes marquées publiques doivent apparaître');

$settings = array(
    'timezone'=>'Europe/Paris','general'=>array_merge($general,array('season_start'=>'2026-03-01','season_end'=>'2026-11-30')),
    'regular_periods'=>$season['regular_periods'],'exceptions'=>$season['exceptions'],'special_periods'=>$season['special_periods'],
    'school_holidays'=>array(),'public_holidays'=>array(),'domain_rules'=>$season['domain_rules'],
);
$report = Parcs_HT_Schedule::audit_season($settings);
htp_assert($report['valid'], 'la saison de test doit être analysable');
htp_assert($report['summary']['exceptional_closures'] === 5, 'les cinq jours de fermeture exceptionnelle doivent être comptés');
htp_assert($report['summary']['domain_limited'] > 0, 'les jours ouverts avec accès limité doivent être comptés');

fwrite(STDOUT, "Tests métier PHP réussis.\n");
