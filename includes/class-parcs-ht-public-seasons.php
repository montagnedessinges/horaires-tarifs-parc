<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Visibilité publique multi-saisons et navigation annuelle.
 *
 * - Une seule saison visible : aucun changement visuel.
 * - Plusieurs saisons visibles : onglets d'années au-dessus des shortcodes concernés.
 * - public_display_until masque automatiquement une saison après la date configurée.
 * - Le paramètre public htp_year sélectionne une saison sans modifier les données enregistrées.
 * - Le Calendrier de l'Avent reste indépendant et peut exposer explicitement une campagne archivée.
 */
final class Parcs_HT_Public_Seasons {
    const QUERY_ARG = 'htp_year';

    private static $raw_main = null;
    private static $raw_group = null;
    private static $advent_target = '';

    public static function init() {
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'filter_main_option'), 5, 1);
        add_filter('option_' . Parcs_HT_Group_Tariff_Settings::OPTION, array(__CLASS__, 'filter_group_option'), 5, 1);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'save_display_until'), 96, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 96);
        add_filter('do_shortcode_tag', array(__CLASS__, 'wrap_year_tabs'), 20, 4);
        add_filter('pre_do_shortcode_tag', array(__CLASS__, 'intercept_advent_shortcode'), 8, 4);
        add_filter('option_' . Parcs_HT_Advent::OPTION, array(__CLASS__, 'filter_advent_option'), 5, 1);
        foreach (array(Parcs_HT_Advent::AJAX_DAY, Parcs_HT_Advent::AJAX_WORD) as $action) {
            add_action('wp_ajax_' . $action, array(__CLASS__, 'prepare_advent_archive_ajax'), 0);
            add_action('wp_ajax_nopriv_' . $action, array(__CLASS__, 'prepare_advent_archive_ajax'), 0);
        }
    }

    private static function today($settings = array()) {
        $timezone = 'Europe/Paris';
        if (is_array($settings) && !empty($settings['timezone'])) $timezone = (string)$settings['timezone'];
        try { $zone = new DateTimeZone($timezone); } catch (Exception $e) { $zone = new DateTimeZone('Europe/Paris'); }
        return wp_date('Y-m-d', null, $zone);
    }

    private static function clean_date($value) {
        $value = trim((string)$value);
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    public static function requested_year() {
        if (!isset($_GET[self::QUERY_ARG])) return '';
        $year = sanitize_text_field(wp_unslash($_GET[self::QUERY_ARG])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection publique en lecture seule.
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function season_is_visible($season, $today) {
        if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') return false;
        $until = self::clean_date($season['public_display_until'] ?? '');
        return $until === '' || $today <= $until;
    }

    public static function filter_main_option($value) {
        if (!is_array($value)) return $value;
        if (self::$raw_main === null) self::$raw_main = $value;
        if (is_admin()) return $value;
        if (empty($value['seasons']) || !is_array($value['seasons'])) return $value;

        $today = self::today($value);
        $requested = self::requested_year();
        $requested_visible = $requested !== '' && isset($value['seasons'][$requested]) && self::season_is_visible($value['seasons'][$requested], $today);

        foreach ($value['seasons'] as $year => &$season) {
            if (!is_array($season)) continue;
            if (!self::season_is_visible($season, $today)) {
                $season['published'] = '0';
                continue;
            }
            if ($requested_visible && (string)$year !== $requested) $season['published'] = '0';
        }
        unset($season);
        return $value;
    }

    public static function filter_group_option($value) {
        if (!is_array($value)) return $value;
        if (self::$raw_group === null) self::$raw_group = $value;
        if (is_admin()) return $value;

        $requested = self::requested_year();
        if ($requested === '' || empty($value['seasons']) || !is_array($value['seasons'])) return $value;
        $visible = self::visible_years(false);
        if (!in_array($requested, $visible, true) || empty($value['seasons'][$requested]) || !is_array($value['seasons'][$requested])) return $value;

        foreach ($value['seasons'] as $year => &$season) {
            if (!is_array($season)) continue;
            if ((string)$year === $requested) {
                $season['display_from'] = '2000-01-01';
            } else {
                $season['display_from'] = '2099-12-31';
            }
        }
        unset($season);
        return $value;
    }

    private static function raw_main() {
        if (self::$raw_main === null) get_option(Parcs_HT_Defaults::OPTION, array());
        return is_array(self::$raw_main) ? self::$raw_main : array();
    }

    private static function raw_group() {
        if (self::$raw_group === null) get_option(Parcs_HT_Group_Tariff_Settings::OPTION, array());
        return is_array(self::$raw_group) ? self::$raw_group : array();
    }

    public static function visible_years($groups_only = false) {
        $settings = self::raw_main();
        $today = self::today($settings);
        $years = array();
        foreach ((array)($settings['seasons'] ?? array()) as $year => $season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !self::season_is_visible($season, $today)) continue;
            if ($groups_only && !self::group_year_is_public((string)$year, $season)) continue;
            $years[] = (string)$year;
        }
        sort($years, SORT_NUMERIC);
        return $years;
    }

    private static function group_year_is_public($year, $season) {
        $store = self::raw_group();
        $row = isset($store['seasons'][$year]) && is_array($store['seasons'][$year]) ? $store['seasons'][$year] : array();
        if ((string)($row['published'] ?? '0') !== '1') return false;
        $tariffs = isset($season['tariffs']) && is_array($season['tariffs']) ? $season['tariffs'] : array();
        $has_row = false;
        foreach ((array)($tariffs['groups'] ?? array()) as $tariff) {
            if (is_array($tariff) && (string)($tariff['enabled'] ?? '1') === '1') { $has_row = true; break; }
        }
        if (!$has_row) return false;
        if (!isset($tariffs['columns']['groups'])) return true;
        foreach ((array)$tariffs['columns']['groups'] as $column) {
            if (!is_array($column) || (string)($column['visible'] ?? '1') === '0') continue;
            if (sanitize_key((string)($column['id'] ?? '')) !== '') return true;
        }
        return false;
    }

    private static function selected_year($years) {
        $requested = self::requested_year();
        if ($requested !== '' && in_array($requested, $years, true)) return $requested;
        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? '');
        if ($active !== '' && in_array($active, $years, true)) return $active;
        $current = wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        return $years ? (string)end($years) : '';
    }

    private static function tabs_markup($years, $selected) {
        if (count($years) < 2) return '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL nettoyée avant sortie.
        $base = remove_query_arg(self::QUERY_ARG, $uri ?: '/');
        $links = '';
        foreach ($years as $year) {
            $url = add_query_arg(self::QUERY_ARG, rawurlencode($year), $base);
            $active = $year === $selected;
            $links .= '<a class="parcs-ht-year-tab' . ($active ? ' is-active' : '') . '" href="' . esc_url($url) . '" role="tab" aria-selected="' . ($active ? 'true' : 'false') . '">' . esc_html($year) . '</a>';
        }
        static $style_done = false;
        $style = '';
        if (!$style_done) {
            $style_done = true;
            $style = '<style>.parcs-ht-year-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 18px}.parcs-ht-year-tab{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 16px;border:1px solid var(--htp-border,#d9d9d9);border-radius:999px;background:transparent;color:inherit;text-decoration:none;font-weight:600}.parcs-ht-year-tab.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}.parcs-ht-year-tab:focus-visible{outline:2px solid currentColor;outline-offset:2px}.parcs-ht-page.has-public-year-tabs .parcs-ht-year-list{display:none!important}</style>';
        }
        return $style . '<nav class="parcs-ht-year-tabs" role="tablist" aria-label="Année">' . $links . '</nav>';
    }

    public static function wrap_year_tabs($output, $tag, $attr, $m) {
        unset($attr, $m);
        if (is_admin() || !is_string($output) || $output === '') return $output;
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        $groups = $base === 'parc_tarifs_groupes';
        $core = in_array($base, array('parc_horaires_tarifs','parc_tableau_tarifs'), true);
        if (!$groups && !$core) return $output;
        $years = self::visible_years($groups);
        if (count($years) < 2) return $output;
        if ($base === 'parc_horaires_tarifs') {
            $output = preg_replace('/class="parcs-ht-page(\s|\")/', 'class="parcs-ht-page has-public-year-tabs$1', $output, 1);
        }
        return self::tabs_markup($years, self::selected_year($years)) . $output;
    }

    public static function save_display_until($new_value, $old_value, $option) {
        unset($old_value, $option);
        if (!is_admin() || !is_array($new_value) || !current_user_can('manage_options')) return $new_value;
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || !isset($new_value['seasons'][$year])) return $new_value;
        $raw = isset($_POST['settings']['general']['public_display_until']) ? wp_unslash($_POST['settings']['general']['public_display_until']) : '';
        $date = self::clean_date($raw);
        $new_value['seasons'][$year]['public_display_until'] = $date;
        if (isset($new_value['general']) && is_array($new_value['general'])) $new_value['general']['public_display_until'] = $date;
        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'administration en lecture seule.
        $all = Parcs_HT_Defaults::all_settings();
        if ($year === '' || !isset($all['seasons'][$year])) {
            foreach ((array)($all['seasons'] ?? array()) as $candidate => $unused) { $year = (string)$candidate; break; }
        }
        $until = $year !== '' && isset($all['seasons'][$year]) ? self::clean_date($all['seasons'][$year]['public_display_until'] ?? '') : '';
        wp_enqueue_script('parcs-ht-public-seasons-admin', PARCS_HT_URL . 'assets/public-seasons-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-public-seasons-admin', 'window.ParcsHTPublicSeasons=' . wp_json_encode(array('year'=>$year,'displayUntil'=>$until)) . ';', 'before');
    }

    private static function raw_advent_campaign($campaign_id) {
        $saved = get_option(Parcs_HT_Advent::OPTION, array());
        $campaigns = is_array($saved) && isset($saved['campaigns']) && is_array($saved['campaigns']) ? $saved['campaigns'] : array();
        return isset($campaigns[$campaign_id]) && is_array($campaigns[$campaign_id]) ? $campaigns[$campaign_id] : null;
    }

    private static function advent_campaign_is_public($campaign_id) {
        $campaign = self::raw_advent_campaign($campaign_id);
        if (!$campaign) return false;
        $status = (string)($campaign['statut_campagne'] ?? '');
        if (!in_array($status, array('active','archivee'), true)) return false;
        $park = Parcs_HT_Advent::installation_park_code();
        return $park !== '' && (string)($campaign['parc_code'] ?? '') === $park;
    }

    private static function latest_advent_campaign_id() {
        $saved = get_option(Parcs_HT_Advent::OPTION, array());
        $campaigns = is_array($saved) && isset($saved['campaigns']) && is_array($saved['campaigns']) ? $saved['campaigns'] : array();
        $park = Parcs_HT_Advent::installation_park_code();
        $active = array();
        $archives = array();
        foreach ($campaigns as $id => $campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park) continue;
            $status = (string)($campaign['statut_campagne'] ?? '');
            if (!in_array($status, array('active','archivee'), true)) continue;
            $key = sprintf('%04d|%s', (int)($campaign['annee'] ?? 0), sanitize_key((string)$id));
            if ($status === 'active') $active[$key] = sanitize_key((string)$id);
            else $archives[$key] = sanitize_key((string)$id);
        }
        if ($active) { krsort($active, SORT_NATURAL); return (string)reset($active); }
        if ($archives) { krsort($archives, SORT_NATURAL); return (string)reset($archives); }
        return '';
    }

    public static function intercept_advent_shortcode($return, $tag, $attr, $m) {
        unset($m);
        if ($return !== false || is_admin() || !class_exists('Parcs_HT_Advent')) return $return;
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        if (!in_array($base, array('parc_calendrier_avent','parc_reglement_avent'), true)) return false;
        $attr = is_array($attr) ? $attr : array();
        $campaign_id = '';
        if (!empty($attr['id'])) $campaign_id = sanitize_key((string)$attr['id']);
        elseif (!empty($attr['campagne'])) $campaign_id = sanitize_key((string)$attr['campagne']);
        else $campaign_id = self::latest_advent_campaign_id();
        if ($campaign_id === '' || !self::advent_campaign_is_public($campaign_id)) return '';

        $language = preg_match('/_(fr|en|de)$/', (string)$tag, $matches) ? $matches[1] : Parcs_HT_Schedule::language();
        self::$advent_target = $campaign_id;
        $mapped = $attr;
        $mapped['campagne'] = $campaign_id;
        $html = $base === 'parc_reglement_avent'
            ? Parcs_HT_Advent::render_rules($language, $mapped)
            : Parcs_HT_Advent::render_calendar($language, $mapped);
        self::$advent_target = '';
        return $html;
    }

    public static function filter_advent_option($value) {
        if (self::$advent_target === '' || !is_array($value) || empty($value['campaigns'][self::$advent_target]) || !is_array($value['campaigns'][self::$advent_target])) return $value;
        $status = (string)($value['campaigns'][self::$advent_target]['statut_campagne'] ?? '');
        if ($status === 'archivee') $value['campaigns'][self::$advent_target]['statut_campagne'] = 'active';
        return $value;
    }

    public static function prepare_advent_archive_ajax() {
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        if ($campaign_id !== '' && self::advent_campaign_is_public($campaign_id)) {
            $campaign = self::raw_advent_campaign($campaign_id);
            if ($campaign && (string)($campaign['statut_campagne'] ?? '') === 'archivee') self::$advent_target = $campaign_id;
        }
    }
}
