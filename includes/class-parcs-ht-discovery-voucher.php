<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Offre découverte : simple filtre postal, puis formulaire CF7 existant.
 * Aucun bon n'est généré ou envoyé par ce module.
 * Les options sont propres à chaque installation WordPress (un parc = un site).
 */
final class Parcs_HT_Discovery_Voucher {
    const OPTION = 'parcs_ht_discovery_voucher';
    const PAGE = 'parcs-ht-discovery-voucher';
    const FIELD = 'bon_code_postal';
    private static $instance = 0;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 70);
        add_action('admin_post_parcs_ht_save_discovery_voucher', array(__CLASS__, 'save'));
        add_action('wp_ajax_parcs_ht_discovery_postcode', array(__CLASS__, 'check_postcode'));
        add_action('wp_ajax_nopriv_parcs_ht_discovery_postcode', array(__CLASS__, 'check_postcode'));
        add_filter('wpcf7_validate_text*', array(__CLASS__, 'validate_cf7_postcode'), 20, 2);
        add_filter('wpcf7_spam', array(__CLASS__, 'block_invalid_cf7_postcode'), 20, 1);
        // Les anciens shortcodes de langue restent des alias pour ne casser aucune page existante.
        // Le contenu de l'offre est volontairement proposé uniquement en français.
        foreach (array('fr', 'en', 'de') as $lang) {
            add_shortcode('parc_bon_decouverte_' . $lang, array(__CLASS__, 'shortcode'));
        }
        add_shortcode('parc_bon_decouverte', array(__CLASS__, 'shortcode'));
    }

    public static function labels() {
        return array(
            'title' => 'Titre du bloc',
            'intro' => 'Texte d’introduction',
            'postcode_label' => 'Libellé du code postal',
            'postcode_placeholder' => 'Exemple dans le champ',
            'postcode_button' => 'Bouton de vérification',
            'invalid_zip' => 'Code postal mal saisi',
            'not_eligible' => 'Département non éligible',
            'request_error' => 'Erreur technique',
            'conditions' => 'Conditions sous le formulaire',
            'expired' => 'Offre terminée',
        );
    }

    public static function defaults() {
        return array(
            'enabled' => '0',
            'departments' => '',
            'expires_on' => '',
            'form_shortcode' => '',
            'texts' => array(
                'fr' => array(
                    'title' => '2 entrées offertes pour votre établissement !',
                    'intro' => 'Enseignants, venez découvrir le parc avant votre prochaine sortie scolaire.',
                    'postcode_label' => 'Code postal de votre établissement',
                    'postcode_placeholder' => 'Ex. : 67600',
                    'postcode_button' => 'Vérifier mon éligibilité',
                    'invalid_zip' => 'Veuillez saisir un code postal à 5 chiffres.',
                    'not_eligible' => 'Cette offre est réservée aux établissements de certains départements.',
                    'request_error' => 'La vérification est momentanément indisponible. Réessayez dans quelques instants.',
                    'conditions' => 'Un seul bon par établissement, sous réserve de vérification par notre équipe. Le bon n’est pas envoyé automatiquement.',
                    'expired' => 'Cette offre n’est plus disponible.',
                ),
            ),
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $settings = array_merge(self::defaults(), $saved);
        if (empty($saved['texts']) || !is_array($saved['texts'])) return $settings;
        // Seuls les textes français sont lus et modifiables. Les anciennes traductions
        // stockées dans les options ne sont pas supprimées par mesure de compatibilité.
        $settings['texts']['fr'] = array_merge(
            self::defaults()['texts']['fr'],
            isset($saved['texts']['fr']) && is_array($saved['texts']['fr']) ? $saved['texts']['fr'] : array()
        );
        return $settings;
    }

    public static function text($settings, $key) {
        return trim((string)($settings['texts']['fr'][$key] ?? ''));
    }

    /** Deux chiffres pour les départements métropolitains, trois pour les DROM. */
    public static function allowed_for_zip($zip, $departments) {
        $zip = trim((string)$zip);
        if (!preg_match('/^[0-9]{5}$/D', $zip)) return false;
        $parts = preg_split('/[\s,;]+/u', trim((string)$departments), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || !$parts) return false;
        foreach ($parts as $part) {
            if (preg_match('/^[0-9]{2,3}$/D', $part) && strpos($zip, $part) === 0) return true;
        }
        return false;
    }

    private static function is_active($settings) {
        if (($settings['enabled'] ?? '0') !== '1') return false;
        $until = (string)($settings['expires_on'] ?? '');
        return $until === '' || (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/D', $until) && wp_date('Y-m-d') <= $until;
    }

    private static function eligible($zip, $settings) {
        return self::is_active($settings) && self::allowed_for_zip($zip, $settings['departments'] ?? '');
    }

    public static function shortcode() {
        return self::render();
    }

    public static function render($lang = 'fr') {
        $settings = self::settings();
        if (!self::is_active($settings)) return '';
        if (trim((string)$settings['departments']) === '' || trim((string)$settings['form_shortcode']) === '') {
            return current_user_can('manage_options') ? '<p>Bon découverte : compléter les départements et le formulaire CF7 dans Gestion du parc → Groupes → Bons découverte.</p>' : '';
        }
        if (!class_exists('WPCF7_ContactForm') || !self::form_id($settings)) {
            return current_user_can('manage_options') ? '<p>Bon découverte : formulaire Contact Form 7 non disponible ou shortcode incorrect.</p>' : '';
        }
        self::$instance++;
        $id = 'parcs-voucher-' . self::$instance;
        wp_enqueue_script('parcs-ht-discovery-voucher', PARCS_HT_URL . 'assets/discovery-voucher.js', array(), PARCS_HT_VERSION, true);
        wp_enqueue_style('parcs-ht-discovery-voucher', PARCS_HT_URL . 'assets/discovery-voucher.css', array(), PARCS_HT_VERSION);
        $t = static function ($key) use ($settings) {
            return Parcs_HT_Discovery_Voucher::text($settings, $key);
        };
        ob_start();
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-voucher"
            data-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
            data-nonce="<?php echo esc_attr(wp_create_nonce('parcs_ht_discovery_postcode')); ?>"
            data-invalid="<?php echo esc_attr($t('invalid_zip')); ?>"
            data-error="<?php echo esc_attr($t('request_error')); ?>">
            <h2><?php echo esc_html($t('title')); ?></h2>
            <p><?php echo esc_html($t('intro')); ?></p>
            <form class="parcs-ht-voucher-check" novalidate>
                <label for="<?php echo esc_attr($id); ?>-postal"><?php echo esc_html($t('postcode_label')); ?></label>
                <div class="parcs-ht-voucher-row">
                    <input id="<?php echo esc_attr($id); ?>-postal" name="postcode" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="5" required pattern="[0-9]{5}" placeholder="<?php echo esc_attr($t('postcode_placeholder')); ?>">
                    <button type="submit"><?php echo esc_html($t('postcode_button')); ?></button>
                </div>
                <p class="parcs-ht-voucher-feedback" role="status" aria-live="polite" data-voucher-feedback></p>
            </form>
            <div class="parcs-ht-voucher-cf7" hidden data-voucher-form>
                <?php echo do_shortcode($settings['form_shortcode']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode CF7 autorisé et contrôlé. ?>
                <?php if ($t('conditions') !== '') : ?><p class="parcs-ht-voucher-conditions"><?php echo esc_html($t('conditions')); ?></p><?php endif; ?>
            </div>
            <span hidden data-voucher-not-eligible="<?php echo esc_attr($t('not_eligible')); ?>"></span>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function check_postcode() {
        check_ajax_referer('parcs_ht_discovery_postcode', 'nonce');
        $settings = self::settings();
        $zip = isset($_POST['postcode']) && is_string($_POST['postcode'])
            ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '';
        wp_send_json_success(array('eligible' => self::eligible($zip, $settings)));
    }

    private static function form_id($settings) {
        $code = trim((string)($settings['form_shortcode'] ?? ''));
        if (!preg_match('~^\[contact-form-7\s+[^\]]*\bid\s*=\s*["\x27]?([A-Za-z0-9]+)~i', $code, $m)) return 0;
        $form = null;
        if (ctype_digit($m[1])) $form = WPCF7_ContactForm::get_instance((int)$m[1]);
        elseif (function_exists('wpcf7_get_contact_form_by_hash')) $form = wpcf7_get_contact_form_by_hash($m[1]);
        return is_object($form) && method_exists($form, 'id') ? (int)$form->id() : 0;
    }

    private static function is_target_cf7() {
        if (!class_exists('WPCF7_ContactForm')) return false;
        $form_id = self::form_id(self::settings());
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Identifiant CF7 lu pour vérifier le formulaire ciblé, aucune écriture ; CF7 gère son propre cycle de soumission.
        $posted_id = isset($_POST['_wpcf7']) && is_scalar($_POST['_wpcf7']) ? absint(wp_unslash($_POST['_wpcf7'])) : 0;
        return $form_id > 0 && $form_id === $posted_id;
    }

    private static function submitted_is_eligible() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Validation de la soumission publique orchestrée par Contact Form 7, sans écriture.
        $code = isset($_POST[self::FIELD]) && is_string($_POST[self::FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::FIELD])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        return self::eligible($code, self::settings());
    }

    /** Le champ CF7 obligatoire [text* bon_code_postal readonly] est contrôlé côté serveur. */
    public static function validate_cf7_postcode($result, $tag) {
        if (!self::is_target_cf7() || !is_object($tag) || $tag->name !== self::FIELD) return $result;
        if (!self::submitted_is_eligible()) {
            $result->invalidate($tag, 'Le code postal de l’établissement n’est pas éligible à cette offre.');
        }
        return $result;
    }

    /** Défense supplémentaire : un formulaire mal configuré ne peut pas envoyer de mail. */
    public static function block_invalid_cf7_postcode($spam) {
        if ($spam || !self::is_target_cf7()) return $spam;
        return !self::submitted_is_eligible();
    }

    public static function menu() {
        add_submenu_page(null, 'Bons découverte', 'Bons découverte', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    private static function clean_form_shortcode($value) {
        $value = trim((string)$value);
        return $value === '' || preg_match('~^\[contact-form-7\s+[^\]\r\n]+\]$~i', $value) ? $value : '';
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_discovery_voucher');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tous les champs de ce tableau sont normalisés ou nettoyés individuellement avant update_option.
        $raw = isset($_POST['voucher']) && is_array($_POST['voucher']) ? wp_unslash($_POST['voucher']) : array();
        $old = self::settings();
        $new = $old;
        $new['enabled'] = !empty($raw['enabled']) ? '1' : '0';
        $dept = isset($raw['departments']) && is_string($raw['departments']) ? $raw['departments'] : '';
        $list = preg_split('/[\s,;]+/u', trim($dept), -1, PREG_SPLIT_NO_EMPTY);
        $list = array_filter((array)$list, static function ($v) { return (bool)preg_match('/^[0-9]{2,3}$/D', $v); });
        $new['departments'] = implode(', ', array_unique($list));
        $new['expires_on'] = isset($raw['expires_on']) && is_string($raw['expires_on']) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $raw['expires_on'])
            ? $raw['expires_on'] : '';
        $code = isset($raw['form_shortcode']) && is_string($raw['form_shortcode']) ? $raw['form_shortcode'] : '';
        $new['form_shortcode'] = self::clean_form_shortcode($code);
        foreach (array_keys(self::labels()) as $key) {
            if (isset($raw['texts']['fr'][$key]) && is_string($raw['texts']['fr'][$key])) {
                $new['texts']['fr'][$key] = sanitize_textarea_field($raw['texts']['fr'][$key]);
            }
        }
        update_option(self::OPTION, $new, false);
        wp_safe_redirect(add_query_arg(array('page' => self::PAGE, 'saved' => '1'), admin_url('admin.php')));
        exit;
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        ?>
        <div class="wrap">
            <h1>Bons découverte — établissements scolaires</h1>
            <p>Ce module vérifie un code postal, puis affiche votre formulaire Contact Form 7. Aucun bon n’est attribué automatiquement. Chaque parc conserve ses propres réglages.</p>
            <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Flag de message d'information, sans mutation.
            if (isset($_GET['saved'])) : ?><div class="notice notice-success"><p>Réglages enregistrés.</p></div><?php endif; ?>
            <p><strong>Shortcode :</strong> <code>[parc_bon_decouverte]</code>.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_discovery_voucher">
                <?php wp_nonce_field('parcs_ht_save_discovery_voucher'); ?>
                <h2>Activation et éligibilité</h2>
                <table class="form-table" role="presentation">
                    <tr><th>Offre visible</th><td><label><input type="checkbox" name="voucher[enabled]" value="1" <?php checked($settings['enabled'], '1'); ?>> Activer le bloc public</label></td></tr>
                    <tr><th><label for="voucher-depts">Départements autorisés</label></th><td><textarea id="voucher-depts" name="voucher[departments]" class="large-text" rows="2"><?php echo esc_textarea($settings['departments']); ?></textarea><p class="description">À saisir manuellement pour ce parc : codes séparés par une virgule ou un espace (ex. : 67, 68). Tant que la liste est vide, aucune demande n’est acceptée. Pour les DROM, utiliser trois chiffres (ex. : 971).</p></td></tr>
                    <tr><th><label for="voucher-until">Date de fin facultative</label></th><td><input id="voucher-until" name="voucher[expires_on]" type="date" value="<?php echo esc_attr($settings['expires_on']); ?>"><p class="description">Laisser vide pour ne pas fermer automatiquement l’offre.</p></td></tr>
                    <tr><th><label for="voucher-shortcode">Formulaire Contact Form 7</label></th><td><input id="voucher-shortcode" class="large-text" name="voucher[form_shortcode]" value="<?php echo esc_attr($settings['form_shortcode']); ?>" placeholder='[contact-form-7 id="123" title="Bon découverte"]'><p class="description">Créer le formulaire dans CF7, avec impérativement un champ <code>[text* bon_code_postal readonly]</code>, puis copier son shortcode ici. Configurer deux e-mails dans CF7 : notification interne et copie de confirmation au demandeur. Aucun PDF joint.</p></td></tr>
                </table>
                <h2>Textes publics modifiables</h2>
                <p>Les messages de confirmation après envoi se règlent dans Contact Form 7.</p>
                <table class="form-table" role="presentation">
                    <?php foreach (self::labels() as $key=>$label_field) : $value = (string)($settings['texts']['fr'][$key] ?? ''); ?>
                    <tr><th><label for="<?php echo esc_attr('voucher-'.$key); ?>"><?php echo esc_html($label_field); ?></label></th><td>
                        <textarea id="<?php echo esc_attr('voucher-'.$key); ?>" name="voucher[texts][fr][<?php echo esc_attr($key); ?>]" class="large-text" rows="<?php echo in_array($key, array('intro','conditions'), true) ? '3' : '2'; ?>"><?php echo esc_textarea($value); ?></textarea>
                    </td></tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button('Enregistrer les bons découverte'); ?>
            </form>
        </div>
        <?php
    }
}
