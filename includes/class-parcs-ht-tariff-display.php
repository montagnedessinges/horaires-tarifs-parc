<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Rendu public neuf des tarifs.
 *
 * Cette classe ne modifie aucune donnée métier. Elle lit les structures tarifaires
 * existantes et reconstruit uniquement leur affichage public avec un HTML/CSS
 * indépendant de l'ancien composant tarifs.
 */
final class Parcs_HT_Tariff_Display {
    private static $instance = 0;
    private static $late_style_printed = false;

    public static function init() {
        add_shortcode('parc_tableau_tarifs', array(__CLASS__, 'shortcode_public'));
        add_shortcode('parc_tarifs_groupes', array(__CLASS__, 'shortcode_group'));
        add_shortcode('parc_horaires_tarifs', array(__CLASS__, 'shortcode_page'));

        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_tableau_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Tariff_Display::render_public($language, is_array($atts) ? $atts : array());
            });
            add_shortcode('parc_tarifs_groupes_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Tariff_Display::render_group($language, is_array($atts) ? $atts : array());
            });
            add_shortcode('parc_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Tariff_Display::render_page($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode_public($atts = array()) {
        return self::render_public(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function shortcode_group($atts = array()) {
        return self::render_group(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function shortcode_page($atts = array()) {
        return self::render_page(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function language($language) {
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function t($language, $fr, $en, $de) {
        return $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
    }

    private static function tr($value, $language, $fallback = '') {
        return Parcs_HT_Schedule::translation(is_array($value) ? $value : array(), $language, $fallback);
    }

    private static function ensure_assets() {
        if (!wp_style_is('parcs-ht-tariff-ui', 'registered')) {
            wp_register_style('parcs-ht-tariff-ui', PARCS_HT_URL . 'assets/tariffs-ui.css', array(), PARCS_HT_VERSION);
        }
        if (!wp_script_is('parcs-ht-tariff-ui', 'registered')) {
            wp_register_script('parcs-ht-tariff-ui', PARCS_HT_URL . 'assets/tariffs-ui.js', array(), PARCS_HT_VERSION, true);
        }
        wp_enqueue_style('parcs-ht-tariff-ui');
        wp_enqueue_script('parcs-ht-tariff-ui');

        if (!self::$late_style_printed && did_action('wp_head') && !wp_style_is('parcs-ht-tariff-ui', 'done')) {
            self::$late_style_printed = true;
            ob_start();
            wp_print_styles('parcs-ht-tariff-ui');
            return ob_get_clean();
        }
        return '';
    }

    private static function style_variables($general) {
        $general = is_array($general) ? $general : array();
        $vars = array(
            '--htp-primary' => sanitize_hex_color($general['primary_color'] ?? '') ?: '#006757',
            '--htp-secondary' => sanitize_hex_color($general['secondary_color'] ?? '') ?: '#31ad81',
            '--htp-accent' => sanitize_hex_color($general['accent_color'] ?? '') ?: '#ef7b5b',
            '--htp-highlight' => sanitize_hex_color($general['highlight_color'] ?? '') ?: '#e7c55b',
            '--htp-tariff-title' => sanitize_hex_color($general['tariff_title_color'] ?? '') ?: '#111111',
        );
        $style = '';
        foreach ($vars as $key=>$value) $style .= $key . ':' . $value . ';';
        return $style;
    }

    public static function render_page($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        $today = class_exists('Parcs_HT_Shortcodes') ? Parcs_HT_Shortcodes::render('today', $language, array()) : '';
        $calendar = class_exists('Parcs_HT_Shortcodes') ? Parcs_HT_Shortcodes::render('calendar', $language, array()) : '';
        $tariffs = self::render_public($language, array());
        return '<div class="parcs-ht-page parcs-ht-page-v2" data-htp-lang="' . esc_attr($language) . '">' . $today . $calendar . $tariffs . '</div>';
    }

    public static function render_public($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        $late_style = self::ensure_assets();
        $settings = Parcs_HT_Defaults::settings();

        if (class_exists('Parcs_HT_Display_Policy') && class_exists('Parcs_HT_Group_Tariff_Settings')) {
            $years = array_values(array_unique(array_merge(
                (array)Parcs_HT_Display_Policy::retail_years(),
                (array)Parcs_HT_Group_Tariff_Settings::public_years()
            )));
            sort($years, SORT_NUMERIC);
            if (!$years) return '';
            $requested = class_exists('Parcs_HT_Public_Seasons') ? Parcs_HT_Public_Seasons::requested_year() : '';
            $current = wp_date('Y');
            $selected = in_array($requested, $years, true) ? $requested : (in_array($current, $years, true) ? $current : (string)end($years));

            self::$instance++;
            $id = 'parcs-ht-tariff-ui-' . self::$instance;
            $html = $late_style . '<div id="' . esc_attr($id) . '" class="parcs-ht-tariff-years-ui" data-htp-ui-years>';
            if (count($years) > 1) {
                $html .= self::year_tabs($id, $years, $selected, $language);
            }
            foreach ($years as $year) {
                $html .= '<div class="parcs-ht-tariff-year-panel-ui" data-htp-ui-year-panel="' . esc_attr($year) . '"' . ($year !== $selected ? ' hidden' : '') . '>';
                $html .= self::render_public_year($language, $settings, $year);
                $html .= '</div>';
            }
            return $html . '</div>';
        }

        if (class_exists('Parcs_HT_Tariff_Seasons')) {
            $settings = Parcs_HT_Tariff_Seasons::select_season_tariffs($settings, true);
        }
        return $late_style . self::render_public_settings($language, $settings);
    }

    private static function year_tabs($id, $years, $selected, $language) {
        $label = self::t($language, 'Année des tarifs', 'Rate year', 'Tarifjahr');
        $html = '<div class="parcs-ht-tariff-year-tabs-ui" role="tablist" aria-label="' . esc_attr($label) . '">';
        foreach ($years as $year) {
            $active = (string)$year === (string)$selected;
            $html .= '<button type="button" class="parcs-ht-tariff-year-tab-ui' . ($active ? ' is-active' : '') . '" data-htp-ui-year="' . esc_attr($year) . '" role="tab" aria-selected="' . ($active ? 'true' : 'false') . '">' . esc_html($year) . '</button>';
        }
        return $html . '</div>';
    }

    private static function render_public_year($language, $base_settings, $year) {
        $settings = $base_settings;
        if (class_exists('Parcs_HT_Display_Policy')) {
            $season = Parcs_HT_Display_Policy::raw_season($year);
            $settings['tariffs'] = Parcs_HT_Display_Policy::normalize_tariffs($season['tariffs'] ?? array());
            if (!isset($settings['general']) || !is_array($settings['general'])) $settings['general'] = array();
            $settings['general']['year'] = (string)$year;
            if (!in_array((string)$year, (array)Parcs_HT_Display_Policy::retail_years(), true)) {
                $settings['tariffs']['individual'] = array();
                $settings['tariffs']['reduced'] = array();
            }
            if (class_exists('Parcs_HT_Group_Tariff_Settings') && !in_array((string)$year, (array)Parcs_HT_Group_Tariff_Settings::public_years(), true)) {
                $settings['tariffs']['groups'] = array();
            }
        }
        return self::render_public_settings($language, $settings);
    }

    private static function render_public_settings($language, $settings) {
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $dicts = Parcs_HT_Schedule::dictionaries();
        $d = isset($dicts[$language]) ? $dicts[$language] : $dicts['fr'];
        $labels = array(
            'individual' => $d['individual'] ?? self::t($language, 'Individuels', 'Individuals', 'Einzelpreise'),
            'reduced' => $d['reduced'] ?? self::t($language, 'Tarifs réduits', 'Reduced rates', 'Ermäßigt'),
            'groups' => $d['groups'] ?? self::t($language, 'Groupes', 'Groups', 'Gruppen'),
        );
        $order = isset($tariffs['group_order']) && is_array($tariffs['group_order']) ? $tariffs['group_order'] : array_keys($labels);
        $groups = array();
        foreach ($order as $key) if (isset($labels[$key]) && !isset($groups[$key]) && self::visible_rows($tariffs[$key] ?? array())) $groups[$key] = $labels[$key];
        foreach ($labels as $key=>$label) if (!isset($groups[$key]) && self::visible_rows($tariffs[$key] ?? array())) $groups[$key] = $label;
        if (!$groups) return '';

        self::$instance++;
        $id = 'parcs-ht-tariff-table-ui-' . self::$instance;
        $year = trim((string)($general['year'] ?? ''));
        $tickets_url = self::translated_url($general['tickets_url'] ?? array(), $language);
        $style = self::style_variables($general);

        $html = '<section id="' . esc_attr($id) . '" class="parcs-ht-tariff-ui" data-htp-ui-tariffs style="' . esc_attr($style) . '">';
        $html .= '<header class="parcs-ht-tariff-ui__header"><div class="parcs-ht-tariff-ui__title" role="heading" aria-level="2">' . esc_html($d['prices'] ?? self::t($language, 'Tarifs', 'Prices', 'Preise')) . '</div></header>';
        $html .= self::public_payment_strip($tariffs, $language, $d);
        $html .= self::category_tabs($id, $groups);

        $first = true;
        foreach ($groups as $key=>$label) {
            $html .= '<div id="' . esc_attr($id . '-panel-' . $key) . '" class="parcs-ht-tariff-ui__panel" role="tabpanel" aria-labelledby="' . esc_attr($id . '-tab-' . $key) . '" data-htp-ui-panel="' . esc_attr($key) . '"' . (!$first ? ' hidden' : '') . '>';
            $html .= self::price_table($tariffs, $key, $language, array('tickets_url'=>$tickets_url));
            if ($key === 'reduced') {
                $note = self::tr($tariffs['notes'] ?? array(), $language, '');
                if ($note !== '') $html .= '<p class="parcs-ht-tariff-ui__note">' . esc_html($note) . '</p>';
            }
            if ($key === 'groups') $html .= self::public_group_extras($general, $language, $d);
            $html .= '</div>';
            $first = false;
        }

        if ($tickets_url !== '') {
            $html .= '<div class="parcs-ht-tariff-ui__actions"><a class="parcs-ht-tariff-ui__button is-primary" href="' . esc_url($tickets_url) . '">' . esc_html($d['tickets'] ?? self::t($language, 'Acheter vos billets', 'Buy tickets', 'Tickets kaufen')) . '</a></div>';
        }
        $html .= self::export_actions($tariffs, $language, $year);
        return $html . '</section>';
    }

    private static function category_tabs($id, $groups) {
        if (count($groups) < 2) return '';
        $html = '<div class="parcs-ht-tariff-ui__tabs" role="tablist">';
        $first = true;
        foreach ($groups as $key=>$label) {
            $html .= '<button type="button" id="' . esc_attr($id . '-tab-' . $key) . '" class="parcs-ht-tariff-ui__tab' . ($first ? ' is-active' : '') . '" data-htp-ui-tab="' . esc_attr($key) . '" aria-controls="' . esc_attr($id . '-panel-' . $key) . '" aria-selected="' . ($first ? 'true' : 'false') . '" role="tab">' . esc_html($label) . '</button>';
            $first = false;
        }
        return $html . '</div>';
    }

    private static function visible_rows($rows) {
        $out = array();
        foreach ((array)$rows as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            if (!self::row_is_visible($row)) continue;
            $out[] = $row;
        }
        return $out;
    }

    private static function row_is_visible($row) {
        if (($row['row_type'] ?? 'standard') !== 'special') return true;
        $today = wp_date('Y-m-d', null, new DateTimeZone(Parcs_HT_Schedule::timezone(Parcs_HT_Defaults::all_settings())));
        $from = (string)($row['display_from'] ?? '');
        $to = (string)($row['display_to'] ?? '');
        if ($from !== '' && $today < $from) return false;
        if ($to !== '' && $today > $to) return false;
        return true;
    }

    private static function columns($tariffs, $group) {
        $columns = isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group]) ? $tariffs['columns'][$group] : array();
        $out = array();
        $seen = array();
        foreach ($columns as $column) {
            if (!is_array($column)) continue;
            if (isset($column['visible']) && (string)$column['visible'] === '0') continue;
            $id = sanitize_key((string)($column['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $out[] = array('id'=>$id,'label'=>isset($column['label']) && is_array($column['label']) ? $column['label'] : array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'));
        }
        if (!$out && !isset($tariffs['columns'][$group])) {
            $out[] = array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'));
        }
        return $out;
    }

    private static function price_table($tariffs, $group, $language, $options = array()) {
        $columns = self::columns($tariffs, $group);
        $rows = self::visible_rows($tariffs[$group] ?? array());
        if (!$columns || !$rows) return '';
        $html = '<div class="parcs-ht-tariff-ui__list">';
        foreach ($rows as $row) {
            $cells = self::row_cells($row, $columns, $language, $options);
            if (!$cells) continue;
            $is_special = (string)($row['row_type'] ?? 'standard') === 'special';
            $label = self::tr($row['label'] ?? array(), $language, '');
            $subtitle = self::tr($row['subtitle'] ?? ($row['detail'] ?? array()), $language, '');
            $note = self::tr($row['note'] ?? array(), $language, '');
            $badge = $is_special ? self::tr($row['special_badge'] ?? array(), $language, '') : '';
            $meta = $is_special ? self::special_meta($row, $language) : array();
            $row_style = self::row_style($row, $options['row_styles'] ?? array());
            $html .= '<article class="parcs-ht-tariff-ui__row' . ($is_special ? ' is-special' : '') . '"' . ($row_style !== '' ? ' style="' . esc_attr($row_style) . '"' : '') . '>';
            $html .= '<div class="parcs-ht-tariff-ui__identity"><div class="parcs-ht-tariff-ui__label-line">';
            if ($is_special && (string)($row['show_special_dot'] ?? '1') === '1') $html .= '<span class="parcs-ht-tariff-ui__special-dot" aria-hidden="true"></span>';
            $html .= '<strong>' . esc_html($label) . '</strong>';
            if ($badge !== '') $html .= '<span class="parcs-ht-tariff-ui__badge">' . esc_html($badge) . '</span>';
            $html .= '</div>';
            if ($subtitle !== '') $html .= '<small class="parcs-ht-tariff-ui__subtitle">' . esc_html($subtitle) . '</small>';
            if ($note !== '') $html .= '<small class="parcs-ht-tariff-ui__row-note">' . esc_html($note) . '</small>';
            foreach ($meta as $line) $html .= '<small class="parcs-ht-tariff-ui__meta">' . esc_html($line) . '</small>';
            $html .= '</div>';
            $html .= '<div class="parcs-ht-tariff-ui__values" style="--htp-ui-value-count:' . (int)count($cells) . '">' . implode('', $cells) . '</div>';
            $html .= '</article>';
        }
        return $html . '</div>';
    }

    private static function row_cells($row, $columns, $language, $options) {
        $out = array();
        $is_special = (string)($row['row_type'] ?? 'standard') === 'special';
        $row_purchase = self::translated_url($row['purchase_url'] ?? array(), $language);
        foreach ($columns as $column) {
            $id = $column['id'];
            $cell = isset($row['cells'][$id]) && is_array($row['cells'][$id]) ? $row['cells'][$id] : array();
            $value = isset($cell['value']) ? trim((string)$cell['value']) : ($id === 'price' ? trim((string)($row['price'] ?? '')) : '');
            if ($value === '') continue;
            $old = trim((string)($cell['old_value'] ?? ''));
            $label = self::tr($column['label'], $language, '');
            $online = self::is_online_channel($id, $label);
            $url = self::cell_url($cell, $language);
            if ($url === '' && $online) $url = (string)($options['tickets_url'] ?? '');
            if ($url === '' && $is_special && $row_purchase !== '') {
                $sale_channel = (string)($row['sale_channel'] ?? 'both');
                if ($sale_channel === 'both' || ($sale_channel === 'online' && $online) || ($sale_channel === 'onsite' && !$online)) $url = $row_purchase;
            }
            $tag = $url !== '' ? 'a' : 'div';
            $attrs = $url !== '' ? ' href="' . esc_url($url) . '"' : '';
            $classes = 'parcs-ht-tariff-ui__price' . ($online ? ' is-online' : ' is-onsite') . ($url !== '' ? ' is-clickable' : '');
            $html = '<' . $tag . ' class="' . esc_attr($classes) . '" data-htp-channel="' . esc_attr($id) . '"' . $attrs . '>';
            if ($label !== '') $html .= '<span class="parcs-ht-tariff-ui__channel">' . esc_html($label) . '</span>';
            if ($is_special && $old !== '') $html .= '<del>' . esc_html($old) . '</del>';
            $html .= '<b>' . esc_html($value) . '</b>';
            if ($url !== '') $html .= '<span class="screen-reader-text"> — ' . esc_html(self::t($language, 'ouvrir la billetterie', 'open ticketing', 'Ticketshop öffnen')) . '</span>';
            $html .= '</' . $tag . '>';
            $out[] = $html;
        }
        return $out;
    }

    private static function cell_url($cell, $language) {
        foreach (array('purchase_url','url') as $key) {
            if (!isset($cell[$key])) continue;
            if (is_array($cell[$key])) {
                $value = self::translated_url($cell[$key], $language);
            } else {
                $value = trim((string)$cell[$key]);
            }
            if ($value !== '') return $value;
        }
        return '';
    }

    private static function translated_url($value, $language) {
        if (is_array($value)) {
            $url = trim((string)($value[$language] ?? ''));
            if ($url === '') $url = trim((string)($value['fr'] ?? ''));
            return $url;
        }
        return trim((string)$value);
    }

    private static function is_online_channel($id, $label) {
        $id = strtolower((string)$id);
        if (in_array($id, array('online','en_ligne','web','internet','online_price','web_price'), true)) return true;
        $label = strtolower(remove_accents((string)$label));
        return strpos($label, 'en ligne') !== false || strpos($label, 'online') !== false || strpos($label, 'web') !== false;
    }

    private static function row_style($row, $external_styles = array()) {
        $styles = is_array($external_styles) ? $external_styles : array();
        $row_id = sanitize_key((string)($row['id'] ?? ''));
        if ($row_id !== '' && isset($styles[$row_id]) && is_array($styles[$row_id])) $styles = array_replace($row, $styles[$row_id]);
        else $styles = $row;
        $style = '';
        $map = array(
            'label_color'=>'--htp-ui-row-label',
            'subtitle_color'=>'--htp-ui-row-detail',
            'note_color'=>'--htp-ui-row-note',
            'price_color'=>'--htp-ui-row-price',
            'row_border_color'=>'--htp-ui-row-border',
        );
        foreach ($map as $key=>$var) {
            if (!empty($styles[$key]) && ($color = sanitize_hex_color((string)$styles[$key]))) $style .= $var . ':' . $color . ';';
        }
        if (!empty($styles['row_bg_transparent']) && (string)$styles['row_bg_transparent'] === '1') {
            $style .= '--htp-ui-row-bg:transparent;';
        } elseif (!empty($styles['row_bg_color']) && ($color = sanitize_hex_color((string)$styles['row_bg_color']))) {
            $style .= '--htp-ui-row-bg:' . $color . ';';
        }
        return $style;
    }

    private static function special_meta($row, $language) {
        $lines = array();
        $from = (string)($row['valid_from'] ?? '');
        $to = (string)($row['valid_to'] ?? '');
        if ($from !== '' || $to !== '') {
            $a = self::date_label($from, $language);
            $b = self::date_label($to, $language);
            if ($language === 'en') $lines[] = ($from && $to) ? 'Valid from ' . $a . ' to ' . $b : ($from ? 'Valid from ' . $a : 'Valid until ' . $b);
            elseif ($language === 'de') $lines[] = ($from && $to) ? 'Gültig vom ' . $a . ' bis ' . $b : ($from ? 'Gültig ab ' . $a : 'Gültig bis ' . $b);
            else $lines[] = ($from && $to) ? 'Valable du ' . $a . ' au ' . $b : ($from ? 'Valable à partir du ' . $a : 'Valable jusqu’au ' . $b);
        }
        $channel = (string)($row['sale_channel'] ?? 'both');
        if ($channel === 'online') $lines[] = self::t($language, 'Uniquement en ligne', 'Online only', 'Nur online');
        if ($channel === 'onsite') $lines[] = self::t($language, 'Uniquement sur place', 'On-site only', 'Nur vor Ort');
        return $lines;
    }

    private static function date_label($value, $language) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$value, $m)) return (string)$value;
        if ($language === 'en') return $m[3] . '/' . $m[2] . '/' . $m[1];
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private static function public_group_extras($general, $language, $d) {
        $html = '';
        $booking = self::tr($general['groups_booking_note'] ?? array(), $language, '');
        if ($booking !== '') $html .= '<p class="parcs-ht-tariff-ui__info">' . nl2br(esc_html($booking)) . '</p>';
        $url = self::translated_url($general['groups_url'] ?? array(), $language);
        $label = self::tr($general['groups_button_label'] ?? array(), $language, '');
        if ($label === '') $label = $d['quote'] ?? self::t($language, 'Faire une demande de devis', 'Request a quote', 'Angebot anfordern');
        if ($url !== '') $html .= '<div class="parcs-ht-tariff-ui__actions"><a class="parcs-ht-tariff-ui__button" href="' . esc_url($url) . '">' . esc_html($label) . '</a></div>';
        return $html;
    }

    private static function public_payment_strip($tariffs, $language, $d) {
        $items = isset($tariffs['payment_items']) && is_array($tariffs['payment_items']) ? $tariffs['payment_items'] : array();
        $normalized = array();
        foreach ($items as $item) {
            if (!is_array($item) || (string)($item['enabled'] ?? '0') !== '1') continue;
            if (isset($item['visible'][$language]) && (string)$item['visible'][$language] !== '1') continue;
            $label = self::tr($item['label'] ?? array(), $language, '');
            if ($label === '') continue;
            $normalized[] = array(
                'label'=>$label,
                'icon'=>(string)($item['icon'] ?? 'card'),
                'custom_svg'=>(string)($item['custom_svg'] ?? ''),
                'style'=>self::payment_item_style($item),
            );
        }
        $title = $d['payments'] ?? self::t($language, 'Moyens de paiement', 'Payment methods', 'Zahlungsmöglichkeiten');
        return self::payment_strip($title, $normalized);
    }

    private static function payment_strip($title, $items) {
        if (!$items) return '';
        $html = '<div class="parcs-ht-tariff-ui__payments" aria-label="' . esc_attr($title) . '"><strong>' . esc_html($title) . '</strong><div class="parcs-ht-tariff-ui__payment-track">';
        foreach ($items as $item) {
            $html .= '<span class="parcs-ht-tariff-ui__payment-chip"' . (!empty($item['style']) ? ' style="' . esc_attr($item['style']) . '"' : '') . '>';
            $html .= '<span class="parcs-ht-tariff-ui__payment-icon" aria-hidden="true">' . wp_kses(self::payment_icon($item['icon'] ?? 'other', $item['custom_svg'] ?? ''), Parcs_HT_Defaults::svg_allowed_tags()) . '</span>';
            $html .= '<span>' . esc_html($item['label']) . '</span></span>';
        }
        return $html . '</div></div>';
    }

    private static function payment_item_style($item) {
        $style = '';
        if (!empty($item['bg_transparent']) && (string)$item['bg_transparent'] === '1') $style .= '--htp-ui-payment-bg:transparent;';
        elseif (!empty($item['bg_color']) && ($c = sanitize_hex_color((string)$item['bg_color']))) $style .= '--htp-ui-payment-bg:' . $c . ';';
        if (!empty($item['text_color']) && ($c = sanitize_hex_color((string)$item['text_color']))) $style .= '--htp-ui-payment-text:' . $c . ';';
        if (!empty($item['icon_color']) && ($c = sanitize_hex_color((string)$item['icon_color']))) $style .= '--htp-ui-payment-icon:' . $c . ';';
        if (!empty($item['border_color']) && ($c = sanitize_hex_color((string)$item['border_color']))) $style .= '--htp-ui-payment-border:' . $c . ';';
        return $style;
    }

    private static function payment_icon($icon, $custom = '') {
        $common = 'viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"';
        switch (sanitize_key((string)$icon)) {
            case 'custom': return trim((string)$custom);
            case 'cash': return '<svg ' . $common . '><rect x="2.5" y="5.5" width="15.5" height="10.5" rx="1.8"/><circle cx="10.2" cy="10.8" r="2.2"/><circle cx="18" cy="16.7" r="3.3"/></svg>';
            case 'cheque': return '<svg ' . $common . '><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M5.5 9h7M5.5 12.5h4.5M14.5 14.2l1.7 1.7 3.1-3.4"/></svg>';
            case 'document': return '<svg ' . $common . '><path d="M6 2.8h8l4 4V21H6z"/><path d="M14 2.8V7h4M9 11h6M9 14.5h6M9 18h4"/></svg>';
            case 'chorus': case 'bank': return '<svg ' . $common . '><path d="M3 20h18M5 20V9h14v11M8 20v-7M12 20v-7M16 20v-7M3.5 9 12 4l8.5 5"/></svg>';
            case 'online': case 'connect': return '<svg ' . $common . '><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>';
            case 'card': return '<svg ' . $common . '><rect x="2.5" y="4.8" width="19" height="14.4" rx="2.4"/><path d="M2.5 9.1h19"/><rect x="6" y="13" width="4.6" height="2.4" rx=".5" fill="currentColor" stroke="none"/></svg>';
            default: return '<svg ' . $common . '><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>';
        }
    }

    private static function export_actions($tariffs, $language, $year) {
        $print = isset($tariffs['print']) && is_array($tariffs['print']) ? $tariffs['print'] : array();
        if (isset($print['enabled']) && (string)$print['enabled'] !== '1') return '';
        $params = array('lang'=>$language);
        if ($year !== '') $params['year'] = $year;
        $html = '<div class="parcs-ht-tariff-ui__export">';
        $print_url = add_query_arg(array_merge(array('action'=>'parcs_ht_tariffs_print'), $params), admin_url('admin-post.php'));
        $html .= '<a href="' . esc_url($print_url) . '">' . esc_html(self::t($language, 'Imprimer les tarifs', 'Print rates', 'Tarife drucken')) . '</a>';
        if (!isset($print['pdf_enabled']) || (string)$print['pdf_enabled'] === '1') {
            $pdf_url = add_query_arg(array_merge(array('action'=>'parcs_ht_tariffs_pdf'), $params), admin_url('admin-post.php'));
            $html .= '<a href="' . esc_url($pdf_url) . '">' . esc_html(self::t($language, 'Télécharger en PDF', 'Download PDF', 'PDF herunterladen')) . '</a>';
        }
        return $html . '</div>';
    }

    public static function render_group($language, $atts = array(), $forced_year = '') {
        unset($atts);
        $language = self::language($language);
        $late_style = self::ensure_assets();
        $year = preg_match('/^20\d{2}$/', (string)$forced_year) ? (string)$forced_year : '';
        if ($year === '' && class_exists('Parcs_HT_Group_Tariff_Settings')) $year = (string)Parcs_HT_Group_Tariff_Settings::public_year();
        if ($year === '') $year = wp_date('Y');

        $settings = Parcs_HT_Defaults::settings($year);
        if (class_exists('Parcs_HT_Display_Policy')) {
            $season = Parcs_HT_Display_Policy::raw_season($year);
            if (is_array($season)) $settings['tariffs'] = Parcs_HT_Display_Policy::normalize_tariffs($season['tariffs'] ?? array());
        }
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        if (!self::visible_rows($tariffs['groups'] ?? array())) {
            return $late_style . '<p class="parcs-ht-tariff-ui__empty">' . esc_html(self::t($language, 'Les tarifs groupes ne sont pas disponibles pour le moment.', 'Group rates are not available at the moment.', 'Die Gruppentarife sind derzeit nicht verfügbar.')) . '</p>';
        }

        $display = class_exists('Parcs_HT_Group_Tariff_Settings') ? (array)Parcs_HT_Group_Tariff_Settings::settings($year) : array();
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $style = self::style_variables($general);
        $title = self::tr($display['title'] ?? array(), $language, '');
        if ($title === '') $title = self::t($language, 'Tarifs groupes', 'Group rates', 'Gruppentarife') . ' ' . $year;
        $intro = self::tr($display['intro'] ?? array(), $language, '');
        $show_heading = !isset($display['show_heading']) || (string)$display['show_heading'] === '1';

        self::$instance++;
        $id = 'parcs-ht-group-tariff-ui-' . self::$instance;
        $html = $late_style . '<section id="' . esc_attr($id) . '" class="parcs-ht-tariff-ui parcs-ht-tariff-ui--groups" data-htp-ui-tariffs style="' . esc_attr($style) . '">';
        if ($show_heading || $intro !== '') {
            $html .= '<header class="parcs-ht-tariff-ui__header">';
            if ($show_heading) $html .= '<div class="parcs-ht-tariff-ui__title" role="heading" aria-level="2">' . esc_html($title) . '</div>';
            if ($intro !== '') $html .= '<p class="parcs-ht-tariff-ui__intro">' . nl2br(esc_html($intro)) . '</p>';
            $html .= '</header>';
        }
        $html .= self::group_payment_strip($display, $language);
        $html .= '<div class="parcs-ht-tariff-ui__panel is-single">';
        $html .= self::price_table($tariffs, 'groups', $language, array('row_styles'=>$display['row_styles'] ?? array()));
        $html .= self::group_info($display, $language, $general);
        $html .= '</div></section>';
        return $html;
    }

    private static function group_payment_strip($display, $language) {
        if ((string)($display['show_payment_methods'] ?? '0') !== '1') return '';
        $items = isset($display['payment_methods']) && is_array($display['payment_methods']) ? $display['payment_methods'] : array();
        $normalized = array();
        foreach ($items as $item) {
            if (!is_array($item) || (isset($item['enabled']) && (string)$item['enabled'] === '0')) continue;
            $label = self::tr($item['label'] ?? array(), $language, '');
            if ($label === '') continue;
            $normalized[] = array('label'=>$label,'icon'=>(string)($item['icon'] ?? 'other'),'custom_svg'=>'','style'=>'');
        }
        $title = self::tr($display['payment_title'] ?? array(), $language, '');
        if ($title === '') $title = self::t($language, 'Moyens de paiement', 'Payment methods', 'Zahlungsmöglichkeiten');
        return self::payment_strip($title, $normalized);
    }

    private static function group_info($display, $language, $general) {
        $html = '';
        if ((string)($display['show_info_blocks'] ?? '0') === '1') {
            foreach ((array)($display['info_blocks'] ?? array()) as $block) {
                if (!is_array($block) || (isset($block['enabled']) && (string)$block['enabled'] === '0')) continue;
                $title = self::tr($block['title'] ?? array(), $language, '');
                $text = self::tr($block['text'] ?? array(), $language, '');
                if ($title === '' && $text === '') continue;
                $html .= '<article class="parcs-ht-tariff-ui__info-card">';
                if ($title !== '') $html .= '<strong>' . esc_html($title) . '</strong>';
                if ($text !== '') $html .= '<p>' . nl2br(esc_html($text)) . '</p>';
                $html .= '</article>';
            }
        } else {
            $booking = self::tr($general['groups_booking_note'] ?? array(), $language, '');
            if ($booking !== '') $html .= '<p class="parcs-ht-tariff-ui__info">' . nl2br(esc_html($booking)) . '</p>';
        }

        $show_button = !isset($display['show_quote_button']) || (string)$display['show_quote_button'] === '1';
        $url = self::translated_url($display['button_url'] ?? array(), $language);
        if ($url === '') $url = self::translated_url($general['groups_url'] ?? array(), $language);
        $label = self::tr($display['button_label'] ?? array(), $language, '');
        if ($label === '') $label = self::tr($general['groups_button_label'] ?? array(), $language, '');
        if ($label === '') $label = self::t($language, 'Faire une demande de devis', 'Request a quote', 'Angebot anfordern');
        if ($show_button && $url !== '') $html .= '<div class="parcs-ht-tariff-ui__actions"><a class="parcs-ht-tariff-ui__button" href="' . esc_url($url) . '">' . esc_html($label) . '</a></div>';
        return $html;
    }
}
