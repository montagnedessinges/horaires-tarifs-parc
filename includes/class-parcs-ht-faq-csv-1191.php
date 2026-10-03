<?php

if (!defined('ABSPATH')) { exit; }

/**
 * FAQ 1.19.1 — administration CSV.
 *
 * La FAQ reste stockée dans parcs_ht_faq et reste totalement indépendante
 * des saisons. Cette couche remplace uniquement l'ancien parcours Google
 * Apps Script dans l'administration ; le moteur public 1.19.0 est conservé.
 */
final class Parcs_HT_FAQ_CSV_1191 {
    const PREVIEW_TTL = 1800;
    const MAX_BYTES = 5242880;
    const MAX_ROWS = 2000;

    public static function init() {
        // Désactive les anciens endpoints Google 1.19.0 : ils ne sont plus
        // accessibles depuis l'administration 1.19.1.
        remove_action('admin_post_parcs_ht_faq_save_connection', array('Parcs_HT_FAQ', 'save_connection'));
        remove_action('admin_post_parcs_ht_faq_check_google', array('Parcs_HT_FAQ', 'check_google'));
        remove_action('admin_post_parcs_ht_faq_apply_import', array('Parcs_HT_FAQ', 'apply_import'));

        add_action('admin_post_parcs_ht_faq_csv_preview', array(__CLASS__, 'preview_csv'));
        add_action('admin_post_parcs_ht_faq_csv_apply', array(__CLASS__, 'apply_csv'));
        add_action('admin_menu', array(__CLASS__, 'replace_page'), 60);
    }

    public static function replace_page() {
        if (!class_exists('Parcs_HT_Admin') || !class_exists('Parcs_HT_FAQ')) return;
        $hook = get_plugin_page_hookname(Parcs_HT_FAQ::PAGE, Parcs_HT_Admin::PAGE);
        if (!$hook) return;
        remove_action($hook, array('Parcs_HT_FAQ', 'page'));
        add_action($hook, array(__CLASS__, 'page'));
    }

    private static function raw_settings() {
        $settings = get_option(Parcs_HT_FAQ::OPTION, array());
        return is_array($settings) ? $settings : array();
    }

    private static function preview_key() {
        return 'parcs_ht_faq_csv_preview_' . (int)get_current_user_id();
    }

    private static function redirect($args = array()) {
        wp_safe_redirect(add_query_arg(array_merge(array('page'=>Parcs_HT_FAQ::PAGE), $args), admin_url('admin.php')));
        exit;
    }

    private static function error_redirect($message) {
        set_transient('parcs_ht_faq_csv_notice_' . (int)get_current_user_id(), sanitize_text_field((string)$message), 120);
        self::redirect(array('faq_csv_error'=>'1'));
    }

    private static function notice() {
        $key = 'parcs_ht_faq_csv_notice_' . (int)get_current_user_id();
        $message = get_transient($key);
        if ($message) delete_transient($key);
        return is_string($message) ? $message : '';
    }

