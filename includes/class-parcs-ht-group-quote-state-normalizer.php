<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Réconciliation entre l'état annuel canonique des saisons et l'ancien stockage
 * technique du moteur de devis.
 *
 * Depuis 1.17.2, `seasons[YYYY][group_quotes_enabled]` est le réglage manipulé par
 * la Vue d'ensemble et l'Administration générale. Le stockage technique v2 reste
 * conservé pour compatibilité avec le moteur 1.15.18, mais il ne doit plus pouvoir
 * contredire une valeur annuelle explicitement enregistrée.
 */
final class Parcs_HT_Group_Quote_State_Normalizer {
    const MARKER = 'parcs_ht_group_quote_state_normalized_v2';

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

        $state = get_option(Parcs_HT_Group_Quotes::STATE_OPTION, array());
        if (!is_array($state)) $state = array();
        if (!isset($state['years']) || !is_array($state['years'])) $state['years'] = array();
        $state['version'] = Parcs_HT_Group_Quotes::STATE_VERSION;

        $main_changed = false;
        $state_changed = false;

        foreach ($all['seasons'] as $year => &$season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;

            if (array_key_exists('group_quotes_enabled', $season)) {
                // Source canonique depuis 1.17.2 : ce que l'administrateur a réellement
                // enregistré pour cette année gagne sur l'ancien stockage technique.
                $enabled = (string)$season['group_quotes_enabled'] === '1' ? '1' : '0';
            } else {
                // Installation historique : on conserve une seule fois l'ancien état,
                // puis on l'inscrit dans la saison afin que les prochains enregistrements
                // disposent eux aussi d'une source annuelle explicite.
                $enabled = (string)($state['years'][$year]['enabled'] ?? '0') === '1' ? '1' : '0';
                $season['group_quotes_enabled'] = $enabled;
                $main_changed = true;
            }

            if ((string)($state['years'][$year]['enabled'] ?? '') !== $enabled) {
                $state['years'][$year] = array('enabled'=>$enabled);
                $state_changed = true;
            }
        }
        unset($season);

        if ($main_changed) update_option(Parcs_HT_Defaults::OPTION, $all, false);
        if ($state_changed) update_option(Parcs_HT_Group_Quotes::STATE_OPTION, $state, false);
        update_option(self::MARKER, '1', false);
    }
}
