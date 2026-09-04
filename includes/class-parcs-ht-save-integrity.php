<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Centralise les sauvegardes sensibles de l'administration.
 *
 * Objectifs :
 * - une seule lecture de la requête avant nettoyage ;
 * - aucune réécriture JavaScript des valeurs au moment du submit ;
 * - relecture systématique après update_option() ;
 * - historique quotidien complet, limité à dix jours.
 */
final class Parcs_HT_Save_Integrity {
    const REVISIONS_OPTION = 'parcs_ht_settings_revisions';
    const PRE_RESTORE_OPTION = 'parcs_ht_pre_restore_backup';
    const MAX_DAILY_BACKUPS = 10;

    private static $restoring = false;

    public static function init() {
        add_action('admin_post_parcs_ht_save_pedagogical_guides', array(__CLASS__, 'save_guides'), 1);
        add_action('admin_post_parcs_ht_save_guide_appearance', array(__CLASS__, 'save_appearance'), 1);
        add_action('admin_post_parcs_ht_restore_revision', array(__CLASS__, 'restore_complete_revision'), 1);
        add_action('updated_option', array(__CLASS__, 'after_option_update'), 100, 3);
        add_action('added_option', array(__CLASS__, 'after_option_add'), 100, 2);
        add_filter('pre_update_option_' . self::REVISIONS_OPTION, array(__CLASS__, 'normalize_revision_option'), 100, 2);
        add_action('admin_init', array(__CLASS__, 'normalize_existing_revisions'), 5);
    }

