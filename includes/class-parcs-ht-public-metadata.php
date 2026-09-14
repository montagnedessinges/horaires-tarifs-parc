<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Public_Metadata {
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 100);
    }

    public static function enqueue() {
        if (is_admin()) return;
        wp_enqueue_script('parcs-ht-public-meta', PARCS_HT_URL . 'assets/interaction-events.js', array(), PARCS_HT_VERSION, true);
        wp_enqueue_script('parcs-ht-form-meta', PARCS_HT_URL . 'assets/interaction-forms.js', array('parcs-ht-public-meta'), PARCS_HT_VERSION, true);
    }
}
