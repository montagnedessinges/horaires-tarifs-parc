<?php

if (!defined('ABSPATH')) { exit; }

/** Administration dédiée aux tarifs visiteurs (Individuels + Tarifs réduits). */
final class Parcs_HT_Admin_Retail_Tariffs {
    const PAGE = 'parcs-ht-tariffs';

    public static function init() {
        // La navigation 1.17.2 connaissait encore cette URL comme un pont legacy.
        // On conserve toutes ses autres routes, mais cette page devient désormais native.
        remove_action('admin_init', array('Parcs_HT_Admin_Navigation', 'route'), 1);
        add_action('admin_init', array(__CLASS__, 'route'), 1);
        add_action('admin_menu', array(__CLASS__, 'menu'), 61);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 65);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'preserve_group_tariffs'), 70, 3);
    }

    private static function requested_year() {
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function selected_year($all = null) {
        if ($all === null) $all = Parcs_HT_Defaults::all_settings();
        $requested = self::requested_year();
        if ($requested !== '' && isset($all['seasons'][$requested])) return $requested;
        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? '');
        if ($active !== '' && isset($all['seasons'][$active])) return $active;
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) return (string)$year;
        }
        return '';
    }

    private static function redirect($url) {
        wp_safe_redirect($url);
        exit;
    }

    /** Préserve les routes de compatibilité et remplace uniquement l’ancien onglet Tarifs. */
    public static function route() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page === self::PAGE) return;

        if (class_exists('Parcs_HT_Admin') && $page === Parcs_HT_Admin::PAGE && $tab === 'htp-tariffs') {
            $args = array('page'=>self::PAGE);
            $year = self::requested_year();
            if ($year !== '') $args['season'] = $year;
            foreach (array('updated','preserved') as $notice) {
                if (isset($_GET[$notice])) $args[$notice] = '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur visuel uniquement.
            }
            self::redirect(add_query_arg($args, admin_url('admin.php')));
        }

        if (class_exists('Parcs_HT_Admin_Navigation')) Parcs_HT_Admin_Navigation::route();
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        remove_submenu_page(Parcs_HT_Admin::PAGE, self::PAGE);
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Tarifs visiteurs',
            'Tarifs visiteurs',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    public static function assets() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        if ($page !== self::PAGE) return;

        $year = self::selected_year();
        $settings = Parcs_HT_Defaults::settings($year);
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();

        wp_enqueue_style('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-admin-tariffs-1175', PARCS_HT_URL . 'assets/admin-tariffs-1175.css', array('parcs-ht-admin'), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-preview-engine', PARCS_HT_URL . 'assets/frontend.js', array(), PARCS_HT_VERSION, true);
        wp_enqueue_script('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.js', array('parcs-ht-preview-engine','jquery-ui-sortable'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin', 'window.ParcsHTAdmin=' . wp_json_encode(array('language'=>'fr')) . ';', 'before');

        // Réutilise exactement les enrichissements historiques (colonnes visibles, offres et pop-up).
        wp_enqueue_script('parcs-ht-tariff-seasons-admin', PARCS_HT_URL . 'assets/tariff-seasons-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-tariff-seasons-admin', 'window.ParcsHTTariffSeasonAdmin=' . wp_json_encode(array('tariffs'=>$tariffs)) . ';', 'before');

        // Réutilise les deux canaux visiteurs introduits en 1.16.7.
        $states = array();
        foreach ((array)($tariffs['payment_items'] ?? array()) as $index => $item) {
            $channels = class_exists('Parcs_HT_Payment_Channels') ? Parcs_HT_Payment_Channels::channels_for_item($item) : array('onsite'=>true,'online'=>false);
            $states[(string)$index] = array('onsite'=>!empty($channels['onsite']), 'online'=>!empty($channels['online']));
        }
        wp_enqueue_script('parcs-ht-payment-channels-admin', PARCS_HT_URL . 'assets/payment-channels-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-payment-channels-admin', 'window.ParcsHTPaymentChannels=' . wp_json_encode(array('items'=>$states)) . ';', 'before');
    }

    /**
     * Un enregistrement de l’écran Visiteurs ne doit jamais toucher au bloc Groupes.
     * Le moteur tarifaire canonique reste unique : on restaure simplement le sous-arbre
     * groupes de la valeur précédente avant les filtres d’identités et de canaux.
     */
    public static function preserve_group_tariffs($new_value, $old_value, $option) {
        unset($option);
        if (!is_admin() || !is_array($new_value) || !is_array($old_value) || !current_user_can('manage_options')) return $new_value;
        $workspace = isset($_POST['parcs_ht_retail_workspace']) ? sanitize_text_field(wp_unslash($_POST['parcs_ht_retail_workspace'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- le nonce canonique est vérifié juste après.
        if ($workspace !== '1') return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) return $new_value;

        $old_global = isset($old_value['tariffs']) && is_array($old_value['tariffs']) ? $old_value['tariffs'] : array();
        $old_season = isset($old_value['seasons'][$year]['tariffs']) && is_array($old_value['seasons'][$year]['tariffs']) ? $old_value['seasons'][$year]['tariffs'] : $old_global;

        self::restore_group_branch($new_value['tariffs'], $old_global);
        if (isset($new_value['seasons'][$year]) && is_array($new_value['seasons'][$year])) {
            if (!isset($new_value['seasons'][$year]['tariffs']) || !is_array($new_value['seasons'][$year]['tariffs'])) $new_value['seasons'][$year]['tariffs'] = array();
            self::restore_group_branch($new_value['seasons'][$year]['tariffs'], $old_season);
        }
        return $new_value;
    }

    private static function restore_group_branch(&$target, $source) {
        if (!is_array($target)) $target = array();
        $source = is_array($source) ? $source : array();
        $target['groups'] = isset($source['groups']) && is_array($source['groups']) ? $source['groups'] : array();
        if (!isset($target['columns']) || !is_array($target['columns'])) $target['columns'] = array();
        $target['columns']['groups'] = isset($source['columns']['groups']) && is_array($source['columns']['groups']) ? $source['columns']['groups'] : array();
        if (isset($source['group_order']) && is_array($source['group_order'])) $target['group_order'] = $source['group_order'];
    }

    private static function input($name, $value, $label, $type = 'text') {
        ?><label class="htp-field"><span><?php echo esc_html($label); ?></span><input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string)$value); ?>"></label><?php
    }

    private static function checkbox($name, $value, $label) {
        ?><label><input type="hidden" name="<?php echo esc_attr($name); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php checked((string)$value, '1'); ?>> <?php echo esc_html($label); ?></label><?php
    }

    private static function enabled($name, $value) {
        ?><label class="htp-enabled"><input type="hidden" name="<?php echo esc_attr($name); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php checked((string)$value, '1'); ?>> Actif</label><?php
    }

    private static function select($name, $value, $label, $options) {
        ?><label class="htp-field"><span><?php echo esc_html($label); ?></span><select name="<?php echo esc_attr($name); ?>"><?php foreach ($options as $key=>$text) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected((string)$value, (string)$key); ?>><?php echo esc_html($text); ?></option><?php endforeach; ?></select></label><?php
    }

    private static function translated_input($name, $values, $label, $type = 'text') {
        $values = is_array($values) ? $values : array(); ?>
        <div class="htp-field htp-local-translation" data-htp-local-lang="fr"><span><?php echo esc_html($label); ?></span><div class="htp-mini-lang" role="tablist" aria-label="Langue du champ"><?php foreach (array('fr','en','de') as $lang) : ?><button type="button" class="button button-small <?php echo $lang === 'fr' ? 'button-primary' : ''; ?>" data-htp-local-language="<?php echo esc_attr($lang); ?>"><?php echo esc_html(strtoupper($lang)); ?></button><?php endforeach; ?></div><?php foreach (array('fr','en','de') as $lang) : ?><input class="htp-local-lang-field" data-lang="<?php echo esc_attr($lang); ?>" type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name . '[' . $lang . ']'); ?>" value="<?php echo esc_attr((string)($values[$lang] ?? '')); ?>" <?php echo $lang === 'fr' ? '' : 'hidden'; ?>><?php endforeach; ?></div><?php
    }

    private static function translated_textarea($name, $values, $label) {
        $values = is_array($values) ? $values : array(); ?>
        <div class="htp-field htp-local-translation" data-htp-local-lang="fr"><span><?php echo esc_html($label); ?></span><div class="htp-mini-lang" role="tablist" aria-label="Langue du champ"><?php foreach (array('fr','en','de') as $lang) : ?><button type="button" class="button button-small <?php echo $lang === 'fr' ? 'button-primary' : ''; ?>" data-htp-local-language="<?php echo esc_attr($lang); ?>"><?php echo esc_html(strtoupper($lang)); ?></button><?php endforeach; ?></div><?php foreach (array('fr','en','de') as $lang) : ?><textarea class="htp-local-lang-field" data-lang="<?php echo esc_attr($lang); ?>" rows="4" name="<?php echo esc_attr($name . '[' . $lang . ']'); ?>" <?php echo $lang === 'fr' ? '' : 'hidden'; ?>><?php echo esc_textarea((string)($values[$lang] ?? '')); ?></textarea><?php endforeach; ?></div><?php
    }

    private static function optional_color_input($name, $value, $label) {
        $color = sanitize_hex_color($value);
        $inherit = !$color; ?>
        <label class="htp-field"><span><?php echo esc_html($label); ?></span><span class="htp-optional-color"><input type="color" class="htp-optional-color-picker" value="<?php echo esc_attr($color ?: '#000000'); ?>" <?php disabled($inherit); ?>><input type="hidden" class="htp-optional-color-value" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($color ?: ''); ?>"><span class="htp-optional-color-inherit-wrap"><input type="checkbox" class="htp-optional-color-inherit" <?php checked($inherit); ?>> Hériter du thème</span></span></label><?php
    }

    private static function column_row($group, $index, $column) {
        $column = wp_parse_args(is_array($column) ? $column : array(), array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
        $raw_id = (string)($column['id'] ?? '');
        $id = $raw_id === '__COLID__' ? '__COLID__' : sanitize_key($raw_id);
        if ($id === '') $id = 'price';
        $base = 'settings[tariffs][columns][' . $group . '][' . $index . ']'; ?>
        <div class="htp-tariff-column-row" data-htp-tariff-column data-col-id="<?php echo esc_attr($id); ?>">
            <button type="button" class="button button-small htp-sort-handle htp-sort-handle-column" title="Glisser pour déplacer" aria-label="Déplacer la colonne">↕</button>
            <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" data-htp-column-id-input>
            <div class="htp-tariff-column-label"><?php self::translated_input($base . '[label]', $column['label'], 'Nom de la colonne'); ?></div>
            <div class="htp-order-buttons"><button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button><button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button></div>
            <button type="button" class="button-link-delete" data-htp-remove-column>Supprimer</button>
        </div><?php
    }

    private static function tariff_row($group, $index, $row, $columns) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'id'=>'','enabled'=>'1','label'=>array('fr'=>'','en'=>'','de'=>''),'detail'=>array('fr'=>'','en'=>'','de'=>''),'subtitle'=>array('fr'=>'','en'=>'','de'=>''),'note'=>array('fr'=>'','en'=>'','de'=>''),'price'=>'','cells'=>array(),
            'row_type'=>'standard','special_badge'=>array('fr'=>'','en'=>'','de'=>''),'valid_from'=>'','valid_to'=>'','display_from'=>'','display_to'=>'','sale_channel'=>'both','purchase_url'=>array('fr'=>'','en'=>'','de'=>''),'show_special_dot'=>'1',
            'label_color'=>'','detail_color'=>'','subtitle_color'=>'','note_color'=>'','price_color'=>'','row_bg_color'=>'#ffffff','row_bg_transparent'=>'1','row_border_color'=>''
        ));
        if (!$columns) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
        $base = 'settings[tariffs][' . $group . '][' . $index . ']';
        $special = (string)$row['row_type'] === 'special'; ?>
        <div class="htp-repeat-row htp-tariff-row" data-htp-tariff-row>
            <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr((string)$row['id']); ?>">
            <div class="htp-row-head"><button type="button" class="button button-small htp-sort-handle htp-sort-handle-row" title="Glisser pour déplacer" aria-label="Déplacer la ligne">↕</button><strong>Ligne tarifaire</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><div class="htp-order-buttons"><button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button><button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button></div><button type="button" class="button button-small" data-htp-duplicate-tariff-row>Dupliquer</button><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-3"><?php self::translated_input($base . '[label]', $row['label'], 'Libellé'); ?><?php self::translated_input($base . '[subtitle]', $row['subtitle'] ?: $row['detail'], 'Sous-titre / âge / précision'); ?><?php self::translated_textarea($base . '[note]', $row['note'], 'Texte secondaire facultatif'); ?><?php self::select($base . '[row_type]', $row['row_type'], 'Type de ligne', array('standard'=>'Tarif classique','special'=>'Offre / billet spécial')); ?></div>
            <div class="htp-tariff-cell-grid" data-htp-tariff-cells><?php foreach ($columns as $column) : $col_id = sanitize_key((string)($column['id'] ?? '')); if ($col_id === '') continue; $cell = isset($row['cells'][$col_id]) && is_array($row['cells'][$col_id]) ? $row['cells'][$col_id] : array(); $value = isset($cell['value']) ? $cell['value'] : ($col_id === 'price' ? $row['price'] : ''); $old = $cell['old_value'] ?? ''; $label = (string)($column['label']['fr'] ?? 'Prix'); ?><div class="htp-tariff-cell-fields" data-htp-tariff-cell data-col-id="<?php echo esc_attr($col_id); ?>"><strong class="htp-tariff-cell-title"><?php echo esc_html($label ?: 'Prix'); ?></strong><?php self::input($base . '[cells][' . $col_id . '][value]', $value, 'Prix / valeur'); ?><div data-htp-old-price-field <?php echo $special ? '' : 'hidden'; ?>><?php self::input($base . '[cells][' . $col_id . '][old_value]', $old, 'Ancien prix à barrer (facultatif)'); ?></div></div><?php endforeach; ?></div>
            <div class="htp-special-offer-settings" data-htp-special-offer-settings <?php echo $special ? '' : 'hidden'; ?>><h4>Billet / offre spéciale</h4><p class="description">Période de validité, période de vente et canal de vente restent liés à cette ligne.</p><div class="htp-grid htp-grid-3"><label class="htp-field"><span>Repère visuel</span><span><?php self::checkbox($base . '[show_special_dot]', $row['show_special_dot'], 'Afficher un petit point devant le billet'); ?></span></label><?php self::translated_input($base . '[special_badge]', $row['special_badge'], 'Petit badge facultatif'); ?><?php self::select($base . '[sale_channel]', $row['sale_channel'], 'Canal de vente', array('both'=>'En ligne + sur place','online'=>'En ligne uniquement','onsite'=>'Sur place uniquement')); ?><?php self::input($base . '[valid_from]', $row['valid_from'], 'Billet valable à partir du', 'date'); ?><?php self::input($base . '[valid_to]', $row['valid_to'], 'Billet valable jusqu’au', 'date'); ?><?php self::input($base . '[display_from]', $row['display_from'], 'Début de vente / affichage', 'date'); ?><?php self::input($base . '[display_to]', $row['display_to'], 'Fin de vente / affichage', 'date'); ?><?php self::translated_input($base . '[purchase_url]', $row['purchase_url'], 'Lien d’achat spécifique (facultatif)', 'url'); ?></div><?php if ($group === 'reduced') : ?><p class="description htp-1175-reduced-rule">Les tarifs réduits restent sans bouton d’achat en ligne dans le rendu public, conformément au comportement visiteurs existant.</p><?php endif; ?></div>
            <details class="htp-row-details"><summary>Apparence de cette ligne</summary><div class="htp-details-content htp-grid htp-grid-3"><?php self::optional_color_input($base . '[label_color]', $row['label_color'], 'Nom du tarif — couleur'); ?><?php self::optional_color_input($base . '[subtitle_color]', $row['subtitle_color'] ?: $row['detail_color'], 'Sous-titre — couleur'); ?><?php self::optional_color_input($base . '[note_color]', $row['note_color'], 'Texte secondaire — couleur'); ?><?php self::optional_color_input($base . '[price_color]', $row['price_color'], 'Prix — couleur'); ?><?php self::input($base . '[row_bg_color]', $row['row_bg_color'], 'Fond de la ligne', 'color'); ?><label class="htp-field"><span>Fond transparent</span><span><?php self::checkbox($base . '[row_bg_transparent]', $row['row_bg_transparent'], 'Transparent'); ?></span></label><?php self::optional_color_input($base . '[row_border_color]', $row['row_border_color'], 'Séparateur / bordure'); ?></div></details>
        </div><?php
    }

    private static function category($group, $title, $description, $tariffs) {
        $columns = isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group]) ? $tariffs['columns'][$group] : array();
        if (!$columns) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))); ?>
        <section class="htp-1175-category" data-htp-tariff-group="<?php echo esc_attr($group); ?>">
            <div class="htp-1175-category-head"><div><h2><?php echo esc_html($title); ?></h2><p class="description"><?php echo esc_html($description); ?></p></div><span class="htp-1175-count"><?php echo esc_html((string)count((array)($tariffs[$group] ?? array()))); ?> ligne(s)</span></div>
            <div class="htp-tariff-columns-manager"><div class="htp-tariff-columns-title"><strong>Colonnes de prix</strong><span class="description">Ajoutez, masquez ou réorganisez les colonnes sans changer les identités métier.</span></div><div class="htp-tariff-columns-list" data-htp-tariff-columns><?php foreach ($columns as $col_index=>$column) self::column_row($group, $col_index, $column); ?></div><button type="button" class="button" data-htp-add-column>Ajouter une colonne</button><script type="text/html" class="htp-template-tariff-column"><?php self::column_row($group, '__COLINDEX__', array('id'=>'__COLID__','label'=>array('fr'=>'Nouvelle colonne','en'=>'New column','de'=>'Neue Spalte'))); ?></script></div>
            <div class="htp-repeater htp-tariff-repeater" data-template="htp-template-tariff-<?php echo esc_attr($group); ?>" data-htp-tariff-row-repeater><div class="htp-repeater-rows"><?php foreach ((array)($tariffs[$group] ?? array()) as $index=>$row) self::tariff_row($group, $index, $row, $columns); ?></div><button type="button" class="button htp-add-row">Ajouter une ligne</button></div><script type="text/html" id="htp-template-tariff-<?php echo esc_attr($group); ?>"><?php self::tariff_row($group, '__INDEX__', array(), $columns); ?></script>
        </section><?php
    }

    private static function payment_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array('enabled'=>'1','icon'=>'card','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'','en'=>'','de'=>''),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'));
        $base = 'settings[tariffs][payment_items][' . $index . ']'; ?>
        <div class="htp-repeat-row htp-payment-admin-row"><div class="htp-row-head"><strong>Moyen de paiement</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div><div class="htp-grid htp-grid-3"><?php self::select($base . '[icon]', $row['icon'], 'Pictogramme', array('card'=>'Carte bancaire (SVG intégré)','cash'=>'Espèces (SVG intégré)','custom'=>'SVG personnalisé','none'=>'Aucun pictogramme')); ?><?php self::translated_input($base . '[label]', $row['label'], 'Nom affiché'); ?><label class="htp-field htp-svg-field"><span>SVG personnalisé</span><textarea rows="5" name="<?php echo esc_attr($base . '[custom_svg]'); ?>"><?php echo esc_textarea($row['custom_svg']); ?></textarea></label><label class="htp-field"><span>Couleur du SVG personnalisé</span><span><?php self::checkbox($base . '[custom_svg_force_color]', $row['custom_svg_force_color'], 'Forcer la couleur'); ?></span></label><fieldset class="htp-field"><span>Afficher dans les langues</span><div class="htp-lang-visibility"><?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?><label><input type="hidden" name="<?php echo esc_attr($base . '[visible][' . $lang . ']'); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($base . '[visible][' . $lang . ']'); ?>" value="1" <?php checked((string)($row['visible'][$lang] ?? '0'), '1'); ?>> <?php echo esc_html($label); ?></label><?php endforeach; ?></div></fieldset><?php self::input($base . '[bg_color]', $row['bg_color'], 'Fond', 'color'); ?><label class="htp-field"><span>Fond transparent</span><span><?php self::checkbox($base . '[bg_transparent]', $row['bg_transparent'], 'Transparent'); ?></span></label><?php self::input($base . '[icon_color]', $row['icon_color'], 'Pictogramme', 'color'); ?><?php self::input($base . '[text_color]', $row['text_color'], 'Texte', 'color'); ?><?php self::input($base . '[border_color]', $row['border_color'], 'Bordure', 'color'); ?><label class="htp-field"><span>Bordure</span><span><?php self::checkbox($base . '[border_enabled]', $row['border_enabled'], 'Afficher'); ?></span></label></div></div><?php
    }

    private static function payment_section($tariffs) { ?>
        <section class="htp-card htp-1175-card"><h2>Moyens de paiement visiteurs</h2><p class="description">Les canaux « Sur place » et « En ligne » restent indépendants. Le canal en ligne n’est utilisé que pour Individuels dans le rendu public.</p><div class="htp-payment-admin"><div class="htp-repeater" data-template="htp-template-payment"><div class="htp-repeater-rows"><?php foreach ((array)($tariffs['payment_items'] ?? array()) as $index=>$row) self::payment_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter un moyen de paiement</button></div><script type="text/html" id="htp-template-payment"><?php self::payment_row('__INDEX__', array()); ?></script></div></section><?php
    }

    private static function appearance_section($g) { ?>
        <section class="htp-card htp-1175-card"><details class="htp-advanced"><summary>Apparence des tarifs visiteurs</summary><div class="htp-advanced-content"><p class="description">Réglages historiques conservés pour compatibilité. Ils seront reliés progressivement au socle d’apparence globale, sans créer de doublons.</p><div class="htp-grid htp-grid-3"><?php self::optional_color_input('settings[general][tariff_kicker_color]', $g['tariff_kicker_color'] ?? '', 'Petit titre TARIFS — texte'); ?><?php self::optional_color_input('settings[general][tariff_title_color]', $g['tariff_title_color'] ?? '', 'Titre Tarifs — texte'); ?><?php self::input('settings[general][tariff_title_bg_color]', $g['tariff_title_bg_color'] ?? '#ffffff', 'Titre Tarifs — fond', 'color'); ?><label class="htp-field"><span>Titre Tarifs — fond transparent</span><span><?php self::checkbox('settings[general][tariff_title_bg_transparent]', $g['tariff_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label><?php self::optional_color_input('settings[general][payment_title_color]', $g['payment_title_color'] ?? '', 'Moyens de paiement — titre'); ?><?php self::input('settings[general][payment_title_bg_color]', $g['payment_title_bg_color'] ?? '#ffffff', 'Moyens de paiement — fond du titre', 'color'); ?><label class="htp-field"><span>Moyens de paiement — fond du titre transparent</span><span><?php self::checkbox('settings[general][payment_title_bg_transparent]', $g['payment_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label><?php self::input('settings[general][tab_bg_color]', $g['tab_bg_color'] ?? '#006757', 'Onglets — fond', 'color'); ?><?php self::input('settings[general][tab_text_color]', $g['tab_text_color'] ?? '#ffffff', 'Onglets — texte', 'color'); ?><?php self::input('settings[general][tab_active_bg_color]', $g['tab_active_bg_color'] ?? '#e7c55b', 'Onglet actif — fond', 'color'); ?><?php self::input('settings[general][tab_active_text_color]', $g['tab_active_text_color'] ?? '#27342f', 'Onglet actif — texte', 'color'); ?><?php self::optional_color_input('settings[general][panel_text_color]', $g['panel_text_color'] ?? '', 'Panneaux — texte'); ?><?php self::optional_color_input('settings[general][panel_border_color]', $g['panel_border_color'] ?? '', 'Panneaux — bordure'); ?><label class="htp-field"><span>Bordure des panneaux</span><span><?php self::checkbox('settings[general][panel_border_enabled]', $g['panel_border_enabled'] ?? '0', 'Afficher'); ?></span></label><?php self::input('settings[general][panel_bg_color]', $g['panel_bg_color'] ?? '#ffffff', 'Panneaux — fond', 'color'); ?><label class="htp-field"><span>Panneaux — fond transparent</span><span><?php self::checkbox('settings[general][panel_bg_transparent]', $g['panel_bg_transparent'] ?? '1', 'Transparent'); ?></span></label><?php self::optional_color_input('settings[general][price_color]', $g['price_color'] ?? '', 'Prix — couleur par défaut'); ?><?php self::optional_color_input('settings[general][tariff_note_text_color]', $g['tariff_note_text_color'] ?? '', 'Note tarifs réduits — texte'); ?><?php self::optional_color_input('settings[general][tariff_note_border_color]', $g['tariff_note_border_color'] ?? '', 'Note tarifs réduits — bordure'); ?><?php self::input('settings[general][primary_button_bg_color]', $g['primary_button_bg_color'] ?? '#ef7b5b', 'Bouton Acheter — fond', 'color'); ?><?php self::input('settings[general][primary_button_text_color]', $g['primary_button_text_color'] ?? '#ffffff', 'Bouton Acheter — texte', 'color'); ?></div></div></details></section><?php
    }

    private static function print_section($tariffs) {
        $print = isset($tariffs['print']) && is_array($tariffs['print']) ? $tariffs['print'] : array(); ?>
        <section class="htp-card htp-1175-card"><details class="htp-advanced"><summary>Impression / PDF des tarifs</summary><div class="htp-advanced-content"><p class="description">Le document utilise les tarifs visiteurs publiés de l’année sélectionnée.</p><div class="htp-grid htp-grid-2"><label class="htp-field"><span>Téléchargement PDF</span><span><?php self::checkbox('settings[tariffs][print][pdf_enabled]', $print['pdf_enabled'] ?? '1', 'Afficher le lien Télécharger les tarifs en PDF'); ?></span></label><?php self::translated_input('settings[tariffs][print][title]', $print['title'] ?? array('fr'=>'','en'=>'','de'=>''), 'Titre du document'); ?><?php self::translated_textarea('settings[tariffs][print][footer]', $print['footer'] ?? array('fr'=>'','en'=>'','de'=>''), 'Texte en bas du document'); ?><label class="htp-field"><span>Date de génération</span><span><?php self::checkbox('settings[tariffs][print][show_generation_date]', $print['show_generation_date'] ?? '1', 'Afficher la date de génération'); ?></span></label><?php self::select('settings[tariffs][print][orientation]', $print['orientation'] ?? 'portrait', 'Format du PDF', array('portrait'=>'A4 portrait','landscape'=>'A4 paysage')); ?></div></div></details></section><?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        if ($year === '' || !isset($all['seasons'][$year])) {
            echo '<div class="wrap"><h1>Tarifs visiteurs</h1><div class="notice notice-warning"><p>Aucune saison disponible.</p></div></div>';
            return;
        }
        $settings = Parcs_HT_Defaults::settings($year);
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        $g = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $content_url = class_exists('Parcs_HT_Public_Content') ? add_query_arg(array('page'=>Parcs_HT_Public_Content::PAGE), admin_url('admin.php')) : '';
        ?>
        <div class="wrap htp-admin htp-1175-tariffs" id="htp-tariffs">
            <div class="htp-1175-title"><div><h1>Tarifs visiteurs</h1><p class="description">Individuels et Tarifs réduits sont administrés ici. Les tarifs Groupes restent dans leur espace dédié et ne sont jamais enregistrés par cette page.</p></div><span>Interface 1.17.5</span></div>
            <?php if (class_exists('Parcs_HT_Admin_Navigation') && method_exists('Parcs_HT_Admin_Navigation', 'render_year_context')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year); ?>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les tarifs visiteurs <?php echo esc_html($year); ?> ont été enregistrés.</p></div><?php endif; ?>
            <div class="htp-1175-rule"><strong>Règles protégées :</strong> moyens de paiement visiteurs séparés Sur place / En ligne, achat en ligne limité à Individuels, et aucune donnée Groupes modifiée.</div>
            <?php if ($content_url !== '') : ?><p class="htp-1175-content-link"><a class="button" href="<?php echo esc_url($content_url); ?>">Ouvrir Contenus & traductions</a> <span class="description">pour les textes éditoriaux FR / EN / DE qui ne sont pas des données tarifaires.</span></p><?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-htp-retail-tariffs-form>
                <input type="hidden" name="action" value="parcs_ht_save">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <input type="hidden" name="htp_active_tab" value="htp-tariffs">
                <input type="hidden" name="htp_save_active" value="1">
                <input type="hidden" name="parcs_ht_retail_workspace" value="1">
                <input type="hidden" name="settings[_complete][tariffs]" value="1">
                <?php wp_nonce_field('parcs_ht_save'); ?>

                <?php self::category('individual', 'Individuels', 'Tarifs visiteurs classiques et offres spéciales. Le bouton Acheter vos billets reste réservé à cette catégorie.', $tariffs); ?>
                <?php self::category('reduced', 'Tarifs réduits', 'Tarifs avec justificatif. Aucun bouton d’achat en ligne visiteurs n’est ajouté à cette catégorie.', $tariffs); ?>

                <section class="htp-card htp-1175-card"><h2>Précisions sur les tarifs réduits</h2><?php self::translated_textarea('settings[tariffs][notes]', $tariffs['notes'] ?? array('fr'=>'','en'=>'','de'=>''), 'Justificatifs et précisions FR / EN / DE'); ?></section>
                <?php self::payment_section($tariffs); ?>
                <?php self::print_section($tariffs); ?>
                <?php self::appearance_section($g); ?>

                <div class="htp-1175-save"><button type="submit" class="button button-primary button-large">Enregistrer les tarifs visiteurs</button></div>
            </form>
        </div>
        <?php
    }
}
