<?php

if (!defined('ABSPATH')) { exit; }

/** Administration dédiée aux tarifs et à l'affichage public Groupes. */
final class Parcs_HT_Admin_Group_Tariffs {
    const PAGE = 'parcs-ht-groups';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'route'), 0);
        add_action('admin_menu', array(__CLASS__, 'menu'), 61);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 65);
        add_action('admin_post_parcs_ht_save_group_display_1176', array(__CLASS__, 'save_display'));
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'preserve_non_group_tariffs'), 69, 3);
    }

    private static function requested_year() {
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
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

    private static function redirect($args) {
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    /** Les anciens liens internes « htp-tariffs-groups » arrivent désormais sur l'écran natif. */
    public static function route() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE || $tab !== 'htp-tariffs-groups') return;
        $args = array('page'=>self::PAGE);
        $year = self::requested_year();
        if ($year !== '') $args['season'] = $year;
        foreach (array('updated','preserved') as $notice) if (isset($_GET[$notice])) $args[$notice] = '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur visuel uniquement.
        self::redirect($args);
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        remove_submenu_page(Parcs_HT_Admin::PAGE, self::PAGE);
        add_submenu_page(Parcs_HT_Admin::PAGE, 'Groupes', 'Groupes', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    public static function assets() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
        if ($page !== self::PAGE) return;
        $year = self::selected_year();
        $settings = Parcs_HT_Defaults::settings($year);
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();

        wp_enqueue_style('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-admin-groups-1176', PARCS_HT_URL . 'assets/admin-groups-1176.css', array('parcs-ht-admin'), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-preview-engine', PARCS_HT_URL . 'assets/frontend.js', array(), PARCS_HT_VERSION, true);
        wp_enqueue_script('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.js', array('parcs-ht-preview-engine','jquery-ui-sortable'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin', 'window.ParcsHTAdmin=' . wp_json_encode(array('language'=>'fr')) . ';', 'before');
        wp_enqueue_script('parcs-ht-tariff-seasons-admin', PARCS_HT_URL . 'assets/tariff-seasons-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-tariff-seasons-admin', 'window.ParcsHTTariffSeasonAdmin=' . wp_json_encode(array('tariffs'=>$tariffs)) . ';', 'before');
    }

    /**
     * Une sauvegarde Groupes ne doit modifier que tariffs.groups et columns.groups.
     * Tout le reste (Individuels, Réduits, paiements visiteurs, PDF et clés historiques)
     * est repris de la valeur précédente avant les filtres métiers suivants.
     */
    public static function preserve_non_group_tariffs($new_value, $old_value, $option) {
        unset($option);
        if (!is_admin() || !is_array($new_value) || !is_array($old_value) || !current_user_can('manage_options')) return $new_value;
        $workspace = isset($_POST['parcs_ht_group_workspace']) ? sanitize_text_field(wp_unslash($_POST['parcs_ht_group_workspace'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce canonique vérifié ci-dessous.
        if ($workspace !== '1') return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) return $new_value;

        $old_global = isset($old_value['tariffs']) && is_array($old_value['tariffs']) ? $old_value['tariffs'] : array();
        $old_season = isset($old_value['seasons'][$year]['tariffs']) && is_array($old_value['seasons'][$year]['tariffs']) ? $old_value['seasons'][$year]['tariffs'] : $old_global;
        if (!isset($new_value['tariffs']) || !is_array($new_value['tariffs'])) $new_value['tariffs'] = array();
        self::keep_only_new_group_branch($new_value['tariffs'], $old_global);
        if (isset($new_value['seasons'][$year]) && is_array($new_value['seasons'][$year])) {
            if (!isset($new_value['seasons'][$year]['tariffs']) || !is_array($new_value['seasons'][$year]['tariffs'])) $new_value['seasons'][$year]['tariffs'] = array();
            self::keep_only_new_group_branch($new_value['seasons'][$year]['tariffs'], $old_season);
        }
        return $new_value;
    }

    private static function keep_only_new_group_branch(&$target, $source) {
        $target = is_array($target) ? $target : array();
        $new_rows = isset($target['groups']) && is_array($target['groups']) ? $target['groups'] : array();
        $new_columns = isset($target['columns']['groups']) && is_array($target['columns']['groups']) ? $target['columns']['groups'] : array();
        $target = is_array($source) ? $source : array();
        $target['groups'] = $new_rows;
        if (!isset($target['columns']) || !is_array($target['columns'])) $target['columns'] = array();
        $target['columns']['groups'] = $new_columns;
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

    private static function column_row($index, $column) {
        $column = wp_parse_args(is_array($column) ? $column : array(), array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
        $raw_id = (string)($column['id'] ?? '');
        $id = $raw_id === '__COLID__' ? '__COLID__' : sanitize_key($raw_id);
        if ($id === '') $id = 'price';
        $base = 'settings[tariffs][columns][groups][' . $index . ']'; ?>
        <div class="htp-tariff-column-row" data-htp-tariff-column data-col-id="<?php echo esc_attr($id); ?>">
            <button type="button" class="button button-small htp-sort-handle htp-sort-handle-column" title="Glisser pour déplacer" aria-label="Déplacer la colonne">↕</button>
            <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" data-htp-column-id-input>
            <div class="htp-tariff-column-label"><?php self::translated_input($base . '[label]', $column['label'], 'Nom de la colonne'); ?></div>
            <div class="htp-order-buttons"><button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button><button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button></div>
            <button type="button" class="button-link-delete" data-htp-remove-column>Supprimer</button>
        </div><?php
    }

    private static function tariff_row($index, $row, $columns) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'id'=>'','enabled'=>'1','label'=>array('fr'=>'','en'=>'','de'=>''),'detail'=>array('fr'=>'','en'=>'','de'=>''),'subtitle'=>array('fr'=>'','en'=>'','de'=>''),'note'=>array('fr'=>'','en'=>'','de'=>''),'price'=>'','cells'=>array(),
            'row_type'=>'standard','special_badge'=>array('fr'=>'','en'=>'','de'=>''),'valid_from'=>'','valid_to'=>'','display_from'=>'','display_to'=>'','sale_channel'=>'both','purchase_url'=>array('fr'=>'','en'=>'','de'=>''),'show_special_dot'=>'1',
            'label_color'=>'','detail_color'=>'','subtitle_color'=>'','note_color'=>'','price_color'=>'','row_bg_color'=>'#ffffff','row_bg_transparent'=>'1','row_border_color'=>''
        ));
        if (!$columns) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
        $base = 'settings[tariffs][groups][' . $index . ']';
        $special = (string)$row['row_type'] === 'special'; ?>
        <div class="htp-repeat-row htp-tariff-row" data-htp-tariff-row>
            <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr((string)$row['id']); ?>" data-htp-tariff-row-id-input>
            <div class="htp-row-head"><button type="button" class="button button-small htp-sort-handle htp-sort-handle-row" title="Glisser pour déplacer" aria-label="Déplacer la ligne">↕</button><strong>Ligne tarifaire groupe</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><div class="htp-order-buttons"><button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button><button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button></div><button type="button" class="button button-small" data-htp-duplicate-tariff-row>Dupliquer</button><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-3"><?php self::translated_input($base . '[label]', $row['label'], 'Libellé'); ?><?php self::translated_input($base . '[subtitle]', $row['subtitle'] ?: $row['detail'], 'Sous-titre / précision'); ?><?php self::translated_textarea($base . '[note]', $row['note'], 'Texte secondaire facultatif'); ?><?php self::select($base . '[row_type]', $row['row_type'], 'Type de ligne', array('standard'=>'Tarif classique','special'=>'Offre / billet spécial')); ?></div>
            <div class="htp-tariff-cell-grid" data-htp-tariff-cells><?php foreach ($columns as $column) : $col_id = sanitize_key((string)($column['id'] ?? '')); if ($col_id === '') continue; $cell = isset($row['cells'][$col_id]) && is_array($row['cells'][$col_id]) ? $row['cells'][$col_id] : array(); $value = isset($cell['value']) ? $cell['value'] : ($col_id === 'price' ? $row['price'] : ''); $old = $cell['old_value'] ?? ''; $label = (string)($column['label']['fr'] ?? 'Prix'); ?><div class="htp-tariff-cell-fields" data-htp-tariff-cell data-col-id="<?php echo esc_attr($col_id); ?>"><strong class="htp-tariff-cell-title"><?php echo esc_html($label ?: 'Prix'); ?></strong><?php self::input($base . '[cells][' . $col_id . '][value]', $value, 'Prix / valeur'); ?><div data-htp-old-price-field <?php echo $special ? '' : 'hidden'; ?>><?php self::input($base . '[cells][' . $col_id . '][old_value]', $old, 'Ancien prix à barrer (facultatif)'); ?></div></div><?php endforeach; ?></div>
            <details class="htp-row-details"><summary>Options avancées de cette ligne</summary><div class="htp-details-content"><div class="htp-grid htp-grid-3"><label class="htp-field"><span>Repère visuel</span><span><?php self::checkbox($base . '[show_special_dot]', $row['show_special_dot'], 'Afficher le repère des offres'); ?></span></label><?php self::translated_input($base . '[special_badge]', $row['special_badge'], 'Badge facultatif'); ?><?php self::select($base . '[sale_channel]', $row['sale_channel'], 'Canal historique', array('both'=>'En ligne + sur place','online'=>'En ligne uniquement','onsite'=>'Sur place uniquement')); ?><?php self::input($base . '[valid_from]', $row['valid_from'], 'Valable à partir du', 'date'); ?><?php self::input($base . '[valid_to]', $row['valid_to'], 'Valable jusqu’au', 'date'); ?><?php self::input($base . '[display_from]', $row['display_from'], 'Début d’affichage', 'date'); ?><?php self::input($base . '[display_to]', $row['display_to'], 'Fin d’affichage', 'date'); ?><?php self::translated_input($base . '[purchase_url]', $row['purchase_url'], 'Lien historique facultatif', 'url'); ?></div><p class="description">Ces champs historiques restent conservés pour compatibilité. Le portail Groupes n’utilise jamais le bouton visiteurs « Acheter vos billets ».</p></div></details>
        </div><?php
    }

    private static function tariff_grid($tariffs) {
        $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? $tariffs['columns']['groups'] : array();
        if (!$columns) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))); ?>
        <section class="htp-1176-card" data-htp-tariff-group="groups">
            <div class="htp-1176-card-head"><div><h2>Grille tarifaire Groupes</h2><p class="description">C’est la grille canonique utilisée par le portail Groupes et, lorsque les tarifs visiteurs de la même année sont publiés, par le tableau public partagé.</p></div><span class="htp-1176-count"><?php echo esc_html((string)count((array)($tariffs['groups'] ?? array()))); ?> ligne(s)</span></div>
            <div class="htp-tariff-columns-manager"><div class="htp-tariff-columns-title"><strong>Colonnes de prix</strong><span class="description">Les identifiants restent permanents afin de ne pas casser la liaison avec les devis.</span></div><div class="htp-tariff-columns-list" data-htp-tariff-columns><?php foreach ($columns as $index=>$column) self::column_row($index, $column); ?></div><button type="button" class="button" data-htp-add-column>Ajouter une colonne</button><script type="text/html" class="htp-template-tariff-column"><?php self::column_row('__COLINDEX__', array('id'=>'__COLID__','label'=>array('fr'=>'Nouvelle colonne','en'=>'New column','de'=>'Neue Spalte'))); ?></script></div>
            <div class="htp-repeater htp-tariff-repeater" data-template="htp-template-tariff-groups" data-htp-tariff-row-repeater><div class="htp-repeater-rows"><?php foreach ((array)($tariffs['groups'] ?? array()) as $index=>$row) self::tariff_row($index, $row, $columns); ?></div><button type="button" class="button htp-add-row">Ajouter une ligne groupe</button></div><script type="text/html" id="htp-template-tariff-groups"><?php self::tariff_row('__INDEX__', array(), $columns); ?></script>
        </section><?php
    }

    private static function payment_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array('enabled'=>'1','icon'=>'other','label'=>array('fr'=>'','en'=>'','de'=>'')));
        $base = 'group_display[payment_methods][' . $index . ']'; ?>
        <div class="htp-repeat-row"><div class="htp-row-head"><strong>Moyen de paiement groupe</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div><div class="htp-grid htp-grid-2"><?php self::select($base . '[icon]', $row['icon'], 'Pictogramme', array('card'=>'Carte','cash'=>'Espèces','cheque'=>'Chèque','document'=>'Document / voucher','chorus'=>'Chorus Pro','bank'=>'Virement','online'=>'En ligne','other'=>'Autre')); ?><?php self::translated_input($base . '[label]', $row['label'], 'Nom affiché'); ?></div></div><?php
    }

    private static function info_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array('enabled'=>'1','title'=>array('fr'=>'','en'=>'','de'=>''),'text'=>array('fr'=>'','en'=>'','de'=>'')));
        $base = 'group_display[info_blocks][' . $index . ']'; ?>
        <div class="htp-repeat-row"><div class="htp-row-head"><strong>Bloc d’information</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div><div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[title]', $row['title'], 'Titre'); ?><?php self::translated_textarea($base . '[text]', $row['text'], 'Texte'); ?></div></div><?php
    }

    private static function appearance($display, $rows) {
        $a = isset($display['appearance']) && is_array($display['appearance']) ? $display['appearance'] : array(); ?>
        <details class="htp-advanced"><summary>Apparence propre au bloc Groupes</summary><div class="htp-advanced-content"><p class="description">Réglages historiques conservés et indépendants des tarifs visiteurs. Le raccordement au socle global reste progressif.</p><div class="htp-grid htp-grid-3">
            <?php self::optional_color_input('group_display[appearance][tariff_title_color]', $a['tariff_title_color'] ?? '', 'Titre tarifs — texte'); ?>
            <?php self::input('group_display[appearance][tariff_title_bg_color]', $a['tariff_title_bg_color'] ?? '#ffffff', 'Titre tarifs — fond', 'color'); ?>
            <label class="htp-field"><span>Titre tarifs — fond transparent</span><span><?php self::checkbox('group_display[appearance][tariff_title_bg_transparent]', $a['tariff_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
            <?php self::optional_color_input('group_display[appearance][payment_title_color]', $a['payment_title_color'] ?? '', 'Paiement — titre'); ?>
            <?php self::input('group_display[appearance][payment_title_bg_color]', $a['payment_title_bg_color'] ?? '#ffffff', 'Paiement — fond du titre', 'color'); ?>
            <label class="htp-field"><span>Paiement — fond transparent</span><span><?php self::checkbox('group_display[appearance][payment_title_bg_transparent]', $a['payment_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
            <?php self::input('group_display[appearance][payment_item_bg_color]', $a['payment_item_bg_color'] ?? '#006757', 'Moyens de paiement — fond', 'color'); ?>
            <?php self::input('group_display[appearance][payment_item_text_color]', $a['payment_item_text_color'] ?? '#ffffff', 'Moyens de paiement — texte', 'color'); ?>
            <?php self::input('group_display[appearance][payment_icon_color]', $a['payment_icon_color'] ?? '#ffffff', 'Moyens de paiement — icône', 'color'); ?>
            <?php self::optional_color_input('group_display[appearance][payment_border_color]', $a['payment_border_color'] ?? '', 'Moyens de paiement — bordure'); ?>
            <label class="htp-field"><span>Bordure moyens de paiement</span><span><?php self::checkbox('group_display[appearance][payment_border_enabled]', $a['payment_border_enabled'] ?? '0', 'Afficher'); ?></span></label>
            <?php self::optional_color_input('group_display[appearance][panel_text_color]', $a['panel_text_color'] ?? '', 'Panneau — texte'); ?>
            <?php self::optional_color_input('group_display[appearance][panel_border_color]', $a['panel_border_color'] ?? '', 'Panneau — bordure'); ?>
            <label class="htp-field"><span>Bordure du panneau</span><span><?php self::checkbox('group_display[appearance][panel_border_enabled]', $a['panel_border_enabled'] ?? '0', 'Afficher'); ?></span></label>
            <?php self::input('group_display[appearance][panel_bg_color]', $a['panel_bg_color'] ?? '#ffffff', 'Panneau — fond', 'color'); ?>
            <label class="htp-field"><span>Fond du panneau transparent</span><span><?php self::checkbox('group_display[appearance][panel_bg_transparent]', $a['panel_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
            <?php self::optional_color_input('group_display[appearance][price_color]', $a['price_color'] ?? '', 'Prix — couleur'); ?>
            <?php self::optional_color_input('group_display[appearance][groups_note_text_color]', $a['groups_note_text_color'] ?? '', 'Informations — texte'); ?>
            <?php self::optional_color_input('group_display[appearance][groups_note_border_color]', $a['groups_note_border_color'] ?? '', 'Informations — bordure'); ?>
            <?php self::input('group_display[appearance][button_bg_color]', $a['button_bg_color'] ?? '#006757', 'Bouton devis — fond', 'color'); ?>
            <?php self::input('group_display[appearance][button_text_color]', $a['button_text_color'] ?? '#ffffff', 'Bouton devis — texte', 'color'); ?>
        </div>
        <?php $styles = isset($display['row_styles']) && is_array($display['row_styles']) ? $display['row_styles'] : array(); $styled = 0; foreach ((array)$rows as $row) : if (!is_array($row)) continue; $id = sanitize_key((string)($row['id'] ?? '')); if (!preg_match('/^tariff_row_\d{6,}$/', $id)) continue; $styled++; $style = isset($styles[$id]) && is_array($styles[$id]) ? $styles[$id] : array(); $label = trim((string)($row['label']['fr'] ?? '')) ?: $id; ?>
            <details class="htp-1176-row-style"><summary><?php echo esc_html($label); ?> — apparence de la ligne</summary><div class="htp-grid htp-grid-3"><?php self::optional_color_input('group_display[row_styles][' . $id . '][label_color]', $style['label_color'] ?? '', 'Libellé'); ?><?php self::optional_color_input('group_display[row_styles][' . $id . '][subtitle_color]', $style['subtitle_color'] ?? '', 'Sous-titre'); ?><?php self::optional_color_input('group_display[row_styles][' . $id . '][note_color]', $style['note_color'] ?? '', 'Note'); ?><?php self::optional_color_input('group_display[row_styles][' . $id . '][price_color]', $style['price_color'] ?? '', 'Prix'); ?><?php self::input('group_display[row_styles][' . $id . '][row_bg_color]', $style['row_bg_color'] ?? '#ffffff', 'Fond', 'color'); ?><label class="htp-field"><span>Fond transparent</span><span><?php self::checkbox('group_display[row_styles][' . $id . '][row_bg_transparent]', $style['row_bg_transparent'] ?? '1', 'Transparent'); ?></span></label><?php self::optional_color_input('group_display[row_styles][' . $id . '][row_border_color]', $style['row_border_color'] ?? '', 'Bordure'); ?></div></details>
        <?php endforeach; if ($styled === 0) : ?><p class="description">Enregistrez d’abord la grille tarifaire pour attribuer ses identifiants permanents avant de personnaliser chaque ligne.</p><?php endif; ?></div></details><?php
    }

    private static function family_nav($year) {
        $quote = add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'tab'=>'htp-quote','season'=>$year), admin_url('admin.php'));
        $guides = class_exists('Parcs_HT_Pedagogical_Guides') ? add_query_arg(array('page'=>Parcs_HT_Pedagogical_Guides::PAGE), admin_url('admin.php')) : add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'tab'=>'htp-guides','season'=>$year), admin_url('admin.php'));
        echo '<nav class="htp-1176-family" aria-label="Rubrique Groupes"><a class="button button-primary" href="' . esc_url(add_query_arg(array('page'=>self::PAGE,'season'=>$year), admin_url('admin.php'))) . '">Tarifs groupes</a><a class="button" href="' . esc_url($quote) . '">Devis groupes</a><a class="button" href="' . esc_url($guides) . '">Guides pédagogiques</a></nav>';
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        if ($year === '' || !isset($all['seasons'][$year])) {
            echo '<div class="wrap"><h1>Groupes</h1><div class="notice notice-warning"><p>Aucune saison disponible.</p></div></div>';
            return;
        }
        $settings = Parcs_HT_Defaults::settings($year);
        $tariffs = isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        $display = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::settings($year) : array();
        $season = isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
        $effective = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::module_visible($year, 'group_tariffs_visible', (string)($display['published'] ?? '0') === '1') : (string)($season['group_tariffs_visible'] ?? ($display['published'] ?? '0')) === '1';
        $schedule_state = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::scheduled_state($year) : 'manual';
        $grid_ready = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::group_tariff_grid_ready($year) : !empty($tariffs['groups']);
        $quote_ok = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::quote_binding_valid($year) : false;
        $overview = class_exists('Parcs_HT_Admin_Overview') ? add_query_arg(array('page'=>Parcs_HT_Admin_Overview::PAGE,'season'=>$year), admin_url('admin.php')) : admin_url('admin.php');
        ?>
        <div class="wrap htp-admin htp-1176-groups" id="htp-groups-tariffs">
            <div class="htp-1176-title"><div><h1>Groupes</h1><p class="description">Tarifs groupes <?php echo esc_html($year); ?> : une seule grille canonique, avec un affichage public et des moyens de paiement propres aux Groupes.</p></div><span>Interface 1.17.6</span></div>
            <?php self::family_nav($year); ?>
            <?php if (class_exists('Parcs_HT_Admin_Navigation') && method_exists('Parcs_HT_Admin_Navigation', 'render_year_context')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year); ?>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La grille Groupes <?php echo esc_html($year); ?> a été enregistrée.</p></div><?php endif; ?>
            <?php if (isset($_GET['display_saved'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>L’affichage public Groupes <?php echo esc_html($year); ?> a été enregistré.</p></div><?php endif; ?>

            <section class="htp-1176-status" aria-label="État Groupes <?php echo esc_attr($year); ?>">
                <div><strong>Publication effective</strong><span class="<?php echo $effective ? 'is-ok' : 'is-off'; ?>"><?php echo $effective ? 'Visible' : 'Masquée'; ?></span><small><?php echo $schedule_state === 'on' ? 'activée par la date d’apparition annuelle' : ($schedule_state === 'off' ? 'désactivée par la date de disparition annuelle' : 'pilotée par l’interrupteur annuel'); ?></small></div>
                <div><strong>Grille tarifaire</strong><span class="<?php echo $grid_ready ? 'is-ok' : 'is-off'; ?>"><?php echo $grid_ready ? 'Prête' : 'À compléter'; ?></span><small>source unique du portail et du rendu partagé</small></div>
                <div><strong>Liaison devis</strong><span class="<?php echo $quote_ok ? 'is-ok' : 'is-warn'; ?>"><?php echo $quote_ok ? 'Valide' : 'À vérifier'; ?></span><small>les devis restent gérés dans leur écran dédié</small></div>
            </section>
            <p class="htp-1176-annual"><a class="button" href="<?php echo esc_url($overview); ?>">Gérer l’activation annuelle</a> <span class="description">La publication annuelle reste la même clé canonique que dans la Vue d’ensemble ; aucun second interrupteur n’est créé ici.</span></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-htp-group-tariffs-form>
                <input type="hidden" name="action" value="parcs_ht_save">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <input type="hidden" name="htp_active_tab" value="htp-tariffs-groups">
                <input type="hidden" name="htp_save_active" value="1">
                <input type="hidden" name="parcs_ht_group_workspace" value="1">
                <input type="hidden" name="settings[_complete][tariffs]" value="1">
                <?php wp_nonce_field('parcs_ht_save'); ?>
                <?php self::tariff_grid($tariffs); ?>
                <div class="htp-1176-save"><?php submit_button('Enregistrer la grille Groupes', 'primary', 'submit', false); ?><span class="description">Aucune donnée Individuels / Réduits n’est enregistrée par ce formulaire.</span></div>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-1176-display-form">
                <input type="hidden" name="action" value="parcs_ht_save_group_display_1176">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_group_display_1176_' . $year); ?>
                <section class="htp-1176-card"><div class="htp-1176-card-head"><div><h2>Affichage public Groupes</h2><p class="description">Titre, introduction, moyens de paiement, informations et bouton de devis restent propres aux Groupes.</p></div></div>
                    <div class="htp-check-list"><?php self::checkbox('group_display[show_heading]', $display['show_heading'] ?? '1', 'Afficher le titre du bloc'); ?><?php self::checkbox('group_display[show_payment_methods]', $display['show_payment_methods'] ?? '0', 'Afficher les moyens de paiement'); ?><?php self::checkbox('group_display[show_info_blocks]', $display['show_info_blocks'] ?? '0', 'Afficher les blocs d’information'); ?><?php self::checkbox('group_display[show_quote_button]', $display['show_quote_button'] ?? '1', 'Afficher le bouton de devis'); ?></div>
                    <div class="htp-grid htp-grid-2"><?php self::translated_input('group_display[title]', $display['title'] ?? array(), 'Titre du bloc (vide = automatique)'); ?><?php self::translated_textarea('group_display[intro]', $display['intro'] ?? array(), 'Introduction facultative'); ?><?php self::translated_input('group_display[payment_title]', $display['payment_title'] ?? array(), 'Titre des moyens de paiement'); ?><?php self::translated_input('group_display[button_label]', $display['button_label'] ?? array(), 'Texte du bouton devis'); ?><?php self::translated_input('group_display[button_url]', $display['button_url'] ?? array(), 'Lien du bouton devis', 'url'); ?></div>

                    <details class="htp-advanced" open><summary>Moyens de paiement Groupes</summary><div class="htp-advanced-content"><p class="description">Aucun moyen de paiement visiteurs n’est réutilisé automatiquement.</p><div class="htp-repeater" data-template="htp-template-group-payment"><div class="htp-repeater-rows"><?php foreach ((array)($display['payment_methods'] ?? array()) as $index=>$row) self::payment_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter un moyen de paiement</button></div><script type="text/html" id="htp-template-group-payment"><?php self::payment_row('__INDEX__', array()); ?></script></div></details>
                    <details class="htp-advanced" open><summary>Informations pratiques sous les tarifs</summary><div class="htp-advanced-content"><div class="htp-repeater" data-template="htp-template-group-info"><div class="htp-repeater-rows"><?php foreach ((array)($display['info_blocks'] ?? array()) as $index=>$row) self::info_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter un bloc d’information</button></div><script type="text/html" id="htp-template-group-info"><?php self::info_row('__INDEX__', array()); ?></script></div></details>
                    <details class="htp-advanced"><summary>Message pour une année future</summary><div class="htp-advanced-content"><div class="htp-check-list"><?php self::checkbox('group_display[show_future_notice]', $display['show_future_notice'] ?? '1', 'Afficher le message'); ?></div><div class="htp-grid htp-grid-2"><?php self::input('group_display[future_year]', $display['future_year'] ?? ((int)$year + 1), 'Année future annoncée', 'number'); ?><?php self::translated_textarea('group_display[future_notice]', $display['future_notice'] ?? array(), 'Message (vide = automatique)'); ?></div></div></details>
                    <?php self::appearance($display, $tariffs['groups'] ?? array()); ?>
                    <p class="htp-1176-display-save"><?php submit_button('Enregistrer l’affichage Groupes', 'secondary', 'submit', false); ?></p>
                </section>
            </form>
        </div>
        <?php
    }

    private static function clean_translations($value, $textarea = false, $url = false) {
        $value = is_array($value) ? $value : array();
        $out = array('fr'=>'','en'=>'','de'=>'');
        foreach ($out as $lang=>$unused) {
            $raw = isset($value[$lang]) ? wp_unslash((string)$value[$lang]) : '';
            $out[$lang] = $url ? esc_url_raw($raw) : ($textarea ? sanitize_textarea_field($raw) : sanitize_text_field($raw));
        }
        return $out;
    }

    public static function save_display() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_save_group_display_1176_' . $year);
        if (!class_exists('Parcs_HT_Group_Tariff_Settings')) wp_die('Module Groupes indisponible.');
        $all = Parcs_HT_Defaults::all_settings();
        if (empty($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_die('Cette saison n’existe pas.');
        $current = Parcs_HT_Group_Tariff_Settings::settings($year);
        $posted = isset($_POST['group_display']) && is_array($_POST['group_display']) ? wp_unslash($_POST['group_display']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- chaque champ est normalisé ci-dessous puis nettoyé une seconde fois par le modèle.

        $raw = array(
            'published'=>(string)($current['published'] ?? '0'),
            'display_from'=>(string)($current['display_from'] ?? ''),
            'show_heading'=>isset($posted['show_heading']) && (string)$posted['show_heading'] === '1' ? '1' : '0',
            'show_future_notice'=>isset($posted['show_future_notice']) && (string)$posted['show_future_notice'] === '1' ? '1' : '0',
            'future_year'=>isset($posted['future_year']) ? sanitize_text_field((string)$posted['future_year']) : (string)((int)$year + 1),
            'show_quote_button'=>isset($posted['show_quote_button']) && (string)$posted['show_quote_button'] === '1' ? '1' : '0',
            'show_payment_methods'=>isset($posted['show_payment_methods']) && (string)$posted['show_payment_methods'] === '1' ? '1' : '0',
            'show_info_blocks'=>isset($posted['show_info_blocks']) && (string)$posted['show_info_blocks'] === '1' ? '1' : '0',
            'title'=>self::clean_translations($posted['title'] ?? array()),
            'intro'=>self::clean_translations($posted['intro'] ?? array(), true),
            'future_notice'=>self::clean_translations($posted['future_notice'] ?? array(), true),
            'button_label'=>self::clean_translations($posted['button_label'] ?? array()),
            'button_url'=>self::clean_translations($posted['button_url'] ?? array(), false, true),
            'payment_title'=>self::clean_translations($posted['payment_title'] ?? array()),
            'payment_methods'=>array(),
            'info_blocks'=>array(),
            'appearance'=>isset($posted['appearance']) && is_array($posted['appearance']) ? map_deep($posted['appearance'], 'sanitize_text_field') : array(),
            'row_styles'=>isset($posted['row_styles']) && is_array($posted['row_styles']) ? map_deep($posted['row_styles'], 'sanitize_text_field') : array(),
        );
        foreach ((array)($posted['payment_methods'] ?? array()) as $row) {
            if (!is_array($row)) continue;
            $raw['payment_methods'][] = array(
                'enabled'=>isset($row['enabled']) && (string)$row['enabled'] === '0' ? '0' : '1',
                'icon'=>isset($row['icon']) ? sanitize_key((string)$row['icon']) : 'other',
                'label'=>self::clean_translations($row['label'] ?? array()),
            );
        }
        foreach ((array)($posted['info_blocks'] ?? array()) as $row) {
            if (!is_array($row)) continue;
            $raw['info_blocks'][] = array(
                'enabled'=>isset($row['enabled']) && (string)$row['enabled'] === '0' ? '0' : '1',
                'title'=>self::clean_translations($row['title'] ?? array()),
                'text'=>self::clean_translations($row['text'] ?? array(), true),
            );
        }
        if (!Parcs_HT_Group_Tariff_Settings::save($year, $raw)) wp_die('WordPress n’a pas confirmé l’enregistrement de l’affichage Groupes.');
        if (class_exists('Parcs_HT_Save_Integrity')) Parcs_HT_Save_Integrity::store_daily_snapshot($year, 'Affichage public des tarifs groupes');
        do_action('litespeed_purge_all');
        self::redirect(array('page'=>self::PAGE,'season'=>$year,'display_saved'=>'1'));
    }
}
