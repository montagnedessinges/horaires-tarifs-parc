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
        if ($hook !== self::SCREEN) {
            return;
        }

        wp_enqueue_style(
            'parcs-ht-admin-shortcode-frontend',
            PARCS_HT_URL . 'assets/frontend.css',
            array(),
            PARCS_HT_VERSION
        );
        wp_enqueue_style(
            'parcs-ht-admin-shortcode-preview',
            PARCS_HT_URL . 'assets/admin-shortcode-preview.css',
            array('parcs-ht-admin', 'parcs-ht-admin-shortcode-frontend'),
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

    /**
     * Retire uniquement les scripts embarqués du HTML affiché dans l'aperçu admin.
     * Le shortcode public reste inchangé ; on évite ici qu'un script inline soit
     * transformé en texte visible par le contexte d'administration.
     */
    private static function preview_html($html) {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }

        $clean = preg_replace('#<script\b[^>]*>.*?</script\s*>#is', '', $html);
        return is_string($clean) ? $clean : $html;
    }

    public static function render_source() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== self::SCREEN) {
            return;
        }

        require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';

        $previews = array(
            array('key'=>'full','label'=>'Page complète','shortcode'=>'[parc_horaires_tarifs]','module'=>'page'),
            array('key'=>'today','label'=>'Horaire du jour','shortcode'=>'[parc_horaires_aujourdhui]','module'=>'today'),
            array('key'=>'calendar','label'=>'Calendrier interactif','shortcode'=>'[parc_calendrier]','module'=>'calendar'),
            array('key'=>'tariffs','label'=>'Tableau des tarifs','shortcode'=>'[parc_tableau_tarifs]','module'=>'tariffs'),
            array('key'=>'alert','label'=>'Alerte de fermeture','shortcode'=>'[parc_fermeture_exceptionnelle]','module'=>'alert'),
            array('key'=>'header-hour','label'=>'Texte horaire pour l’en-tête','shortcode'=>'[parc_horaire]','module'=>'header_hour'),
            array('key'=>'header-status','label'=>'Statut OUVERT / FERMÉ','shortcode'=>'[parc_statut]','module'=>'header_status'),
            array('key'=>'home-opening','label'=>'Horaire d’accueil','shortcode'=>'[parc_horaire_accueil]','module'=>'home_opening'),
            array('key'=>'quote','label'=>'Devis groupe','shortcode'=>'[parc_devis_groupe]','module'=>'quote_page'),
        );

        echo '<div id="parcs-ht-real-shortcode-preview-sources" hidden aria-hidden="true">';
        foreach ($previews as $preview) {
            $html = self::preview_html(Parcs_HT_Shortcodes::render($preview['module'], 'fr', array()));
            echo '<div data-htp-shortcode-preview-source="' . esc_attr($preview['key']) . '" data-label="' . esc_attr($preview['label']) . '" data-shortcode="' . esc_attr($preview['shortcode']) . '">';
            if (trim((string)$html) === '') {
                echo '<p class="htp-shortcode-preview-empty">Aucun rendu avec les données actuellement enregistrées.</p>';
            } else {
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML produit par le moteur interne de shortcode, déjà échappé dans ses méthodes de rendu et nettoyé des scripts pour l'aperçu admin.
            }
            echo '</div>';
        }

        if (shortcode_exists('parc_guides_pedagogiques_fr')) {
            $guides_html = self::preview_html(do_shortcode('[parc_guides_pedagogiques_fr]'));
            echo '<div data-htp-shortcode-preview-source="guides" data-label="Guides pédagogiques" data-shortcode="[parc_guides_pedagogiques_fr]">';
            if (trim((string)$guides_html) === '') {
                echo '<p class="htp-shortcode-preview-empty">Aucun guide actuellement enregistré.</p>';
            } else {
                echo $guides_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML du shortcode interne déjà échappé et nettoyé des scripts pour l'aperçu admin.
            }
            echo '</div>';
        }
        echo '</div>';

        wp_dequeue_script('parcs-ht-frontend');
        wp_dequeue_style('parcs-ht-frontend');
    }
}
