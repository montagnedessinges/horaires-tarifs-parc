<?php
/**
 * Contrat statique de l'administration CSE en français et de la conservation des anciennes traductions.
 */
$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$cse = file_get_contents($root . '/includes/class-parcs-ht-cse.php');
function cse_french_contract_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}
cse_french_contract_assert(is_string($cse) && $cse !== '', 'module CSE absent');
cse_french_contract_assert(strpos($cse, "self::localized_urls($raw[$key] ?? '', $old[$key] ?? array())") !== false, 'les URLs EN/DE historiques ne sont pas préservées à la sauvegarde');
cse_french_contract_assert(strpos($cse, "self::translations($raw[$label_key] ?? '', false, $old[$label_key] ?? array())") !== false, 'les libellés EN/DE historiques ne sont pas préservés');
cse_french_contract_assert(strpos($cse, "'en'=>(string)($existing['en'] ?? '')") !== false && strpos($cse, "'de'=>(string)($existing['de'] ?? '')") !== false, 'les traductions historiques ne sont pas recopiées');
cse_french_contract_assert(strpos($cse, 'name="cse[' . "' . esc_attr($key) . '" . ']"') !== false, 'le formulaire ne poste pas un champ français unique');
cse_french_contract_assert(strpos($cse, 'name="cse[rows][' . "' . (int)$index . '" . '][label]"') !== false, 'les lignes ne postent pas un seul libellé français');
cse_french_contract_assert(strpos($cse, "foreach (array('fr','en','de') as $lang)") !== false, 'les anciens alias de shortcode ne sont plus enregistrés');
cse_french_contract_assert(strpos($cse, "Parcs_HT_Schedule::language() === 'fr'") !== false, 'les shortcodes CSE ne sont pas limités aux pages françaises');
fwrite(STDOUT, "OK cse-french-admin-contract\n");
