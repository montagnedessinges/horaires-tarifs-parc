<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Étape 1.16.8 : séparation publique visiteurs / groupes sans dupliquer le tableau groupes.
 *
 * - les onglets Individuels et Réduits conservent le rendu 1.16.7 ;
 * - l'onglet Groupes réutilise exactement le renderer canonique du shortcode groupes ;
 * - lorsqu'une année groupes est publiée avant les tarifs visiteurs, le tableau public
 *   affiche seulement un renvoi vers l'espace Groupes et n'expose aucun prix de cette année.
 */
final class Parcs_HT_Tariff_Shared_1168 {
    private static $instance = 0;
    private static $portal_url_cache = array();

    public static function init() {
        add_shortcode('parc_tableau_tarifs', array(__CLASS__, 'shortcode_public'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_tableau_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Tariff_Shared_1168::render_public($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode_public($atts = array()) {
        return self::render_public(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function language($language) {
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function t($language, $fr, $en, $de) {
        return $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
    }

    private static function content($language, $key, $fr, $en, $de, $vars = array()) {
        $fallback = self::t($language, $fr, $en, $de);
        $value = class_exists('Parcs_HT_Public_Content')
            ? Parcs_HT_Public_Content::text($key, $language, $fallback)
            : $fallback;
        return class_exists('Parcs_HT_Public_Content') && $vars
            ? Parcs_HT_Public_Content::format($value, $vars)
            : ($vars ? strtr($value, array_combine(array_map(static function ($key) { return '{' . $key . '}'; }, array_keys($vars)), array_values($vars))) : $value);
    }

    private static function tr($value, $language, $fallback = '') {
        return Parcs_HT_Schedule::translation(is_array($value) ? $value : array(), $language, $fallback);
    }

    private static function translated_url($value, $language) {
        if (is_array($value)) {
            $url = trim((string)($value[$language] ?? ''));
            if ($url === '') $url = trim((string)($value['fr'] ?? ''));
            return $url;
        }
        return trim((string)$value);
    }

    /** Réutilise les méthodes privées du correctif 1.16.7 au lieu de recopier leur logique. */
    private static function fixes_call($method, $args = array()) {
        $reflection = new ReflectionMethod('Parcs_HT_Tariff_Public_Fixes', $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs(null, $args);
    }

    private static function display_call($method, $args = array()) {
        $reflection = new ReflectionMethod('Parcs_HT_Tariff_Display', $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs(null, $args);
    }

    /** Renderer canonique groupes : le même que celui utilisé par [parc_tarifs_groupes]. */
    public static function render_group_year($language, $year) {
        $language = self::language($language);
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return '';
        return self::fixes_call('render_group_year', array($language, $year));
    }

    public static function render_public($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);

        if (!class_exists('Parcs_HT_Public_Visibility') || !class_exists('Parcs_HT_Tariff_Public_Fixes')) {
            return Parcs_HT_Tariff_Display::render_public($language, array());
        }

        $late_style = self::display_call('ensure_assets');
        $years = Parcs_HT_Public_Visibility::tariff_years();
        if (!$years) return '';

        $requested = class_exists('Parcs_HT_Public_Seasons') ? Parcs_HT_Public_Seasons::requested_year() : '';
        $selected = Parcs_HT_Public_Visibility::default_year($years, $requested);

        self::$instance++;
        $id = 'parcs-ht-tariff-shared-years-' . self::$instance;
        $html = $late_style . '<div id="' . esc_attr($id) . '" class="parcs-ht-tariff-years-ui" data-htp-ui-years>';
        if (count($years) > 1) $html .= self::fixes_call('year_tabs', array($years, $selected, $language));

        foreach ($years as $year) {
            $year = (string)$year;
            $html .= '<div class="parcs-ht-tariff-year-panel-ui" data-htp-ui-year-panel="' . esc_attr($year) . '"' . ($year !== (string)$selected ? ' hidden' : '') . '>';
            $html .= self::render_public_year($language, $year);
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    private static function render_public_year($language, $year) {
        $retail_years = Parcs_HT_Public_Visibility::retail_years();
        $group_years = Parcs_HT_Public_Visibility::group_tariff_years();
        $retail_visible = in_array($year, $retail_years, true);
        $group_visible = in_array($year, $group_years, true);
        if (!$retail_visible && !$group_visible) return '';

        $settings = Parcs_HT_Defaults::settings($year);
        $season = Parcs_HT_Public_Visibility::raw_season($year);
        $source_tariffs = $season && isset($season['tariffs']) && is_array($season['tariffs'])
            ? $season['tariffs']
            : (isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array());

        $tariffs = self::display_call('normalize_for_display', array($source_tariffs));
        $tariffs = self::fixes_call('fix_tariff_data', array($tariffs));
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $general['year'] = $year;

        if (!$retail_visible) {
            $tariffs['individual'] = array();
            $tariffs['reduced'] = array();
        }

        $dicts = Parcs_HT_Schedule::dictionaries();
        $d = isset($dicts[$language]) ? $dicts[$language] : $dicts['fr'];
        $labels = array(
            'individual'=>self::content($language, 'common.individual', $d['individual'] ?? 'Individuels', $d['individual'] ?? 'Individuals', $d['individual'] ?? 'Einzelbesucher'),
            'reduced'=>self::content($language, 'common.reduced', $d['reduced'] ?? 'Tarifs réduits', $d['reduced'] ?? 'Reduced rates', $d['reduced'] ?? 'Ermäßigte Tarife'),
            'groups'=>self::content($language, 'common.groups', $d['groups'] ?? 'Groupes', $d['groups'] ?? 'Groups', $d['groups'] ?? 'Gruppen'),
        );

        $order = isset($tariffs['group_order']) && is_array($tariffs['group_order']) ? $tariffs['group_order'] : array_keys($labels);
        $groups = array();
        foreach ($order as $key) {
            if (!isset($labels[$key]) || isset($groups[$key])) continue;
            if ($key === 'groups') {
                if ($group_visible) $groups[$key] = $labels[$key];
                continue;
            }
            if ($retail_visible && self::display_call('visible_rows', array($tariffs[$key] ?? array()))) {
                $groups[$key] = $labels[$key];
            }
        }
        foreach ($labels as $key=>$label) {
            if (isset($groups[$key])) continue;
            if ($key === 'groups') {
                if ($group_visible) $groups[$key] = $label;
            } elseif ($retail_visible && self::display_call('visible_rows', array($tariffs[$key] ?? array()))) {
                $groups[$key] = $label;
            }
        }
        if (!$groups) return '';

        self::$instance++;
        $id = 'parcs-ht-tariff-shared-' . self::$instance;
        $tickets_url = self::translated_url($general['tickets_url'] ?? array(), $language);
        $style = self::display_call('style_variables', array($general));
        $prices_title = self::content($language, 'common.prices', $d['prices'] ?? 'Tarifs', $d['prices'] ?? 'Prices', $d['prices'] ?? 'Preise');

        $html = '<section id="' . esc_attr($id) . '" class="parcs-ht-tariff-ui" data-htp-ui-tariffs style="' . esc_attr($style) . '">';
        $html .= '<div class="parcs-ht-tariff-ui__header"><div class="parcs-ht-tariff-ui__title">' . esc_html($prices_title) . '</div></div>';
        $html .= self::display_call('category_tabs', array($id, $groups));

        $first = true;
        foreach ($groups as $key=>$label) {
            $html .= '<div id="' . esc_attr($id . '-panel-' . $key) . '" class="parcs-ht-tariff-ui__panel" role="tabpanel" aria-labelledby="' . esc_attr($id . '-tab-' . $key) . '" data-htp-ui-panel="' . esc_attr($key) . '"' . (!$first ? ' hidden' : '') . '>';

            if ($key === 'individual') {
                $html .= self::fixes_call('visitor_payment_strip', array($tariffs, $language, 'individual', $label));
                $html .= self::display_call('price_table', array($tariffs, 'individual', $language, array('tickets_url'=>$tickets_url)));
                if ($tickets_url !== '') {
                    $tickets_label = self::content($language, 'common.tickets', $d['tickets'] ?? 'Acheter vos billets', $d['tickets'] ?? 'Buy tickets', $d['tickets'] ?? 'Tickets kaufen');
                    $html .= '<div class="parcs-ht-tariff-ui__actions"><a class="parcs-ht-tariff-ui__button is-primary" href="' . esc_url($tickets_url) . '">' . esc_html($tickets_label) . '</a></div>';
                }
            } elseif ($key === 'reduced') {
                $html .= self::fixes_call('visitor_payment_strip', array($tariffs, $language, 'reduced', $label));
                $note = self::tr($tariffs['notes'] ?? array(), $language, '');
                if ($note !== '') $html .= '<p class="parcs-ht-tariff-ui__note is-before-table">' . esc_html($note) . '</p>';
                $html .= self::display_call('price_table', array($tariffs, 'reduced', $language, array()));
            } elseif ($retail_visible) {
                // Un seul renderer groupes : exactement celui du shortcode / portail Groupes.
                $html .= self::render_group_year($language, $year);
            } else {
                // L'année est déjà ouverte aux groupes, mais pas encore aux visiteurs.
                $html .= self::group_redirect($language, $year, $general);
            }

            $html .= '</div>';
            $first = false;
        }

        // Une année uniquement Groupes ne doit pas exposer de PDF / impression du tableau visiteurs.
        if ($retail_visible) $html .= self::display_call('export_actions', array($tariffs, $language, $year));
        return $html . '</section>';
    }

    private static function group_redirect($language, $year, $general) {
        $vars = array('year'=>$year);
        $title = self::content($language, 'groups.redirect.title', 'Vous venez en groupe ?', 'Visiting as a group?', 'Sie kommen als Gruppe?');
        $text = self::content(
            $language,
            'groups.redirect.text',
            'Retrouvez les tarifs groupes {year}, les horaires d’ouverture et toutes les informations pour organiser votre visite sur notre espace dédié.',
            'Find the {year} group rates, opening hours and all the information you need to organise your visit in our dedicated group area.',
            'Die Gruppentarife {year}, Öffnungszeiten und alle Informationen zur Organisation Ihres Besuchs finden Sie in unserem Gruppenbereich.',
            $vars
        );
        $visitor_notice = self::content(
            $language,
            'groups.redirect.visitor_notice',
            'Les tarifs individuels et réduits {year} ne sont pas encore disponibles.',
            'Individual and reduced rates for {year} are not available yet.',
            'Einzel- und ermäßigte Tarife für {year} sind noch nicht verfügbar.',
            $vars
        );
        $button = self::content($language, 'groups.redirect.button', 'Voir les tarifs et horaires groupes', 'View group rates and opening hours', 'Gruppentarife und Öffnungszeiten ansehen');
        $url = self::group_portal_url($language, $general);

        $html = '<div class="parcs-ht-tariff-ui__info-card parcs-ht-group-redirect">';
        $html .= '<strong>' . esc_html($title) . '</strong>';
        $html .= '<p>' . esc_html($text) . '</p>';
        $html .= '<p>' . esc_html($visitor_notice) . '</p>';
        if ($url !== '') {
            $html .= '<div class="parcs-ht-tariff-ui__actions"><a class="parcs-ht-tariff-ui__button" href="' . esc_url($url) . '">' . esc_html($button) . '</a></div>';
        }
        return $html . '</div>';
    }

    /**
     * Cherche d'abord la page qui contient réellement le portail Groupes, afin de
     * ne pas figer un slug propre à un parc. Le lien historique Groupes reste le repli.
     */
    private static function group_portal_url($language, $general) {
        if (array_key_exists($language, self::$portal_url_cache)) return self::$portal_url_cache[$language];

        $configured = class_exists('Parcs_HT_Public_Content') ? Parcs_HT_Public_Content::url('groups.redirect.url', $language, '') : '';
        if ($configured !== '') {
            self::$portal_url_cache[$language] = $configured;
            return $configured;
        }

        $wanted = array('parc_groupes_horaires_tarifs_' . $language, 'parc_groupes_horaires_tarifs');
        $url = '';
        if (function_exists('get_posts') && function_exists('has_shortcode')) {
            $pages = get_posts(array(
                'post_type'=>'page',
                'post_status'=>'publish',
                'posts_per_page'=>20,
                's'=>'parc_groupes_horaires_tarifs',
                'orderby'=>'modified',
                'order'=>'DESC',
                'suppress_filters'=>false,
                'no_found_rows'=>true,
            ));
            foreach ((array)$pages as $page) {
                $content = isset($page->post_content) ? (string)$page->post_content : '';
                foreach ($wanted as $shortcode) {
                    if ($content !== '' && has_shortcode($content, $shortcode)) {
                        $candidate = get_permalink($page);
                        if (is_string($candidate) && $candidate !== '') $url = $candidate;
                        break 2;
                    }
                }
            }
        }

        if ($url === '') $url = self::translated_url($general['groups_url'] ?? array(), $language);
        self::$portal_url_cache[$language] = $url;
        return $url;
    }
}
