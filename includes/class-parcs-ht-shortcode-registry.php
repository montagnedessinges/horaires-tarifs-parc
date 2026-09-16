<?php

if (!defined('ABSPATH')) { exit; }

/** Référentiel unique des shortcodes exposés par l'extension. */
final class Parcs_HT_Shortcode_Registry {
    public static function definitions() {
        return array(
            'parc_horaires_tarifs' => array('label'=>'Page complète','kind'=>'core','module'=>'page','preview'=>true),
            'parc_horaires_aujourdhui' => array('label'=>'Horaire du jour','kind'=>'core','module'=>'today','preview'=>true),
            'parc_calendrier' => array('label'=>'Calendrier interactif','kind'=>'core','module'=>'calendar','preview'=>true),
            'parc_calendrier_avent' => array('label'=>'Calendrier de l’Avent','kind'=>'advent','module'=>'calendar','preview'=>true),
            'parc_reglement_avent' => array('label'=>'Règlement du Calendrier de l’Avent','kind'=>'advent','module'=>'rules','preview'=>true),
            'parc_tableau_tarifs' => array('label'=>'Tableau des tarifs','kind'=>'core','module'=>'tariffs','preview'=>true),
            'parc_tarifs_groupes' => array('label'=>'Tarifs groupes uniquement','kind'=>'groups','module'=>'group_tariffs','preview'=>true),
            'parc_groupes_horaires_tarifs' => array('label'=>'Groupes — horaires et tarifs','kind'=>'group_portal','module'=>'group_portal','preview'=>true),
            'parc_fermeture_exceptionnelle' => array('label'=>'Alerte de fermeture','kind'=>'core','module'=>'alert','preview'=>true),
            'parc_horaire' => array('label'=>'Texte horaire dynamique pour l’en-tête','kind'=>'core','module'=>'header_hour','preview'=>true),
            'parc_statut' => array('label'=>'Statut OUVERT / FERMÉ pour l’en-tête','kind'=>'core','module'=>'header_status','preview'=>true),
            'parc_horaire_accueil' => array('label'=>'Horaire d’accueil','kind'=>'core','module'=>'home_opening','preview'=>true),
            'parc_devis_groupe' => array('label'=>'Devis groupe autour du formulaire Contact Form 7','kind'=>'core','module'=>'quote_page','preview'=>true),
            'parc_devis' => array('label'=>'Alias compatible du module Devis groupe','kind'=>'core','module'=>'quote_page','preview'=>true),
            'parc_guides_pedagogiques' => array('label'=>'Guides pédagogiques','kind'=>'guides','module'=>'guides','preview'=>true),
        );
    }

    public static function languages() {
        return array('fr','en','de');
    }

    public static function shortcode($base, $language = '') {
        $base = sanitize_key($base);
        $language = sanitize_key($language);
        return '[' . $base . ($language !== '' ? '_' . $language : '') . ']';
    }

    public static function render_preview($base, $language) {
        $definitions = self::definitions();
        if (!isset($definitions[$base])) return '';
        $definition = $definitions[$base];
        $language = in_array($language, self::languages(), true) ? $language : 'fr';

        if ($base === 'parc_horaires_tarifs' && class_exists('Parcs_HT_Tariff_Display')) {
            return Parcs_HT_Tariff_Display::render_page($language, array());
        }
        if ($base === 'parc_tableau_tarifs' && class_exists('Parcs_HT_Tariff_Display')) {
            return Parcs_HT_Tariff_Display::render_public($language, array());
        }
        if ($definition['kind'] === 'core') {
            require_once PARCS_HT_DIR . 'includes/class-parcs-ht-shortcodes.php';
            return Parcs_HT_Shortcodes::render($definition['module'], $language, array());
        }
        if ($definition['kind'] === 'groups') {
            if (class_exists('Parcs_HT_Tariff_Display')) return Parcs_HT_Tariff_Display::render_group($language, array());
            if (class_exists('Parcs_HT_Group_Tariffs')) return Parcs_HT_Group_Tariffs::render($language, array());
        }
        if ($definition['kind'] === 'group_portal' && class_exists('Parcs_HT_Group_Portal')) {
            return Parcs_HT_Group_Portal::render($language, array());
        }
        if ($definition['kind'] === 'guides') {
            return do_shortcode(self::shortcode($base, $language));
        }
        if ($definition['kind'] === 'advent' && class_exists('Parcs_HT_Advent')) {
            if ($definition['module'] === 'rules') return Parcs_HT_Advent::render_rules($language, array());
            return Parcs_HT_Advent::render_calendar($language, array());
        }
        return '';
    }

    public static function public_rows() {
        $out = array();
        foreach (self::definitions() as $base => $definition) {
            $row = array(
                'base'=>$base,
                'label'=>$definition['label'],
                'preview'=>!empty($definition['preview']),
                'shortcodes'=>array('auto'=>self::shortcode($base)),
            );
            foreach (self::languages() as $language) {
                $row['shortcodes'][$language] = self::shortcode($base, $language);
            }
            $out[] = $row;
        }
        return $out;
    }
}