    private static function normalize_text($value) {
        $value = remove_accents(wp_strip_all_tags((string)$value));
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string)$value, '-');
    }

    private static function value_from($row, $aliases, $fallback = '') {
        if (!is_array($row)) return $fallback;
        foreach ((array)$aliases as $alias) {
            if (array_key_exists($alias, $row)) return is_scalar($row[$alias]) ? (string)$row[$alias] : $fallback;
        }
        $normalized = array();
        foreach ($row as $key => $value) $normalized[self::normalize_text($key)] = $value;
        foreach ((array)$aliases as $alias) {
            $key = self::normalize_text($alias);
            if (array_key_exists($key, $normalized)) return is_scalar($normalized[$key]) ? (string)$normalized[$key] : $fallback;
        }
        return $fallback;
    }

    private static function list_from($value) {
        $parts = preg_split('/\s*\|\s*|\r\n|\r|\n/', (string)$value);
        $out = array();
        foreach ((array)$parts as $part) {
            $part = sanitize_text_field(trim((string)$part));
            if ($part !== '') $out[] = $part;
        }
        return array_values(array_unique($out));
    }

    private static function bool_value($value) {
        return in_array(self::normalize_text($value), array('1','oui','yes','ja','true','actif','active'), true);
    }

    private static function triple($fr, $en, $de) {
        return array('fr'=>(string)$fr, 'en'=>(string)$en, 'de'=>(string)$de);
    }

    private static function category_labels($category, $row) {
        $category = sanitize_text_field((string)$category);
        $slug = self::normalize_text($category);
        $dictionary = array(
            'horaires'=>self::triple('Horaires','Opening hours','Öffnungszeiten'),
            'meteo'=>self::triple('Météo','Weather','Wetter'),
            'visite'=>self::triple('Visite','Visit','Besuch'),
            'billets'=>self::triple('Billets','Tickets','Tickets'),
            'tarifs'=>self::triple('Tarifs','Prices','Preise'),
            'tarifs-reduits'=>self::triple('Tarifs réduits','Reduced rates','Ermäßigte Tarife'),
            'paiement'=>self::triple('Paiement','Payment','Zahlung'),
            'accessibilite'=>self::triple('Accessibilité','Accessibility','Barrierefreiheit'),
            'chiens'=>self::triple('Chiens','Dogs','Hunde'),
            'restauration'=>self::triple('Restauration','Food & drink','Gastronomie'),
            'pique-nique'=>self::triple('Pique-nique','Picnic','Picknick'),
            'familles'=>self::triple('Familles','Families','Familien'),
            'parking'=>self::triple('Parking','Parking','Parkplatz'),
            'acces'=>self::triple('Accès','Getting here','Anreise'),
            'camping-car'=>self::triple('Camping-car','Camper vans','Wohnmobile'),
            'groupes'=>self::triple('Groupes','Groups','Gruppen'),
            'groupes-scolaires'=>self::triple('Groupes scolaires','School groups','Schulgruppen'),
            'activites'=>self::triple('Activités','Activities','Aktivitäten'),
            'regles-du-parc'=>self::triple('Règles du parc','Visiting rules','Besuchsregeln'),
            'animations'=>self::triple('Animations','Activities & talks','Animationen'),
            'tombola-demande-de-lots'=>self::triple('Tombola / demande de lots','Raffle / prize requests','Tombola / Sachspendenanfragen'),
        );
        $labels = isset($dictionary[$slug]) ? $dictionary[$slug] : self::triple($category, $category, $category);
        $en = sanitize_text_field(self::value_from($row, array('Catégorie EN','Categorie EN','Category EN'), ''));
        $de = sanitize_text_field(self::value_from($row, array('Catégorie DE','Categorie DE','Kategorie DE'), ''));
        if ($en !== '') $labels['en'] = $en;
        if ($de !== '') $labels['de'] = $de;
        return $labels;
    }

    private static function public_url($row) {
        $candidate = trim(self::value_from($row, array('Lien public','URL publique','Public URL'), ''));
        if ($candidate === '') $candidate = trim(self::value_from($row, array('Source principale','Source'), ''));
        if (!preg_match('#^https://#i', $candidate)) return '';
        $url = esc_url_raw($candidate);
        $host = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
        if ($host === '' || in_array($host, array('docs.google.com','drive.google.com','mail.google.com'), true)) return '';
        return $url;
    }

    private static function default_link_label($category) {
        $slug = self::normalize_text($category);
        if ($slug === 'horaires') return self::triple('Voir les horaires à jour','See current opening hours','Aktuelle Öffnungszeiten ansehen');
        if ($slug === 'tarifs' || $slug === 'tarifs-reduits') return self::triple('Voir les tarifs à jour','See current prices','Aktuelle Preise ansehen');
        if (strpos($slug, 'groupes') === 0) return self::triple('Voir les informations groupes','See group information','Gruppeninformationen ansehen');
        if ($slug === 'acces' || $slug === 'camping-car' || $slug === 'parking') return self::triple('Voir les informations d’accès','See access information','Anreiseinformationen ansehen');
        return self::triple('Voir les informations à jour','See up-to-date information','Aktuelle Informationen ansehen');
    }

    private static function response_mode($row, $id, $category, $dynamic, $public_url) {
        $raw = self::normalize_text(self::value_from($row, array('Mode FAQ','Type de réponse','Type de reponse','Response mode'), ''));
        if (in_array($raw, array('direct','reponse-directe'), true)) return 'direct';
        if (in_array($raw, array('lien','reponse-lien','answer-link','answer-linked'), true)) return 'answer_link';
        if (in_array($raw, array('renvoi','renvoi-canonique','canonical','lien-dynamique','link-only'), true)) return 'canonical';
        $category_slug = self::normalize_text($category);
        if ($public_url !== '' && ($category_slug === 'horaires' || $category_slug === 'tarifs') && preg_match('/-(?:OUV|TAR)-001$/', strtoupper((string)$id))) return 'canonical';
        if ($dynamic && $public_url !== '') return 'answer_link';
        return 'direct';
    }

    private static function import_row($row, $order) {
        if (!is_array($row)) return null;
        $id = strtoupper(sanitize_text_field(self::value_from($row, array('ID stable','ID','Stable ID'), '')));
        if ($id === '' || !preg_match('/^[A-Z0-9][A-Z0-9_-]{2,80}$/', $id)) return null;

        $priority = strtoupper(sanitize_text_field(self::value_from($row, array('Priorité','Priorite','Priority'), 'P3')));
        if (!in_array($priority, array('P1','P2','P3'), true)) $priority = 'P3';
        $category = sanitize_text_field(self::value_from($row, array('Catégorie','Categorie','Category'), 'Autres'));
        if ($category === '') $category = 'Autres';

        $question = array(
            'fr'=>sanitize_text_field(self::value_from($row, array('Question canonique FR','Question FR'), '')),
            'en'=>sanitize_text_field(self::value_from($row, array('Question canonique EN','Question EN'), '')),
            'de'=>sanitize_text_field(self::value_from($row, array('Question canonique DE','Question DE','Frage DE'), '')),
        );
        $answer = array(
            'fr'=>wp_kses_post(self::value_from($row, array('Réponse courte FR','Reponse courte FR','Réponse FR','Reponse FR'), '')),
            'en'=>wp_kses_post(self::value_from($row, array('Réponse courte EN','Reponse courte EN','Answer EN','Short answer EN'), '')),
            'de'=>wp_kses_post(self::value_from($row, array('Réponse courte DE','Reponse courte DE','Antwort DE','Kurzantwort DE'), '')),
        );
        if ($question['fr'] === '' || $answer['fr'] === '') return null;

        $variants = array(
            'fr'=>self::list_from(self::value_from($row, array('Variantes / formulations IA FR','Variantes FR','Synonymes FR'), '')),
            'en'=>self::list_from(self::value_from($row, array('Variantes / formulations IA EN','Variantes EN','Synonymes EN','AI variants / phrasings EN'), '')),
            'de'=>self::list_from(self::value_from($row, array('Variantes / formulations IA DE','Variantes DE','Synonymes DE','Varianten / KI-Formulierungen DE'), '')),
        );

        $visibility = sanitize_text_field(self::value_from($row, array('Usage / visibilité','Usage / visibilite','Usage','Visibilité','Visibility'), ''));
        $status = sanitize_text_field(self::value_from($row, array('Statut','Status'), ''));
        $status_key = self::normalize_text($status);
        $visibility_key = self::normalize_text($visibility);
        $publishable = in_array($status_key, array('valide','publie','published'), true) && (strpos($visibility_key, 'faq-publique') !== false || strpos($visibility_key, 'public-faq') !== false);
        $dynamic = self::bool_value(self::value_from($row, array('Donnée dynamique ?','Donnee dynamique ?','Dynamique','Dynamic'), ''));
        $public_url = self::public_url($row);
        $link_label = self::default_link_label($category);
        foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $language => $suffix) {
            $custom = sanitize_text_field(self::value_from($row, array('Libellé du lien ' . $suffix, 'Libelle du lien ' . $suffix, 'Link label ' . $suffix), ''));
            if ($custom !== '') $link_label[$language] = $custom;
        }

        return array(
            'id'=>$id,
            'priority'=>$priority,
            'category'=>$category,
            'category_label'=>self::category_labels($category, $row),
            'question'=>$question,
            'variants'=>$variants,
            'answer'=>$answer,
            'visibility'=>$visibility,
            'dynamic'=>$dynamic ? '1' : '0',
            'source_url'=>esc_url_raw(self::value_from($row, array('Source principale','Source'), '')),
            'public_url'=>$public_url,
            'link_label'=>$link_label,
            'response_mode'=>self::response_mode($row, $id, $category, $dynamic, $public_url),
            'verified'=>sanitize_text_field(self::value_from($row, array('Vérifié le','Verifie le','Verified'), '')),
            'status'=>$status,
            'notes'=>sanitize_textarea_field(self::value_from($row, array('Notes / garde-fou','Notes','Guardrail'), '')),
            'enabled'=>$publishable ? '1' : '0',
            'remote_order'=>(int)$order,
        );
    }

    private static function import_knowledge_row($row, $order) {
        if (!is_array($row)) return null;
        $id = strtoupper(sanitize_text_field(self::value_from($row, array('ID stable','ID'), '')));
        if ($id === '' || strpos($id, 'SIN-COM-') !== 0) return null;

        $question_fr = sanitize_text_field(self::value_from($row, array('Question canonique','Question canonique FR','Question FR'), ''));
        $answer_fr = wp_kses_post(self::value_from($row, array('Réponse courte vérifiée','Reponse courte verifiee','Réponse courte FR','Reponse courte FR'), ''));
        if ($question_fr === '' || $answer_fr === '') return null;

        $visibility = sanitize_text_field(self::value_from($row, array('Usage','Usage / visibilité','Usage / visibilite'), ''));
        $status = sanitize_text_field(self::value_from($row, array('Statut','Status'), ''));
        $status_key = self::normalize_text($status);
        $visibility_key = self::normalize_text($visibility);
        $publishable = in_array($status_key, array('valide','publie','published'), true) && strpos($visibility_key, 'ia') !== false;

        $source = esc_url_raw(self::value_from($row, array('Source officielle','Source principale','Source'), ''));
        $public_url = self::public_url(array('Source principale'=>$source));

        return array(
            'id'=>$id,
            'priority'=>'P2',
            'category'=>'Règles du parc',
            'category_label'=>self::triple('Règles du parc','Visiting rules','Besuchsregeln'),
            'question'=>array('fr'=>$question_fr,'en'=>'','de'=>''),
            'variants'=>array(
                'fr'=>self::list_from(self::value_from($row, array('Variantes / formulations IA','Variantes / formulations IA FR','Variantes FR'), '')),
                'en'=>array(),
                'de'=>array(),
            ),
            'answer'=>array('fr'=>$answer_fr,'en'=>'','de'=>''),
            'visibility'=>$visibility,
            'dynamic'=>'0',
            'source_url'=>$source,
            'public_url'=>$public_url,
            'link_label'=>self::triple('Source','Source','Quelle'),
            'response_mode'=>'direct',
            'verified'=>sanitize_text_field(self::value_from($row, array('Vérifié le','Verifie le','Verified'), '')),
            'status'=>$status,
            'notes'=>'Import depuis « Connaissances singes - IA » ; affichage secondaire discret dans « Règles du parc ».',
            'enabled'=>$publishable ? '1' : '0',
            'remote_order'=>10000 + (int)$order,
        );
    }

    private static function item_fingerprint($item) {
        $copy = is_array($item) ? $item : array();
        unset($copy['remote_order']);
        return hash('sha256', wp_json_encode($copy));
    }

    private static function strip_bom($value) {
        $value = (string)$value;
        return strncmp($value, "\xEF\xBB\xBF", 3) === 0 ? substr($value, 3) : $value;
    }

    private static function detect_delimiter($path) {
        foreach (array(',', ';', "\t") as $delimiter) {
            $handle = fopen($path, 'rb');
            if (!$handle) continue;
            $found = false;
            for ($i = 0; $i < 15 && ($row = fgetcsv($handle, 0, $delimiter)) !== false; $i++) {
                if (isset($row[0])) $row[0] = self::strip_bom($row[0]);
                if (in_array('ID stable', array_map('trim', $row), true)) { $found = true; break; }
            }
            fclose($handle);
            if ($found) return $delimiter;
        }
        return '';
    }

    private static function parse_csv($path, $park_code, $source_kind = 'faq') {
        $source_kind = $source_kind === 'knowledge' ? 'knowledge' : 'faq';
        $delimiter = self::detect_delimiter($path);
        if ($delimiter === '') self::error_redirect('Format CSV non reconnu : la colonne « ID stable » est introuvable.');
        $handle = fopen($path, 'rb');
        if (!$handle) self::error_redirect('Impossible de lire le fichier CSV.');

        $headers = null;
        $records = array();
        $seen = array();
        $row_number = 0;
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row_number++;
            if ($row_number > self::MAX_ROWS + 20) { fclose($handle); self::error_redirect('Le CSV contient trop de lignes.'); }
            if (isset($cells[0])) $cells[0] = self::strip_bom($cells[0]);
            $cells = array_map(static function ($value) { return trim((string)$value); }, $cells);

            if ($headers === null) {
                if (!in_array('ID stable', $cells, true)) continue;
                $headers = $cells;
                $required = $source_kind === 'knowledge'
                    ? array('ID stable','Question canonique','Réponse courte vérifiée','Statut','Usage')
                    : array('ID stable','Question canonique FR','Réponse courte FR','Statut','Usage / visibilité');
                foreach ($required as $required_header) {
                    if (!in_array($required_header, $headers, true)) { fclose($handle); self::error_redirect('CSV incomplet : colonne requise manquante (« ' . $required_header . ' »).'); }
                }
                $non_empty = array_values(array_filter($headers, static function ($value) { return $value !== ''; }));
                if (count($non_empty) !== count(array_unique($non_empty))) { fclose($handle); self::error_redirect('CSV invalide : un nom de colonne est dupliqué.'); }
                continue;
            }

            $has_value = false;
            foreach ($cells as $cell) if ($cell !== '') { $has_value = true; break; }
            if (!$has_value) continue;
            if (count($cells) < count($headers)) $cells = array_pad($cells, count($headers), '');
            if (count($cells) > count($headers)) $cells = array_slice($cells, 0, count($headers));
            $record = array();
            foreach ($headers as $index => $header) if ($header !== '') $record[$header] = $cells[$index] ?? '';

            $id = strtoupper(trim((string)($record['ID stable'] ?? '')));
            if ($id === '') continue;
            if ($source_kind === 'knowledge') {
                if (strpos($id, 'SIN-COM-') !== 0) { fclose($handle); self::error_redirect('Le CSV de connaissances contient un ID non reconnu (' . $id . '). Import annulé.'); }
            } elseif (strpos($id, $park_code . '-') !== 0) {
                fclose($handle);
                self::error_redirect('Le CSV contient une fiche d’un autre parc (' . $id . '). Import annulé.');
            }
            if (isset($seen[$id])) { fclose($handle); self::error_redirect('Le CSV contient un ID stable dupliqué : ' . $id . '.'); }
            $seen[$id] = true;
            $records[] = $record;
        }
        fclose($handle);
        if ($headers === null) self::error_redirect('Format CSV non reconnu.');
        if (!$records) self::error_redirect('Aucune fiche FAQ n’a été trouvée dans le CSV.');
        return $records;
    }

    public static function preview_csv() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_csv_preview');
        delete_transient(self::preview_key());

        $park_code = isset($_POST['park_code']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['park_code']))) : '';
        if (!in_array($park_code, array('MDS','FDS'), true)) self::error_redirect('Choisissez le parc correspondant au CSV.');
        $source_kind = isset($_POST['source_kind']) ? sanitize_key(wp_unslash($_POST['source_kind'])) : 'faq';
        if (!in_array($source_kind, array('faq','knowledge'), true)) self::error_redirect('Type de CSV non reconnu.');
        if (empty($_FILES['faq_csv']) || !is_array($_FILES['faq_csv'])) self::error_redirect('Choisissez un fichier CSV.');
        $file = $_FILES['faq_csv'];
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) self::error_redirect('Le téléversement du CSV a échoué.');
        $name = sanitize_file_name((string)($file['name'] ?? ''));
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') self::error_redirect('Le fichier doit avoir l’extension .csv.');
        $size = (int)($file['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_BYTES) self::error_redirect('Le CSV est vide ou dépasse 5 Mo.');
        $path = (string)($file['tmp_name'] ?? '');
        if ($path === '' || !is_uploaded_file($path)) self::error_redirect('Fichier CSV temporaire invalide.');

        $records = self::parse_csv($path, $park_code, $source_kind);
        $current = Parcs_HT_FAQ::settings();
        $current_index = array();
        foreach ((array)($current['items'] ?? array()) as $item) {
            if (is_array($item) && !empty($item['id'])) $current_index[(string)$item['id']] = $item;
        }

        $rows = array();
        $counts = array('new'=>0,'modified'=>0,'unchanged'=>0,'blocked'=>0,'invalid'=>0);
        foreach ($records as $order => $record) {
            $item = $source_kind === 'knowledge' ? self::import_knowledge_row($record, $order) : self::import_row($record, $order);
            if (!$item) { $counts['invalid']++; continue; }
            $id = (string)$item['id'];
            $publishable = (string)$item['enabled'] === '1';
            if (!$publishable) { $change = 'blocked'; $counts['blocked']++; }
            elseif (!isset($current_index[$id])) { $change = 'new'; $counts['new']++; }
            elseif (hash_equals(self::item_fingerprint($current_index[$id]), self::item_fingerprint($item))) { $change = 'unchanged'; $counts['unchanged']++; }
            else { $change = 'modified'; $counts['modified']++; }
            $rows[] = array('id'=>$id,'change'=>$change,'publishable'=>$publishable,'item'=>$item);
        }
        if (!$rows) self::error_redirect('Aucune fiche exploitable n’a été trouvée dans le CSV.');

        set_transient(self::preview_key(), array(
            'created_at'=>time(),
            'park_code'=>$park_code,
            'source_kind'=>$source_kind,
            'source_name'=>$name,
            'counts'=>$counts,
            'rows'=>$rows,
        ), self::PREVIEW_TTL);
        self::redirect(array('csv_preview'=>'1'));
    }

    private static function add_revision($settings, $reason) {
        $revisions = get_option(Parcs_HT_FAQ::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        array_unshift($revisions, array(
            'id'=>gmdate('YmdHis') . '-' . wp_generate_password(6, false, false),
            'created_at'=>time(),
            'reason'=>sanitize_text_field((string)$reason),
            'items'=>isset($settings['items']) && is_array($settings['items']) ? array_values($settings['items']) : array(),
            'has_import'=>(string)($settings['has_import'] ?? '0'),
            'last_import'=>(string)($settings['last_import'] ?? ''),
        ));
        $revisions = array_slice($revisions, 0, 10);
        update_option(Parcs_HT_FAQ::REVISIONS_OPTION, $revisions, false);
        if (get_option(Parcs_HT_FAQ::REVISIONS_OPTION) !== $revisions) self::error_redirect('Sauvegarde de sécurité impossible : import annulé.');
    }

    public static function apply_csv() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_csv_apply');
        $preview = get_transient(self::preview_key());
        if (!is_array($preview) || empty($preview['rows'])) self::error_redirect('L’aperçu CSV a expiré. Importez à nouveau le fichier.');

        $selected = array();
        if (isset($_POST['selected']) && is_array($_POST['selected'])) $selected = array_values(array_unique(array_map('sanitize_text_field', wp_unslash($_POST['selected']))));
        if (!$selected) self::error_redirect('Aucune modification n’a été sélectionnée.');

        $settings = self::raw_settings();
        $index = array();
        foreach ((array)($settings['items'] ?? array()) as $item) if (is_array($item) && !empty($item['id'])) $index[(string)$item['id']] = $item;
        $applied = 0;
        foreach ((array)$preview['rows'] as $row) {
            if (!is_array($row) || empty($row['publishable']) || !isset($row['item']) || !is_array($row['item'])) continue;
            $id = (string)($row['id'] ?? '');
            if ($id === '' || !in_array($id, $selected, true)) continue;
            $index[$id] = $row['item'];
            $applied++;
        }
        if ($applied < 1) self::error_redirect('Aucune fiche validée et publiable n’a été sélectionnée.');

        $revision_reason = (string)($preview['source_kind'] ?? 'faq') === 'knowledge' ? 'Avant import CSV connaissances IA' : 'Avant import CSV FAQ';
        self::add_revision($settings, $revision_reason);
        $items = array_values($index);
        usort($items, static function ($a, $b) {
            $pa = (int)substr((string)($a['priority'] ?? 'P3'), 1);
            $pb = (int)substr((string)($b['priority'] ?? 'P3'), 1);
            if ($pa !== $pb) return $pa <=> $pb;
            return (int)($a['remote_order'] ?? 9999) <=> (int)($b['remote_order'] ?? 9999);
        });
        $settings['items'] = $items;
        $settings['has_import'] = '1';
        $settings['last_import'] = gmdate('c');
        $settings['store_version'] = 1;
        update_option(Parcs_HT_FAQ::OPTION, $settings, false);
        if (get_option(Parcs_HT_FAQ::OPTION) !== $settings) self::error_redirect('Échec de l’enregistrement FAQ : import annulé.');
        delete_transient(self::preview_key());
        do_action('litespeed_purge_all');
        self::redirect(array('csv_imported'=>(string)$applied));
    }

    private static function change_label($change) {
        $map = array('new'=>'Nouvelle','modified'=>'Modifiée','unchanged'=>'Identique','blocked'=>'Non publiable');
        return $map[$change] ?? $change;
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::raw_settings();
        $display = Parcs_HT_FAQ::settings();
        $items = (array)($display['items'] ?? array());
        $preview = get_transient(self::preview_key());
        if (!is_array($preview)) $preview = array();
        $revisions = get_option(Parcs_HT_FAQ::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        $error = self::notice();
        $enabled_count = 0;
        foreach ($items as $item) if (is_array($item) && (string)($item['enabled'] ?? '0') === '1') $enabled_count++;
        ?>
        <div class="wrap htp-faq-admin">
            <h1>FAQ</h1>
            <p class="description">Base FAQ globale du parc, indépendante des années et des saisons. Les mises à jour de contenu se font désormais par import CSV.</p>
            <?php if ($error !== '') : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div><?php endif; ?>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les réglages FAQ ont été enregistrés.</p></div><?php endif; ?>
            <?php if (isset($_GET['csv_imported'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html((int)$_GET['csv_imported']); ?> fiche(s) FAQ ont été appliquées. Les fiches absentes du CSV n’ont pas été supprimées.</p></div><?php endif; ?>
            <?php if (isset($_GET['restored'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La révision FAQ a été restaurée.</p></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">
                <section class="postbox" style="padding:18px;">
                    <h2 style="margin-top:0;">Affichage public</h2>
                    <p><strong><?php echo esc_html($enabled_count); ?></strong> fiche(s) publiable(s) actuellement disponibles.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_save_settings">
                        <?php wp_nonce_field('parcs_ht_faq_save_settings'); ?>
                        <label><input type="checkbox" name="faq[enabled]" value="1" <?php checked((string)($settings['enabled'] ?? '0'), '1'); ?>> Activer la FAQ publique</label><br>
                        <label><input type="checkbox" name="faq[show_search]" value="1" <?php checked((string)($settings['show_search'] ?? '1'), '1'); ?>> Afficher la recherche</label><br>
                        <label><input type="checkbox" name="faq[show_categories]" value="1" <?php checked((string)($settings['show_categories'] ?? '1'), '1'); ?>> Afficher les filtres par catégorie</label>
                        <input type="hidden" name="faq[_complete]" value="1">
                        <p><button type="submit" class="button button-primary">Enregistrer l’affichage FAQ</button></p>
                    </form>
                </section>

                <section class="postbox" style="padding:18px;">
                    <h2 style="margin-top:0;">Importer la FAQ depuis un CSV</h2>
                    <p>Dans Google Sheets : ouvrez l’onglet du parc puis <strong>Fichier → Télécharger → Valeurs séparées par des virgules (.csv)</strong>. Importez ensuite ce fichier ici.</p>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_csv_preview">
                        <input type="hidden" name="source_kind" value="faq">
                        <?php wp_nonce_field('parcs_ht_faq_csv_preview'); ?>
                        <p><label><strong>Parc du fichier</strong><br><select name="park_code"><option value="MDS">Montagne des Singes (MDS)</option><option value="FDS">Forêt des Singes (FDS)</option></select></label></p>
                        <p><label><strong>Fichier CSV</strong><br><input type="file" name="faq_csv" accept=".csv,text/csv" required></label></p>
                        <p><button type="submit" class="button button-primary">Analyser le CSV</button></p>
                    </form>
                    <p class="description">L’analyse ne modifie rien. L’extension vérifie le parc, les colonnes, les IDs et les statuts avant d’afficher un aperçu.</p>
                    <hr>
                    <h3>Connaissances singes → Règles du parc</h3>
                    <p>Exportez l’onglet <strong>« Connaissances singes - IA »</strong> en CSV. Les lignes validées destinées à l’IA sont importées dans le même stockage FAQ et affichées dans la catégorie secondaire « Règles du parc ».</p>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_csv_preview">
                        <input type="hidden" name="source_kind" value="knowledge">
                        <?php wp_nonce_field('parcs_ht_faq_csv_preview'); ?>
                        <p><label><strong>Parc destinataire</strong><br><select name="park_code"><option value="MDS">Montagne des Singes (MDS)</option><option value="FDS">Forêt des Singes (FDS)</option></select></label></p>
                        <p><label><strong>CSV Connaissances singes - IA</strong><br><input type="file" name="faq_csv" accept=".csv,text/csv" required></label></p>
                        <p><button type="submit" class="button">Analyser les connaissances</button></p>
                    </form>
                    <p class="description">Les IDs attendus commencent par <code>SIN-COM-</code>. Les fiches sont fusionnées avec la FAQ existante ; aucune fiche absente n’est supprimée.</p>
                </section>

                <section class="postbox" style="padding:18px;">
                    <h2 style="margin-top:0;">Shortcodes</h2>
                    <code>[parc_faq]</code><br><code>[parc_faq_fr]</code><br><code>[parc_faq_en]</code><br><code>[parc_faq_de]</code>
                    <p class="description">Exemple : <code>[parc_faq_fr categorie="billets"]</code>. Options : <code>recherche="0"</code>, <code>categories="0"</code>, <code>titre="0"</code>.</p>
                    <h3>Sécurité</h3>
                    <ul style="list-style:disc;padding-left:20px;"><li>Aucune suppression automatique.</li><li>Les lignes non validées ou non publiques sont bloquées.</li><li>Une révision est créée avant chaque import.</li><li>L’import n’écrit jamais dans les horaires, tarifs, saisons ou devis.</li></ul>
                </section>
            </div>

            <?php if (!empty($preview['rows'])) : ?>
                <section class="postbox" style="padding:18px;margin-top:18px;">
                    <h2 style="margin-top:0;">Aperçu CSV — aucune modification n’est encore appliquée</h2>
                    <p><strong>Fichier :</strong> <?php echo esc_html((string)($preview['source_name'] ?? '')); ?> · <strong>Parc :</strong> <?php echo esc_html((string)($preview['park_code'] ?? '')); ?> · <strong>Type :</strong> <?php echo (string)($preview['source_kind'] ?? 'faq') === 'knowledge' ? 'Connaissances IA' : 'FAQ du parc'; ?></p>
                    <?php $counts = (array)($preview['counts'] ?? array()); ?>
                    <p><strong><?php echo esc_html((int)($counts['new'] ?? 0)); ?></strong> nouvelle(s) · <strong><?php echo esc_html((int)($counts['modified'] ?? 0)); ?></strong> modifiée(s) · <strong><?php echo esc_html((int)($counts['unchanged'] ?? 0)); ?></strong> identique(s) · <strong><?php echo esc_html((int)($counts['blocked'] ?? 0)); ?></strong> non publiable(s) · <strong><?php echo esc_html((int)($counts['invalid'] ?? 0)); ?></strong> invalide(s)</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_csv_apply">
                        <?php wp_nonce_field('parcs_ht_faq_csv_apply'); ?>
                        <div style="overflow:auto;"><table class="widefat striped"><thead><tr><th>Appliquer</th><th>ID</th><th>État</th><th>Catégorie</th><th>Question FR</th><th>Langues</th><th>Statut source</th></tr></thead><tbody>
                        <?php foreach ((array)$preview['rows'] as $preview_row) : $item = (array)($preview_row['item'] ?? array()); $publishable = !empty($preview_row['publishable']); $change = (string)($preview_row['change'] ?? ''); ?>
                            <tr><td><?php if ($publishable && $change !== 'unchanged') : ?><input type="checkbox" name="selected[]" value="<?php echo esc_attr((string)$preview_row['id']); ?>" checked><?php else : ?>—<?php endif; ?></td><td><code><?php echo esc_html((string)$preview_row['id']); ?></code></td><td><?php echo esc_html(self::change_label($change)); ?></td><td><?php echo esc_html((string)($item['category'] ?? '')); ?></td><td><?php echo esc_html((string)($item['question']['fr'] ?? '')); ?></td><td><?php echo !empty($item['question']['fr']) && !empty($item['answer']['fr']) ? 'FR ' : ''; ?><?php echo !empty($item['question']['en']) && !empty($item['answer']['en']) ? 'EN ' : ''; ?><?php echo !empty($item['question']['de']) && !empty($item['answer']['de']) ? 'DE' : ''; ?></td><td><?php echo esc_html((string)($item['status'] ?? '')); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                        <p><button type="submit" class="button button-primary">Appliquer les modifications cochées</button> <span class="description">Les lignes absentes du CSV restent inchangées.</span></p>
                    </form>
                </section>
            <?php endif; ?>

            <section class="postbox" style="padding:18px;margin-top:18px;">
                <h2 style="margin-top:0;">Fiches actuellement enregistrées</h2>
                <?php if (!$items) : ?><p>Aucune fiche n’a encore été importée.</p><?php else : ?>
                    <div style="overflow:auto;"><table class="widefat striped"><thead><tr><th>ID</th><th>Priorité</th><th>Catégorie</th><th>Question FR</th><th>FR</th><th>EN</th><th>DE</th><th>Publication</th><th>Vérifié le</th></tr></thead><tbody>
                    <?php foreach ($items as $item) : if (!is_array($item)) continue; ?>
                        <tr><td><code><?php echo esc_html((string)($item['id'] ?? '')); ?></code></td><td><?php echo esc_html((string)($item['priority'] ?? '')); ?></td><td><?php echo esc_html((string)($item['category'] ?? '')); ?></td><td><?php echo esc_html((string)($item['question']['fr'] ?? '')); ?></td><td><?php echo !empty($item['answer']['fr']) ? '✓' : '—'; ?></td><td><?php echo !empty($item['answer']['en']) ? '✓' : '—'; ?></td><td><?php echo !empty($item['answer']['de']) ? '✓' : '—'; ?></td><td><?php if ((string)($item['enabled'] ?? '0') !== '1') echo 'Interne'; elseif (self::normalize_text((string)($item['category'] ?? '')) === 'regles-du-parc') echo 'Secondaire / IA'; else echo 'FAQ principale'; ?></td><td><?php echo esc_html((string)($item['verified'] ?? '')); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>

            <?php if ($revisions) : ?>
                <section class="postbox" style="padding:18px;margin-top:18px;">
                    <h2 style="margin-top:0;">Révisions de sécurité</h2>
                    <p class="description">Les 10 dernières sauvegardes automatiques de la base FAQ sont conservées.</p>
                    <table class="widefat striped"><thead><tr><th>Date</th><th>Motif</th><th>Fiches</th><th></th></tr></thead><tbody>
                    <?php foreach ($revisions as $revision) : if (!is_array($revision)) continue; ?>
                        <tr><td><?php echo esc_html(wp_date('d/m/Y H:i', (int)($revision['created_at'] ?? time()))); ?></td><td><?php echo esc_html((string)($revision['reason'] ?? '')); ?></td><td><?php echo esc_html(count((array)($revision['items'] ?? array()))); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_faq_restore_revision"><input type="hidden" name="revision_id" value="<?php echo esc_attr((string)($revision['id'] ?? '')); ?>"><?php wp_nonce_field('parcs_ht_faq_restore_revision'); ?><button type="submit" class="button" onclick="return confirm('Restaurer cette version de la FAQ ? Une sauvegarde de l’état actuel sera créée avant la restauration.');">Restaurer</button></form></td></tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </section>
            <?php endif; ?>
        </div>
        <?php
    }
}
