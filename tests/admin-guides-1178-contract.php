<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.8')) {
    echo "SKIP: contract 1.17.8 applies from 1.17.8.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin-guides-1178.php');
$guides = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$stats = file_get_contents($root . '/includes/class-parcs-ht-guide-stats.php');
$appearance = file_get_contents($root . '/includes/class-parcs-ht-guide-appearance.php');
$content = file_get_contents($root . '/includes/class-parcs-ht-public-content.php');
$css = file_get_contents($root . '/assets/admin-guides-1178.css');
$js = file_get_contents($root . '/assets/admin-guides-1178.js');

foreach (array('main'=>$main,'guides admin'=>$admin,'guides engine'=>$guides,'guide stats'=>$stats,'guide appearance'=>$appearance,'public content'=>$content,'guides css'=>$css,'guides js'=>$js) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_regex($main, array(
    'plugin version remains 1.17.8 or newer'=>'/Version:\s*([0-9.]+)/',
    'PARCS_HT_VERSION constant remains defined'=>"/define\\('PARCS_HT_VERSION',\\s*'[0-9.]+'\\)/",
), '1.17.8 plugin version');

release_contract_require_all($main, array(
    'class-parcs-ht-admin-guides-1178.php',
    'Parcs_HT_Admin_Guides_1178::init();',
), '1.17.8 plugin wiring');

release_contract_require_all($admin, array(
    "const PAGE = 'parcs-ht-guides-1178'",
    "'htp-guides'",
    'Parcs_HT_Admin_Navigation::render_year_context',
    "name=\"action\" value=\"parcs_ht_save_pedagogical_guides\"",
    "wp_nonce_field('parcs_ht_save_pedagogical_guides')",
    'Parcs_HT_Pedagogical_Guides::settings($year)',
    'Parcs_HT_Guide_Stats::render_admin_panel($year)',
    'Parcs_HT_Guide_Appearance::settings()',
    "name=\"action\" value=\"parcs_ht_save_guide_appearance\"",
    'Réglages avancés d’apparence',
    'Contenus & traductions',
    '[parc_guides_pedagogiques]',
    '[parc_guides_pedagogiques_fr]',
    '[parc_guides_pedagogiques_en]',
    '[parc_guides_pedagogiques_de]',
), '1.17.8 dedicated guides admin');

release_contract_require_all($admin, array(
    "'cycle1'", "'cycle2'", "'cycle3'", "'cycle4'", "'multi'",
    "array('fr','de','en')",
    "'available'", "'new'", "'coming'",
    '[pdf_url]', '[cover_url]', '[order]', '[enabled]', '[languages]', '[title]', '[description]'
), '1.17.8 guide metadata preservation');

release_contract_require_all($guides, array(
    "const OPTION = 'parcs_ht_pedagogical_guides'",
    "add_shortcode('parc_guides_pedagogiques'",
    "foreach (array('fr','en','de') as \$language)",
    "add_shortcode('parc_guides_pedagogiques_' . \$language",
    'Parcs_HT_Guide_Stats::tracking_token',
), '1.17.8 historical guides engine preserved');

release_contract_require_all($stats, array(
    "const ID_OPTION = 'parcs_ht_pedagogical_guide_ids'",
    "const META_OPTION = 'parcs_ht_pedagogical_guide_stats_meta'",
    'render_admin_panel($year)',
    "array('view','download')",
), '1.17.8 guide statistics preserved');

release_contract_require_all($appearance, array(
    "const OPTION = 'parcs_ht_guide_appearance'",
    "'card_background'", "'text_color'", "'title_color'",
    "'primary_button_background'", "'primary_button_text'", "'secondary_button_color'", "'category_color'",
), '1.17.8 historical appearance settings preserved');

release_contract_require_all($content, array(
    "'guides.resources'", "'guides.categories'", "'guides.languages'",
    "'guides.cycle1.label'", "'guides.cycle4.detail'", "'guides.multi.label'",
    "'guides.language.fr'", "'guides.language.de'", "'guides.language.en'",
    'replace_guide_content',
), '1.17.8 editorial content connection');

release_contract_require_all($css, array('.htp-1178-guides','.htp-1178-guide','.htp-1178-advanced'), '1.17.8 page-specific styles');
release_contract_require_all($js, array('[data-add-guide]','[data-remove-guide]','[data-media-field]','sortable'), '1.17.8 page-specific interactions');

echo "OK: 1.17.8 pedagogical guides admin simplified without replacing historical engines.\n";

if (release_contract_at_least($version, '1.17.9')) {
    require __DIR__ . '/admin-communication-1179-contract.php';
}
