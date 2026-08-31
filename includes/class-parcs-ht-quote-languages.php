<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Quote_Languages {
    const OPTION = 'parcs_ht_quote_language_shortcodes';
    const PAGE = 'parcs-ht-quote-languages';

    public static function init() {
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'apply_request_form'), 20, 1);
        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'menu'), 30);
            add_action('admin_post_parcs_ht_save_quote_languages', array(__CLASS__, 'save'));
        }
    }

    public static function defaults() {
        return array('fr'=>'', 'en'=>'', 'de'=>'');
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        return array_merge(self::defaults(), is_array($saved) ? $saved : array());
    }

    private static function request_language() {
        global $post;
        $content = ($post && isset($post->post_content)) ? (string)$post->post_content : '';
        foreach (array('fr','en','de') as $language) {
            if ($content !== '' && (has_shortcode($content, 'parc_devis_groupe_' . $language) || has_shortcode($content, 'parc_devis_' . $language))) return $language;
        }
        $language = Parcs_HT_Schedule::language();
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    public static function apply_request_form($settings) {
        if (is_admin() || !is_array($settings)) return $settings;
        $forms = self::settings();
        $language = self::request_language();
        $selected = trim((string)($forms[$language] ?? ''));
        if ($selected === '') return $settings;
        if (!isset($settings['quote_page']) || !is_array($settings['quote_page'])) $settings['quote_page'] = array();
        $settings['quote_page']['form_shortcode'] = $selected;
        return $settings;
    }

    public static function menu() {
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Formulaires de devis par langue',
            'Devis par langue',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $forms = self::settings();
        ?>
        <div class="wrap">
            <h1>Formulaires de devis par langue</h1>
            <p>Chaque shortcode du module de devis affiche automatiquement le formulaire Contact Form 7 configuré pour sa langue.</p>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?><div class="notice notice-success is-dismissible"><p>Les shortcodes des formulaires ont été enregistrés.</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_quote_languages">
                <?php wp_nonce_field('parcs_ht_save_quote_languages'); ?>
                <table class="form-table" role="presentation">
                    <?php foreach (array('fr'=>'Français','en'=>'Anglais','de'=>'Allemand') as $lang=>$label) : ?>
                        <tr>
                            <th scope="row"><label for="parcs-ht-quote-<?php echo esc_attr($lang); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <input id="parcs-ht-quote-<?php echo esc_attr($lang); ?>" class="large-text code" type="text" name="forms[<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($forms[$lang]); ?>" placeholder='[contact-form-7 id="..."]'>
                                <p class="description">Utilisé par <code>[parc_devis_<?php echo esc_html($lang); ?>]</code> et <code>[parc_devis_groupe_<?php echo esc_html($lang); ?>]</code>. Si le champ est vide, le formulaire général reste utilisé en secours.</p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button('Enregistrer les formulaires'); ?>
            </form>
        </div>
        <?php
    }

    private static function sanitize_form_shortcode($value) {
        $value = trim(sanitize_text_field((string)$value));
        if ($value === '') return '';
        if (!preg_match('/^\[contact-form-7(?:\s+[^\]]*)?\s*\/?\]$/i', $value)) return null;
        return $value;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_languages');

        $posted = isset($_POST['forms']) && is_array($_POST['forms']) ? wp_unslash($_POST['forms']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Les trois valeurs sont nettoyées et validées individuellement ci-dessous.
        $clean = self::defaults();
        $labels = array('fr'=>'français', 'en'=>'anglais', 'de'=>'allemand');

        foreach ($clean as $lang=>$unused) {
            $value = self::sanitize_form_shortcode($posted[$lang] ?? '');
            if ($value === null) {
                wp_die('Le shortcode du formulaire ' . esc_html($labels[$lang]) . ' n’est pas valide. Copiez le shortcode Contact Form 7 complet, par exemple [contact-form-7 id="..."] puis réessayez.');
            }
            $clean[$lang] = $value;
        }

        update_option(self::OPTION, $clean, false);

        $stored = get_option(self::OPTION, array());
        $stored = array_merge(self::defaults(), is_array($stored) ? $stored : array());
        if ($stored !== $clean) {
            wp_die('WordPress n’a pas confirmé l’enregistrement des formulaires de devis. Aucune fausse confirmation n’a été affichée ; réessayez après avoir vidé le cache d’administration.');
        }

        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE, 'updated'=>'1'), admin_url('admin.php')));
        exit;
    }
}
