<?php
/**
 * Export/import contrôlé des réglages du parc, par saison ou au format archive.
 *
 * Format CSV "chemin JSON" intentionnel : conserve sans perte les identifiants,
 * colonnes, traductions, cellules tarifaires, booléens et ordre des lignes.
 * Il complète (sans le modifier) l'import CSV historique des horaires.
 */
if (!defined('ABSPATH')) { exit; }

final class Parcs_HT_Backup_Import {
    const FORMAT = 'parcs-ht-configuration-v1';
    const CSV_COLUMNS = array('scope','park','year','path','json_value');
    const MAX_UPLOAD_BYTES = 12582912; // 12 Mio compressés.
    const MAX_JSON_BYTES = 8388608; // 8 Mio décompressés.
    const MAX_CSV_ROWS = 30000;
    const PREVIEW_TTL = 900;
    const REVISIONS_OPTION = 'parcs_ht_backup_import_revisions';

    /** Liste autorisée : pas de devis reçus, identités de visiteurs, journaux ni jetons. */
    private static function option_names() {
        return array(
            'parcs_ht_settings',
            'parcs_ht_group_tariff_settings',
            'parcs_ht_advent',
            'parcs_ht_pedagogical_guides',
            'parcs_ht_faq',
            'parcs_ht_wordpress_events',
            'parcs_ht_guide_appearance',
            'parcs_ht_public_content',
            'parcs_ht_popups_1200'
        );
    }

    private static function sections() {
        return array(
            'schedule' => 'Horaires et calendrier',
            'visitors' => 'Tarifs visiteurs',
            'groups' => 'Tarifs groupes'
        );
    }

    public static function init() {
        add_action('admin_post_parcs_ht_backup_export', array(__CLASS__, 'export'));
        add_action('admin_post_parcs_ht_backup_preview', array(__CLASS__, 'preview'));
        add_action('admin_post_parcs_ht_backup_apply', array(__CLASS__, 'apply'));
    }

    private static function require_admin($nonce) {
        if (!current_user_can('manage_options')) wp_die('Accès interdit.');
        check_admin_referer($nonce);
    }

