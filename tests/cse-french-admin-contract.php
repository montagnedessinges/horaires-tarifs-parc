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
cse_french_contract_assert(strpos($cse, 'localized_urls($raw[$key] ??') !== false, 'la sauvegarde traite les URLs françaises séparément');
cse_french_contract_assert(strpos($cse, 'translations($raw[$label_key] ??') !== false, 'la sauvegarde traite les libellés français séparément');
cse_french_contract_assert(strpos($cse, "'en'=>(string)($existing['en'] ?? '')") !== false && strpos($cse, "'de'=>(string)($existing['de'] ?? '')") !== false, 'les traductions historiques sont recopiées');
cse_french_contract_assert(strpos($cse, 'name="cse[') !== false && strpos($cse, '[label]') !== false, 'le formulaire propose les champs français canoniques');
cse_french_contract_assert(strpos($cse, "foreach (array('fr','en','de') as $lang)") !== false, 'les anciens alias de shortcode restent enregistrés');
cse_french_contract_assert(strpos($cse, "Parcs_HT_Schedule::language() === 'fr'") !== false, 'les shortcodes CSE sont limités aux pages françaises');
fwrite(STDOUT, "OK cse-french-admin-contract\n");
