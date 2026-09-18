<?php

if (!defined('ABSPATH')) { exit; }

/** Interface d’administration du référentiel visuel global 1.17.1. */
final class Parcs_HT_Global_Appearance_Admin {
    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('admin_post_parcs_ht_save_global_appearance', array(__CLASS__, 'save'));
    }

    public static function assets($hook) {
        unset($hook);
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        if (!class_exists('Parcs_HT_Admin_General') || $page !== Parcs_HT_Admin_General::PAGE) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('wp-color-picker', 'jQuery(function($){$(".htp-global-color").wpColorPicker({clear:true});});');
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

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_global_appearance');
        if (!class_exists('Parcs_HT_Global_Appearance')) wp_die('Référentiel d’apparence indisponible.');

        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['general']) || !is_array($all['general'])) $all['general'] = array();
        $raw = isset($_POST['appearance']) && is_array($_POST['appearance']) ? map_deep(wp_unslash($_POST['appearance']), 'sanitize_text_field') : array();
        $all['general'] = Parcs_HT_Global_Appearance::merge_general($all['general'], $raw);
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        do_action('litespeed_purge_all');

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        $args = array('page'=>Parcs_HT_Admin_General::PAGE, 'appearance_updated'=>'1');
        if (preg_match('/^20\d{2}$/', $year)) $args['season'] = $year;
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function render($all, $year) {
        $g = Parcs_HT_Global_Appearance::general($all);
        $vars = Parcs_HT_Global_Appearance::css_variables($all);
        $preview_style = '';
        foreach ($vars as $name => $value) $preview_style .= $name . ':' . $value . ';';
        ?>
        <section class="htp-general-card htp-global-appearance-card">
            <div class="htp-general-card-head">
                <div>
                    <h2>Apparence globale</h2>
                    <p>Cette apparence devient la référence commune. Chaque catégorie sera reliée à ce socle au fur et à mesure des prochaines versions.</p>
                </div>
            </div>

            <div class="notice notice-info inline htp-global-rule"><p><strong>Principe d’héritage :</strong> « Utiliser l’apparence globale » sera le comportement par défaut. Si « Personnaliser ce module » est activé dans une catégorie, ses valeurs locales prendront le dessus. Un élément spécial pourra ensuite prendre le dessus sur le module.</p></div>

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
                    <p class="description">Les couleurs qui ont un sens métier restent locales : horaires, jours fermés, événements, accès limité, états du Calendrier de l’Avent et messages sémantiques.</p>
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
        <style>
        .htp-global-rule{margin:0 0 18px!important}.htp-global-group{border-top:1px solid #dcdcde;padding:0}.htp-global-group>summary{cursor:pointer;padding:14px 0;font-size:15px;font-weight:700}.htp-global-group h3{margin:10px 0}.htp-global-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding:2px 0 18px}.htp-global-field{display:flex;flex-direction:column;gap:6px}.htp-global-field>span{font-weight:600}.htp-global-field small{color:#646970}.htp-global-field input[type=text],.htp-global-field input[type=number],.htp-global-field select{max-width:100%}.htp-global-number{display:flex;align-items:center;gap:6px}.htp-global-number input{width:100px}.htp-global-number em{font-style:normal;color:#646970}.htp-global-check label{font-weight:400}.htp-global-preview{margin:18px 0;padding:16px;border:1px dashed #a7aaad;border-radius:8px}.htp-global-preview-card{padding:18px;background:var(--htp-card-bg);border:1px solid var(--htp-card-border);border-radius:var(--htp-card-radius);box-shadow:var(--htp-card-shadow)}.htp-global-preview-card p{color:var(--htp-body-text);margin:8px 0 14px}.htp-global-preview-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.htp-preview-button,.htp-preview-chip{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-style:solid;border-width:1px}.htp-preview-button{border-radius:var(--htp-button-radius)}.htp-preview-button.is-primary{background:var(--htp-button-primary-bg);color:var(--htp-button-primary-text);border-color:var(--htp-button-primary-border)}.htp-preview-button.is-secondary{background:var(--htp-button-secondary-bg);color:var(--htp-button-secondary-text);border-color:var(--htp-button-secondary-border)}.htp-preview-chip{background:var(--htp-chip-bg);color:var(--htp-chip-text);border-color:var(--htp-chip-border);border-radius:var(--htp-chip-radius)}@media(max-width:900px){.htp-global-fields{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
