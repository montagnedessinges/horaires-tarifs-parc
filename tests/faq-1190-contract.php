<?php

$root = getenv('PLUGIN_ROOT');
if (!$root) $root = dirname(__DIR__);
require_once __DIR__ . '/release-contract.php';

$feature_path = $root . '/includes/class-parcs-ht-faq.php';
$csv_path = $root . '/includes/class-parcs-ht-faq-csv-1191.php';
$contact_path = $root . '/includes/class-parcs-ht-faq-contact-1192.php';
$unified_path = $root . '/includes/class-parcs-ht-faq-unified-1193.php';
$faq_js_path = $root . '/assets/faq.js';
if (!is_file($feature_path) || !is_file($csv_path) || !is_file($contact_path) || !is_file($unified_path) || !is_file($faq_js_path)) {
    fwrite(STDERR, "FAQ contract files missing.\n");
    exit(1);
}

$feature = file_get_contents($feature_path);
$csv = file_get_contents($csv_path);
$contact = file_get_contents($contact_path);
$unified = file_get_contents($unified_path);
$faq_js = file_get_contents($faq_js_path);
$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$uninstall = file_get_contents($root . '/uninstall.php');
if (!is_string($feature) || !is_string($csv) || !is_string($contact) || !is_string($unified) || !is_string($faq_js) || !is_string($bootstrap) || !is_string($registry) || !is_string($uninstall)) {
    fwrite(STDERR, "Unable to read FAQ sources.\n");
    exit(1);
}

release_contract_require_all($feature, array(
    "const OPTION = 'parcs_ht_faq';",
    "const REVISIONS_OPTION = 'parcs_ht_faq_revisions';",
    "const PAGE = 'parcs-ht-faq';",
    "add_shortcode('parc_faq'",
    "add_shortcode('parc_faq_' . \$language",
    "admin_post_parcs_ht_faq_save_settings",
    "admin_post_parcs_ht_faq_restore_revision",
    "check_admin_referer('parcs_ht_faq_save_settings')",
    "do_action('litespeed_purge_all')",
    "'has_import'=>'0'",
    "'enabled'=>'0'",
    "'show_search'=>'1'",
    "'show_categories'=>'1'",
    '[parc_faq_fr]',
    '[parc_faq_en]',
    '[parc_faq_de]',
    'data-htp-faq-item',
    'data-htp-faq-secondary',
    'data-htp-faq-secondary-item',
    'parcs-ht-faq-source',
    'regles-du-parc',
    '<details class=',
), 'FAQ public feature');

release_contract_forbid($feature, array(
    "update_option(Parcs_HT_Defaults::OPTION",
    "update_option('parcs_ht_settings'",
    'wp_ajax_',
    'display:none',
    'visibility:hidden',
), 'FAQ isolation/public contract');

release_contract_require_all($csv, array(
    'final class Parcs_HT_FAQ_CSV_1191',
    "admin_post_parcs_ht_faq_csv_preview",
    "admin_post_parcs_ht_faq_csv_apply",
    "remove_action('admin_post_parcs_ht_faq_save_connection'",
    "remove_action('admin_post_parcs_ht_faq_check_google'",
    "remove_action('admin_post_parcs_ht_faq_apply_import'",
    "remove_action(\$hook, array('Parcs_HT_FAQ', 'page'))",
    "check_admin_referer('parcs_ht_faq_csv_preview')",
    "check_admin_referer('parcs_ht_faq_csv_apply')",
    'fgetcsv(',
    "'ID stable'",
    "'Question canonique FR'",
    "'Réponse courte FR'",
    "'Statut'",
    "'Usage / visibilité'",
    'is_uploaded_file',
    'MAX_BYTES',
    'MAX_ROWS',
    'Aucune suppression automatique',
    'Avant import CSV FAQ',
    'Les fiches absentes du CSV n’ont pas été supprimées',
    "update_option(Parcs_HT_FAQ::OPTION",
    "update_option(Parcs_HT_FAQ::REVISIONS_OPTION",
    "do_action('litespeed_purge_all')",
    'Frage DE',
    'Kurzantwort DE',
    'Question EN',
    'Short answer EN',
    "'source_kind'",
    "'knowledge'",
    "'SIN-COM-'",
    "'Question canonique'",
    "'Réponse courte vérifiée'",
    'Connaissances singes - IA',
    'Secondaire / IA',
), 'FAQ CSV 1.19.6 workflow');

