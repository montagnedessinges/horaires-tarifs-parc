<?php

if (!defined('ABSPATH')) { exit; }

/** Correctifs de stabilisation introduits après la 1.15.10. */
final class Parcs_HT_Stability_11511 {
    public static function init() {
        if (class_exists('Parcs_HT_Public_Seasons')) {
            remove_filter('pre_do_shortcode_tag', array('Parcs_HT_Public_Seasons', 'prepare_year_scope'), 6);
            remove_filter('do_shortcode_tag', array('Parcs_HT_Public_Seasons', 'wrap_year_tabs'), 20);
        }

        add_filter('do_shortcode_tag', array(__CLASS__, 'stabilize_tariff_output'), 60, 4);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'), 40);

        // 1.17.1 : un seul référentiel d’apparence, stocké dans les réglages généraux.
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-global-appearance.php';
        Parcs_HT_Global_Appearance::init();

        // L’Administration générale reste isolée des moteurs métier pendant la refonte.
        if (is_admin()) {
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin-general.php';
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-admin-general-park.php';
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-global-appearance-admin.php';
            Parcs_HT_Admin_General::init();
            Parcs_HT_Admin_General_Park::init();
            Parcs_HT_Global_Appearance_Admin::init();
        }

        add_action('plugins_loaded', array(__CLASS__, 'disable_blocking_admin_verifier'), 999);
    }

    public static function disable_blocking_admin_verifier() {
        if (class_exists('Parcs_HT_Verifier')) {
            remove_action('admin_init', array('Parcs_HT_Verifier', 'maybe_verify_version_once'));
        }
    }

    public static function register_assets() {
        wp_register_script(
            'parcs-ht-stability-11511',
            PARCS_HT_URL . 'assets/stability-11511.js',
            array('parcs-ht-frontend'),
            PARCS_HT_VERSION,
            true
        );
        wp_register_style('parcs-ht-stability-11511', false, array('parcs-ht-frontend'), PARCS_HT_VERSION);
    }

    public static function stabilize_tariff_output($output, $tag, $attr, $m) {
        return $output;
    }
}
