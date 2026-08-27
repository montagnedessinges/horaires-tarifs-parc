<?php
/**
 * Plugin Name: Horaires et tarifs du parc
 * Description: Horaires, calendrier interactif, exceptions, alertes et tarifs multilingues pour les parcs.
 * Version: 1.9.0
 * Update URI: https://github.com/montagnedessinges/horaires-tarifs-parc
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Tanguy Huriez – Montagne des Singes
 * Text Domain: horaires-tarifs-parc
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PARCS_HT_VERSION', '1.9.0');
define('PARCS_HT_FILE', __FILE__);
define('PARCS_HT_DIR', plugin_dir_path(__FILE__));
define('PARCS_HT_URL', plugin_dir_url(__FILE__));

require_once PARCS_HT_DIR . 'includes/class-parcs-ht-defaults.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-schedule.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-bootstrap.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-slot-last-entry.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-http-ssl.php';

Parcs_HT_HTTP_SSL::init();

register_activation_hook(__FILE__, array('Parcs_HT_Defaults', 'activate'));
register_deactivation_hook(__FILE__, static function () {
    require_once PARCS_HT_DIR . 'includes/class-parcs-ht-health.php';
    Parcs_HT_Health::deactivate();
});

add_action('updated_option', static function ($option, $old_value, $value) {
    unset($old_value);
    if ($option === Parcs_HT_Defaults::OPTION && is_array($value)) {
        Parcs_HT_Defaults::refresh_popup_flag($value);
        update_option('parcs_ht_export_revision', (int) get_option('parcs_ht_export_revision', 0) + 1, false);
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

add_action('wp_footer', static function () {
    if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;
    wp_enqueue_script(
        'parcs-ht-status-sync',
        PARCS_HT_URL . 'assets/status-sync.js',
        array('parcs-ht-frontend', 'parcs-ht-slot-last-entry-frontend'),
        PARCS_HT_VERSION,
        true
    );
}, 2);

// Simulateur d'aperçu : administration uniquement.
add_action('admin_enqueue_scripts', static function ($hook) {
    if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
    wp_enqueue_script(
        'parcs-ht-admin-preview-enhanced',
        PARCS_HT_URL . 'assets/admin-preview-enhanced.js',
        array('parcs-ht-admin'),
        PARCS_HT_VERSION,
        true
    );
}, 20);

add_action('plugins_loaded', static function () {
    Parcs_HT_Bootstrap::init();

    if (is_admin()) {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin.php';
        Parcs_HT_Admin::init();
        Parcs_HT_Defaults::maybe_upgrade();
    } else {
        if (Parcs_HT_Defaults::has_popup_source_fast()) {
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-alerts.php';
            Parcs_HT_Alerts::init();
        }
    }

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
