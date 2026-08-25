<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Shortcodes {
    private static $instance = 0;

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue_assets'), 20);
        add_action('admin_post_parcs_ht_tariffs_print', array(__CLASS__, 'tariffs_print_endpoint'));
        add_action('admin_post_nopriv_parcs_ht_tariffs_print', array(__CLASS__, 'tariffs_print_endpoint'));
        add_action('admin_post_parcs_ht_tariffs_pdf', array(__CLASS__, 'tariffs_pdf_endpoint'));
        add_action('admin_post_nopriv_parcs_ht_tariffs_pdf', array(__CLASS__, 'tariffs_pdf_endpoint'));
        add_action('admin_post_parcs_ht_schedule_pdf', array(__CLASS__, 'schedule_pdf_endpoint'));
        add_action('admin_post_nopriv_parcs_ht_schedule_pdf', array(__CLASS__, 'schedule_pdf_endpoint'));

        self::register('parc_horaires_tarifs', 'page');
        self::register('parc_horaires_aujourdhui', 'today');
        self::register('parc_calendrier', 'calendar');
        self::register('parc_tableau_tarifs', 'tariffs');
        self::register('parc_fermeture_exceptionnelle', 'alert');
        self::register('parc_horaire', 'header_hour');
        self::register('parc_statut', 'header_status');
        self::register('parc_horaire_accueil', 'home_opening');
        self::register('parc_devis', 'quote_page');
        self::register('parc_devis_groupe', 'quote_page');

        foreach (array('fr', 'en', 'de') as $language) {
            self::register('parc_horaires_tarifs_' . $language, 'page', $language);
            self::register('parc_horaires_aujourdhui_' . $language, 'today', $language);
            self::register('parc_calendrier_' . $language, 'calendar', $language);
            self::register('parc_tableau_tarifs_' . $language, 'tariffs', $language);
            self::register('parc_fermeture_exceptionnelle_' . $language, 'alert', $language);
            self::register('parc_horaire_' . $language, 'header_hour', $language);
            self::register('parc_statut_' . $language, 'header_status', $language);
            self::register('parc_horaire_accueil_' . $language, 'home_opening', $language);
            self::register('parc_devis_' . $language, 'quote_page', $language);
            self::register('parc_devis_groupe_' . $language, 'quote_page', $language);
        }
    }

    private static function register($tag, $module, $language = '') {
        add_shortcode($tag, static function ($atts = array(), $content = null, $shortcode_tag = '') use ($module, $language) {
            $atts = is_array($atts) ? $atts : array();
            $lang = $language ?: Parcs_HT_Schedule::language();
            return Parcs_HT_Shortcodes::render($module, $lang, $atts);
        });
    }

    public static function maybe_enqueue_assets() {
        wp_register_style('parcs-ht-frontend', PARCS_HT_URL . 'assets/frontend.css', array(), PARCS_HT_VERSION);
        wp_register_script('parcs-ht-frontend', PARCS_HT_URL . 'assets/frontend.js', array(), PARCS_HT_VERSION, true);

        if (self::page_has_shortcode()) {
            self::enqueue_assets();
        }
    }

    private static function page_has_shortcode() {
        if (!is_singular()) return false;
        global $post;
        if (!$post || empty($post->post_content)) return false;
        $tags = array(
            'parc_horaires_tarifs','parc_horaires_aujourdhui','parc_calendrier','parc_tableau_tarifs','parc_fermeture_exceptionnelle',
            'parc_horaires_tarifs_fr','parc_horaires_tarifs_en','parc_horaires_tarifs_de',
            'parc_horaires_aujourdhui_fr','parc_horaires_aujourdhui_en','parc_horaires_aujourdhui_de',
            'parc_calendrier_fr','parc_calendrier_en','parc_calendrier_de',
            'parc_tableau_tarifs_fr','parc_tableau_tarifs_en','parc_tableau_tarifs_de',
            'parc_fermeture_exceptionnelle_fr','parc_fermeture_exceptionnelle_en','parc_fermeture_exceptionnelle_de',
            'parc_horaire','parc_horaire_fr','parc_horaire_en','parc_horaire_de','parc_statut','parc_statut_fr','parc_statut_en','parc_statut_de',
            'parc_horaire_accueil','parc_horaire_accueil_fr','parc_horaire_accueil_en','parc_horaire_accueil_de',
            'parc_devis','parc_devis_fr','parc_devis_en','parc_devis_de','parc_devis_groupe','parc_devis_groupe_fr','parc_devis_groupe_en','parc_devis_groupe_de',
        );
        foreach ($tags as $tag) if (has_shortcode($post->post_content, $tag)) return true;
        return false;
    }

    private static function enqueue_assets() {
        static $payload_added = false;
        if (!wp_style_is('parcs-ht-frontend', 'registered')) {
            wp_register_style('parcs-ht-frontend', PARCS_HT_URL . 'assets/frontend.css', array(), PARCS_HT_VERSION);
        }
        if (!wp_script_is('parcs-ht-frontend', 'registered')) {
            wp_register_script('parcs-ht-frontend', PARCS_HT_URL . 'assets/frontend.js', array(), PARCS_HT_VERSION, true);
        }
        wp_enqueue_style('parcs-ht-frontend');
        wp_enqueue_script('parcs-ht-frontend');
        if (!$payload_added) {
            $settings = Parcs_HT_Defaults::settings();
            $payload = array('settings'=>Parcs_HT_Schedule::public_settings($settings),'dictionary'=>Parcs_HT_Schedule::dictionaries());
            wp_add_inline_script('parcs-ht-frontend', 'window.ParcsHTPData=' . wp_json_encode($payload) . ';', 'before');
            $payload_added = true;
        }
    }

    private static function late_style_markup() {
        static $printed = false;
        if ($printed || !did_action('wp_head') || wp_style_is('parcs-ht-frontend', 'done')) return '';
        $printed = true;
        return '<link rel="stylesheet" id="parcs-ht-frontend-late-css" href="' . esc_url(PARCS_HT_URL . 'assets/frontend.css?ver=' . rawurlencode(PARCS_HT_VERSION)) . '" media="all">';
    }

    public static function render($module, $language, $atts = array()) {
        self::enqueue_assets();
        $late_style = self::late_style_markup();
        $settings = Parcs_HT_Defaults::settings();
        $dictionary = Parcs_HT_Schedule::dictionaries();
        $language = isset($dictionary[$language]) ? $language : 'fr';
        self::$instance++;
        $id = 'parcs-ht-' . self::$instance;
        $style = self::style_variables($settings['general']);

        if ($module === 'today') return $late_style . self::today($id, $language, $style);
        if ($module === 'calendar') return $late_style . self::calendar($id, $language, $style);
        if ($module === 'tariffs') return $late_style . self::tariffs($id, $language, $style, $settings);
        if ($module === 'alert') return $late_style . self::alert($id, $language, $style, $atts);
        if ($module === 'header_hour') return $late_style . self::header_hour($id, $language, $style);
        if ($module === 'header_status') return $late_style . self::header_status($id, $language, $style);
        if ($module === 'home_opening') return $late_style . self::home_opening($id, $language);
        if ($module === 'quote_page') return $late_style . self::quote_page($id, $language, $style, $settings);

        return $late_style . '<div id="' . esc_attr($id) . '" class="parcs-ht-page" data-htp-lang="' . esc_attr($language) . '" style="' . esc_attr($style) . '">' .
            self::today($id . '-today', $language, '') .
            self::calendar($id . '-calendar', $language, '') .
            self::tariffs($id . '-tariffs', $language, '', $settings) .
            '</div>';
    }

    private static function today($id, $language, $style) {
        return '<section id="' . esc_attr($id) . '" class="parcs-ht-today" data-htp-component="today" data-htp-lang="' . esc_attr($language) . '" style="' . esc_attr($style) . '">' .
            '<header class="parcs-ht-heading parcs-ht-today-heading"><p class="parcs-ht-kicker" data-htp-today-kicker></p><h2 data-htp-today-status aria-live="polite"></h2></header>' .
            '<p class="parcs-ht-today-detail" data-htp-today-detail></p>' .
            '</section>';
    }

    private static function calendar($id, $language, $style) {
        return '<section id="' . esc_attr($id) . '" class="parcs-ht-calendar" data-htp-component="calendar" data-htp-lang="' . esc_attr($language) . '" style="' . esc_attr($style) . '">' .
            '<header class="parcs-ht-heading"><p class="parcs-ht-kicker" data-htp-calendar-kicker></p><h2 data-htp-calendar-title></h2></header>' .
            '<div class="parcs-ht-year-list" data-htp-year-list hidden></div>' .
            '<div class="parcs-ht-month-nav"><button type="button" class="parcs-ht-month-arrow" data-htp-prev aria-label="Mois précédent">‹</button><div class="parcs-ht-month-list" data-htp-month-list role="tablist"></div><button type="button" class="parcs-ht-month-arrow" data-htp-next aria-label="Mois suivant">›</button></div>' .
            '<div class="parcs-ht-day-detail parcs-ht-day-detail-top" data-htp-day-detail aria-live="polite"></div>' .
            '<div class="parcs-ht-month-summary" data-htp-month-summary></div>' .
            '<div class="parcs-ht-calendar-grid" data-htp-calendar-grid></div>' .
            '<div class="parcs-ht-legend"><span><i class="parcs-ht-symbol is-hours">!</i><span data-htp-legend-hours></span></span><span><i class="parcs-ht-symbol is-closed">×</i><span data-htp-legend-closed></span></span><span data-htp-event-legend-wrap hidden><i class="parcs-ht-event-legend" aria-hidden="true">★</i><span data-htp-legend-event></span></span><span class="parcs-ht-period-legends" data-htp-period-legends></span></div>' .
            self::schedule_export_button($language) .
            '</section>';
    }

    private static function tariffs($id, $language, $style, $settings) {
        $dictionaries = Parcs_HT_Schedule::dictionaries();
        $d = $dictionaries[$language];
        $tariffs = $settings['tariffs'];
        $labels = array('individual'=>$d['individual'],'reduced'=>$d['reduced'],'groups'=>$d['groups']);
        $order = isset($tariffs['group_order']) && is_array($tariffs['group_order']) ? $tariffs['group_order'] : array_keys($labels);
        $groups = array();
        foreach ($order as $key) if (isset($labels[$key]) && !isset($groups[$key])) $groups[$key] = $labels[$key];
        foreach ($labels as $key=>$label) if (!isset($groups[$key])) $groups[$key] = $label;
        ob_start();
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-tariffs" data-htp-component="tariffs" data-htp-lang="<?php echo esc_attr($language); ?>" style="<?php echo esc_attr($style); ?>">
            <header class="parcs-ht-heading parcs-ht-tariff-heading"><p class="parcs-ht-kicker"><?php echo esc_html($d['prices']); ?></p><div class="parcs-ht-title" role="heading" aria-level="2"><?php echo esc_html($d['prices'] . (!empty($settings['general']['year']) ? ' ' . $settings['general']['year'] : '')); ?></div></header>
            <?php echo self::payment_strip($tariffs, $language, $d); ?>
            <div class="parcs-ht-tariff-tabs" role="tablist" aria-label="<?php echo esc_attr($d['prices']); ?>">
                <?php $first = true; foreach ($groups as $key => $label) : ?>
                    <button type="button" id="<?php echo esc_attr($id . '-tab-' . $key); ?>" role="tab" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($id . '-panel-' . $key); ?>" tabindex="<?php echo $first ? '0' : '-1'; ?>" data-htp-tariff-tab="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
                <?php $first = false; endforeach; ?>
            </div>
            <?php $first = true; foreach ($groups as $key => $label) :
                $columns = self::tariff_columns($tariffs, $key);
                $show_head = count($columns) > 1;
            ?>
                <div id="<?php echo esc_attr($id . '-panel-' . $key); ?>" class="parcs-ht-tariff-panel" role="tabpanel" aria-labelledby="<?php echo esc_attr($id . '-tab-' . $key); ?>" <?php if (!$first) echo 'hidden'; ?>>
                    <h3 class="screen-reader-text"><?php echo esc_html($label); ?></h3>
                    <div class="parcs-ht-price-list" style="--htp-tariff-column-count:<?php echo (int)count($columns); ?>">
                        <?php if ($show_head) : ?>
                            <div class="parcs-ht-price-head" aria-hidden="true"><span></span><?php foreach ($columns as $column) : ?><span><?php echo esc_html(Parcs_HT_Schedule::translation($column['label'], $language, '')); ?></span><?php endforeach; ?></div>
                        <?php endif; ?>
                        <?php foreach ($tariffs[$key] as $row_index => $row) :
                            if (!isset($row['enabled']) || $row['enabled'] !== '1') continue;
                            if (!self::tariff_row_is_visible($row)) continue;
                            $is_special = isset($row['row_type']) && $row['row_type'] === 'special';
                            $row_style = self::tariff_row_style($row);
                            $label_text = Parcs_HT_Schedule::translation($row['label'], $language);
                            $subtitle = Parcs_HT_Schedule::translation(isset($row['subtitle']) ? $row['subtitle'] : (isset($row['detail']) ? $row['detail'] : array()), $language);
                            $note = Parcs_HT_Schedule::translation(isset($row['note']) ? $row['note'] : array(), $language);
                            $badge = $is_special ? Parcs_HT_Schedule::translation(isset($row['special_badge']) ? $row['special_badge'] : array(), $language, '') : '';
                            $meta = $is_special ? self::special_offer_meta($row, $language) : array();
                            $purchase_url = $is_special ? Parcs_HT_Schedule::translation(isset($row['purchase_url']) ? $row['purchase_url'] : array(), $language, '') : '';
                            $classes = 'parcs-ht-price-row' . ($is_special ? ' is-special-offer' : '');
                        ?>
                            <div class="<?php echo esc_attr($classes); ?>"<?php echo $row_style !== '' ? ' style="' . esc_attr($row_style) . '"' : ''; ?>>
                                <div class="parcs-ht-price-label">
                                    <div class="parcs-ht-price-label-line">
                                        <?php if ($is_special && (string)($row['show_special_dot'] ?? '1') === '1') : ?><span class="parcs-ht-special-dot" aria-hidden="true"></span><?php endif; ?>
                                        <strong><?php echo esc_html($label_text); ?></strong>
                                        <?php if ($badge !== '') : ?><span class="parcs-ht-special-badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($subtitle) : ?><small class="parcs-ht-price-subtitle"><?php echo esc_html($subtitle); ?></small><?php endif; ?>
                                    <?php if ($note) : ?><small class="parcs-ht-price-note"><?php echo esc_html($note); ?></small><?php endif; ?>
                                    <?php foreach ($meta as $line) : ?><small class="parcs-ht-special-meta"><?php echo esc_html($line); ?></small><?php endforeach; ?>
                                    <?php if ($purchase_url) : ?><a class="parcs-ht-special-buy" href="<?php echo esc_url($purchase_url); ?>"><?php echo esc_html(self::special_buy_label($language)); ?></a><?php endif; ?>
                                </div>
                                <div class="parcs-ht-price-values">
                                    <?php foreach ($columns as $column) :
                                        $col_id = $column['id'];
                                        $cell = isset($row['cells'][$col_id]) && is_array($row['cells'][$col_id]) ? $row['cells'][$col_id] : array();
                                        $value = isset($cell['value']) ? (string)$cell['value'] : ($col_id === 'price' ? (string)($row['price'] ?? '') : '');
                                        $old_value = isset($cell['old_value']) ? (string)$cell['old_value'] : '';
                                        $col_label = Parcs_HT_Schedule::translation($column['label'], $language, '');
                                    ?>
                                        <div class="parcs-ht-price-cell"<?php echo !$show_head && $col_label ? ' aria-label="' . esc_attr($col_label) . '"' : ''; ?>>
                                            <?php if ($is_special && $old_value !== '') : ?><del><?php echo esc_html($old_value); ?></del><?php endif; ?>
                                            <b><?php echo esc_html($value); ?></b>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($key === 'reduced') : $note = Parcs_HT_Schedule::translation($tariffs['notes'], $language); if ($note) : ?><p class="parcs-ht-tariff-note"><?php echo esc_html($note); ?></p><?php endif; endif; ?>
                    <?php if ($key === 'groups') :
                        $groups_url = isset($settings['general']['groups_url'][$language]) ? $settings['general']['groups_url'][$language] : '';
                        $groups_email = isset($settings['general']['groups_email']) ? sanitize_email($settings['general']['groups_email']) : '';
                        $groups_booking_note = Parcs_HT_Schedule::translation(isset($settings['general']['groups_booking_note']) ? $settings['general']['groups_booking_note'] : array(), $language);
                        $groups_closed_note = Parcs_HT_Schedule::translation(isset($settings['general']['groups_closed_note']) ? $settings['general']['groups_closed_note'] : array(), $language);
                        $groups_button_label = Parcs_HT_Schedule::translation(isset($settings['general']['groups_button_label']) ? $settings['general']['groups_button_label'] : array(), $language);
                        if ($groups_booking_note) : ?><p class="parcs-ht-groups-booking-note"><?php echo nl2br(esc_html($groups_booking_note)); ?></p><?php endif;
                        if ($groups_closed_note) : ?><p class="parcs-ht-groups-closed-note" data-htp-groups-closed-note hidden><?php echo nl2br(esc_html($groups_closed_note)); ?><?php if ($groups_email) : ?> <a href="mailto:<?php echo esc_attr($groups_email); ?>"><?php echo esc_html($groups_email); ?></a><?php endif; ?></p><?php endif;
                        if ($groups_url) : ?>
                        <div class="parcs-ht-panel-actions"><a class="parcs-ht-button" href="<?php echo esc_url($groups_url); ?>"><?php echo esc_html($groups_button_label ?: $d['quote']); ?></a></div>
                    <?php endif; endif; ?>
                </div>
            <?php $first = false; endforeach; ?>
            <div class="parcs-ht-actions">
                <?php $tickets_url = isset($settings['general']['tickets_url'][$language]) ? $settings['general']['tickets_url'][$language] : ''; if ($tickets_url) : ?><a class="parcs-ht-button is-primary" href="<?php echo esc_url($tickets_url); ?>"><?php echo esc_html($d['tickets']); ?></a><?php endif; ?>
            </div>
            <?php echo self::tariff_export_actions($tariffs, $language); ?>
        </section>
        <?php
        return ob_get_clean();
    }

    private static function normalize_cf7_shortcode($value) {
        $value = trim((string) $value);
        if ($value === '') return '';

        // Supporte les shortcodes CF7 standards, avec ou sans slash final,
        // tout en refusant l'exécution d'un autre shortcode.
        $value = html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'));
        $value = preg_replace('/[\r\n\t]+/', ' ', $value);
        $value = trim($value);

        if (!preg_match('/^\[contact-form-7(?:\s+[^\]]*)?\s*\/?\]$/i', $value)) {
            return '';
        }

        return $value;
    }

    private static function render_cf7_form($shortcode) {
        $shortcode = self::normalize_cf7_shortcode($shortcode);
        if ($shortcode === '') {
            return array('html'=>'', 'status'=>'invalid');
        }

        if (!shortcode_exists('contact-form-7')) {
            return array('html'=>'', 'status'=>'cf7_missing');
        }

        // Ne pas tenter de résoudre nous-mêmes l'identifiant CF7 :
        // les versions récentes de Contact Form 7 peuvent utiliser des identifiants hash
        // (ex. 6c681fb). Contact Form 7 est la source de vérité pour cet identifiant.
        $html = function_exists('apply_shortcodes')
            ? apply_shortcodes($shortcode)
            : do_shortcode($shortcode);

        if (!is_string($html)) $html = '';
        $html = trim($html);

        // Si CF7 a laissé le shortcode intact, il n'a pas réellement été exécuté.
        if ($html === '' || strpos($html, '[contact-form-7') !== false) {
            return array('html'=>'', 'status'=>'not_rendered');
        }

        return array('html'=>$html, 'status'=>'ok');
    }

    private static function quote_page($id, $language, $style, $settings) {
        $q = isset($settings['quote_page']) && is_array($settings['quote_page']) ? $settings['quote_page'] : array();
        // Le fait d'insérer [parc_devis_groupe] dans une page suffit à activer le rendu.
        // On ne retourne plus une chaîne vide à cause d'un second interrupteur d'administration.
        $title = Parcs_HT_Schedule::translation($q['title'] ?? array(), $language, '');
        $intro = Parcs_HT_Schedule::translation($q['intro'] ?? array(), $language, '');
        $form_title = Parcs_HT_Schedule::translation($q['form_title'] ?? array(), $language, '');
        $form_shortcode = self::normalize_cf7_shortcode($q['form_shortcode'] ?? '');
        $form_result = self::render_cf7_form($form_shortcode);
        $important_before = self::quote_important_messages($q['important_messages'] ?? array(), $language, 'before');
        $important_after = self::quote_important_messages($q['important_messages'] ?? array(), $language, 'after');
        $quick_links = self::quote_quick_links($q, $language);
        $before_info = self::quote_items($q['info_blocks'] ?? array(), $language, 'before', false);
        $after_info = self::quote_items($q['info_blocks'] ?? array(), $language, 'after', false);
        $before_acc = self::quote_items($q['accordions'] ?? array(), $language, 'before', true);
        $after_acc = self::quote_items($q['accordions'] ?? array(), $language, 'after', true);
        ob_start(); ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-quote" data-htp-component="quote" data-htp-lang="<?php echo esc_attr($language); ?>" style="<?php echo esc_attr($style); ?>">
            <?php if ($title || $intro) : ?><div class="parcs-ht-quote-head"><?php if ($title) : ?><div class="parcs-ht-quote-title" role="heading" aria-level="2"><?php echo esc_html($title); ?></div><?php endif; ?><?php if ($intro) : ?><p><?php echo nl2br(esc_html($intro)); ?></p><?php endif; ?></div><?php endif; ?>
            <?php echo $important_before; echo $quick_links; echo $before_info; echo $before_acc; ?>
            <div class="parcs-ht-quote-form-wrap">
                <?php if ($form_title) : ?><div class="parcs-ht-quote-form-title" role="heading" aria-level="3"><?php echo esc_html($form_title); ?></div><?php endif; ?>
                <?php if ($form_result['status'] === 'ok') : ?>
                    <div class="parcs-ht-quote-form"><?php echo $form_result['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML généré par Contact Form 7. ?></div>
                <?php else :
                    $missing = $language==='de'
                        ? 'Das Formular kann derzeit nicht angezeigt werden.'
                        : ($language==='en' ? 'The form cannot currently be displayed.' : 'Le formulaire ne peut pas être affiché pour le moment.');
                ?>
                    <p class="parcs-ht-quote-missing"><?php echo esc_html($missing); ?></p>
                <?php endif; ?>
            </div>
            <?php echo $important_after; echo $after_info; echo $after_acc; ?>
        </section>
        <?php return ob_get_clean();
    }

    private static function quote_quick_links($q, $language) {
        $rows = isset($q['quick_links']) && is_array($q['quick_links']) ? $q['quick_links'] : array();
        if (!$rows) return '';

        $title = Parcs_HT_Schedule::translation($q['quick_links_title'] ?? array(), $language, '');
        $intro = Parcs_HT_Schedule::translation($q['quick_links_intro'] ?? array(), $language, '');
        $items = array();

        foreach ($rows as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            $label = Parcs_HT_Schedule::translation($row['label'] ?? array(), $language, '');
            $url = Parcs_HT_Schedule::translation($row['url'] ?? array(), $language, '');

            if ($url === '' && $language !== 'fr') {
                $url = Parcs_HT_Schedule::translation($row['url'] ?? array(), 'fr', '');
            }

            if ($label === '' || $url === '') continue;
            $icon = isset($row['icon']) ? trim((string)$row['icon']) : '';
            $items[] = '<a class="parcs-ht-quote-quick-link" href="'.esc_url($url).'">' .
                ($icon!==''?'<span class="parcs-ht-quote-quick-icon" aria-hidden="true">'.esc_html($icon).'</span>':'') .
                '<span>'.esc_html($label).'</span></a>';
        }

        if (!$items) return '';
        return '<nav class="parcs-ht-quote-quick" aria-label="'.esc_attr($title ?: 'Liens utiles').'">' .
            ($title!==''?'<div class="parcs-ht-quote-quick-title" role="heading" aria-level="3">'.esc_html($title).'</div>':'') .
            ($intro!==''?'<p class="parcs-ht-quote-quick-intro">'.nl2br(esc_html($intro)).'</p>':'') .
            '<div class="parcs-ht-quote-quick-links">'.implode('', $items).'</div></nav>';
    }

    private static function quote_important_messages($rows, $language, $position = 'before') {
        if (!is_array($rows)) return '';
        $items = array();
        foreach ($rows as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            $row_position = (($row['position'] ?? 'before') === 'after') ? 'after' : 'before';
            if ($row_position !== $position) continue;
            $title = Parcs_HT_Schedule::translation($row['title'] ?? array(), $language, '');
            $text = Parcs_HT_Schedule::translation($row['text'] ?? array(), $language, '');
            if ($title === '' && $text === '') continue;
            $color = sanitize_hex_color($row['color'] ?? '') ?: '#ef7658';
            $items[] = '<article class="parcs-ht-quote-important" style="--htp-quote-accent:'.esc_attr($color).'">' .
                ($title!==''?'<div class="parcs-ht-quote-important-title" role="heading" aria-level="3">'.esc_html($title).'</div>':'') .
                ($text!==''?'<p>'.nl2br(esc_html($text)).'</p>':'') .
                '</article>';
        }
        return $items ? '<div class="parcs-ht-quote-important-list">'.implode('', $items).'</div>' : '';
    }

    private static function quote_items($rows, $language, $position, $accordion) {
        if (!is_array($rows)) return '';
        $items = array();
        foreach ($rows as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['position'] ?? '') !== $position) continue;
            $title = Parcs_HT_Schedule::translation($row['title'] ?? array(), $language, '');
            $text = Parcs_HT_Schedule::translation($row['text'] ?? array(), $language, '');
            $button_label = Parcs_HT_Schedule::translation($row['button_label'] ?? array(), $language, '');
            $button_url = Parcs_HT_Schedule::translation($row['button_url'] ?? array(), $language, '');
            $button = ((string)($row['show_button'] ?? '0') === '1' && $button_label !== '' && $button_url !== '') ? '<a class="parcs-ht-quote-button" href="'.esc_url($button_url).'">'.esc_html($button_label).'</a>' : '';
            if ($title === '' && $text === '' && $button === '') continue;
            if ($accordion) {
                $items[] = '<details class="parcs-ht-quote-accordion"><summary>'.esc_html($title).'</summary><div>'.($text!==''?nl2br(esc_html($text)):'').$button.'</div></details>';
            } else {
                $items[] = '<article class="parcs-ht-quote-info">'.($title!==''?'<div class="parcs-ht-quote-info-title" role="heading" aria-level="3">'.esc_html($title).'</div>':'').($text!==''?'<p>'.nl2br(esc_html($text)).'</p>':'').$button.'</article>';
            }
        }
        if (!$items) return '';
        return $accordion ? '<div class="parcs-ht-quote-accordions">'.implode('', $items).'</div>' : '<div class="parcs-ht-quote-info-grid">'.implode('', $items).'</div>';
    }

    private static function tariff_export_actions($tariffs, $language) {
        $print = isset($tariffs['print']) && is_array($tariffs['print']) ? $tariffs['print'] : array();
        if ((string)($print['pdf_enabled'] ?? '1') !== '1') return '';

        $nonce = wp_create_nonce('parcs_ht_tariff_export');
        $pdf_url = add_query_arg(
            array(
                'action'   => 'parcs_ht_tariffs_pdf',
                'lang'     => $language,
                '_wpnonce' => $nonce,
            ),
            admin_url('admin-post.php')
        );

        $labels = array(
            'fr' => 'Télécharger les tarifs en PDF',
            'en' => 'Download prices as PDF',
            'de' => 'Preise als PDF herunterladen',
        );
        $label = $labels[$language] ?? $labels['fr'];

        return '<div class="parcs-ht-tariff-export-actions">' .
            '<a class="parcs-ht-tariff-export-button is-pdf" href="' . esc_url($pdf_url) . '">' .
            '<span class="parcs-ht-pdf-mark" aria-hidden="true">PDF</span>' .
            '<span>' . esc_html($label) . '</span>' .
            '</a></div>';
    }


    private static function schedule_export_button($language) {
        $labels = array(
            'fr' => 'Télécharger le planning des horaires',
            'en' => 'Download opening-hours schedule',
            'de' => 'Öffnungszeitenplan herunterladen',
        );
        $url = add_query_arg(
            array(
                'action' => 'parcs_ht_schedule_pdf',
                'lang' => $language,
                '_wpnonce' => wp_create_nonce('parcs_ht_schedule_export'),
            ),
            admin_url('admin-post.php')
        );
        return '<div class="parcs-ht-schedule-export"><a class="parcs-ht-schedule-export-button" href="' .
            esc_url($url) . '"><span class="parcs-ht-pdf-mark" aria-hidden="true">PDF</span>' .
            esc_html($labels[$language] ?? $labels['fr']) . '</a></div>';
    }

    public static function schedule_pdf_endpoint() {
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'parcs_ht_schedule_export')) {
            wp_die('Lien d’export invalide.', '', array('response'=>403));
        }
        $language = self::export_language();
        $settings = Parcs_HT_Defaults::settings();
        $ctx = self::schedule_export_context($settings, $language);
        $slug = sanitize_title($ctx['park'] ?: 'parc');
        $year = $ctx['year'] !== '' ? '-' . $ctx['year'] : '';
        self::serve_cached_pdf('planning', $ctx['language'], $ctx['year'], $slug . '-planning-horaires' . $year . '.pdf', static function() use ($ctx) {
            return Parcs_HT_Shortcodes::build_schedule_pdf($ctx);
        });
    }

    private static function schedule_export_context($settings, $language) {
        unset($settings);
        $all = Parcs_HT_Defaults::all_settings();
        $year = Parcs_HT_Defaults::select_season_year($all);
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year]))
            ? $all['seasons'][$year] : array();

        $park = Parcs_HT_Schedule::translation($all['general']['park_name'] ?? array(), $language, '');
        if ($language === 'fr') {
            $plain = function_exists('remove_accents') ? strtolower(remove_accents($park)) : strtolower($park);
            if (strpos($plain, 'foret des singes') !== false) $park = 'La Forêt des Singes';
            if (strpos($plain, 'montagne des singes') !== false) $park = 'La Montagne des Singes';
        }

        $start = (string)($season['season_start'] ?? '');
        $end = (string)($season['season_end'] ?? '');
        if ($start === '' || $end === '') {
            $dates = array();
            foreach (array('regular_periods','exceptions','special_periods') as $key) {
                foreach (($season[$key] ?? array()) as $row) {
                    if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
                    if (!empty($row['start'])) $dates[] = (string)$row['start'];
                    if (!empty($row['end'])) $dates[] = (string)$row['end'];
                }
            }
            sort($dates);
            if ($start === '' && $dates) $start = reset($dates);
            if ($end === '' && $dates) $end = end($dates);
        }
        if ($start === '' && $year !== '') $start = $year . '-01-01';
        if ($end === '' && $year !== '') $end = $year . '-12-31';

        return array(
            'language'=>$language,
            'park'=>$park,
            'website'=>untrailingslashit(home_url('/')),
            'generated_on'=>wp_date('d/m/Y', null, new DateTimeZone('Europe/Paris')),
            'year'=>(string)$year,
            'start'=>$start,
            'end'=>$end,
            'general'=>isset($all['general']) && is_array($all['general']) ? $all['general'] : array(),
            'regular_periods'=>isset($season['regular_periods']) && is_array($season['regular_periods']) ? $season['regular_periods'] : array(),
            'exceptions'=>isset($season['exceptions']) && is_array($season['exceptions']) ? $season['exceptions'] : array(),
            'special_periods'=>isset($season['special_periods']) && is_array($season['special_periods']) ? $season['special_periods'] : array(),
            'school_holidays'=>isset($season['school_holidays']) && is_array($season['school_holidays']) ? $season['school_holidays'] : array(),
            'public_holidays'=>isset($season['public_holidays']) && is_array($season['public_holidays']) ? $season['public_holidays'] : array(),
            'domain_rules'=>isset($season['domain_rules']) && is_array($season['domain_rules']) ? $season['domain_rules'] : array(),
        );
    }

    /**
     * Resolve the effective schedule exactly like the front-end calendar:
     * enabled exception with highest priority > regular period > closed.
     */
    private static function schedule_resolve_day($ctx, $date) {
        if ($ctx['start'] !== '' && $date < $ctx['start']) return array('in_season'=>false,'open'=>false,'type'=>'outside','color'=>'#f3f3f3','slots'=>array());
        if ($ctx['end'] !== '' && $date > $ctx['end']) return array('in_season'=>false,'open'=>false,'type'=>'outside','color'=>'#f3f3f3','slots'=>array());

        $exceptions = array();
        foreach ($ctx['exceptions'] as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            $from=(string)($row['start'] ?? ''); $to=(string)($row['end'] ?? '');
            if ($from !== '' && $to !== '' && $date >= $from && $date <= $to) $exceptions[]=$row;
        }
        usort($exceptions, static function($a,$b){
            $priority=(int)($b['priority'] ?? 0)-(int)($a['priority'] ?? 0);
            if($priority!==0)return $priority;
            $at=(string)($a['type'] ?? 'hours'); $bt=(string)($b['type'] ?? 'hours');
            if($at===$bt)return 0;
            return $at==='closed' ? -1 : 1;
        });
        if ($exceptions) {
            $ex=$exceptions[0];
            if ((string)($ex['type'] ?? 'hours') === 'closed') {
                return array('in_season'=>true,'open'=>false,'type'=>'closed','exceptional'=>true,'color'=>'#d9d9d9','slots'=>array(),'source'=>$ex);
            }
            if (!empty($ex['open']) && !empty($ex['close'])) {
                $slots=array(array('open'=>(string)$ex['open'],'close'=>(string)$ex['close']));
                if (!empty($ex['open2']) && !empty($ex['close2'])) $slots[]=array('open'=>(string)$ex['open2'],'close'=>(string)$ex['close2']);
                return array('in_season'=>true,'open'=>true,'type'=>'hours','exceptional'=>true,'color'=>(string)($ctx['general']['accent_color'] ?? '#ef7b5b'),'slots'=>$slots,'source'=>$ex);
            }
        }

        $weekday=(string)(new DateTimeImmutable($date, new DateTimeZone('Europe/Paris')))->format('N');
        foreach ($ctx['regular_periods'] as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            $from=(string)($row['start'] ?? ''); $to=(string)($row['end'] ?? '');
            $days=isset($row['weekdays']) && is_array($row['weekdays']) ? array_map('strval',$row['weekdays']) : array();
            if ($from === '' || $to === '' || $date < $from || $date > $to || !in_array($weekday,$days,true)) continue;
            if (empty($row['open']) || empty($row['close'])) break;
            $slots=array(array('open'=>(string)$row['open'],'close'=>(string)$row['close']));
            if (!empty($row['open2']) && !empty($row['close2'])) $slots[]=array('open'=>(string)$row['open2'],'close'=>(string)$row['close2']);
            return array('in_season'=>true,'open'=>true,'type'=>'regular','exceptional'=>false,'color'=>(string)($row['color'] ?? '#9AAA8B'),'slots'=>$slots,'source'=>$row);
        }
        return array('in_season'=>true,'open'=>false,'type'=>'closed','exceptional'=>false,'color'=>'#d9d9d9','slots'=>array());
    }

    private static function schedule_calendar_items($ctx, $date) {
        $out=array();
        foreach ($ctx['special_periods'] as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['show_on_calendar'] ?? '1') === '0') continue;
            $from=(string)($row['start'] ?? ''); $to=(string)($row['end'] ?? '');
            if ($from === '' || $to === '' || $date < $from || $date > $to) continue;
            $title=Parcs_HT_Schedule::translation($row['title'] ?? array(),$ctx['language'],(string)($row['internal_label'] ?? ''));
            if ($title==='') continue;
            $out[]=array('title'=>$title,'kind'=>(string)($row['kind'] ?? 'event'),'color'=>(string)($row['color'] ?? '#e7c55b'));
        }
        return $out;
    }

    private static function schedule_in_school_holiday($ctx, $date) {
        foreach ($ctx['school_holidays'] as $row) {
            if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && $date >= (string)($row['start'] ?? '') && $date <= (string)($row['end'] ?? '')) return true;
        }
        return false;
    }

    private static function schedule_is_public_holiday($ctx, $date) {
        foreach ($ctx['public_holidays'] as $row) {
            if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['date'] ?? '') === $date) return true;
        }
        return false;
    }

    private static function schedule_domain_rule($ctx, $date, $status) {
        if (empty($status['open'])) return false;
        foreach ($ctx['special_periods'] as $period) {
            if (!is_array($period) || (string)($period['enabled'] ?? '0') !== '1' || (string)($period['kind'] ?? '') === 'event' || (string)($period['skip_domain_rules'] ?? '0') !== '1') continue;
            if ($date >= (string)($period['start'] ?? '') && $date <= (string)($period['end'] ?? '')) return false;
        }
        if (!empty($status['exceptional']) && (string)($status['source']['apply_domain_rules'] ?? '1') === '0') return false;
        $weekday=(string)(new DateTimeImmutable($date,new DateTimeZone('Europe/Paris')))->format('N');
        foreach ($ctx['domain_rules'] as $rule) {
            if (!is_array($rule) || (string)($rule['enabled'] ?? '0') !== '1') continue;
            if ($date < (string)($rule['start'] ?? '') || $date > (string)($rule['end'] ?? '')) continue;
            $days=array_map('strval',is_array($rule['weekdays'] ?? null)?$rule['weekdays']:array());
            if (!in_array($weekday,$days,true)) continue;
            if ((string)($rule['exclude_weekends'] ?? '0') === '1' && in_array($weekday,array('6','7'),true)) continue;
            if ((string)($rule['exclude_school_holidays'] ?? '0') === '1' && self::schedule_in_school_holiday($ctx,$date)) continue;
            if ((string)($rule['exclude_public_holidays'] ?? '0') === '1' && self::schedule_is_public_holiday($ctx,$date)) continue;
            return $rule;
        }
        return false;
    }

    private static function schedule_hours_label($status) {
        if (empty($status['open'])) return '';
        $parts=array();
        foreach (($status['slots'] ?? array()) as $slot) {
            if (!empty($slot['open']) && !empty($slot['close'])) $parts[]=$slot['open'].'–'.$slot['close'];
        }
        return implode(' / ',$parts);
    }

    private static function pdf_rgb($hex) {
        $hex=ltrim((string)$hex,'#'); if(strlen($hex)===3)$hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if(!preg_match('/^[0-9a-fA-F]{6}$/',$hex))$hex='999999';
        return array(hexdec(substr($hex,0,2))/255,hexdec(substr($hex,2,2))/255,hexdec(substr($hex,4,2))/255);
    }
    private static function pdf_light_rgb($hex,$mix=0.86) {
        $rgb=self::pdf_rgb($hex); return array($rgb[0]*(1-$mix)+$mix,$rgb[1]*(1-$mix)+$mix,$rgb[2]*(1-$mix)+$mix);
    }
    private static function pdf_short($text,$limit) {
        $text=trim(preg_replace('/\s+/u',' ',(string)$text));
        if(self::u_len($text)<=$limit)return $text;
        $cut=function_exists('mb_substr')?mb_substr($text,0,max(1,$limit-1),'UTF-8'):substr($text,0,max(1,$limit-1));
        return rtrim($cut).'…';
    }

    private static function build_schedule_pdf($ctx) {
        $w=1191; $h=842; $margin=28;
        $lang=$ctx['language'];
        $monthNames=array(
            'fr'=>array(1=>'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'),
            'en'=>array(1=>'January','February','March','April','May','June','July','August','September','October','November','December'),
            'de'=>array(1=>'Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'),
        );
        $weekNames=array(
            'fr'=>array('LUN','MAR','MER','JEU','VEN','SAM','DIM'),
            'en'=>array('MON','TUE','WED','THU','FRI','SAT','SUN'),
            'de'=>array('MO','DI','MI','DO','FR','SA','SO'),
        );
        $closedLabel=$lang==='de'?'GESCHLOSSEN':($lang==='en'?'CLOSED':'FERMÉ');
        $title=$lang==='de'?'Öffnungszeitenplan':($lang==='en'?'Opening-hours schedule':'Planning des horaires');
        $eventsLabel=$lang==='de'?'Veranstaltungen':($lang==='en'?'Events':'Événements');

        try {
            $first=new DateTimeImmutable(substr($ctx['start'],0,7).'-01',new DateTimeZone('Europe/Paris'));
            $last=new DateTimeImmutable(substr($ctx['end'],0,7).'-01',new DateTimeZone('Europe/Paris'));
        } catch (Exception $e) {
            return self::pdf_document(array('BT /F2 18 Tf 1 0 0 1 42 520 Tm ('.self::pdf_text($title).') Tj ET'),$w,$h);
        }

        $cmd=array(); $legend=array(); $allEvents=array(); $allPeriods=array(); $domainLegends=array(); $monthIndex=0;
        $hasClosed=false; $hasExceptionalHours=false; $hasEvent=false; $hasPeriod=false; $hasDomain=false; $hasHoliday=false;
        $text=static function(&$cmd,$x,$y,$value,$size=9,$bold=false){$cmd[]='0.125 0.153 0.141 rg BT /'.($bold?'F2':'F1').' '.$size.' Tf 1 0 0 1 '.round($x,2).' '.round($y,2).' Tm ('.Parcs_HT_Shortcodes::pdf_text($value).') Tj ET';};
        $rect=static function(&$cmd,$x,$y,$rw,$rh,$fill,$stroke='#d9dfdc',$line=0.6){$f=Parcs_HT_Shortcodes::pdf_rgb($fill);$st=Parcs_HT_Shortcodes::pdf_rgb($stroke);$cmd[]=sprintf('%.3F %.3F %.3F rg %.3F %.3F %.3F RG %.2F w %.2F %.2F %.2F %.2F re B',$f[0],$f[1],$f[2],$st[0],$st[1],$st[2],$line,$x,$y,$rw,$rh);};
        $fillrect=static function(&$cmd,$x,$y,$rw,$rh,$fill){$f=Parcs_HT_Shortcodes::pdf_rgb($fill);$cmd[]=sprintf('%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f',$f[0],$f[1],$f[2],$x,$y,$rw,$rh);};
        $cross=static function(&$cmd,$x,$y,$rw,$rh){$cmd[]=sprintf('0.72 0.72 0.72 RG 0.55 w %.2F %.2F m %.2F %.2F l S %.2F %.2F m %.2F %.2F l S',$x+4,$y+4,$x+$rw-4,$y+$rh-4,$x+4,$y+$rh-4,$x+$rw-4,$y+4);};
        $fillrect($cmd,0,$h-70,$w,70,'#eef4f1');
        $text($cmd,$margin,$h-34,$ctx['park'].' — '.$title.' '.$ctx['year'],21,true);
        $text($cmd,$margin,$h-52,$lang==='de'?'Effektiver Jahreskalender':($lang==='en'?'Effective annual calendar':'Calendrier annuel effectif'),8,false);

        for($month=$first;$month<=$last && $monthIndex<12;$month=$month->modify('+1 month'),$monthIndex++){

            $m=(int)$month->format('n'); $year=$month->format('Y');
            $panelCol=$monthIndex%4; $panelRow=intdiv($monthIndex,4);
            $panelW=276; $panelH=192; $panelX=$margin+$panelCol*286; $panelTop=$h-86-$panelRow*202;
            $rect($cmd,$panelX,$panelTop-$panelH,$panelW,$panelH,'#f4f7f5','#e1e7e4',0.4);
            $text($cmd,$panelX+8,$panelTop-15,$monthNames[$lang][$m],10,true);
            $gridX=$panelX+8; $gridTop=$panelTop-27; $gridW=$panelW-16; $cellW=$gridW/7; $weekH=12; $cellH=23;
            foreach($weekNames[$lang] as $i=>$name){$x=$gridX+$i*$cellW;$text($cmd,$x+13,$gridTop-9,substr($name,0,1),5.5,true);}

            $firstWeekday=(int)$month->format('N'); $days=(int)$month->format('t');
            for($slot=0;$slot<42;$slot++){
                $row=intdiv($slot,7);$col=$slot%7;$day=$slot-$firstWeekday+2;
                $x=$gridX+$col*$cellW;$top=$gridTop-$weekH-$row*$cellH;$y=$top-$cellH;
                if($day<1||$day>$days)continue;
                $date=sprintf('%s-%02d-%02d',$year,$m,$day);$status=self::schedule_resolve_day($ctx,$date);
                $isClosed=empty($status['open']);
                $base=$isClosed?'#d9d9d9':(string)$status['color'];$light=self::pdf_light_rgb($base,$isClosed?0.72:0.88);$fill=sprintf('#%02x%02x%02x',(int)round($light[0]*255),(int)round($light[1]*255),(int)round($light[2]*255));
                $holiday=(string)($ctx['general']['show_public_holidays'] ?? '0')==='1' && self::schedule_is_public_holiday($ctx,$date);
                if($holiday)$hasHoliday=true;
                $rect($cmd,$x+1,$y+1,$cellW-2,$cellH-2,$fill,$holiday?(string)($ctx['general']['holiday_border_color'] ?? '#e7c55b'):'#cbd2ce',$holiday?1.5:0.35);$fillrect($cmd,$x+1,$top-3,$cellW-2,3,$base);
                if($isClosed){$cross($cmd,$x+1,$y+1,$cellW-2,$cellH-2);$hasClosed=true;}
                $text($cmd,$x+14,$y+8,(string)$day,5.7,true);
                $hours=self::schedule_hours_label($status);
                if($hours!==''){
                    $legend[$base.'|'.$hours]=array('color'=>$base,'label'=>$hours);
                }else{
                    $legend['#d9d9d9|'.$closedLabel]=array('color'=>'#d9d9d9','label'=>$closedLabel);
                }
                $items=self::schedule_calendar_items($ctx,$date); $dayHasEvent=false; $dayHasPeriod=false;
                foreach($items as $item){
                    if((string)($item['kind'] ?? '')==='event'){$dayHasEvent=true;$hasEvent=true;$allEvents[$item['title']]=true;}
                    else{$dayHasPeriod=true;$hasPeriod=true;$allPeriods[$item['title']]=true;}
                }
                if($dayHasEvent)$text($cmd,$x+3,$top-10,'E',5.5,true);
                if($dayHasPeriod)$text($cmd,$x+$cellW-7,$y+4,'P',5,true);
                if(!$isClosed && !empty($status['exceptional']) && (string)($status['source']['show_public_marker'] ?? '1')==='1'){$text($cmd,$x+$cellW-7,$top-10,'!',6,true);$hasExceptionalHours=true;}
                $domainRule=self::schedule_domain_rule($ctx,$date,$status);
                if($domainRule){
                    $hasDomain=true;
                    $text($cmd,$x+3,$y+4,'D',5,true);
                    $domainLabel=$domainRule['label'] ?? '';
                    $domainText=is_array($domainLabel)?Parcs_HT_Schedule::translation($domainLabel,$lang,''):(string)$domainLabel;
                    if($domainText==='')$domainText=$lang==='de'?'Eingeschränkter Bereichszugang':($lang==='en'?'Limited area access':'Accès au domaine limité');
                    $times=array_filter(array((string)($domainRule['pause_start'] ?? ''),(string)($domainRule['resume'] ?? '')));
                    if($times)$domainText.=' '.implode('–',$times);
                    if((string)($domainRule['last_entry'] ?? '')!=='')$domainText.=' ('.($lang==='de'?'letzter Einlass':($lang==='en'?'last entry':'dernière entrée')).' '.(string)$domainRule['last_entry'].')';
                    $domainLegends[$domainText]=true;
                }
            }

        }
        $footerY=75;$text($cmd,$margin,$footerY+31,$lang==='de'?'Legende':($lang==='en'?'Legend':'Légende'),8,true);$lx=$margin;
        foreach($legend as $item){if($lx>$w-185){$lx=$margin;$footerY-=15;}$fillrect($cmd,$lx,$footerY+11,10,10,$item['color']);$text($cmd,$lx+15,$footerY+13,(string)$item['label'],6.2,false);$lx+=180;}
        $periodsLabel=$lang==='de'?'Zeiträume':($lang==='en'?'Reference periods':'Périodes repères');
        $explanations=array();
        if($lang==='de'){
            if($hasClosed)$explanations[]='× Geschlossen: kein Öffnungszeitraum; der Tag ist durchgestrichen.';
            if($hasExceptionalHours)$explanations[]='! Sonderöffnungszeit: ersetzt die übliche Öffnungszeit; maßgeblich ist die Zeit im Feld.';
            if($hasEvent)$explanations[]='E Veranstaltung: aktuelle Einzelheiten auf der Website prüfen.';
            if($hasPeriod)$explanations[]='P Bezugszeitraum: Kontextangabe, ändert die Öffnungszeit nicht zwingend.';
            if($hasDomain)$explanations[]='D Eingeschränkter Zugang: Der Park bleibt geöffnet; Unterbrechung und Wiederaufnahme stehen unten.';
            if($hasHoliday)$explanations[]='Farbiger Rand: Feiertag.';
        }elseif($lang==='en'){
            if($hasClosed)$explanations[]='× Closed: no opening time applies; the day is crossed out.';
            if($hasExceptionalHours)$explanations[]='! Exceptional hours: replace the usual hours; follow the time shown in the day cell.';
            if($hasEvent)$explanations[]='E Event: check the website for current details.';
            if($hasPeriod)$explanations[]='P Reference period: contextual information; it does not necessarily change the hours.';
            if($hasDomain)$explanations[]='D Limited access: the park remains open; interruption and reopening times are listed below.';
            if($hasHoliday)$explanations[]='Coloured outline: public holiday.';
        }else{
            if($hasClosed)$explanations[]='× Fermé : aucun horaire d’ouverture ne s’applique ; la journée est barrée.';
            if($hasExceptionalHours)$explanations[]='! Horaire exceptionnel : remplace l’horaire habituel ; l’horaire inscrit dans la case fait foi.';
            if($hasEvent)$explanations[]='E Événement : consultez le site pour les détails à jour.';
            if($hasPeriod)$explanations[]='P Période repère : information de contexte ; elle ne modifie pas forcément l’horaire.';
            if($hasDomain)$explanations[]='D Accès au domaine limité : le parc reste ouvert ; interruption et reprise sont précisées ci-dessous.';
            if($hasHoliday)$explanations[]='Contour coloré : jour férié.';
        }
        $explanationLines=self::pdf_wrap(implode('  |  ',$explanations),185);
        foreach(array_slice($explanationLines,0,4) as $i=>$line)$text($cmd,$margin,91-($i*9),$line,6.2,false);
        $summaries=array();
        if($allEvents)$summaries[]=$eventsLabel.' : '.implode(' · ',array_keys($allEvents));
        if($allPeriods)$summaries[]=$periodsLabel.' : '.implode(' · ',array_keys($allPeriods));
        if($domainLegends)$summaries[]='D : '.implode(' · ',array_keys($domainLegends));
        $summaryLines=array();foreach($summaries as $summary)$summaryLines=array_merge($summaryLines,self::pdf_wrap($summary,155));
        foreach(array_slice($summaryLines,0,2) as $i=>$line)$text($cmd,$margin,45-($i*8),$line,6,false);
        $site=(string)($ctx['website'] ?? '');$generated=(string)($ctx['generated_on'] ?? '');
        if($lang==='de')$disclaimer='Stand '.$generated.': Öffnungszeiten können jederzeit geändert werden. Bitte prüfen Sie vor Ihrem Besuch die aktuellen Angaben von '.$ctx['park'].' auf '.$site.'.';
        elseif($lang==='en')$disclaimer='Generated on '.$generated.': opening times may change at any time. Before visiting, always check the latest information from '.$ctx['park'].' at '.$site.'.';
        else $disclaimer='Planning généré le '.$generated.' : les horaires peuvent changer à tout moment. Avant votre visite, vérifiez toujours les informations à jour de '.$ctx['park'].' sur '.$site.'.';
        $text($cmd,$margin,18,$disclaimer,6.2,true);
        return self::pdf_document(array(implode("\n",$cmd)),$w,$h);
    }

    public static function tariffs_print_endpoint() {
        self::validate_tariff_export_request();
        $language = self::export_language();
        $settings = Parcs_HT_Defaults::settings();
        $ctx = self::tariff_export_context($settings, $language);
        nocache_headers();
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        echo '<!doctype html><html lang="'.esc_attr($language).'"><head><meta charset="'.esc_attr(get_bloginfo('charset')).'"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.esc_html($ctx['title']).'</title>';
        echo '<style>@page{size:A4;margin:14mm}*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;color:#202724;margin:0;font-size:12px}.head{border-bottom:2px solid #1f6554;padding-bottom:10px;margin-bottom:16px}.park{font-size:13px;margin:0 0 4px}.title{font-size:26px;line-height:1.1;margin:0}.meta{color:#65706b;margin-top:6px}.group{margin:18px 0;page-break-inside:avoid}.group h2{font-size:17px;margin:0 0 8px;color:#1f6554}.table{width:100%;border-collapse:collapse}.table th,.table td{border-bottom:1px solid #d7ddda;padding:8px 6px;text-align:left;vertical-align:top}.table th{font-size:10px;text-transform:uppercase;color:#5c6661}.price{text-align:right!important;white-space:nowrap;font-weight:700}.old{text-decoration:line-through;font-weight:400;color:#777;margin-right:5px}.sub{display:block;font-size:10px;color:#666;margin-top:2px}.badge{display:inline-block;font-size:9px;border:1px solid #d4ad2f;border-radius:10px;padding:1px 5px;margin-left:5px}.note{font-size:10px;line-height:1.45}.footer{border-top:1px solid #d7ddda;margin-top:20px;padding-top:10px;color:#666;font-size:9px}.actions{display:flex;gap:8px;margin:0 0 16px}.actions button,.actions a{border:0;border-radius:5px;padding:9px 13px;background:#1f6554;color:#fff;text-decoration:none;cursor:pointer}@media print{.actions{display:none}.group{break-inside:avoid}}@media(max-width:600px){body{font-size:11px}.title{font-size:21px}.table th,.table td{padding:6px 4px}}</style></head><body>';
        $pdf_url = add_query_arg(array('action'=>'parcs_ht_tariffs_pdf','lang'=>$language,'_wpnonce'=>wp_create_nonce('parcs_ht_tariff_export')), admin_url('admin-post.php'));
        $print_label = $language==='de'?'Drucken':($language==='en'?'Print':'Imprimer');
        $pdf_label = $language==='de'?'PDF herunterladen':($language==='en'?'Download PDF':'Télécharger PDF');
        echo '<div class="actions"><button type="button" onclick="window.print()">'.esc_html($print_label).'</button>';
        if ((string)($ctx['print']['pdf_enabled'] ?? '1') === '1') echo '<a href="'.esc_url($pdf_url).'">'.esc_html($pdf_label).'</a>';
        echo '</div>';
        echo self::tariff_export_html($ctx);
        if (isset($_GET['auto']) && (string)$_GET['auto']==='1') echo '<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},180);});</script>';
        echo '</body></html>';
        exit;
    }

    public static function tariffs_pdf_endpoint() {
        self::validate_tariff_export_request();
        $language = self::export_language();
        $settings = Parcs_HT_Defaults::settings();
        $ctx = self::tariff_export_context($settings, $language);
        $orientation = (string)($ctx['print']['orientation'] ?? 'portrait');
        $slug = sanitize_title($ctx['park'] ?: 'tarifs');
        $year = !empty($settings['general']['year']) ? '-' . $settings['general']['year'] : '';
        self::serve_cached_pdf('tarifs-' . $orientation, $language, ltrim($year, '-'), $slug . '-tarifs' . $year . '.pdf', static function() use ($ctx, $orientation) {
            return Parcs_HT_Shortcodes::build_tariff_pdf($ctx, $orientation === 'landscape');
        });
    }

    private static function serve_cached_pdf($type, $language, $year, $download_name, $builder) {
        $uploads = wp_upload_dir();
        $revision = max(1, (int) get_option('parcs_ht_export_revision', 1));
        $key = sanitize_file_name($type . '-' . $year . '-' . $language . '-r' . $revision . '-v' . PARCS_HT_VERSION);
        $directory = trailingslashit($uploads['basedir']) . 'horaires-tarifs-parc-exports';
        $url_base = trailingslashit($uploads['baseurl']) . 'horaires-tarifs-parc-exports';
        if (!wp_mkdir_p($directory)) {
            self::output_pdf((string) call_user_func($builder), $download_name);
        }
        $path = trailingslashit($directory) . $key . '.pdf';
        if (!is_file($path) || filesize($path) < 100) {
            $lock_path = $path . '.lock';
            $lock = @fopen($lock_path, 'c');
            if ($lock && flock($lock, LOCK_EX)) {
                if (!is_file($path) || filesize($path) < 100) {
                    $pdf = (string) call_user_func($builder);
                    $temporary = $path . '.tmp-' . wp_generate_password(8, false, false);
                    if (@file_put_contents($temporary, $pdf, LOCK_EX) !== false) {
                        @rename($temporary, $path);
                        foreach ((array) glob(trailingslashit($directory) . sanitize_file_name($type . '-' . $year . '-' . $language) . '-r*.pdf') as $old) {
                            if ($old !== $path && is_file($old)) @unlink($old);
                        }
                    }
                }
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
        if (is_file($path) && filesize($path) >= 100) {
            wp_redirect(esc_url_raw($url_base . '/' . rawurlencode(basename($path))), 302, 'Horaires-Tarifs-Parc');
            exit;
        }
        self::output_pdf((string) call_user_func($builder), $download_name);
    }

    private static function output_pdf($pdf, $download_name) {
        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($download_name) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private static function validate_tariff_export_request() {
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'parcs_ht_tariff_export')) wp_die('Lien d’export invalide.', '', array('response'=>403));
    }

    private static function export_language() {
        $lang = isset($_GET['lang']) ? sanitize_key(wp_unslash($_GET['lang'])) : Parcs_HT_Schedule::language();
        return in_array($lang, array('fr','en','de'), true) ? $lang : 'fr';
    }

    private static function tariff_export_context($settings, $language) {
        $d = Parcs_HT_Schedule::dictionaries();
        $dict = $d[$language] ?? $d['fr'];
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        $print = isset($tariffs['print']) && is_array($tariffs['print']) ? $tariffs['print'] : array();
        $year = (string)($settings['general']['year'] ?? '');
        $title = Parcs_HT_Schedule::translation($print['title'] ?? array(), $language, '');
        if ($title === '') $title = $dict['prices'] . ($year !== '' ? ' ' . $year : '');
        $park = Parcs_HT_Schedule::translation($settings['general']['park_name'] ?? array(), $language, '');
        $labels = array('individual'=>$dict['individual'],'reduced'=>$dict['reduced'],'groups'=>$dict['groups']);
        $order = isset($tariffs['group_order']) && is_array($tariffs['group_order']) ? $tariffs['group_order'] : array_keys($labels);
        $groups = array();
        foreach ($order as $key) if (isset($labels[$key]) && !isset($groups[$key])) $groups[$key]=$labels[$key];
        foreach ($labels as $key=>$label) if (!isset($groups[$key])) $groups[$key]=$label;
        $out_groups = array();
        foreach ($groups as $key=>$group_label) {
            $columns = self::tariff_columns($tariffs, $key);
            $rows = array();
            foreach (($tariffs[$key] ?? array()) as $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || !self::tariff_row_is_visible($row)) continue;
                $is_special = (string)($row['row_type'] ?? 'standard') === 'special';
                $item = array(
                    'label'=>Parcs_HT_Schedule::translation($row['label'] ?? array(), $language, ''),
                    'subtitle'=>Parcs_HT_Schedule::translation($row['subtitle'] ?? ($row['detail'] ?? array()), $language, ''),
                    'note'=>Parcs_HT_Schedule::translation($row['note'] ?? array(), $language, ''),
                    'badge'=>$is_special ? Parcs_HT_Schedule::translation($row['special_badge'] ?? array(), $language, '') : '',
                    'meta'=>$is_special ? self::special_offer_meta($row, $language) : array(),
                    'cells'=>array(), 'special'=>$is_special,
                );
                foreach ($columns as $column) {
                    $id=$column['id']; $cell=isset($row['cells'][$id])&&is_array($row['cells'][$id])?$row['cells'][$id]:array();
                    $item['cells'][]=array('label'=>Parcs_HT_Schedule::translation($column['label'],$language,''),'value'=>(string)($cell['value'] ?? ($id==='price'?($row['price']??''):'')),'old'=>(string)($cell['old_value']??''));
                }
                $rows[]=$item;
            }
            if ($rows) $out_groups[]=array('key'=>$key,'label'=>$group_label,'columns'=>$columns,'rows'=>$rows,'note'=>$key==='reduced'?Parcs_HT_Schedule::translation($tariffs['notes']??array(),$language,''):'');
        }
        $date = '';
        if ((string)($print['show_generation_date'] ?? '1') === '1') {
            $fmt = $language==='en'?'F j, Y':($language==='de'?'j. F Y':'j F Y');
            $date = wp_date($fmt, null, new DateTimeZone('Europe/Paris'));
        }
        return array('language'=>$language,'title'=>$title,'park'=>$park,'year'=>$year,'groups'=>$out_groups,'footer'=>Parcs_HT_Schedule::translation($print['footer']??array(),$language,''),'date'=>$date,'print'=>$print);
    }

    private static function tariff_export_html($ctx) {
        ob_start(); ?>
        <header class="head"><?php if ($ctx['park']) : ?><p class="park"><?php echo esc_html($ctx['park']); ?></p><?php endif; ?><h1 class="title"><?php echo esc_html($ctx['title']); ?></h1><?php if ($ctx['date']) : ?><div class="meta"><?php echo esc_html($ctx['date']); ?></div><?php endif; ?></header>
        <?php foreach ($ctx['groups'] as $group) : ?><section class="group"><h2><?php echo esc_html($group['label']); ?></h2><table class="table"><thead><tr><th><?php echo esc_html($ctx['language']==='de'?'Kategorie':($ctx['language']==='en'?'Category':'Catégorie')); ?></th><?php foreach ($group['columns'] as $col) : ?><th class="price"><?php echo esc_html(Parcs_HT_Schedule::translation($col['label'],$ctx['language'],'')); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($group['rows'] as $row) : ?><tr><td><strong><?php echo esc_html($row['label']); ?></strong><?php if ($row['badge']) : ?><span class="badge"><?php echo esc_html($row['badge']); ?></span><?php endif; ?><?php if ($row['subtitle']) : ?><span class="sub"><?php echo esc_html($row['subtitle']); ?></span><?php endif; ?><?php if ($row['note']) : ?><span class="sub"><?php echo esc_html($row['note']); ?></span><?php endif; ?><?php foreach ($row['meta'] as $meta) : ?><span class="sub"><?php echo esc_html($meta); ?></span><?php endforeach; ?></td><?php foreach ($row['cells'] as $cell) : ?><td class="price"><?php if ($row['special'] && $cell['old']!=='') : ?><span class="old"><?php echo esc_html($cell['old']); ?></span><?php endif; ?><?php echo esc_html($cell['value']); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table><?php if ($group['note']) : ?><p class="note"><?php echo nl2br(esc_html($group['note'])); ?></p><?php endif; ?></section><?php endforeach; ?>
        <?php if ($ctx['footer']) : ?><footer class="footer"><?php echo nl2br(esc_html($ctx['footer'])); ?></footer><?php endif; ?>
        <?php return ob_get_clean();
    }

    private static function build_tariff_pdf($ctx, $landscape = false) {
        $w = $landscape ? 842 : 595; $h = $landscape ? 595 : 842; $margin=42; $maxY=$h-42;
        $pages=array(); $cmd=array(); $y=$maxY;
        $addLine = static function($text,$size=10,$bold=false,$indent=0,$gap=14) use (&$cmd,&$y,$margin) {
            $font=$bold?'F2':'F1'; $text=Parcs_HT_Shortcodes::pdf_text($text);
            $cmd[]='BT /'.$font.' '.$size.' Tf 1 0 0 1 '.($margin+$indent).' '.$y.' Tm ('.$text.') Tj ET'; $y-=$gap;
        };
        $newPage = static function() use (&$pages,&$cmd,&$y,$maxY) { if ($cmd) $pages[]=implode("\n",$cmd); $cmd=array(); $y=$maxY; };
        $ensure = static function($needed=60) use (&$y,$margin,$newPage) { if ($y-$needed < $margin) $newPage(); };
        $wrap = static function($text,$limit) { return Parcs_HT_Shortcodes::pdf_wrap($text,$limit); };
        if ($ctx['park']) $addLine($ctx['park'],11,false,0,16);
        $addLine($ctx['title'],20,true,0,28);
        if ($ctx['date']) $addLine($ctx['date'],9,false,0,18);
        foreach ($ctx['groups'] as $group) {
            $ensure(70); $y-=5; $addLine($group['label'],14,true,0,22);
            foreach ($group['rows'] as $row) {
                $ensure(55);
                $label=$row['label']; if ($row['badge']) $label.=' ['.$row['badge'].']';
                $addLine($label,10,true,0,14);
                $priceParts=array(); foreach ($row['cells'] as $cell) { $part=$cell['label']!==''?$cell['label'].': ':''; if ($row['special']&&$cell['old']!=='')$part.=$cell['old'].' -> '; $part.=$cell['value']; $priceParts[]=$part; }
                foreach ($wrap(implode(' | ',$priceParts), $landscape?110:78) as $line) $addLine($line,10,false,12,13);
                $details=array_filter(array_merge(array($row['subtitle'],$row['note']),$row['meta']));
                foreach ($details as $detail) foreach ($wrap($detail,$landscape?115:82) as $line) $addLine($line,8,false,12,11);
                $y-=4;
            }
            if ($group['note']) { $ensure(45); foreach ($wrap($group['note'],$landscape?120:88) as $line) $addLine($line,8,false,0,11); }
        }
        if ($ctx['footer']) { $ensure(45); $y-=6; foreach ($wrap($ctx['footer'],$landscape?120:88) as $line) $addLine($line,8,false,0,10); }
        $newPage();
        return self::pdf_document($pages,$w,$h);
    }

    private static function pdf_wrap($text,$limit) {
        $text=trim(preg_replace('/\s+/u',' ',(string)$text)); if($text==='')return array();
        $words=preg_split('/\s+/u',$text); $lines=array(); $line='';
        foreach($words as $word){$test=$line===''?$word:$line.' '.$word; if(self::u_len($test)>$limit&&$line!==''){$lines[]=$line;$line=$word;}else{$line=$test;}} if($line!=='')$lines[]=$line; return $lines;
    }
    private static function u_len($text){return function_exists('mb_strlen')?mb_strlen($text,'UTF-8'):strlen($text);}
    private static function pdf_text($text) {
        $text=html_entity_decode(wp_strip_all_tags((string)$text),ENT_QUOTES,'UTF-8');
        $encoded=function_exists('iconv')?@iconv('UTF-8','Windows-1252//TRANSLIT',$text):$text; if($encoded===false)$encoded=$text;
        return str_replace(array('\\','(',')',"\r","\n"),array('\\\\','\\(','\\)',' ',' '),$encoded);
    }
    private static function pdf_document($pageStreams,$w,$h) {
        if(!$pageStreams)$pageStreams=array(''); $objects=array(); $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
        $font1=3;$font2=4; $objects[$font1]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'; $objects[$font2]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids=array(); $next=5;
        foreach($pageStreams as $stream){$contentId=$next++;$pageId=$next++;$objects[$contentId]='<< /Length '.strlen($stream).' >>' . "\nstream\n".$stream."\nendstream";$objects[$pageId]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$w.' '.$h.'] /Resources << /Font << /F1 '.$font1.' 0 R /F2 '.$font2.' 0 R >> >> /Contents '.$contentId.' 0 R >>';$kids[]=$pageId.' 0 R';}
        $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>'; ksort($objects);
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=array(0=>0);
        foreach($objects as $id=>$body){$offsets[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$body."\nendobj\n";}
        $xref=strlen($pdf);$max=max(array_keys($objects));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf('%010d 00000 n ', $offsets[$i]??0)."\n";$pdf.='trailer << /Size '.($max+1).' /Root 1 0 R >>' . "\nstartxref\n".$xref."\n%%EOF"; return $pdf;
    }

    private static function tariff_columns($tariffs, $group) {
        $columns = isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group]) ? $tariffs['columns'][$group] : array();
        $out = array(); $seen = array();
        foreach ($columns as $column) {
            if (!is_array($column)) continue;
            $id = sanitize_key(isset($column['id']) ? $column['id'] : '');
            if ($id === '' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $out[] = array('id'=>$id,'label'=>isset($column['label']) && is_array($column['label']) ? $column['label'] : array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'));
        }
        if (empty($out)) $out[] = array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'));
        return $out;
    }

    private static function tariff_row_is_visible($row) {
        if (!is_array($row) || ($row['row_type'] ?? 'standard') !== 'special') return true;
        $today = wp_date('Y-m-d', null, new DateTimeZone('Europe/Paris'));
        $from = (string)($row['display_from'] ?? '');
        $to = (string)($row['display_to'] ?? '');
        if ($from !== '' && $today < $from) return false;
        if ($to !== '' && $today > $to) return false;
        return true;
    }

    private static function special_offer_meta($row, $language) {
        $lines = array();
        $from = (string)($row['valid_from'] ?? '');
        $to = (string)($row['valid_to'] ?? '');
        if ($from !== '' || $to !== '') {
            $from_text = self::tariff_date_label($from, $language);
            $to_text = self::tariff_date_label($to, $language);
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

    private static function tariff_date_label($value, $language) {
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

    private static function special_buy_label($language) {
        if ($language === 'en') return 'Buy';
        if ($language === 'de') return 'Kaufen';
        return 'Acheter';
    }


    private static function payment_strip($tariffs, $language, $d) {
        $items = isset($tariffs['payment_items']) && is_array($tariffs['payment_items']) ? $tariffs['payment_items'] : array();
        if (empty($items)) return '';
        $visible = array();
        foreach ($items as $item) {
            if (!is_array($item) || (string)($item['enabled'] ?? '0') !== '1') continue;
            if ((string)($item['visible'][$language] ?? '0') !== '1') continue;
            $label = Parcs_HT_Schedule::translation(isset($item['label']) ? $item['label'] : array(), $language, '');
            if ($label === '') continue;
            $item['_label'] = $label;
            $visible[] = $item;
        }
        if (empty($visible)) return '';
        ob_start(); ?>
        <div class="parcs-ht-payment-strip" aria-label="<?php echo esc_attr($d['payments']); ?>">
            <strong class="parcs-ht-payment-title"><?php echo esc_html($d['payments']); ?></strong>
            <div class="parcs-ht-payment-icons">
                <?php foreach ($visible as $item) : ?>
                    <span class="parcs-ht-payment-item" style="<?php echo esc_attr(self::payment_item_style($item)); ?>">
                        <span class="parcs-ht-payment-icon" aria-hidden="true"><?php echo self::payment_icon_svg($item['icon'] ?? 'card', $item); ?></span>
                        <span><?php echo esc_html($item['_label']); ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    private static function payment_item_style($row) {
        if (empty($row) || !is_array($row)) return '';
        $style = '';
        $bg = !empty($row['bg_transparent']) && (string)$row['bg_transparent'] === '1' ? 'transparent' : (sanitize_hex_color($row['bg_color'] ?? '') ?: 'transparent');
        $style .= '--htp-payment-local-bg:' . $bg . ';';
        $text_color = sanitize_hex_color($row['text_color'] ?? '') ?: '#ffffff';
        $icon_color = sanitize_hex_color($row['icon_color'] ?? '') ?: $text_color;
        $style .= '--htp-payment-local-text:' . $text_color . ';color:' . $text_color . ' !important;';
        $style .= '--htp-payment-local-icon:' . $icon_color . ';';
        if (!empty($row['border_color']) && ($c = sanitize_hex_color($row['border_color']))) $style .= '--htp-payment-local-border:' . $c . ';';
        $style .= '--htp-payment-local-border-width:' . ((!empty($row['border_enabled']) && (string)$row['border_enabled']==='1') ? '1px' : '0px') . ';';
        return $style;
    }

    private static function payment_icon_svg($icon, $item = array()) {
        $common = 'viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"';
        switch ($icon) {
            case 'cash':
                // Billet + pièce, volontairement générique et sans marque.
                return '<svg ' . $common . '><rect x="2.5" y="5.5" width="15.5" height="10.5" rx="1.8"/><path d="M6 9.2h.01M14.5 12.4h.01"/><circle cx="10.2" cy="10.8" r="2.2"/><circle cx="18" cy="16.7" r="3.3"/><path d="M16.7 16.7h2.6"/></svg>';
            case 'custom':
                $svg = isset($item['custom_svg']) ? trim((string)$item['custom_svg']) : '';
                if ($svg === '') return '';
                if (!empty($item['custom_svg_force_color']) && (string)$item['custom_svg_force_color'] === '1') {
                    $svg = self::force_svg_current_color($svg);
                }
                return $svg;
            case 'none':
                return '';
            case 'card':
            default:
                // Carte bancaire générique, sans marque ni réseau de paiement.
                return '<svg ' . $common . '><rect x="2.5" y="4.8" width="19" height="14.4" rx="2.4"/><path d="M2.5 9.1h19"/><rect x="6" y="13" width="4.6" height="2.4" rx=".5" fill="currentColor" stroke="none"/><path d="M13.4 14.2h4.2"/></svg>';
        }
    }

    private static function force_svg_current_color($svg) {
        // Le SVG a déjà été filtré à l'enregistrement. Ici on ne fait que remplacer
        // ses couleurs graphiques afin que le sélecteur WordPress puisse le recolorer.
        $svg = preg_replace('/\s(fill|stroke)=("|\')(?!none\2)[^"\']*\2/i', ' $1="currentColor"', $svg);
        // Si le SVG n'indique aucune couleur, currentColor reste disponible via le conteneur.
        return $svg;
    }


    private static function tariff_row_style($row) {
        $style = '';
        $map = array(
            'label_color' => '--htp-row-label',
            'subtitle_color' => '--htp-row-detail',
            'note_color' => '--htp-row-note',
            'price_color' => '--htp-row-price',
            'row_border_color' => '--htp-row-border',
        );
        foreach ($map as $key => $var) {
            if (!empty($row[$key]) && ($color = sanitize_hex_color($row[$key]))) {
                $style .= $var . ':' . $color . ';';
            }
        }
        $transparent = !empty($row['row_bg_transparent']) && (string)$row['row_bg_transparent'] === '1';
        if ($transparent) {
            $style .= '--htp-row-bg:transparent;';
        } elseif (!empty($row['row_bg_color']) && ($color = sanitize_hex_color($row['row_bg_color']))) {
            $style .= '--htp-row-bg:' . $color . ';';
        }
        return $style;
    }

    private static function header_hour($id, $language, $style = '') {
        self::enqueue_assets();
        return '<span id="' . esc_attr($id) . '" class="parcs-ht-header-hour" data-htp-component="header-hour" data-htp-lang="' . esc_attr($language) . '" style="' . esc_attr($style) . '"></span>';
    }

    private static function header_status($id, $language, $style = '') {
        self::enqueue_assets();
        return '<span id="' . esc_attr($id) . '" class="parcs-ht-header-status" data-htp-component="header-status" data-htp-lang="' . esc_attr($language) . '" style="' . esc_attr($style) . '"></span>';
    }

    private static function home_opening($id, $language) {
        self::enqueue_assets();
        return '<div id="' . esc_attr($id) . '" class="parc-home-opening" data-htp-component="home-opening" data-htp-lang="' . esc_attr($language) . '" aria-live="polite">' .
            '<strong class="parc-home-opening__status" data-htp-home-status></strong>' .
            '<span class="parc-home-opening__hours" data-htp-home-hours></span>' .
            '<span class="parc-home-opening__last-entry" data-htp-home-last-entry hidden></span>' .
            '</div>';
    }

    private static function alert($id, $language, $style, $atts) {
        $atts = shortcode_atts(array('affichage' => 'inline'), $atts, 'parc_fermeture_exceptionnelle');
        $display = $atts['affichage'] === 'popup' ? 'popup' : 'inline';
        return '<div id="' . esc_attr($id) . '" class="parcs-ht-alert-host" data-htp-component="alert" data-htp-lang="' . esc_attr($language) . '" data-htp-display="' . esc_attr($display) . '" style="' . esc_attr($style) . '" hidden></div>';
    }

    private static function style_variables($general) {
        $base = array(
            '--htp-primary' => $general['primary_color'] ?? '#006757', '--htp-secondary' => $general['secondary_color'] ?? '#31ad81', '--htp-accent' => $general['accent_color'] ?? '#ef7b5b', '--htp-highlight' => $general['highlight_color'] ?? '#e7c55b',
            '--htp-calendar-nav-bg' => $general['calendar_nav_bg_color'] ?? '#006757', '--htp-calendar-nav-text' => $general['calendar_nav_text_color'] ?? '#ffffff', '--htp-calendar-nav-active-bg' => $general['calendar_nav_active_bg_color'] ?? '#e7c55b', '--htp-calendar-nav-active-text' => $general['calendar_nav_active_text_color'] ?? '#27342f',
            '--htp-calendar-closed-bg' => $general['calendar_closed_bg_color'] ?? '#e3e5e4', '--htp-calendar-closed-text' => $general['calendar_closed_text_color'] ?? '#616765', '--htp-calendar-selected' => $general['calendar_selected_color'] ?? '#006757', '--htp-holiday-border' => $general['holiday_border_color'] ?? '#e7c55b',
            '--htp-payment-item-bg' => $general['payment_item_bg_color'] ?? '#006757', '--htp-payment-item-text' => $general['payment_item_text_color'] ?? '#ffffff', '--htp-payment-icon' => $general['payment_icon_color'] ?? '#ffffff',
            '--htp-tab-bg' => $general['tab_bg_color'] ?? '#006757', '--htp-tab-text' => $general['tab_text_color'] ?? '#ffffff', '--htp-tab-active-bg' => $general['tab_active_bg_color'] ?? '#e7c55b', '--htp-tab-active-text' => $general['tab_active_text_color'] ?? '#27342f',
            '--htp-button-bg' => $general['button_bg_color'] ?? '#006757', '--htp-button-text' => $general['button_text_color'] ?? '#ffffff', '--htp-primary-button-bg' => $general['primary_button_bg_color'] ?? '#ef7b5b', '--htp-primary-button-text' => $general['primary_button_text_color'] ?? '#ffffff',
            '--htp-alert-bg' => $general['alert_bg_color'] ?? '#006757', '--htp-alert-title' => $general['alert_title_color'] ?? '#ffffff', '--htp-alert-text' => $general['alert_text_color'] ?? '#ffffff', '--htp-alert-border' => $general['alert_border_color'] ?? '#ef7b5b', '--htp-alert-button-bg' => $general['alert_button_bg_color'] ?? '#ef7b5b', '--htp-alert-button-text' => $general['alert_button_text_color'] ?? '#ffffff', '--htp-alert-button-border' => $general['alert_button_border_color'] ?? '#ef7b5b',
        );
        $optional = array(
            'body_text_color'=>'--htp-body-text','heading_text_color'=>'--htp-heading-text','border_color'=>'--htp-border',
            'today_title_color'=>'--htp-today-title','today_status_color'=>'--htp-today-status','today_closed_color'=>'--htp-today-closed','today_detail_color'=>'--htp-today-detail',
            'calendar_title_color'=>'--htp-calendar-title','calendar_weekday_color'=>'--htp-calendar-weekday','calendar_detail_text_color'=>'--htp-calendar-detail-text','calendar_detail_border_color'=>'--htp-calendar-detail-border',
            'tariff_kicker_color'=>'--htp-tariff-kicker','tariff_title_color'=>'--htp-tariff-title','payment_title_color'=>'--htp-payment-title','payment_border_color'=>'--htp-payment-border','panel_text_color'=>'--htp-panel-text','panel_border_color'=>'--htp-panel-border','price_color'=>'--htp-price','tariff_note_text_color'=>'--htp-tariff-note-text','tariff_note_border_color'=>'--htp-tariff-note-border','groups_note_text_color'=>'--htp-groups-note-text','groups_note_border_color'=>'--htp-groups-note-border'
        );
        $style = '';
        foreach ($base as $var=>$value) { $c = sanitize_hex_color($value); if ($c) $style .= $var . ':' . $c . ';'; }
        foreach ($optional as $key=>$var) { if (!empty($general[$key]) && ($c = sanitize_hex_color($general[$key]))) $style .= $var . ':' . $c . ';'; }
        $title_bgs = array(
            array('today_title_bg_transparent','today_title_bg_color','--htp-today-title-bg'),
            array('calendar_title_bg_transparent','calendar_title_bg_color','--htp-calendar-title-bg'),
            array('tariff_title_bg_transparent','tariff_title_bg_color','--htp-tariff-title-bg'),
            array('payment_title_bg_transparent','payment_title_bg_color','--htp-payment-title-bg'),
            array('panel_bg_transparent','panel_bg_color','--htp-panel-bg'),
        );
        foreach ($title_bgs as $bg) {
            $transparent = !empty($general[$bg[0]]) && (string)$general[$bg[0]] === '1';
            $value = $transparent ? 'transparent' : (sanitize_hex_color($general[$bg[1]] ?? '') ?: 'transparent');
            $style .= $bg[2] . ':' . $value . ';';
        }
        $day_bg = (!empty($general['calendar_day_bg_transparent']) && (string)$general['calendar_day_bg_transparent']==='1') ? 'transparent' : (sanitize_hex_color($general['calendar_day_bg_color'] ?? '') ?: '#ffffff');
        $style .= '--htp-calendar-day-bg:' . $day_bg . ';';
        $holiday_width = isset($general['holiday_border_width']) ? max(1, min(8, (int)$general['holiday_border_width'])) : 3;
        $style .= '--htp-holiday-border-width:' . $holiday_width . 'px;';
        $spacing = isset($general['block_spacing']) ? max(0, min(60, (int)$general['block_spacing'])) : 8;
        $style .= '--htp-block-spacing:' . $spacing . 'px;';
        $style .= '--htp-block-border-width:' . ((!empty($general['block_border_enabled']) && (string)$general['block_border_enabled']==='1') ? '1px' : '0px') . ';';
        $style .= '--htp-payment-border-width:' . ((!empty($general['payment_border_enabled']) && (string)$general['payment_border_enabled']==='1') ? '1px' : '0px') . ';';
        $style .= '--htp-panel-border-width:' . ((!empty($general['panel_border_enabled']) && (string)$general['panel_border_enabled']==='1') ? '1px' : '0px') . ';';
        $mobile_size = isset($general['calendar_mobile_size']) ? (string)$general['calendar_mobile_size'] : 'medium';
        $mobile_presets = array(
            'small' => array('grid'=>'306px','day_h'=>'31px','day_font'=>'11px','weekday_font'=>'9px','legend_font'=>'8px','symbol'=>'13px','detail_title'=>'14px','detail_hours'=>'14px','detail_last'=>'11px'),
            'medium' => array('grid'=>'318px','day_h'=>'32px','day_font'=>'12px','weekday_font'=>'11px','legend_font'=>'10px','symbol'=>'15px','detail_title'=>'15px','detail_hours'=>'16px','detail_last'=>'12px'),
            'large' => array('grid'=>'336px','day_h'=>'34px','day_font'=>'13px','weekday_font'=>'12px','legend_font'=>'11px','symbol'=>'16px','detail_title'=>'16px','detail_hours'=>'17px','detail_last'=>'13px'),
        );
        if (!isset($mobile_presets[$mobile_size])) $mobile_size = 'medium';
        foreach ($mobile_presets[$mobile_size] as $key => $value) $style .= '--htp-mobile-' . str_replace('_','-',$key) . ':' . $value . ';';
        // 1.5.5 : profil typographique. La police elle-même reste toujours héritée du thème.
        $font_profile = isset($general['font_profile']) ? (string)$general['font_profile'] : 'inherit';
        if ($font_profile === 'caltons') {
            // Caltons a une hauteur visuelle plus compacte : titres légèrement agrandis et interlignes resserrés.
            $style .= '--htp-title-line-height:.94;--htp-kicker-line-height:1;--htp-header-status-line-height:.94;--htp-header-hour-line-height:1.12;';
            if (empty($general['font_heading_size'])) $style .= '--htp-font-heading:clamp(34px,4.6vw,52px);';
            if (empty($general['font_today_status_size'])) $style .= '--htp-font-today-status:clamp(36px,6.5vw,64px);';
            if (empty($general['font_today_title_size'])) $style .= '--htp-font-today-title:15px;';
            if (empty($general['font_calendar_title_size'])) $style .= '--htp-font-calendar-title:clamp(34px,4.6vw,52px);';
            if (empty($general['font_tariff_title_size'])) $style .= '--htp-font-tariff-title:clamp(34px,4.6vw,52px);';
            if (empty($general['font_calendar_detail_title_size'])) $style .= '--htp-font-calendar-detail-title:clamp(25px,3.4vw,34px);';
            $style .= '--htp-header-status-size:1.46em;--htp-header-hour-size:1em;';
        } elseif ($font_profile === 'custom') {
            // Les tailles personnalisées sont gérées par les champs avancés existants.
            $style .= '--htp-title-line-height:1.05;--htp-kicker-line-height:1.08;';
        } else {
            $style .= '--htp-title-line-height:1.08;--htp-kicker-line-height:1.15;';
        }

        // 1.3.0 : réglages simples de lisibilité.
        $typography = isset($general['typography_preset']) ? (string)$general['typography_preset'] : 'standard';
        $typography_scale = array('compact'=>'.92em','standard'=>'1em','large'=>'1.12em');
        $style .= '--htp-type-scale:' . (isset($typography_scale[$typography]) ? $typography_scale[$typography] : '1') . ';';
        $detail = isset($general['calendar_detail_preset']) ? (string)$general['calendar_detail_preset'] : 'large';
        $detail_presets = array(
            'standard'=>array('title'=>'17px','hours'=>'20px','last'=>'13px'),
            'large'=>array('title'=>'19px','hours'=>'25px','last'=>'15px'),
            'xlarge'=>array('title'=>'21px','hours'=>'29px','last'=>'16px'),
        );
        if (!isset($detail_presets[$detail])) $detail='large';
        $style .= '--htp-simple-detail-title:' . $detail_presets[$detail]['title'] . ';';
        $style .= '--htp-simple-detail-hours:' . $detail_presets[$detail]['hours'] . ';';
        $style .= '--htp-simple-detail-last:' . $detail_presets[$detail]['last'] . ';';
        $font_vars = array(
            'font_body_size'=>'--htp-font-body','font_heading_size'=>'--htp-font-heading','font_kicker_size'=>'--htp-font-kicker','font_button_size'=>'--htp-font-button',
            'font_today_title_size'=>'--htp-font-today-title','font_today_status_size'=>'--htp-font-today-status','font_today_detail_size'=>'--htp-font-today-detail',
            'font_calendar_title_size'=>'--htp-font-calendar-title','font_calendar_month_size'=>'--htp-font-calendar-month','font_calendar_summary_size'=>'--htp-font-calendar-summary','font_calendar_weekday_size'=>'--htp-font-calendar-weekday','font_calendar_day_size'=>'--htp-font-calendar-day','font_calendar_detail_title_size'=>'--htp-font-calendar-detail-title','font_calendar_detail_hours_size'=>'--htp-font-calendar-detail-hours','font_calendar_detail_last_size'=>'--htp-font-calendar-detail-last','font_calendar_legend_size'=>'--htp-font-calendar-legend',
            'font_tariff_title_size'=>'--htp-font-tariff-title','font_tariff_tab_size'=>'--htp-font-tariff-tab','font_tariff_label_size'=>'--htp-font-tariff-label','font_tariff_detail_size'=>'--htp-font-tariff-detail','font_tariff_note_size'=>'--htp-font-tariff-note','font_tariff_price_size'=>'--htp-font-tariff-price',
            'font_payment_title_size'=>'--htp-font-payment-title','font_payment_item_size'=>'--htp-font-payment-item','font_groups_note_size'=>'--htp-font-groups-note','font_alert_title_size'=>'--htp-font-alert-title','font_alert_text_size'=>'--htp-font-alert-text','font_alert_button_size'=>'--htp-font-alert-button',
        );
        foreach ($font_vars as $key=>$var) {
            if (isset($general[$key]) && $general[$key] !== '' && is_numeric($general[$key])) {
                $px = max(8, min(100, (int)$general[$key]));
                $style .= $var . ':' . $px . 'px;';
            }
        }
        return $style;
    }
}
