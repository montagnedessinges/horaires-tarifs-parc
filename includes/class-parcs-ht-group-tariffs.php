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

    private static function row_style($row) {
        $style = '';
        $map = array(
            'label_color'=>'--htp-row-label',
            'subtitle_color'=>'--htp-row-detail',
            'note_color'=>'--htp-row-note',
            'price_color'=>'--htp-row-price',
            'row_border_color'=>'--htp-row-border',
        );
        foreach ($map as $key=>$var) {
            if (!empty($row[$key]) && ($color = sanitize_hex_color($row[$key]))) $style .= $var . ':' . $color . ';';
        }
        if (!empty($row['row_bg_transparent']) && (string)$row['row_bg_transparent'] === '1') {
            $style .= '--htp-row-bg:transparent;';
        } elseif (!empty($row['row_bg_color']) && ($color = sanitize_hex_color($row['row_bg_color']))) {
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

    private static function style_variables($general) {
        $general = is_array($general) ? $general : array();
        $map = array(
            'primary_color'=>'--htp-primary','secondary_color'=>'--htp-secondary','accent_color'=>'--htp-accent','highlight_color'=>'--htp-highlight',
            'tab_bg_color'=>'--htp-tab-bg','tab_text_color'=>'--htp-tab-text','tab_active_bg_color'=>'--htp-tab-active-bg','tab_active_text_color'=>'--htp-tab-active-text',
            'button_bg_color'=>'--htp-button-bg','button_text_color'=>'--htp-button-text','price_color'=>'--htp-price','groups_note_text_color'=>'--htp-groups-note-text','groups_note_border_color'=>'--htp-groups-note-border',
        );
        $style = '';
        foreach ($map as $key=>$variable) {
            if (empty($general[$key])) continue;
            $color = sanitize_hex_color((string)$general[$key]);
            if ($color) $style .= $variable . ':' . $color . ';';
        }
        if (!empty($general['panel_bg_transparent']) && (string)$general['panel_bg_transparent'] === '1') {
            $style .= '--htp-panel-bg:transparent;';
        } elseif (!empty($general['panel_bg_color']) && ($color = sanitize_hex_color((string)$general['panel_bg_color']))) {
            $style .= '--htp-panel-bg:' . $color . ';';
        }
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
        $style = self::style_variables($general);
        $late_style = self::ensure_style();
        $year = trim((string)($general['year'] ?? ''));
        $titles = array('fr'=>'Tarifs groupes','en'=>'Group rates','de'=>'Gruppentarife');
        $title = $titles[$language] . ($year !== '' ? ' ' . $year : '');
        $show_head = count($columns) > 1;

        $groups_url = isset($general['groups_url'][$language]) ? (string)$general['groups_url'][$language] : '';
        $groups_booking_note = self::translation($general['groups_booking_note'] ?? array(), $language, '');
        $groups_button_label = self::translation($general['groups_button_label'] ?? array(), $language, '');
        if ($groups_button_label === '') {
            $groups_button_label = $language === 'en' ? 'Request a quote' : ($language === 'de' ? 'Angebot anfordern' : 'Faire une demande de devis');
        }

        ob_start();
        echo $late_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Feuille de style WordPress déjà échappée.
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-tariffs parcs-ht-group-tariffs-only" data-htp-lang="<?php echo esc_attr($language); ?>" style="<?php echo esc_attr($style); ?>">
            <header class="parcs-ht-heading parcs-ht-tariff-heading">
                <div class="parcs-ht-title" role="heading" aria-level="2"><?php echo esc_html($title); ?></div>
            </header>
            <div class="parcs-ht-tariff-panel">
                <div class="parcs-ht-price-list" style="--htp-tariff-column-count:<?php echo (int)count($columns); ?>">
                    <?php if ($show_head) : ?>
                        <div class="parcs-ht-price-head" aria-hidden="true"><span></span><?php foreach ($columns as $column) : ?><span><?php echo esc_html(self::translation($column['label'], $language, '')); ?></span><?php endforeach; ?></div>
                    <?php endif; ?>
                    <?php foreach ($visible_rows as $row) :
                        $is_special = isset($row['row_type']) && $row['row_type'] === 'special';
                        $row_style = self::row_style($row);
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
                <?php if ($groups_booking_note !== '') : ?><p class="parcs-ht-groups-booking-note"><?php echo nl2br(esc_html($groups_booking_note)); ?></p><?php endif; ?>
                <?php if ($groups_url !== '') : ?><div class="parcs-ht-panel-actions"><a class="parcs-ht-button" href="<?php echo esc_url($groups_url); ?>"><?php echo esc_html($groups_button_label); ?></a></div><?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
