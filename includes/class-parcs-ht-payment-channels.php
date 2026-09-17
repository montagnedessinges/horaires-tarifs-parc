<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Canaux des moyens de paiement visiteurs.
 *
 * Les moyens de paiement groupes restent entièrement gérés par leur module
 * dédié. Cette couche ajoute seulement les deux canaux visiteurs « Sur place »
 * et « En ligne » aux moyens de paiement existants.
 */
final class Parcs_HT_Payment_Channels {
    public static function init() {
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'save_channels'), 99, 3);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 100);
    }

    /**
     * Retourne les deux canaux d'un moyen de paiement.
     *
     * Compatibilité historique : avant 1.16.8, aucun canal n'était enregistré.
     * Tous les moyens restent alors disponibles sur place et une carte bancaire
     * est également considérée disponible en ligne jusqu'au premier enregistrement
     * explicite dans l'administration.
     */
    public static function channels_for_item($item) {
        $item = is_array($item) ? $item : array();
        if (isset($item['channels']) && is_array($item['channels'])) {
            return array(
                'onsite' => (string)($item['channels']['onsite'] ?? '0') === '1',
                'online' => (string)($item['channels']['online'] ?? '0') === '1',
            );
        }

        $icon = strtolower((string)($item['icon'] ?? ''));
        $labels = is_array($item['label'] ?? null) ? $item['label'] : array();
        $text = strtolower(implode(' ', array_map('strval', $labels)));
        if (function_exists('remove_accents')) $text = remove_accents($text);
        $looks_like_card = $icon === 'card' || (bool)preg_match('/(?:^|\b)(?:cb|carte(?:\s+bancaire)?|card|bank card|bankkarte|visa|mastercard)(?:\b|$)/u', $text);

        return array('onsite'=>true, 'online'=>$looks_like_card);
    }

    public static function items_for_channel($tariffs, $language, $channel) {
        $channel = $channel === 'online' ? 'online' : 'onsite';
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $items = isset($tariffs['payment_items']) && is_array($tariffs['payment_items']) ? $tariffs['payment_items'] : array();
        $out = array();
        foreach ($items as $item) {
            if (!is_array($item) || (string)($item['enabled'] ?? '0') !== '1') continue;
            if (isset($item['visible'][$language]) && (string)$item['visible'][$language] !== '1') continue;
            $channels = self::channels_for_item($item);
            if (empty($channels[$channel])) continue;
            $out[] = $item;
        }
        return $out;
    }

    private static function posted_channels($row) {
        $channels = isset($row['channels']) && is_array($row['channels']) ? $row['channels'] : array();
        return array(
            'onsite' => isset($channels['onsite']) && (string)$channels['onsite'] === '1' ? '1' : '0',
            'online' => isset($channels['online']) && (string)$channels['online'] === '1' ? '1' : '0',
        );
    }

    private static function apply_posted_channels($items, $posted_items) {
        $items = is_array($items) ? $items : array();
        $posted_items = is_array($posted_items) ? $posted_items : array();
        foreach ($items as $index => &$item) {
            if (!is_array($item)) continue;
            if (isset($posted_items[$index]) && is_array($posted_items[$index])) {
                $item['channels'] = self::posted_channels($posted_items[$index]);
            } elseif (!isset($item['channels'])) {
                $legacy = self::channels_for_item($item);
                $item['channels'] = array(
                    'onsite'=>$legacy['onsite'] ? '1' : '0',
                    'online'=>$legacy['online'] ? '1' : '0',
                );
            }
        }
        unset($item);
        return $items;
    }

    public static function save_channels($new_value, $old_value, $option) {
        unset($option, $old_value);
        if (!is_admin() || !is_array($new_value) || !current_user_can('manage_options')) return $new_value;
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) return $new_value;

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- La structure n'est jamais enregistrée directement : seuls deux booléens stricts sont lus par posted_channels().
        $raw_settings = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        $raw_tariffs = isset($raw_settings['tariffs']) && is_array($raw_settings['tariffs']) ? $raw_settings['tariffs'] : array();
        if (!array_key_exists('payment_items', $raw_tariffs) || !is_array($raw_tariffs['payment_items'])) return $new_value;
        $posted_items = $raw_tariffs['payment_items'];

        if (isset($new_value['seasons'][$year]['tariffs']['payment_items']) && is_array($new_value['seasons'][$year]['tariffs']['payment_items'])) {
            $new_value['seasons'][$year]['tariffs']['payment_items'] = self::apply_posted_channels(
                $new_value['seasons'][$year]['tariffs']['payment_items'],
                $posted_items
            );
        }

        if (isset($new_value['tariffs']['payment_items']) && is_array($new_value['tariffs']['payment_items'])) {
            $new_value['tariffs']['payment_items'] = self::apply_posted_channels(
                $new_value['tariffs']['payment_items'],
                $posted_items
            );
        }

        return $new_value;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection de saison en lecture seule.
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : '';
        $settings = Parcs_HT_Defaults::settings($year);
        $items = isset($settings['tariffs']['payment_items']) && is_array($settings['tariffs']['payment_items']) ? $settings['tariffs']['payment_items'] : array();
        $states = array();
        foreach ($items as $index => $item) {
            $channels = self::channels_for_item($item);
            $states[(string)$index] = array('onsite'=>$channels['onsite'], 'online'=>$channels['online']);
        }

        wp_enqueue_script(
            'parcs-ht-payment-channels-admin',
            PARCS_HT_URL . 'assets/payment-channels-admin.js',
            array('parcs-ht-admin'),
            PARCS_HT_VERSION,
            true
        );
        wp_add_inline_script(
            'parcs-ht-payment-channels-admin',
            'window.ParcsHTPaymentChannels=' . wp_json_encode(array('items'=>$states)) . ';',
            'before'
        );
    }
}
