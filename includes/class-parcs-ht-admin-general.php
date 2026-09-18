<?php

if (!defined('ABSPATH')) { exit; }

/** Administration générale 1.17.1. */
final class Parcs_HT_Admin_General {
    const PAGE = 'parcs-ht-general';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 35);
        add_action('admin_init', array(__CLASS__, 'redirect_default_entry'), 10);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('admin_post_parcs_ht_save_global_appearance', array(__CLASS__, 'save_global_appearance'));
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Administration générale',
            'Administration générale',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    public static function redirect_default_entry() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        if ($page !== Parcs_HT_Admin::PAGE) return;
        if (isset($_GET['tab']) || isset($_GET['season']) || isset($_GET['advent_fragment'])) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation en lecture seule.
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE), admin_url('admin.php')));
        exit;
    }

    public static function assets($hook) {
        unset($hook);
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        if ($page !== self::PAGE) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('wp-color-picker', 'jQuery(function($){$(".htp-global-color").wpColorPicker({clear:true});});');
    }

    private static function selected_year($all) {
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'affichage uniquement.
        if ($requested !== '' && preg_match('/^20\d{2}$/', $requested) && isset($all['seasons'][$requested])) return $requested;
        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? '');
        if ($active !== '' && isset($all['seasons'][$active])) return $active;
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) return (string)$year;
        }
        return '';
    }

    private static function detailed_url($year, $tab = 'htp-general') {
        $args = array('page'=>Parcs_HT_Admin::PAGE, 'tab'=>$tab);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function overview_url($year = '') {
        $args = array('page'=>self::PAGE);
        if ($year !== '') $args['season'] = $year;
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function translated_value($value) {
        if (!is_array($value)) return trim((string)$value);
        foreach (array('fr','en','de') as $lang) if (!empty($value[$lang])) return trim((string)$value[$lang]);
        return '';
    }

    private static function automatic_state($year) {
        if (!class_exists('Parcs_HT_Public_Visibility')) return 'manual';
        $state = Parcs_HT_Public_Visibility::scheduled_state($year);
        return in_array($state, array('manual','on','off'), true) ? $state : 'manual';
    }

    private static function status_rows($year, $season) {
        $rows = array(
            'calendar_visible'=>'Calendrier public',
            'retail_tariffs_visible'=>'Tarifs visiteurs',
            'groups_schedule_visible'=>'Horaires groupes',
            'group_quotes_enabled'=>'Devis groupes',
            'group_tariffs_visible'=>'Tarifs groupes',
        );
        $automatic = self::automatic_state($year);
        $out = array();
        foreach ($rows as $key => $label) {
            $fallback = $key === 'calendar_visible' && (string)($season['published'] ?? '0') === '1';
            $manual = array_key_exists($key, $season) ? (string)$season[$key] === '1' : $fallback;
            $effective = $automatic === 'on' ? true : ($automatic === 'off' ? false : $manual);
            $out[] = array('label'=>$label, 'on'=>$effective, 'source'=>$automatic === 'manual' ? 'manuel' : 'automatique');
        }
        return $out;
    }

    private static function date_label($value) {
        $value = trim((string)$value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) return 'non définie';
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private static function color_field($name, $value, $label, $help = '') {
        ?>
        <label class="htp-global-field">
            <span><?php echo esc_html($label); ?></span>
            <input class="htp-global-color" type="text" name="appearance[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr((string)$value); ?>" autocomplete="off">
            <?php if ($help !== '') : ?><small><?php echo esc_html($help); ?></small><?php endif; ?>
        </label>
        <?php
    }

    private static function number_field($name, $value, $label, $min, $max, $suffix = 'px') {
        ?>
        <label class="htp-global-field">
            <span><?php echo esc_html($label); ?></span>
            <span class="htp-global-number"><input type="number" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" name="appearance[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr((string)$value); ?>"><em><?php echo esc_html($suffix); ?></em></span>
        </label>
        <?php
    }

    public static function save_global_appearance() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_global_appearance');
        if (!class_exists('Parcs_HT_Global_Appearance')) wp_die('Référentiel d’apparence indisponible.');

        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['general']) || !is_array($all['general'])) $all['general'] = array();
        $raw = isset($_POST['appearance']) && is_array($_POST['appearance']) ? wp_unslash($_POST['appearance']) : array();
        $all['general'] = Parcs_HT_Global_Appearance::merge_general($all['general'], $raw);
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        do_action('litespeed_purge_all');

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        $args = array('page'=>self::PAGE, 'appearance_updated'=>'1');
        if (preg_match('/^20\d{2}$/', $year)) $args['season'] = $year;
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private static function appearance_form($all, $year) {
        $g = Parcs_HT_Global_Appearance::general($all);
        $vars = Parcs_HT_Global_Appearance::css_variables($all);
        $preview_style = '';
        foreach ($vars as $name => $value) $preview_style .= $name . ':' . $value . ';';
        ?>
        <section class="htp-general-card htp-global-appearance-card">
            <div class="htp-general-card-head">
                <div>
                    <h2>Apparence globale</h2>
                    <p>Cette apparence devient la référence commune. Les catégories seront reliées à ce socle au fur et à mesure des versions suivantes.</p>
                </div>
            </div>

            <div class="notice notice-info inline htp-global-rule"><p><strong>Principe d’héritage :</strong> un module utilisera « Utiliser l’apparence globale » par défaut. Si « Personnaliser ce module » est activé plus tard dans sa catégorie, ses valeurs locales prendront le dessus. Un élément spécial pourra ensuite prendre le dessus sur le module.</p></div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_global_appearance">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_global_appearance'); ?>

                <details class="htp-global-group" open>
                    <summary>Identité, textes et bordures</summary>
                    <div class="htp-global-fields">
                        <?php self::color_field('primary_color', $g['primary_color'], 'Couleur principale'); ?>
                        <?php self::color_field('secondary_color', $g['secondary_color'], 'Couleur secondaire'); ?>
                        <?php self::color_field('accent_color', $g['accent_color'], 'Couleur d’accent'); ?>
                        <?php self::color_field('highlight_color', $g['highlight_color'], 'Couleur de mise en valeur'); ?>
                        <?php self::color_field('body_text_color', $g['body_text_color'], 'Texte principal', 'Vide = couleur du thème du site'); ?>
                        <?php self::color_field('heading_text_color', $g['heading_text_color'], 'Titres', 'Vide = couleur du thème du site'); ?>
                        <?php self::color_field('border_color', $g['border_color'], 'Bordures générales', 'Vide = couleur du thème du site'); ?>
                        <?php self::color_field('link_color', $g['link_color'], 'Liens', 'Vide = couleur principale'); ?>
                        <?php self::color_field('focus_color', $g['focus_color'], 'Focus clavier', 'Vide = couleur de mise en valeur'); ?>
                    </div>
                </details>

                <details class="htp-global-group">
                    <summary>Boutons</summary>
                    <h3>Bouton principal</h3>
                    <div class="htp-global-fields">
                        <?php self::color_field('button_primary_bg_color', $g['button_primary_bg_color'], 'Fond', 'Vide = couleur principale'); ?>
                        <?php self::color_field('button_primary_text_color', $g['button_primary_text_color'], 'Texte'); ?>
                        <?php self::color_field('button_primary_border_color', $g['button_primary_border_color'], 'Bordure', 'Vide = couleur principale'); ?>
                    </div>
                    <h3>Bouton secondaire</h3>
                    <div class="htp-global-fields">
                        <?php self::color_field('button_secondary_bg_color', $g['button_secondary_bg_color'], 'Fond', 'Vide = transparent'); ?>
                        <?php self::color_field('button_secondary_text_color', $g['button_secondary_text_color'], 'Texte', 'Vide = couleur principale'); ?>
                        <?php self::color_field('button_secondary_border_color', $g['button_secondary_border_color'], 'Bordure', 'Vide = couleur principale'); ?>
                        <?php self::number_field('button_radius', $g['button_radius'], 'Arrondi commun', 0, 80); ?>
                    </div>
                </details>

                <details class="htp-global-group">
                    <summary>Cartes et blocs</summary>
                    <div class="htp-global-fields">
                        <?php self::color_field('card_bg_color', $g['card_bg_color'], 'Fond des cartes', 'Vide = transparent'); ?>
                        <?php self::color_field('card_border_color', $g['card_border_color'], 'Bordure des cartes', 'Vide = bordure générale'); ?>
                        <?php self::number_field('card_radius', $g['card_radius'], 'Arrondi des cartes', 0, 80); ?>
                        <?php self::number_field('block_spacing', $g['block_spacing'], 'Espacement entre blocs', 0, 60); ?>
                        <label class="htp-global-field"><span>Ombre des cartes</span><select name="appearance[card_shadow]"><option value="none" <?php selected($g['card_shadow'], 'none'); ?>>Aucune</option><option value="soft" <?php selected($g['card_shadow'], 'soft'); ?>>Légère</option><option value="medium" <?php selected($g['card_shadow'], 'medium'); ?>>Moyenne</option></select></label>
                        <div class="htp-global-field htp-global-check"><span>Bordure autour des blocs publics</span><input type="hidden" name="appearance[block_border_enabled]" value="0"><label><input type="checkbox" name="appearance[block_border_enabled]" value="1" <?php checked($g['block_border_enabled'], '1'); ?>> Afficher la bordure générale</label></div>
                    </div>
                </details>

                <details class="htp-global-group">
                    <summary>Onglets et petits badges</summary>
                    <h3>Onglets</h3>
                    <div class="htp-global-fields">
                        <?php self::color_field('tab_bg_color', $g['tab_bg_color'], 'Fond normal', 'Vide = transparent'); ?>
                        <?php self::color_field('tab_text_color', $g['tab_text_color'], 'Texte normal', 'Vide = thème'); ?>
                        <?php self::color_field('tab_active_bg_color', $g['tab_active_bg_color'], 'Fond actif', 'Vide = couleur principale'); ?>
                        <?php self::color_field('tab_active_text_color', $g['tab_active_text_color'], 'Texte actif'); ?>
                    </div>
                    <h3>Badges / pastilles génériques</h3>
                    <div class="htp-global-fields">
                        <?php self::color_field('chip_bg_color', $g['chip_bg_color'], 'Fond', 'Vide = transparent'); ?>
                        <?php self::color_field('chip_text_color', $g['chip_text_color'], 'Texte', 'Vide = thème'); ?>
                        <?php self::color_field('chip_border_color', $g['chip_border_color'], 'Bordure', 'Vide = bordure générale'); ?>
                        <?php self::number_field('chip_radius', $g['chip_radius'], 'Arrondi', 0, 999); ?>
                    </div>
                    <p class="description">Les couleurs qui ont un sens métier restent locales : jour fermé, horaires, événements, accès limité, états du Calendrier de l’Avent, messages importants, etc.</p>
                </details>

                <div class="htp-global-preview" style="<?php echo esc_attr($preview_style); ?>">
                    <h3>Aperçu du socle global</h3>
                    <div class="htp-global-preview-card">
                        <strong>Exemple de carte</strong>
                        <p>Les futures catégories pourront reprendre automatiquement ces réglages.</p>
                        <div class="htp-global-preview-actions"><span class="htp-preview-button is-primary">Bouton principal</span><span class="htp-preview-button is-secondary">Bouton secondaire</span><span class="htp-preview-chip">Badge</span></div>
                    </div>
                </div>

                <?php submit_button('Enregistrer l’apparence globale'); ?>
            </form>
        </section>
        <?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        $season = ($year !== '' && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) ? $all['seasons'][$year] : array();
        $settings = Parcs_HT_Defaults::settings($year);
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $candidate) if (preg_match('/^20\d{2}$/', (string)$candidate)) $years[] = (string)$candidate;
        sort($years, SORT_NUMERIC);
        $automatic = $year !== '' ? self::automatic_state($year) : 'manual';
        $park_name = self::translated_value($general['park_name'] ?? '');
        $timezone = (string)($settings['timezone'] ?? 'Europe/Paris');
        ?>
        <div class="wrap htp-general-admin">
            <h1>Administration générale</h1>
            <p class="description">Gérez ici les éléments communs du parc et de l’année sélectionnée. Les autres catégories restent séparées pour éviter les modifications trop larges et les régressions.</p>

            <?php if (isset($_GET['appearance_updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message de confirmation uniquement. */ ?>
                <div class="notice notice-success is-dismissible"><p>L’apparence globale a été enregistrée. Les catégories déjà existantes conservent leur rendu actuel tant qu’elles ne sont pas reliées à ce nouveau socle.</p></div>
            <?php endif; ?>

            <?php if ($years) : ?>
                <nav class="htp-general-years" aria-label="Saisons"><strong>Année sélectionnée :</strong><?php foreach ($years as $candidate) : ?><a class="button <?php echo $candidate === $year ? 'button-primary' : ''; ?>" href="<?php echo esc_url(self::overview_url($candidate)); ?>"><?php echo esc_html($candidate); ?></a><?php endforeach; ?></nav>
            <?php endif; ?>

            <div class="htp-general-grid">
                <section class="htp-general-card">
                    <div class="htp-general-card-head"><div><h2>Parc</h2><p>Informations réellement communes à toute l’installation.</p></div><a class="button" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Modifier les informations</a></div>
                    <dl class="htp-general-facts"><div><dt>Nom du parc</dt><dd><?php echo esc_html($park_name !== '' ? $park_name : 'Non renseigné'); ?></dd></div><div><dt>Fuseau horaire</dt><dd><?php echo esc_html($timezone); ?></dd></div><div><dt>Début de saison</dt><dd><?php echo esc_html(self::date_label($season['season_start'] ?? '')); ?></dd></div><div><dt>Fin de saison</dt><dd><?php echo esc_html(self::date_label($season['season_end'] ?? '')); ?></dd></div></dl>
                </section>

                <section class="htp-general-card">
                    <div class="htp-general-card-head"><div><h2>Saisons</h2><p>Préparez les années séparément, sans publier involontairement la suivante.</p></div><a class="button" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Gérer les saisons</a></div>
                    <div class="htp-general-season-list"><?php foreach ($years as $candidate) : $candidate_season = isset($all['seasons'][$candidate]) && is_array($all['seasons'][$candidate]) ? $all['seasons'][$candidate] : array(); $has_public=false; foreach (array('calendar_visible','retail_tariffs_visible','groups_schedule_visible','group_quotes_enabled','group_tariffs_visible') as $key) if ((string)($candidate_season[$key] ?? '0') === '1') {$has_public=true;break;} ?><a href="<?php echo esc_url(self::overview_url($candidate)); ?>" class="htp-general-season <?php echo $candidate === $year ? 'is-current' : ''; ?>"><strong><?php echo esc_html($candidate); ?></strong><span><?php echo $has_public ? 'au moins un module actif' : 'préparation / brouillon'; ?></span></a><?php endforeach; ?></div>
                </section>
            </div>

            <?php if ($year !== '') : ?>
                <section class="htp-general-card htp-general-publication">
                    <div class="htp-general-card-head"><div><h2>Publication de l’année sélectionnée — <?php echo esc_html($year); ?></h2><p>Les cinq activations restent indépendantes avant la bascule automatique.</p></div><a class="button button-primary" href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Modifier la publication</a></div>
                    <div class="htp-general-auto"><div><span>Activation automatique</span><strong><?php echo esc_html(self::date_label($season['public_display_from'] ?? '')); ?></strong></div><div><span>Désactivation automatique</span><strong><?php echo esc_html(self::date_label($season['public_display_until'] ?? '')); ?></strong></div><div><span>État actuel</span><strong><?php echo esc_html($automatic === 'on' ? 'année activée automatiquement' : ($automatic === 'off' ? 'année désactivée automatiquement' : 'interrupteurs manuels')); ?></strong></div></div>
                    <div class="htp-general-status-list"><?php foreach (self::status_rows($year, $season) as $row) : ?><span class="htp-general-status <?php echo $row['on'] ? 'is-on' : 'is-off'; ?>"><i aria-hidden="true"></i><?php echo esc_html($row['label']); ?> : <?php echo $row['on'] ? 'affiché' : 'masqué'; ?> <small>(<?php echo esc_html($row['source']); ?>)</small></span><?php endforeach; ?></div>
                </section>
            <?php endif; ?>

            <?php self::appearance_form($all, $year); ?>
            <p class="htp-general-legacy"><a href="<?php echo esc_url(self::detailed_url($year, 'htp-general')); ?>">Ouvrir les réglages détaillés historiques</a> — conservés pendant la refonte pour sécuriser la migration.</p>
        </div>
        <style>
        .htp-general-admin{max-width:1240px}.htp-general-years{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:18px 0 22px}.htp-general-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.htp-general-card{margin:0 0 16px;padding:20px;background:#fff;border:1px solid #dcdcde;border-radius:10px}.htp-general-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.htp-general-card-head h2{margin:0 0 6px}.htp-general-card-head p{margin:0;color:#646970}.htp-general-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0}.htp-general-facts div,.htp-general-auto>div{padding:12px;background:#f6f7f7;border-radius:8px}.htp-general-facts dt,.htp-general-auto span{display:block;color:#646970;font-size:12px;margin-bottom:4px}.htp-general-facts dd{margin:0;font-weight:600}.htp-general-season-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px}.htp-general-season{display:flex;flex-direction:column;gap:3px;padding:12px;border:1px solid #dcdcde;border-radius:8px;text-decoration:none;color:#1d2327}.htp-general-season.is-current{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.htp-general-season span{font-size:12px;color:#646970}.htp-general-publication{margin-top:16px}.htp-general-auto{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px}.htp-general-status-list{display:flex;flex-wrap:wrap;gap:8px}.htp-general-status{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f0f0f1;font-weight:600}.htp-general-status small{font-weight:400;opacity:.78}.htp-general-status i{width:9px;height:9px;border-radius:50%;background:#8c8f94}.htp-general-status.is-on{background:#edfaef;color:#176b2c}.htp-general-status.is-on i{background:#00a32a}.htp-global-rule{margin:0 0 18px!important}.htp-global-group{border-top:1px solid #dcdcde;padding:0}.htp-global-group>summary{cursor:pointer;padding:14px 0;font-size:15px;font-weight:700}.htp-global-group h3{margin:10px 0}.htp-global-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding:2px 0 18px}.htp-global-field{display:flex;flex-direction:column;gap:6px}.htp-global-field>span{font-weight:600}.htp-global-field small{color:#646970}.htp-global-field input[type=text],.htp-global-field input[type=number],.htp-global-field select{max-width:100%}.htp-global-number{display:flex;align-items:center;gap:6px}.htp-global-number input{width:100px}.htp-global-number em{font-style:normal;color:#646970}.htp-global-check label{font-weight:400}.htp-global-preview{margin:18px 0;padding:16px;border:1px dashed #a7aaad;border-radius:8px}.htp-global-preview-card{padding:18px;background:var(--htp-card-bg);border:1px solid var(--htp-card-border);border-radius:var(--htp-card-radius);box-shadow:var(--htp-card-shadow)}.htp-global-preview-card p{color:var(--htp-body-text);margin:8px 0 14px}.htp-global-preview-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.htp-preview-button,.htp-preview-chip{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-style:solid;border-width:1px;text-decoration:none}.htp-preview-button{border-radius:var(--htp-button-radius)}.htp-preview-button.is-primary{background:var(--htp-button-primary-bg);color:var(--htp-button-primary-text);border-color:var(--htp-button-primary-border)}.htp-preview-button.is-secondary{background:var(--htp-button-secondary-bg);color:var(--htp-button-secondary-text);border-color:var(--htp-button-secondary-border)}.htp-preview-chip{background:var(--htp-chip-bg);color:var(--htp-chip-text);border-color:var(--htp-chip-border);border-radius:var(--htp-chip-radius)}.htp-general-legacy{text-align:right}.htp-general-legacy a{text-decoration:none}@media(max-width:900px){.htp-general-grid,.htp-general-auto,.htp-global-fields{grid-template-columns:1fr}.htp-general-card-head{flex-direction:column}.htp-general-facts{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
