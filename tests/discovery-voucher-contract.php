<?php
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$file = $root . '/includes/class-parcs-ht-discovery-voucher.php';
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$navigation = file_get_contents($root . '/includes/class-parcs-ht-admin-navigation.php');
$js = file_get_contents($root . '/assets/discovery-voucher.js');
if (!defined('ABSPATH')) define('ABSPATH', '/');
require_once $file;
function discovery_check($ok, $why) {
    if (!$ok) { fwrite(STDERR, "[FAIL] " . $why . PHP_EOL); exit(1); }
    echo "[OK] " . $why . PHP_EOL;
}
$defaults = Parcs_HT_Discovery_Voucher::defaults();
discovery_check($defaults['enabled'] === '0' && $defaults['departments'] === '' && $defaults['form_shortcode'] === '', 'safe inactive and unconfigured defaults');
discovery_check(Parcs_HT_Discovery_Voucher::allowed_for_zip('67600','67, 68'), 'valid listed department');
discovery_check(!Parcs_HT_Discovery_Voucher::allowed_for_zip('54000','67, 68'), 'unlisted department rejected');
discovery_check(!Parcs_HT_Discovery_Voucher::allowed_for_zip('67abc','67, 68'), 'malformed code rejected');
discovery_check(!Parcs_HT_Discovery_Voucher::allowed_for_zip('67600',''), 'empty department list blocks all');
discovery_check(!Parcs_HT_Discovery_Voucher::allowed_for_zip('67600','6'), 'one-digit wildcard is forbidden');
discovery_check(Parcs_HT_Discovery_Voucher::allowed_for_zip('97100','971'), 'three-digit overseas department');
discovery_check(!Parcs_HT_Discovery_Voucher::allowed_for_zip('97200','971'), 'overseas prefix is exact');
discovery_check(count(Parcs_HT_Discovery_Voucher::labels()) >= 9, 'editorial field list is available');
discovery_check(!isset($defaults['texts']['en']) && !isset($defaults['texts']['de']), 'voucher has French-only editorial defaults');
$settings = array('texts'=>array('fr'=>array('title'=>'Bonjour'),'en'=>array('title'=>'Hello')));
discovery_check(Parcs_HT_Discovery_Voucher::text($settings, 'title') === 'Bonjour', 'French offer text ignores old English settings');
discovery_check(strpos($main, 'Parcs_HT_Discovery_Voucher::init();') !== false && strpos($main, 'class-parcs-ht-discovery-voucher.php') !== false, 'plugin bootstraps voucher module');
discovery_check(strpos($registry, "'parc_bon_decouverte'") !== false && strpos($navigation, "'Bons découverte'") !== false, 'shortcode registered and Groupes admin link provided');
$src = file_get_contents($file);
discovery_check(strpos($src, "foreach (array('fr'=>'Français','en'=>'Anglais','de'=>'Allemand')") === false, 'no language panels in voucher admin');
discovery_check(strpos($src, 'Les traductions vides') === false, 'no language availability message in the offer');
discovery_check(strpos($registry, "'single_language'=>true") !== false, 'voucher is represented by one shortcode in registry');
discovery_check(strpos($src, 'wpcf7_validate_text*') !== false && strpos($src, 'wpcf7_spam') !== false, 'CF7 validation and spam fallback enforced at submission');
discovery_check(strpos($src, 'check_ajax_referer') !== false && strpos($src, 'wp_send_json_success') !== false, 'public postcode endpoint verifies nonce');
discovery_check(strpos($js, 'panel.hidden = false') !== false && strpos($js, 'cf7postal.value = code') !== false, 'client reveals CF7 only following eligible reply');
echo "Discovery voucher contract OK\n";
