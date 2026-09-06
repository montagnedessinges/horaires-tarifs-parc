<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aperçus administratifs des vrais shortcodes publics.
 *
 * Chaque aperçu est généré par le même moteur PHP que le frontend. Le seul
 * réglage propre à l'aperçu est la couleur de fond de simulation, conservée
 * uniquement dans le navigateur.
 */
final class Parcs_HT_Admin_Shortcode_Preview {
    const SCREEN = 'toplevel_page_parcs-horaires-tarifs';

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 30);
        add_action('admin_footer', array(__CLASS__, 'render_source'), 25);
    }

    public static function assets($hook) {
        if ($hook !== self::SCREEN) return;

        wp_enqueue_style(
            'parcs-ht-admin-shortcode-frontend',
            PARCS_HT_URL . 'assets/frontend.css',
            array(),
            PARCS_HT_VERSION
        );

        // Le shortcode guides possède sa propre feuille publique : l'aperçu admin
        // doit charger exactement cette feuille pour servir de vrai banc de test.
        wp_enqueue_style(
            'parcs-ht-pedagogical-guides',
            PARCS_HT_URL . 'assets/pedagogical-guides.css',
            array(),
            PARCS_HT_VERSION
        );

        if (class_exists('Parcs_HT_Guide_Appearance')) {
            $s = Parcs_HT_Guide_Appearance::settings();
            $guide_css = '.parcs-ht-guides{'
                . '--htp-guide-card-bg:' . esc_html($s['card_background']) . ';'
                . '--htp-guide-text:' . esc_html($s['text_color']) . ';'
                . '--htp-guide-title:' . esc_html($s['title_color']) . ';'
                . '--htp-guide-primary-bg:' . esc_html($s['primary_button_background']) . ';'
                . '--htp-guide-primary-text:' . esc_html($s['primary_button_text']) . ';'
                . '--htp-guide-secondary:' . esc_html($s['secondary_button_color']) . ';'
                . '--htp-guide-category:' . esc_html($s['category_color']) . ';'
                . '}';
            wp_add_inline_style('parcs-ht-pedagogical-guides', $guide_css);
        }

        wp_enqueue_style(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.css',
            array('parcs-ht-admin', 'parcs-ht-admin-shortcode-frontend', 'parcs-ht-pedagogical-guides'),
            PARCS_HT_VERSION
        );
        wp_enqueue_script(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.js',
            array('parcs-ht-admin', 'parcs-ht-preview-engine'),
            PARCS_HT_VERSION,
            true
        );
    }

    /** Retire uniquement les scripts embarqués de la copie d'aperçu admin. */
    private static function preview_html($html) {
        $html = (string)$html;
        if ($html === '') return '';
        $clean = preg_replace('#<script\b[^>]*>.*?</script\s*>#is', '', $html);
        return is_string($clean) ? $clean : $html;
    }

    public static function render_source() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== self::SCREEN || !class_exists('Parcs_HT_Shortcode_Registry')) return;

        echo '<div id="parcs-ht-real-shortcode-preview-sources" hidden aria-hidden="true">';
        foreach (Parcs_HT_Shortcode_Registry::public_rows() as $row) {
            if (empty($row['preview'])) continue;
            $base = sanitize_key((string)$row['base']);
            foreach (Parcs_HT_Shortcode_Registry::languages() as $language) {
                $shortcode = isset($row['shortcodes'][$language]) ? (string)$row['shortcodes'][$language] : Parcs_HT_Shortcode_Registry::shortcode($base, $language);
                $html = self::preview_html(Parcs_HT_Shortcode_Registry::render_preview($base, $language));
                echo '<div data-htp-shortcode-preview-source data-base="' . esc_attr($base) . '" data-lang="' . esc_attr($language) . '" data-label="' . esc_attr($row['label']) . '" data-shortcode="' . esc_attr($shortcode) . '">';
                if (trim((string)$html) === '') {
                    echo '<p class="htp-shortcode-preview-empty">Aucun rendu avec les données actuellement enregistrées.</p>';
                } else {
                    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML des moteurs internes, déjà échappé puis nettoyé des scripts pour l'aperçu admin.
                }
                echo '</div>';
            }
        }
        echo '</div>';

        // Le vrai frontend de la page admin est fourni sous un handle dédié.
        wp_dequeue_script('parcs-ht-frontend');
        wp_dequeue_style('parcs-ht-frontend');
    }
}
