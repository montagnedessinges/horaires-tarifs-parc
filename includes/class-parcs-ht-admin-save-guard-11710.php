<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Protection des sauvegardes d'administration contre les POST tronqués.
 *
 * Les formulaires métier peuvent utiliser l'absence d'une ligne comme une
 * suppression volontaire. Une troncature PHP ne doit donc jamais être prise
 * pour une suppression. Les navigateurs compatibles envoient un snapshot JSON
 * compact qui contourne max_input_vars ; le marqueur final reste le filet de
 * sécurité si le snapshot ou le POST est incomplet.
 */
final class Parcs_HT_Admin_Save_Guard_11710 {
    const FIELD = 'parcs_ht_11710_complete';
    const SNAPSHOT_FIELD = 'parcs_ht_11710_snapshot';

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

    public static function request_complete($action, $post) {
        $action = sanitize_key((string)$action);
        $post = is_array($post) ? $post : array();
        if ($action === '' || !isset($post[self::FIELD]) || is_array($post[self::FIELD])) return false;
        $marker = sanitize_key(wp_unslash((string)$post[self::FIELD]));
        return $marker !== '' && hash_equals($action, $marker);
    }

    /**
     * Restaure un POST complet envoyé dans une variable JSON unique.
     * WordPress ajoute normalement des slashes à $_POST avant plugins_loaded ;
     * on reproduit donc ce format pour que les handlers historiques continuent
     * d'utiliser wp_unslash() sans changement.
     *
     * Cette méthode reçoit uniquement un tableau déjà autorisé par le contrôle
     * de capacité et de nonce effectué dans guard_admin_post().
     */
    public static function restore_snapshot($action, $post) {
        $action = sanitize_key((string)$action);
        $post = is_array($post) ? $post : array();
        if ($action === '' || !isset($post[self::SNAPSHOT_FIELD]) || is_array($post[self::SNAPSHOT_FIELD])) return false;

        $json = wp_unslash((string)$post[self::SNAPSHOT_FIELD]);
        if ($json === '') return false;
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return false;

        $snapshot_action = isset($decoded['action']) && !is_array($decoded['action'])
            ? sanitize_key((string)$decoded['action'])
            : '';
        if ($snapshot_action === '' || !hash_equals($action, $snapshot_action)) return false;

        $_POST = wp_slash($decoded);
        $_POST[self::FIELD] = wp_slash($action);
        return true;
    }

    private static function nonce_action($action, $year = '') {
        $action = sanitize_key((string)$action);
        if ($action === 'parcs_ht_schedule_csv_import') {
            $year = sanitize_text_field((string)$year);
            return preg_match('/^20\d{2}$/', $year) ? $action . '_' . $year : '';
        }
        return $action;
    }

    public static function guard_admin_post() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        $method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper(sanitize_text_field(wp_unslash((string)$_SERVER['REQUEST_METHOD'])))
            : '';
        if ($method !== 'POST') return;

        // Ces deux valeurs servent uniquement à sélectionner le nonce à vérifier.
        // Aucune donnée métier n'est lue ou écrite avant check_admin_referer().
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $action = isset($_POST['action']) && !is_array($_POST['action'])
            ? sanitize_key(wp_unslash((string)$_POST['action']))
            : '';
        $year_for_nonce = isset($_POST['season_year']) && !is_array($_POST['season_year'])
            ? sanitize_text_field(wp_unslash((string)$_POST['season_year']))
            : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ($action === '' || !in_array($action, self::protected_actions(), true)) return;
        $nonce_action = self::nonce_action($action, $year_for_nonce);
        if ($nonce_action === '') self::abort_incomplete_request('Le contexte de sécurité du formulaire est incomplet.');
        check_admin_referer($nonce_action);

        $posted = $_POST;
        if (isset($posted[self::SNAPSHOT_FIELD])) {
            if (self::restore_snapshot($action, $posted) && self::request_complete($action, $_POST)) return;
            self::abort_incomplete_request('Le snapshot complet du formulaire n’a pas pu être relu.');
        }

        if (self::request_complete($action, $posted)) return;
        self::abort_incomplete_request('Le marqueur de fin du formulaire n’a pas été reçu.');
    }

    private static function abort_incomplete_request($reason) {
        $max_vars = (string)ini_get('max_input_vars');
        $post_max = (string)ini_get('post_max_size');
        $details = array();
        if ($max_vars !== '') $details[] = 'max_input_vars=' . $max_vars;
        if ($post_max !== '') $details[] = 'post_max_size=' . $post_max;

        status_header(400);
        wp_die(
            '<h1>Enregistrement annulé</h1>' .
            '<p>' . esc_html((string)$reason) . ' Aucune donnée n’a été modifiée afin d’éviter de supprimer ou remettre à zéro des réglages existants.</p>' .
            '<p>Revenez à la page précédente et réessayez. Si le problème se répète, vérifiez les limites PHP du formulaire' .
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
                'snapshotField'=>self::SNAPSHOT_FIELD,
                'actions'=>self::protected_actions(),
            )) . ';',
            'before'
        );
    }
}
