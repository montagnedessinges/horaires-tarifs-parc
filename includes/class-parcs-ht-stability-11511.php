<?php

if (!defined('ABSPATH')) { exit; }

/** Correctifs de stabilisation introduits après la 1.15.10. */
final class Parcs_HT_Stability_11511 {
    public static function init() {
        // La navigation annuelle 1.15.10 enveloppait les shortcodes avec des liens
        // provoquant un rechargement complet et masquait le sélecteur natif du calendrier.
        if (class_exists('Parcs_HT_Public_Seasons')) {
            remove_filter('pre_do_shortcode_tag', array('Parcs_HT_Public_Seasons', 'prepare_year_scope'), 6);
            remove_filter('do_shortcode_tag', array('Parcs_HT_Public_Seasons', 'wrap_year_tabs'), 20);
        }

        add_filter('do_shortcode_tag', array(__CLASS__, 'stabilize_tariff_output'), 60, 4);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'), 40);

        // Le contrôle complet reste disponible manuellement, mais ne doit plus bloquer
        // la première page d'administration après chaque mise à jour de l'extension.
        add_action('plugins_loaded', array(__CLASS__, 'disable_blocking_admin_verifier'), 999);
    }

    public static function disable_blocking_admin_verifier() {
        if (class_exists('Parcs_HT_Verifier')) {
            remove_action('admin_init', array('Parcs_HT_Verifier', 'maybe_verify_version_once'));
        }
    }

    public static function register_assets() {
        wp_register_script(
            'parcs-ht-stability-11511',
            PARCS_HT_URL . 'assets/stability-11511.js',
            array('parcs-ht-frontend'),
            PARCS_HT_VERSION,
            true
        );
        wp_register_style('parcs-ht-stability-11511', false, array('parcs-ht-frontend'), PARCS_HT_VERSION);
    }

    private static function is_tariff_shortcode($tag) {
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        return in_array($base, array('parc_tableau_tarifs', 'parc_horaires_tarifs'), true);
    }

    private static function selected_year($years) {
        if (!$years) return '';
        // Lecture seule : ce paramètre ne modifie aucune donnée persistée.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $requested = isset($_GET['htp_year']) ? sanitize_text_field(wp_unslash($_GET['htp_year'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if (preg_match('/^20\d{2}$/', $requested) && in_array($requested, $years, true)) return $requested;
        $current = wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? ($settings['general']['year'] ?? ''));
        if ($active !== '' && in_array($active, $years, true)) return $active;
        return (string)end($years);
    }

    private static function tabs_markup($years, $selected) {
        if (count($years) < 2) return '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL nettoyée avant sortie.
        $base = remove_query_arg('htp_year', $uri ?: '/');
        $links = '';
        foreach ($years as $year) {
            $active = (string)$year === (string)$selected;
            $url = add_query_arg('htp_year', rawurlencode((string)$year), $base);
            $links .= '<a class="parcs-ht-retail-year-tab' . ($active ? ' is-active' : '') . '" href="' . esc_url($url) . '" role="tab" aria-selected="' . ($active ? 'true' : 'false') . '" data-htp-retail-year="' . esc_attr((string)$year) . '">' . esc_html((string)$year) . '</a>';
        }
        return '<nav class="parcs-ht-retail-year-tabs" role="tablist" aria-label="Année des tarifs">' . $links . '</nav>';
    }

    public static function stabilize_tariff_output($output, $tag, $attr, $m) {
        unset($attr, $m);
        if (is_admin() || !self::is_tariff_shortcode($tag) || !is_string($output) || $output === '') return $output;

        // Supprime tout vestige du sélecteur 1.15.10 s'il a été généré avant ce correctif.
        $output = preg_replace('/(?:<style>[^<]*\.parcs-ht-year-tabs.*?<\/style>)?\s*<nav class="parcs-ht-year-tabs".*?<\/nav>/s', '', $output, 1);
        $output = str_replace(' has-public-year-tabs', '', $output);

        // Le titre reste volontairement indépendant de l'année sélectionnée.
        $output = preg_replace('/(<div class="parcs-ht-title"[^>]*>)([^<]*?)\s+20\d{2}(<\/div>)/u', '$1$2$3', $output, 1);

        $years = class_exists('Parcs_HT_Display_Policy') ? Parcs_HT_Display_Policy::retail_years() : array();
        if (count($years) > 1) {
            $selected = self::selected_year($years);
            $tabs = self::tabs_markup($years, $selected);
            $output = preg_replace('/(<div class="parcs-ht-tariff-tabs"\b)/', $tabs . '$1', $output, 1);
        }

        if (strpos($output, 'data-htp-component="tariffs"') !== false) {
            wp_enqueue_script('parcs-ht-stability-11511');
            wp_enqueue_style('parcs-ht-stability-11511');
            wp_add_inline_style('parcs-ht-stability-11511',
                '.parcs-ht-retail-year-tabs{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;max-width:800px;margin:20px auto 10px}.parcs-ht-retail-year-tab{display:inline-flex;align-items:center;justify-content:center;min-width:72px;min-height:42px;padding:8px 16px;border:2px solid var(--htp-primary,#006757);border-radius:999px;background:var(--htp-primary,#006757);color:#fff!important;text-decoration:none!important;font-weight:800}.parcs-ht-retail-year-tab.is-active{border-color:var(--htp-highlight,#e7c55b);background:var(--htp-highlight,#e7c55b);color:#27342f!important}.parcs-ht-retail-year-tabs[aria-busy="true"]{opacity:.6;pointer-events:none}'
            );
        }
        return $output;
    }
}
