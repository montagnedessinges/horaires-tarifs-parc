<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extensions UX du Calendrier de l’Avent.
 *
 * Ce module reste volontairement séparé du schéma canonique d’import :
 * - il enrichit l’affichage public sans exposer de donnée sensible ;
 * - il stocke les pop-ups quotidiens dans une option dédiée ;
 * - il injecte ces pop-ups dans le moteur d’alertes existant de l’extension.
 */
final class Parcs_HT_Advent_UX {
    const OPTION = 'parcs_ht_advent_ux';
    const SCHEMA_VERSION = 1;

    public static function init() {
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'inject_advent_popups'), 20, 1);
        add_action('admin_post_parcs_ht_advent_save_content', array(__CLASS__, 'capture_popup_before_content_save'), 5);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 60);
        add_action('wp_footer', array(__CLASS__, 'frontend_assets'), 5);

        if (!is_admin() && self::has_any_enabled_popup()) {
            if (!class_exists('Parcs_HT_Alerts')) {
                require_once PARCS_HT_DIR . 'includes/class-parcs-ht-alerts.php';
            }
            if (!has_action('wp_footer', array('Parcs_HT_Alerts', 'render_auto_popup'))) {
                Parcs_HT_Alerts::init();
            }
        }
    }

    private static function empty_store() {
        return array(
            'schema_version' => self::SCHEMA_VERSION,
            'campaigns' => array(),
        );
    }

    private static function default_popup() {
        return array(
            'enabled' => '0',
            'title_fr' => '',
            'message_fr' => '',
            'start' => '',
            'end' => '',
            'show_button' => '1',
            'button_label_fr' => 'Découvrir la case',
            'button_url' => '',
        );
    }

    private static function sanitize_datetime_local($value) {
        $value = trim((string)$value);
        return preg_match('/^20\d{2}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) ? $value : '';
    }

    private static function sanitize_popup($raw) {
        $raw = is_array($raw) ? $raw : array();
        $popup = self::default_popup();
        $popup['enabled'] = (string)($raw['enabled'] ?? '0') === '1' ? '1' : '0';
        $popup['title_fr'] = sanitize_text_field((string)($raw['title_fr'] ?? ''));
        $popup['message_fr'] = sanitize_textarea_field((string)($raw['message_fr'] ?? ''));
        $popup['start'] = self::sanitize_datetime_local($raw['start'] ?? '');
        $popup['end'] = self::sanitize_datetime_local($raw['end'] ?? '');
        $popup['show_button'] = (string)($raw['show_button'] ?? '0') === '1' ? '1' : '0';
        $popup['button_label_fr'] = sanitize_text_field((string)($raw['button_label_fr'] ?? ''));
        $popup['button_url'] = esc_url_raw((string)($raw['button_url'] ?? ''));
        return $popup;
    }

    private static function store() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $store = self::empty_store();
        $campaigns = isset($saved['campaigns']) && is_array($saved['campaigns']) ? $saved['campaigns'] : array();
        foreach ($campaigns as $campaign_id => $campaign) {
            $campaign_id = sanitize_key((string)$campaign_id);
            if ($campaign_id === '' || !is_array($campaign)) continue;
            $contents = isset($campaign['contents']) && is_array($campaign['contents']) ? $campaign['contents'] : array();
            foreach ($contents as $content_id => $popup) {
                $content_id = sanitize_key((string)$content_id);
                if ($content_id === '') continue;
                $store['campaigns'][$campaign_id]['contents'][$content_id] = self::sanitize_popup($popup);
            }
        }
        return $store;
    }

    private static function save_popup($campaign_id, $content_id, $popup) {
        $campaign_id = sanitize_key((string)$campaign_id);
        $content_id = sanitize_key((string)$content_id);
        if ($campaign_id === '' || $content_id === '') return;
        $store = self::store();
        if (!isset($store['campaigns'][$campaign_id]) || !is_array($store['campaigns'][$campaign_id])) {
            $store['campaigns'][$campaign_id] = array('contents' => array());
        }
        if (!isset($store['campaigns'][$campaign_id]['contents']) || !is_array($store['campaigns'][$campaign_id]['contents'])) {
            $store['campaigns'][$campaign_id]['contents'] = array();
        }
        $store['campaigns'][$campaign_id]['contents'][$content_id] = self::sanitize_popup($popup);
        $store['schema_version'] = self::SCHEMA_VERSION;
        update_option(self::OPTION, $store, false);
    }

    private static function has_any_enabled_popup() {
        $store = self::store();
        foreach ($store['campaigns'] as $campaign) {
            foreach ((array)($campaign['contents'] ?? array()) as $popup) {
                if (is_array($popup) && (string)($popup['enabled'] ?? '0') === '1') return true;
            }
        }
        return false;
    }

    /**
     * Enregistre la configuration pop-up avant le handler canonique, qui redirige ensuite la requête.
     */
    public static function capture_popup_before_content_save() {
        if (!current_user_can('manage_options')) return;
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'parcs_ht_advent_save_content')) return;

        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        $content = isset($_POST['content']) && is_array($_POST['content']) ? map_deep(wp_unslash($_POST['content']), 'sanitize_text_field') : array();
        $content_id = sanitize_key((string)($content['contenu_id'] ?? ''));
        $type = sanitize_key((string)($content['type_contenu'] ?? ''));
        if ($campaign_id === '' || $content_id === '' || $type !== 'jour') return;
        if (!Parcs_HT_Advent::campaign($campaign_id, true)) return;

        $popup = isset($_POST['advent_popup']) && is_array($_POST['advent_popup']) ? wp_unslash($_POST['advent_popup']) : array();
        self::save_popup($campaign_id, $content_id, $popup);
    }

    private static function content_open_datetime($campaign, $content) {
        $date = trim((string)($content['date_publication'] ?? ''));
        $time = trim((string)($content['heure_ouverture'] ?? ''));
        if ($time === '') $time = trim((string)($content['heure_publication'] ?? ''));
        if ($time === '') $time = trim((string)($campaign['heure_ouverture_globale'] ?? ''));
        if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) return '';
        return $date . 'T' . $time;
    }

    /**
     * Ajoute les pop-ups Avent comme alertes synthétiques afin de réutiliser le moteur existant.
     */
    public static function inject_advent_popups($saved) {
        if (is_admin() || !is_array($saved) || !class_exists('Parcs_HT_Advent')) return $saved;

        $store = self::store();
        if (!$store['campaigns']) return $saved;
        $advent = Parcs_HT_Advent::store();
        $park = Parcs_HT_Advent::installation_park_code();
        if ($park === '') return $saved;
        if (!isset($saved['alerts']) || !is_array($saved['alerts'])) $saved['alerts'] = array();

        foreach ((array)($advent['campaigns'] ?? array()) as $campaign_id => $campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park || (string)($campaign['statut_campagne'] ?? '') !== 'active') continue;
            $popup_rows = isset($store['campaigns'][$campaign_id]['contents']) && is_array($store['campaigns'][$campaign_id]['contents']) ? $store['campaigns'][$campaign_id]['contents'] : array();
            foreach ($popup_rows as $content_id => $popup) {
                if (!is_array($popup) || (string)($popup['enabled'] ?? '0') !== '1') continue;
                $content = isset($campaign['contents'][$content_id]) && is_array($campaign['contents'][$content_id]) ? $campaign['contents'][$content_id] : null;
                if (!$content || (string)($content['type_contenu'] ?? '') !== 'JOUR') continue;

                $start = self::sanitize_datetime_local($popup['start'] ?? '');
                if ($start === '') $start = self::content_open_datetime($campaign, $content);
                if ($start === '') continue;
                $end = self::sanitize_datetime_local($popup['end'] ?? '');
                if ($end === '') {
                    $date = substr($start, 0, 10);
                    $end = $date . 'T23:59';
                }

                $title = trim((string)($popup['title_fr'] ?? ''));
                if ($title === '') {
                    $day_title = trim((string)($content['titre_fr'] ?? ''));
                    $title = $day_title !== '' ? $day_title : sprintf('Calendrier de l’Avent — Jour %d', (int)($content['jour_numero'] ?? 0));
                }
                $message = trim((string)($popup['message_fr'] ?? ''));
                if ($title === '' && $message === '') continue;

                $button_url = esc_url_raw((string)($popup['button_url'] ?? ''));
                if ($button_url === '') $button_url = esc_url_raw((string)($campaign['page_calendrier_url'] ?? ''));
                $button_label = trim((string)($popup['button_label_fr'] ?? ''));
                if ($button_label === '') $button_label = 'Découvrir la case';
                $show_button = (string)($popup['show_button'] ?? '0') === '1' && $button_url !== '' ? '1' : '0';

                $saved['alerts']['advent_' . sanitize_key($campaign_id) . '_' . sanitize_key($content_id)] = array(
                    'enabled' => '1',
                    'published' => '1',
                    'start' => $start,
                    'end' => $end,
                    'title' => array('fr'=>$title,'en'=>'','de'=>''),
                    'message' => array('fr'=>$message,'en'=>'','de'=>''),
                    'button_label' => array('fr'=>$button_label,'en'=>'','de'=>''),
                    'button_url' => array('fr'=>$button_url,'en'=>$button_url,'de'=>$button_url),
                    'show_button' => $show_button,
                );
            }
        }
        return $saved;
    }

    private static function account_url($override, $value, $network) {
        $override = esc_url_raw((string)$override);
        if ($override !== '') return $override;
        $value = trim((string)$value);
        if ($value === '') return '';
        if (preg_match('#^https?://#i', $value)) return esc_url_raw($value);
        $value = ltrim($value, '@/ ');
        if ($value === '') return '';
        return $network === 'instagram'
            ? 'https://www.instagram.com/' . rawurlencode($value) . '/'
            : 'https://www.facebook.com/' . str_replace('%2F', '/', rawurlencode($value));
    }

    private static function partner_url($partner) {
        if (!is_array($partner)) return '';
        $instagram = self::account_url($partner['instagram_url_override'] ?? '', $partner['instagram_handle'] ?? '', 'instagram');
        if ($instagram !== '') return $instagram;
        $facebook = self::account_url($partner['facebook_url_override'] ?? '', $partner['facebook_slug'] ?? '', 'facebook');
        if ($facebook !== '') return $facebook;
        return esc_url_raw((string)($partner['site_url'] ?? ''));
    }

    private static function public_payload() {
        $store = Parcs_HT_Advent::store();
        $park = Parcs_HT_Advent::installation_park_code();
        $payload = array('campaigns'=>array());
        foreach ((array)($store['campaigns'] ?? array()) as $campaign_id => $campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park) continue;
            $row = array(
                'participateText' => (string)($campaign['texte_comment_participer_fr'] ?? ''),
                'dailyText' => (string)($campaign['reglement_quotidien_fr'] ?? ''),
                'mysteryText' => (string)($campaign['texte_rappel_grand_jeu_fr'] ?? ''),
                'rulesUrl' => esc_url_raw((string)($campaign['page_reglement_url'] ?? '')),
                'rulesLabel' => (string)($campaign['libelle_reglement_complet_fr'] ?? ''),
                'calendarUrl' => esc_url_raw((string)($campaign['page_calendrier_url'] ?? '')),
                'facebookUrl' => self::account_url($campaign['facebook_url_override'] ?? '', $campaign['facebook_slug'] ?? '', 'facebook'),
                'instagramUrl' => self::account_url('', $campaign['instagram_parc'] ?? '', 'instagram'),
                'contents' => array(),
            );
            foreach ((array)($campaign['contents'] ?? array()) as $content_id => $content) {
                if (!is_array($content) || (string)($content['type_contenu'] ?? '') !== 'JOUR') continue;
                $partner = null;
                $partner_id = sanitize_key((string)($content['partenaire_id'] ?? ''));
                if ($partner_id !== '' && isset($campaign['partners'][$partner_id]) && is_array($campaign['partners'][$partner_id])) {
                    $partner = $campaign['partners'][$partner_id];
                }
                $row['contents'][$content_id] = array(
                    'day' => (int)($content['jour_numero'] ?? 0),
                    'facebookUrl' => esc_url_raw((string)($content['facebook_post_url'] ?? '')),
                    'instagramUrl' => esc_url_raw((string)($content['instagram_post_url'] ?? '')),
                    'partnerName' => $partner ? (string)($partner['nom'] ?? '') : '',
                    'partnerUrl' => self::partner_url($partner),
                );
            }
            $payload['campaigns'][$campaign_id] = $row;
        }
        return $payload;
    }

    public static function frontend_assets() {
        if (is_admin() || !wp_script_is('parcs-ht-advent', 'enqueued')) return;
        wp_enqueue_script(
            'parcs-ht-advent-ux',
            PARCS_HT_URL . 'assets/advent-ux.js',
            array('parcs-ht-advent'),
            PARCS_HT_VERSION,
            true
        );
        wp_add_inline_script(
            'parcs-ht-advent-ux',
            'window.ParcsHTAdventUX=' . wp_json_encode(self::public_payload()) . ';',
            'before'
        );
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        wp_enqueue_script(
            'parcs-ht-advent-ux-admin',
            PARCS_HT_URL . 'assets/advent-ux-admin.js',
            array('parcs-ht-advent-admin'),
            PARCS_HT_VERSION,
            true
        );
        wp_add_inline_script(
            'parcs-ht-advent-ux-admin',
            'window.ParcsHTAdventUXAdmin=' . wp_json_encode(array('campaigns'=>self::store()['campaigns'])) . ';',
            'before'
        );
    }
}
