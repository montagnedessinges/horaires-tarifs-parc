<?php

if (!defined('ABSPATH')) { exit; }

/** Tarifs par saison, offres temporaires et brouillons. */
final class Parcs_HT_Tariff_Seasons {
    private static $filtering = false;

    public static function init() {
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'store_season_tariffs'), 40, 3);
        add_filter('option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'select_season_tariffs'), 20, 1);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 80);
        add_action('wp_footer', array(__CLASS__, 'frontend_assets'), 30);
        add_action('wp_footer', array(__CLASS__, 'render_offer_popup'), 40);
    }

    public static function store_season_tariffs($new_value, $old_value, $option) {
        unset($option);
        if (!is_array($new_value) || !is_admin()) return $new_value;
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save')) return $new_value;
        if (!isset($_POST['action']) || sanitize_key(wp_unslash($_POST['action'])) !== 'parcs_ht_save') return $new_value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || !isset($new_value['seasons'][$year])) return $new_value;

        $tariffs = isset($new_value['tariffs']) && is_array($new_value['tariffs']) ? $new_value['tariffs'] : array();
        $raw = isset($_POST['settings']['tariffs']) && is_array($_POST['settings']['tariffs']) ? map_deep(wp_unslash($_POST['settings']['tariffs']), 'sanitize_textarea_field') : array();
        foreach (array('individual','reduced','groups') as $group) {
            if (isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group])) {
                foreach ($tariffs['columns'][$group] as $i => &$column) {
                    $posted = isset($raw['columns'][$group][$i]) && is_array($raw['columns'][$group][$i]) ? $raw['columns'][$group][$i] : array();
                    $column['visible'] = isset($posted['visible']) && (string)$posted['visible'] === '0' ? '0' : '1';
                }
                unset($column);
            }
            if (!isset($tariffs[$group]) || !is_array($tariffs[$group])) continue;
            foreach ($tariffs[$group] as $i => &$row) {
                $posted = isset($raw[$group][$i]) && is_array($raw[$group][$i]) ? $raw[$group][$i] : array();
                $row['offer_group'] = isset($posted['offer_group']) ? sanitize_text_field($posted['offer_group']) : '';
                $row['offer_popup'] = isset($posted['offer_popup']) && (string)$posted['offer_popup'] === '1' ? '1' : '0';
                $row['offer_popup_title'] = self::translations($posted['offer_popup_title'] ?? array());
                $row['offer_popup_message'] = self::translations($posted['offer_popup_message'] ?? array(), true);
                $row['offer_popup_button_label'] = self::translations($posted['offer_popup_button_label'] ?? array());
                $row['offer_popup_button_url'] = self::urls($posted['offer_popup_button_url'] ?? array());
            }
            unset($row);
        }
        $new_value['seasons'][$year]['tariffs'] = $tariffs;
        $published = (string)($new_value['seasons'][$year]['published'] ?? '0') === '1';
        $new_value['tariffs'] = (!$published && is_array($old_value) && isset($old_value['tariffs'])) ? $old_value['tariffs'] : $tariffs;
        return $new_value;
    }

    private static function hide_unpublished_groups($tariffs, $year) {
        if (!is_array($tariffs)) $tariffs = array();
        if ($year === '' || !class_exists('Parcs_HT_Group_Tariff_Settings') || in_array((string)$year, Parcs_HT_Group_Tariff_Settings::public_years(), true)) return $tariffs;
        $tariffs['groups'] = array();
        if (!isset($tariffs['columns']) || !is_array($tariffs['columns'])) $tariffs['columns'] = array();
        $tariffs['columns']['groups'] = array();
        return $tariffs;
    }

    public static function select_season_tariffs($value, $public = false) {
        if (self::$filtering || !is_array($value) || empty($value['seasons']) || !is_array($value['seasons'])) return $value;
        self::$filtering = true;
        $year = self::requested_year($value, $public);
        if ($year !== '' && isset($value['seasons'][$year]['tariffs']) && is_array($value['seasons'][$year]['tariffs'])) {
            $value['tariffs'] = $value['seasons'][$year]['tariffs'];
            if ($public) {
                if (class_exists('Parcs_HT_Display_Policy')) $value['tariffs'] = Parcs_HT_Display_Policy::normalize_tariffs($value['tariffs']);
                $value['tariffs'] = self::hide_unpublished_groups($value['tariffs'], $year);
            }
            if (isset($value['general']) && is_array($value['general'])) $value['general']['year'] = $year;
            $value['active_season_year'] = $year;
        }
        if ($public && $year === '') {
            // Sans saison tarifaire autorisée, aucun ancien tarif global ne doit servir de secours public.
            $value['tariffs'] = array('individual'=>array(),'reduced'=>array(),'groups'=>array(),'columns'=>array('individual'=>array(),'reduced'=>array(),'groups'=>array()),'notes'=>array(),'payment_methods'=>array(),'payment_items'=>array(),'print'=>array());
        }
        // La visibilité est appliquée au rendu, jamais à l'option pouvant être réenregistrée.
        self::$filtering = false;
        return $value;
    }

    private static function requested_year($settings, $public = false) {
        global $pagenow;
        // admin-post.php est aussi public : is_admin() seul n'autorise pas un aperçu brouillon.
        if (!$public && is_admin() && current_user_can('manage_options') && $pagenow === 'admin.php' && isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === 'parcs-horaires-tarifs' && isset($_GET['season'])) {
            $year = sanitize_text_field(wp_unslash($_GET['season']));
            if (preg_match('/^20\d{2}$/', $year) && isset($settings['seasons'][$year])) return $year;
        }
        if (!$public && is_admin() && current_user_can('manage_options') && isset($_POST['action'], $_POST['_wpnonce']) && sanitize_key(wp_unslash($_POST['action'])) === 'parcs_ht_save' && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'parcs_ht_save') && isset($_POST['season_year'])) {
            $year = sanitize_text_field(wp_unslash($_POST['season_year']));
            if (preg_match('/^20\d{2}$/', $year) && isset($settings['seasons'][$year])) return $year;
        }

        if ($public && class_exists('Parcs_HT_Display_Policy')) {
            $years = Parcs_HT_Display_Policy::retail_years();
            $requested = isset($_GET['htp_year']) ? sanitize_text_field(wp_unslash($_GET['htp_year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection publique en lecture seule.
            if (preg_match('/^20\d{2}$/', $requested) && in_array($requested, $years, true)) return $requested;

            $current = wp_date('Y');
            if (in_array($current, $years, true)) return $current;

            $active = (string)($settings['active_season_year'] ?? ($settings['general']['year'] ?? ''));
            if ($active !== '' && in_array($active, $years, true)) return $active;

            return $years ? (string)end($years) : '';
        }

        $today = wp_date('Y-m-d', null, new DateTimeZone(isset($settings['timezone']) ? (string)$settings['timezone'] : 'Europe/Paris'));
        $candidate = '';
        foreach ($settings['seasons'] as $year => $season) {
            if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;
            $start = (string)($season['season_start'] ?? ''); $end = (string)($season['season_end'] ?? '');
            if ($start !== '' && $end !== '' && $today >= $start && $today <= $end) return (string)$year;
            if ($candidate === '' || (int)$year > (int)$candidate) $candidate = (string)$year;
        }
        return $candidate;
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_parcs-horaires-tarifs') return;
        $settings = Parcs_HT_Defaults::settings();
        wp_enqueue_script('parcs-ht-tariff-seasons-admin', PARCS_HT_URL . 'assets/tariff-seasons-admin.js', array('parcs-ht-admin'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-tariff-seasons-admin', 'window.ParcsHTTariffSeasonAdmin=' . wp_json_encode(array('tariffs'=>$settings['tariffs'] ?? array())) . ';', 'before');
    }

    public static function frontend_assets() {
        if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        $settings = self::select_season_tariffs(Parcs_HT_Defaults::settings(), true); $meta = array();
        foreach (array('individual','reduced','groups') as $group) {
            $meta[$group] = array();
            foreach ((array)($settings['tariffs'][$group] ?? array()) as $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || !self::row_visible_now($row, $settings)) continue;
                $meta[$group][] = array('special'=>(string)($row['row_type'] ?? 'standard') === 'special','offer'=>(string)($row['offer_group'] ?? ''));
            }
        }
        wp_enqueue_script('parcs-ht-tariff-seasons-frontend', PARCS_HT_URL . 'assets/tariff-seasons-frontend.js', array('parcs-ht-frontend'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-tariff-seasons-frontend', 'window.ParcsHTTariffOffers=' . wp_json_encode($meta) . ';', 'before');
    }

    private static function row_visible_now($row, $settings) {
        if ((string)($row['row_type'] ?? 'standard') !== 'special') return true;
        $today = wp_date('Y-m-d', null, new DateTimeZone(isset($settings['timezone']) ? (string)$settings['timezone'] : 'Europe/Paris'));
        $from = (string)($row['display_from'] ?? ''); $to = (string)($row['display_to'] ?? '');
        return !($from !== '' && $today < $from) && !($to !== '' && $today > $to);
    }

    public static function render_offer_popup() {
        if (is_admin() || !wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        $settings = self::select_season_tariffs(Parcs_HT_Defaults::settings(), true); $language = Parcs_HT_Schedule::language(); $seen = array();
        foreach (array('individual','reduced','groups') as $group) foreach ((array)($settings['tariffs'][$group] ?? array()) as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['row_type'] ?? '') !== 'special' || (string)($row['offer_popup'] ?? '0') !== '1' || !self::row_visible_now($row, $settings)) continue;
            $offer = trim((string)($row['offer_group'] ?? '')); $key = $offer !== '' ? sanitize_key($offer) : $group . '-' . md5(wp_json_encode($row));
            if (isset($seen[$key])) continue; $seen[$key] = true;
            $title = Parcs_HT_Schedule::translation((array)($row['offer_popup_title'] ?? array()), $language, '');
            $message = Parcs_HT_Schedule::translation((array)($row['offer_popup_message'] ?? array()), $language, '');
            $button = Parcs_HT_Schedule::translation((array)($row['offer_popup_button_label'] ?? array()), $language, '');
            $url = Parcs_HT_Schedule::translation((array)($row['offer_popup_button_url'] ?? array()), $language, '');
            if ($title === '' && $message === '') continue;
            $id = 'parcs-ht-offer-popup-' . substr(md5($key), 0, 10); ?>
            <div id="<?php echo esc_attr($id); ?>" class="parcs-ht-offer-popup" data-htp-offer-popup data-storage-key="<?php echo esc_attr('htp_offer_' . $key); ?>" hidden><div class="parcs-ht-offer-popup-overlay" data-htp-offer-close></div><div class="parcs-ht-offer-popup-dialog" role="dialog" aria-modal="true"<?php echo $title !== '' ? ' aria-labelledby="' . esc_attr($id . '-title') . '"' : ''; ?>><button type="button" class="parcs-ht-offer-popup-close" data-htp-offer-close aria-label="Fermer">×</button><?php if ($title !== '') : ?><h2 id="<?php echo esc_attr($id . '-title'); ?>"><?php echo esc_html($title); ?></h2><?php endif; ?><?php if ($message !== '') : ?><p><?php echo nl2br(esc_html($message)); ?></p><?php endif; ?><?php if ($button !== '' && $url !== '') : ?><a class="parcs-ht-offer-popup-button" href="<?php echo esc_url($url); ?>"><?php echo esc_html($button); ?></a><?php endif; ?></div></div>
            <style>.parcs-ht-offer-popup{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px}.parcs-ht-offer-popup[hidden]{display:none}.parcs-ht-offer-popup-overlay{position:absolute;inset:0;background:rgba(0,0,0,.65)}.parcs-ht-offer-popup-dialog{position:relative;z-index:1;width:min(560px,100%);background:#fff;color:#222;border-radius:16px;padding:28px;box-shadow:0 18px 60px rgba(0,0,0,.25)}.parcs-ht-offer-popup-close{position:absolute;top:10px;right:12px;border:0;background:transparent;font-size:28px;cursor:pointer}.parcs-ht-offer-popup-button{display:inline-block;margin-top:12px;padding:10px 16px;border-radius:8px;background:var(--htp-primary,#006757);color:#fff;text-decoration:none}</style>
            <script>(function(){var el=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!el)return;var key=el.getAttribute('data-storage-key'),last=0;try{last=parseInt(localStorage.getItem(key)||'0',10)||0;}catch(e){}if(Date.now()-last>3600000)el.hidden=false;el.querySelectorAll('[data-htp-offer-close]').forEach(function(btn){btn.addEventListener('click',function(){el.hidden=true;try{localStorage.setItem(key,String(Date.now()));}catch(e){}});});}());</script><?php
            return;
        }
    }

    private static function translations($values, $textarea = false) { $out=array('fr'=>'','en'=>'','de'=>''); if(!is_array($values))return$out; foreach($out as $lang=>$unused){$value=isset($values[$lang])?(string)$values[$lang]:'';$out[$lang]=$textarea?sanitize_textarea_field($value):sanitize_text_field($value);} return$out; }
    private static function urls($values) { $out=array('fr'=>'','en'=>'','de'=>''); if(!is_array($values))return$out; foreach($out as $lang=>$unused)$out[$lang]=isset($values[$lang])?esc_url_raw((string)$values[$lang]):''; return$out; }
}
