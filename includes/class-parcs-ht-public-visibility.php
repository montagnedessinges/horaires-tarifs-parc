<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Visibilité publique annuelle.
 *
 * Avant la date d'apparition, chaque interrupteur reste manuel. À partir de la
 * date d'apparition, tous les modules de l'année sont actifs. À partir de la date
 * de disparition, tous les modules sont inactifs. La disparition est prioritaire.
 */
final class Parcs_HT_Public_Visibility {
    public static function init() {
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'filter_calendar_window'), 8, 1);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'save_window'), 98, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 99);
        if (class_exists('Parcs_HT_Group_Quotes')) {
            add_filter('option_' . Parcs_HT_Group_Quotes::STATE_OPTION, array(__CLASS__, 'filter_quote_state'), 20, 1);
        }
    }

    private static function clean_date($value) {
        $value = trim((string)$value);
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function all_settings() {
        return Parcs_HT_Defaults::all_settings();
    }

    private static function today_from_settings($settings) {
        $settings = is_array($settings) ? $settings : array();
        $timezone = class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::timezone($settings) : (string)($settings['timezone'] ?? 'Europe/Paris');
        try { $zone = new DateTimeZone($timezone); } catch (Exception $e) { $zone = new DateTimeZone('Europe/Paris'); }
        return wp_date('Y-m-d', null, $zone);
    }

    public static function today() {
        return self::today_from_settings(self::all_settings());
    }

    public static function current_year() {
        return substr(self::today(), 0, 4);
    }

    public static function raw_season($year) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return array();
        if (class_exists('Parcs_HT_Display_Policy')) {
            $season = Parcs_HT_Display_Policy::raw_season($year);
            if (is_array($season) && $season) return $season;
        }
        $all = self::all_settings();
        return isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
    }

    private static function all_years() {
        $all = self::all_settings();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) $years[] = (string)$year;
        }
        return self::order_years($years);
    }

    public static function scheduled_state($year, $today = '') {
        $season = self::raw_season($year);
        if (!$season) return 'off';
        return self::scheduled_state_for_season($season, $today);
    }

    private static function scheduled_state_for_season($season, $today = '') {
        $season = is_array($season) ? $season : array();
        $today = self::clean_date($today);
        if ($today === '') $today = self::today();
        $from = self::clean_date($season['public_display_from'] ?? '');
        $until = self::clean_date($season['public_display_until'] ?? '');
        if ($until !== '' && $today >= $until) return 'off';
        if ($from !== '' && $today >= $from) return 'on';
        return 'manual';
    }

    private static function module_visible_for_season($season, $flag, $fallback, $today = '') {
        $state = self::scheduled_state_for_season($season, $today);
        if ($state === 'off') return false;
        if ($state === 'on') return true;
        if (array_key_exists($flag, $season)) return (string)$season[$flag] === '1';
        return (bool)$fallback;
    }

    public static function module_visible($year, $flag, $fallback = false, $today = '') {
        $season = self::raw_season($year);
        if (!$season) return false;
        return self::module_visible_for_season($season, (string)$flag, (bool)$fallback, $today);
    }

    /** Compatibilité avec les anciens appels : fenêtre stricte apparition/retrait. */
    public static function in_window($year, $today = '') {
        $season = self::raw_season($year);
        if (!$season) return false;
        $today = self::clean_date($today);
        if ($today === '') $today = self::today();
        $from = self::clean_date($season['public_display_from'] ?? '');
        $until = self::clean_date($season['public_display_until'] ?? '');
        if ($from !== '' && $today < $from) return false;
        if ($until !== '' && $today >= $until) return false;
        return true;
    }

    public static function save_window($new_value, $old_value, $option) {
        unset($option);
        if (!is_admin() || !is_array($new_value) || !current_user_can('manage_options')) return $new_value;
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || empty($new_value['seasons'][$year]) || !is_array($new_value['seasons'][$year])) return $new_value;
        $posted = isset($_POST['settings']['general']) && is_array($_POST['settings']['general']) ? map_deep(wp_unslash($_POST['settings']['general']), 'sanitize_text_field') : array();
        foreach (array('public_display_from','public_display_until') as $field) {
            if (array_key_exists($field, $posted) && is_scalar($posted[$field])) $new_value['seasons'][$year][$field] = self::clean_date($posted[$field]);
            elseif (isset($old_value['seasons'][$year][$field])) $new_value['seasons'][$year][$field] = $old_value['seasons'][$year][$field];
        }
        if (isset($old_value['seasons'][$year]['public_force_display'])) {
            $new_value['seasons'][$year]['public_force_display'] = $old_value['seasons'][$year]['public_force_display'];
        }
        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.
        $all = Parcs_HT_Defaults::all_settings();
        if ($year === '' || empty($all['seasons'][$year])) foreach ((array)($all['seasons'] ?? array()) as $candidate => $unused) { $year = (string)$candidate; break; }
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        wp_enqueue_script('parcs-ht-public-visibility-admin', PARCS_HT_URL . 'assets/public-visibility-admin.js', array('parcs-ht-display-policy-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-public-visibility-admin', 'window.ParcsHTPublicVisibility=' . wp_json_encode(array(
            'from'=>self::clean_date($season['public_display_from'] ?? ''),
            'until'=>self::clean_date($season['public_display_until'] ?? ''),
        )) . ';', 'before');
    }

    /**
     * Le calendrier historique lit encore `published`. On lui fournit l'état effectif
     * calculé depuis la saison brute capturée avant les filtres de compatibilité.
     */
    public static function filter_calendar_window($value) {
        if (is_admin() || !is_array($value) || empty($value['seasons']) || !is_array($value['seasons'])) return $value;
        $today = self::today_from_settings($value);
        foreach ($value['seasons'] as $year => &$season) {
            if (!is_array($season)) continue;
            $raw = class_exists('Parcs_HT_Display_Policy') ? Parcs_HT_Display_Policy::raw_season((string)$year) : $season;
            if (!is_array($raw) || !$raw) $raw = $season;
            $fallback = array_key_exists('calendar_visible', $raw)
                ? (string)$raw['calendar_visible'] === '1'
                : (string)($raw['published'] ?? '0') === '1';
            $season['published'] = self::module_visible_for_season($raw, 'calendar_visible', $fallback, $today) ? '1' : '0';
            $season['public_display_from'] = self::clean_date($raw['public_display_from'] ?? '');
            $season['public_display_until'] = self::clean_date($raw['public_display_until'] ?? '');
        }
        unset($season);
        return $value;
    }

    /** Applique la même règle aux devis, y compris pendant les requêtes AJAX CF7. */
    public static function filter_quote_state($value) {
        $doing_ajax = function_exists('wp_doing_ajax') && wp_doing_ajax();
        if (is_admin() && !$doing_ajax) return $value;
        if (!is_array($value)) $value = array();
        if (!isset($value['years']) || !is_array($value['years'])) $value['years'] = array();
        foreach (self::all_years() as $year) {
            $fallback = isset($value['years'][$year]['enabled']) && (string)$value['years'][$year]['enabled'] === '1';
            $value['years'][$year] = array('enabled'=>self::module_visible($year, 'group_quotes_enabled', $fallback) ? '1' : '0');
        }
        return $value;
    }

    public static function order_years($years) {
        $years = array_values(array_unique(array_filter(array_map('strval', (array)$years), static function ($year) {
            return preg_match('/^20\d{2}$/', $year);
        })));
        sort($years, SORT_NUMERIC);
        if (!$years) return array();
        $current = self::current_year();
        $future = array();
        $past = array();
        $ordered = array();
        foreach ($years as $year) {
            if ($year === $current) $ordered[] = $year;
            elseif ((int)$year > (int)$current) $future[] = $year;
            else $past[] = $year;
        }
        sort($future, SORT_NUMERIC);
        rsort($past, SORT_NUMERIC);
        return array_values(array_unique(array_merge($ordered, $future, $past)));
    }

    public static function calendar_years() {
        $years = array();
        foreach (self::all_years() as $year) {
            $season = self::raw_season($year);
            $fallback = array_key_exists('calendar_visible', $season)
                ? (string)$season['calendar_visible'] === '1'
                : (string)($season['published'] ?? '0') === '1';
            if (self::module_visible($year, 'calendar_visible', $fallback)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function retail_years() {
        $years = array();
        $current = self::current_year();
        foreach (self::all_years() as $year) {
            $season = self::raw_season($year);
            $fallback = array_key_exists('retail_tariffs_visible', $season)
                ? (string)$season['retail_tariffs_visible'] === '1'
                : ((string)($season['published'] ?? '0') === '1' && $year === $current);
            if (self::module_visible($year, 'retail_tariffs_visible', $fallback)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function group_schedule_years() {
        $years = array();
        foreach (self::all_years() as $year) {
            $season = self::raw_season($year);
            $fallback = (string)($season['groups_schedule_visible'] ?? '0') === '1';
            if (self::module_visible($year, 'groups_schedule_visible', $fallback)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function group_tariff_years() {
        $years = array();
        foreach (self::all_years() as $year) {
            if (!class_exists('Parcs_HT_Group_Tariff_Settings') || !Parcs_HT_Group_Tariff_Settings::has_grid($year)) continue;
            $season = self::raw_season($year);
            $fallback = array_key_exists('group_tariffs_visible', $season)
                ? (string)$season['group_tariffs_visible'] === '1'
                : (string)(Parcs_HT_Group_Tariff_Settings::settings($year)['published'] ?? '0') === '1';
            if (self::module_visible($year, 'group_tariffs_visible', $fallback)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function quote_years() {
        $years = array();
        foreach (self::all_years() as $year) {
            $season = self::raw_season($year);
            $fallback = (string)($season['group_quotes_enabled'] ?? '0') === '1';
            if (self::module_visible($year, 'group_quotes_enabled', $fallback)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function tariff_years() {
        return self::order_years(array_merge(self::retail_years(), self::group_tariff_years()));
    }

    public static function group_portal_years() {
        return self::order_years(array_merge(self::group_tariff_years(), self::group_schedule_years()));
    }

    public static function default_year($years, $requested = '') {
        $years = self::order_years($years);
        $requested = (string)$requested;
        if ($requested !== '' && in_array($requested, $years, true)) return $requested;
        return $years ? (string)$years[0] : '';
    }
}
