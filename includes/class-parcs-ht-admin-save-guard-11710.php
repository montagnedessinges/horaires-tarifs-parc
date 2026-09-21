<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Protection anti-troncature des formulaires d'administration.
 *
 * Plusieurs écrans métier utilisent volontairement l'absence d'une ligne comme
 * une suppression. Si PHP tronque le POST (max_input_vars/post_max_size), cette
 * absence ne doit jamais être confondue avec une suppression demandée.
 *
 * Le script admin ajoute donc un marqueur à la FIN des données du formulaire.
 * S'il n'arrive pas au serveur, l'écriture est annulée avant tout handler métier.
 */
final class Parcs_HT_Admin_Save_Guard_11710 {
    const FIELD = 'parcs_ht_11710_complete';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'guard_admin_post'), 0);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 100);
    }

    public static function protected_actions() {
        return array(
            'parcs_ht_save',
            'parcs_ht_save_schedule_1173',
            'parcs_ht_save_general_publication',
            'parcs_ht_save_general_park',
            'parcs_ht_save_global_appearance',
            'parcs_ht_save_public_content',
            'parcs_ht_save_group_display_1176',
            'parcs_ht_save_quote_binding_1177',
            'parcs_ht_save_quote_forms_1177',
            'parcs_ht_save_quote_gate_1177',
            'parcs_ht_save_quote_engine_1177',
            'parcs_ht_save_pedagogical_guides',
            'parcs_ht_save_guide_appearance',
            'parcs_ht_advent_create_campaign',
            'parcs_ht_advent_save_campaign',
            'parcs_ht_advent_save_content',
            'parcs_ht_advent_save_partner',
            'parcs_ht_advent_save_result',
            'parcs_ht_advent_import_csv',
            'parcs_ht_advent_apply_import',
            'parcs_ht_schedule_csv_import',
        );
    }

    public static function request_complete($action, $post = null) {
        $action = sanitize_key((string)$action);
        $post = is_array($post) ? $post : $_POST;
        if ($action === '' || !isset($post[self::FIELD]) || is_array($post[self::FIELD])) return false;
        $marker = sanitize_key(wp_unslash((string)$post[self::FIELD]));
        return $marker !== '' && hash_equals($action, $marker);
    }

    public static function guard_admin_post() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : '';
        if ($method !== 'POST') return;

        $action = isset($_POST['action']) && !is_array($_POST['action'])
            ? sanitize_key(wp_unslash((string)$_POST['action']))
            : '';
        if ($action === '' || !in_array($action, self::protected_actions(), true)) return;
        if (self::request_complete($action)) return;

        $max_vars = (string)ini_get('max_input_vars');
        $post_max = (string)ini_get('post_max_size');
        $details = array();
        if ($max_vars !== '') $details[] = 'max_input_vars=' . $max_vars;
        if ($post_max !== '') $details[] = 'post_max_size=' . $post_max;

        status_header(400);
        wp_die(
            '<h1>Enregistrement annulé</h1>' .
            '<p>Le formulaire reçu est incomplet. Aucune donnée n’a été modifiée afin d’éviter de supprimer ou remettre à zéro des réglages existants.</p>' .
            '<p>Revenez à la page précédente et réessayez. Si le problème se répète, augmentez les limites PHP du formulaire' .
            ($details ? ' (' . esc_html(implode(' · ', $details)) . ')' : '') . '.</p>',
            'Formulaire incomplet',
            array('response'=>400, 'back_link'=>true)
        );
    }

    private static function is_plugin_admin_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture du slug uniquement pour limiter l'asset.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page === 'parcs-horaires-tarifs') return true;
        return strpos($page, 'parcs-ht-') === 0;
    }

    public static function assets() {
        if (!current_user_can('manage_options') || !self::is_plugin_admin_page()) return;
        wp_enqueue_script(
            'parcs-ht-admin-save-guard-11710',
            PARCS_HT_URL . 'assets/admin-save-guard-11710.js',
            array(),
            PARCS_HT_VERSION,
            true
        );
        wp_add_inline_script(
            'parcs-ht-admin-save-guard-11710',
            'window.ParcsHTSaveGuard11710=' . wp_json_encode(array(
                'field'=>self::FIELD,
                'actions'=>self::protected_actions(),
            )) . ';',
            'before'
        );
    }
}
