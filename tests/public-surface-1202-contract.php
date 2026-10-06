<?php
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$semantic = file_get_contents($root . '/includes/class-parcs-ht-calendar-semantic.php');
$tariffs = file_get_contents($root . '/includes/class-parcs-ht-tariff-display.php');

function surface1202_check($ok, $message) {
    if (!$ok) { fwrite(STDERR, "[FAIL] {$message}\n"); exit(1); }
    echo "[OK] {$message}\n";
}

surface1202_check(is_string($composer) && is_string($portal) && is_string($semantic) && is_string($tariffs), 'sources 1.20.2 readable');
surface1202_check(strpos($composer, "add_shortcode('parc_groupes_horaires_tarifs'") === false, 'composer no longer registers a competing group portal shortcode');
surface1202_check(strpos($composer, 'Parcs_HT_Group_Portal::render') !== false, 'legacy composer group calls delegate to the canonical portal');
surface1202_check(strpos($portal, 'Parcs_HT_Public_Visibility::group_schedule_years()') !== false && strpos($portal, 'Parcs_HT_Public_Visibility::group_tariff_years()') !== false, 'group portal uses central annual visibility');
surface1202_check(strpos($portal, 'htp_group_year') !== false && strpos($portal, 'data-group-year=') !== false, 'group year selector has a server navigation fallback');
surface1202_check(strpos($portal, "add_filter('parcs_ht_calendar_semantic_years'") !== false, 'group portal scopes semantic calendar to public group schedule years');
surface1202_check(strpos($portal, 'Parcs_HT_Tariff_Display::render_group($language, array(), $year)') !== false, 'group tariffs are rendered server-side for their explicit year');
surface1202_check(strpos($portal, 'private static function public_row') !== false && strpos($portal, 'private static function schedule_payload') !== false, 'group schedule payload is rebuilt from an allowlist');
surface1202_check(strpos($portal, 'internal_label') === false, 'group portal never serializes or renders internal labels');
surface1202_check(strpos($portal, "if (!\$active_has_hours) echo esc_html(\$hours_unavailable);") !== false, 'unavailable-hours text is emitted only when initially true');
surface1202_check(strpos($semantic, 'public static function render($language = \'\', $allowed_years = null)') !== false, 'semantic renderer accepts an explicit public year scope');
surface1202_check(strpos($semantic, "apply_filters('parcs_ht_calendar_semantic_years'") !== false, 'semantic shortcode supports context-specific public year scope');
surface1202_check(strpos($tariffs, 'Parcs_HT_Public_Visibility::tariff_years()') !== false, 'visitor tariff renderer uses central visibility');
surface1202_check(strpos($tariffs, 'Parcs_HT_Public_Visibility::group_tariff_years()') !== false, 'group tariff renderer uses central visibility');

echo "Public surface 1.20.2 contract OK.\n";
