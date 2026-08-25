<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibilité 1.8.2 : dernières entrées indépendantes par créneau.
 *
 * Cette couche reste volontairement isolée du moteur historique afin de ne pas
 * modifier la structure Créneau 1 / Créneau 2 ni les réglages des deux parcs.
 */
final class Parcs_HT_Slot_Last_Entry {
    const MIGRATION_OPTION = 'parcs_ht_slot_last_entry_migrated_182';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'maybe_migrate'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'), 50);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_assets'), 100);
        add_action('wp_footer', array(__CLASS__, 'frontend_assets'), 1);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'preserve_and_save_slot_values'), 20, 3);
    }

    /**
     * Les anciennes configurations ne possèdent qu'un seul délai spécifique.
     * On le recopie dans les deux créneaux pour que la mise à jour soit neutre.
     * L'administrateur peut ensuite donner une valeur différente à chaque créneau.
     */
    public static function maybe_migrate() {
        if (get_option(self::MIGRATION_OPTION, '0') === '1') return;
        $settings = get_option(Parcs_HT_Defaults::OPTION, null);
        if (!is_array($settings) || empty($settings['seasons']) || !is_array($settings['seasons'])) {
            update_option(self::MIGRATION_OPTION, '1', false);
            return;
        }

        $changed = false;
        foreach ($settings['seasons'] as &$season) {
            if (!is_array($season)) continue;
            foreach (array('regular_periods', 'exceptions') as $list_key) {
                if (empty($season[$list_key]) || !is_array($season[$list_key])) continue;
                foreach ($season[$list_key] as &$row) {
                    if (!is_array($row)) continue;
                    $legacy = isset($row['last_entry_minutes']) ? (string) $row['last_entry_minutes'] : '';
                    if (!array_key_exists('last_entry_minutes_slot1', $row)) {
                        $row['last_entry_minutes_slot1'] = $legacy;
                        $changed = true;
                    }
                    if (!array_key_exists('last_entry_minutes_slot2', $row)) {
                        $row['last_entry_minutes_slot2'] = $legacy;
                        $changed = true;
                    }
                }
                unset($row);
            }
        }
        unset($season);

        if ($changed) update_option(Parcs_HT_Defaults::OPTION, $settings, false);
        update_option(self::MIGRATION_OPTION, '1', false);
    }

    public static function admin_assets($hook) {
        if ($hook !== 'toplevel_page_' . Parcs_HT_Admin::PAGE) return;

        $requested_year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : '';
        $settings = Parcs_HT_Defaults::settings($requested_year);
        $payload = array(
            'regular_periods' => self::slot_payload(isset($settings['regular_periods']) ? $settings['regular_periods'] : array()),
            'exceptions' => self::slot_payload(isset($settings['exceptions']) ? $settings['exceptions'] : array()),
        );

        wp_enqueue_script(
            'parcs-ht-slot-last-entry-admin',
            PARCS_HT_URL . 'assets/slot-last-entry-admin.js',
            array('parcs-ht-admin'),
            PARCS_HT_VERSION,
            true
        );
        wp_add_inline_script(
            'parcs-ht-slot-last-entry-admin',
            'window.ParcsHTSlotAdminData=' . wp_json_encode($payload) . ';',
            'before'
        );
    }

    public static function frontend_assets() {
        static $done = false;
        if ($done || !wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        $done = true;
        wp_enqueue_script(
            'parcs-ht-slot-last-entry-frontend',
            PARCS_HT_URL . 'assets/slot-last-entry-frontend.js',
            array('parcs-ht-frontend'),
            PARCS_HT_VERSION,
            true
        );
    }

    private static function slot_payload($rows) {
        $out = array();
        foreach ((array) $rows as $row) {
            if (!is_array($row)) continue;
            $legacy = isset($row['last_entry_minutes']) ? (string) $row['last_entry_minutes'] : '';
            $out[] = array(
                'slot1' => isset($row['last_entry_minutes_slot1']) ? (string) $row['last_entry_minutes_slot1'] : $legacy,
                'slot2' => isset($row['last_entry_minutes_slot2']) ? (string) $row['last_entry_minutes_slot2'] : $legacy,
            );
        }
        return $out;
    }

    /**
     * Le sanitizer historique ignore les nouvelles clés. Ce filtre les remet
     * juste avant l'écriture de l'option et conserve les valeurs lors d'une
     * sauvegarde d'un autre onglet.
     */
    public static function preserve_and_save_slot_values($new_value, $old_value, $option) {
        unset($option);
        if (!is_array($new_value) || !is_array($old_value) || empty($new_value['seasons'])) return $new_value;

        $posted = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';

        foreach ($new_value['seasons'] as $season_year => &$season) {
            if (!is_array($season)) continue;
            foreach (array('regular_periods', 'exceptions') as $list_key) {
                if (empty($season[$list_key]) || !is_array($season[$list_key])) continue;
                $old_rows = isset($old_value['seasons'][$season_year][$list_key]) && is_array($old_value['seasons'][$season_year][$list_key])
                    ? array_values($old_value['seasons'][$season_year][$list_key]) : array();
                $posted_rows = ($year === (string) $season_year && isset($posted[$list_key]) && is_array($posted[$list_key]))
                    ? array_values($posted[$list_key]) : array();

                foreach ($season[$list_key] as $index => &$row) {
                    if (!is_array($row)) continue;
                    $old_row = isset($old_rows[$index]) && is_array($old_rows[$index]) ? $old_rows[$index] : array();
                    $legacy = isset($row['last_entry_minutes']) ? (string) $row['last_entry_minutes'] : '';
                    $slot1 = array_key_exists('last_entry_minutes_slot1', $old_row) ? (string) $old_row['last_entry_minutes_slot1'] : $legacy;
                    $slot2 = array_key_exists('last_entry_minutes_slot2', $old_row) ? (string) $old_row['last_entry_minutes_slot2'] : $legacy;

                    if (isset($posted_rows[$index]) && is_array($posted_rows[$index])) {
                        if (array_key_exists('last_entry_minutes_slot1', $posted_rows[$index])) {
                            $slot1 = self::clean_optional_minutes($posted_rows[$index]['last_entry_minutes_slot1']);
                        }
                        if (array_key_exists('last_entry_minutes_slot2', $posted_rows[$index])) {
                            $slot2 = self::clean_optional_minutes($posted_rows[$index]['last_entry_minutes_slot2']);
                        }
                    }

                    $row['last_entry_minutes_slot1'] = $slot1;
                    $row['last_entry_minutes_slot2'] = $slot2;
                    // Le champ historique représente la dernière fermeture de la journée.
                    // Il reste renseigné pour les exports et anciennes fonctions qui le lisent encore.
                    $row['last_entry_minutes'] = $slot2 !== '' ? $slot2 : $slot1;
                }
                unset($row);
            }
        }
        unset($season);
        return $new_value;
    }

    private static function clean_optional_minutes($value) {
        if ($value === '' || $value === null) return '';
        $value = (int) $value;
        return (string) max(0, min(1440, $value));
    }
}
