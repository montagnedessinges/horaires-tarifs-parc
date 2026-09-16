<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Correctifs publics ciblés ajoutés après la composition 1.16.2.
 *
 * Le moteur de tarifs existant reste la source unique du rendu. Cette classe :
 * - remet le sélecteur annuel sur le shortcode tarifs groupes ;
 * - impose l'affichage des tarifs réduits en « Sur place » uniquement ;
 * - remplace les libellés de canal des tarifs groupes par un libellé générique « Tarif ».
 */
final class Parcs_HT_Tariff_Public_Fixes {
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_tableau_tarifs', array(__CLASS__, 'shortcode_public'));
        add_shortcode('parc_tarifs_groupes', array(__CLASS__, 'shortcode_group'));

        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_tableau_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Tariff_Public_Fixes::render_public($language, is_array($atts) ? $atts : array());
            });
            add_shortcode('parc_tarifs_groupes_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Tariff_Public_Fixes::render_group($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode_public($atts = array()) {
        return self::render_public(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function shortcode_group($atts = array()) {
        return self::render_group(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function language($language) {
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function t($language, $fr, $en, $de) {
        return $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
    }

    /**
     * Appelle une méthode privée du moteur de rendu existant afin de ne pas dupliquer
     * sa logique HTML/CSS. Le correctif reste ainsi limité à la préparation des données.
     */
    private static function display_call($method, $args = array()) {
        $reflection = new ReflectionMethod('Parcs_HT_Tariff_Display', $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs(null, $args);
    }

    public static function render_public($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);

        if (!class_exists('Parcs_HT_Public_Visibility')) {
            return Parcs_HT_Tariff_Display::render_public($language, array());
        }

        $late_style = self::display_call('ensure_assets');
        $years = Parcs_HT_Public_Visibility::tariff_years();
        if (!$years) return '';

        $requested = class_exists('Parcs_HT_Public_Seasons') ? Parcs_HT_Public_Seasons::requested_year() : '';
        $selected = Parcs_HT_Public_Visibility::default_year($years, $requested);

        self::$instance++;
        $id = 'parcs-ht-tariff-fix-years-' . self::$instance;
        $html = $late_style . '<div id="' . esc_attr($id) . '" class="parcs-ht-tariff-years-ui" data-htp-ui-years>';
        if (count($years) > 1) $html .= self::year_tabs($years, $selected, $language);

        foreach ($years as $year) {
            $html .= '<div class="parcs-ht-tariff-year-panel-ui" data-htp-ui-year-panel="' . esc_attr($year) . '"' . ((string)$year !== (string)$selected ? ' hidden' : '') . '>';
            $html .= self::render_public_year($language, (string)$year);
            $html .= '</div>';
        }

        return $html . '</div>';
    }

    private static function render_public_year($language, $year) {
        $settings = Parcs_HT_Defaults::settings($year);
        $season = Parcs_HT_Public_Visibility::raw_season($year);
        $source_tariffs = $season && isset($season['tariffs']) && is_array($season['tariffs'])
            ? $season['tariffs']
            : (isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array());

        $settings['tariffs'] = self::display_call('normalize_for_display', array($source_tariffs));
        if (!isset($settings['general']) || !is_array($settings['general'])) $settings['general'] = array();
        $settings['general']['year'] = $year;

        if (!in_array($year, Parcs_HT_Public_Visibility::retail_years(), true)) {
            $settings['tariffs']['individual'] = array();
            $settings['tariffs']['reduced'] = array();
        }
        if (!in_array($year, Parcs_HT_Public_Visibility::group_tariff_years(), true)) {
            $settings['tariffs']['groups'] = array();
        }

        $settings['tariffs'] = self::fix_tariff_data($settings['tariffs']);
        return self::display_call('render_public_settings', array($language, $settings));
    }

    /**
     * Les tarifs réduits sont vendus uniquement sur place. Si une ancienne donnée
     * a été rangée dans la cellule online, sa valeur est réutilisée uniquement pour
     * l'affichage Sur place, sans modifier les données enregistrées en base.
     */
    private static function fix_tariff_data($tariffs) {
        $tariffs = is_array($tariffs) ? $tariffs : array();
        if (!isset($tariffs['columns']) || !is_array($tariffs['columns'])) $tariffs['columns'] = array();

        $onsite_label = array('fr'=>'Sur place','en'=>'On site','de'=>'Vor Ort');
        $generic_label = array('fr'=>'Tarif','en'=>'Price','de'=>'Preis');

        if (isset($tariffs['reduced']) && is_array($tariffs['reduced'])) {
            foreach ($tariffs['reduced'] as &$row) {
                if (!is_array($row)) continue;
                if (!isset($row['cells']) || !is_array($row['cells'])) $row['cells'] = array();

                $onsite = isset($row['cells']['onsite']) && is_array($row['cells']['onsite']) ? $row['cells']['onsite'] : array();
                $onsite_value = trim((string)($onsite['value'] ?? ''));

                if ($onsite_value === '') {
                    $candidate = array();
                    if (isset($row['cells']['online']) && is_array($row['cells']['online']) && trim((string)($row['cells']['online']['value'] ?? '')) !== '') {
                        $candidate = $row['cells']['online'];
                    } else {
                        foreach ($row['cells'] as $cell) {
                            if (is_array($cell) && trim((string)($cell['value'] ?? '')) !== '') {
                                $candidate = $cell;
                                break;
                            }
                        }
                    }
                    if (!$candidate && trim((string)($row['price'] ?? '')) !== '') {
                        $candidate = array('value'=>(string)$row['price'],'old_value'=>'');
                    }
                    if ($candidate) $onsite = $candidate;
                }

                unset($onsite['url'], $onsite['purchase_url']);
                $row['cells']['onsite'] = $onsite;
                $row['cells']['online'] = array('value'=>'','old_value'=>'');
                if (isset($row['sale_channel'])) $row['sale_channel'] = 'onsite';
            }
            unset($row);
            $tariffs['columns']['reduced'] = array(array('id'=>'onsite','label'=>$onsite_label,'visible'=>'1'));
        }

        if (isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups'])) {
            foreach ($tariffs['columns']['groups'] as &$column) {
                if (!is_array($column)) continue;
                $column['label'] = $generic_label;
            }
            unset($column);
        }

        return $tariffs;
    }

    public static function render_group($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        $years = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::group_tariff_years()
            : (class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::public_years() : array());
        $years = self::order_years($years);

        if (!$years) {
            return self::generic_group_labels(Parcs_HT_Tariff_Display::render_group($language, array()), $language);
        }

        $requested = class_exists('Parcs_HT_Public_Seasons') ? Parcs_HT_Public_Seasons::requested_year() : '';
        $current = wp_date('Y');
        $selected = in_array((string)$requested, $years, true) ? (string)$requested : (in_array($current, $years, true) ? $current : $years[0]);

        self::$instance++;
        $id = 'parcs-ht-group-tariff-years-' . self::$instance;
        $html = '<div id="' . esc_attr($id) . '" class="parcs-ht-tariff-years-ui parcs-ht-group-tariff-years-ui" data-htp-ui-years>';
        if (count($years) > 1) $html .= self::year_tabs($years, $selected, $language);

        foreach ($years as $year) {
            $panel = Parcs_HT_Tariff_Display::render_group($language, array(), $year);
            $panel = self::generic_group_labels($panel, $language);
            $html .= '<div class="parcs-ht-tariff-year-panel-ui" data-htp-ui-year-panel="' . esc_attr($year) . '"' . ($year !== $selected ? ' hidden' : '') . '>' . $panel . '</div>';
        }

        return $html . '</div>';
    }

    private static function year_tabs($years, $selected, $language) {
        $label = self::t($language, 'Année des tarifs', 'Rate year', 'Tarifjahr');
        $html = '<div class="parcs-ht-tariff-year-tabs-ui" role="tablist" aria-label="' . esc_attr($label) . '">';
        foreach ($years as $year) {
            $active = (string)$year === (string)$selected;
            $html .= '<button type="button" class="parcs-ht-tariff-year-tab-ui' . ($active ? ' is-active' : '') . '" data-htp-ui-year="' . esc_attr($year) . '" role="tab" aria-selected="' . ($active ? 'true' : 'false') . '" tabindex="' . ($active ? '0' : '-1') . '">' . esc_html($year) . '</button>';
        }
        return $html . '</div>';
    }

    private static function order_years($years) {
        $years = array_values(array_unique(array_filter(array_map('strval', (array)$years), static function ($year) {
            return preg_match('/^20\d{2}$/', $year);
        })));
        $current = wp_date('Y');
        usort($years, static function ($a, $b) use ($current) {
            if ($a === $current) return -1;
            if ($b === $current) return 1;
            $a_future = $a > $current;
            $b_future = $b > $current;
            if ($a_future !== $b_future) return $a_future ? -1 : 1;
            return $a_future ? strcmp($a, $b) : strcmp($b, $a);
        });
        return $years;
    }

    private static function generic_group_labels($html, $language) {
        if ($html === '') return $html;
        $label = esc_html(self::t($language, 'Tarif', 'Price', 'Preis'));

        $html = preg_replace(
            '/(<span class="parcs-ht-tariff-ui__channel">).*?(<\/span>)/us',
            '$1' . $label . '$2',
            $html
        );
        $html = preg_replace_callback(
            '/(<div class="parcs-ht-tariff-ui__column-heads"[^>]*>)(.*?)(<\/div>)/us',
            static function ($match) use ($label) {
                $inner = preg_replace('/<span>.*?<\/span>/us', '<span>' . $label . '</span>', $match[2]);
                return $match[1] . $inner . $match[3];
            },
            $html
        );

        return $html;
    }
}
