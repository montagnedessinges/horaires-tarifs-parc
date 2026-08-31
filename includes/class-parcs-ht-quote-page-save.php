<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Quote_Page_Save {
    private static $lists = array('important_messages', 'quick_links', 'info_blocks', 'accordions');

    public static function init() {
        add_action('admin_post_parcs_ht_save', array(__CLASS__, 'mark_explicit_empty_lists'), 1);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'respect_explicit_list_deletions'), 99, 3);
    }

    private static function is_main_admin_save() {
        if (!is_admin() || !current_user_can('manage_options')) return false;
        if (!isset($_POST['action'], $_POST['_wpnonce'])) return false;
        if (sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return false;
        return wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save');
    }

    private static function quote_section_is_complete($settings) {
        if (!is_array($settings)) return false;
        $complete = isset($settings['_complete']) && is_array($settings['_complete']) ? $settings['_complete'] : array();
        return isset($complete['quote_page']) && (string)$complete['quote_page'] === '1';
    }

    public static function mark_explicit_empty_lists() {
        if (!self::is_main_admin_save()) return;
        if (!isset($_POST['settings']) || !is_array($_POST['settings'])) return;
        if (!self::quote_section_is_complete($_POST['settings'])) return;

        if (!isset($_POST['settings']['quote_page']) || !is_array($_POST['settings']['quote_page'])) {
            $_POST['settings']['quote_page'] = array();
        }

        foreach (self::$lists as $list) {
            if (!array_key_exists($list, $_POST['settings']['quote_page'])) {
                // array_replace_recursive() conserverait l'ancienne liste si on injectait
                // simplement un tableau vide. Une valeur scalaire force son remplacement ;
                // le sanitizer principal la convertit ensuite proprement en tableau vide.
                $_POST['settings']['quote_page'][$list] = null;
            }
        }
    }

    public static function respect_explicit_list_deletions($new_value, $old_value, $option) {
        unset($option);
        if (!self::is_main_admin_save() || !is_array($new_value)) return $new_value;
        if (!isset($_POST['settings']) || !is_array($_POST['settings'])) return $new_value;

        $posted_settings = wp_unslash($_POST['settings']);
        if (!self::quote_section_is_complete($posted_settings)) return $new_value;

        $posted_quote = isset($posted_settings['quote_page']) && is_array($posted_settings['quote_page']) ? $posted_settings['quote_page'] : array();
        if (!isset($new_value['quote_page']) || !is_array($new_value['quote_page'])) $new_value['quote_page'] = array();

        foreach (self::$lists as $list) {
            if (!array_key_exists($list, $posted_quote) || $posted_quote[$list] === null || !is_array($posted_quote[$list])) {
                $new_value['quote_page'][$list] = array();
            }
        }

        // Les formulaires FR/EN/DE utilisent désormais la même option principale.
        // Le formulaire général de l'administration ne contient pas encore ces champs :
        // on les conserve explicitement lors de toute autre modification du module Devis.
        if (is_array($old_value)
            && isset($old_value['quote_page'])
            && is_array($old_value['quote_page'])
            && array_key_exists('form_shortcodes', $old_value['quote_page'])
            && !array_key_exists('form_shortcodes', $new_value['quote_page'])) {
            $new_value['quote_page']['form_shortcodes'] = $old_value['quote_page']['form_shortcodes'];
        }

        return $new_value;
    }
}
