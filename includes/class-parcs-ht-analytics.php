<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Analytics {
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 100);
    }

    public static function enqueue() {
        if (is_admin()) return;
        wp_enqueue_script('parcs-ht-analytics', PARCS_HT_URL . 'assets/analytics.js', array(), PARCS_HT_VERSION, true);
    }
}