release_contract_forbid($csv, array(
    'wp_safe_remote_post',
    'script.google.com',
    'FAQ_SHARED_SECRET',
    "update_option(Parcs_HT_Defaults::OPTION",
    "update_option('parcs_ht_settings'",
    'wp_ajax_',
), 'FAQ CSV isolation/no-Google contract');

release_contract_require_all($contact, array(
    'final class Parcs_HT_FAQ_Contact_1192',
    "const OPTION = 'parcs_ht_faq_contact';",
    "add_shortcode('parc_faq_contact'",
    "add_shortcode('parc_faq_contact_' . \$language",
    "add_shortcode('parc_faq'",
    "\$atts['titre'] = '0';",
    'parcs-ht-faq-intro',
    '<p><strong>',
    "shortcode_exists('contact-form-7')",
    'do_shortcode($shortcode)',
    "admin_post_parcs_ht_faq_contact_save",
    "check_admin_referer('parcs_ht_faq_contact_save')",
    "update_option(self::OPTION",
    'Contact Form 7 envoie ensuite la demande par e-mail',
    '[parc_faq_contact]',
    '[parc_faq_contact_fr]',
    '[parc_faq_contact_en]',
    '[parc_faq_contact_de]',
    'data-htp-faq-contact',
    'data-htp-faq-contact-toggle',
    'data-htp-faq-contact-form',
    'Je n’ai pas trouvé ma réponse',
    'data-htp-faq-contact-form hidden',
), 'FAQ contact 1.19.5 integration');

release_contract_forbid($contact, array(
    "update_option(Parcs_HT_Defaults::OPTION",
    "update_option('parcs_ht_settings'",
    '<header class="parcs-ht-faq-heading"',
    'wp_mail(',
    "'contact_button'=>'Nous écrire'",
), 'FAQ contact isolation/plain rendering');

release_contract_require_all($unified, array(
    'final class Parcs_HT_FAQ_Unified_1193',
    "remove_filter('do_shortcode_tag'",
    "array('Parcs_HT_AI_Google', 'append_visit_rules')",
    'FAQ unifiée',
    'Montagne des Singes',
    'même CSV',
    'Gestion du parc → FAQ',
), 'FAQ unified source 1.19.3');

release_contract_forbid($unified, array(
    "update_option(Parcs_HT_Defaults::OPTION",
    "update_option('parcs_ht_settings'",
    'wp_mail(',
), 'FAQ unified isolation');

release_contract_require_all($faq_js, array(
    '[data-htp-faq-contact]',
    '[data-htp-faq-contact-toggle]',
    '[data-htp-faq-contact-form]',
    'aria-expanded',
    'is-search-fallback',
    '[data-htp-faq-secondary]',
    'section.open = true',
), 'FAQ secondary/search JavaScript');

release_contract_require_all($bootstrap, array(
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-faq.php';",
    'Parcs_HT_FAQ::init();',
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-faq-csv-1191.php';",
    'Parcs_HT_FAQ_CSV_1191::init();',
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-faq-contact-1192.php';",
    'Parcs_HT_FAQ_Contact_1192::init();',
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-faq-unified-1193.php';",
    'Parcs_HT_FAQ_Unified_1193::init();',
), 'FAQ bootstrap');

release_contract_require_all($registry, array(
    "'parc_faq' => array('label'=>'FAQ','kind'=>'faq'",
    "'parc_faq_contact' => array('label'=>'FAQ + formulaire de contact','kind'=>'faq_contact'",
    "\$definition['kind'] === 'faq'",
    "\$definition['kind'] === 'faq_contact'",
    'Parcs_HT_FAQ_Contact_1192::render_faq',
    'Parcs_HT_FAQ_Contact_1192::render_combined',
), 'FAQ shortcode registry');

release_contract_require_all($uninstall, array('parcs_ht_faq', 'parcs_ht_faq_revisions', 'parcs_ht_faq_contact'), 'FAQ uninstall');

echo "FAQ 1.19.6 secondary rules and knowledge import contract OK.\n";