    private static function request_action() {
        if (isset($_POST['action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Lecture du nom d'action uniquement ; aucune écriture ici.
            return sanitize_key(wp_unslash($_POST['action'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
        if (isset($_GET['action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Lecture du nom d'action uniquement.
            return sanitize_key(wp_unslash($_GET['action'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        return '';
    }

    private static function request_year() {
        if (isset($_POST['season_year'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Utilisé uniquement comme libellé de sauvegarde.
            $year = sanitize_text_field(wp_unslash($_POST['season_year'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (preg_match('/^20\d{2}$/', $year)) return $year;
        }
        $main = get_option(Parcs_HT_Defaults::OPTION, array());
        if (is_array($main)) {
            $year = (string)($main['active_season_year'] ?? '');
            if (preg_match('/^20\d{2}$/', $year)) return $year;
        }
        return (string)wp_date('Y');
    }

    private static function same_value($expected, $actual) {
        return hash('sha256', wp_json_encode($expected)) === hash('sha256', wp_json_encode($actual));
    }

    private static function clean_translation_array($value, $textarea = false) {
        $value = is_array($value) ? $value : array();
        $clean = array('fr'=>'', 'en'=>'', 'de'=>'');
        foreach ($clean as $lang => $unused) {
            $raw = (string)($value[$lang] ?? '');
            $clean[$lang] = $textarea ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        }
        return $clean;
    }

    public static function sanitize_guides_value($raw) {
        $raw = is_array($raw) ? $raw : array();
        $out = array('guides'=>array());
        $allowed_cycles = array('cycle1','cycle2','cycle3','cycle4','multi');
        $allowed_status = array('available','new','coming');

        foreach ((array)($raw['items'] ?? array()) as $item) {
            if (!is_array($item)) continue;

            $cycle = sanitize_key($item['cycle'] ?? 'cycle1');
            if (!in_array($cycle, $allowed_cycles, true)) $cycle = 'cycle1';

            $status = sanitize_key($item['status'] ?? 'available');
            if (!in_array($status, $allowed_status, true)) $status = 'available';

            $languages = array();
            foreach (array('fr','de','en') as $lang) {
                if (!empty($item['languages'][$lang])) $languages[] = $lang;
            }
            if (!$languages) $languages = array('fr');

            $title = self::clean_translation_array($item['title'] ?? array());
            $pdf_url = esc_url_raw((string)($item['pdf_url'] ?? ''));
            if (!array_filter($title) && $pdf_url === '' && $status !== 'coming') continue;

            $out['guides'][] = array(
                'enabled' => isset($item['enabled']) && (string)$item['enabled'] === '1' ? '1' : '0',
                'cycle' => $cycle,
                'languages' => $languages,
                'status' => $status,
                'title' => $title,
                'description' => self::clean_translation_array($item['description'] ?? array(), true),
                'pdf_url' => $pdf_url,
                'cover_url' => esc_url_raw((string)($item['cover_url'] ?? '')),
                'order' => (int)($item['order'] ?? 0),
            );
        }

        return $out;
    }

    private static function guides_store() {
        $saved = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
        if (is_array($saved) && isset($saved['seasons']) && is_array($saved['seasons'])) {
            return array('version'=>3, 'seasons'=>$saved['seasons']);
        }

        $store = array('version'=>3, 'seasons'=>array());
        $all = Parcs_HT_Defaults::all_settings();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            $store['seasons'][(string)$year] = Parcs_HT_Pedagogical_Guides::settings((string)$year);
        }
        return $store;
    }

    public static function persist_guides_value($year, $raw) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return false;

        $clean = self::sanitize_guides_value($raw);
        $store = self::guides_store();
        $store['seasons'][$year] = $clean;
        update_option(Parcs_HT_Pedagogical_Guides::OPTION, $store, false);

        $stored = get_option(Parcs_HT_Pedagogical_Guides::OPTION, array());
        $stored_year = is_array($stored) && isset($stored['seasons'][$year]) && is_array($stored['seasons'][$year])
            ? $stored['seasons'][$year]
            : null;
        return is_array($stored_year) && self::same_value($clean, $stored_year);
    }

    public static function save_guides() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_pedagogical_guides');

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validation d'année juste après.
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Déséchappé une seule fois puis nettoyé champ par champ.
        $raw = isset($_POST['guides']) && is_array($_POST['guides']) ? wp_unslash($_POST['guides']) : array();
        if (!self::persist_guides_value($year, $raw)) {
            wp_die('WordPress n’a pas confirmé l’enregistrement des guides pédagogiques. Les anciennes valeurs ont été conservées pour éviter une fausse confirmation.');
        }

        self::store_daily_snapshot($year, 'Guides pédagogiques');
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-guides','guides-updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function color($value, $fallback, $allow_special = false) {
        $value = trim((string)$value);
        if ($allow_special && in_array($value, array('inherit','transparent'), true)) return $value;
        $color = sanitize_hex_color($value);
        return $color ? $color : $fallback;
    }

    public static function sanitize_appearance_value($raw) {
        $raw = is_array($raw) ? $raw : array();
        $defaults = array(
            'card_background'=>'transparent',
            'text_color'=>'inherit',
            'title_color'=>'inherit',
            'primary_button_background'=>'#176b57',
            'primary_button_text'=>'#ffffff',
            'secondary_button_color'=>'inherit',
            'category_color'=>'inherit',
        );

        return array(
            'card_background'=>self::color($raw['card_background'] ?? '', $defaults['card_background'], true),
            'text_color'=>self::color($raw['text_color'] ?? '', $defaults['text_color'], true),
            'title_color'=>self::color($raw['title_color'] ?? '', $defaults['title_color'], true),
            'primary_button_background'=>self::color($raw['primary_button_background'] ?? '', $defaults['primary_button_background']),
            'primary_button_text'=>self::color($raw['primary_button_text'] ?? '', $defaults['primary_button_text']),
            'secondary_button_color'=>self::color($raw['secondary_button_color'] ?? '', $defaults['secondary_button_color'], true),
            'category_color'=>self::color($raw['category_color'] ?? '', $defaults['category_color'], true),
        );
    }

    public static function persist_appearance_value($raw) {
        $clean = self::sanitize_appearance_value($raw);
        update_option(Parcs_HT_Guide_Appearance::OPTION, $clean, false);
        $stored = get_option(Parcs_HT_Guide_Appearance::OPTION, array());
        return is_array($stored) && self::same_value($clean, $stored);
    }

    public static function save_appearance() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_guide_appearance');

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Déséchappé une seule fois puis validé champ par champ.
        $raw = isset($_POST['appearance']) && is_array($_POST['appearance']) ? wp_unslash($_POST['appearance']) : array();
        if (!self::persist_appearance_value($raw)) {
            wp_die('WordPress n’a pas confirmé l’enregistrement de l’apparence des guides. Les anciennes valeurs ont été conservées pour éviter une fausse confirmation.');
        }

        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Libellé de redirection uniquement.
        self::store_daily_snapshot($year, 'Apparence des guides');
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-guides','guide-appearance-updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function tracked_snapshot_options() {
        $options = array(
            Parcs_HT_Defaults::OPTION,
            Parcs_HT_Pedagogical_Guides::OPTION,
            Parcs_HT_Guide_Appearance::OPTION,
        );
        if (class_exists('Parcs_HT_Quote_Languages')) $options[] = Parcs_HT_Quote_Languages::OPTION;
        return array_values(array_unique($options));
    }

    public static function capture_snapshot($year = '', $reason = 'Enregistrement') {
        $main = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!preg_match('/^20\d{2}$/', (string)$year)) {
            $year = is_array($main) ? (string)($main['active_season_year'] ?? '') : '';
        }
        if (!preg_match('/^20\d{2}$/', (string)$year)) $year = (string)wp_date('Y');

        $options = array();
        foreach (self::tracked_snapshot_options() as $option) {
            if ($option === Parcs_HT_Defaults::OPTION) continue;
            $options[$option] = get_option($option, array());
        }

        return array(
            'created_at'=>time(),
            'year'=>(string)$year,
            'user_id'=>function_exists('get_current_user_id') ? (int)get_current_user_id() : 0,
            'reason'=>sanitize_text_field($reason),
            'settings'=>is_array($main) ? $main : array(),
            'options'=>$options,
        );
    }

    private static function revision_day($revision) {
        $timestamp = (int)($revision['created_at'] ?? 0);
        return $timestamp > 0 ? wp_date('Y-m-d', $timestamp) : '';
    }

    public static function normalize_revisions($revisions) {
        $revisions = is_array($revisions) ? array_values(array_filter($revisions, 'is_array')) : array();
        usort($revisions, static function ($a, $b) {
            return (int)($b['created_at'] ?? 0) <=> (int)($a['created_at'] ?? 0);
        });

        $days = array();
        $out = array();
        foreach ($revisions as $revision) {
            $day = self::revision_day($revision);
            if ($day === '' || isset($days[$day])) continue;
            $days[$day] = true;
            $out[] = $revision;
            if (count($out) >= self::MAX_DAILY_BACKUPS) break;
        }
        return $out;
    }

    public static function normalize_revision_option($new_value, $old_value) {
        unset($old_value);
        return self::normalize_revisions($new_value);
    }

    public static function normalize_existing_revisions() {
        $current = get_option(self::REVISIONS_OPTION, array());
        $normalized = self::normalize_revisions($current);
        if (!self::same_value($current, $normalized)) update_option(self::REVISIONS_OPTION, $normalized, false);
    }

    public static function store_daily_snapshot($year = '', $reason = 'Enregistrement') {
        $revisions = get_option(self::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        array_unshift($revisions, self::capture_snapshot($year, $reason));
        update_option(self::REVISIONS_OPTION, self::normalize_revisions($revisions), false);
    }

    private static function is_main_configuration_action($action) {
        return in_array($action, array(
            'parcs_ht_save',
            'parcs_ht_save_quote_languages',
            'parcs_ht_duplicate_season',
            'parcs_ht_delete_season',
        ), true);
    }

    public static function after_option_update($option, $old_value, $value) {
        unset($old_value, $value);
        if (self::$restoring || $option !== Parcs_HT_Defaults::OPTION) return;
        $action = self::request_action();
        if (!self::is_main_configuration_action($action)) return;
        self::store_daily_snapshot(self::request_year(), 'Réglages enregistrés');
    }

    public static function after_option_add($option, $value) {
        unset($value);
        if (self::$restoring || $option !== Parcs_HT_Defaults::OPTION) return;
        $action = self::request_action();
        if (!self::is_main_configuration_action($action)) return;
        self::store_daily_snapshot(self::request_year(), 'Réglages enregistrés');
    }

    public static function restore_complete_revision() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $index = isset($_GET['revision']) ? absint(wp_unslash($_GET['revision'])) : -1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Le nonce dépend de cet index et est vérifié juste après.
        check_admin_referer('parcs_ht_restore_revision_' . $index);

        $revisions = get_option(self::REVISIONS_OPTION, array());
        if (!isset($revisions[$index]) || !is_array($revisions[$index])) return;
        $revision = $revisions[$index];
        if (!isset($revision['options']) || !is_array($revision['options'])) return;

        update_option(self::PRE_RESTORE_OPTION, self::capture_snapshot(self::request_year(), 'Avant restauration'), false);

        self::$restoring = true;
        update_option(Parcs_HT_Defaults::OPTION, is_array($revision['settings'] ?? null) ? $revision['settings'] : array(), false);
        foreach ((array)$revision['options'] as $option => $value) {
            if (!in_array($option, self::tracked_snapshot_options(), true) || $option === Parcs_HT_Defaults::OPTION) continue;
            update_option($option, $value, false);
        }
        self::$restoring = false;

        $year = (string)($revision['year'] ?? self::request_year());
        self::store_daily_snapshot($year, 'Restauration');
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'restored'=>'1','tab'=>'htp-preview'), admin_url('admin.php')));
        exit;
    }
}
