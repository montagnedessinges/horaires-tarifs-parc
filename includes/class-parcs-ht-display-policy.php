<?php

if (!defined('ABSPATH')) { exit; }

/** Règles de visibilité publique, visibilité groupes et canaux tarifaires fixes. */
final class Parcs_HT_Display_Policy {
    private static $raw_main = null;
    private static $raw_group = null;
    private static $normalized_main = null;
    private static $forced_group_tariff_year = '';

    public static function init() {
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'filter_main_option'), 4, 1);
        add_filter('option_' . Parcs_HT_Group_Tariff_Settings::OPTION, array(__CLASS__, 'capture_group_option'), 2, 1);
        add_filter('option_' . Parcs_HT_Group_Tariff_Settings::OPTION, array(__CLASS__, 'filter_group_option'), 7, 1);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'save_controls'), 97, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 98);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_assets'), 35);


    }

    private static function clean_date($value) {
        $value = trim((string)$value);
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function today($settings) {
        $timezone = is_array($settings) && !empty($settings['timezone']) ? (string)$settings['timezone'] : 'Europe/Paris';
        try { $zone = new DateTimeZone($timezone); } catch (Exception $e) { $zone = new DateTimeZone('Europe/Paris'); }
        return wp_date('Y-m-d', null, $zone);
    }

    private static function labels() {
        return array(
            'online'=>array('fr'=>'En ligne','en'=>'Online','de'=>'Online'),
            'onsite'=>array('fr'=>'Sur place','en'=>'On site','de'=>'Vor Ort'),
        );
    }

    private static function source_ids($columns) {
        $out = array('online'=>'','onsite'=>'');
        $fallback = array();
        foreach (is_array($columns) ? $columns : array() as $column) {
            if (!is_array($column)) continue;
            $id = sanitize_key((string)($column['id'] ?? ''));
            if ($id === '') continue;
            $labels = isset($column['label']) && is_array($column['label']) ? $column['label'] : array();
            $text = strtolower($id . ' ' . implode(' ', array_map('strval', $labels)));
            $fallback[] = $id;
            if ($out['online'] === '' && preg_match('/online|web|internet|en ligne/', $text)) $out['online'] = $id;
            if ($out['onsite'] === '' && preg_match('/onsite|on-site|sur place|caisse|guichet|place/', $text)) $out['onsite'] = $id;
        }
        foreach ($fallback as $id) {
            if (in_array($id, $out, true)) continue;
            if ($out['onsite'] === '') $out['onsite'] = $id;
            elseif ($out['online'] === '') $out['online'] = $id;
        }
        return $out;
    }

    public static function normalize_tariffs($tariffs) {
        $tariffs = is_array($tariffs) ? $tariffs : array();
        if (!isset($tariffs['columns']) || !is_array($tariffs['columns'])) $tariffs['columns'] = array();
        $labels = self::labels();
        foreach (array('individual','reduced') as $group) {
            $old_columns = isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group]) ? $tariffs['columns'][$group] : array();
            $sources = self::source_ids($old_columns);
            $legacy_columns = $tariffs['_legacy_retail_1_15_9']['columns'][$group] ?? array();
            if ($legacy_columns) {
                $legacy_sources = self::source_ids($legacy_columns);
                foreach (array('onsite','online') as $channel) {
                    if ($sources[$channel] === $channel && $legacy_sources[$channel] !== '') $sources[$channel] = $legacy_sources[$channel];
                }
            }
            $tariffs['columns'][$group] = array(
                array('id'=>'onsite','label'=>$labels['onsite'],'visible'=>'1'),
                array('id'=>'online','label'=>$labels['online'],'visible'=>'1'),
            );
            if (!isset($tariffs[$group]) || !is_array($tariffs[$group])) $tariffs[$group] = array();
            foreach ($tariffs[$group] as &$row) {
                if (!is_array($row)) continue;
                if (!isset($row['cells']) || !is_array($row['cells'])) $row['cells'] = array();
                foreach (array('online','onsite') as $channel) {
                    if (isset($row['cells'][$channel]) && is_array($row['cells'][$channel])) continue;
                    $source = $sources[$channel];
                    if ($source !== '' && isset($row['cells'][$source]) && is_array($row['cells'][$source]) && trim((string)($row['cells'][$source]['value'] ?? '')) !== '') {
                        $row['cells'][$channel] = $row['cells'][$source];
                    } elseif ($channel === 'onsite' && isset($row['price']) && (string)$row['price'] !== '') {
                        $row['cells'][$channel] = array('value'=>(string)$row['price'],'old_value'=>'');
                    } elseif (!isset($row['cells'][$channel]) || !is_array($row['cells'][$channel])) {
                        $row['cells'][$channel] = array('value'=>'','old_value'=>'');
                    }
                }
            }
            unset($row);
            $has_online = false;
            foreach ($tariffs[$group] as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '1') === '1' && trim((string)($row['cells']['online']['value'] ?? '')) !== '') $has_online = true;
            }
            if (!$has_online) $tariffs['columns'][$group][1]['visible'] = '0';
        }
        return $tariffs;
    }

    private static function normalize_all_tariffs($value) {
        if (!is_array($value)) return $value;
        if (isset($value['tariffs']) && is_array($value['tariffs'])) $value['tariffs'] = self::normalize_tariffs($value['tariffs']);
        foreach ((array)($value['seasons'] ?? array()) as $year => $season) {
            if (!is_array($season) || !isset($season['tariffs']) || !is_array($season['tariffs'])) continue;
            $value['seasons'][$year]['tariffs'] = self::normalize_tariffs($season['tariffs']);
        }
        return $value;
    }

    public static function filter_main_option($value) {
        if (!is_array($value)) return $value;
        if (self::$raw_main !== $value) self::$normalized_main = null;
        self::$raw_main = $value;
        if (is_admin()) return $value;
        if (self::$normalized_main === null) self::$normalized_main = self::normalize_all_tariffs($value);
        $value = self::$normalized_main;
        if (empty($value['seasons']) || !is_array($value['seasons'])) return $value;
        $today = self::today($value);
        foreach ($value['seasons'] as &$season) {
            if (!is_array($season)) continue;
            if (array_key_exists('calendar_visible', $season)) {
                $season['published'] = (string)$season['calendar_visible'] === '1' ? '1' : '0';
                $season['public_display_until'] = '';
                continue;
            }
            if ((string)($season['published'] ?? '0') !== '1') continue;
            if ((string)($season['public_force_display'] ?? '0') === '1') {
                $season['public_display_until'] = '';
                continue;
            }
            $from = self::clean_date($season['public_display_from'] ?? '');
            if ($from !== '' && $today < $from) $season['published'] = '0';
        }
        unset($season);
        return $value;
    }

    public static function capture_group_option($value) {
        if (self::$raw_group === null && is_array($value)) self::$raw_group = $value;
        return $value;
    }

    public static function filter_group_option($value) {
        if (!is_array($value) || is_admin()) return $value;
        return $value;
    }

    private static function raw_main() {
        if (self::$raw_main === null) get_option(Parcs_HT_Defaults::OPTION, array());
        return is_array(self::$raw_main) ? self::$raw_main : array();
    }

    public static function retail_year_is_visible($year, $season = null) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return false;
        if (!is_array($season)) {
            $all = self::raw_main();
            $season = isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
        }
        if (!$season) return false;
        if (array_key_exists('retail_tariffs_visible', $season)) return (string)$season['retail_tariffs_visible'] === '1';
        return (string)($season['published'] ?? '0') === '1' && $year === wp_date('Y');
    }

    public static function retail_years() {
        $all = self::raw_main();
        $today = self::today($all);
        $years = array();
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            $year = (string)$year;
            if (!self::retail_year_is_visible($year, $season)) continue;
            $force = (string)($season['public_force_display'] ?? '0') === '1';
            if (!$force && !array_key_exists('retail_tariffs_visible', $season)) {
                $from = self::clean_date($season['public_display_from'] ?? '');
                $until = self::clean_date($season['public_display_until'] ?? '');
                if ($from !== '' && $today < $from) continue;
                if ($until !== '' && $today > $until) continue;
            }
            $years[] = $year;
        }
        sort($years, SORT_NUMERIC);
        return $years;
    }

    public static function group_schedule_years() {
        $years = array();
        $all = self::raw_main();
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
            if ((string)($season['groups_schedule_visible'] ?? '0') === '1') $years[] = (string)$year;
        }
        sort($years, SORT_NUMERIC);
        return $years;
    }

    public static function raw_season($year) {
        $all = self::raw_main();
        $year = (string)$year;
        return isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
    }

    public static function begin_group_tariff_year($year) {
        self::$forced_group_tariff_year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
    }

    public static function end_group_tariff_year() { self::$forced_group_tariff_year = ''; }

    private static function requested_group_year() {
        $year = isset($_GET['htp_group_year']) ? sanitize_text_field(wp_unslash($_GET['htp_group_year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection publique en lecture seule.
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    public static function prepare_group_tariff_year($return, $tag, $attr, $m) {
        unset($attr, $m);
        if ($return !== false || is_admin()) return $return;
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        if ($base !== 'parc_tarifs_groupes') return false;
        $year = self::requested_group_year();
        $published = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::published_years() : array();
        if ($year !== '' && in_array($year, $published, true)) self::begin_group_tariff_year($year);
        return false;
    }

    private static function group_year_tabs($years, $selected) {
        if (count($years) < 2) return '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL nettoyée avant sortie.
        $base = remove_query_arg('htp_group_year', $uri ?: '/');
        $html = '<nav class="parcs-ht-group-year-tabs" aria-label="Année des tarifs groupes">';
        foreach ($years as $year) {
            $url = add_query_arg('htp_group_year', $year, $base);
            $html .= '<a class="parcs-ht-year-tab' . ($year === $selected ? ' is-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($year) . '</a>';
        }
        return $html . '</nav>';
    }

    public static function wrap_group_tariff_years($output, $tag, $attr, $m) {
        unset($attr, $m);
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        if ($base !== 'parc_tarifs_groupes' || is_admin()) return $output;
        $years = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::published_years() : array();
        $requested = self::requested_group_year();
        $selected = $requested !== '' && in_array($requested, $years, true) ? $requested : ($years ? (string)end($years) : '');
        self::end_group_tariff_year();
        $output = preg_replace('/^(?:<style>.*?<\/style>)?<nav class="parcs-ht-year-tabs".*?<\/nav>/s', '', (string)$output, 1);
        return self::group_year_tabs($years, $selected) . $output;
    }

    public static function save_controls($new_value, $old_value, $option) {
        unset($option);
        if (!is_admin() || !is_array($new_value) || !current_user_can('manage_options')) return $new_value;
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || empty($new_value['seasons'][$year]) || !is_array($new_value['seasons'][$year])) return $new_value;

        $display_from = isset($_POST['settings']['general']['public_display_from']) ? sanitize_text_field(wp_unslash($_POST['settings']['general']['public_display_from'])) : '';
        $force_display = isset($_POST['settings']['general']['public_force_display']) ? sanitize_text_field(wp_unslash($_POST['settings']['general']['public_force_display'])) : '0';
        $groups_visible = isset($_POST['settings']['general']['groups_schedule_visible']) ? sanitize_text_field(wp_unslash($_POST['settings']['general']['groups_schedule_visible'])) : '0';
        // Missing controls mean a partial save, never an implicit OFF.
        $fields = array('calendar_visible','retail_tariffs_visible','group_quotes_enabled','group_tariffs_visible',
            'public_display_from','public_force_display','groups_schedule_visible');
        $posted = isset($_POST['settings']['general']) && is_array($_POST['settings']['general']) ? $_POST['settings']['general'] : array();
        foreach ($fields as $field) {
            if (array_key_exists($field, $posted) && is_scalar($posted[$field])) {
                $raw = sanitize_text_field(wp_unslash($posted[$field]));
                $new_value['seasons'][$year][$field] = $field === 'public_display_from' ? self::clean_date($raw) : ($raw === '1' ? '1' : '0');
            } elseif (isset($old_value['seasons'][$year][$field])) {
                $new_value['seasons'][$year][$field] = $old_value['seasons'][$year][$field];
            }
        }

        $backup_source = is_array(self::$raw_main) ? self::$raw_main : array();
        if (isset($backup_source['seasons'][$year]['tariffs']) && is_array($backup_source['seasons'][$year]['tariffs']) && !isset($new_value['seasons'][$year]['tariffs']['_legacy_retail_1_15_9'])) {
            $old_tariffs = $backup_source['seasons'][$year]['tariffs'];
            $new_value['seasons'][$year]['tariffs']['_legacy_retail_1_15_9'] = array(
                'columns'=>array(
                    'individual'=>$old_tariffs['columns']['individual'] ?? array(),
                    'reduced'=>$old_tariffs['columns']['reduced'] ?? array(),
                ),
                'individual'=>$old_tariffs['individual'] ?? array(),
                'reduced'=>$old_tariffs['reduced'] ?? array(),
            );
        }
        return $new_value;
    }

    public static function render_controls($year, $season) {
        $year = (string)$year;
        $calendar = (string)($season['published'] ?? '0') === '1';
        $today = self::today(array());
        if ((string)($season['public_force_display'] ?? '0') !== '1') {
            $from = self::clean_date($season['public_display_from'] ?? '');
            $until = self::clean_date($season['public_display_until'] ?? '');
            $calendar = $calendar && ($from === '' || $from <= $today) && ($until === '' || $until >= $today);
        }
        $defaults = array(
            'calendar_visible'=>$calendar,
            'retail_tariffs_visible'=>self::retail_year_is_visible($year, $season),
            'group_quotes_enabled'=>Parcs_HT_Group_Tariff_Settings::quote_enabled($year),
            'group_tariffs_visible'=>in_array($year, Parcs_HT_Group_Tariff_Settings::public_years(), true),
        );
        $labels = array(
            'calendar_visible'=>'Afficher le calendrier',
            'retail_tariffs_visible'=>'Afficher les tarifs visiteurs',
            'group_quotes_enabled'=>'Activer les tarifs groupes pour les devis',
            'group_tariffs_visible'=>'Afficher les tarifs groupes sur le site',
        );
        echo '<section class="htp-card htp-year-controls" aria-label="Affichage de l’année ' . esc_attr($year) . '"><h2>Affichage et devis — ' . esc_html($year) . '</h2><p>Ces quatre commandes sont indépendantes du statut général de la saison. Enregistrez pour appliquer vos choix.</p><div class="htp-year-controls-grid">';
        foreach ($labels as $key => $label) {
            $on = array_key_exists($key, $season) ? (string)$season[$key] === '1' : $defaults[$key];
            $name = 'settings[general][' . $key . ']';
            echo '<label class="htp-year-control"><span>' . esc_html($label . ' ' . $year) . '</span><select name="' . esc_attr($name) . '" aria-label="' . esc_attr($label . ' ' . $year) . '"><option value="1"' . ($on ? ' selected' : '') . '>OUI — activé</option><option value="0"' . (!$on ? ' selected' : '') . '>NON — désactivé</option></select></label>';
        }
        echo '</div></section>';
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’administration en lecture seule.
        $all = Parcs_HT_Defaults::all_settings();
        if ($year === '' || empty($all['seasons'][$year])) foreach ((array)($all['seasons'] ?? array()) as $candidate => $unused) { $year = (string)$candidate; break; }
        $season = $year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
        $retail_default = array_key_exists('retail_tariffs_visible', $season) ? (string)$season['retail_tariffs_visible'] : ($year === wp_date('Y') ? '1' : '0');
        wp_enqueue_script('parcs-ht-display-policy-admin', PARCS_HT_URL . 'assets/display-policy-admin.js', array('parcs-ht-tariff-seasons-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-display-policy-admin', 'window.ParcsHTDisplayPolicy=' . wp_json_encode(array(
            'displayFrom'=>self::clean_date($season['public_display_from'] ?? ''),
            'forceDisplay'=>(string)($season['public_force_display'] ?? '0'),
            'groupsScheduleVisible'=>(string)($season['groups_schedule_visible'] ?? '0'),
            'retailTariffsVisible'=>$retail_default,
        )) . ';', 'before');
    }

    public static function frontend_assets() {
        if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;

        wp_register_style('parcs-ht-retail-channels', false, array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-retail-channels');
        wp_add_inline_style('parcs-ht-retail-channels', '.parcs-ht-price-channel-label{display:block;font-size:.78em;font-weight:600;opacity:.72;margin-bottom:2px}.parcs-ht-group-year-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 18px}.parcs-ht-year-tab{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 16px;border:1px solid var(--htp-border,#d9d9d9);border-radius:999px;background:transparent;color:inherit;text-decoration:none;font-weight:600}.parcs-ht-year-tab.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}.parcs-ht-price-cell[hidden]{display:none!important}');
    }
}
