<?php

$root = getenv('PLUGIN_ROOT');
if (!$root) $root = dirname(__DIR__);

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!function_exists('esc_html')) { function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('esc_attr')) { function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('wp_kses')) { function wp_kses($value, $allowed) { unset($allowed); return (string)$value; } }
if (!function_exists('apply_filters')) { function apply_filters($hook, $value) { unset($hook); return $value; } }

final class Parcs_HT_Defaults {
    public static function all_settings() {
        return array(
            'general' => array('show_public_holidays' => '1'),
            'seasons' => array(
                '2026' => array(
                    'published' => '1',
                    'season_start' => '2026-03-21',
                    'season_end' => '2026-11-08',
                    'regular_periods' => array(
                        array(
                            'enabled'=>'1', 'start'=>'2026-10-01', 'end'=>'2026-10-31',
                            'weekdays'=>array('1','2','3','4','5','6','7'), 'open'=>'10:00', 'close'=>'17:00',
                        ),
                    ),
                    'exceptions' => array(
                        array(
                            'enabled'=>'1', 'type'=>'closed', 'start'=>'2026-10-12', 'end'=>'2026-10-12',
                            'show_public_marker'=>'1',
                            'context'=>array('fr'=>'Fermeture technique','en'=>'Technical closure','de'=>'Technische Schließung'),
                        ),
                    ),
                    'special_periods' => array(
                        array(
                            'enabled'=>'1', 'kind'=>'event', 'show_on_calendar'=>'1',
                            'start'=>'2026-10-24', 'end'=>'2026-10-31', 'internal_label'=>'NE PAS EXPOSER',
                            'title'=>array('fr'=>'Halloween au parc','en'=>'','de'=>'Halloween im Park'),
                        ),
                        array(
                            'enabled'=>'1', 'kind'=>'school_holiday', 'show_on_calendar'=>'1',
                            'start'=>'2026-10-17', 'end'=>'2026-11-01', 'internal_label'=>'INTERNE TOUSSAINT',
                            'title'=>array('fr'=>'Vacances de la Toussaint','en'=>'Autumn school holidays','de'=>'Herbstferien'),
                        ),
                    ),
                    'public_holidays' => array(
                        array(
                            'enabled'=>'1', 'date'=>'2026-11-01',
                            'title'=>array('fr'=>'Toussaint','en'=>'All Saints’ Day','de'=>'Allerheiligen'),
                        ),
                    ),
                ),
            ),
        );
    }
}

final class Parcs_HT_Public_Visibility {
    public static function scheduled_state($year) { return (string)$year === '2026' ? 'on' : 'off'; }
}

final class Parcs_HT_Schedule {
    public static function language() { return 'fr'; }
}

require_once $root . '/includes/class-parcs-ht-calendar-semantic.php';

function indexation_assert_contains($haystack, $needle, $message) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\nMissing: " . $needle . "\n");
        exit(1);
    }
}

function indexation_assert_not_contains($haystack, $needle, $message) {
    if (strpos($haystack, $needle) !== false) {
        fwrite(STDERR, $message . "\nUnexpected: " . $needle . "\n");
        exit(1);
    }
}

$fr = Parcs_HT_Calendar_Semantic::render('fr');
indexation_assert_contains($fr, 'Calendrier 2026', 'La saison doit être lisible dans le HTML serveur.');
indexation_assert_contains($fr, '10:00–17:00', 'Les horaires habituels doivent être lisibles dans le HTML serveur.');
indexation_assert_contains($fr, 'Fermeture technique', 'Le motif public d’une fermeture doit être lisible.');
indexation_assert_contains($fr, 'Halloween au parc', 'Le vrai nom public d’un événement doit être lisible.');
indexation_assert_contains($fr, 'Vacances de la Toussaint', 'Le vrai nom public d’une période doit être lisible.');
indexation_assert_contains($fr, 'Toussaint', 'Un jour férié public doit être lisible quand son affichage est activé.');
indexation_assert_contains($fr, '<time datetime="2026-10-24">', 'Les dates doivent être balisées avec time/datetime.');
indexation_assert_not_contains($fr, 'NE PAS EXPOSER', 'Un libellé interne ne doit jamais être exposé au public.');
indexation_assert_not_contains($fr, 'INTERNE TOUSSAINT', 'Un libellé interne de période ne doit jamais être exposé au public.');

$en = Parcs_HT_Calendar_Semantic::render('en');
indexation_assert_contains($en, '<strong>Event</strong>', 'Une traduction manquante doit utiliser le type générique localisé.');
indexation_assert_not_contains($en, 'Halloween au parc', 'Une traduction anglaise manquante ne doit pas reprendre automatiquement le titre français.');
indexation_assert_contains($en, 'Autumn school holidays', 'Une traduction anglaise existante doit être rendue.');

if (strpos($fr, 'application/ld+json') !== false || strpos($fr, 'schema.org/Event') !== false) {
    fwrite(STDERR, "Le rendu sémantique HTML ne doit pas fabriquer de schéma Event.\n");
    exit(1);
}

echo "Indexation 1.20.1 runtime OK.\n";
