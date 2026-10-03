<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Pop-up visuels autonomes — 1.20.0.
 *
 * Les pop-up ne dépendent plus des événements, périodes ou exceptions.
 * Chaque langue possède son propre visuel, son lien et son texte alternatif.
 */
final class Parcs_HT_Popup_1200 {
    const OPTION = 'parcs_ht_popups_1200';
    const MIGRATION_OPTION = 'parcs_ht_popups_1200_migrated';

    public static function init() {
        add_action('init', array(__CLASS__, 'maybe_migrate'), 2);
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_public_assets'), 25);
    }

    public static function default_store() {
        return array(
            'version' => 1,
            'popups' => array(),
            'migration_count' => 0,
            'updated_at' => '',
        );
    }

    public static function default_popup() {
        return array(
            'id' => '',
            'internal_name' => '',
            'enabled' => '0',
            'published' => '0',
            'start' => '',
            'end' => '',
            'priority' => 10,
            'reappear_mode' => 'hours',
            'reappear_hours' => 24,
            'size_preset' => 'medium',
            'custom_width' => 620,
            'media' => array(
                'fr' => self::default_media(),
                'en' => self::default_media(),
                'de' => self::default_media(),
            ),
        );
    }

    private static function default_media() {
        return array(
            'image_id' => 0,
            'legacy_image_url' => '',
            'link_url' => '',
            'alt' => '',
        );
    }

