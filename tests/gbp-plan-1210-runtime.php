<?php
/**
 * Contrats de projection du calendrier pour Google Business Profile.
 * Ne nécessite ni compte Google ni base de données WordPress.
 */
define('ABSPATH', __DIR__.'/');
class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) { $this->code=$code; $this->message=$message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($v) { return $v instanceof WP_Error; }
require_once __DIR__.'/../includes/class-parcs-ht-schedule.php';
require_once __DIR__.'/../includes/class-parcs-ht-gbp-plan.php';
function expect_gbp($ok,$why) {
    if (!$ok) { fwrite(STDERR, "ÉCHEC GBP : ".$why."\n"); exit(1); }
}
$calendar = array(
    'timezone'=>'Europe/Paris',
    'general'=>array(),
    'seasons'=>array(
        '2026'=>array(
            'season_start'=>'2026-11-01', 'season_end'=>'2026-11-11',
            'regular_periods'=>array(array(
                'enabled'=>'1','start'=>'2026-11-01','end'=>'2026-11-11',
                'weekdays'=>array('6','7'),'open'=>'10:00','close'=>'17:00',
            )),
            'exceptions'=>array(
                array('enabled'=>'1','start'=>'2026-11-02','end'=>'2026-11-06','type'=>'closed','priority'=>4),
                array('enabled'=>'1','start'=>'2026-11-09','end'=>'2026-11-10','type'=>'closed','priority'=>4),
                array('enabled'=>'1','start'=>'2026-11-11','end'=>'2026-11-11','type'=>'hours','priority'=>10,'open'=>'10:00','close'=>'17:00'),
            ),
        ),
        '2027'=>array(
            'season_start'=>'2027-03-20','season_end'=>'2027-11-11',
            'regular_periods'=>array(array(
                'enabled'=>'1','start'=>'2027-03-20','end'=>'2027-11-11',
                'weekdays'=>array('1','2','3','4','5','6','7'),
                'open'=>'09:30','close'=>'18:00',
            )),
            'exceptions'=>array(),
        ),
    )
);
$original = serialize($calendar);
$plan = Parcs_HT_GBP_Plan::build($calendar,'2026-11-02');
expect_gbp(!is_wp_error($plan), 'Plan valide au 2 novembre.');
expect_gbp($plan['mode'] === 'open', 'Parc en période d’ouverture.');
expect_gbp(count($plan['regularHours']['periods']) === 2, 'Les deux jours du week-end sont dans la base.');
$special = $plan['specialHours']['specialHourPeriods'];
$by_date = array();
foreach ($special as $row) {
    $date = sprintf('%04d-%02d-%02d',$row['startDate']['year'],$row['startDate']['month'],$row['startDate']['day']);
    $by_date[$date][] = $row;
}
expect_gbp(isset($by_date['2026-11-11']) && empty($by_date['2026-11-11'][0]['closed']), '11 novembre : ouverture exceptionnelle calculée.');
expect_gbp(isset($by_date['2026-11-14']) && !empty($by_date['2026-11-14'][0]['closed']), 'Après fin de saison, les week-ends habituels sont annulés.');
expect_gbp(serialize($calendar) === $original, 'Lecture seule : pas de modification du calendrier WordPress.');
$closed = Parcs_HT_GBP_Plan::build($calendar,'2026-11-12');
expect_gbp(!is_wp_error($closed) && $closed['mode'] === 'temporary_closed', '12 novembre : fermeture hivernale.');
$next = Parcs_HT_GBP_Plan::build($calendar,'2027-03-20');
expect_gbp(!is_wp_error($next) && $next['mode'] === 'open', 'Réouverture 2027 via le même calendrier.');
$bad = $calendar;
$bad['seasons']['2026']['exceptions'][]=array(
    'enabled'=>'1','start'=>'2026-11-07','end'=>'2026-11-07','type'=>'hours','priority'=>20,
    'open'=>'17:00','close'=>'10:00',
);
$wrong = Parcs_HT_GBP_Plan::build($bad,'2026-11-02');
expect_gbp(is_wp_error($wrong), 'Horaire inversé non envoyé à Google.');
echo "GBP 1.21.0 : calendrier canonique, exceptions, fermeture saisonnière, réouverture, sécurité — OK\n";
