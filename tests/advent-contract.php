<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$advent = file_get_contents($root . '/includes/class-parcs-ht-advent.php');
$appearance_path = $root . '/includes/class-parcs-ht-advent-appearance.php';
$appearance = file_get_contents($appearance_path);
$admin_path = $root . '/includes/class-parcs-ht-advent-admin.php';
$admin = file_get_contents($admin_path);
$core_admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$preview = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$frontend_js = file_get_contents($root . '/assets/advent.js');
$advent_admin_js = file_get_contents($root . '/assets/advent-admin.js');
$appearance_admin_js = file_get_contents($root . '/assets/advent-appearance-admin.js');
$appearance_admin_css = file_get_contents($root . '/assets/advent-appearance-admin.css');
$public_css = file_get_contents($root . '/assets/advent.css');
$core_admin_js = file_get_contents($root . '/assets/admin.js');
$admin_css = file_get_contents($root . '/assets/advent-admin.css');
$uninstall = file_get_contents($root . '/uninstall.php');

function advent_check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

advent_check(strpos($main, 'Version: 1.15.7') !== false && strpos($main, "PARCS_HT_VERSION', '1.15.7") !== false, 'Advent preview color fix remains active in version 1.15.7');
advent_check(strpos($main, 'class-parcs-ht-advent.php') !== false && strpos($main, 'class-parcs-ht-advent-admin.php') !== false, 'Advent public and canonical admin modules are bootstrapped');
advent_check(strpos($main, 'class-parcs-ht-advent-appearance.php') !== false && strpos($main, 'Parcs_HT_Advent_Appearance::init()') !== false, 'dedicated Advent appearance module is bootstrapped');
advent_check(!file_exists($root . '/includes/class-parcs-ht-advent-admin-v2.php'), 'obsolete Advent v2 admin filename is removed');
advent_check(strpos($admin, 'add_menu_page(') === false && strpos($admin, 'add_submenu_page(') === false, 'Advent no longer creates a separate WordPress menu');
advent_check(strpos($core_admin, 'data-htp-admin-tab="htp-advent"') !== false && strpos($core_admin, 'Calendrier de l’Avent</button>') !== false, 'Advent is a native main Horaires du parc tab');
advent_check(strpos($core_admin, 'Parcs_HT_Advent_Admin::render_workspace()') !== false && strpos($admin, 'public static function render_workspace()') !== false, 'main admin renders the Advent workspace directly');
advent_check(strpos($core_admin, 'data-htp-main-settings-form') !== false && strpos($core_admin, 'data-htp-season-manager') !== false, 'main settings and season manager can be hidden cleanly while Advent is active');
advent_check(strpos($core_admin, "isset(\$_GET['advent_fragment'])") !== false && strpos($core_admin, 'Parcs_HT_Advent_Admin::render_workspace();') !== false, 'Advent supports a lightweight server fragment for inner navigation');
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
advent_check(strpos($preview, 'private static function advent_preview_assets()') !== false, 'Advent preview has a dedicated asset bridge');
advent_check(strpos($preview, "wp_enqueue_style('parcs-ht-advent')") !== false && strpos($preview, 'Parcs_HT_Advent_Appearance::frontend_styles()') !== false, 'saved Advent palette is explicitly injected into the isolated shortcode preview');
advent_check(strpos($preview, '$html = Parcs_HT_Shortcode_Registry::render_preview($base, $language);') !== false && strpos($preview, 'if ($is_advent) self::advent_preview_assets();') !== false, 'Advent appearance is applied after the real shortcode renderer has selected its campaign');
advent_check(strpos($core_admin, "Parcs_HT_Shortcode_Registry::public_rows()") !== false && strpos($registry, "'parc_calendrier_avent'") !== false && strpos($registry, "'parc_reglement_avent'") !== false, 'central Shortcodes tab exposes both Advent blocks natively');

