<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Gestion explicite du statut d'une saison.
 * Enregistrer ne change jamais le statut ; publier/remettre en brouillon sont des actions volontaires.
 */
final class Parcs_HT_Season_Status {
    public static function init() {
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'enforce_explicit_status'), 95, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 95);
    }

    public static function enforce_explicit_status($new_value, $old_value, $option) {
        unset($option);
        if (!is_admin() || !is_array($new_value) || !is_array($old_value)) return $new_value;
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || !isset($new_value['seasons'][$year])) return $new_value;

        $action = isset($_POST['htp_season_action']) ? sanitize_key(wp_unslash($_POST['htp_season_action'])) : 'save';
        $old_status = isset($old_value['seasons'][$year]['published']) && (string)$old_value['seasons'][$year]['published'] === '1' ? '1' : '0';

        if ($action === 'publish') {
            $new_status = '1';
        } elseif ($action === 'draft') {
            $new_status = '0';
        } else {
            // Une simple sauvegarde ne publie ni ne dépublie jamais une saison.
            $new_status = $old_status;
        }

        $new_value['seasons'][$year]['published'] = $new_status;
        if (isset($new_value['general']) && is_array($new_value['general'])) $new_value['general']['published'] = $new_status;
        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’aperçu en lecture seule ; aucun enregistrement.
        $all = Parcs_HT_Defaults::all_settings();
        if ($year === '' || !isset($all['seasons'][$year])) {
            foreach ((array)($all['seasons'] ?? array()) as $candidate => $season) { $year = (string)$candidate; break; }
        }
        $published = $year !== '' && isset($all['seasons'][$year]) && (string)($all['seasons'][$year]['published'] ?? '0') === '1';
        wp_enqueue_script('parcs-ht-season-status-admin', PARCS_HT_URL . 'assets/season-status-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-season-status-admin', 'window.ParcsHTSeasonStatus=' . wp_json_encode(array('year'=>$year,'published'=>$published)) . ';', 'before');
    }
}
