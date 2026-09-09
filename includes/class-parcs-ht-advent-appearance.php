<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Apparence dédiée du Calendrier de l’Avent, stockée séparément par campagne. */
final class Parcs_HT_Advent_Appearance {
    const OPTION = 'parcs_ht_advent_appearance';
    const SCHEMA_VERSION = 1;
    const AJAX_ACTION = 'parcs_ht_advent_save_appearance';
    const NONCE_ACTION = 'parcs_ht_advent_appearance';

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_styles'), 20);
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 35);
            add_action('wp_ajax_' . self::AJAX_ACTION, array(__CLASS__, 'ajax_save'));
        }
    }

    public static function empty_palette() {
        return array(
            'primary' => '',
            'secondary' => '',
            'open_day' => '',
            'today' => '',
            'locked' => '',
            'special' => '',
        );
    }

    private static function empty_store() {
        return array(
            'schema_version' => self::SCHEMA_VERSION,
            'campaigns' => array(),
        );
    }

    private static function sanitize_color($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $color = sanitize_hex_color($value);
        return $color ? $color : '';
    }

    private static function sanitize_palette($raw) {
        $raw = is_array($raw) ? $raw : array();
        $palette = self::empty_palette();
        foreach (array_keys($palette) as $key) {
            $palette[$key] = self::sanitize_color($raw[$key] ?? '');
        }
        return $palette;
    }

    public static function store() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        $store = self::empty_store();
        $campaigns = isset($saved['campaigns']) && is_array($saved['campaigns']) ? $saved['campaigns'] : array();
        foreach ($campaigns as $campaign_id => $palette) {
            $campaign_id = sanitize_key((string) $campaign_id);
            if ($campaign_id === '') {
                continue;
            }
            $store['campaigns'][$campaign_id] = self::sanitize_palette($palette);
        }
        return $store;
    }

    public static function palette($campaign_id) {
        $campaign_id = sanitize_key((string) $campaign_id);
        $store = self::store();
        return isset($store['campaigns'][$campaign_id]) ? $store['campaigns'][$campaign_id] : self::empty_palette();
    }

    private static function palette_has_custom_color($palette) {
        foreach ((array) $palette as $color) {
            if ((string) $color !== '') {
                return true;
            }
        }
        return false;
    }

    private static function save_palette($campaign_id, $palette) {
        $campaign_id = sanitize_key((string) $campaign_id);
        if ($campaign_id === '') {
            return false;
        }
        $store = self::store();
        $palette = self::sanitize_palette($palette);
        if (self::palette_has_custom_color($palette)) {
            $store['campaigns'][$campaign_id] = $palette;
        } else {
            unset($store['campaigns'][$campaign_id]);
        }
        $store['schema_version'] = self::SCHEMA_VERSION;
        return update_option(self::OPTION, $store, false) || self::palette($campaign_id) === $palette;
    }

    private static function css_variable_map() {
        return array(
            'primary' => '--htp-advent-primary',
            'secondary' => '--htp-advent-secondary',
            'open_day' => '--htp-advent-open-day',
            'today' => '--htp-advent-today',
            'locked' => '--htp-advent-locked',
            'special' => '--htp-advent-special',
        );
    }

    private static function css_background_variable_map() {
        return array(
            'primary' => array('--htp-advent-primary-bg', '8%'),
            'secondary' => array('--htp-advent-secondary-bg', '5%'),
            'open_day' => array('--htp-advent-open-day-bg', '12%'),
            'today' => array('--htp-advent-today-bg', '10%'),
            'locked' => array('--htp-advent-locked-bg', '7%'),
            'special' => array('--htp-advent-special-bg', '8%'),
        );
    }

    public static function frontend_styles() {
        if (!wp_style_is('parcs-ht-advent', 'registered')) {
            return;
        }
        $store = self::store();
        $rules = array();
        $backgrounds = self::css_background_variable_map();
        foreach ($store['campaigns'] as $campaign_id => $palette) {
            $declarations = array();
            foreach (self::css_variable_map() as $key => $variable) {
                $color = isset($palette[$key]) ? self::sanitize_color($palette[$key]) : '';
                if ($color === '') {
                    continue;
                }
                $declarations[] = $variable . ':' . $color;
                if (isset($backgrounds[$key])) {
                    $declarations[] = $backgrounds[$key][0] . ':color-mix(in srgb,' . $color . ' ' . $backgrounds[$key][1] . ',transparent)';
                }
            }
            if (!$declarations) {
                continue;
            }
            $selector = '.parcs-ht-advent[data-campaign-id="' . $campaign_id . '"]';
            $rules[] = $selector . '{' . implode(';', $declarations) . '}';
        }
        if ($rules) {
            wp_add_inline_style('parcs-ht-advent', implode("\n", $rules));
        }
    }

    private static function picker_defaults() {
        $park = class_exists('Parcs_HT_Advent') ? Parcs_HT_Advent::installation_park_code() : '';
        return array(
            'primary' => $park === 'fds' ? '#ED8D1D' : '#E14B24',
            'secondary' => '#006050',
            'open_day' => '#32AA79',
            'today' => '#FBBD51',
            'locked' => '#383E42',
            'special' => '#E04B24',
        );
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') {
            return;
        }
        wp_enqueue_style(
            'parcs-ht-advent-appearance-admin',
            PARCS_HT_URL . 'assets/advent-appearance-admin.css',
            array('parcs-ht-advent-admin'),
            PARCS_HT_VERSION
        );
        wp_enqueue_script(
            'parcs-ht-advent-appearance-admin',
            PARCS_HT_URL . 'assets/advent-appearance-admin.js',
            array('parcs-ht-advent-admin'),
            PARCS_HT_VERSION,
            true
        );
        $store = self::store();
        wp_add_inline_script(
            'parcs-ht-advent-appearance-admin',
            'window.ParcsHTAdventAppearance=' . wp_json_encode(array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'action' => self::AJAX_ACTION,
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'campaigns' => $store['campaigns'],
                'pickerDefaults' => self::picker_defaults(),
            )) . ';',
            'before'
        );
    }

    public static function ajax_save() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Accès refusé.'), 403);
        }
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        if ($campaign_id === '' || !class_exists('Parcs_HT_Advent') || !Parcs_HT_Advent::campaign($campaign_id, true)) {
            wp_send_json_error(array('message' => 'Campagne introuvable pour cette installation.'), 404);
        }

        $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'save';
        if ($mode === 'reset') {
            $palette = self::empty_palette();
        } else {
            $raw = isset($_POST['colors']) && is_array($_POST['colors']) ? wp_unslash($_POST['colors']) : array();
            $palette = self::sanitize_palette($raw);
        }

        if (!self::save_palette($campaign_id, $palette)) {
            wp_send_json_error(array('message' => 'Les couleurs n’ont pas pu être enregistrées.'), 500);
        }

        wp_send_json_success(array(
            'campaignId' => $campaign_id,
            'settings' => self::palette($campaign_id),
            'message' => $mode === 'reset' ? 'Les couleurs héritées de l’extension sont rétablies.' : 'Les couleurs du Calendrier de l’Avent sont enregistrées.',
        ));
    }
}
