<?php

if (!defined('ABSPATH')) { exit; }

/**
 * FAQ / Contact 1.19.2, maintenance 1.19.5.
 *
 * Cette couche garde le moteur FAQ 1.19.x comme source de vérité et ajoute :
 * - un rendu public sans faux en-tête de page ;
 * - un shortcode combiné FAQ + Contact Form 7 ;
 * - un bouton de contact qui ouvre le formulaire sans quitter la FAQ ;
 * - un stockage dédié au formulaire de contact, séparé des saisons.
 */
final class Parcs_HT_FAQ_Contact_1192 {
    const OPTION = 'parcs_ht_faq_contact';
    private static $contact_instance = 0;

    public static function init() {
        // Remplace uniquement l'enveloppe des shortcodes FAQ. Le moteur métier,
        // les filtres, la recherche et les accordéons restent ceux de Parcs_HT_FAQ.
        foreach (array('parc_faq','parc_faq_fr','parc_faq_en','parc_faq_de') as $tag) {
            remove_shortcode($tag);
        }

        add_shortcode('parc_faq', static function ($atts = array()) {
            return Parcs_HT_FAQ_Contact_1192::render_faq(Parcs_HT_FAQ_Contact_1192::current_language(), is_array($atts) ? $atts : array());
        });
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_faq_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_FAQ_Contact_1192::render_faq($language, is_array($atts) ? $atts : array());
            });
        }

        add_shortcode('parc_faq_contact', static function ($atts = array()) {
            return Parcs_HT_FAQ_Contact_1192::render_combined(Parcs_HT_FAQ_Contact_1192::current_language(), is_array($atts) ? $atts : array());
        });
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_faq_contact_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_FAQ_Contact_1192::render_combined($language, is_array($atts) ? $atts : array());
            });
        }

        if (is_admin()) {
            add_action('admin_post_parcs_ht_faq_contact_save', array(__CLASS__, 'save'));
            add_action('admin_menu', array(__CLASS__, 'replace_faq_page'), 70);
        }
    }

    private static function current_language() {
        if (class_exists('Parcs_HT_Schedule')) {
            $language = Parcs_HT_Schedule::language();
            if (in_array($language, array('fr','en','de'), true)) return $language;
        }
        return 'fr';
    }

    private static function bool_value($value) {
        $value = strtolower(trim((string)$value));
        return in_array($value, array('1','yes','true','oui','ja','on'), true);
    }

    private static function defaults() {
        return array(
            'enabled'=>'0',
            'forms'=>array('fr'=>'','en'=>'','de'=>''),
        );
    }

    public static function settings() {
        $defaults = self::defaults();
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $settings = array_replace($defaults, $saved);
        $forms = isset($saved['forms']) && is_array($saved['forms']) ? $saved['forms'] : array();
        $settings['forms'] = array_replace($defaults['forms'], $forms);
        return $settings;
    }

    private static function texts($language) {
        $texts = array(
            'fr'=>array(
                'title'=>'Questions fréquentes',
                'intro'=>'Trouvez rapidement la réponse à votre question.',
                'contact_title'=>'Vous n’avez pas trouvé votre réponse ?',
                'contact_intro'=>'Consultez d’abord la FAQ ci-dessus. Si aucune réponse ne correspond à votre situation, vous pouvez contacter l’équipe.',
                'contact_button'=>'Je n’ai pas trouvé ma réponse',
                'contact_close'=>'Masquer le formulaire',
            ),
            'en'=>array(
                'title'=>'Frequently asked questions',
                'intro'=>'Quickly find the answer to your question.',
                'contact_title'=>'Didn’t find your answer?',
                'contact_intro'=>'Please check the FAQ above first. If none of the answers matches your situation, you can contact the team.',
                'contact_button'=>'I didn’t find my answer',
                'contact_close'=>'Hide the form',
            ),
            'de'=>array(
                'title'=>'Häufig gestellte Fragen',
                'intro'=>'Finden Sie schnell die Antwort auf Ihre Frage.',
                'contact_title'=>'Keine passende Antwort gefunden?',
                'contact_intro'=>'Bitte prüfen Sie zuerst die FAQ oben. Wenn keine Antwort zu Ihrer Situation passt, können Sie das Team kontaktieren.',
                'contact_button'=>'Ich habe keine Antwort gefunden',
                'contact_close'=>'Formular ausblenden',
            ),
        );
        return isset($texts[$language]) ? $texts[$language] : $texts['fr'];
    }

    private static function plain_intro($language) {
        $texts = self::texts($language);
        return '<div class="parcs-ht-faq-intro">'
            . '<p><strong>' . esc_html($texts['title']) . '</strong></p>'
            . '<p>' . esc_html($texts['intro']) . '</p>'
            . '</div>';
    }

    public static function render_faq($language = 'fr', $atts = array()) {
        if (!class_exists('Parcs_HT_FAQ')) return '';
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $atts = is_array($atts) ? $atts : array();
        $show_intro = !array_key_exists('titre', $atts) || self::bool_value($atts['titre']);
        $atts['titre'] = '0';

        $faq = Parcs_HT_FAQ::render($language, $atts);
        if (trim((string)$faq) === '') return '';
        return ($show_intro ? self::plain_intro($language) : '') . $faq;
    }

    private static function render_contact($language) {
        $settings = self::settings();
        if ((string)$settings['enabled'] !== '1') return '';
        if (!shortcode_exists('contact-form-7')) return '';
        $shortcode = trim((string)($settings['forms'][$language] ?? ''));
        if ($shortcode === '') return '';

        $form = do_shortcode($shortcode);
        if (trim((string)$form) === '' || trim((string)$form) === $shortcode) return '';
        $texts = self::texts($language);
        self::$contact_instance++;
        $form_id = 'parcs-ht-faq-contact-form-' . self::$contact_instance;

        return '<section class="parcs-ht-faq-contact" data-htp-faq-contact>'
            . '<p><strong>' . esc_html($texts['contact_title']) . '</strong></p>'
            . '<p>' . esc_html($texts['contact_intro']) . '</p>'
            . '<p><button type="button" class="parcs-ht-faq-contact-toggle" data-htp-faq-contact-toggle aria-expanded="false" aria-controls="' . esc_attr($form_id) . '" data-open-label="' . esc_attr($texts['contact_button']) . '" data-close-label="' . esc_attr($texts['contact_close']) . '">' . esc_html($texts['contact_button']) . '</button></p>'
            . '<div id="' . esc_attr($form_id) . '" class="parcs-ht-faq-contact-form" data-htp-faq-contact-form hidden>'
            . $form
            . '</div>'
            . '</section>';
    }

    public static function render_combined($language = 'fr', $atts = array()) {
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        return self::render_faq($language, $atts) . self::render_contact($language);
    }

    private static function sanitize_cf7_shortcode($value) {
        $value = trim(sanitize_text_field((string)$value));
        if ($value === '') return '';
        if (!preg_match('/^\[contact-form-7\s+[^\]]+\]$/', $value)) return '';
        return $value;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_contact_save');

        $form_fr = isset($_POST['contact']['forms']['fr']) ? sanitize_text_field(wp_unslash($_POST['contact']['forms']['fr'])) : '';
        $form_en = isset($_POST['contact']['forms']['en']) ? sanitize_text_field(wp_unslash($_POST['contact']['forms']['en'])) : '';
        $form_de = isset($_POST['contact']['forms']['de']) ? sanitize_text_field(wp_unslash($_POST['contact']['forms']['de'])) : '';
        $settings = array(
            'enabled'=>isset($_POST['contact']['enabled']) ? '1' : '0',
            'forms'=>array(
                'fr'=>self::sanitize_cf7_shortcode($form_fr),
                'en'=>self::sanitize_cf7_shortcode($form_en),
                'de'=>self::sanitize_cf7_shortcode($form_de),
            ),
        );

        update_option(self::OPTION, $settings, false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_FAQ::PAGE,'contact_updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    public static function replace_faq_page() {
        if (!class_exists('Parcs_HT_Admin') || !class_exists('Parcs_HT_FAQ_CSV_1191')) return;
        $hook = get_plugin_page_hookname(Parcs_HT_FAQ::PAGE, Parcs_HT_Admin::PAGE);
        if (!$hook) return;
        remove_action($hook, array('Parcs_HT_FAQ_CSV_1191', 'page'));
        add_action($hook, array(__CLASS__, 'page'));
    }

    public static function page() {
        Parcs_HT_FAQ_CSV_1191::page();
        self::contact_panel();
    }

    private static function contact_panel() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        $cf7_active = shortcode_exists('contact-form-7');
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Message de confirmation en lecture seule.
        $contact_updated = isset($_GET['contact_updated']) ? sanitize_text_field(wp_unslash($_GET['contact_updated'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap">
            <?php if ($contact_updated === '1') : ?>
                <div class="notice notice-success is-dismissible"><p>Le formulaire de contact FAQ a été enregistré.</p></div>
            <?php endif; ?>
            <section class="postbox" style="padding:18px;margin-top:18px;">
                <h2 style="margin-top:0;">Formulaire de contact</h2>
                <p>Dans le shortcode FAQ + Contact, la <strong>FAQ reste prioritaire</strong> et le formulaire est <strong>masqué par défaut</strong>. Le visiteur ne l’ouvre qu’en cliquant sur <strong>« Je n’ai pas trouvé ma réponse »</strong> ; <strong>Contact Form 7 envoie ensuite la demande par e-mail</strong> selon les destinataires configurés dans chaque formulaire.</p>
                <p><strong>Contact Form 7 :</strong> <?php echo $cf7_active ? 'détecté' : 'non détecté'; ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="parcs_ht_faq_contact_save">
                    <?php wp_nonce_field('parcs_ht_faq_contact_save'); ?>
                    <p><label><input type="checkbox" name="contact[enabled]" value="1" <?php checked((string)$settings['enabled'], '1'); ?>> Activer le contact en dernier recours dans le shortcode FAQ + Contact</label></p>
                    <?php foreach (array('fr'=>'Français','en'=>'Anglais','de'=>'Allemand') as $language=>$label) : ?>
                        <?php $configured = trim((string)$settings['forms'][$language]) !== ''; ?>
                        <p><label><strong>Shortcode Contact Form 7 — <?php echo esc_html($label); ?></strong> <span class="description">(<?php echo $configured ? 'configuré' : 'à renseigner'; ?>)</span><br>
                            <input type="text" class="large-text code" name="contact[forms][<?php echo esc_attr($language); ?>]" value="<?php echo esc_attr((string)$settings['forms'][$language]); ?>" placeholder='[contact-form-7 id="123" title="Contact"]'>
                        </label></p>
                    <?php endforeach; ?>
                    <p><button type="submit" class="button button-primary">Enregistrer le formulaire de contact</button></p>
                </form>
                <h3>Shortcodes à utiliser</h3>
                <p><code>[parc_faq_contact]</code> utilise automatiquement la langue du site.</p>
                <p><code>[parc_faq_contact_fr]</code> · <code>[parc_faq_contact_en]</code> · <code>[parc_faq_contact_de]</code></p>
                <p class="description">Le bouton public n’apparaît que si l’option ci-dessus est activée, que Contact Form 7 est actif et qu’un shortcode CF7 est renseigné pour la langue affichée.</p>
                <p class="description">Les shortcodes <code>[parc_faq]</code>, <code>[parc_faq_fr]</code>, <code>[parc_faq_en]</code> et <code>[parc_faq_de]</code> continuent d’afficher uniquement la FAQ.</p>
            </section>
        </div>
        <?php
    }
}
