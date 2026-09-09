<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$advent = file_get_contents($root . '/includes/class-parcs-ht-advent.php');
$admin_path = $root . '/includes/class-parcs-ht-advent-admin.php';
$admin = file_get_contents($admin_path);
$core_admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$preview = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$frontend_js = file_get_contents($root . '/assets/advent.js');
$uninstall = file_get_contents($root . '/uninstall.php');

function advent_check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

advent_check(strpos($main, 'Version: 1.15.1') !== false && strpos($main, "PARCS_HT_VERSION', '1.15.1") !== false, 'admin architecture cleanup uses version 1.15.1');
advent_check(strpos($main, 'class-parcs-ht-advent.php') !== false && strpos($main, 'class-parcs-ht-advent-admin.php') !== false, 'Advent public and canonical admin modules are bootstrapped');
advent_check(!file_exists($root . '/includes/class-parcs-ht-advent-admin-v2.php'), 'obsolete Advent v2 admin filename is removed');
advent_check(strpos($admin, 'add_menu_page(') !== false && strpos($admin, 'add_submenu_page(') === false, 'Advent is a first-class WordPress admin menu');
advent_check(strpos($core_admin, 'Parcs_HT_Advent_Admin::PAGE') !== false && strpos($core_admin, 'Calendrier de l’Avent') !== false, 'main park admin links natively to the Advent workspace');
advent_check(strpos($core_admin, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false, 'central Shortcodes tab is rendered from the registry');
advent_check(!file_exists($root . '/assets/advent-shortcodes-admin.js'), 'obsolete Advent shortcode DOM injection is removed');
advent_check(strpos($advent, "const OPTION = 'parcs_ht_advent'") !== false && strpos($advent, 'const SCHEMA_VERSION = 3') !== false, 'Advent has a dedicated schema 3 store');
advent_check(strpos($advent, "array('mds', 'fds')") !== false || strpos($advent, "array('mds','fds')") !== false, 'installation isolation accepts only mds or fds');

foreach (array('intro_partenaire_fr','intro_question_fr','bonne_reponse_code','bonne_reponse_texte_fr','indice_lettre','indice_position','mot_mystere','grand_jeu_lot_fr') as $field) {
    advent_check(strpos($advent, "'" . $field . "'") !== false, 'canonical field is implemented: ' . $field);
}

advent_check(strpos($registry, "'parc_calendrier_avent'") !== false && strpos($registry, "'parc_reglement_avent'") !== false, 'both Advent shortcodes are in the central registry');
advent_check(substr_count($registry, "'kind'=>'advent'") >= 2, 'Advent shortcodes use the dedicated preview renderer');
advent_check(strpos($preview, 'Parcs_HT_Advent::set_preview_datetime') !== false, 'shared preview date and time are forwarded to the server Advent renderer');
advent_check(strpos($core_admin, "Parcs_HT_Shortcode_Registry::public_rows()") !== false && strpos($registry, "'parc_calendrier_avent'") !== false && strpos($registry, "'parc_reglement_avent'") !== false, 'central Shortcodes tab exposes both Advent blocks natively');

$runtime = $advent . "\n" . $frontend_js;
foreach (array('KINTZHEIM','ROCAMADOUR','Kintzheim','Rocamadour') as $forbidden) {
    advent_check(strpos($runtime, $forbidden) === false, 'runtime contains no hardcoded campaign value: ' . $forbidden);
}
advent_check(strpos($runtime, '2026-12-') === false && strpos($runtime, "'2026'") === false, 'runtime contains no hardcoded 2026 campaign dates');

$enqueue_start = strpos($advent, 'private static function enqueue_assets()');
$enqueue_end = strpos($advent, 'public static function set_preview_datetime', $enqueue_start);
$enqueue = $enqueue_start !== false && $enqueue_end !== false ? substr($advent, $enqueue_start, $enqueue_end - $enqueue_start) : '';
advent_check($enqueue !== '', 'public asset payload can be inspected');
foreach (array('mot_mystere','bonne_reponse_code','bonne_reponse_texte_fr','indice_lettre','indice_position','grand_jeu_formulaire_shortcode') as $secret) {
    advent_check(strpos($enqueue, $secret) === false, 'initial browser payload excludes secret: ' . $secret);
}

advent_check(strpos($frontend_js, "button.addEventListener('click'") !== false, 'day detail is requested after a visitor click');
advent_check(strpos($frontend_js, 'data-advent-day') !== false && strpos($frontend_js, 'content_id:button.getAttribute') !== false, 'open day detail is fetched on demand from the server');
advent_check(strpos($advent, 'check_ajax_referer(self::PUBLIC_NONCE_ACTION') !== false, 'public Advent AJAX endpoints require a nonce');
advent_check(strpos($advent, 'self::day_open_at') !== false && strpos($advent, 'self::now($campaign) < $open_at') !== false, 'server enforces day opening date and time');
advent_check(strpos($advent, "'statut_resultat'] ?? '') === 'publie'") !== false, 'daily result requires explicit published status');
advent_check(strpos($advent, 'hash_equals($expected, $provided)') !== false, 'mystery word is compared on the server');
advent_check(strpos($advent, 'rate_limit_reached') !== false && strpos($advent, 'authorization_token') !== false, 'final game uses rate limiting and signed authorization');
advent_check(strpos($advent, 'do_shortcode($shortcode)') !== false && strpos($advent, 'render_final_form') !== false, 'final form shortcode is rendered only through the authorized server path');

foreach (array(
    'save_campaign'=>'parcs_ht_advent_save_campaign',
    'save_content'=>'parcs_ht_advent_save_content',
    'save_partner'=>'parcs_ht_advent_save_partner',
    'save_result'=>'parcs_ht_advent_save_result',
    'csv_template'=>'parcs_ht_advent_csv_template',
    'import_csv'=>'parcs_ht_advent_import_csv',
    'apply_import'=>'parcs_ht_advent_apply_import',
) as $method => $nonce_action) {
    $start = strpos($admin, 'public static function ' . $method . '()');
    $next = $start !== false ? strpos($admin, 'public static function ', $start + 20) : false;
    if ($start === false) {
        $block = '';
    } elseif ($next === false) {
        $block = substr($admin, $start);
    } else {
        $block = substr($admin, $start, $next - $start);
    }
    $nonce_position = strpos($block, "check_admin_referer('" . $nonce_action . "')");
    $post_position = strpos($block, "\$_POST['campaign_id']");
    advent_check($block !== '' && $nonce_position !== false, 'admin write verifies nonce: ' . $method);
    advent_check($post_position === false || $nonce_position < $post_position, 'nonce is verified before campaign POST data is read: ' . $method);
}
advent_check(strpos($admin, "check_admin_referer('parcs_ht_advent_create_campaign')") !== false, 'campaign creation verifies nonce');
advent_check(strpos($admin, "wp_nonce_field(\$action);") !== false, 'shared Advent forms use action-scoped nonces without pre-reading campaign input');

advent_check(strpos($admin, 'Analyser sans écrire') !== false && strpos($admin, 'original_hash') !== false, 'import performs a dry run before writing');
advent_check(strpos($admin, "'schema_version'") !== false && strpos($admin, "'parc_code'") !== false && strpos($admin, "'campagne_id'") !== false, 'import validates schema, park and campaign identifiers');
advent_check(strpos($admin, '!empty($before[\'visuel_url\'])') !== false && strpos($admin, '!empty($before[\'logo_url\'])') !== false, 'smart reimport preserves manually assigned media when incoming media is empty');
advent_check(strpos($admin, 'count($seen) !== 24') !== false, 'smart import enforces exactly 24 daily entries');
advent_check(strpos($admin, 'Publier le résultat') !== false && strpos($admin, 'Enregistrer sans publier') !== false, 'result publication is an explicit separate admin action');
advent_check(strpos($admin, 'Aperçu Facebook') !== false && strpos($admin, 'Aperçu Instagram') !== false && strpos($admin, 'Copier le texte') !== false, 'admin provides generated social previews with copy actions');
advent_check(strpos($uninstall, "'parcs_ht_advent'") !== false, 'Advent data follows the plugin uninstall data-deletion preference');

echo "Advent prototype contract: OK\n";
