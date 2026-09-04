<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aperçu administratif simple du vrai shortcode public.
 *
 * Le rendu est généré par le même moteur PHP que le frontend. Le seul réglage
 * propre à l'aperçu est la couleur de fond de simulation, conservée uniquement
 * dans le navigateur.
 */
final class Parcs_HT_Admin_Shortcode_Preview {
    const SCREEN = 'toplevel_page_parcs-horaires-tarifs';

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 30);
        add_action('admin_footer', array(__CLASS__, 'render_source'), 25);
    }

    public static function assets($hook) {
        if ($hook !== self::SCREEN) {
            return;
        }

        // Le vrai CSS public est utilisé afin que l'aperçu corresponde au shortcode.
        wp_enqueue_style(
            'parcs-ht-admin-shortcode-frontend',
            PARCS_HT_URL . 'assets/frontend.css',
            array(),
            PARCS_HT_VERSION
        );
        wp_enqueue_style(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.css',
            array('parcs-ht-admin', 'parcs-ht-admin-shortcode-frontend'),
            PARCS_HT_VERSION
        );
        wp_enqueue_script(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.js',
            array('parcs-ht-admin', 'parcs-ht-preview-engine'),
            PARCS_HT_VERSION,
            true
        );
    }

    public static function render_source() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== self::SCREEN) {
            return;
        }

        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
        $html = Parcs_HT_Shortcodes::render('page', 'fr', array());

        // render() enregistre aussi les assets publics. Dans l'administration,
        // frontend.js est déjà chargé sous le handle parcs-ht-preview-engine et
        // frontend.css est chargé ci-dessus : on évite donc tout doublon.
        wp_dequeue_script('parcs-ht-frontend');
        wp_dequeue_style('parcs-ht-frontend');

        echo '<div id="parcs-ht-real-shortcode-preview-source" hidden aria-hidden="true">';
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML produit par le moteur de shortcode interne, déjà échappé dans ses méthodes de rendu.
        echo '</div>';
    }
}
