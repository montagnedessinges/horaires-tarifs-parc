<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Nettoyage final 1.17.10 des couches de navigation transitoires.
 *
 * Les anciennes URLs restent compatibles via les routeurs historiques, mais les
 * entrées principales n'utilisent plus les pages-ponts devenues inutiles.
 */
final class Parcs_HT_Admin_Cleanup_11710 {
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'route_obsolete_landings'), 5);
        add_action('admin_menu', array(__CLASS__, 'rebind_canonical_pages'), 900);
    }

    public static function route_obsolete_landings() {
        if (!is_admin() || !current_user_can('manage_options')) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'parcs-ht-communication' || !class_exists('Parcs_HT_Admin_Communication_1179')) return;

        $args = array('page'=>Parcs_HT_Admin_Communication_1179::POPUP_PAGE);
        foreach (array('updated','preserved') as $notice) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur de retour uniquement.
            if (isset($_GET[$notice])) $args[$notice] = '1';
        }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function rebind_canonical_pages() {
        if (!current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $parent = Parcs_HT_Admin::PAGE;

        // 1.17.4 dispose désormais d'un vrai écran métier. Le pont 1.17.2 ne doit
        // plus prendre la main sur le même slug.
        if (class_exists('Parcs_HT_Admin_Periods')) {
            remove_submenu_page($parent, Parcs_HT_Admin_Periods::PAGE);
            add_submenu_page(
                $parent,
                'Périodes, événements et exceptions',
                'Périodes & événements',
                'manage_options',
                Parcs_HT_Admin_Periods::PAGE,
                array('Parcs_HT_Admin_Periods', 'page')
            );
        }
    }
}
