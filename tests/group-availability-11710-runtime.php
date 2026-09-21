<?php

define('ABSPATH', __DIR__ . '/');

$GLOBALS['htp_11710_settings'] = array();

function wp_date($format, $timestamp = null, $timezone = null) {
    unset($timestamp, $timezone);
    if ($format === 'Y-m-d') return '2026-09-21';
    if ($format === 'Y') return '2026';
    return '2026-09-21';
}

final class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
    public static function all_settings() {
        return $GLOBALS['htp_11710_settings'];
    }
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-public-visibility.php';

function htp11710_assert($condition, $message) {
    if ($condition) {
        echo "[OK] {$message}\n";
        return;
    }
    fwrite(STDERR, "[FAIL] {$message}\n");
    exit(1);
}

function htp11710_group_season($price, $visible, $quote = '0', $from = '', $until = '') {
    $rows = array();
    if ($price !== '') {
        $rows[] = array(
            'id'=>'tariff_row_000001',
            'enabled'=>'1',
            'cells'=>array('tariff_col_000001'=>array('value'=>$price)),
        );
    }
    return array(
        'group_tariffs_visible'=>(string)$visible,
        'group_quotes_enabled'=>(string)$quote,
        'public_display_from'=>(string)$from,
        'public_display_until'=>(string)$until,
        'tariffs'=>array('groups'=>$rows),
    );
}

$GLOBALS['htp_11710_settings'] = array(
    'timezone'=>'Europe/Paris',
    'seasons'=>array(
        // Année courante : tarifs présents, devis volontairement désactivé.
        '2026'=>htp11710_group_season('9 €', '1', '0'),
        // Année future : tarifs et devis actifs, données propres à 2027.
        '2027'=>htp11710_group_season('10 €', '1', '1'),
        // Année sans tarifs : l’interrupteur seul ne doit pas rendre l’année publiable.
        '2028'=>htp11710_group_season('', '1', '1'),
        // Tarifs présents mais masqués manuellement.
        '2029'=>htp11710_group_season('11 €', '0', '1'),
        // Tarifs manuellement masqués mais activation automatique déjà commencée.
        '2030'=>htp11710_group_season('12 €', '0', '0', '2026-09-01', '2030-12-31'),
        // Tarifs manuellement visibles mais fenêtre automatique déjà terminée.
        '2031'=>htp11710_group_season('13 €', '1', '1', '', '2026-09-21'),
        // Devis actif sans publication des tarifs : ne doit jamais publier les tarifs par effet de bord.
        '2032'=>htp11710_group_season('14 €', '0', '1'),
    ),
);

$years = Parcs_HT_Public_Visibility::group_tariff_years();

htp11710_assert(in_array('2026', $years, true), 'année courante avec grille Groupes publiée disponible');
htp11710_assert(in_array('2027', $years, true), 'année future avec sa propre grille Groupes publiée disponible');
htp11710_assert(!in_array('2028', $years, true), 'année sans tarifs Groupes exclue même si son interrupteur est actif');
htp11710_assert(!Parcs_HT_Public_Visibility::group_tariff_grid_ready('2028'), 'absence réelle de grille détectée sans fallback vers une autre année');
htp11710_assert(!in_array('2029', $years, true), 'désactivation manuelle des tarifs Groupes respectée');
htp11710_assert(in_array('2030', $years, true), 'activation automatique rend la grille Groupes disponible pendant sa fenêtre');
htp11710_assert(!in_array('2031', $years, true), 'désactivation automatique prioritaire à la date de fin');
htp11710_assert(!in_array('2032', $years, true), 'devis actif ne publie jamais les tarifs Groupes masqués');
htp11710_assert(in_array('2026', $years, true), 'devis désactivé ne masque jamais des tarifs Groupes publiés');
htp11710_assert($years[0] === '2026', 'année courante reste prioritaire dans l’ordre public');

$season2026 = Parcs_HT_Public_Visibility::raw_season('2026');
$season2027 = Parcs_HT_Public_Visibility::raw_season('2027');
htp11710_assert((string)$season2026['tariffs']['groups'][0]['cells']['tariff_col_000001']['value'] === '9 €', '2026 conserve exclusivement son prix Groupes');
htp11710_assert((string)$season2027['tariffs']['groups'][0]['cells']['tariff_col_000001']['value'] === '10 €', '2027 conserve exclusivement son prix Groupes');

htp11710_assert(Parcs_HT_Public_Visibility::module_visible('2026', 'group_quotes_enabled', false) === false, 'état devis 2026 reste indépendant des tarifs');
htp11710_assert(Parcs_HT_Public_Visibility::module_visible('2027', 'group_quotes_enabled', false) === true, 'état devis 2027 reste indépendant et propre à son année');

echo "OK: 1.17.10 group availability scenarios cover current/future/no-data/manual/automatic states without quote coupling or cross-year fallback.\n";
