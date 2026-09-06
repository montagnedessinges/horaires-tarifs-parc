<?php

if (!defined('ABSPATH')) { exit; }

/** Réglages publics propres aux tarifs groupes, enregistrés par saison. */
final class Parcs_HT_Group_Tariff_Settings {
    const OPTION = 'parcs_ht_group_tariff_settings';
    const STORE_VERSION = 1;

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'ensure_store'), 6);
    }

    private static function clean_translations($value, $textarea = false) {
        $value = is_array($value) ? $value : array();
        $out = array('fr'=>'','en'=>'','de'=>'');
        foreach ($out as $lang => $unused) {
            $raw = (string)($value[$lang] ?? '');
            $out[$lang] = $textarea ? sanitize_textarea_field(wp_unslash($raw)) : sanitize_text_field(wp_unslash($raw));
        }
        return $out;
    }

    private static function clean_urls($value) {
        $value = is_array($value) ? $value : array();
        $out = array('fr'=>'','en'=>'','de'=>'');
        foreach ($out as $lang => $unused) $out[$lang] = esc_url_raw((string)($value[$lang] ?? ''));
        return $out;
    }

    public static function defaults($year = '') {
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : (string)wp_date('Y');
        return array(
            'published'=>'0',
            'show_heading'=>'1',
            'title'=>array('fr'=>'','en'=>'','de'=>''),
            'intro'=>array('fr'=>'','en'=>'','de'=>''),
            'show_future_notice'=>'1',
            'future_year'=>(string)((int)$year + 1),
            'future_notice'=>array('fr'=>'','en'=>'','de'=>''),
            'show_quote_button'=>'1',
            'button_label'=>array(
                'fr'=>'Faire une demande de devis',
                'en'=>'Request a quote',
                'de'=>'Angebot anfordern',
            ),
            'button_url'=>array('fr'=>'','en'=>'','de'=>''),
        );
    }

    public static function store() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        if (!isset($saved['seasons']) || !is_array($saved['seasons'])) $saved['seasons'] = array();
        $saved['version'] = self::STORE_VERSION;
        return $saved;
    }

    public static function ensure_store() {
        if (!current_user_can('manage_options')) return;
        $existing = get_option(self::OPTION, null);
        if (is_array($existing) && (int)($existing['version'] ?? 0) >= self::STORE_VERSION && isset($existing['seasons']) && is_array($existing['seasons'])) return;

        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        $store = array('version'=>self::STORE_VERSION,'seasons'=>array());
        foreach ((array)(is_array($all) ? ($all['seasons'] ?? array()) : array()) as $year => $season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
            $row = self::defaults((string)$year);
            $has_groups = !empty($season['tariffs']['groups']) && is_array($season['tariffs']['groups']);
            // Migration prudente : seules les saisons déjà publiées avec une grille groupes
            // deviennent publiées dans ce nouveau sous-statut.
            $row['published'] = ((string)($season['published'] ?? '0') === '1' && $has_groups) ? '1' : '0';
            $general = is_array($all['general'] ?? null) ? $all['general'] : array();
            if (isset($general['groups_url']) && is_array($general['groups_url'])) $row['button_url'] = self::clean_urls($general['groups_url']);
            if (isset($general['groups_button_label']) && is_array($general['groups_button_label'])) {
                $labels = self::clean_translations($general['groups_button_label']);
                foreach ($labels as $lang => $label) if ($label !== '') $row['button_label'][$lang] = $label;
            }
            $store['seasons'][(string)$year] = $row;
        }
        update_option(self::OPTION, $store, false);
    }

    public static function settings($year) {
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : (string)wp_date('Y');
        $store = self::store();
        $saved = isset($store['seasons'][$year]) && is_array($store['seasons'][$year]) ? $store['seasons'][$year] : array();
        return array_replace_recursive(self::defaults($year), $saved);
    }

    public static function save($year, $raw) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return false;
        $raw = is_array($raw) ? $raw : array();
        $future_year = isset($raw['future_year']) ? sanitize_text_field(wp_unslash($raw['future_year'])) : (string)((int)$year + 1);
        if (!preg_match('/^20\d{2}$/', $future_year)) $future_year = (string)((int)$year + 1);
        $clean = array(
            'published'=>isset($raw['published']) && (string)$raw['published'] === '1' ? '1' : '0',
            'show_heading'=>isset($raw['show_heading']) && (string)$raw['show_heading'] === '1' ? '1' : '0',
            'title'=>self::clean_translations($raw['title'] ?? array()),
            'intro'=>self::clean_translations($raw['intro'] ?? array(), true),
            'show_future_notice'=>isset($raw['show_future_notice']) && (string)$raw['show_future_notice'] === '1' ? '1' : '0',
            'future_year'=>$future_year,
            'future_notice'=>self::clean_translations($raw['future_notice'] ?? array(), true),
            'show_quote_button'=>isset($raw['show_quote_button']) && (string)$raw['show_quote_button'] === '1' ? '1' : '0',
            'button_label'=>self::clean_translations($raw['button_label'] ?? array()),
            'button_url'=>self::clean_urls($raw['button_url'] ?? array()),
        );
        $store = self::store();
        $store['seasons'][$year] = $clean;
        update_option(self::OPTION, $store, false);
        $stored = self::settings($year);
        return wp_json_encode($clean) === wp_json_encode(array_intersect_key($stored, $clean));
    }

    private static function raw_all_settings() {
        $saved = get_option(Parcs_HT_Defaults::OPTION, array());
        return is_array($saved) ? $saved : array();
    }

    public static function is_published($year) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return false;
        $all = self::raw_all_settings();
        $season = isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : null;
        if (!$season || (string)($season['published'] ?? '0') !== '1') return false;
        if (empty($season['tariffs']['groups']) || !is_array($season['tariffs']['groups'])) return false;
        return (string)(self::settings($year)['published'] ?? '0') === '1';
    }

    public static function published_years() {
        $all = self::raw_all_settings();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) if (self::is_published((string)$year)) $years[] = (string)$year;
        sort($years, SORT_NUMERIC);
        return $years;
    }

    public static function public_year() {
        $years = self::published_years();
        if (!$years) return '';
        $current = (string)wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        $past = array_values(array_filter($years, static function ($year) use ($current) { return (int)$year <= (int)$current; }));
        if ($past) return (string)end($past);
        return (string)$years[0];
    }

    public static function default_title($language, $year) {
        $labels = array(
            'fr'=>'Tarifs groupes',
            'en'=>'Group rates',
            'de'=>'Gruppentarife',
        );
        return ($labels[$language] ?? $labels['fr']) . ($year !== '' ? ' ' . $year : '');
    }

    public static function default_future_notice($language, $year) {
        if ($language === 'en') return 'Group rates for ' . $year . ' are not available yet. They will be displayed here as soon as they are published.';
        if ($language === 'de') return 'Die Gruppentarife für ' . $year . ' sind noch nicht verfügbar. Sie werden hier angezeigt, sobald sie veröffentlicht sind.';
        return 'Les tarifs groupes ' . $year . ' ne sont pas encore disponibles. Ils seront affichés ici dès leur publication.';
    }
}
