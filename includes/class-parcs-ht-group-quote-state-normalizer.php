<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Réconciliation unique entre le nouvel état annuel des devis et les anciens
 * champs de saison affichés dans l'administration.
 *
 * Le moteur annuel reste la source de vérité. Cette migration évite qu'une
 * installation historique affiche 2026 = NON alors que 2026 a été restauré
 * comme actif à partir de ses données de devis valides.
 */
final class Parcs_HT_Group_Quote_State_Normalizer {
    const MARKER = 'parcs_ht_group_quote_state_normalized_v1';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'normalize'), 8);
    }

    public static function normalize() {
        if (!current_user_can('manage_options') || get_option(self::MARKER, '0') === '1') return;
        if (!class_exists('Parcs_HT_Group_Quotes') || !class_exists('Parcs_HT_Defaults')) return;

        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || !is_array($all['seasons'] ?? null)) {
            update_option(self::MARKER, '1', false);
            return;
        }

        $changed = false;
        foreach ($all['seasons'] as $year => &$season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
            $enabled = Parcs_HT_Group_Quotes::quote_enabled_for_year((string)$year) ? '1' : '0';
            if ((string)($season['group_quotes_enabled'] ?? '') === $enabled) continue;
            $season['group_quotes_enabled'] = $enabled;
            $changed = true;
        }
        unset($season);

        if ($changed) update_option(Parcs_HT_Defaults::OPTION, $all, false);
        update_option(self::MARKER, '1', false);
    }
}
