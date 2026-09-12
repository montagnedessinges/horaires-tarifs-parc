<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$core = file_get_contents($root . '/includes/class-parcs-ht-advent.php');
$ux = file_get_contents($root . '/includes/class-parcs-ht-advent-ux.php');
$frontend = file_get_contents($root . '/assets/advent-ux.js');
$admin = file_get_contents($root . '/assets/advent-ux-admin.js');
$uninstall = file_get_contents($root . '/uninstall.php');

function advent_ux_check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

advent_ux_check(strpos($main, 'class-parcs-ht-advent-ux.php') !== false && strpos($main, 'Parcs_HT_Advent_UX::init()') !== false, 'Advent UX module is bootstrapped');
advent_ux_check(strpos($ux, "const OPTION = 'parcs_ht_advent_ux'") !== false && strpos($ux, 'const SCHEMA_VERSION = 1') !== false, 'Advent UX uses a dedicated lightweight store');
advent_ux_check(strpos($ux, "add_filter('option_' . Parcs_HT_Defaults::OPTION") !== false, 'daily Advent popups are injected into the existing alert settings');
advent_ux_check(strpos($ux, "Parcs_HT_Alerts::init()") !== false && strpos($ux, "class-parcs-ht-alerts.php") !== false, 'Advent popups reuse the existing popup engine');
advent_ux_check(strpos($ux, "admin_post_parcs_ht_advent_save_content") !== false && strpos($ux, 'capture_popup_before_content_save') !== false, 'popup settings are captured from the canonical day editor');
advent_ux_check(strpos($ux, "wp_verify_nonce($nonce, 'parcs_ht_advent_save_content')") !== false && strpos($ux, "current_user_can('manage_options')") !== false, 'popup settings keep the canonical admin security boundary');
advent_ux_check(strpos($ux, "'enabled' => '0'") !== false && strpos($ux, "'show_button' => '1'") !== false, 'daily popup remains opt-in');
advent_ux_check(strpos($ux, "if ($start === '') $start = self::content_open_datetime") !== false, 'popup start can inherit the day opening time');
advent_ux_check(strpos($ux, "$end = $date . 'T23:59'") !== false, 'popup end defaults to the end of the same day');
advent_ux_check(strpos($ux, "partner_url") !== false && strpos($ux, "instagram_url_override") !== false && strpos($ux, "facebook_url_override") !== false && strpos($ux, "site_url") !== false, 'partner destination follows Instagram then Facebook then website data');

advent_ux_check(strpos($frontend, 'Jeu quotidien') !== false && strpos($frontend, 'Mystère de Noël') !== false, 'participation panel exposes the two validated columns');
advent_ux_check(strpos($frontend, 'Participer sur Facebook') !== false && strpos($frontend, 'Participer sur Instagram') !== false, 'participation panel exposes both social actions');
advent_ux_check(strpos($frontend, 'content.facebookUrl ? content.facebookUrl : (campaign.facebookUrl') !== false, 'Facebook action prefers the exact daily publication then falls back to the park account');
advent_ux_check(strpos($frontend, 'content.instagramUrl ? content.instagramUrl : (campaign.instagramUrl') !== false, 'Instagram action prefers the exact daily publication then falls back to the park account');
advent_ux_check(strpos($frontend, 'parcs-ht-advent-partner-link') !== false && strpos($frontend, "target = '_blank'") !== false, 'partner links open separately without losing the calendar');
advent_ux_check(strpos($frontend, 'grid-template-columns:repeat(2') !== false && strpos($frontend, '@media(max-width:720px)') !== false, 'participation layout is two columns on desktop and stacked on mobile');

advent_ux_check(strpos($admin, 'Activer un pop-up pour cette journée') !== false, 'day editor exposes an explicit popup opt-in');
advent_ux_check(strpos($admin, 'fields.hidden = !enable.box.checked') !== false, 'popup settings remain hidden until activation');
advent_ux_check(strpos($admin, 'Le pop-up réutilise le moteur général des alertes') !== false, 'admin explains the shared popup engine');

advent_ux_check(strpos($core, 'const SCHEMA_VERSION = 3') !== false, 'canonical CSV schema remains version 3');
advent_ux_check(strpos($core, "'popup_enabled'") === false && strpos($core, "'advent_popup'") === false, 'optional popup settings do not silently change the canonical CSV contract');
advent_ux_check(strpos($uninstall, "'parcs_ht_advent_ux'") !== false, 'Advent UX data follows the plugin uninstall preference');

$payload_start = strpos($ux, 'private static function public_payload()');
$payload_end = strpos($ux, 'public static function frontend_assets()', $payload_start);
$payload = $payload_start !== false && $payload_end !== false ? substr($ux, $payload_start, $payload_end - $payload_start) : '';
foreach (array('mot_mystere','bonne_reponse_code','bonne_reponse_texte_fr','indice_lettre','indice_position','grand_jeu_formulaire_shortcode') as $secret) {
    advent_ux_check(strpos($payload, $secret) === false, 'UX public payload excludes secret: ' . $secret);
}

echo "Advent UX contract: OK\n";
