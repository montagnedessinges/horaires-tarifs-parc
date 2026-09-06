<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap léger : enregistre les shortcodes du moteur principal et endpoints
 * sans parser le gros moteur de rendu sur les pages qui n'utilisent pas l'extension.
 */
final class Parcs_HT_Bootstrap {
    private static $tags = null;

    public static function init() {
        foreach (self::tags() as $tag => $config) {
            add_shortcode($tag, static function ($atts = array()) use ($config) {
                require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
                $atts = is_array($atts) ? $atts : array();
                $language = $config['language'] !== '' ? $config['language'] : Parcs_HT_Schedule::language();
                return Parcs_HT_Shortcodes::render($config['module'], $language, $atts);
            });
        }

        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_preload_assets'), 20);
        add_action('parcs_ht_pregenerate_exports', array(__CLASS__, 'pregenerate_exports'));

        foreach (array('tariffs_print', 'tariffs_pdf', 'schedule_pdf') as $endpoint) {
            add_action('admin_post_parcs_ht_' . $endpoint, array(__CLASS__, $endpoint . '_endpoint'));
            add_action('admin_post_nopriv_parcs_ht_' . $endpoint, array(__CLASS__, $endpoint . '_endpoint'));
        }
    }

    public static function maybe_preload_assets() {
        if (!is_singular()) return;
        global $post;
        if (!$post || empty($post->post_content)) return;
        foreach (array_keys(self::tags()) as $tag) {
            if (!has_shortcode($post->post_content, $tag)) continue;
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
            Parcs_HT_Shortcodes::maybe_enqueue_assets();
            return;
        }
    }

    public static function tariffs_print_endpoint() {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
        Parcs_HT_Shortcodes::tariffs_print_endpoint();
    }

    public static function tariffs_pdf_endpoint() {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
        Parcs_HT_Shortcodes::tariffs_pdf_endpoint();
    }

    public static function schedule_pdf_endpoint() {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
        Parcs_HT_Shortcodes::schedule_pdf_endpoint();
    }

    public static function pregenerate_exports() {
        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
        Parcs_HT_Shortcodes::pregenerate_exports();
    }

    private static function tags() {
        if (is_array(self::$tags)) return self::$tags;
        $tags = array();
        if (!class_exists('Parcs_HT_Shortcode_Registry')) {
            self::$tags = $tags;
            return self::$tags;
        }
        foreach (Parcs_HT_Shortcode_Registry::definitions() as $base => $definition) {
            // Les modules Groupes et Guides possèdent leur propre bootstrap léger.
            if (($definition['kind'] ?? '') !== 'core') continue;
            $module = sanitize_key((string)($definition['module'] ?? ''));
            if ($module === '') continue;
            $tags[$base] = array('module'=>$module,'language'=>'');
            foreach (Parcs_HT_Shortcode_Registry::languages() as $language) {
                $tags[$base . '_' . $language] = array('module'=>$module,'language'=>$language);
            }
        }
        self::$tags = $tags;
        return self::$tags;
    }
}
