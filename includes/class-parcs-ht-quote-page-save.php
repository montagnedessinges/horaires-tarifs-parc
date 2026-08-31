<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Quote_Page_Save {
    public static function init() {
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'respect_explicit_list_deletions'), 99, 3);
    }

    public static function respect_explicit_list_deletions($new_value, $old_value, $option) {
        unset($old_value, $option);
        if (!is_admin() || !is_array($new_value) || !current_user_can('manage_options')) return $new_value;
        if (!isset($_POST['action'], $_POST['_wpnonce'], $_POST['settings']) || !is_array($_POST['settings'])) return $new_value;
        if (sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;

        $settings = wp_unslash($_POST['settings']);
        $complete = isset($settings['_complete']) && is_array($settings['_complete']) ? $settings['_complete'] : array();
        if (!isset($complete['quote_page']) || (string)$complete['quote_page'] !== '1') return $new_value;

        $posted_quote = isset($settings['quote_page']) && is_array($settings['quote_page']) ? $settings['quote_page'] : array();
        if (!isset($new_value['quote_page']) || !is_array($new_value['quote_page'])) $new_value['quote_page'] = array();

        foreach (array('important_messages', 'quick_links', 'info_blocks', 'accordions') as $list) {
            if (!array_key_exists($list, $posted_quote)) $new_value['quote_page'][$list] = array();
        }

        return $new_value;
    }
}
