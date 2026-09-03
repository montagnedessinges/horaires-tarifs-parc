<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$php = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$css = file_get_contents($root . '/assets/pedagogical-guides.css');

function guide_contract($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

guide_contract(strpos($php, "const STORE_VERSION = 3") !== false, 'Guide storage contract is version 3');
guide_contract(strpos($php, "private static function cycle_catalog()") !== false, 'Cycles are a fixed reusable catalog');
guide_contract(strpos($php, "data-guide-cycle-filters") !== false, 'Public cycle filters are rendered');
guide_contract(strpos($php, "data-guide-language-filters") !== false, 'Public language filters are rendered');
guide_contract(strpos($php, "count($used_cycles)>1") !== false, 'Cycle filters are hidden when only one cycle is available');
guide_contract(strpos($php, "count($used_languages)>1") !== false, 'Language filters are hidden when only one language is available');
guide_contract(strpos($php, "data-languages=") !== false && strpos($php, "data-cycle=") !== false, 'Guide cards expose cycle and language filter data');
guide_contract(strpos($php, "cycle==='all'") !== false && strpos($php, "lang==='all'") !== false, 'Default public state displays all guides');
guide_contract(strpos($php, "cm&&lm") !== false, 'Cycle and language filters combine');
guide_contract(strpos($php, 'Ajouter une catégorie') === false, 'Admin no longer exposes category management');
guide_contract(strpos($php, 'Cycle / niveau') !== false && strpos($php, 'Langue(s) du document') !== false, 'Each guide directly owns its cycle and languages');
guide_contract(strpos($css, '.parcs-ht-guide-filters') !== false && strpos($css, '@media(max-width:800px)') !== false, 'Public filters have responsive styles');

echo "Pedagogical guides contract: OK\n";
