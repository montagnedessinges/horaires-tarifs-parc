<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Guide_Appearance {
    const OPTION = 'parcs_ht_guide_appearance';

    public static function init() {
        add_action('admin_post_parcs_ht_save_guide_appearance', array(__CLASS__, 'save'));
        add_action('admin_footer', array(__CLASS__, 'admin_panel'), 20);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'public_assets'), 40);
    }

    public static function defaults() {
        return array(
            'card_background' => 'transparent',
            'text_color' => 'inherit',
            'title_color' => 'inherit',
            'primary_button_background' => '#176b57',
            'primary_button_text' => '#ffffff',
            'secondary_button_color' => 'inherit',
            'category_color' => 'inherit',
            'image_mobile_height' => '145',
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        return wp_parse_args(is_array($saved) ? $saved : array(), self::defaults());
    }

    private static function color($value, $fallback, $allow_special = false) {
        $value = trim((string) $value);
        if ($allow_special && in_array($value, array('inherit', 'transparent'), true)) return $value;
        $color = sanitize_hex_color($value);
        return $color ? $color : $fallback;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_guide_appearance');
        $defaults = self::defaults();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chaque valeur est validée et nettoyée ci-dessous avant enregistrement.
        $raw = isset($_POST['appearance']) && is_array($_POST['appearance']) ? wp_unslash($_POST['appearance']) : array();
        $settings = array(
            'card_background' => self::color($raw['card_background'] ?? '', $defaults['card_background'], true),
            'text_color' => self::color($raw['text_color'] ?? '', $defaults['text_color'], true),
            'title_color' => self::color($raw['title_color'] ?? '', $defaults['title_color'], true),
            'primary_button_background' => self::color($raw['primary_button_background'] ?? '', $defaults['primary_button_background']),
            'primary_button_text' => self::color($raw['primary_button_text'] ?? '', $defaults['primary_button_text']),
            'secondary_button_color' => self::color($raw['secondary_button_color'] ?? '', $defaults['secondary_button_color'], true),
            'category_color' => self::color($raw['category_color'] ?? '', $defaults['category_color'], true),
            'image_mobile_height' => (string) max(90, min(220, (int) ($raw['image_mobile_height'] ?? 145))),
        );
        update_option(self::OPTION, $settings, false);
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-guides','guide-appearance-updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function public_assets() {
        $s = self::settings();
        $css = '.parcs-ht-guides{--htp-guide-card-bg:' . esc_html($s['card_background']) . ';--htp-guide-text:' . esc_html($s['text_color']) . ';--htp-guide-title:' . esc_html($s['title_color']) . ';--htp-guide-primary-bg:' . esc_html($s['primary_button_background']) . ';--htp-guide-primary-text:' . esc_html($s['primary_button_text']) . ';--htp-guide-secondary:' . esc_html($s['secondary_button_color']) . ';--htp-guide-category:' . esc_html($s['category_color']) . ';--htp-guide-mobile-image-height:' . (int)$s['image_mobile_height'] . 'px;}';
        wp_add_inline_style('parcs-ht-pedagogical-guides', $css);
        wp_register_script('parcs-ht-guide-enhancements', false, array(), PARCS_HT_VERSION, true);
        wp_enqueue_script('parcs-ht-guide-enhancements');
        wp_add_inline_script('parcs-ht-guide-enhancements', self::frontend_script());
    }

    private static function frontend_script() {
        return "(function(){function enhance(r){if(!r||r.dataset.guideAppearanceReady)return;r.dataset.guideAppearanceReady='1';if(!r.querySelector('[data-guide-cycle-filters]')){var c=r.querySelector('[data-guide-card]');if(c){var lang=r.dataset.guideLanguage||'fr',cycle=c.dataset.cycle||'cycle1';var labels={fr:{title:'Cycles / niveaux',cycle1:'Cycle 1',cycle2:'Cycle 2',cycle3:'Cycle 3',cycle4:'Cycle 4',multi:'Multiniveaux'},en:{title:'Age groups / levels',cycle1:'Ages 3–6',cycle2:'Ages 6–9',cycle3:'Ages 9–12',cycle4:'Ages 12–15',multi:'Multi-level'},de:{title:'Altersgruppen / Niveaus',cycle1:'3–6 Jahre',cycle2:'6–9 Jahre',cycle3:'9–12 Jahre',cycle4:'12–15 Jahre',multi:'Mehrere Stufen'}};var l=labels[lang]||labels.fr;var g=document.createElement('div');g.className='parcs-ht-guide-filter-group parcs-ht-guide-single-category';g.innerHTML='<strong>'+l.title+'</strong><div class=\"parcs-ht-guide-filters\"><button type=\"button\" class=\"is-active\" disabled>'+((l[cycle])||cycle)+'</button></div>';var anchor=r.querySelector('.parcs-ht-guide-filter-group,.parcs-ht-guide-grid');if(anchor)r.insertBefore(g,anchor);}}}document.querySelectorAll('[data-htp-guides]').forEach(enhance);document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-htp-guides]').forEach(enhance);});}());";
    }

    private static function preview_guide() {
        if (!class_exists('Parcs_HT_Pedagogical_Guides')) return array();
        $settings = Parcs_HT_Pedagogical_Guides::settings();
        foreach ((array)($settings['guides'] ?? array()) as $guide) if (is_array($guide)) return $guide;
        return array();
    }

    public static function admin_panel() {
        if (!current_user_can('manage_options')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'toplevel_page_' . Parcs_HT_Admin::PAGE) return;
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Lecture seule.
        $s = self::settings();
        $guide = self::preview_guide();
        $cover = esc_url((string)($guide['cover_url'] ?? ''));
        $title = '';
        $description = '';
        if (!empty($guide['title']) && is_array($guide['title'])) $title = (string)($guide['title']['fr'] ?? reset($guide['title']));
        if (!empty($guide['description']) && is_array($guide['description'])) $description = (string)($guide['description']['fr'] ?? reset($guide['description']));
        if ($title === '') $title = 'Dossier pédagogique – Cycle 1';
        if ($description === '') $description = 'Aperçu du rendu d’un guide pédagogique sur le site.';
        ?>
        <section class="htp-card htp-guide-appearance-panel" data-guide-appearance-panel>
            <h2>Apparence & aperçu des guides</h2>
            <p class="description">Modifiez les couleurs et la hauteur de l’image sur mobile. L’aperçu se met à jour immédiatement avant l’enregistrement.</p>
            <?php if (isset($_GET['guide-appearance-updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message uniquement. */ ?><div class="notice notice-success inline"><p>Apparence des guides enregistrée.</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_guide_appearance">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_guide_appearance'); ?>
                <div class="htp-guide-appearance-grid">
                    <?php self::field('card_background','Fond de la carte',$s['card_background'],true); ?>
                    <?php self::field('text_color','Couleur du texte',$s['text_color'],true); ?>
                    <?php self::field('title_color','Couleur des titres',$s['title_color'],true); ?>
                    <?php self::field('category_color','Couleur catégorie / niveau',$s['category_color'],true); ?>
                    <?php self::field('primary_button_background','Fond bouton principal',$s['primary_button_background']); ?>
                    <?php self::field('primary_button_text','Texte bouton principal',$s['primary_button_text']); ?>
                    <?php self::field('secondary_button_color','Bouton secondaire',$s['secondary_button_color'],true); ?>
                    <label class="htp-guide-appearance-field"><span>Hauteur image mobile</span><input type="range" min="90" max="220" step="5" name="appearance[image_mobile_height]" value="<?php echo esc_attr($s['image_mobile_height']); ?>" data-guide-style="image_mobile_height"><output><?php echo (int)$s['image_mobile_height']; ?> px</output></label>
                </div>
                <div class="htp-guide-live-preview" data-guide-preview style="--preview-bg:<?php echo esc_attr($s['card_background']); ?>;--preview-text:<?php echo esc_attr($s['text_color']); ?>;--preview-title:<?php echo esc_attr($s['title_color']); ?>;--preview-category:<?php echo esc_attr($s['category_color']); ?>;--preview-primary:<?php echo esc_attr($s['primary_button_background']); ?>;--preview-primary-text:<?php echo esc_attr($s['primary_button_text']); ?>;--preview-secondary:<?php echo esc_attr($s['secondary_button_color']); ?>;--preview-image-height:<?php echo (int)$s['image_mobile_height']; ?>px">
                    <div class="htp-guide-preview-label">Aperçu mobile</div>
                    <article class="htp-guide-preview-card">
                        <div class="htp-guide-preview-cover"><?php if ($cover) : ?><img src="<?php echo esc_url($cover); ?>" alt=""><?php else : ?><span>📘</span><?php endif; ?></div>
                        <div class="htp-guide-preview-body"><div class="htp-guide-preview-category">Cycle 1<small>Maternelle – 3 à 6 ans</small></div><div class="htp-guide-preview-language">🇫🇷 Français</div><h4><?php echo esc_html($title); ?></h4><p><?php echo esc_html($description); ?></p><div class="htp-guide-preview-actions"><span class="is-primary">Consulter</span><span class="is-secondary">Télécharger le PDF</span></div></div>
                    </article>
                </div>
                <?php submit_button('Enregistrer l’apparence'); ?>
            </form>
        </section>
        <script>(function(){var panel=document.querySelector('[data-guide-appearance-panel]'),host=document.getElementById('htp-guides');if(!panel||!host)return;host.appendChild(panel);var preview=panel.querySelector('[data-guide-preview]');var map={card_background:'--preview-bg',text_color:'--preview-text',title_color:'--preview-title',category_color:'--preview-category',primary_button_background:'--preview-primary',primary_button_text:'--preview-primary-text',secondary_button_color:'--preview-secondary'};panel.querySelectorAll('[data-guide-style]').forEach(function(input){function sync(){var key=input.dataset.guideStyle;if(key==='image_mobile_height'){preview.style.setProperty('--preview-image-height',input.value+'px');var o=input.parentNode.querySelector('output');if(o)o.textContent=input.value+' px';return;}if(map[key])preview.style.setProperty(map[key],input.value||'inherit');}input.addEventListener('input',sync);input.addEventListener('change',sync);});}());</script>
        <?php
    }

    private static function field($key, $label, $value, $allow_special = false) {
        $type = preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? 'color' : 'text';
        ?><label class="htp-guide-appearance-field"><span><?php echo esc_html($label); ?></span><div class="htp-guide-color-control"><input type="<?php echo esc_attr($type); ?>" name="appearance[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" data-guide-style="<?php echo esc_attr($key); ?>"><?php if ($allow_special) : ?><small>Hex, inherit ou transparent</small><?php endif; ?></div></label><?php
    }
}
