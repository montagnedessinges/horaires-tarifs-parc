<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Shortcode autonome d'affichage des tarifs groupes.
 *
 * Source unique des prix : la même grille tariffs.groups de la saison publique
 * que celle utilisée par [parc_tableau_tarifs]. Aucun tarif n'est dupliqué ici.
 */
final class Parcs_HT_Group_Tariffs {
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_tarifs_groupes', array(__CLASS__, 'shortcode'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_tarifs_groupes_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Group_Tariffs::render($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode($atts = array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function selected_settings() {
        $settings = Parcs_HT_Defaults::settings();
        if (class_exists('Parcs_HT_Tariff_Seasons')) {
            $settings = Parcs_HT_Tariff_Seasons::select_season_tariffs($settings, true);
        }
        return is_array($settings) ? $settings : array();
    }

    private static function translation($value, $language, $fallback = '') {
        return Parcs_HT_Schedule::translation(is_array($value) ? $value : array(), $language, $fallback);
    }

    private static function display_settings($year) {
        if (class_exists('Parcs_HT_Group_Tariff_Settings')) {
            $settings = Parcs_HT_Group_Tariff_Settings::settings($year);
            if (is_array($settings)) return $settings;
        }
        return array(
            'show_heading'=>'1','title'=>array(),'intro'=>array(),
            'show_quote_button'=>'1','button_label'=>array(),'button_url'=>array(),
            'show_payment_methods'=>'0','payment_title'=>array(),'payment_methods'=>array(),
            'show_info_blocks'=>'0','info_blocks'=>array(),
        );
    }
    private static function columns($tariffs) {
        $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups'])
            ? $tariffs['columns']['groups'] : array();
        $out = array();
        $seen = array();

        foreach ($columns as $column) {
            if (!is_array($column)) continue;
            if (isset($column['visible']) && (string)$column['visible'] === '0') continue;
            $id = sanitize_key(isset($column['id']) ? $column['id'] : '');
            if ($id === '' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $out[] = array(
                'id'=>$id,
                'label'=>isset($column['label']) && is_array($column['label'])
                    ? $column['label'] : array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'),
            );
        }

        // Même secours historique que le tableau principal : uniquement si aucune
        // configuration de colonnes n'existe, jamais si une colonne a été masquée.
        if (!$out && !isset($tariffs['columns']['groups'])) {
            $out[] = array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'));
        }
        return $out;
    }

    private static function row_is_visible($row) {
        if (!is_array($row) || ($row['row_type'] ?? 'standard') !== 'special') return true;
        $today = wp_date('Y-m-d', null, new DateTimeZone(Parcs_HT_Schedule::timezone(Parcs_HT_Defaults::all_settings())));
        $from = (string)($row['display_from'] ?? '');
        $to = (string)($row['display_to'] ?? '');
        if ($from !== '' && $today < $from) return false;
        if ($to !== '' && $today > $to) return false;
        return true;
    }

    private static function row_style($row, $display) {
        $row_id = sanitize_key((string)($row['id'] ?? ''));
        $styles = isset($display['row_styles'][$row_id]) && is_array($display['row_styles'][$row_id]) ? $display['row_styles'][$row_id] : array();
        $style = '';
        $map = array(
            'label_color'=>'--htp-row-label',
            'subtitle_color'=>'--htp-row-detail',
            'note_color'=>'--htp-row-note',
            'price_color'=>'--htp-row-price',
            'row_border_color'=>'--htp-row-border',
        );
        foreach ($map as $key=>$var) {
            if (!empty($styles[$key]) && ($color = sanitize_hex_color($styles[$key]))) $style .= $var . ':' . $color . ';';
        }
        if (!empty($styles['row_bg_transparent']) && (string)$styles['row_bg_transparent'] === '1') {
            $style .= '--htp-row-bg:transparent;';
        } elseif (!empty($styles['row_bg_color']) && ($color = sanitize_hex_color($styles['row_bg_color']))) {
            $style .= '--htp-row-bg:' . $color . ';';
        }
        return $style;
    }

    private static function special_offer_meta($row, $language) {
        $lines = array();
        $from = (string)($row['valid_from'] ?? '');
        $to = (string)($row['valid_to'] ?? '');
        if ($from !== '' || $to !== '') {
            $from_text = self::date_label($from, $language);
            $to_text = self::date_label($to, $language);
            if ($language === 'en') {
                $lines[] = ($from && $to) ? 'Valid from ' . $from_text . ' to ' . $to_text : ($from ? 'Valid from ' . $from_text : 'Valid until ' . $to_text);
            } elseif ($language === 'de') {
                $lines[] = ($from && $to) ? 'Gültig vom ' . $from_text . ' bis ' . $to_text : ($from ? 'Gültig ab ' . $from_text : 'Gültig bis ' . $to_text);
            } else {
                $lines[] = ($from && $to) ? 'Valable du ' . $from_text . ' au ' . $to_text : ($from ? 'Valable à partir du ' . $from_text : 'Valable jusqu’au ' . $to_text);
            }
        }
        $channel = (string)($row['sale_channel'] ?? 'both');
        if ($channel === 'online') $lines[] = $language === 'en' ? 'Online only' : ($language === 'de' ? 'Nur online' : 'Uniquement en ligne');
        if ($channel === 'onsite') $lines[] = $language === 'en' ? 'On-site only' : ($language === 'de' ? 'Nur vor Ort' : 'Uniquement sur place');
        return $lines;
    }

    private static function date_label($value, $language) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$value, $m)) return (string)$value;
        $months = array(
            'fr'=>array(1=>'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'),
            'en'=>array(1=>'January','February','March','April','May','June','July','August','September','October','November','December'),
            'de'=>array(1=>'Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'),
        );
        $year=(int)$m[1]; $month=(int)$m[2]; $day=(int)$m[3];
        if ($language === 'de') return $day . '. ' . $months['de'][$month] . ' ' . $year;
        return $day . ' ' . $months[$language === 'en' ? 'en' : 'fr'][$month] . ' ' . $year;
    }

    private static function buy_label($language) {
        if ($language === 'en') return 'Buy';
        if ($language === 'de') return 'Kaufen';
        return 'Acheter';
    }

    private static function default_payment_title($language) {
        if ($language === 'en') return 'Payment methods';
        if ($language === 'de') return 'Zahlungsmöglichkeiten';
        return 'Moyens de paiement';
    }

    private static function payment_icon_svg($icon) {
        $common = 'viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"';
        switch ($icon) {
            case 'cash':
                return '<svg ' . $common . '><rect x="2.5" y="5.5" width="15.5" height="10.5" rx="1.8"/><circle cx="10.2" cy="10.8" r="2.2"/><circle cx="18" cy="16.7" r="3.3"/><path d="M16.7 16.7h2.6"/></svg>';
            case 'cheque':
                return '<svg ' . $common . '><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M5.5 9h7M5.5 12.5h4.5M14.5 14.2l1.7 1.7 3.1-3.4"/></svg>';
            case 'document':
                return '<svg ' . $common . '><path d="M6 2.8h8l4 4V21H6z"/><path d="M14 2.8V7h4M9 11h6M9 14.5h6M9 18h4"/></svg>';
            case 'chorus':
                return '<svg ' . $common . '><path d="M3 20.5h18M5 20.5V9.5h14v11M8 20.5v-7h3v7M14 20.5v-7h3v7M4 9.5 12 4l8 5.5"/></svg>';
            case 'bank':
                return '<svg ' . $common . '><path d="M3 20h18M5 20V9h14v11M8 20v-7M12 20v-7M16 20v-7M3.5 9 12 4l8.5 5"/></svg>';
            case 'online':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>';
            case 'other':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>';
            case 'card':
            default:
                return '<svg ' . $common . '><rect x="2.5" y="4.8" width="19" height="14.4" rx="2.4"/><path d="M2.5 9.1h19"/><rect x="6" y="13" width="4.6" height="2.4" rx=".5" fill="currentColor" stroke="none"/><path d="M13.4 14.2h4.2"/></svg>';
        }
    }

    private static function payment_strip($display, $language) {
        if ((string)($display['show_payment_methods'] ?? '0') !== '1') return '';
        $items = isset($display['payment_methods']) && is_array($display['payment_methods']) ? $display['payment_methods'] : array();
        $rendered = array();
        foreach ($items as $item) {
            if (!is_array($item) || (isset($item['enabled']) && (string)$item['enabled'] === '0')) continue;
            $label = self::translation($item['label'] ?? array(), $language, '');
            if ($label === '') continue;
            $rendered[] = array('icon'=>sanitize_key((string)($item['icon'] ?? 'other')),'label'=>$label);
        }
        if (!$rendered) return '';
        $title = self::translation($display['payment_title'] ?? array(), $language, self::default_payment_title($language));
        if ($title === '') $title = self::default_payment_title($language);
        ob_start(); ?>
        <div class="parcs-ht-payment-strip parcs-ht-group-payment-strip" aria-label="<?php echo esc_attr($title); ?>">
            <strong class="parcs-ht-payment-title"><?php echo esc_html($title); ?></strong>
            <div class="parcs-ht-payment-icons">
                <?php foreach ($rendered as $item) : ?>
                    <span class="parcs-ht-payment-item">
                        <span class="parcs-ht-payment-icon" aria-hidden="true"><?php echo wp_kses(self::payment_icon_svg($item['icon']), Parcs_HT_Defaults::svg_allowed_tags()); ?></span>
                        <span><?php echo esc_html($item['label']); ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    private static function information_blocks($display, $language) {
        if ((string)($display['show_info_blocks'] ?? '0') !== '1') return '';
        $blocks = isset($display['info_blocks']) && is_array($display['info_blocks']) ? $display['info_blocks'] : array();
        $html = '';
        foreach ($blocks as $block) {
            if (!is_array($block) || (isset($block['enabled']) && (string)$block['enabled'] === '0')) continue;
            $title = self::translation($block['title'] ?? array(), $language, '');
            $text = self::translation($block['text'] ?? array(), $language, '');
            if ($title === '' && $text === '') continue;
            $html .= '<article class="parcs-ht-quote-info">';
            if ($title !== '') $html .= '<div class="parcs-ht-quote-info-title" role="heading" aria-level="3">' . esc_html($title) . '</div>';
            if ($text !== '') $html .= '<p>' . nl2br(esc_html($text)) . '</p>';
            $html .= '</article>';
        }
        return $html === '' ? '' : '<div class="parcs-ht-quote-info-grid parcs-ht-group-info-grid">' . $html . '</div>';
    }
    private static function style_variables($appearance) {
        $appearance = is_array($appearance) ? $appearance : array();
        $map = array(
            'tariff_title_color'=>'--htp-tariff-title',
            'payment_title_color'=>'--htp-payment-title','payment_border_color'=>'--htp-payment-border','payment_item_bg_color'=>'--htp-payment-item-bg','payment_item_text_color'=>'--htp-payment-item-text','payment_icon_color'=>'--htp-payment-icon',
            'panel_text_color'=>'--htp-panel-text','panel_border_color'=>'--htp-panel-border','price_color'=>'--htp-price',
            'groups_note_text_color'=>'--htp-groups-note-text','groups_note_border_color'=>'--htp-groups-note-border',
            'button_bg_color'=>'--htp-button-bg','button_text_color'=>'--htp-button-text',
        );
        $style = '';
        foreach ($map as $key=>$variable) {
            if (empty($appearance[$key])) continue;
            $color = sanitize_hex_color((string)$appearance[$key]);
            if ($color) $style .= $variable . ':' . $color . ';';
        }
        if (!empty($appearance['tariff_title_bg_transparent']) && (string)$appearance['tariff_title_bg_transparent'] === '1') {
            $style .= '--htp-tariff-title-bg:transparent;';
        } elseif (!empty($appearance['tariff_title_bg_color']) && ($color = sanitize_hex_color((string)$appearance['tariff_title_bg_color']))) {
            $style .= '--htp-tariff-title-bg:' . $color . ';';
        }
        if (!empty($appearance['payment_title_bg_transparent']) && (string)$appearance['payment_title_bg_transparent'] === '1') {
            $style .= '--htp-payment-title-bg:transparent;';
        } elseif (!empty($appearance['payment_title_bg_color']) && ($color = sanitize_hex_color((string)$appearance['payment_title_bg_color']))) {
            $style .= '--htp-payment-title-bg:' . $color . ';';
        }
        if (!empty($appearance['panel_bg_transparent']) && (string)$appearance['panel_bg_transparent'] === '1') {
            $style .= '--htp-panel-bg:transparent;';
        } elseif (!empty($appearance['panel_bg_color']) && ($color = sanitize_hex_color((string)$appearance['panel_bg_color']))) {
            $style .= '--htp-panel-bg:' . $color . ';';
        }
        $style .= '--htp-payment-border-width:' . ((!empty($appearance['payment_border_enabled']) && (string)$appearance['payment_border_enabled'] === '1') ? '1px' : '0px') . ';';
        $style .= '--htp-panel-border-width:' . ((!empty($appearance['panel_border_enabled']) && (string)$appearance['panel_border_enabled'] === '1') ? '1px' : '0px') . ';';
        return $style;
    }

    private static function ensure_style() {
        if (!wp_style_is('parcs-ht-frontend', 'registered')) {
            wp_register_style('parcs-ht-frontend', PARCS_HT_URL . 'assets/frontend.css', array(), PARCS_HT_VERSION);
        }
        wp_enqueue_style('parcs-ht-frontend');
        if (!did_action('wp_head') || wp_style_is('parcs-ht-frontend', 'done')) return '';
        ob_start();
        wp_print_styles('parcs-ht-frontend');
        return ob_get_clean();
    }

    public static function render($language, $atts = array()) {
        unset($atts);
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $settings = self::selected_settings();
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? $tariffs['groups'] : array();
        $columns = self::columns($tariffs);

        $visible_rows = array();
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['enabled']) || (string)$row['enabled'] !== '1') continue;
            if (!self::row_is_visible($row)) continue;
            $visible_rows[] = $row;
        }

        $fallbacks = array(
            'fr'=>'Les tarifs groupes ne sont pas disponibles pour le moment.',
            'en'=>'Group rates are not available at the moment.',
            'de'=>'Die Gruppentarife sind derzeit nicht verfügbar.',
        );
        if (!$columns || !$visible_rows) {
            return '<p class="parcs-ht-group-tariffs-empty">' . esc_html($fallbacks[$language]) . '</p>';
        }

        self::$instance++;
        $id = 'parcs-ht-group-tariffs-' . self::$instance;
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $late_style = self::ensure_style();
        $year = trim((string)($general['year'] ?? ''));
        $titles = array('fr'=>'Tarifs groupes','en'=>'Group rates','de'=>'Gruppentarife');
        $title = $titles[$language] . ($year !== '' ? ' ' . $year : '');
        $show_head = count($columns) > 1;
        $display = self::display_settings($year);
        $style = self::style_variables($display['appearance'] ?? array());
        $show_heading = (string)($display['show_heading'] ?? '1') === '1';
        $custom_title = self::translation($display['title'] ?? array(), $language, '');
        if ($custom_title !== '') $title = $custom_title;
        $intro = self::translation($display['intro'] ?? array(), $language, '');
        $payment_html = self::payment_strip($display, $language);
        $info_html = self::information_blocks($display, $language);

        $fallback_url = isset($general['groups_url'][$language]) ? (string)$general['groups_url'][$language] : '';
        $configured_url = isset($display['button_url'][$language]) ? (string)$display['button_url'][$language] : '';
        $groups_url = $configured_url !== '' ? $configured_url : $fallback_url;
        $groups_booking_note = self::translation($general['groups_booking_note'] ?? array(), $language, '');
        $groups_button_label = self::translation($display['button_label'] ?? array(), $language, '');
        if ($groups_button_label === '') $groups_button_label = self::translation($general['groups_button_label'] ?? array(), $language, '');
        if ($groups_button_label === '') {
            $groups_button_label = $language === 'en' ? 'Request a quote' : ($language === 'de' ? 'Angebot anfordern' : 'Faire une demande de devis');
        }
        $show_quote_button = (string)($display['show_quote_button'] ?? '1') === '1';

        ob_start();
        echo $late_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Feuille de style WordPress déjà échappée.
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-tariffs parcs-ht-group-tariffs-only" data-htp-lang="<?php echo esc_attr($language); ?>" style="<?php echo esc_attr($style); ?>">
            <?php if ($show_heading || $intro !== '') : ?>
                <header class="parcs-ht-heading parcs-ht-tariff-heading">
                    <?php if ($show_heading) : ?><div class="parcs-ht-title" role="heading" aria-level="2"><?php echo esc_html($title); ?></div><?php endif; ?>
                    <?php if ($intro !== '') : ?><p class="parcs-ht-group-tariffs-intro"><?php echo nl2br(esc_html($intro)); ?></p><?php endif; ?>
                </header>
            <?php endif; ?>
            <?php echo $payment_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne entièrement échappé. ?>
            <div class="parcs-ht-tariff-panel">
                <div class="parcs-ht-price-list" style="--htp-tariff-column-count:<?php echo (int)count($columns); ?>">
                    <?php if ($show_head) : ?>
                        <div class="parcs-ht-price-head" aria-hidden="true"><span></span><?php foreach ($columns as $column) : ?><span><?php echo esc_html(self::translation($column['label'], $language, '')); ?></span><?php endforeach; ?></div>
                    <?php endif; ?>
                    <?php foreach ($visible_rows as $row) :
                        $is_special = isset($row['row_type']) && $row['row_type'] === 'special';
                        $row_style = self::row_style($row, $display);
                        $label_text = self::translation($row['label'] ?? array(), $language, '');
                        $subtitle = self::translation($row['subtitle'] ?? ($row['detail'] ?? array()), $language, '');
                        $note = self::translation($row['note'] ?? array(), $language, '');
                        $badge = $is_special ? self::translation($row['special_badge'] ?? array(), $language, '') : '';
                        $meta = $is_special ? self::special_offer_meta($row, $language) : array();
                        $purchase_url = $is_special ? self::translation($row['purchase_url'] ?? array(), $language, '') : '';
                        $classes = 'parcs-ht-price-row' . ($is_special ? ' is-special-offer' : '');
                    ?>
                        <div class="<?php echo esc_attr($classes); ?>"<?php echo $row_style !== '' ? ' style="' . esc_attr($row_style) . '"' : ''; ?>>
                            <div class="parcs-ht-price-label">
                                <div class="parcs-ht-price-label-line">
                                    <?php if ($is_special && (string)($row['show_special_dot'] ?? '1') === '1') : ?><span class="parcs-ht-special-dot" aria-hidden="true"></span><?php endif; ?>
                                    <strong><?php echo esc_html($label_text); ?></strong>
                                    <?php if ($badge !== '') : ?><span class="parcs-ht-special-badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                                </div>
                                <?php if ($subtitle !== '') : ?><small class="parcs-ht-price-subtitle"><?php echo esc_html($subtitle); ?></small><?php endif; ?>
                                <?php if ($note !== '') : ?><small class="parcs-ht-price-note"><?php echo esc_html($note); ?></small><?php endif; ?>
                                <?php foreach ($meta as $line) : ?><small class="parcs-ht-special-meta"><?php echo esc_html($line); ?></small><?php endforeach; ?>
                                <?php if ($purchase_url !== '') : ?><a class="parcs-ht-special-buy" href="<?php echo esc_url($purchase_url); ?>"><?php echo esc_html(self::buy_label($language)); ?></a><?php endif; ?>
                            </div>
                            <div class="parcs-ht-price-values">
                                <?php foreach ($columns as $column) :
                                    $col_id = $column['id'];
                                    $cell = isset($row['cells'][$col_id]) && is_array($row['cells'][$col_id]) ? $row['cells'][$col_id] : array();
                                    $value = isset($cell['value']) ? (string)$cell['value'] : ($col_id === 'price' ? (string)($row['price'] ?? '') : '');
                                    $old_value = isset($cell['old_value']) ? (string)$cell['old_value'] : '';
                                    $col_label = self::translation($column['label'], $language, '');
                                ?>
                                    <div class="parcs-ht-price-cell"<?php echo !$show_head && $col_label !== '' ? ' aria-label="' . esc_attr($col_label) . '"' : ''; ?>>
                                        <?php if ($is_special && $old_value !== '') : ?><del><?php echo esc_html($old_value); ?></del><?php endif; ?>
                                        <b><?php echo esc_html($value); ?></b>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($info_html !== '') : ?>
                    <?php echo $info_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne entièrement échappé. ?>
                <?php elseif ($groups_booking_note !== '') : ?>
                    <p class="parcs-ht-groups-booking-note"><?php echo nl2br(esc_html($groups_booking_note)); ?></p>
                <?php endif; ?>
                <?php if ($show_quote_button && $groups_url !== '') : ?><div class="parcs-ht-panel-actions"><a class="parcs-ht-button" href="<?php echo esc_url($groups_url); ?>"><?php echo esc_html($groups_button_label); ?></a></div><?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
