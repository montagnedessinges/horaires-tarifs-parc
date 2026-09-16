<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Règles publiques communes aux saisons visibles.
 *
 * Centralise l'ordre des années et les fenêtres d'affichage afin que le calendrier,
 * les tarifs visiteurs et les espaces groupes utilisent la même logique temporelle.
 */
final class Parcs_HT_Public_Visibility {
    public static function init() {
        // Parcs_HT_Display_Policy capture l'option brute en priorité 4. On applique
        // ensuite la fenêtre publique au calendrier historique sans écraser les données.
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'filter_calendar_window'), 8, 1);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'save_window'), 98, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 99);
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

    public static function in_window($year, $today = '') {
        $season = self::raw_season($year);
        if (!$season) return false;
        if ((string)($season['public_force_display'] ?? '0') === '1') return true;
        $today = self::clean_date($today);
        if ($today === '') $today = self::today();
        $from = self::clean_date($season['public_display_from'] ?? '');
        $until = self::clean_date($season['public_display_until'] ?? '');
        if ($from !== '' && $today < $from) return false;
        if ($until !== '' && $today > $until) return false;
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
        if (array_key_exists('public_force_display', $posted) && is_scalar($posted['public_force_display'])) {
            $new_value['seasons'][$year]['public_force_display'] = (string)$posted['public_force_display'] === '1' ? '1' : '0';
        } elseif (isset($old_value['seasons'][$year]['public_force_display'])) {
            $new_value['seasons'][$year]['public_force_display'] = $old_value['seasons'][$year]['public_force_display'];
        }
        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection admin en lecture seule.
        $all = Parcs_HT_Defaults::all_settings();
        if ($year === '' || empty($all['seasons'][$year])) foreach ((array)($all['seasons'] ?? array()) as $candidate => $unused) { $year = (string)$candidate; break; }
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        wp_enqueue_script('parcs-ht-public-visibility-admin', PARCS_HT_URL . 'assets/public-visibility-admin.js', array('parcs-ht-display-policy-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-public-visibility-admin', 'window.ParcsHTPublicVisibility=' . wp_json_encode(array(
            'from'=>self::clean_date($season['public_display_from'] ?? ''),
            'until'=>self::clean_date($season['public_display_until'] ?? ''),
            'force'=>(string)($season['public_force_display'] ?? '0'),
        )) . ';', 'before');
    }

    public static function filter_calendar_window($value) {
        if (is_admin() || !is_array($value) || empty($value['seasons']) || !is_array($value['seasons'])) return $value;
        // Ne rappelle jamais get_option() depuis ce filtre : cela provoquerait une récursion.
        $today = self::today_from_settings($value);
        foreach ($value['seasons'] as $year => &$season) {
            if (!is_array($season)) continue;
            if (!self::in_window((string)$year, $today)) $season['published'] = '0';
        }
        unset($season);
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
            $enabled = array_key_exists('calendar_visible', $season)
                ? (string)$season['calendar_visible'] === '1'
                : (string)($season['published'] ?? '0') === '1';
            if ($enabled && self::in_window($year)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function retail_years() {
        $years = array();
        $current = self::current_year();
        foreach (self::all_years() as $year) {
            $season = self::raw_season($year);
            $enabled = array_key_exists('retail_tariffs_visible', $season)
                ? (string)$season['retail_tariffs_visible'] === '1'
                : ((string)($season['published'] ?? '0') === '1' && $year === $current);
            if ($enabled && self::in_window($year)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function group_schedule_years() {
        $years = array();
        foreach (self::all_years() as $year) {
            $season = self::raw_season($year);
            if ((string)($season['groups_schedule_visible'] ?? '0') === '1' && self::in_window($year)) $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function group_tariff_years() {
        $years = array();
        foreach (self::all_years() as $year) {
            if (!class_exists('Parcs_HT_Group_Tariff_Settings') || !Parcs_HT_Group_Tariff_Settings::is_published($year)) continue;
            if (!self::in_window($year)) continue;
            $display = Parcs_HT_Group_Tariff_Settings::settings($year);
            $from = self::clean_date($display['display_from'] ?? '');
            if ($from !== '' && self::today() < $from) continue;
            $years[] = $year;
        }
        return self::order_years($years);
    }

    public static function tariff_years() {
        return self::order_years(array_merge(self::retail_years(), self::group_tariff_years()));
    }

    public static function default_year($years, $requested = '') {
        $years = self::order_years($years);
        $requested = (string)$requested;
        if ($requested !== '' && in_array($requested, $years, true)) return $requested;
        return $years ? (string)$years[0] : '';
    }
}
