<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Garantit qu'un écran d'administration annuel lit toujours les tarifs de
 * l'année explicitement demandée.
 *
 * Depuis la séparation des écrans 1.17.5 / 1.17.6, plusieurs pages ne passent
 * plus par l'ancien slug principal. L'ancien filtre des saisons tarifaires ne
 * reconnaissait donc pas toujours leur paramètre `season` et pouvait exposer la
 * grille d'une autre année pendant une lecture ou un enregistrement AJAX.
 */
final class Parcs_HT_Admin_Year_Context {
    public static function init() {
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'select_exact_year_tariffs'), 30, 1);
    }

    private static function valid_year($year) {
        $year = trim((string)$year);
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function plugin_admin_page($page) {
        $page = (string)$page;
        return $page === 'parcs-horaires-tarifs' || strpos($page, 'parcs-ht-') === 0;
    }

    private static function requested_year() {
        if (!is_admin() || !current_user_can('manage_options')) return '';

        // Navigation annuelle : lecture seule, le paramètre ne modifie aucune donnée.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- contexte d'écran uniquement.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- contexte d'écran uniquement.
        $get_year = isset($_GET['season']) ? self::valid_year(sanitize_text_field(wp_unslash($_GET['season']))) : '';
        if ($get_year !== '' && self::plugin_admin_page($page)) return $get_year;

        // Les handlers d'administration valident eux-mêmes leur nonce avant toute
        // écriture. Ici nous ne faisons qu'aligner la lecture préparatoire sur l'année
        // envoyée par le formulaire ou l'appel AJAX.
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        if (strpos($action, 'parcs_ht_') !== 0) return '';
        if (isset($_POST['season_year'])) {
            $year = self::valid_year(sanitize_text_field(wp_unslash($_POST['season_year'])));
            if ($year !== '') return $year;
        }
        if (isset($_POST['year'])) {
            $year = self::valid_year(sanitize_text_field(wp_unslash($_POST['year'])));
            if ($year !== '') return $year;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        return '';
    }

    public static function select_exact_year_tariffs($value) {
        if (!is_array($value)) return $value;
        $year = self::requested_year();
        if ($year === '' || empty($value['seasons'][$year]) || !is_array($value['seasons'][$year])) return $value;

        $season = $value['seasons'][$year];
        if (isset($season['tariffs']) && is_array($season['tariffs'])) {
            $value['tariffs'] = $season['tariffs'];
        }
        if (!isset($value['general']) || !is_array($value['general'])) $value['general'] = array();
        $value['general']['year'] = $year;
        $value['active_season_year'] = $year;
        return $value;
    }
}
