<?php

if (!defined('ABSPATH')) { exit; }

/** Compatibilité de navigation pour l’écran Périodes introduit en 1.17.4. */
final class Parcs_HT_Admin_Periods_Routing {
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'route_legacy'), 0);
        add_action('admin_menu', array(__CLASS__, 'replace_menu_callback'), 61);
    }

    public static function route_legacy() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE || !in_array($tab, array('htp-holidays','htp-exceptions','htp-domain'), true)) return;

        $args = array('page'=>Parcs_HT_Admin_Periods::PAGE);
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if (preg_match('/^20\d{2}$/', $year)) $args['season'] = $year;
        foreach (array('updated','preserved','csv_imported') as $notice) {
            if (isset($_GET[$notice])) $args[$notice] = '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur visuel uniquement.
        }
        if ($tab === 'htp-exceptions') $args['section'] = 'exceptions';
        if ($tab === 'htp-domain') $args['section'] = 'access';
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function replace_menu_callback() {
        if (!class_exists('Parcs_HT_Admin')) return;
        remove_submenu_page(Parcs_HT_Admin::PAGE, Parcs_HT_Admin_Periods::PAGE);
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Périodes, événements et exceptions',
            'Périodes & événements',
            'manage_options',
            Parcs_HT_Admin_Periods::PAGE,
            array('Parcs_HT_Admin_Periods', 'page')
        );
    }
}
