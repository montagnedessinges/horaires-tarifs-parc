<?php

if (!defined('ABSPATH')) { exit; }

/** Shortcode autonome des tarifs groupes, alimenté par Groupes → Tarifs. */
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

    private static function translations($value, $language, $fallback = '') {
        return Parcs_HT_Schedule::translation(is_array($value) ? $value : array(), $language, $fallback);
    }

    private static function style_variables($general) {
        $general = is_array($general) ? $general : array();
        $map = array(
            'primary_color'=>'--htp-primary','secondary_color'=>'--htp-secondary','accent_color'=>'--htp-accent','highlight_color'=>'--htp-highlight',
            'tab_bg_color'=>'--htp-tab-bg','tab_text_color'=>'--htp-tab-text','tab_active_bg_color'=>'--htp-tab-active-bg','tab_active_text_color'=>'--htp-tab-active-text',
            'button_bg_color'=>'--htp-button-bg','button_text_color'=>'--htp-button-text','price_color'=>'--htp-price','groups_note_text_color'=>'--htp-groups-note-text','groups_note_border_color'=>'--htp-groups-note-border',
        );
        $style = '';
        foreach ($map as $key => $variable) {
            if (empty($general[$key])) continue;
            $color = sanitize_hex_color((string)$general[$key]);
            if ($color) $style .= $variable . ':' . $color . ';';
        }
        if (!empty($general['panel_bg_transparent']) && (string)$general['panel_bg_transparent'] === '1') $style .= '--htp-panel-bg:transparent;';
        elseif (!empty($general['panel_bg_color']) && ($color = sanitize_hex_color((string)$general['panel_bg_color']))) $style .= '--htp-panel-bg:' . $color . ';';
        return $style;
    }

    private static function ensure_style() {
        if (!wp_style_is('parcs-ht-frontend', 'registered')) wp_register_style('parcs-ht-frontend', PARCS_HT_URL . 'assets/frontend.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-frontend');
        if (!did_action('wp_head') || wp_style_is('parcs-ht-frontend', 'done')) return '';
        ob_start();
        wp_print_styles('parcs-ht-frontend');
        return ob_get_clean();
    }

    private static function row_visible($row) {
        if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') return false;
        if ((string)($row['row_type'] ?? 'standard') !== 'special') return true;
        $today = wp_date('Y-m-d');
        $from = (string)($row['display_from'] ?? '');
        $to = (string)($row['display_to'] ?? '');
        return !($from !== '' && $today < $from) && !($to !== '' && $today > $to);
    }

    private static function columns($tariffs) {
        $out = array();
        foreach ((array)($tariffs['columns']['groups'] ?? array()) as $column) {
            if (!is_array($column) || (string)($column['visible'] ?? '1') === '0') continue;
            $id = sanitize_key((string)($column['id'] ?? ''));
            if (!class_exists('Parcs_HT_Tariff_Identities') || !Parcs_HT_Tariff_Identities::is_column_id($id)) continue;
            $out[] = $column;
        }
        return $out;
    }

    private static function season_context($all, $year, $language) {
        if (empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) return null;
        $season = $all['seasons'][$year];
        $tariffs = isset($season['tariffs']) && is_array($season['tariffs']) ? $season['tariffs'] : array();
        $columns = self::columns($tariffs);
        if (!$columns) return null;
        $rows = array();
        foreach ((array)($tariffs['groups'] ?? array()) as $row) {
            if (!self::row_visible($row)) continue;
            $cells = array();
            $has_value = false;
            foreach ($columns as $column) {
                $id = (string)$column['id'];
                $cell = isset($row['cells'][$id]) && is_array($row['cells'][$id]) ? $row['cells'][$id] : array();
                $value = trim((string)($cell['value'] ?? ''));
                if ($value !== '') $has_value = true;
                $cells[] = array('id'=>$id,'value'=>$value,'old_value'=>(string)($cell['old_value'] ?? ''),'label'=>self::translations($column['label'] ?? array(), $language, ''));
            }
            if (!$has_value) continue;
            $rows[] = array(
                'label'=>self::translations($row['label'] ?? array(), $language, ''),
                'subtitle'=>self::translations($row['subtitle'] ?? ($row['detail'] ?? array()), $language, ''),
                'note'=>self::translations($row['note'] ?? array(), $language, ''),
                'special'=>(string)($row['row_type'] ?? 'standard') === 'special',
                'badge'=>self::translations($row['special_badge'] ?? array(), $language, ''),
                'cells'=>$cells,
            );
        }
        return $rows ? array('columns'=>$columns,'rows'=>$rows) : null;
    }

    public static function render($language, $atts = array()) {
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        $all = is_array($all) ? $all : array();
        $years = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::published_years() : array();
        $contexts = array();
        foreach ($years as $year) {
            $ctx = self::season_context($all, $year, $language);
            if ($ctx) $contexts[$year] = $ctx;
        }
        $years = array_keys($contexts);
        $fallbacks = array(
            'fr'=>'Les tarifs groupes ne sont pas disponibles pour le moment.',
            'en'=>'Group rates are not available at the moment.',
            'de'=>'Die Gruppentarife sind derzeit nicht verfügbar.',
        );
        if (!$years) return '<p class="parcs-ht-group-tariffs-empty">' . esc_html($fallbacks[$language]) . '</p>';

        $default_year = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::public_year() : (string)end($years);
        if (!isset($contexts[$default_year])) $default_year = (string)$years[0];
        self::$instance++;
        $id = 'parcs-ht-group-tariffs-' . self::$instance;
        $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
        $style = self::style_variables($general);
        $late_style = self::ensure_style();
        $show_heading_attr = !isset($atts['titre']) || (string)$atts['titre'] !== '0';

        ob_start();
        echo $late_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Feuille de style WordPress déjà échappée.
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-group-tariffs-only parcs-ht-tariffs" data-htp-group-tariffs data-htp-lang="<?php echo esc_attr($language); ?>" style="<?php echo esc_attr($style); ?>">
            <?php if (count($years) > 1) : ?>
                <div class="parcs-ht-tariff-tabs" role="tablist" aria-label="<?php echo esc_attr($language === 'fr' ? 'Année des tarifs groupes' : ($language === 'de' ? 'Jahr der Gruppentarife' : 'Group rate year')); ?>">
                    <?php foreach ($years as $year) : ?><button type="button" role="tab" aria-selected="<?php echo $year === $default_year ? 'true' : 'false'; ?>" data-htp-group-year-tab="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></button><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php foreach ($contexts as $year => $context) :
                $settings = Parcs_HT_Group_Tariff_Settings::settings($year);
                $title = self::translations($settings['title'] ?? array(), $language, '');
                if ($title === '') $title = Parcs_HT_Group_Tariff_Settings::default_title($language, $year);
                $intro = self::translations($settings['intro'] ?? array(), $language, '');
                $show_heading = $show_heading_attr && (string)($settings['show_heading'] ?? '1') === '1';
                $future_year = (string)($settings['future_year'] ?? ((int)$year + 1));
                $future_notice = self::translations($settings['future_notice'] ?? array(), $language, '');
                if ($future_notice === '') $future_notice = Parcs_HT_Group_Tariff_Settings::default_future_notice($language, $future_year);
                $show_future = (string)($settings['show_future_notice'] ?? '0') === '1' && $future_year !== '' && !Parcs_HT_Group_Tariff_Settings::is_published($future_year);
                $booking_note = self::translations($general['groups_booking_note'] ?? array(), $language, '');
                $button_label = self::translations($settings['button_label'] ?? array(), $language, '');
                $button_url = self::translations($settings['button_url'] ?? array(), $language, '');
                if ($button_url === '') $button_url = self::translations($general['groups_url'] ?? array(), $language, '');
                $show_button = (string)($settings['show_quote_button'] ?? '1') === '1' && $button_url !== '';
                $columns = $context['columns'];
                $show_head = count($columns) > 1;
            ?>
                <div class="parcs-ht-tariff-panel" data-htp-group-year-panel="<?php echo esc_attr($year); ?>" <?php if ($year !== $default_year) echo 'hidden'; ?>>
                    <?php if ($show_heading) : ?><header class="parcs-ht-heading parcs-ht-tariff-heading"><div class="parcs-ht-title" role="heading" aria-level="2"><?php echo esc_html($title); ?></div></header><?php endif; ?>
                    <?php if ($intro !== '') : ?><p class="parcs-ht-groups-booking-note"><?php echo nl2br(esc_html($intro)); ?></p><?php endif; ?>
                    <div class="parcs-ht-price-list" style="--htp-tariff-column-count:<?php echo (int)count($columns); ?>">
                        <?php if ($show_head) : ?><div class="parcs-ht-price-head" aria-hidden="true"><span></span><?php foreach ($columns as $column) : ?><span><?php echo esc_html(self::translations($column['label'] ?? array(), $language, '')); ?></span><?php endforeach; ?></div><?php endif; ?>
                        <?php foreach ($context['rows'] as $row) : ?>
                            <div class="parcs-ht-price-row<?php echo $row['special'] ? ' is-special' : ''; ?>">
                                <div class="parcs-ht-price-label"><strong><?php echo esc_html($row['label']); ?></strong><?php if ($row['badge'] !== '') : ?><span class="parcs-ht-special-badge"><?php echo esc_html($row['badge']); ?></span><?php endif; ?><?php if ($row['subtitle'] !== '') : ?><span class="parcs-ht-price-detail"><?php echo esc_html($row['subtitle']); ?></span><?php endif; ?><?php if ($row['note'] !== '') : ?><span class="parcs-ht-price-detail"><?php echo esc_html($row['note']); ?></span><?php endif; ?></div>
                                <?php foreach ($row['cells'] as $cell) : ?><div class="parcs-ht-price-value"><?php if ($row['special'] && $cell['old_value'] !== '') : ?><span class="parcs-ht-old-price"><?php echo esc_html($cell['old_value']); ?></span><?php endif; ?><?php echo esc_html($cell['value']); ?></div><?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($booking_note !== '') : ?><p class="parcs-ht-groups-booking-note"><?php echo nl2br(esc_html($booking_note)); ?></p><?php endif; ?>
                    <?php if ($show_future) : ?><p class="parcs-ht-groups-booking-note parcs-ht-group-future-notice"><strong><?php echo esc_html($future_notice); ?></strong></p><?php endif; ?>
                    <?php if ($show_button) : ?><div class="parcs-ht-panel-actions"><a class="parcs-ht-button" href="<?php echo esc_url($button_url); ?>"><?php echo esc_html($button_label !== '' ? $button_label : ($language === 'fr' ? 'Faire une demande de devis' : ($language === 'de' ? 'Angebot anfordern' : 'Request a quote'))); ?></a></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
        <?php if (count($years) > 1) : ?>
        <script>(function(){var root=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!root)return;root.querySelectorAll('[data-htp-group-year-tab]').forEach(function(button){button.addEventListener('click',function(){var year=button.getAttribute('data-htp-group-year-tab');root.querySelectorAll('[data-htp-group-year-tab]').forEach(function(item){item.setAttribute('aria-selected',item===button?'true':'false');});root.querySelectorAll('[data-htp-group-year-panel]').forEach(function(panel){panel.hidden=panel.getAttribute('data-htp-group-year-panel')!==year;});});});}());</script>
        <?php endif;
        return ob_get_clean();
    }
}
