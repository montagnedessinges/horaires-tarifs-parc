<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Outil de mise à jour contrôlée du formulaire CF7 français des devis groupes.
 *
 * La mise à jour n'est jamais appliquée automatiquement : un administrateur doit
 * explicitement cliquer sur le bouton depuis l'écran Devis groupes. Le formulaire
 * courant et ses conditions sont sauvegardés avant remplacement.
 */
final class Parcs_HT_CF7_Form_11712 {
    const ACTION = 'parcs_ht_apply_cf7_form_11712';
    const BACKUP_OPTION = 'parcs_ht_cf7_form_backup_11712';

    public static function init() {
        if (!is_admin()) return;
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_action('admin_post_' . self::ACTION, array(__CLASS__, 'apply'));
    }

    private static function is_quotes_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- contexte d'écran en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        return class_exists('Parcs_HT_Admin_Group_Quotes_1177') && $page === Parcs_HT_Admin_Group_Quotes_1177::PAGE;
    }

    private static function fr_shortcode() {
        $forms = class_exists('Parcs_HT_Quote_Languages') ? Parcs_HT_Quote_Languages::settings() : array();
        $shortcode = trim((string)($forms['fr'] ?? ''));
        if ($shortcode !== '') return $shortcode;

        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        return is_array($all) && isset($all['quote_page']['form_shortcode'])
            ? trim((string)$all['quote_page']['form_shortcode'])
            : '';
    }

    private static function form_id_from_shortcode($shortcode) {
        $shortcode = (string)$shortcode;
        if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $shortcode, $match)) return trim($match[1]);
        if (preg_match('/\bid\s*=\s*([^\s\]]+)/i', $shortcode, $match)) return trim($match[1], " \t\n\r\0\x0B\"'");
        return '';
    }

    private static function form_instance() {
        if (!class_exists('WPCF7_ContactForm')) return null;
        $id = self::form_id_from_shortcode(self::fr_shortcode());
        if ($id === '') return null;
        return WPCF7_ContactForm::get_instance(ctype_digit($id) ? (int)$id : $id);
    }

    private static function current_form_markup($form) {
        if (!is_object($form) || !method_exists($form, 'prop')) return '';
        return (string)$form->prop('form');
    }

    private static function form_is_current($form) {
        $markup = self::current_form_markup($form);
        if ($markup === '') return false;
        foreach (array(
            'ageenfants', 'heurevisite', 'responsablejour_nom', 'responsablejour_tel',
            'langues', 'langueautre', 'moyenpaiement', 'typediffere',
            'siretfacturation', 'codeservice', 'referenceengagement'
        ) as $field) {
            if (strpos($markup, $field) === false) return false;
        }
        return true;
    }

    public static function notice() {
        if (!self::is_quotes_page() || !current_user_can('manage_options')) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message de résultat uniquement.
        $updated = isset($_GET['cf7_11712']) ? sanitize_key(wp_unslash($_GET['cf7_11712'])) : '';
        if ($updated === 'updated') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Formulaire CF7 français mis à jour.</strong> Le modèle 1.17.12 et ses règles conditionnelles ont été enregistrés. Le PDF existant n’a pas été modifié.</p></div>';
            return;
        }

        $shortcode = self::fr_shortcode();
        if ($shortcode === '') {
            echo '<div class="notice notice-warning"><p><strong>Formulaire CF7 1.17.12 :</strong> aucun formulaire français n’est configuré. Enregistrez d’abord son shortcode dans « Formulaires Contact Form 7 ».</p></div>';
            return;
        }

        if (!class_exists('WPCF7_ContactForm')) {
            echo '<div class="notice notice-warning"><p><strong>Formulaire CF7 1.17.12 :</strong> Contact Form 7 doit être actif pour appliquer le nouveau modèle.</p></div>';
            return;
        }

        if (!class_exists('CF7CF') || !method_exists('CF7CF', 'parse_conditions') || !method_exists('CF7CF', 'setConditions')) {
            echo '<div class="notice notice-warning"><p><strong>Formulaire CF7 1.17.12 :</strong> Conditional Fields for Contact Form 7 doit être actif pour enregistrer les groupes conditionnels.</p></div>';
            return;
        }

        $form = self::form_instance();
        if (!$form) {
            echo '<div class="notice notice-error"><p><strong>Formulaire CF7 1.17.12 :</strong> le formulaire français configuré n’a pas pu être retrouvé à partir de son shortcode.</p></div>';
            return;
        }

        if (self::form_is_current($form)) {
            echo '<div class="notice notice-success"><p><strong>Formulaire CF7 français :</strong> les champs du modèle 1.17.12 sont déjà présents. Aucune action n’est nécessaire.</p></div>';
            return;
        }

        $url = admin_url('admin-post.php');
        echo '<div class="notice notice-info"><p><strong>Mise à jour CF7 1.17.12 disponible.</strong> Cette action remplace uniquement le contenu de l’onglet Formulaire du CF7 français et ses règles Conditional Fields. Les réglages e-mail, messages et le PDF restent inchangés. Une sauvegarde du formulaire actuel est créée avant modification.</p>';
        echo '<form method="post" action="' . esc_url($url) . '" style="margin:0 0 12px">';
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '">';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- conservation du contexte d'année uniquement.
        $year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : '';
        if (preg_match('/^20\d{2}$/', $year)) echo '<input type="hidden" name="season" value="' . esc_attr($year) . '">';
        wp_nonce_field(self::ACTION);
        submit_button('Mettre à jour le formulaire CF7 français', 'primary', 'submit', false);
        echo '</form></div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer(self::ACTION);

        if (!class_exists('WPCF7_ContactForm')) wp_die('Contact Form 7 n’est pas actif.');
        if (!class_exists('CF7CF') || !method_exists('CF7CF', 'parse_conditions') || !method_exists('CF7CF', 'setConditions')) {
            wp_die('Conditional Fields for Contact Form 7 n’est pas actif ou ne fournit pas l’API attendue.');
        }

        $form = self::form_instance();
        if (!$form) wp_die('Le formulaire CF7 français configuré est introuvable.');

        $form_id = method_exists($form, 'id') ? $form->id() : 0;
        if (!$form_id) wp_die('Identifiant du formulaire CF7 introuvable.');

        $conditions = CF7CF::parse_conditions(self::conditions_text());
        if (!is_array($conditions)) wp_die('Les règles conditionnelles 1.17.12 n’ont pas pu être préparées.');

        $backup = array(
            'created_at' => current_time('mysql'),
            'form_id' => $form_id,
            'form' => self::current_form_markup($form),
            'conditions' => method_exists('CF7CF', 'getConditions') ? CF7CF::getConditions($form_id) : get_post_meta($form_id, 'wpcf7cf_options', true),
        );
        update_option(self::BACKUP_OPTION, $backup, false);

        $form->set_properties(array('form' => self::form_template()));
        $saved = $form->save();
        if ($saved === false) wp_die('Contact Form 7 n’a pas confirmé l’enregistrement du nouveau formulaire. La sauvegarde précédente est conservée.');

        CF7CF::setConditions($form_id, $conditions);
        do_action('litespeed_purge_all');

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- valeur issue du formulaire signé par nonce et utilisée uniquement comme contexte de redirection.
        $year = isset($_POST['season']) ? sanitize_text_field(wp_unslash($_POST['season'])) : '';
        $args = array('page' => Parcs_HT_Admin_Group_Quotes_1177::PAGE, 'cf7_11712' => 'updated');
        if (preg_match('/^20\d{2}$/', $year)) $args['season'] = $year;
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function conditions_text() {
        return implode("\n\n", array(
            'show [group-scolaire] if [groupedevis] equals "Groupe"',
            'show [group-handicape] if [groupedevis] equals "Groupe en situation de handicap"',
            'show [group-langue-autre] if [langues] equals "Autre"',
            'show [group-paiement-differe] if [moyenpaiement] equals "Paiement différé"',
            'show [group-chorus] if [typediffere] equals "Chorus Pro"',
        ));
    }

    public static function form_template() {
        return <<<'CF7'
<div class="flex-xbetween">

  [hidden devisannee]
  [hidden tarifenfant]
  [hidden tarifadulte]
  [hidden tarifhandicap]
  [hidden tarifaccompagnateur]

  <div class="cp full">
    <label>Votre groupe*</label>
    [select* groupedevis include_blank "Groupe" "Groupe en situation de handicap"]
  </div>

  [group group-scolaire clear_on_hide]

  <div class="cp">
    <label>Nombre d'enfants (3 à 18 ans)</label>
    [number nbrenfants class:nbrenfants min:0]
  </div>

  <div class="cp">
    <label>Nombre d'adultes</label>
    [number nbradultes class:nbradultes min:0]
  </div>

  <div class="cp">
    <label>Nombre d'adultes gratuits</label>
    [text nbradultgratuit class:nbradultgratuit "0"]
  </div>

  <div class="cp">
    <label>Nombre d'adultes payants</label>
    [text nbradultpayant class:nbradultpayant "0"]
  </div>

  <div class="cp">
    <label>Total billets enfants</label>
    [text nbrprixenfants class:nbrprixenfants]
  </div>

  <div class="cp">
    <label>Total billets adultes</label>
    [text nbrprixadultes class:nbrprixadultes]
  </div>

  <div class="cp full">
    <label>Total Global TTC</label>
    [text totalprixscolaire class:totalprixscolaire]
  </div>

  <div class="cp full">
    <label>Tranche(s) d'âge des enfants</label>
    [checkbox ageenfants use_label_element "3-5 ans" "6-10 ans" "11-14 ans" "15-18 ans"]
    <small>Plusieurs choix possibles.</small>
  </div>

  [/group]

  [group group-handicape clear_on_hide]

  <div class="cp">
    <label>Nombre de personnes en situation de handicap</label>
    [number nbrpersohandicape class:nbrpersohandicape min:0]
  </div>

  <div class="cp">
    <label>Nombre d'accompagnateurs</label>
    [number nbraccompa class:nbraccompa min:0]
  </div>

  <div class="cp full">
    <label>Total Global TTC</label>
    [text totalprixhandicape class:totalprixhandicape readonly]
  </div>

  [/group]

  <div class="cp full">
    <label>Votre organisme*</label>
    [text* organisme]
  </div>

  <div class="cp">
    <label>Nom du contact*</label>
    [text* nom]
  </div>

  <div class="cp">
    <label>Email*</label>
    [email* emailform]
  </div>

  <div class="cp">
    <label>Téléphone*</label>
    [tel* telephone]
  </div>

  <div class="cp full">
    <label>Adresse*</label>
    [text* adresse]
  </div>

  <div class="cp">
    <label>Code postal</label>
    [text postal]
  </div>

  <div class="cp">
    <label>Ville</label>
    [text city]
  </div>

  <div class="cp">
    <label>Pays</label>
    [text country]
  </div>

  <div class="cp">
    <label>Date de visite*</label>
    [date* visite]
  </div>

  <div class="cp">
    <label>Heure d'arrivée prévue*</label>
    [text* heurevisite placeholder "Ex. : 10h00"]
  </div>

  <div class="cp full">
    <p><strong>Responsable du groupe le jour de la visite</strong><br>
    Si vous connaissez déjà la personne responsable le jour de la visite, vous pouvez renseigner ses coordonnées. Sinon, vous pourrez les compléter ultérieurement sur le devis.</p>
  </div>

  <div class="cp">
    <label>Nom du responsable le jour de la visite</label>
    [text responsablejour_nom]
  </div>

  <div class="cp">
    <label>Téléphone du responsable le jour de la visite</label>
    [tel responsablejour_tel]
  </div>

  <div class="cp full">
    <label>Langue(s) du groupe</label>
    [checkbox langues use_label_element default:1 "Français" "Allemand" "Anglais" "Autre"]
    <small>Plusieurs choix possibles.</small>
  </div>

  [group group-langue-autre clear_on_hide]

  <div class="cp full">
    <label>Précisez la ou les autres langues</label>
    [text langueautre]
  </div>

  [/group]

  <div class="cp full">
    <label>Moyen de paiement prévu*</label>
    [radio moyenpaiement use_label_element "Paiement sur place" "Paiement différé"]
  </div>

  [group group-paiement-differe clear_on_hide]

  <div class="cp full">
    <label>Type de paiement différé*</label>
    [radio typediffere use_label_element "Voucher" "Bon de commande" "Chorus Pro"]
  </div>

  [group group-chorus clear_on_hide]

  <div class="cp full">
    <p><strong>Informations pour la facturation via Chorus Pro</strong><br>
    Si vous connaissez ces informations, vous pouvez les renseigner maintenant. Sinon, laissez les champs vides : ils pourront être complétés ultérieurement sur le devis.</p>
  </div>

  <div class="cp">
    <label>N° SIRET de facturation</label>
    [text siretfacturation maxlength:14]
  </div>

  <div class="cp">
    <label>Code service</label>
    [text codeservice]
  </div>

  <div class="cp full">
    <label>Référence d'engagement / N° de bon de commande</label>
    [text referenceengagement]
  </div>

  [/group]
  [/group]

  <div class="cp full">
    [acceptance acceptance-319]
    J’accepte la politique de confidentialité.
    [/acceptance]
  </div>

  <div class="cp full">
    <div class="wp-txt-center">
      [submit "Générer mon devis"]
    </div>
  </div>

</div>
CF7;
    }
}
