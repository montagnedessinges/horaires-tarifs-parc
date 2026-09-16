<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Couche de compatibilité du statut historique d'une saison.
 * Depuis 1.15.13, la visibilité publique est pilotée par les commandes annuelles
 * (calendrier, tarifs visiteurs, horaires groupes, devis et tarifs groupes),
 * et non par un bouton global Brouillon / Publié.
 */
final class Parcs_HT_Season_Status {
    public static function init() {
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'enforce_explicit_status'), 95, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 95);
    }

    public static function enforce_explicit_status($new_value, $old_value, $option) {
        unset($option);
        if (!is_admin() || !is_array($new_value) || !is_array($old_value)) return $new_value;
        if (!current_user_can('manage_options')) return $new_value;

        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';

        // Lors d'une duplication, la nouvelle année reste entièrement inactive par défaut.
        if ($action === 'parcs_ht_duplicate_season') {
            $source = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
            if (!preg_match('/^20\d{2}$/', $source) || !wp_verify_nonce($nonce, 'parcs_ht_duplicate_season_' . $source)) return $new_value;

            foreach ((array)($new_value['seasons'] ?? array()) as $year => &$season) {
                if (isset($old_value['seasons'][$year]) || !is_array($season)) continue;
                foreach (array('calendar_visible','retail_tariffs_visible','groups_schedule_visible','group_quotes_enabled','group_tariffs_visible') as $flag) {
                    $season[$flag] = '0';
                }
                $season['published'] = '0';
            }
            unset($season);
            return $new_value;
        }

        if ($action !== 'parcs_ht_save') return $new_value;
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || !isset($new_value['seasons'][$year]) || !is_array($new_value['seasons'][$year])) return $new_value;

        $old_status = isset($old_value['seasons'][$year]['published']) && (string)$old_value['seasons'][$year]['published'] === '1' ? '1' : '0';
        $posted_general = isset($_POST['settings']['general']) && is_array($_POST['settings']['general']) ? wp_unslash($_POST['settings']['general']) : array();

        // Le vieux champ `published` reste seulement pour compatibilité interne.
        // Sa valeur suit désormais la commande annuelle « Afficher le calendrier ».
        if (array_key_exists('calendar_visible', $posted_general)) {
            $new_status = (string)$posted_general['calendar_visible'] === '1' ? '1' : '0';
        } else {
            $new_status = $old_status;
        }

        $new_value['seasons'][$year]['published'] = $new_status;
        if (isset($new_value['general']) && is_array($new_value['general'])) $new_value['general']['published'] = $new_status;
        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        // Le script ne reconstruit plus la barre d'enregistrement et n'ajoute plus
        // de statut Brouillon / Publié. Il masque uniquement l'ancien champ global
        // encore rendu par le formulaire historique.
        wp_enqueue_script('parcs-ht-season-status-admin', PARCS_HT_URL . 'assets/season-status-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
    }
}
