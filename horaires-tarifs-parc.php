<?php
/**
 * Plugin Name: Horaires et tarifs du parc
 * Description: Horaires, calendrier interactif, exceptions, alertes et tarifs multilingues pour les parcs.
 * Version: 1.8.2
 * Update URI: https://github.com/montagnedessinges/horaires-tarifs-parc
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Tanguy Huriez – Montagne des Singes
 * Text Domain: horaires-tarifs-parc
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PARCS_HT_VERSION', '1.8.2');
define('PARCS_HT_FILE', __FILE__);
define('PARCS_HT_DIR', plugin_dir_path(__FILE__));
define('PARCS_HT_URL', plugin_dir_url(__FILE__));

// Le noyau léger est chargé partout. Les modules lourds d'administration et
// de mise à jour ne sont chargés que dans les contextes qui en ont besoin.
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-defaults.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-schedule.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-bootstrap.php';
require_once PARCS_HT_DIR . 'includes/class-parcs-ht-slot-last-entry.php';

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

add_action('plugins_loaded', static function () {
    // Les shortcodes restent enregistrés partout, mais leur gros moteur n'est chargé
    // que lorsqu'un shortcode/export est réellement utilisé.
    Parcs_HT_Bootstrap::init();
    Parcs_HT_Slot_Last_Entry::init();

    if (is_admin()) {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin.php';
        Parcs_HT_Admin::init();

        // Une migration n'a aucune raison de lire la grosse option du plugin sur
        // chaque page publique. En administration, on vérifie le schéma une fois.
        Parcs_HT_Defaults::maybe_upgrade();
    } else {
        // Un petit indicateur autoloadé évite de charger/analyser toute la configuration
        // des pop-up sur chaque page d'un site qui n'en utilise pas.
        if (Parcs_HT_Defaults::has_popup_source_fast()) {
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-alerts.php';
            Parcs_HT_Alerts::init();
        }
    }

    // Les contrôles de versions WordPress ont lieu dans l'administration ou via cron.
    // Le moteur GitHub n'est donc pas parsé sur les visites publiques ordinaires.
    $doing_cron = function_exists('wp_doing_cron') && wp_doing_cron();
    if (is_admin() || $doing_cron) {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-health.php';
        Parcs_HT_Health::init();
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-updater.php';
        Parcs_HT_Updater::init();
    }
});