advent_check(strpos($core_admin_js, "'htp-advent'") !== false && strpos($core_admin_js, "id === 'htp-advent'") !== false, 'core tab engine manages Advent like the other main tabs');
advent_check(strpos($core_admin_js, "querySelector('[data-htp-main-settings-form]')") !== false && strpos($core_admin_js, "querySelector('[data-htp-season-manager]')") !== false, 'core tab engine switches the main forms cleanly for Advent');
advent_check(strpos($advent_admin_js, "parsed.searchParams.get('page')==='parcs-horaires-tarifs'") !== false && strpos($advent_admin_js, "parsed.searchParams.get('tab')==='htp-advent'") !== false, 'Advent inner router stays inside Horaires du parc');
advent_check(strpos($advent_admin_js, "searchParams.set('advent_fragment','1')") !== false && strpos($advent_admin_js, "querySelector('[data-htp-advent-workspace]')") !== false, 'inner Advent navigation refreshes only the workspace');
advent_check(strpos($advent_admin_js, 'window.history.pushState') !== false && strpos($advent_admin_js, "window.addEventListener('popstate'") !== false, 'Advent in-page navigation preserves browser history');
advent_check(strpos($admin_css, '.htp-advent-day-grid,') !== false && strpos($admin_css, '.htp-advent-day-card,') !== false, 'actual calendar markup is styled as a responsive grid of cards');
advent_check(strpos($admin_css, 'grid-template-columns: repeat(6') !== false && strpos($admin_css, 'grid-template-columns: repeat(2') !== false, 'calendar grid has desktop and mobile layouts');
advent_check(strpos($admin_css, 'max-width: none') !== false && strpos($admin_css, 'width: 100%') !== false, 'Advent workspace uses the full useful width of the main admin tab');

advent_check(strpos($appearance, "const OPTION = 'parcs_ht_advent_appearance'") !== false && strpos($appearance, 'const SCHEMA_VERSION = 1') !== false, 'Advent appearance uses a dedicated lightweight store');
foreach (array('primary','secondary','open_day','today','locked','special') as $color_key) {
    advent_check(strpos($appearance, "'" . $color_key . "' => ''") !== false, 'appearance keeps inherited display by default: ' . $color_key);
}
advent_check(strpos($appearance, 'sanitize_hex_color') !== false, 'custom Advent colors are sanitized as hexadecimal colors');
advent_check(strpos($appearance, 'Parcs_HT_Advent::campaign($campaign_id, true)') !== false, 'appearance save is restricted to a campaign from the current installation');
advent_check(strpos($appearance, "check_ajax_referer(self::NONCE_ACTION, 'nonce')") !== false && strpos($appearance, "current_user_can('manage_options')") !== false, 'appearance save requires administrator capability and nonce');
advent_check(strpos($appearance, "wp_add_inline_style('parcs-ht-advent'") !== false && strpos($appearance, 'data-campaign-id') !== false, 'palette is scoped to the public campaign without changing campaign data');
foreach (array('--htp-advent-primary','--htp-advent-secondary','--htp-advent-open-day','--htp-advent-today','--htp-advent-locked','--htp-advent-special') as $variable) {
    advent_check(strpos($public_css, $variable) !== false, 'public Advent stylesheet exposes variable: ' . $variable);
}
advent_check(strpos($appearance_admin_js, 'Couleurs du Calendrier de l’Avent') !== false && strpos($appearance_admin_js, "currentView()!=='campaign'") !== false, 'color controls stay inside the Advent campaign workspace');
advent_check(strpos($appearance_admin_js, 'data-advent-appearance-save') !== false && strpos($appearance_admin_js, "mode==='reset'") !== false, 'admin can save or return to inherited Advent colors');
advent_check(strpos($appearance_admin_css, 'background: transparent') !== false, 'appearance settings keep the admin card background neutral');

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
advent_check(strpos($uninstall, "'parcs_ht_advent'") !== false && strpos($uninstall, "'parcs_ht_advent_appearance'") !== false, 'Advent data and appearance follow the plugin uninstall data-deletion preference');

echo "Advent prototype contract: OK\n";
