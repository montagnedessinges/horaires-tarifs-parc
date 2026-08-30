<?php
/**
 * Plugin Name: Horaires et tarifs du parc
 * Description: Horaires, calendrier interactif, exceptions, alertes et tarifs multilingues pour les parcs.
 * Version: 1.9.18
 * Update URI: https://github.com/montagnedessinges/horaires-tarifs-parc
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Tanguy Huriez – Montagne des Singes
 * Text Domain: horaires-tarifs-parc
 */

if (!defined('ABSPATH')) { exit; }

define('PARCS_HT_VERSION', '1.9.18');
define('PARCS_HT_FILE', __FILE__);
define('PARCS_HT_DIR', plugin_dir_path(__FILE__));
define('PARCS_HT_URL', plugin_dir_url(__FILE__));

require_once PARCS_HT_DIR . 'includes/class-parcs-ht-defaults.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-schedule.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-bootstrap.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-slot-last-entry.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-http-ssl.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-tariff-seasons.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-season-status.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-group-quotes.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-quote-languages.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-quote-gate.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin-groups.php';
Parcs_HT_HTTP_SSL::init();
Parcs_HT_Tariff_Seasons::init();
Parcs_HT_Season_Status::init();

register_activation_hook(__FILE__, array('Parcs_HT_Defaults', 'activate'));
register_deactivation_hook(__FILE__, static function () { require_once PARCS_HT_DIR . 'includes/class-parcs-ht-health.php'; Parcs_HT_Health::deactivate(); });

add_action('updated_option', static function ($option, $old_value, $value) {
    unset($old_value);
    if ($option === Parcs_HT_Defaults::OPTION && is_array($value)) {
        Parcs_HT_Defaults::refresh_popup_flag($value);
        update_option('parcs_ht_export_revision', (int)get_option('parcs_ht_export_revision', 0) + 1, false);
        if (!wp_next_scheduled('parcs_ht_pregenerate_exports')) wp_schedule_single_event(time() + 10, 'parcs_ht_pregenerate_exports');
    }
}, 10, 3);
add_action('added_option', static function ($option, $value) {
    if ($option === Parcs_HT_Defaults::OPTION && is_array($value)) {
        Parcs_HT_Defaults::refresh_popup_flag($value);
        update_option('parcs_ht_export_revision', 1, false);
        if (!wp_next_scheduled('parcs_ht_pregenerate_exports')) wp_schedule_single_event(time() + 10, 'parcs_ht_pregenerate_exports');
    }
}, 10, 2);

add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, static function ($new_value, $old_value) {
    if (!is_array($new_value) || !is_admin() || !isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;
    $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
    if ($year === '' || !isset($new_value['seasons'][$year]) || (string)($new_value['seasons'][$year]['published'] ?? '0') === '1') return $new_value;
    foreach ((array)($old_value['seasons'] ?? array()) as $published_year => $season) {
        if ((string)$published_year === $year || !is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;
        if (!empty($season['tariffs']) && is_array($season['tariffs'])) { $new_value['tariffs'] = $season['tariffs']; break; }
    }
    return $new_value;
}, 50, 2);

add_action('wp_footer', static function () {
    if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;
    wp_enqueue_script('parcs-ht-frontend-i18n', PARCS_HT_URL . 'assets/frontend-i18n.js', array('parcs-ht-frontend'), PARCS_HT_VERSION, true);
    wp_enqueue_script('parcs-ht-display-state', PARCS_HT_URL . 'assets/display-state.js', array('parcs-ht-frontend'), PARCS_HT_VERSION, true);
    wp_enqueue_script('parcs-ht-status-sync', PARCS_HT_URL . 'assets/status-sync.js', array('parcs-ht-display-state','parcs-ht-slot-last-entry-frontend'), PARCS_HT_VERSION, true);
}, 2);

add_action('admin_enqueue_scripts', static function ($hook) {
    if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
    wp_enqueue_script('parcs-ht-display-state', PARCS_HT_URL . 'assets/display-state.js', array('parcs-ht-preview-engine'), PARCS_HT_VERSION, true);
    wp_enqueue_script('parcs-ht-admin-preview-enhanced', PARCS_HT_URL . 'assets/admin-preview-enhanced.js', array('parcs-ht-admin','parcs-ht-display-state'), PARCS_HT_VERSION, true);
}, 20);

add_action('admin_enqueue_scripts', static function ($hook) {
    if ($hook !== 'toplevel_page_parcs-horaires-tarifs' || !wp_script_is('parcs-ht-tariff-seasons-admin', 'enqueued')) return;
    $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : '';
    $settings = Parcs_HT_Defaults::settings($year);
    wp_add_inline_script('parcs-ht-tariff-seasons-admin', 'window.ParcsHTTariffSeasonAdmin=' . wp_json_encode(array('tariffs'=>(array)($settings['tariffs'] ?? array()))) . ';', 'before');
}, 90);

add_action('plugins_loaded', static function () {
    Parcs_HT_Bootstrap::init();
    if (is_admin()) {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin.php';
        Parcs_HT_Admin::init();
        Parcs_HT_Admin_Groups::init();
        Parcs_HT_Defaults::maybe_upgrade();
        if (get_option('parcs_ht_tariff_seasons_migrated_193', '0') !== '1') {
            $all = get_option(Parcs_HT_Defaults::OPTION, array());
            if (is_array($all) && !empty($all['seasons']) && is_array($all['seasons']) && isset($all['tariffs']) && is_array($all['tariffs'])) {
                $changed = false;
                foreach ($all['seasons'] as &$season) {
                    if (!is_array($season)) continue;
                    if (!isset($season['tariffs']) || !is_array($season['tariffs'])) { $season['tariffs'] = $all['tariffs']; $changed = true; }
                }
                unset($season);
                if ($changed) update_option(Parcs_HT_Defaults::OPTION, $all, false);
            }
            update_option('parcs_ht_tariff_seasons_migrated_193', '1', false);
        }
    } else {
        if (Parcs_HT_Defaults::has_popup_source_fast()) { require_once PARCS_HT_DIR . 'includes/class-parcs-ht-alerts.php'; Parcs_HT_Alerts::init(); }
    }
    Parcs_HT_Group_Quotes::init();
    Parcs_HT_Quote_Languages::init();
    Parcs_HT_Quote_Gate::init();
    Parcs_HT_Slot_Last_Entry::init();
    $doing_cron = function_exists('wp_doing_cron') && wp_doing_cron();
    if (is_admin() || $doing_cron) {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-verifier.php';
        Parcs_HT_Verifier::init();
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-health.php';
        Parcs_HT_Health::init();
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-updater.php';
        Parcs_HT_Updater::init();
    }
});
