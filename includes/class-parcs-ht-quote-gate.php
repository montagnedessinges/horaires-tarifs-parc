<?php

if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Quote_Gate {
    const OPTION = 'parcs_ht_quote_gate';
    const PAGE = 'parcs-ht-quote-gate';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save_quote_gate', array(__CLASS__, 'save'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'), 31);
        add_action('wp_ajax_parcs_ht_quote_date_status', array(__CLASS__, 'date_status'));
        add_action('wp_ajax_nopriv_parcs_ht_quote_date_status', array(__CLASS__, 'date_status'));
    }

    public static function defaults() {
        return array(
            'enabled' => '1',
            'closed_enabled' => '1',
            'closed_message_fr' => "Le parc est fermé au public à cette date. Une visite de groupe peut toutefois être possible. Merci de nous contacter en nous indiquant la date et l’horaire souhaités afin que nous puissions vérifier si nous pouvons vous accueillir. Vous pouvez malgré tout générer votre devis automatiquement ci-dessous.",
            'closed_message_en' => "The park is closed to the public on this date. A group visit may nevertheless be possible. Please contact us with your preferred date and time so that we can check whether we are able to welcome you. You can still generate your quote automatically below.",
            'closed_message_de' => "Der Park ist an diesem Datum für die Öffentlichkeit geschlossen. Ein Gruppenbesuch kann dennoch möglich sein. Bitte kontaktieren Sie uns mit Ihrem gewünschten Datum und Ihrer gewünschten Uhrzeit, damit wir prüfen können, ob wir Sie empfangen können. Sie können Ihr Angebot trotzdem unten automatisch erstellen.",
            'closed_contact' => 'info@montagnedessinges.com',
            'unavailable_enabled' => '1',
            'unavailable_message_fr' => "Les tarifs groupes ne sont pas encore disponibles pour l’année correspondant à cette date. Le devis automatique ne peut pas encore être généré.",
            'unavailable_message_en' => "Group rates are not yet available for the year corresponding to this date. The automatic quote cannot be generated yet.",
            'unavailable_message_de' => "Die Gruppentarife für das Jahr, das diesem Datum entspricht, sind noch nicht verfügbar. Das automatische Angebot kann noch nicht erstellt werden.",
            'unavailable_contact' => 'info@montagnedessinges.com',
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        $saved = is_array($saved) ? $saved : array();
        $out = array_merge(self::defaults(), $saved);

        if (!array_key_exists('closed_message_fr', $saved) && isset($saved['closed_message'])) {
            $out['closed_message_fr'] = (string)$saved['closed_message'];
        }
        if (!array_key_exists('unavailable_message_fr', $saved) && isset($saved['unavailable_message'])) {
            $out['unavailable_message_fr'] = (string)$saved['unavailable_message'];
        }

        return $out;
    }

    private static function request_language() {
        global $post;
        $content = ($post && isset($post->post_content)) ? (string)$post->post_content : '';
        foreach (array('fr','en','de') as $language) {
            if ($content !== '' && (has_shortcode($content, 'parc_devis_groupe_' . $language) || has_shortcode($content, 'parc_devis_' . $language))) {
                return $language;
            }
        }
        $language = Parcs_HT_Schedule::language();
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function localized_message($settings, $prefix, $language) {
        $key = $prefix . '_message_' . $language;
        $fr_key = $prefix . '_message_fr';
        $legacy_key = $prefix . '_message';
        $value = trim((string)($settings[$key] ?? ''));
        if ($value !== '') return $value;
        $value = trim((string)($settings[$fr_key] ?? ''));
        if ($value !== '') return $value;
        return trim((string)($settings[$legacy_key] ?? ''));
    }

    public static function menu() {
        add_submenu_page(Parcs_HT_Admin::PAGE, 'Accès au devis', 'Accès au devis', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $s = self::settings();
        ?>
        <div class="wrap"><h1>Accès au devis automatique</h1>
        <p>Le visiteur choisit d’abord sa date de visite. Le formulaire complet s’affiche ensuite uniquement si une grille de tarifs groupes est disponible pour l’année choisie. Une date de fermeture n’empêche pas de générer un devis : elle affiche seulement un avertissement.</p>
        <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?><div class="notice notice-success is-dismissible"><p>Les réglages ont été enregistrés.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="parcs_ht_save_quote_gate"><?php wp_nonce_field('parcs_ht_save_quote_gate'); ?>
        <table class="form-table" role="presentation">
        <tr><th>Étape préalable par date</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked($s['enabled'], '1'); ?>> Activer le choix de la date avant l’affichage du formulaire complet</label></td></tr>
        <tr><th>Date où le parc est fermé</th><td><label><input type="checkbox" name="closed_enabled" value="1" <?php checked($s['closed_enabled'], '1'); ?>> Afficher un avertissement</label>
        <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?><p><label><strong><?php echo esc_html($label); ?></strong><br><textarea class="large-text" rows="4" name="closed_message_<?php echo esc_attr($lang); ?>"><?php echo esc_textarea($s['closed_message_' . $lang]); ?></textarea></label></p><?php endforeach; ?>
        <label>Contact ou lien <input class="regular-text" name="closed_contact" value="<?php echo esc_attr($s['closed_contact']); ?>"></label><p class="description">Le formulaire reste accessible et le devis reste générable.</p></td></tr>
        <tr><th>Tarifs indisponibles</th><td><label><input type="checkbox" name="unavailable_enabled" value="1" <?php checked($s['unavailable_enabled'], '1'); ?>> Afficher le message d’indisponibilité</label>
        <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?><p><label><strong><?php echo esc_html($label); ?></strong><br><textarea class="large-text" rows="4" name="unavailable_message_<?php echo esc_attr($lang); ?>"><?php echo esc_textarea($s['unavailable_message_' . $lang]); ?></textarea></label></p><?php endforeach; ?>
        <label>Contact ou lien <input class="regular-text" name="unavailable_contact" value="<?php echo esc_attr($s['unavailable_contact']); ?>"></label><p class="description">Sans grille tarifaire publiée pour l’année choisie, le formulaire complet reste masqué et aucun devis chiffré ne peut être envoyé.</p></td></tr>
        </table><?php submit_button('Enregistrer'); ?></form></div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_gate');
        $out = array(
            'enabled' => isset($_POST['enabled']) ? '1' : '0',
            'closed_enabled' => isset($_POST['closed_enabled']) ? '1' : '0',
            'closed_contact' => isset($_POST['closed_contact']) ? sanitize_text_field(wp_unslash($_POST['closed_contact'])) : '',
            'unavailable_enabled' => isset($_POST['unavailable_enabled']) ? '1' : '0',
            'unavailable_contact' => isset($_POST['unavailable_contact']) ? sanitize_text_field(wp_unslash($_POST['unavailable_contact'])) : '',
        );
        foreach (array('fr','en','de') as $lang) {
            $closed_key = 'closed_message_' . $lang;
            $unavailable_key = 'unavailable_message_' . $lang;
            $out[$closed_key] = isset($_POST[$closed_key]) ? sanitize_textarea_field(wp_unslash($_POST[$closed_key])) : '';
            $out[$unavailable_key] = isset($_POST[$unavailable_key]) ? sanitize_textarea_field(wp_unslash($_POST[$unavailable_key])) : '';
        }
        update_option(self::OPTION, $out, false);
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function tariff_available($year) {
        $q = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings() : array();
        $row = is_array($q) && isset($q['seasons'][$year]) && is_array($q['seasons'][$year]) ? $q['seasons'][$year] : array();
        if ((string)($row['published'] ?? '0') !== '1') return false;
        foreach (array('child','adult','disability','companion') as $key) if (!isset($row[$key]) || !is_numeric($row[$key]) || (float)$row[$key] < 0) return false;
        return true;
    }

    private static function valid_date($date) {
        $date = trim((string)$date);
        if (!preg_match('/^(20\d{2})-(\d{2})-(\d{2})$/', $date)) return false;
        $year = (int)substr($date, 0, 4);
        $month = (int)substr($date, 5, 2);
        $day = (int)substr($date, 8, 2);
        return checkdate($month, $day, $year);
    }

    /**
     * Résout le statut d'une date sans dépendre de la visibilité du calendrier public.
     * Règle de sécurité : si aucune saison exploitable n'est trouvée, la date est fermée.
     */
    public static function status_for_date($date) {
        $date = trim((string)$date);
        if (!self::valid_date($date)) {
            return array('valid'=>false, 'tariffs'=>false, 'closed'=>true, 'year'=>'');
        }

        $year = substr($date, 0, 4);
        $tariffs = self::tariff_available($year);
        $closed = true;
        $all = get_option(Parcs_HT_Defaults::OPTION, array());

        if (is_array($all) && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) {
            $season = $all['seasons'][$year];
            $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
            $status = Parcs_HT_Schedule::resolve_day($season, $general, $date, Parcs_HT_Schedule::timezone($general));
            $closed = empty($status['open']);
        }

        return array('valid'=>true, 'tariffs'=>$tariffs, 'closed'=>$closed, 'year'=>$year);
    }

    public static function date_status() {
        check_ajax_referer('parcs_ht_quote_gate', 'nonce');
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
        $status = self::status_for_date($date);
        if (empty($status['valid'])) wp_send_json_error(array('message'=>'Date invalide.'), 400);
        unset($status['valid']);
        wp_send_json_success($status);
    }

    public static function assets() {
        $s = self::settings();
        if ((string)$s['enabled'] !== '1') return;
        $quote_settings = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings() : array();
        $visit_field = (string)($quote_settings['visit_field'] ?? 'visite');
        $group_field = (string)($quote_settings['group_field'] ?? 'groupedevis');
        $language = self::request_language();
        wp_enqueue_style('parcs-ht-quote-gate', PARCS_HT_URL . 'assets/quote-gate.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-quote-gate', PARCS_HT_URL . 'assets/quote-gate.js', array('jquery','parcs-ht-group-quotes'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-quote-gate', 'window.ParcsHTQuoteGate=' . wp_json_encode(array(
            'ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('parcs_ht_quote_gate'),
            'visitField'=>$visit_field,'groupField'=>$group_field,'language'=>$language,
            'closedEnabled'=>(string)$s['closed_enabled']==='1','closedMessage'=>self::localized_message($s, 'closed', $language),'closedContact'=>(string)$s['closed_contact'],
            'unavailableEnabled'=>(string)$s['unavailable_enabled']==='1','unavailableMessage'=>self::localized_message($s, 'unavailable', $language),'unavailableContact'=>(string)$s['unavailable_contact'],
        )) . ';', 'before');
    }
}
