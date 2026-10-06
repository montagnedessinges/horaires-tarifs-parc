<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Assembleur des shortcodes publics composés.
 *
 * La page Horaires & Tarifs assemble les moteurs existants. Le portail groupes
 * possède son renderer canonique dans Parcs_HT_Group_Portal : cette classe ne
 * réenregistre plus les shortcodes groupes afin d'éviter deux implémentations
 * concurrentes du même composant public.
 */
final class Parcs_HT_Shortcode_Composer {
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_horaires_tarifs', array(__CLASS__, 'shortcode_page'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Shortcode_Composer::render_page($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode_page($atts = array()) {
        return self::render_page(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    /** Compatibilité PHP pour les anciens appels directs ; le shortcode est détenu par Group_Portal. */
    public static function shortcode_groups($atts = array()) {
        return self::render_groups(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function language($language) {
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function child($base, $language) {
        return do_shortcode('[' . $base . '_' . self::language($language) . ']');
    }

    /** Shortcode public complet : statut du jour + calendrier + tarifs. */
    public static function render_page($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        self::$instance++;
        $id = 'parcs-ht-composed-page-' . self::$instance;

        $today = self::child('parc_horaires_aujourdhui', $language);
        $calendar = self::child('parc_calendrier', $language);
        $tariffs = self::child('parc_tableau_tarifs', $language);

        return '<div id="' . esc_attr($id) . '" class="parcs-ht-page parcs-ht-composed-page" data-htp-lang="' . esc_attr($language) . '">' .
            $today . $calendar . $tariffs .
            '</div>';
    }

    /**
     * Compatibilité : l'ancien assembleur groupes délègue désormais au renderer
     * unique au lieu de conserver une seconde copie de sa logique.
     */
    public static function render_groups($language, $atts = array()) {
        if (!class_exists('Parcs_HT_Group_Portal')) return '';
        return Parcs_HT_Group_Portal::render(self::language($language), is_array($atts) ? $atts : array());
    }
}
