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
            // On existing sites a closed day remains eligible until the setting is changed.
            'allow_closed_group_quotes' => '1',
            'closed_date_exceptions' => array(),
            'blocked_message_fr' => "Le parc est fermé et nous n’accueillons pas de groupes à cette date. Merci de choisir une autre date pour votre visite.",
            'blocked_message_en' => "The park is closed and we cannot welcome groups on this date. Please choose another date for your visit.",
            'blocked_message_de' => "Der Park ist geschlossen und wir können an diesem Tag keine Gruppen empfangen. Bitte wählen Sie ein anderes Besuchsdatum.",
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
        $out['allow_closed_group_quotes'] = (string)($out['allow_closed_group_quotes'] ?? '1') === '0' ? '0' : '1';
        $out['closed_date_exceptions'] = self::sanitize_closed_date_exceptions($out['closed_date_exceptions'] ?? array());

        if (!array_key_exists('closed_message_fr', $saved) && isset($saved['closed_message'])) {
            $out['closed_message_fr'] = (string)$saved['closed_message'];
        }
        if (!array_key_exists('unavailable_message_fr', $saved) && isset($saved['unavailable_message'])) {
            $out['unavailable_message_fr'] = (string)$saved['unavailable_message'];
        }

        return $out;
    }

    /**
     * Inclusive ranges; an empty end date means a single day.
     * These settings apply to the whole WordPress site, across all seasons.
     */
    public static function sanitize_closed_date_exceptions($ranges) {
        $clean = array();
        if (!is_array($ranges)) return $clean;
        foreach ($ranges as $range) {
            if (count($clean) >= 100) break;
            if (!is_array($range)) continue;
            $start = isset($range['start']) && is_scalar($range['start']) ? trim((string)$range['start']) : '';
            $end = isset($range['end']) && is_scalar($range['end']) ? trim((string)$range['end']) : '';
            if ($end === '') $end = $start;
            if (!self::valid_date($start) || !self::valid_date($end) || $end < $start) continue;
            $clean[] = array('start'=>$start, 'end'=>$end);
        }
        return $clean;
    }

    /** The inverse exceptions only apply to fully closed days. */
    public static function group_date_allowed($date, $closed, $gate = null) {
        if (!is_scalar($date) || !self::valid_date($date)) return false;
        if (!$closed) return true;
        $settings = is_array($gate) ? $gate : self::settings();
        $default = (string)($settings['allow_closed_group_quotes'] ?? '1') !== '0';
        $ranges = self::sanitize_closed_date_exceptions($settings['closed_date_exceptions'] ?? array());
        foreach ($ranges as $range) {
            if ($date >= $range['start'] && $date <= $range['end']) return !$default;
        }
        return $default;
    }

    public static function blocked_message_for_request() {
        return self::localized_message(self::settings(), 'blocked', self::request_language());
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
        <p>Le visiteur choisit sa date. Les devis dépendent de la disponibilité des tarifs et des règles d’accueil des groupes pendant les journées entièrement fermées.</p>
        <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?><div class="notice notice-success is-dismissible"><p>Les réglages ont été enregistrés.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="parcs_ht_save_quote_gate"><?php wp_nonce_field('parcs_ht_save_quote_gate'); ?>
        <table class="form-table" role="presentation">
        <tr><th>Étape préalable par date</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked($s['enabled'], '1'); ?>> Activer le choix de la date avant l’affichage du formulaire complet</label></td></tr>
        <tr><th>Devis les jours où le parc est fermé</th><td>
        <p>Autoriser les devis groupes quand le parc est fermé toute la journée ?</p>
        <label><input type="radio" name="allow_closed_group_quotes" value="1" <?php checked($s['allow_closed_group_quotes'], '1'); ?>> Oui</label>
        <label style="margin-left:15px"><input type="radio" name="allow_closed_group_quotes" value="0" <?php checked($s['allow_closed_group_quotes'], '0'); ?>> Non</label>
        <p class="description">Les exceptions inversent la règle uniquement pour les journées entièrement fermées. Les journées ouvertes, même seulement l’après-midi, restent autorisées.</p>
        <?php foreach ($s['closed_date_exceptions'] as $index=>$range) : ?>
        <p><label>Du <input type="date" name="closed_date_exceptions[<?php echo esc_attr($index); ?>][start]" value="<?php echo esc_attr($range['start']); ?>"></label>
        <label>Au <input type="date" name="closed_date_exceptions[<?php echo esc_attr($index); ?>][end]" value="<?php echo esc_attr($range['end']); ?>"></label></p>
        <?php endforeach; ?>
        <p>Pour ajouter une exception, utilisez la rubrique <strong>Gestion du parc → Devis groupes → Accès au devis</strong>.</p>
        </td></tr>
        <tr><th>Message si le devis est interdit</th><td>
        <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?><p><label><strong><?php echo esc_html($label); ?></strong><br><textarea class="large-text" rows="2" name="blocked_message_<?php echo esc_attr($lang); ?>"><?php echo esc_textarea($s['blocked_message_' . $lang]); ?></textarea></label></p><?php endforeach; ?>
        </td></tr>
        <tr><th>Date où le parc est fermé</th><td><label><input type="checkbox" name="closed_enabled" value="1" <?php checked($s['closed_enabled'], '1'); ?>> Afficher un avertissement</label>
        <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?><p><label><strong><?php echo esc_html($label); ?></strong><br><textarea class="large-text" rows="4" name="closed_message_<?php echo esc_attr($lang); ?>"><?php echo esc_textarea($s['closed_message_' . $lang]); ?></textarea></label></p><?php endforeach; ?>
        <label>Contact ou lien <input class="regular-text" name="closed_contact" value="<?php echo esc_attr($s['closed_contact']); ?>"></label><p class="description">Cet avertissement ne s’affiche que lorsque le devis reste autorisé.</p></td></tr>
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
            'allow_closed_group_quotes' => isset($_POST['allow_closed_group_quotes']) && sanitize_text_field(wp_unslash($_POST['allow_closed_group_quotes'])) === '0' ? '0' : '1',
            'closed_date_exceptions' => self::sanitize_closed_date_exceptions(isset($_POST['closed_date_exceptions']) ? wp_unslash($_POST['closed_date_exceptions']) : array()),
            'closed_contact' => isset($_POST['closed_contact']) ? sanitize_text_field(wp_unslash($_POST['closed_contact'])) : '',
            'unavailable_enabled' => isset($_POST['unavailable_enabled']) ? '1' : '0',
            'unavailable_contact' => isset($_POST['unavailable_contact']) ? sanitize_text_field(wp_unslash($_POST['unavailable_contact'])) : '',
        );
        foreach (array('fr','en','de') as $lang) {
            $blocked_key = 'blocked_message_' . $lang;
            $out[$blocked_key] = isset($_POST[$blocked_key]) ? sanitize_textarea_field(wp_unslash($_POST[$blocked_key])) : '';
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

        return array('valid'=>true, 'tariffs'=>$tariffs, 'closed'=>$closed, 'allowed'=>self::group_date_allowed($date, $closed), 'year'=>$year);
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
        // Always display the date gate when a closure policy can refuse a date.
        if ((string)$s['enabled'] !== '1' && (string)$s['allow_closed_group_quotes'] === '1' && !$s['closed_date_exceptions']) return;
        $quote_settings = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings() : array();
        $visit_field = (string)($quote_settings['visit_field'] ?? 'visite');
        $group_field = (string)($quote_settings['group_field'] ?? 'groupedevis');
        $language = self::request_language();
        wp_enqueue_style('parcs-ht-quote-gate', PARCS_HT_URL . 'assets/quote-gate.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-quote-gate', PARCS_HT_URL . 'assets/quote-gate.js', array('jquery','parcs-ht-group-quotes'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-quote-gate', 'window.ParcsHTQuoteGate=' . wp_json_encode(array(
            'ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('parcs_ht_quote_gate'),
            'visitField'=>$visit_field,'groupField'=>$group_field,'language'=>$language,
            'blockedMessage'=>self::localized_message($s, 'blocked', $language),
            'closedEnabled'=>(string)$s['closed_enabled']==='1','closedMessage'=>self::localized_message($s, 'closed', $language),'closedContact'=>(string)$s['closed_contact'],
            'unavailableEnabled'=>(string)$s['unavailable_enabled']==='1','unavailableMessage'=>self::localized_message($s, 'unavailable', $language),'unavailableContact'=>(string)$s['unavailable_contact'],
        )) . ';', 'before');
    }
}