    /** Lecture fidèle : évite les filtres WordPress d'affichage des tarifs qui altèrent get_option(). */
    private static function stored_option($name) {
        global $wpdb;
        if (!in_array($name, self::option_names(), true)) return null;
        $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name));
        return $value === null ? null : maybe_unserialize($value);
    }

    private static function raw_settings() {
        $all = self::stored_option(Parcs_HT_Defaults::OPTION);
        return is_array($all) ? $all : array();
    }

    private static function park($all) {
        return sanitize_key((string)($all['site_type'] ?? ''));
    }

    private static function host() {
        return strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
    }

    private static function year($raw) {
        $year = sanitize_text_field((string)$raw);
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        return $year;
    }

    private static function url($year, $args = array()) {
        return add_query_arg(array_merge(array('page'=>Parcs_HT_Admin_Overview::PAGE,'season'=>$year), $args), admin_url('admin.php')) . '#htp-backup-import';
    }

    private static function input($key) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Appels réservés aux actions require_admin() avec nonce validé.
        return isset($_POST[$key]) && is_scalar($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    }

    private static function preview_key() {
        return 'parcs_ht_backup_preview_' . get_current_user_id();
    }

    private static function fail($message) {
        wp_die(esc_html($message), 'Import impossible', array('response'=>400));
    }

    private static function section_data($section, $year, $all, $display_store) {
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) self::fail('La saison sélectionnée est introuvable.');
        $season = $all['seasons'][$year];
        if ($section === 'schedule') {
            $keys = array('regular_periods','school_holidays','special_periods','public_holidays','domain_rules','exceptions');
            $out = array();
            foreach ($keys as $key) $out[$key] = isset($season[$key]) && is_array($season[$key]) ? $season[$key] : array();
            return array('calendar'=>$out);
        }
        $tariffs = isset($season['tariffs']) && is_array($season['tariffs']) ? $season['tariffs'] : array();
        if ($section === 'visitors') {
            $tariffs['individual'] = isset($tariffs['individual']) ? $tariffs['individual'] : array();
            $tariffs['reduced'] = isset($tariffs['reduced']) ? $tariffs['reduced'] : array();
            $tariffs['columns'] = isset($tariffs['columns']) && is_array($tariffs['columns']) ? $tariffs['columns'] : array();
            unset($tariffs['groups'], $tariffs['columns']['groups'], $tariffs['group_order']);
            return array('tariffs'=>$tariffs);
        }
        if ($section === 'groups') {
            $columns = isset($tariffs['columns']['groups']) ? $tariffs['columns']['groups'] : array();
            $groups = isset($tariffs['groups']) ? $tariffs['groups'] : array();
            $display = isset($display_store['seasons'][$year]) && is_array($display_store['seasons'][$year]) ? $display_store['seasons'][$year] : array();
            return array('tariffs'=>array('groups'=>$groups,'columns'=>array('groups'=>$columns)),'group_display'=>$display);
        }
        self::fail('Rubrique inconnue.');
    }

    private static function display_store() {
        $store = self::stored_option('parcs_ht_group_tariff_settings');
        return is_array($store) ? $store : array();
    }

    /** JSON pointer (RFC 6901) ; seules des feuilles ou des listes vides sont émises. */
    public static function flatten($value, $path = '') {
        if (!is_array($value) || !$value) return array(array($path, wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)));
        $out = array();
        foreach ($value as $key => $child) {
            $part = str_replace(array('~','/'), array('~0','~1'), (string)$key);
            $out = array_merge($out, self::flatten($child, $path . '/' . $part));
        }
        return $out;
    }

    public static function inflate($pairs) {
        $out = array();
        $used = array();
        foreach ($pairs as $pair) {
            if (!is_array($pair) || count($pair) !== 2) return false;
            $path = (string)$pair[0];
            if ($path === '' || $path[0] !== '/' || isset($used[$path]) || strlen($path) > 700) return false;
            $used[$path] = true;
            $parts = explode('/', substr($path, 1));
            if (count($parts) > 24) return false;
            $decoded = json_decode((string)$pair[1], true, 48);
            if (json_last_error() !== JSON_ERROR_NONE) return false;
            $node =& $out;
            foreach ($parts as $i => $encoded) {
                if (preg_match('/~(?!0|1)/', $encoded)) return false;
                $key = str_replace(array('~1','~0'), array('/','~'), $encoded);
                if ($key === '' || strlen($key) > 150) return false;
                if ($i === count($parts) - 1) {
                    if (array_key_exists($key, $node)) return false;
                    $node[$key] = $decoded;
                } else {
                    if (!isset($node[$key])) $node[$key] = array();
                    if (!is_array($node[$key])) return false;
                    $node =& $node[$key];
                }
            }
            unset($node);
        }
        return $out;
    }

    private static function rows_for($scope, $year, $all, $display) {
        $data = self::section_data($scope, $year, $all, $display);
        $rows = array();
        foreach (self::flatten($data) as $part) $rows[] = array($scope, self::park($all), $year, $part[0], $part[1]);
        return $rows;
    }

    private static function csv_string($scope, $year, $all, $display) {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, self::CSV_COLUMNS, ';', '"', '');
        foreach (self::rows_for($scope, $year, $all, $display) as $row) fputcsv($stream, $row, ';', '"', '');
        rewind($stream);
        $data = stream_get_contents($stream);
        fclose($stream);
        return $data;
    }

    private static function json_string($value) {
        $json = wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if (!is_string($json)) self::fail('Impossible de convertir les réglages en JSON.');
        return $json;
    }

    private static function archive($all) {
        $options = array();
        foreach (self::option_names() as $key) {
            $value = self::stored_option($key);
            if ($value !== null && is_array($value)) $options[$key] = $value;
        }
        if (!isset($options[Parcs_HT_Defaults::OPTION])) self::fail('Configuration principale absente.');
        $meta = array(
            'format'=>self::FORMAT,
            'scope'=>'global',
            'park'=>self::park($all),
            'host'=>self::host(),
            'schema_version'=>(int)($all['schema_version'] ?? 0),
            'plugin_version'=>PARCS_HT_VERSION,
            'exported_at'=>gmdate('c'),
            'options'=>array_keys($options)
        );
        return array('manifest'=>$meta, 'options'=>$options);
    }

    private static function zip_string($archive, $all) {
        if (!class_exists('ZipArchive')) self::fail('Le module PHP ZipArchive est requis pour exporter une archive.');
        $tmp = wp_tempnam('parcs-ht-backup.zip');
        if (!$tmp) self::fail('Création du fichier temporaire impossible.');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            self::fail('Impossible de construire le ZIP.');
        }
        $zip->addFromString('manifest.json', self::json_string($archive['manifest']));
        $zip->addFromString('configuration.json', self::json_string($archive['options']));
        $display = self::display_store();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (!preg_match('/^20\d{2}$/', (string)$year)) continue;
            foreach (self::sections() as $scope=>$label) {
                unset($label);
                $zip->addFromString('tableaux/' . $year . '-' . $scope . '.csv', self::csv_string($scope, (string)$year, $all, $display));
            }
        }
        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        if (!is_string($bytes) || $bytes === '') self::fail('Impossible de lire le ZIP.');
        return $bytes;
    }

    public static function export() {
        self::require_admin('parcs_ht_backup_export');
        $scope = self::input('backup_scope');
        $year = self::year(self::input('season_year'));
        $all = self::raw_settings();
        if (!isset($all['seasons'][$year])) self::fail('Saison inexistante.');
        if ($scope !== 'global' && !isset(self::sections()[$scope])) self::fail('Rubrique inconnue.');
        if ($scope === 'global') {
            $body = self::zip_string(self::archive($all), $all);
            $type = 'application/zip';
            $name = 'parc-configuration-' . gmdate('Ymd-His') . '.zip';
        } else {
            $body = self::csv_string($scope, $year, $all, self::display_store());
            $type = 'text/csv; charset=UTF-8';
            $name = 'parc-' . $scope . '-' . $year . '.csv';
        }
        if (strlen($body) > self::MAX_UPLOAD_BYTES) self::fail('Export trop volumineux.');
        nocache_headers();
        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fichier contrôlé.
        exit;
    }

    private static function parse_csv($file, $wanted_year, $park) {
        $stream = fopen($file, 'rb');
        if (!$stream) self::fail('Impossible de lire le CSV.');
        $head = fgetcsv($stream, 0, ';', '"', '');
        if (!is_array($head)) self::fail('En-tête CSV manquant.');
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
        if ($head !== self::CSV_COLUMNS) self::fail('En-tête CSV différent du format de sauvegarde de l’extension.');
        $pairs = array();
        $scope = '';
        $line = 1;
        while (($cols = fgetcsv($stream, 0, ';')) !== false) {
            $line++;
            if ($cols === array(null)) continue;
            if ($line > self::MAX_CSV_ROWS) self::fail('Trop de lignes CSV.');
            if (count($cols) !== 5) self::fail('Nombre de colonnes incorrect ligne ' . $line);
            if ($scope === '') $scope = (string)$cols[0];
            if (!isset(self::sections()[$scope]) || (string)$cols[0] !== $scope) self::fail('Rubriques mélangées.');
            if ((string)$cols[1] !== $park) self::fail('Le CSV provient d’un autre parc.');
            if ((string)$cols[2] !== $wanted_year) self::fail('Le CSV correspond à une autre année.');
            $pairs[] = array($cols[3], $cols[4]);
        }
        fclose($stream);
        if (!$pairs) self::fail('CSV sans donnée.');
        $data = self::inflate($pairs);
        if (!is_array($data)) self::fail('Chemin ou valeur JSON invalide dans le CSV.');
        if ($scope === 'schedule') {
            if (!isset($data['calendar']) || !is_array($data['calendar'])) self::fail('Calendrier invalide.');
            foreach (array('regular_periods','school_holidays','special_periods','public_holidays','domain_rules','exceptions') as $key)
                if (!isset($data['calendar'][$key]) || !is_array($data['calendar'][$key])) self::fail('Section calendrier manquante : ' . $key);
            if (count($data) !== 1 || count($data['calendar']) !== 6) self::fail('Rubriques calendrier inattendues.');
        } else {
            if (empty($data['tariffs']) || !is_array($data['tariffs'])) self::fail('Données tarifaires absentes.');
            if ($scope === 'visitors') {
                if (!isset($data['tariffs']['individual'],$data['tariffs']['reduced'],$data['tariffs']['columns']['individual'],$data['tariffs']['columns']['reduced'])) self::fail('Tarifs visiteurs incomplets.');
                if (isset($data['group_display'],$data['tariffs']['groups'],$data['tariffs']['columns']['groups'])) self::fail('CSV visiteurs contient des données groupes.');
                if (count($data) !== 1) self::fail('Section inattendue.');
            } elseif ($scope === 'groups') {
                if (!isset($data['group_display'],$data['tariffs']['groups'],$data['tariffs']['columns']['groups']) || !is_array($data['group_display'])) self::fail('Tarifs groupes incomplets.');
                if (count($data) !== 2 || count($data['tariffs']) !== 2 || count($data['tariffs']['columns']) !== 1) self::fail('Sections groupes inattendues.');
            }
        }
        return array('scope'=>$scope,'year'=>$wanted_year,'data'=>$data);
    }

    private static function parse_zip($file, $all) {
        if (!class_exists('ZipArchive')) self::fail('ZipArchive est requis pour importer le ZIP.');
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) self::fail('ZIP illisible.');
        $stat = $zip->statName('configuration.json');
        $mstat = $zip->statName('manifest.json');
        if (!$stat || !$mstat || $stat['size'] > self::MAX_JSON_BYTES || $mstat['size'] > 32768) { $zip->close(); self::fail('Archive invalide ou trop volumineuse.'); }
        $manifest_raw = $zip->getFromName('manifest.json');
        $options_raw = $zip->getFromName('configuration.json');
        $zip->close();
        $manifest = json_decode((string)$manifest_raw, true, 64);
        $options = json_decode((string)$options_raw, true, 64);
        if (!is_array($manifest) || !is_array($options)) self::fail('Archive JSON invalide.');
        if (($manifest['format'] ?? '') !== self::FORMAT || ($manifest['scope'] ?? '') !== 'global') self::fail('Format d’archive inconnu.');
        if (($manifest['park'] ?? '') !== self::park($all) || strtolower((string)($manifest['host'] ?? '')) !== self::host()) self::fail('L’archive appartient à un autre parc ou un autre site.');
        if ((int)($manifest['schema_version'] ?? 0) > (int)Parcs_HT_Defaults::SCHEMA_VERSION) self::fail('Archive produite par une version de données plus récente.');
        if (!isset($options[Parcs_HT_Defaults::OPTION]) || !is_array($options[Parcs_HT_Defaults::OPTION])) self::fail('Réglages principaux manquants.');
        if ((string)($options[Parcs_HT_Defaults::OPTION]['site_type'] ?? '') !== self::park($all)) self::fail('Type de parc incohérent.');
        if (!isset($options[Parcs_HT_Defaults::OPTION]['seasons']) || !is_array($options[Parcs_HT_Defaults::OPTION]['seasons'])) self::fail('Saisons absentes.');
        foreach ($options[Parcs_HT_Defaults::OPTION]['seasons'] as $year=>$season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) self::fail('Saison invalide dans l’archive.');
        }
        foreach ($options as $key=>$value)
            if (!in_array($key, self::option_names(), true) || !is_array($value)) self::fail('Option non autorisée : ' . $key);
        if (array_keys($options) !== (array)($manifest['options'] ?? array())) self::fail('Liste d’options incohérente.');
        return array('scope'=>'global','year'=>'','data'=>$options);
    }

    private static function read_candidate($year, $park) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Appelé uniquement après require_admin() dans preview().
        if (!isset($_FILES['backup_file']) || !is_array($_FILES['backup_file'])) self::fail('Fichier manquant.');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload contrôlé, nonce déjà validé.
        $file = $_FILES['backup_file'];
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) self::fail('Erreur de téléversement.');
        if ((int)($file['size'] ?? 0) < 1 || (int)$file['size'] > self::MAX_UPLOAD_BYTES) self::fail('Fichier vide ou trop volumineux.');
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        if (!$tmp || !is_uploaded_file($tmp)) self::fail('Fichier non valide.');
        $name = strtolower((string)($file['name'] ?? ''));
        if (substr($name, -4) === '.zip') return self::parse_zip($tmp, self::raw_settings());
        if (substr($name, -4) === '.csv') return self::parse_csv($tmp, $year, $park);
        self::fail('Extension de fichier non acceptée (.csv ou .zip).');
    }

    /** Ne modifie que l'année et le groupe de réglages explicitement choisis. */
    public static function merge_section($candidate, $all, $display_store) {
        $section = $candidate['scope'];
        $year = $candidate['year'];
        $data = $candidate['data'];
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) return false;
        if ($section === 'schedule') {
            foreach ($data['calendar'] as $key=>$value) $all['seasons'][$year][$key] = $value;
        } else {
            if (!isset($all['seasons'][$year]['tariffs']) || !is_array($all['seasons'][$year]['tariffs'])) $all['seasons'][$year]['tariffs'] = array();
            $target =& $all['seasons'][$year]['tariffs'];
            if ($section === 'visitors') {
                foreach ($data['tariffs'] as $key=>$value) {
                    if ($key === 'columns') {
                        if (!isset($target['columns']) || !is_array($target['columns'])) $target['columns'] = array();
                        foreach ($value as $column=>$rows) if ($column !== 'groups') $target['columns'][$column] = $rows;
                    } elseif ($key !== 'groups' && $key !== 'group_order') $target[$key] = $value;
                }
            } elseif ($section === 'groups') {
                $target['groups'] = $data['tariffs']['groups'];
                if (!isset($target['columns']) || !is_array($target['columns'])) $target['columns'] = array();
                $target['columns']['groups'] = $data['tariffs']['columns']['groups'];
                if (!isset($display_store['seasons']) || !is_array($display_store['seasons'])) $display_store['seasons'] = array();
                $display_store['seasons'][$year] = $data['group_display'];
            } else return false;
            unset($target);
        }
        return array('settings'=>$all,'display'=>$display_store);
    }

    private static function affected_state($candidate, $all, $display) {
        if ($candidate['scope'] === 'global') {
            $state = array();
            foreach (self::option_names() as $name) {
                $value = self::stored_option($name);
                if (isset($candidate['data'][$name])) $state[$name] = $value;
            }
            return $state;
        }
        return self::section_data($candidate['scope'], $candidate['year'], $all, $display);
    }

    private static function state_hash($state) {
        return hash('sha256', self::json_string($state));
    }

    private static function diff_paths($before, $after) {
        $one = array();
        $two = array();
        foreach (self::flatten($before) as $pair) $one[$pair[0]] = $pair[1];
        foreach (self::flatten($after) as $pair) $two[$pair[0]] = $pair[1];
        $all = array_unique(array_merge(array_keys($one), array_keys($two)));
        $out = array();
        foreach ($all as $path) {
            if (!array_key_exists($path, $one) || !array_key_exists($path, $two) || $one[$path] !== $two[$path]) $out[] = $path;
        }
        return array('count'=>count($out), 'sample'=>array_slice($out, 0, 20));
    }

    public static function preview() {
        self::require_admin('parcs_ht_backup_preview');
        $year = self::year(self::input('season_year'));
        $all = self::raw_settings();
        if (!isset($all['seasons'][$year])) self::fail('Saison inexistante.');
        $candidate = self::read_candidate($year, self::park($all));
        if ($candidate['scope'] !== 'global') {
            if ($candidate['year'] !== $year) self::fail('Année incompatible.');
            $merged = self::merge_section($candidate, $all, self::display_store());
            if ($merged === false) self::fail('Import incompatible.');
        }
        $before = self::affected_state($candidate, $all, self::display_store());
        $after = $candidate['data'];
        if ($candidate['scope'] === 'global') {
            $names = array_keys($candidate['data']);
        } else {
            $names = array($candidate['scope'] . ' / ' . $candidate['year']);
        }
        $candidate['before_hash'] = self::state_hash($before);
        $candidate['after_hash'] = self::state_hash($after);
        $candidate['overview'] = $names;
        $candidate['changed'] = $candidate['before_hash'] !== $candidate['after_hash'];
        $candidate['diff_paths'] = self::diff_paths($before, $after);
        $candidate['park'] = self::park($all);
        $candidate['host'] = self::host();
        $candidate['created'] = time();
        set_transient(self::preview_key(), $candidate, self::PREVIEW_TTL);
        wp_safe_redirect(self::url($year, array('backup_preview'=>'1')));
        exit;
    }

    private static function revision($before, $candidate) {
        $revisions = get_option(self::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        array_unshift($revisions, array(
            'at'=>time(),
            'scope'=>$candidate['scope'],
            'year'=>$candidate['year'],
            'user_id'=>get_current_user_id(),
            'previous'=>$before
        ));
        $new = array_slice($revisions, 0, 4);
        update_option(self::REVISIONS_OPTION, $new, false);
        if (get_option(self::REVISIONS_OPTION) !== $new) self::fail('Sauvegarde de sécurité impossible : import annulé.');
    }

    public static function apply() {
        self::require_admin('parcs_ht_backup_apply');
        if (self::input('confirm') !== '1') self::fail('Confirmation requise.');
        $candidate = get_transient(self::preview_key());
        if (!is_array($candidate) || time() - (int)($candidate['created'] ?? 0) > self::PREVIEW_TTL) self::fail('Aperçu expiré, recommencez.');
        $all = self::raw_settings();
        if (($candidate['park'] ?? '') !== self::park($all) || ($candidate['host'] ?? '') !== self::host()) self::fail('Site différent.');
        $display = self::display_store();
        $before = self::affected_state($candidate, $all, $display);
        if (self::state_hash($before) !== ($candidate['before_hash'] ?? '')) self::fail('Les données ont changé depuis l’aperçu. Recommencez.');
        if (empty($candidate['changed'])) self::fail('Aucune différence à importer.');
        self::revision($before, $candidate);
        if ($candidate['scope'] === 'global') {
            foreach ($candidate['data'] as $key=>$value) {
                update_option($key, $value, false);
                if (self::stored_option($key) != $value) self::fail('Échec d’écriture de ' . $key . ' (vérifiez la sauvegarde).');
            }
        } else {
            $merged = self::merge_section($candidate, $all, $display);
            if ($merged === false) self::fail('Impossible de fusionner l’import.');
            update_option(Parcs_HT_Defaults::OPTION, $merged['settings'], false);
            if ($candidate['scope'] === 'groups') update_option('parcs_ht_group_tariff_settings', $merged['display'], false);
            // Vérification du bloc réellement ciblé, indépendamment du reste de l'année.
            $after = self::section_data($candidate['scope'], $candidate['year'], self::raw_settings(), self::display_store());
            if (self::state_hash($after) !== $candidate['after_hash']) self::fail('Les données enregistrées diffèrent du CSV (voir la révision).');
        }
        delete_transient(self::preview_key());
        if (class_exists('Parcs_HT_Defaults')) Parcs_HT_Defaults::refresh_popup_flag();
        do_action('litespeed_purge_all');
        $year = $candidate['year'] !== '' ? $candidate['year'] : self::year(self::input('season_year'));
        wp_safe_redirect(self::url($year, array('backup_imported'=>'1')));
        exit;
    }

    public static function render_panel($year) {
        if ($year === '') return;
        $preview = get_transient(self::preview_key());
        ?>
        <section class="htp-overview-section" id="htp-backup-import" style="border:1px solid #dcdcde;border-radius:10px;padding:18px 20px;margin:18px 0;background:#fff">
            <h2>Import / export et sauvegarde</h2>
            <p>Exportez une archive complète de la configuration de ce site ou un CSV pour une rubrique de <?php echo esc_html($year); ?>. L'import n'agit qu'après un aperçu et une confirmation explicite.</p>
            <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur de succès purement visuel.
            if (isset($_GET['backup_imported'])) : ?><div class="notice notice-success inline"><p>Import terminé. Vérifiez maintenant le calendrier et les tarifs publics.</p></div><?php endif; ?>
            <div style="display:flex;flex-wrap:wrap;gap:22px;align-items:flex-start">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="flex:1;min-width:275px">
                    <input type="hidden" name="action" value="parcs_ht_backup_export">
                    <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                    <?php wp_nonce_field('parcs_ht_backup_export'); ?>
                    <p><label for="htp-backup-scope"><strong>Exporter</strong></label></p>
                    <p><select id="htp-backup-scope" name="backup_scope">
                        <option value="global">Tout le site — ZIP (JSON + CSV par année)</option>
                        <?php foreach (self::sections() as $scope=>$title) : ?><option value="<?php echo esc_attr($scope); ?>"><?php echo esc_html($title); ?> <?php echo esc_html($year); ?> — CSV</option><?php endforeach; ?>
                    </select></p>
                    <button type="submit" class="button button-secondary">Télécharger l'export</button>
                </form>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="flex:1;min-width:275px">
                    <input type="hidden" name="action" value="parcs_ht_backup_preview">
                    <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                    <?php wp_nonce_field('parcs_ht_backup_preview'); ?>
                    <p><label for="htp-backup-file"><strong>Importer un fichier</strong></label></p>
                    <p><input type="file" id="htp-backup-file" name="backup_file" accept=".zip,.csv,application/zip,text/csv" required></p>
                    <p class="description">Un ZIP restaure la configuration globale du même site. Un CSV modifie uniquement sa rubrique et son année. Ne mélangez pas les parcs.</p>
                    <button type="submit" class="button button-secondary">Analyser avant import</button>
                </form>
            </div>
            <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage de l'aperçu stocké côté serveur uniquement.
            if (isset($_GET['backup_preview']) && is_array($preview)) : ?>
                <div style="border-top:1px solid #ddd;margin-top:20px;padding-top:16px">
                    <h3>Aperçu de l'import</h3>
                    <p><strong>Portée :</strong> <?php echo esc_html($preview['scope'] === 'global' ? 'Configuration globale de toutes les années' : self::sections()[$preview['scope']] . ' — ' . $preview['year']); ?></p>
                    <p><strong>Sections concernées :</strong> <?php echo esc_html(implode(', ', (array)$preview['overview'])); ?></p>
                    <p><strong>Valeurs ajoutées, supprimées ou différentes :</strong> <?php echo esc_html((string)($preview['diff_paths']['count'] ?? 0)); ?></p>
                    <?php if ($preview['scope'] !== 'global' && !empty($preview['diff_paths']['sample'])) : ?>
                        <details><summary>Voir les chemins concernés (20 maximum)</summary><ul>
                            <?php foreach ($preview['diff_paths']['sample'] as $path) : ?><li><code><?php echo esc_html($path); ?></code></li><?php endforeach; ?>
                        </ul></details>
                    <?php endif; ?>
                    <?php if (empty($preview['changed'])) : ?>
                        <p>Aucune modification détectée. Aucun import nécessaire.</p>
                    <?php else : ?>
                        <p>Les données seront remplacées dans cette portée uniquement. Les autres rubriques restent inchangées pour les imports CSV. Vérifiez les années et les statuts de publication après un ZIP global.</p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="parcs_ht_backup_apply">
                            <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                            <?php wp_nonce_field('parcs_ht_backup_apply'); ?>
                            <p><label><input type="checkbox" name="confirm" value="1" required> Je confirme le remplacement des réglages indiqués ci-dessus.</label></p>
                            <button type="submit" class="button button-primary">Confirmer l'import</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
}
