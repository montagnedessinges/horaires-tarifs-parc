<?php

if (!defined('ABSPATH')) { exit; }

/** Réglages réellement globaux du parc dans l’Administration générale. */
final class Parcs_HT_Admin_General_Park {
    public static function init() {
        add_action('admin_post_parcs_ht_save_general_park', array(__CLASS__, 'save'));
    }

    private static function translations($value) {
        $value = is_array($value) ? $value : array();
        $out = array();
        foreach (array('fr','en','de') as $lang) $out[$lang] = sanitize_text_field((string)($value[$lang] ?? ''));
        return $out;
    }

    private static function urls($value) {
        $value = is_array($value) ? $value : array();
        $out = array();
        foreach (array('fr','en','de') as $lang) $out[$lang] = esc_url_raw((string)($value[$lang] ?? ''));
        return $out;
    }

    private static function timezone($value, $fallback) {
        $value = trim(sanitize_text_field((string)$value));
        if ($value === '') return $fallback;
        try {
            new DateTimeZone($value);
            return $value;
        } catch (Exception $e) {
            return $fallback;
        }
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_general_park');

        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['general']) || !is_array($all['general'])) $all['general'] = array();
        $raw = isset($_POST['park']) && is_array($_POST['park']) ? map_deep(wp_unslash($_POST['park']), 'sanitize_text_field') : array();

        $all['general']['park_name'] = self::translations($raw['park_name'] ?? array());
        $all['general']['tickets_url'] = self::urls($raw['tickets_url'] ?? array());
        $all['general']['groups_url'] = self::urls($raw['groups_url'] ?? array());
        $all['general']['groups_email'] = sanitize_email((string)($raw['groups_email'] ?? ''));
        $all['timezone'] = self::timezone($raw['timezone'] ?? '', (string)($all['timezone'] ?? 'Europe/Paris'));

        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        do_action('litespeed_purge_all');

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        $args = array('page'=>Parcs_HT_Admin_General::PAGE, 'park_updated'=>'1');
        if (preg_match('/^20\d{2}$/', $year)) $args['season'] = $year;
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private static function translated_inputs($name, $value, $label, $type = 'text') {
        $value = is_array($value) ? $value : array();
        ?>
        <fieldset class="htp-park-translations">
            <legend><?php echo esc_html($label); ?></legend>
            <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang => $short) : ?>
                <label><span><?php echo esc_html($short); ?></span><input type="<?php echo esc_attr($type); ?>" name="park[<?php echo esc_attr($name); ?>][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr((string)($value[$lang] ?? '')); ?>"></label>
            <?php endforeach; ?>
        </fieldset>
        <?php
    }

    public static function render($all, $year) {
        $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
        $timezone = (string)($all['timezone'] ?? 'Europe/Paris');
        ?>
        <section class="htp-general-card htp-general-park-card">
            <div class="htp-general-card-head"><div><h2>Parc</h2><p>Informations communes à toute l’installation, indépendantes des années et des catégories métier.</p></div></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_general_park">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_general_park'); ?>

                <div class="htp-park-fields">
                    <?php self::translated_inputs('park_name', $general['park_name'] ?? array(), 'Nom du parc'); ?>
                    <label class="htp-park-field"><span>Fuseau horaire</span><select name="park[timezone]"><?php echo function_exists('wp_timezone_choice') ? wp_timezone_choice($timezone, get_user_locale()) : '<option value="Europe/Paris">Europe/Paris</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML généré par WordPress. ?></select></label>
                    <?php self::translated_inputs('tickets_url', $general['tickets_url'] ?? array(), 'Lien billetterie', 'url'); ?>
                    <?php self::translated_inputs('groups_url', $general['groups_url'] ?? array(), 'Lien groupes', 'url'); ?>
                    <label class="htp-park-field"><span>E-mail groupes</span><input type="email" name="park[groups_email]" value="<?php echo esc_attr((string)($general['groups_email'] ?? '')); ?>"></label>
                </div>
                <p class="description">Les textes publics détaillés et leurs traductions restent dans « Contenus & traductions ». Les réglages d’horaires, tarifs et devis restent dans leurs catégories.</p>
                <?php submit_button('Enregistrer les informations du parc'); ?>
            </form>
        </section>
        <style>
        .htp-park-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.htp-park-field,.htp-park-translations{display:flex;flex-direction:column;gap:7px;margin:0;padding:12px;border:1px solid #dcdcde;border-radius:8px}.htp-park-field>span,.htp-park-translations legend{font-weight:600}.htp-park-translations legend{padding:0 4px}.htp-park-translations label{display:grid;grid-template-columns:32px 1fr;align-items:center;gap:8px}.htp-park-translations label span{font-size:12px;font-weight:600;color:#646970}.htp-park-field select,.htp-park-field input,.htp-park-translations input{width:100%;max-width:none}@media(max-width:900px){.htp-park-fields{grid-template-columns:1fr}}
        </style>
        <?php
    }
}
