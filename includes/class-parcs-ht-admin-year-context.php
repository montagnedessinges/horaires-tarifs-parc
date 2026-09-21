<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Contexte annuel de l'administration.
 *
 * L'année administrée est un contexte de travail propre à l'utilisateur et ne
 * doit jamais être confondue avec la visibilité publique ni avec l'ancien
 * active_season_year. Dès qu'un administrateur choisit une année, elle est
 * conservée lors des changements d'écran et des retours après sauvegarde.
 */
final class Parcs_HT_Admin_Year_Context {
    const USER_META = 'parcs_ht_admin_year';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'sync_navigation_year'), 0);
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

    private static function annual_page($page, $tab = '') {
        $page = (string)$page;
        $tab = (string)$tab;
        if ($page === 'parcs-horaires-tarifs') {
            return !in_array($tab, array('htp-advent','htp-updates'), true);
        }
        if (strpos($page, 'parcs-ht-') !== 0) return false;
        return !in_array($page, array(
            'parcs-ht-communication',
            'parcs-ht-popup-1179',
            'parcs-ht-advent-1179',
            'parcs-ht-updates',
            'parcs-ht-public-content',
        ), true);
    }

    private static function raw_seasons($all) {
        return is_array($all) && isset($all['seasons']) && is_array($all['seasons']) ? $all['seasons'] : array();
    }

    private static function preferred_year($all) {
        $seasons = self::raw_seasons($all);
        if (!$seasons) return '';

        $user_id = function_exists('get_current_user_id') ? (int)get_current_user_id() : 0;
        if ($user_id > 0) {
            $remembered = self::valid_year(get_user_meta($user_id, self::USER_META, true));
            if ($remembered !== '' && isset($seasons[$remembered])) return $remembered;
        }

        $current = self::valid_year(wp_date('Y'));
        if ($current !== '' && isset($seasons[$current])) return $current;

        $active = self::valid_year($all['active_season_year'] ?? '');
        if ($active !== '' && isset($seasons[$active])) return $active;

        $years = array();
        foreach (array_keys($seasons) as $year) {
            $year = self::valid_year($year);
            if ($year !== '') $years[] = $year;
        }
        sort($years, SORT_NUMERIC);
        return $years ? (string)end($years) : '';
    }

    /**
     * Mémorise une année explicitement choisie ou complète une URL annuelle qui
     * a perdu son paramètre season. Cette préférence n'écrit jamais dans les
     * réglages publics du parc.
     */
    public static function sync_navigation_year() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        // Le fragment Avent est chargé comme une requête d'interface et n'a aucun contexte saisonnier.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routage en lecture seule.
        if (isset($_GET['advent_fragment'])) return;

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- contexte de navigation uniquement.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $requested = isset($_GET['season']) ? self::valid_year(sanitize_text_field(wp_unslash($_GET['season']))) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if (!self::annual_page($page, $tab)) return;

        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        $seasons = self::raw_seasons($all);
        if (!$seasons) return;

        if ($requested !== '' && isset($seasons[$requested])) {
            update_user_meta(get_current_user_id(), self::USER_META, $requested);
            return;
        }

        $year = self::preferred_year($all);
        if ($year === '') return;

        $args = array();
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- paramètres de navigation uniquement.
        foreach ((array)$_GET as $key => $value) {
            if (is_array($value)) continue;
            $key = sanitize_key((string)$key);
            if ($key === '' || $key === 'season') continue;
            $args[$key] = sanitize_text_field(wp_unslash((string)$value));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $args['season'] = $year;
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private static function requested_year() {
        if (!is_admin() || !current_user_can('manage_options')) return '';

        // Navigation annuelle : lecture seule, le paramètre ne modifie aucune donnée.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- contexte d'écran uniquement.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- contexte d'écran uniquement.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- contexte d'écran uniquement.
        $get_year = isset($_GET['season']) ? self::valid_year(sanitize_text_field(wp_unslash($_GET['season']))) : '';
        if ($get_year !== '' && self::plugin_admin_page($page)) return $get_year;

        // Les handlers d'administration valident eux-mêmes leur nonce avant toute
        // écriture. Ici nous ne faisons qu'aligner la lecture préparatoire sur l'année
        // envoyée par le formulaire ou l'appel AJAX.
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        if (strpos($action, 'parcs_ht_') === 0) {
            if (isset($_POST['season_year'])) {
                $year = self::valid_year(sanitize_text_field(wp_unslash($_POST['season_year'])));
                if ($year !== '') return $year;
            }
            if (isset($_POST['year'])) {
                $year = self::valid_year(sanitize_text_field(wp_unslash($_POST['year'])));
                if ($year !== '') return $year;
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if (self::annual_page($page, $tab)) {
            $remembered = self::valid_year(get_user_meta(get_current_user_id(), self::USER_META, true));
            if ($remembered !== '') return $remembered;
        }
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
        // Compatibilité de lecture uniquement : ne jamais persister ce contexte utilisateur.
        $value['active_season_year'] = $year;
        return $value;
    }
}
