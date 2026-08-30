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
            'closed_message' => "Le parc est fermé au public à cette date. Une visite de groupe peut toutefois être possible. Merci de nous contacter en nous indiquant la date et l’horaire souhaités afin que nous puissions vérifier si nous pouvons vous accueillir. Vous pouvez malgré tout générer votre devis automatiquement ci-dessous.",
            'closed_contact' => 'info@montagnedessinges.com',
            'unavailable_enabled' => '1',
            'unavailable_message' => "Les tarifs groupes ne sont pas encore disponibles pour l’année correspondant à cette date. Le devis automatique ne peut pas encore être généré.",
            'unavailable_contact' => 'info@montagnedessinges.com',
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        return array_merge(self::defaults(), is_array($saved) ? $saved : array());
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
        <?php if (isset($_GET['updated'])) : ?><div class="notice notice-success is-dismissible"><p>Les réglages ont été enregistrés.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="parcs_ht_save_quote_gate"><?php wp_nonce_field('parcs_ht_save_quote_gate'); ?>
        <table class="form-table" role="presentation">
        <tr><th>Étape préalable par date</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked($s['enabled'], '1'); ?>> Activer le choix de la date avant l’affichage du formulaire complet</label></td></tr>
        <tr><th>Date où le parc est fermé</th><td><label><input type="checkbox" name="closed_enabled" value="1" <?php checked($s['closed_enabled'], '1'); ?>> Afficher un avertissement</label><p><textarea class="large-text" rows="5" name="closed_message"><?php echo esc_textarea($s['closed_message']); ?></textarea></p><label>Contact ou lien <input class="regular-text" name="closed_contact" value="<?php echo esc_attr($s['closed_contact']); ?>"></label><p class="description">Le formulaire reste accessible et le devis reste générable.</p></td></tr>
        <tr><th>Tarifs indisponibles</th><td><label><input type="checkbox" name="unavailable_enabled" value="1" <?php checked($s['unavailable_enabled'], '1'); ?>> Afficher le message d’indisponibilité</label><p><textarea class="large-text" rows="5" name="unavailable_message"><?php echo esc_textarea($s['unavailable_message']); ?></textarea></p><label>Contact ou lien <input class="regular-text" name="unavailable_contact" value="<?php echo esc_attr($s['unavailable_contact']); ?>"></label><p class="description">Sans grille tarifaire publiée pour l’année choisie, le formulaire complet reste masqué et aucun devis chiffré ne peut être envoyé.</p></td></tr>
        </table><?php submit_button('Enregistrer'); ?></form></div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_quote_gate');
        $out = array(
            'enabled' => isset($_POST['enabled']) ? '1' : '0',
            'closed_enabled' => isset($_POST['closed_enabled']) ? '1' : '0',
            'closed_message' => isset($_POST['closed_message']) ? sanitize_textarea_field(wp_unslash($_POST['closed_message'])) : '',
            'closed_contact' => isset($_POST['closed_contact']) ? sanitize_text_field(wp_unslash($_POST['closed_contact'])) : '',
            'unavailable_enabled' => isset($_POST['unavailable_enabled']) ? '1' : '0',
            'unavailable_message' => isset($_POST['unavailable_message']) ? sanitize_textarea_field(wp_unslash($_POST['unavailable_message'])) : '',
            'unavailable_contact' => isset($_POST['unavailable_contact']) ? sanitize_text_field(wp_unslash($_POST['unavailable_contact'])) : '',
        );
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

    public static function date_status() {
        check_ajax_referer('parcs_ht_quote_gate', 'nonce');
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
        if (!preg_match('/^(20\d{2})-\d{2}-\d{2}$/', $date)) wp_send_json_error(array('message'=>'Date invalide.'), 400);
        $year = substr($date, 0, 4);
        $tariffs = self::tariff_available($year);
        $closed = false;
        $all = get_option(Parcs_HT_Defaults::OPTION, array());
        if (is_array($all) && isset($all['seasons'][$year]) && is_array($all['seasons'][$year])) {
            $season = $all['seasons'][$year];
            $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
            $status = Parcs_HT_Schedule::resolve_day($season, $general, $date, Parcs_HT_Schedule::timezone($general));
            $closed = empty($status['open']);
        }
        wp_send_json_success(array('tariffs'=>$tariffs,'closed'=>$closed,'year'=>$year));
    }

    public static function assets() {
        $s = self::settings();
        if ((string)$s['enabled'] !== '1') return;
        $quote_settings = class_exists('Parcs_HT_Group_Quotes') ? Parcs_HT_Group_Quotes::settings() : array();
        $visit_field = (string)($quote_settings['visit_field'] ?? 'visite');
        $group_field = (string)($quote_settings['group_field'] ?? 'groupedevis');
        wp_enqueue_style('parcs-ht-quote-gate', PARCS_HT_URL . 'assets/quote-gate.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-quote-gate', PARCS_HT_URL . 'assets/quote-gate.js', array('jquery','parcs-ht-group-quotes'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-quote-gate', 'window.ParcsHTQuoteGate=' . wp_json_encode(array(
            'ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('parcs_ht_quote_gate'),
            'visitField'=>$visit_field,'groupField'=>$group_field,
            'closedEnabled'=>(string)$s['closed_enabled']==='1','closedMessage'=>(string)$s['closed_message'],'closedContact'=>(string)$s['closed_contact'],
            'unavailableEnabled'=>(string)$s['unavailable_enabled']==='1','unavailableMessage'=>(string)$s['unavailable_message'],'unavailableContact'=>(string)$s['unavailable_contact'],
        )) . ';', 'before');
    }
}
