<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Répare une seule fois les activations de devis historiques qui ont pu être
 * enregistrées à 0 lors de l'introduction des interrupteurs annuels.
 *
 * Les années futures ne sont jamais activées par cette migration : leur
 * interrupteur group_quotes_enabled reste entièrement explicite.
 */
final class Parcs_HT_Quote_Activation_Migration {
    const MARKER = 'parcs_ht_quote_activation_migrated_11518';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'run'), 8);
    }

    public static function run() {
        if (get_option(self::MARKER, '0') === '1') return;
        if (!current_user_can('manage_options')) return;
        if (!class_exists('Parcs_HT_Defaults') || !class_exists('Parcs_HT_Group_Quotes')) return;

        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($all) || empty($all['seasons']) || !is_array($all['seasons'])) {
            update_option(self::MARKER, '1', false);
            return;
        }

        $raw_quotes = get_option(Parcs_HT_Group_Quotes::OPTION, array());
        $raw_quotes = is_array($raw_quotes) ? $raw_quotes : array();
        $quote_settings = Parcs_HT_Group_Quotes::settings(false);
        $current_year = (string)wp_date('Y');
        $changed = false;

        foreach ($all['seasons'] as $year => &$season) {
            $year = (string)$year;
            if (!preg_match('/^20\\d{2}$/', $year) || !is_array($season)) continue;
            if ((int)$year > (int)$current_year) continue;
            if (!array_key_exists('group_quotes_enabled', $season) || (string)$season['group_quotes_enabled'] !== '0') continue;

            // Une ancienne saison publique disposant encore d'une vraie liaison
            // tarifaire de devis est considérée comme historiquement active.
            $historical_evidence = false;
            if (!empty($raw_quotes['tariff_bindings'][$year]) && is_array($raw_quotes['tariff_bindings'][$year])) {
                $historical_evidence = true;
            }
            if (!$historical_evidence && !empty($raw_quotes['seasons'][$year]) && is_array($raw_quotes['seasons'][$year])) {
                $legacy = $raw_quotes['seasons'][$year];
                $historical_evidence = true;
                foreach (array('child','adult','disability','companion') as $key) {
                    if (!isset($legacy[$key]) || !is_numeric(str_replace(',', '.', (string)$legacy[$key]))) {
                        $historical_evidence = false;
                        break;
                    }
                }
            }
            // Compatibilité 2026 : les premières versions du moteur pouvaient
            // fonctionner uniquement grâce aux valeurs par défaut, sans avoir
            // encore persisté de liaison annuelle explicite.
            if (!$historical_evidence && $year === $current_year && (string)($season['published'] ?? '0') === '1') {
                $historical_evidence = true;
            }
            if (!$historical_evidence) continue;

            $binding = Parcs_HT_Group_Quotes::binding_for_year($year, $quote_settings);
            if (!$binding) continue;

            $season['group_quotes_enabled'] = '1';
            $changed = true;
        }
        unset($season);

        if ($changed) update_option(Parcs_HT_Defaults::OPTION, $all, false);
        update_option(self::MARKER, '1', false);
    }
}
