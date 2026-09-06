<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Registre permanent des identifiants tarifaires.
 *
 * Les identifiants sont des identités métier, jamais des libellés ni des
 * positions dans un tableau. Ils survivent aux renommages, changements de prix,
 * traductions, réordonnancements et duplications de saison. Un identifiant
 * supprimé reste réservé et n'est jamais recyclé.
 */
final class Parcs_HT_Tariff_Identities {
    const OPTION = 'parcs_ht_tariff_id_registry';
    const REGISTRY_VERSION = 1;

    private static $normalizing = false;

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'ensure_existing_ids'), 4);
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'preserve_ids_on_update'), 90, 3);
    }

    public static function is_row_id($id) {
        return (bool)preg_match('/^tariff_row_[0-9]{6,}$/', (string)$id);
    }

    public static function is_column_id($id) {
        return (bool)preg_match('/^tariff_col_[0-9]{6,}$/', (string)$id);
    }

    private static function id_number($id, $prefix) {
        return (int)substr((string)$id, strlen($prefix));
    }

    private static function registry() {
        $raw = get_option(self::OPTION, array());
        $raw = is_array($raw) ? $raw : array();
        return array(
            'version' => self::REGISTRY_VERSION,
            'next_row' => max(1, (int)($raw['next_row'] ?? 1)),
            'next_col' => max(1, (int)($raw['next_col'] ?? 1)),
            'used_rows' => isset($raw['used_rows']) && is_array($raw['used_rows']) ? $raw['used_rows'] : array(),
            'used_cols' => isset($raw['used_cols']) && is_array($raw['used_cols']) ? $raw['used_cols'] : array(),
        );
    }

    private static function save_registry($state) {
        update_option(self::OPTION, array(
            'version' => self::REGISTRY_VERSION,
            'next_row' => max(1, (int)($state['next_row'] ?? 1)),
            'next_col' => max(1, (int)($state['next_col'] ?? 1)),
            'used_rows' => is_array($state['used_rows'] ?? null) ? $state['used_rows'] : array(),
            'used_cols' => is_array($state['used_cols'] ?? null) ? $state['used_cols'] : array(),
        ), false);
    }

    private static function claim_row_id($id, &$state) {
        $id = sanitize_key((string)$id);
        if (!self::is_row_id($id)) return '';
        $state['used_rows'][$id] = 1;
        $number = self::id_number($id, 'tariff_row_');
        if ($number >= $state['next_row']) $state['next_row'] = $number + 1;
        return $id;
    }

    private static function claim_column_id($id, &$state) {
        $id = sanitize_key((string)$id);
        if (!self::is_column_id($id)) return '';
        $state['used_cols'][$id] = 1;
        $number = self::id_number($id, 'tariff_col_');
        if ($number >= $state['next_col']) $state['next_col'] = $number + 1;
        return $id;
    }

    private static function allocate_row_id(&$state) {
        do {
            $id = 'tariff_row_' . str_pad((string)$state['next_row'], 6, '0', STR_PAD_LEFT);
            $state['next_row']++;
        } while (isset($state['used_rows'][$id]));
        $state['used_rows'][$id] = 1;
        return $id;
    }

    private static function allocate_column_id(&$state) {
        do {
            $id = 'tariff_col_' . str_pad((string)$state['next_col'], 6, '0', STR_PAD_LEFT);
            $state['next_col']++;
        } while (isset($state['used_cols'][$id]));
        $state['used_cols'][$id] = 1;
        return $id;
    }

    private static function translations_signature($value) {
        $value = is_array($value) ? $value : array();
        $parts = array();
        foreach (array('fr','en','de') as $lang) {
            $text = trim(wp_strip_all_tags((string)($value[$lang] ?? '')));
            $parts[] = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        }
        return implode('|', $parts);
    }

    private static function row_signature($group, $row, $index) {
        $label = self::translations_signature(is_array($row) ? ($row['label'] ?? array()) : array());
        $type = is_array($row) ? sanitize_key($row['row_type'] ?? 'standard') : 'standard';
        if ($label === '||') return $group . '|blank|' . (int)$index . '|' . $type;
        return $group . '|' . $label . '|' . $type;
    }

    private static function column_signature($group, $column, $index) {
        $label = self::translations_signature(is_array($column) ? ($column['label'] ?? array()) : array());
        if ($label === '||') return $group . '|blank|' . (int)$index;
        return $group . '|' . $label;
    }

    private static function raw_tariff_hints() {
        if (!is_admin() || !isset($_POST['settings']['tariffs']) || !is_array($_POST['settings']['tariffs'])) return array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Lecture uniquement ; le nonce est vérifié par le gestionnaire de sauvegarde principal.
        return wp_unslash($_POST['settings']['tariffs']); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Les seules valeurs utilisées ici sont ensuite passées dans sanitize_key().
    }

    /**
     * Normalise une grille tarifaire complète et retourne true si elle a changé.
     * Les maps de signature sont partagées entre les saisons pendant la migration
     * afin qu'une copie réellement identique 2026 -> 2027 garde la même identité.
     */
    private static function normalize_tariffs(&$tariffs, &$row_map, &$col_map, &$state, $hints = array()) {
        if (!is_array($tariffs)) return false;
        $changed = false;
        if (!isset($tariffs['columns']) || !is_array($tariffs['columns'])) $tariffs['columns'] = array();

        foreach (array('individual','reduced','groups') as $group) {
            $columns = isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group]) ? array_values($tariffs['columns'][$group]) : array();
            if (!$columns) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
            $hint_columns = isset($hints['columns'][$group]) && is_array($hints['columns'][$group]) ? array_values($hints['columns'][$group]) : array();
            $old_to_new = array();
            $seen_column_ids = array();

            foreach ($columns as $index => &$column) {
                if (!is_array($column)) $column = array();
                $old_id = sanitize_key((string)($column['id'] ?? ''));
                $hint_id = isset($hint_columns[$index]['id']) ? sanitize_key((string)$hint_columns[$index]['id']) : '';
                $candidate = self::is_column_id($hint_id) ? $hint_id : (self::is_column_id($old_id) ? $old_id : '');
                $signature = self::column_signature($group, $column, $index);

                if ($candidate === '' && isset($col_map[$signature])) $candidate = $col_map[$signature];
                if ($candidate === '' || isset($seen_column_ids[$candidate])) $candidate = self::allocate_column_id($state);
                else self::claim_column_id($candidate, $state);

                $seen_column_ids[$candidate] = true;
                if (!isset($col_map[$signature])) $col_map[$signature] = $candidate;
                if ($old_id !== '') $old_to_new[$old_id] = $candidate;
                if ($hint_id !== '') $old_to_new[$hint_id] = $candidate;
                if (($column['id'] ?? '') !== $candidate) {
                    $column['id'] = $candidate;
                    $changed = true;
                }
            }
            unset($column);
            $tariffs['columns'][$group] = $columns;

            $rows = isset($tariffs[$group]) && is_array($tariffs[$group]) ? array_values($tariffs[$group]) : array();
            $hint_rows = isset($hints[$group]) && is_array($hints[$group]) ? array_values($hints[$group]) : array();
            $seen_row_ids = array();

            foreach ($rows as $index => &$row) {
                if (!is_array($row)) $row = array();
                $hint_id = isset($hint_rows[$index]['id']) ? sanitize_key((string)$hint_rows[$index]['id']) : '';
                $existing_id = sanitize_key((string)($row['id'] ?? ''));
                $candidate = self::is_row_id($hint_id) ? $hint_id : (self::is_row_id($existing_id) ? $existing_id : '');
                $signature = self::row_signature($group, $row, $index);

                if ($candidate === '' && isset($row_map[$signature])) $candidate = $row_map[$signature];
                if ($candidate === '' || isset($seen_row_ids[$candidate])) $candidate = self::allocate_row_id($state);
                else self::claim_row_id($candidate, $state);

                $seen_row_ids[$candidate] = true;
                if (!isset($row_map[$signature])) $row_map[$signature] = $candidate;
                if (($row['id'] ?? '') !== $candidate) {
                    $row['id'] = $candidate;
                    $changed = true;
                }

                $old_cells = isset($row['cells']) && is_array($row['cells']) ? $row['cells'] : array();
                $new_cells = array();
                foreach ($columns as $column_index => $column) {
                    $new_id = (string)$column['id'];
                    $cell = array();
                    foreach ($old_to_new as $old_key => $mapped) {
                        if ($mapped !== $new_id || !isset($old_cells[$old_key]) || !is_array($old_cells[$old_key])) continue;
                        $cell = $old_cells[$old_key];
                        break;
                    }
                    if (!$cell && isset($old_cells[$new_id]) && is_array($old_cells[$new_id])) $cell = $old_cells[$new_id];
                    if (!$cell && $column_index === 0 && isset($row['price']) && trim((string)$row['price']) !== '') $cell = array('value'=>(string)$row['price'],'old_value'=>'');
                    if (!isset($cell['value'])) $cell['value'] = '';
                    if (!isset($cell['old_value'])) $cell['old_value'] = '';
                    $new_cells[$new_id] = $cell;
                }
                if (wp_json_encode($new_cells) !== wp_json_encode($old_cells)) {
                    $row['cells'] = $new_cells;
                    $changed = true;
                } else {
                    $row['cells'] = $old_cells;
                }
            }
            unset($row);
            $tariffs[$group] = $rows;
        }
        return $changed;
    }

    private static function normalize_settings(&$settings, $target_year = '', $hints = array()) {
        if (!is_array($settings)) return false;
        $state = self::registry();
        $row_map = array();
        $col_map = array();
        $changed = false;

        if (isset($settings['seasons']) && is_array($settings['seasons'])) {
            $years = array_keys($settings['seasons']);
            sort($years, SORT_NUMERIC);
            foreach ($years as $year) {
                if (!isset($settings['seasons'][$year]['tariffs']) || !is_array($settings['seasons'][$year]['tariffs'])) continue;
                $season_hints = ((string)$year === (string)$target_year) ? $hints : array();
                if (self::normalize_tariffs($settings['seasons'][$year]['tariffs'], $row_map, $col_map, $state, $season_hints)) $changed = true;
            }
        }

        if (isset($settings['tariffs']) && is_array($settings['tariffs'])) {
            if (self::normalize_tariffs($settings['tariffs'], $row_map, $col_map, $state, array())) $changed = true;
        }

        self::save_registry($state);
        return $changed;
    }

    public static function preserve_ids_on_update($new_value, $old_value, $option) {
        unset($old_value, $option);
        if (self::$normalizing || !is_array($new_value)) return $new_value;
        self::$normalizing = true;
        $year = '';
        if (isset($_POST['season_year'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Utilisé uniquement pour rattacher les IDs au brouillon en cours de sauvegarde.
            $candidate = sanitize_text_field(wp_unslash($_POST['season_year'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (preg_match('/^20\d{2}$/', $candidate)) $year = $candidate;
        }
        self::normalize_settings($new_value, $year, self::raw_tariff_hints());
        self::$normalizing = false;
        return $new_value;
    }

    public static function ensure_existing_ids() {
        if (self::$normalizing || !current_user_can('manage_options')) return;
        $saved = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($saved)) return;
        self::$normalizing = true;
        $changed = self::normalize_settings($saved);
        if ($changed) update_option(Parcs_HT_Defaults::OPTION, $saved, false);
        self::$normalizing = false;
        self::migrate_quote_bindings($saved);
    }

    private static function translated_label($value) {
        if (!is_array($value)) return '';
        foreach (array('fr','en','de') as $lang) {
            $text = trim((string)($value[$lang] ?? ''));
            if ($text !== '') return $text;
        }
        return '';
    }

    private static function legacy_row($rows, $legacy, $role) {
        $index = isset($legacy[$role . '_row']) ? (int)$legacy[$role . '_row'] : -1;
        $expected = trim((string)($legacy[$role . '_label'] ?? ''));
        if ($index >= 0 && isset($rows[$index]) && is_array($rows[$index])) {
            $label = self::translated_label($rows[$index]['label'] ?? array());
            if ($expected === '' || $label === $expected) return $rows[$index];
        }
        if ($expected !== '') {
            foreach ($rows as $row) {
                if (is_array($row) && self::translated_label($row['label'] ?? array()) === $expected) return $row;
            }
        }
        return null;
    }

    private static function binding_is_stable($binding) {
        if (!is_array($binding) || !self::is_column_id($binding['column_id'] ?? '')) return false;
        foreach (array('child','adult','disability','companion') as $role) if (!self::is_row_id($binding[$role . '_row_id'] ?? '')) return false;
        return true;
    }

    private static function migrate_quote_bindings($settings) {
        if (!class_exists('Parcs_HT_Group_Quotes') || !is_array($settings)) return;
        $saved = get_option(Parcs_HT_Group_Quotes::OPTION, array());
        $saved = is_array($saved) ? $saved : array();
        if (!isset($saved['tariff_bindings']) || !is_array($saved['tariff_bindings'])) $saved['tariff_bindings'] = array();
        $legacy = isset($saved['tariff_binding']) && is_array($saved['tariff_binding']) ? $saved['tariff_binding'] : Parcs_HT_Group_Quotes::legacy_binding_defaults();
        $changed = false;

        foreach ((array)($settings['seasons'] ?? array()) as $year => $season) {
            if (!is_array($season) || empty($season['tariffs']) || !is_array($season['tariffs'])) continue;
            if (self::binding_is_stable($saved['tariff_bindings'][$year] ?? null)) continue;
            $tariffs = $season['tariffs'];
            $rows = isset($tariffs['groups']) && is_array($tariffs['groups']) ? array_values($tariffs['groups']) : array();
            $columns = isset($tariffs['columns']['groups']) && is_array($tariffs['columns']['groups']) ? array_values($tariffs['columns']['groups']) : array();
            if (!$rows || !$columns) continue;

            $column_id = '';
            $legacy_column = sanitize_key((string)($legacy['column'] ?? 'price'));
            foreach ($columns as $column) {
                if (!is_array($column)) continue;
                $candidate = sanitize_key((string)($column['id'] ?? ''));
                if (self::is_column_id($candidate) && ($legacy_column === '' || $legacy_column === 'price')) { $column_id = $candidate; break; }
                if ($candidate === $legacy_column && self::is_column_id($candidate)) { $column_id = $candidate; break; }
            }
            if ($column_id === '') $column_id = sanitize_key((string)($columns[0]['id'] ?? ''));
            if (!self::is_column_id($column_id)) continue;

            $binding = array(
                'column_id' => $column_id,
                'free_adult_children' => (string)max(1, (int)($legacy['free_adult_children'] ?? 10)),
                'free_adult_round_threshold' => (string)max(1, (int)($legacy['free_adult_round_threshold'] ?? 5)),
            );
            $valid = true;
            foreach (array('child','adult','disability','companion') as $role) {
                $row = self::legacy_row($rows, $legacy, $role);
                $id = sanitize_key((string)(is_array($row) ? ($row['id'] ?? '') : ''));
                if (!self::is_row_id($id)) { $valid = false; break; }
                $binding[$role . '_row_id'] = $id;
            }
            if (!$valid) continue;
            $saved['tariff_bindings'][(string)$year] = $binding;
            $changed = true;
        }

        if ($changed || (int)($saved['binding_version'] ?? 0) < 2) {
            $saved['binding_version'] = 2;
            update_option(Parcs_HT_Group_Quotes::OPTION, $saved, false);
        }
    }

    public static function identity_snapshot($settings) {
        $out = array();
        $tariffs = is_array($settings) && isset($settings['tariffs']) && is_array($settings['tariffs']) ? $settings['tariffs'] : array();
        foreach (array('individual','reduced','groups') as $group) {
            $out[$group] = array('rows'=>array(),'columns'=>array());
            foreach ((array)($tariffs['columns'][$group] ?? array()) as $column) {
                if (!is_array($column)) continue;
                $out[$group]['columns'][] = array(
                    'id'=>sanitize_key((string)($column['id'] ?? '')),
                    'label'=>self::translated_label($column['label'] ?? array()),
                );
            }
            foreach ((array)($tariffs[$group] ?? array()) as $row) {
                if (!is_array($row)) continue;
                $out[$group]['rows'][] = array(
                    'id'=>sanitize_key((string)($row['id'] ?? '')),
                    'label'=>self::translated_label($row['label'] ?? array()),
                );
            }
        }
        return $out;
    }

    public static function row_by_id($rows, $id) {
        $id = sanitize_key((string)$id);
        if (!self::is_row_id($id)) return null;
        foreach ((array)$rows as $row) if (is_array($row) && sanitize_key((string)($row['id'] ?? '')) === $id) return $row;
        return null;
    }

    public static function column_exists($columns, $id) {
        $id = sanitize_key((string)$id);
        if (!self::is_column_id($id)) return false;
        foreach ((array)$columns as $column) if (is_array($column) && sanitize_key((string)($column['id'] ?? '')) === $id) return true;
        return false;
    }
}
