<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$php = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$css = file_get_contents($root . '/assets/pedagogical-guides.css');

function guide_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

guide_contract(strpos($php, 'const STORE_VERSION = 3') !== false, 'Guide storage contract is version 3');
guide_contract(strpos($php, 'private static function cycle_catalog()') !== false, 'Cycles are a fixed reusable catalog');
guide_contract(strpos($php, "'cycle4'=>array(") !== false, 'Cycle 4 is supported');
guide_contract(strpos($php, "'fr'=>'Cycle 1'") !== false && strpos($php, "'fr'=>'Maternelle – 3 à 6 ans'") !== false, 'French filter keeps Cycle 1 and exposes its detail');
guide_contract(strpos($php, "'en'=>'Ages 6–9'") !== false && strpos($php, "'en'=>'Primary School – Ages 6–9'") !== false, 'English filter uses ages and exposes school detail');
guide_contract(strpos($php, "'de'=>'9–12 Jahre'") !== false && strpos($php, "'de'=>'Grundschule / Sekundarstufe I – 9–12 Jahre'") !== false, 'German filter uses ages and exposes school detail');
guide_contract(strpos($php, "'de'=>'Sekundarstufe I – 12–15 Jahre'") !== false, 'German Cycle 4 wording uses Jahre');
guide_contract(strpos($php, "add_shortcode('parc_guides_pedagogiques_' . \$language") !== false, 'Dedicated FR EN DE guide shortcodes remain registered');
guide_contract(strpos($php, 'data-guide-cycle-filters') !== false, 'Public cycle filters are rendered');
guide_contract(strpos($php, 'data-guide-info') !== false && strpos($php, 'aria-expanded') !== false, 'Each cycle filter has accessible touch-friendly information');
guide_contract(strpos($php, 'data-guide-language-filters') !== false, 'Public language filters are rendered');
guide_contract(strpos($php, "title=\"<?php echo esc_attr(\$m[\$language]);?>\"") !== false, 'Language filter flags retain accessible language names');
guide_contract(strpos($php, 'count($used_cycles)>1') !== false, 'Cycle filters are hidden when only one cycle is available');
guide_contract(strpos($php, 'count($used_languages)>1') !== false, 'Language filters are hidden when only one language is available');
guide_contract(strpos($php, 'data-languages=') !== false && strpos($php, 'data-cycle=') !== false, 'Guide cards expose cycle and language filter data');
guide_contract(strpos($php, "cycle==='all'") !== false && strpos($php, "lang==='all'") !== false, 'Default public state displays all guides');
guide_contract(strpos($php, 'cm&&lm') !== false, 'Cycle and language filters combine');
guide_contract(strpos($php, 'Ajouter une catégorie') === false, 'Admin no longer exposes category management');
guide_contract(strpos($php, 'Cycle / niveau') !== false && strpos($php, 'Langue(s) du document') !== false, 'Each guide directly owns its cycle and languages');
guide_contract(strpos($css, '.parcs-ht-guide-info-pop') !== false && strpos($css, '@media(max-width:800px)') !== false, 'Cycle information and public filters have responsive styles');

echo "Pedagogical guides contract: OK\n";