    public static function store() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $saved = array_replace(self::default_store(), $saved);
        $saved['popups'] = isset($saved['popups']) && is_array($saved['popups']) ? array_values($saved['popups']) : array();
        return $saved;
    }

    public static function save_popups($popups) {
        $store = self::store();
        $store['version'] = 1;
        $store['popups'] = array_values(is_array($popups) ? $popups : array());
        $store['updated_at'] = gmdate('c');
        update_option(self::OPTION, $store, false);
        do_action('litespeed_purge_all');
    }

    public static function current_language() {
        $language = class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::language() : '';
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    public static function register_rest_routes() {
        register_rest_route('parcs-ht/v1', '/popups', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'rest_popups'),
            'permission_callback' => '__return_true',
            'args' => array(
                'lang' => array(
                    'sanitize_callback' => 'sanitize_key',
                ),
            ),
        ));
    }

    public static function rest_popups($request) {
        $lang = sanitize_key((string)$request->get_param('lang'));
        if (!in_array($lang, array('fr','en','de'), true)) $lang = 'fr';

        $settings = class_exists('Parcs_HT_Defaults') ? Parcs_HT_Defaults::settings() : array();
        $timezone = class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::timezone($settings) : 'Europe/Paris';
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone($timezone));
        } catch (Exception $e) {
            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        }
        $now_value = $now->format('Y-m-d\\TH:i');
        $rows = array();

        foreach ((array)(self::store()['popups'] ?? array()) as $row) {
            if (!is_array($row)) continue;
            if ((string)($row['enabled'] ?? '0') !== '1' || (string)($row['published'] ?? '0') !== '1') continue;

            $start = (string)($row['start'] ?? '');
            $end = (string)($row['end'] ?? '');
            if ($start !== '' && $now_value < $start) continue;
            if ($end !== '' && $now_value > $end) continue;

            $media = isset($row['media'][$lang]) && is_array($row['media'][$lang]) ? $row['media'][$lang] : array();
            $image_url = self::media_url($media);
            if ($image_url === '') continue;

            $rows[] = array(
                'id' => sanitize_key((string)($row['id'] ?? '')),
                'imageUrl' => $image_url,
                'linkUrl' => esc_url_raw((string)($media['link_url'] ?? '')),
                'alt' => sanitize_text_field((string)($media['alt'] ?? '')),
                'width' => self::popup_width($row),
                'priority' => (int)($row['priority'] ?? 10),
                'start' => $start,
                'reappearMode' => (string)($row['reappear_mode'] ?? 'hours') === 'once' ? 'once' : 'hours',
                'reappearHours' => max(1, min(8760, (int)($row['reappear_hours'] ?? 24))),
            );
        }

        usort($rows, static function ($a, $b) {
            $priority = ((int)$b['priority']) <=> ((int)$a['priority']);
            if ($priority !== 0) return $priority;
            $start = strcmp((string)$b['start'], (string)$a['start']);
            if ($start !== 0) return $start;
            return strcmp((string)$a['id'], (string)$b['id']);
        });

        $response = rest_ensure_response(array('language'=>$lang, 'popups'=>$rows));
        if ($response instanceof WP_REST_Response) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
        }
        return $response;
    }

    public static function enqueue_public_assets() {
        if (is_admin() || !self::has_public_source()) return;

        wp_enqueue_style(
            'parcs-ht-popup-1200',
            PARCS_HT_URL . 'assets/popup-1200.css',
            array(),
            PARCS_HT_VERSION
        );
        wp_enqueue_script(
            'parcs-ht-popup-1200',
            PARCS_HT_URL . 'assets/popup-1200.js',
            array(),
            PARCS_HT_VERSION,
            true
        );
        $config = array(
            'endpoint' => rest_url('parcs-ht/v1/popups'),
            'language' => self::current_language(),
        );
        wp_add_inline_script(
            'parcs-ht-popup-1200',
            'window.ParcsHTPopup1200=' . wp_json_encode($config) . ';',
            'before'
        );
    }

    private static function has_public_source() {
        foreach ((array)(self::store()['popups'] ?? array()) as $row) {
            if (!is_array($row)) continue;
            if ((string)($row['enabled'] ?? '0') !== '1' || (string)($row['published'] ?? '0') !== '1') continue;
            foreach (array('fr','en','de') as $lang) {
                $media = isset($row['media'][$lang]) && is_array($row['media'][$lang]) ? $row['media'][$lang] : array();
                if ((int)($media['image_id'] ?? 0) > 0 || trim((string)($media['legacy_image_url'] ?? '')) !== '') return true;
            }
        }
        return false;
    }

    private static function media_url($media) {
        $image_id = (int)($media['image_id'] ?? 0);
        if ($image_id > 0) {
            $url = wp_get_attachment_image_url($image_id, 'full');
            if (is_string($url) && $url !== '') return $url;
        }
        return esc_url_raw((string)($media['legacy_image_url'] ?? ''));
    }

    private static function popup_width($row) {
        $preset = (string)($row['size_preset'] ?? 'medium');
        if ($preset === 'small') return 480;
        if ($preset === 'large') return 800;
        if ($preset === 'custom') return max(320, min(1200, (int)($row['custom_width'] ?? 620)));
        return 620;
    }

    public static function maybe_migrate() {
        if (get_option(self::MIGRATION_OPTION, '') === '1') return;

        $existing = get_option(self::OPTION, null);
        if (is_array($existing) && isset($existing['popups'])) {
            update_option(self::MIGRATION_OPTION, '1', false);
            return;
        }

        $popups = array();
        $all = class_exists('Parcs_HT_Defaults') ? Parcs_HT_Defaults::all_settings() : array();
        $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
        $reappear = max(1, min(720, (int)($general['alert_reappear_hours'] ?? 24)));

        foreach ((array)($all['alerts'] ?? array()) as $index=>$row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            $title = self::legacy_translation($row['title'] ?? array(), 'fr');
            $popup = self::default_popup();
            $popup['id'] = 'legacy-alert-' . sanitize_key((string)$index);
            $popup['internal_name'] = $title !== '' ? $title : 'Ancien pop-up autonome';
            $popup['start'] = self::clean_datetime((string)($row['start'] ?? ''));
            $popup['end'] = self::clean_datetime((string)($row['end'] ?? ''));
            $popup['reappear_hours'] = $reappear;
            foreach (array('fr','en','de') as $lang) {
                $popup['media'][$lang]['alt'] = self::legacy_translation($row['title'] ?? array(), $lang);
                $popup['media'][$lang]['link_url'] = self::legacy_translation($row['button_url'] ?? array(), $lang);
            }
            $popups[] = $popup;
        }

        foreach ((array)($all['seasons'] ?? array()) as $year=>$season) {
            if (!is_array($season)) continue;

            foreach ((array)($season['special_periods'] ?? array()) as $index=>$row) {
                if (!is_array($row) || (string)($row['show_popup'] ?? '0') !== '1') continue;
                $popup = self::default_popup();
                $popup['id'] = 'legacy-event-' . sanitize_key((string)$year . '-' . (string)$index);
                $popup['internal_name'] = (string)($row['internal_label'] ?? '');
                if ($popup['internal_name'] === '') $popup['internal_name'] = self::legacy_translation($row['title'] ?? array(), 'fr');
                if ($popup['internal_name'] === '') $popup['internal_name'] = 'Ancien pop-up événement';
                $popup['start'] = self::legacy_popup_start($row, (string)($row['start'] ?? ''), class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::timezone($all) : 'Europe/Paris');
                $popup['end'] = self::legacy_popup_end($row, (string)($row['end'] ?? ''));
                $popup['reappear_hours'] = $reappear;

                $legacy_image = esc_url_raw((string)($row['popup_image_url'] ?? ''));
                if ($legacy_image !== '') {
                    $attachment_id = attachment_url_to_postid($legacy_image);
                    if ($attachment_id > 0) $popup['media']['fr']['image_id'] = $attachment_id;
                    else $popup['media']['fr']['legacy_image_url'] = $legacy_image;
                }

                foreach (array('fr','en','de') as $lang) {
                    $popup['media'][$lang]['alt'] = self::legacy_translation(
                        !empty($row['popup_title']) ? $row['popup_title'] : ($row['title'] ?? array()),
                        $lang
                    );
                    $links = !empty($row['popup_button_url']) ? $row['popup_button_url'] : ($row['button_url'] ?? array());
                    $popup['media'][$lang]['link_url'] = self::legacy_translation($links, $lang);
                }
                $popups[] = $popup;
            }

            foreach ((array)($season['exceptions'] ?? array()) as $index=>$row) {
                if (!is_array($row) || (string)($row['show_popup'] ?? '0') !== '1') continue;
                $popup = self::default_popup();
                $popup['id'] = 'legacy-exception-' . sanitize_key((string)$year . '-' . (string)$index);
                $popup['internal_name'] = (string)($row['label'] ?? '');
                if ($popup['internal_name'] === '') $popup['internal_name'] = self::legacy_translation($row['title'] ?? array(), 'fr');
                if ($popup['internal_name'] === '') $popup['internal_name'] = 'Ancien pop-up exception';
                $popup['start'] = self::legacy_popup_start($row, (string)($row['start'] ?? ''), class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::timezone($all) : 'Europe/Paris');
                $popup['end'] = self::legacy_popup_end($row, (string)($row['end'] ?? ''));
                $popup['reappear_hours'] = $reappear;
                foreach (array('fr','en','de') as $lang) {
                    $popup['media'][$lang]['alt'] = self::legacy_translation($row['title'] ?? array(), $lang);
                    $popup['media'][$lang]['link_url'] = self::legacy_translation($row['popup_button_url'] ?? array(), $lang);
                }
                $popups[] = $popup;
            }
        }

        update_option(self::OPTION, array(
            'version'=>1,
            'popups'=>$popups,
            'migration_count'=>count($popups),
            'updated_at'=>gmdate('c'),
        ), false);
        update_option(self::MIGRATION_OPTION, '1', false);
    }

    private static function clean_datetime($value) {
        return preg_match('/^20\\d{2}-\\d{2}-\\d{2}T\\d{2}:\\d{2}$/', $value) ? $value : '';
    }

    private static function legacy_popup_start($row, $event_start, $timezone) {
        $mode = (string)($row['popup_lead_mode'] ?? 'same');
        if ($mode === 'custom' && !empty($row['popup_start'])) return self::clean_datetime((string)$row['popup_start']);
        if (!preg_match('/^20\\d{2}-\\d{2}-\\d{2}$/', $event_start)) return '';
        if ($mode === 'days_before') {
            $days = max(0, min(365, (int)($row['popup_days_before'] ?? 0)));
            try {
                $date = new DateTimeImmutable($event_start . ' 00:00:00', new DateTimeZone($timezone));
                return $date->modify('-' . $days . ' days')->format('Y-m-d\\TH:i');
            } catch (Exception $e) {
                return $event_start . 'T00:00';
            }
        }
        return $event_start . 'T00:00';
    }

    private static function legacy_popup_end($row, $event_end) {
        if (!empty($row['popup_end'])) {
            $custom = self::clean_datetime((string)$row['popup_end']);
            if ($custom !== '') return $custom;
        }
        return preg_match('/^20\\d{2}-\\d{2}-\\d{2}$/', $event_end) ? $event_end . 'T23:59' : '';
    }

    private static function legacy_translation($value, $lang) {
        if (is_array($value)) {
            if (!empty($value[$lang])) return sanitize_text_field((string)$value[$lang]);
            if (!empty($value['fr'])) return sanitize_text_field((string)$value['fr']);
            return '';
        }
        return sanitize_text_field((string)$value);
    }
}
