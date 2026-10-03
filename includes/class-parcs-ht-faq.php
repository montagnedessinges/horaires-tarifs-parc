<?php

if (!defined('ABSPATH')) { exit; }

/**
 * FAQ globale 1.19.0.
 *
 * Ce module est volontairement indépendant des saisons : il utilise sa propre
 * option, ses propres handlers admin et ses propres révisions. Aucune action de
 * cet écran ne doit écrire dans Parcs_HT_Defaults::OPTION.
 */
final class Parcs_HT_FAQ {
    const OPTION = 'parcs_ht_faq';
    const REVISIONS_OPTION = 'parcs_ht_faq_revisions';
    const PAGE = 'parcs-ht-faq';
    const STORE_VERSION = 1;
    const PREVIEW_TTL = 1800;

    private static $instance = 0;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 50);
        add_action('admin_post_parcs_ht_faq_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_parcs_ht_faq_save_connection', array(__CLASS__, 'save_connection'));
        add_action('admin_post_parcs_ht_faq_check_google', array(__CLASS__, 'check_google'));
        add_action('admin_post_parcs_ht_faq_apply_import', array(__CLASS__, 'apply_import'));
        add_action('admin_post_parcs_ht_faq_restore_revision', array(__CLASS__, 'restore_revision'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue_assets'), 22);

        add_shortcode('parc_faq', static function ($atts = array()) {
            $language = class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::language() : 'fr';
            return Parcs_HT_FAQ::render($language, is_array($atts) ? $atts : array());
        });
        foreach (array('fr', 'en', 'de') as $language) {
            add_shortcode('parc_faq_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_FAQ::render($language, is_array($atts) ? $atts : array());
            });
        }
    }

    private static function triple($fr, $en, $de) {
        return array('fr'=>(string)$fr, 'en'=>(string)$en, 'de'=>(string)$de);
    }

    private static function defaults() {
        return array(
            'store_version'=>self::STORE_VERSION,
            'enabled'=>'0',
            'show_search'=>'1',
            'show_categories'=>'1',
            'has_import'=>'0',
            'google'=>array(
                'sheet_url'=>'',
                'endpoint'=>'',
                'secret'=>'',
                'tab'=>'Montagne des Singes',
                'park_code'=>'MDS',
            ),
            'last_import'=>'',
            'items'=>array(),
        );
    }

    private static function legacy_ai_items() {
        if (!class_exists('Parcs_HT_AI_Google')) return array();
        $ai = Parcs_HT_AI_Google::settings();
        $out = array();
        foreach ((array)($ai['knowledge'] ?? array()) as $index => $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            $question = self::clean_translation($row['question'] ?? array(), false);
            $answer = self::clean_translation($row['answer'] ?? array(), false);
            if ($question['fr'] === '' || $answer['fr'] === '') continue;
            $id = sanitize_key((string)($row['id'] ?? 'legacy-ai-' . ((int)$index + 1)));
            $out[] = array(
                'id'=>$id,
                'priority'=>'P1',
                'category'=>'Règles du parc',
                'category_label'=>self::triple('Règles du parc', 'Visiting rules', 'Besuchsregeln'),
                'question'=>$question,
                'variants'=>array(
                    'fr'=>array_values(array_filter(array_map('sanitize_text_field', (array)($row['keywords'] ?? array())))),
                    'en'=>array(),
                    'de'=>array(),
                ),
                'answer'=>$answer,
                'visibility'=>'FAQ publique / IA',
                'dynamic'=>'0',
                'source_url'=>'',
                'public_url'=>'',
                'link_label'=>self::triple('', '', ''),
                'response_mode'=>'direct',
                'verified'=>sanitize_text_field((string)($row['last_verified'] ?? '')),
                'status'=>'Validé',
                'notes'=>'Import de compatibilité depuis IA & Google 1.18.0.',
                'enabled'=>'1',
                'remote_order'=>(int)$index,
            );
        }
        return $out;
    }

    public static function settings() {
        $defaults = self::defaults();
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();

        $out = array_replace($defaults, $saved);
        $out['google'] = array_replace($defaults['google'], isset($saved['google']) && is_array($saved['google']) ? $saved['google'] : array());
        $out['items'] = isset($saved['items']) && is_array($saved['items']) ? array_values($saved['items']) : array();

        // Tant qu'aucun import FAQ n'a été appliqué, la réponse officielle 1.18
        // reste disponible comme amorce sans recopier physiquement son option.
        if ((string)($out['has_import'] ?? '0') !== '1' && !$out['items']) {
            $out['items'] = self::legacy_ai_items();
        }
        return $out;
    }

    private static function raw_settings() {
        $defaults = self::defaults();
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $out = array_replace($defaults, $saved);
        $out['google'] = array_replace($defaults['google'], isset($saved['google']) && is_array($saved['google']) ? $saved['google'] : array());
        $out['items'] = isset($saved['items']) && is_array($saved['items']) ? array_values($saved['items']) : array();
        return $out;
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'FAQ',
            'FAQ',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    private static function clean_translation($value, $sanitize = true) {
        $value = is_array($value) ? $value : array();
        $out = array();
        foreach (array('fr','en','de') as $language) {
            $text = (string)($value[$language] ?? '');
            $out[$language] = $sanitize ? wp_kses_post($text) : $text;
        }
        return $out;
    }

    private static function normalize_text($value) {
        $value = remove_accents(wp_strip_all_tags((string)$value));
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string)$value, '-');
    }

    private static function bool_value($value) {
        $value = self::normalize_text($value);
        return in_array($value, array('1','oui','yes','ja','true','actif','active'), true);
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

    private static function category_labels($category, $row = array()) {
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
        foreach (array('en'=>'Catégorie EN','de'=>'Catégorie DE') as $language => $header) {
            $custom = sanitize_text_field(self::value_from($row, array($header, 'Categorie ' . strtoupper($language)), ''));
            if ($custom !== '') $labels[$language] = $custom;
        }
        return $labels;
    }

    private static function public_url($row) {
        $candidate = self::value_from($row, array('Lien public','URL publique','Public URL'), '');
        if ($candidate === '') $candidate = self::value_from($row, array('Source principale','Source'), '');
        $candidate = trim((string)$candidate);
        if (!preg_match('#^https://#i', $candidate)) return '';
        $url = esc_url_raw($candidate);
        $host = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
        if ($host === '' || in_array($host, array('docs.google.com','drive.google.com','mail.google.com'), true)) return '';
        return $url;
    }

    private static function response_mode($row, $id, $category, $dynamic, $public_url) {
        $raw = self::normalize_text(self::value_from($row, array('Mode FAQ','Type de réponse','Type de reponse','Response mode'), ''));
        if (in_array($raw, array('direct','reponse-directe'), true)) return 'direct';
        if (in_array($raw, array('lien','reponse-lien','answer-link','answer-linked'), true)) return 'answer_link';
        if (in_array($raw, array('renvoi','renvoi-canonique','canonical','lien-dynamique','link-only'), true)) return 'canonical';

        $id_upper = strtoupper((string)$id);
        $category_slug = self::normalize_text($category);
        if ($public_url !== '' && ($category_slug === 'horaires' || $category_slug === 'tarifs') && preg_match('/-(?:OUV|TAR)-001$/', $id_upper)) {
            return 'canonical';
        }
        if ($dynamic && $public_url !== '') return 'answer_link';
        return 'direct';
    }

    private static function default_link_label($category) {
        $slug = self::normalize_text($category);
        if ($slug === 'horaires') return self::triple('Voir les horaires à jour','See current opening hours','Aktuelle Öffnungszeiten ansehen');
        if ($slug === 'tarifs' || $slug === 'tarifs-reduits') return self::triple('Voir les tarifs à jour','See current prices','Aktuelle Preise ansehen');
        if (strpos($slug, 'groupes') === 0) return self::triple('Voir les informations groupes','See group information','Gruppeninformationen ansehen');
        if ($slug === 'acces' || $slug === 'camping-car' || $slug === 'parking') return self::triple('Voir les informations d’accès','See access information','Anreiseinformationen ansehen');
        return self::triple('Voir les informations à jour','See up-to-date information','Aktuelle Informationen ansehen');
    }

    private static function canonical_answer($category, $language, $fallback) {
        $slug = self::normalize_text($category);
        $messages = array(
            'horaires'=>self::triple(
                'Les jours et horaires varient selon la période. Consultez les horaires à jour avant votre visite.',
                'Opening days and times vary throughout the season. Please check the current opening hours before your visit.',
                'Öffnungstage und -zeiten variieren je nach Zeitraum. Bitte prüfen Sie vor Ihrem Besuch die aktuellen Öffnungszeiten.'
            ),
            'tarifs'=>self::triple(
                'Les tarifs peuvent évoluer. Retrouvez les tarifs à jour sur la page officielle.',
                'Prices may change. Please check the official page for current prices.',
                'Die Preise können sich ändern. Die aktuellen Preise finden Sie auf der offiziellen Seite.'
            ),
        );
        if (!isset($messages[$slug])) return (string)$fallback;
        return (string)($messages[$slug][$language] ?? $messages[$slug]['fr']);
    }

    private static function import_row($row, $order = 0) {
        if (!is_array($row)) return null;
        $id = strtoupper(sanitize_text_field(self::value_from($row, array('ID stable','ID','Stable ID'), '')));
        if ($id === '' || !preg_match('/^[A-Z0-9][A-Z0-9_-]{2,80}$/', $id)) return null;

        $priority = strtoupper(sanitize_text_field(self::value_from($row, array('Priorité','Priorite','Priority'), 'P3')));
        if (!in_array($priority, array('P1','P2','P3'), true)) $priority = 'P3';
        $category = sanitize_text_field(self::value_from($row, array('Catégorie','Categorie','Category'), 'Autres'));
        if ($category === '') $category = 'Autres';

        $question = array();
        $answer = array();
        $variants = array();
        foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $language => $suffix) {
            $question[$language] = sanitize_text_field(self::value_from($row, array('Question canonique ' . $suffix, 'Question ' . $suffix), ''));
            $answer[$language] = wp_kses_post(self::value_from($row, array('Réponse courte ' . $suffix, 'Reponse courte ' . $suffix, 'Réponse ' . $suffix, 'Reponse ' . $suffix, 'Answer ' . $suffix), ''));
            $variants[$language] = self::list_from(self::value_from($row, array('Variantes / formulations IA ' . $suffix, 'Variantes ' . $suffix, 'Synonymes ' . $suffix), ''));
        }
        if ($question['fr'] === '' || $answer['fr'] === '') return null;

        $visibility = sanitize_text_field(self::value_from($row, array('Usage / visibilité','Usage / visibilite','Usage','Visibilité','Visibility'), ''));
        $dynamic = self::bool_value(self::value_from($row, array('Donnée dynamique ?','Donnee dynamique ?','Dynamique','Dynamic'), ''));
        $status = sanitize_text_field(self::value_from($row, array('Statut','Status'), ''));
        $status_key = self::normalize_text($status);
        $visibility_key = self::normalize_text($visibility);
        $publishable = in_array($status_key, array('valide','publie','published'), true) && (strpos($visibility_key, 'faq-publique') !== false || strpos($visibility_key, 'public-faq') !== false);
        $public_url = self::public_url($row);
        $mode = self::response_mode($row, $id, $category, $dynamic, $public_url);
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
            'response_mode'=>$mode,
            'verified'=>sanitize_text_field(self::value_from($row, array('Vérifié le','Verifie le','Verified'), '')),
            'status'=>$status,
            'notes'=>sanitize_textarea_field(self::value_from($row, array('Notes / garde-fou','Notes','Guardrail'), '')),
            'enabled'=>$publishable ? '1' : '0',
            'remote_order'=>(int)$order,
        );
    }

    private static function item_fingerprint($item) {
        $copy = is_array($item) ? $item : array();
        unset($copy['remote_order']);
        return hash('sha256', wp_json_encode($copy));
    }

    private static function preview_key() {
        return 'parcs_ht_faq_preview_' . (int)get_current_user_id();
    }

    private static function redirect($args = array()) {
        wp_safe_redirect(add_query_arg(array_merge(array('page'=>self::PAGE), $args), admin_url('admin.php')));
        exit;
    }

    private static function error_redirect($message) {
        set_transient('parcs_ht_faq_notice_' . (int)get_current_user_id(), sanitize_text_field((string)$message), 120);
        self::redirect(array('faq_error'=>'1'));
    }

    public static function save_settings() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_save_settings');

        $settings = self::raw_settings();
        $raw = array();
        if (isset($_POST['faq']) && is_array($_POST['faq'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Conteneur déslashé puis chaque champ est assaini séparément.
            $raw = wp_unslash($_POST['faq']);
        }
        if (($raw['_complete'] ?? '') !== '1') self::error_redirect('Formulaire incomplet : aucun réglage modifié.');
        foreach (array('enabled', 'show_search', 'show_categories') as $field) {
            $settings[$field] = isset($raw[$field]) && (string)$raw[$field] === '1' ? '1' : '0';
        }

        self::persist($settings);
        do_action('litespeed_purge_all');
        self::redirect(array('updated'=>'1'));
    }

    private static function persist($settings) {
        update_option(self::OPTION, $settings, false);
        if (get_option(self::OPTION) !== $settings) self::error_redirect('Échec de l’enregistrement FAQ. Réessayez.');
    }

    private static function sheet_id($url) {
        return preg_match('#^https://docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]{20,100})(?:/[^\s]*)?$#D', $url, $matches) ? $matches[1] : '';
    }

    private static function valid_endpoint($url) {
        return wp_http_validate_url($url) && preg_match('#^https://script\.google\.com/macros/s/[a-zA-Z0-9_-]+/exec$#D', $url);
    }

    private static function google_fingerprint($google) {
        return hash('sha256', wp_json_encode($google));
    }

    public static function save_connection() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_save_connection');
        $before = self::raw_settings();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chaque champ scalaire est validé et assaini ci-dessous.
        $raw = isset($_POST['google']) && is_array($_POST['google']) ? wp_unslash($_POST['google']) : array();
        foreach (array('sheet_url', 'endpoint', 'secret', 'tab', 'park_code', '_complete') as $key) {
            if (!isset($raw[$key]) || !is_string($raw[$key])) self::error_redirect('Connexion incomplète : source actuelle conservée.');
        }
        if ($raw['_complete'] !== '1') self::error_redirect('Connexion incomplète : source actuelle conservée.');
        $google = array(
            'sheet_url'=>esc_url_raw(trim($raw['sheet_url'])),
            'endpoint'=>esc_url_raw(trim($raw['endpoint'])),
            'secret'=>$raw['secret'] === '' ? $before['google']['secret'] : sanitize_text_field($raw['secret']),
            'tab'=>sanitize_text_field($raw['tab']),
            'park_code'=>sanitize_text_field($raw['park_code']),
        );
        // La source candidate est lue et validée avant toute écriture locale.
        self::fetch_google($google);
        $settings = self::raw_settings();
        if ($settings['google'] !== $before['google']) self::error_redirect('La connexion a été modifiée pendant le test. Rechargez la page.');
        $settings['google'] = $google;
        self::persist($settings);
        delete_transient(self::preview_key());
        self::redirect(array('connected'=>'1'));
    }

    private static function fetch_google($google) {
        $endpoint = (string)($google['endpoint'] ?? '');
        $secret = (string)($google['secret'] ?? '');
        $tab = (string)($google['tab'] ?? '');
        $park_code = (string)($google['park_code'] ?? '');
        $sheet_id = self::sheet_id((string)($google['sheet_url'] ?? ''));
        if (!self::valid_endpoint($endpoint)) self::error_redirect('URL Apps Script invalide. Utilisez l’URL HTTPS /exec du déploiement Web App.');
        if ($sheet_id === '') self::error_redirect('Lien Google Sheet invalide. Copiez son URL https://docs.google.com/spreadsheets/d/…/edit.');
        if (strlen($secret) < 32) self::error_redirect('Configurez la clé secrète générée par le script (32 caractères minimum).');
        $tabs = array('MDS'=>'Montagne des Singes', 'FDS'=>'Forêt des Singes');
        if (!isset($tabs[$park_code]) || $tabs[$park_code] !== $tab) self::error_redirect('Le parc et l’onglet ne correspondent pas.');

        $response = wp_safe_remote_post($endpoint, array(
            'timeout'=>20,
            'redirection'=>0,
            'limit_response_size'=>2097152,
            'headers'=>array('Content-Type'=>'application/json; charset=utf-8'),
            'body'=>wp_json_encode(array(
                'action'=>'faq_export',
                'secret'=>$secret,
                'spreadsheet_id'=>$sheet_id,
                'tab'=>$tab,
                'park_code'=>$park_code,
                'schema_version'=>1,
            )),
        ));
        if (is_wp_error($response)) self::error_redirect('Connexion Google impossible. La source actuelle est conservée.');
        // ContentService redirige vers une URL temporaire : GET sans clé ni corps POST.
        if (in_array((int)wp_remote_retrieve_response_code($response), array(302,303), true)) {
            $location = (string)wp_remote_retrieve_header($response, 'location');
            if (!wp_http_validate_url($location) || !preg_match('#^https://script\.googleusercontent\.com/macros/echo\?[^\s]+$#D', $location)) {
                self::error_redirect('Redirection Google refusée. Vérifiez le déploiement Web App et son accès « Tout le monde ».');
            }
            $response = wp_safe_remote_get($location, array('timeout'=>20, 'redirection'=>0, 'limit_response_size'=>2097152));
        }
        if (is_wp_error($response)) self::error_redirect('Lecture Google impossible. La source actuelle est conservée.');
        $code = (int)wp_remote_retrieve_response_code($response);
        $data = json_decode((string)wp_remote_retrieve_body($response), true);
        if ($code !== 200 || !is_array($data) || ($data['ok'] ?? false) !== true) {
            self::error_redirect('Test Google échoué : vérifiez la clé, les autorisations du compte Google, l’onglet et ses colonnes. Source actuelle conservée.');
        }
        if (($data['schema_version'] ?? null) !== 1 || ($data['spreadsheet_id'] ?? '') !== $sheet_id || ($data['tab'] ?? '') !== $tab || ($data['park_code'] ?? '') !== $park_code || !isset($data['records']) || !is_array($data['records'])) {
            self::error_redirect('La réponse ne correspond pas à la source demandée. Mettez à jour le script fourni et son déploiement.');
        }
        $seen = array();
        $valid = 0;
        foreach ($data['records'] as $record) {
            $id = strtoupper(self::value_from($record, array('ID stable'), ''));
            if (strpos($id, $park_code . '-') !== 0 || isset($seen[$id])) self::error_redirect('ID FAQ incorrect, dupliqué ou appartenant à un autre parc.');
            $seen[$id] = true;
            if (self::import_row($record)) $valid++;
        }
        if (!$valid) self::error_redirect('Aucune fiche FAQ valide : source actuelle conservée.');
        return $data;
    }

    public static function check_google() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_check_google');
        delete_transient(self::preview_key());
        $settings = self::raw_settings();
        $google = $settings['google'];
        $data = self::fetch_google($google);
        $records = $data['records'];
        $tab = $google['tab'];

        $current = self::settings();
        $current_index = array();
        foreach ((array)$current['items'] as $item) {
            if (is_array($item) && !empty($item['id'])) $current_index[(string)$item['id']] = $item;
        }

        $rows = array();
        $counts = array('new'=>0,'modified'=>0,'unchanged'=>0,'blocked'=>0,'invalid'=>0);
        foreach ($records as $order => $record) {
            $item = self::import_row($record, $order);
            if (!$item) { $counts['invalid']++; continue; }
            $id = (string)$item['id'];
            $publishable = (string)$item['enabled'] === '1';
            if (!$publishable) {
                $change = 'blocked';
                $counts['blocked']++;
            } elseif (!isset($current_index[$id])) {
                $change = 'new';
                $counts['new']++;
            } elseif (hash_equals(self::item_fingerprint($current_index[$id]), self::item_fingerprint($item))) {
                $change = 'unchanged';
                $counts['unchanged']++;
            } else {
                $change = 'modified';
                $counts['modified']++;
            }
            $rows[] = array('id'=>$id,'change'=>$change,'publishable'=>$publishable,'item'=>$item);
        }

        $preview = array(
            'google_fingerprint'=>self::google_fingerprint($google),
            'created_at'=>time(),
            'remote_checked_at'=>sanitize_text_field((string)($data['checked_at'] ?? '')),
            'tab'=>sanitize_text_field((string)($data['tab'] ?? $tab)),
            'counts'=>$counts,
            'rows'=>$rows,
        );
        set_transient(self::preview_key(), $preview, self::PREVIEW_TTL);
        self::redirect(array('preview'=>'1'));
    }

    private static function add_revision($settings, $reason) {
        $revisions = get_option(self::REVISIONS_OPTION, array());
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
        update_option(self::REVISIONS_OPTION, $revisions, false);
        if (get_option(self::REVISIONS_OPTION) !== $revisions) self::error_redirect('Sauvegarde de sécurité impossible : opération annulée.');
    }

    public static function apply_import() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_apply_import');
        $preview = get_transient(self::preview_key());
        if (!is_array($preview) || empty($preview['rows'])) self::error_redirect('L’aperçu a expiré. Relancez « Vérifier le Google Sheet ».');

        $selected = array();
        if (isset($_POST['selected']) && is_array($_POST['selected'])) {
            $selected = array_values(array_unique(array_map('sanitize_text_field', wp_unslash($_POST['selected']))));
        }
        if (!$selected) self::error_redirect('Aucune modification n’a été sélectionnée.');

        $settings = self::raw_settings();
        if (($preview['google_fingerprint'] ?? '') !== self::google_fingerprint($settings['google'])) self::error_redirect('La source a changé. Relancez la vérification.');
        $index = array();
        foreach ((array)$settings['items'] as $item) {
            if (is_array($item) && !empty($item['id'])) $index[(string)$item['id']] = $item;
        }
        $applied = 0;
        foreach ((array)$preview['rows'] as $row) {
            if (!is_array($row) || empty($row['publishable']) || !isset($row['item']) || !is_array($row['item'])) continue;
            $id = (string)($row['id'] ?? '');
            if ($id === '' || !in_array($id, $selected, true)) continue;
            $index[$id] = $row['item'];
            $applied++;
        }
        if ($applied < 1) self::error_redirect('Aucune fiche validée et publiable n’a été sélectionnée.');

        self::add_revision($settings, 'Avant import Google Sheet');
        $items = array_values($index);
        usort($items, static function ($a, $b) {
            $pa = (int)substr((string)($a['priority'] ?? 'P3'), 1);
            $pb = (int)substr((string)($b['priority'] ?? 'P3'), 1);
            if ($pa !== $pb) return $pa <=> $pb;
            $oa = (int)($a['remote_order'] ?? 9999);
            $ob = (int)($b['remote_order'] ?? 9999);
            return $oa <=> $ob;
        });
        $settings['items'] = $items;
        $settings['has_import'] = '1';
        $settings['last_import'] = gmdate('c');
        $settings['store_version'] = self::STORE_VERSION;
        self::persist($settings);
        delete_transient(self::preview_key());
        do_action('litespeed_purge_all');
        self::redirect(array('imported'=>(string)$applied));
    }

    public static function restore_revision() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_faq_restore_revision');
        $revision_id = isset($_POST['revision_id']) ? sanitize_text_field(wp_unslash($_POST['revision_id'])) : '';
        if ($revision_id === '') self::error_redirect('Révision FAQ invalide.');
        $revisions = get_option(self::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        $target = null;
        foreach ($revisions as $revision) {
            if (is_array($revision) && hash_equals((string)($revision['id'] ?? ''), $revision_id)) { $target = $revision; break; }
        }
        if (!is_array($target)) self::error_redirect('La révision demandée est introuvable.');
        $settings = self::raw_settings();
        self::add_revision($settings, 'Avant restauration d’une révision');
        $settings['items'] = isset($target['items']) && is_array($target['items']) ? array_values($target['items']) : array();
        $settings['has_import'] = (string)($target['has_import'] ?? '1');
        $settings['last_import'] = (string)($target['last_import'] ?? '');
        self::persist($settings);
        do_action('litespeed_purge_all');
        self::redirect(array('restored'=>'1'));
    }

    private static function has_faq_shortcode($content) {
        foreach (array('parc_faq','parc_faq_fr','parc_faq_en','parc_faq_de') as $tag) {
            if (has_shortcode((string)$content, $tag)) return true;
        }
        return false;
    }

    public static function maybe_enqueue_assets() {
        if (!is_singular()) return;
        global $post;
        if (!$post || !self::has_faq_shortcode($post->post_content ?? '')) return;
        self::enqueue_assets();
    }

    private static function enqueue_assets() {
        wp_enqueue_style('parcs-ht-faq', PARCS_HT_URL . 'assets/faq.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-faq', PARCS_HT_URL . 'assets/faq.js', array(), PARCS_HT_VERSION, true);
    }

    private static function late_style_markup() {
        static $printed = false;
        if ($printed || !did_action('wp_head') || wp_style_is('parcs-ht-faq', 'done')) return '';
        $printed = true;
        ob_start();
        wp_print_styles('parcs-ht-faq');
        return ob_get_clean();
    }

    private static function ui_texts($language) {
        $texts = array(
            'fr'=>array('kicker'=>'Aide & informations','title'=>'Questions fréquentes','intro'=>'Trouvez rapidement la réponse à votre question.','search'=>'Rechercher dans la FAQ','all'=>'Toutes les catégories','empty'=>'Aucune réponse ne correspond à votre recherche.'),
            'en'=>array('kicker'=>'Help & information','title'=>'Frequently asked questions','intro'=>'Quickly find the answer to your question.','search'=>'Search the FAQ','all'=>'All categories','empty'=>'No answer matches your search.'),
            'de'=>array('kicker'=>'Hilfe & Informationen','title'=>'Häufig gestellte Fragen','intro'=>'Finden Sie schnell die Antwort auf Ihre Frage.','search'=>'FAQ durchsuchen','all'=>'Alle Kategorien','empty'=>'Keine Antwort entspricht Ihrer Suche.'),
        );
        return $texts[$language] ?? $texts['fr'];
    }

    private static function translated($value, $language, $fallback = '') {
        if (!is_array($value)) return (string)$value;
        if (isset($value[$language]) && trim((string)$value[$language]) !== '') return (string)$value[$language];
        return (string)$fallback;
    }

    private static function is_secondary_category($category) {
        return self::normalize_text((string)$category) === 'regles-du-parc';
    }

    private static function source_label($language) {
        $labels = array('fr'=>'Source','en'=>'Source','de'=>'Quelle');
        return $labels[$language] ?? $labels['fr'];
    }

    private static function render_item($item, $slug, $language, $secondary = false) {
        $variants = isset($item['variants'][$language]) && is_array($item['variants'][$language]) ? $item['variants'][$language] : array();
        $search = trim($item['_question'] . ' ' . implode(' ', $variants));
        $answer = (string)$item['_answer'];
        if ((string)($item['response_mode'] ?? 'direct') === 'canonical') {
            $answer = self::canonical_answer((string)($item['category'] ?? ''), $language, $answer);
        }

        $secondary_attr = $secondary ? ' data-htp-faq-secondary-item="1"' : '';
        $html = '<details class="parcs-ht-faq-item" data-htp-faq-item data-category="' . esc_attr($slug) . '" data-search="' . esc_attr($search) . '"' . $secondary_attr . '><summary>' . esc_html($item['_question']) . '</summary><div class="parcs-ht-faq-answer">' . wp_kses_post(wpautop($answer));
        $url = (string)($item['public_url'] ?? '');
        $mode = (string)($item['response_mode'] ?? 'direct');
        $link_rendered = false;
        if ($url !== '' && in_array($mode, array('answer_link','canonical'), true)) {
            $label = $item['_link_label'] !== '' ? $item['_link_label'] : self::default_link_label((string)($item['category'] ?? ''))[$language];
            $html .= '<p class="parcs-ht-faq-action"><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></p>';
            $link_rendered = true;
        }
        if ($secondary && $url !== '' && !$link_rendered) {
            $html .= '<p class="parcs-ht-faq-source"><a href="' . esc_url($url) . '">' . esc_html(self::source_label($language)) . '</a></p>';
        }
        return $html . '</div></details>';
    }

    public static function public_items($language = 'fr', $category_filter = '') {
        $settings = self::settings();
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $category_filter = self::normalize_text($category_filter);
        $out = array();
        foreach ((array)$settings['items'] as $item) {
            if (!is_array($item) || (string)($item['enabled'] ?? '0') !== '1') continue;
            $category_slug = self::normalize_text((string)($item['category'] ?? ''));
            if ($category_filter !== '' && $category_slug !== $category_filter) continue;
            $question = self::translated($item['question'] ?? array(), $language, '');
            $answer = self::translated($item['answer'] ?? array(), $language, '');
            if ($question === '' || $answer === '') continue;
            $item['_question'] = $question;
            $item['_answer'] = $answer;
            $item['_category'] = self::translated($item['category_label'] ?? array(), $language, (string)($item['category'] ?? ''));
            $item['_link_label'] = self::translated($item['link_label'] ?? array(), $language, '');
            $out[] = $item;
        }
        return $out;
    }

    public static function render($language = 'fr', $atts = array()) {
        $settings = self::settings();
        if ((string)($settings['enabled'] ?? '0') !== '1') return '';
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $atts = shortcode_atts(array('categorie'=>'','recherche'=>'','categories'=>'','titre'=>'1'), is_array($atts) ? $atts : array(), 'parc_faq');
        $items = self::public_items($language, (string)$atts['categorie']);
        if (!$items) return '';
        self::enqueue_assets();
        $late_style = self::late_style_markup();
        $show_search = $atts['recherche'] === '' ? (string)($settings['show_search'] ?? '1') === '1' : self::bool_value($atts['recherche']);
        $show_categories = $atts['categories'] === '' ? (string)($settings['show_categories'] ?? '1') === '1' : self::bool_value($atts['categories']);
        $show_title = self::bool_value($atts['titre']);
        $texts = self::ui_texts($language);
        self::$instance++;
        $id = 'parcs-ht-faq-' . self::$instance;

        $groups = array();
        foreach ($items as $item) {
            $slug = self::normalize_text((string)($item['category'] ?? 'autres'));
            if (!isset($groups[$slug])) {
                $groups[$slug] = array(
                    'label'=>$item['_category'],
                    'items'=>array(),
                    'secondary'=>self::is_secondary_category($slug),
                );
            }
            $groups[$slug]['items'][] = $item;
        }
        $primary_groups = array_filter($groups, static function ($group) { return empty($group['secondary']); });
        $secondary_groups = array_filter($groups, static function ($group) { return !empty($group['secondary']); });

        $html = '<section id="' . esc_attr($id) . '" class="parcs-ht-faq" data-htp-faq data-htp-lang="' . esc_attr($language) . '">';
        if ($show_title) {
            $html .= '<header class="parcs-ht-faq-heading"><p class="parcs-ht-faq-kicker">' . esc_html($texts['kicker']) . '</p><h2>' . esc_html($texts['title']) . '</h2><p>' . esc_html($texts['intro']) . '</p></header>';
        }
        if ($show_search) {
            $html .= '<div class="parcs-ht-faq-search"><label for="' . esc_attr($id . '-search') . '">' . esc_html($texts['search']) . '</label><input id="' . esc_attr($id . '-search') . '" type="search" autocomplete="off" data-htp-faq-search placeholder="' . esc_attr($texts['search']) . '"></div>';
        }
        if ($show_categories && count($primary_groups) > 1) {
            $html .= '<div class="parcs-ht-faq-categories" role="group" aria-label="' . esc_attr($texts['all']) . '"><button type="button" class="is-active" data-htp-faq-category="" aria-pressed="true">' . esc_html($texts['all']) . '</button>';
            foreach ($primary_groups as $slug => $group) {
                $html .= '<button type="button" data-htp-faq-category="' . esc_attr($slug) . '" aria-pressed="false">' . esc_html($group['label']) . '</button>';
            }
            $html .= '</div>';
        }
        $html .= '<div class="parcs-ht-faq-groups">';
        foreach ($primary_groups as $slug => $group) {
            $html .= '<section class="parcs-ht-faq-group" data-htp-faq-group="' . esc_attr($slug) . '"><h3>' . esc_html($group['label']) . '</h3><div class="parcs-ht-faq-list">';
            foreach ($group['items'] as $item) $html .= self::render_item($item, $slug, $language, false);
            $html .= '</div></section>';
        }
        $html .= '</div>';

        if ($secondary_groups) {
            $html .= '<div class="parcs-ht-faq-secondary-groups" data-htp-faq-secondary-groups>';
            foreach ($secondary_groups as $slug => $group) {
                $html .= '<details class="parcs-ht-faq-secondary" data-htp-faq-secondary><summary>' . esc_html($group['label']) . '</summary><div class="parcs-ht-faq-secondary-content">';
                $html .= '<section class="parcs-ht-faq-group parcs-ht-faq-group-secondary" data-htp-faq-group="' . esc_attr($slug) . '"><div class="parcs-ht-faq-list">';
                foreach ($group['items'] as $item) $html .= self::render_item($item, $slug, $language, true);
                $html .= '</div></section></div></details>';
            }
            $html .= '</div>';
        }

        $html .= '<p class="parcs-ht-faq-empty" data-htp-faq-empty hidden>' . esc_html($texts['empty']) . '</p></section>';
        return $late_style . $html;
    }

    private static function preview() {
        $preview = get_transient(self::preview_key());
        return is_array($preview) ? $preview : array();
    }

    private static function notice() {
        $key = 'parcs_ht_faq_notice_' . (int)get_current_user_id();
        $message = get_transient($key);
        if ($message) delete_transient($key);
        return is_string($message) ? $message : '';
    }

    private static function change_label($change) {
        $map = array('new'=>'Nouvelle','modified'=>'Modifiée','unchanged'=>'Identique','blocked'=>'Non publiable');
        return $map[$change] ?? $change;
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::raw_settings();
        $display_settings = self::settings();
        $google = (array)$settings['google'];
        $preview = self::preview();
        $revisions = get_option(self::REVISIONS_OPTION, array());
        if (!is_array($revisions)) $revisions = array();
        wp_enqueue_script('parcs-ht-faq-admin', PARCS_HT_URL . 'assets/faq-admin.js', array(), PARCS_HT_VERSION, true);
        $error = self::notice();
        $items = (array)$display_settings['items'];
        $enabled_count = 0;
        foreach ($items as $item) if (is_array($item) && (string)($item['enabled'] ?? '0') === '1') $enabled_count++;
        ?>
        <div class="wrap htp-faq-admin">
            <h1>FAQ</h1>
            <p class="description">Base FAQ globale du parc. Elle n’est liée à aucune année ni saison et possède son propre système d’enregistrement.</p>
            <?php if ($error !== '') : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div><?php endif; ?>
            <?php if (isset($_GET['connected'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Connexion testée et enregistrée. Vous pouvez maintenant vérifier le Google Sheet pour préparer un import.</p></div><?php endif; ?>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les réglages FAQ ont été enregistrés.</p></div><?php endif; ?>
            <?php if (isset($_GET['imported'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html((int)$_GET['imported']); ?> fiche(s) FAQ ont été appliquées. Les fiches absentes du Sheet n’ont pas été supprimées.</p></div><?php endif; ?>
            <?php if (isset($_GET['restored'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>La révision FAQ a été restaurée.</p></div><?php endif; ?>

            <div class="htp-faq-admin-grid">
                <section class="htp-faq-card">
                    <h2>Affichage public</h2>
                    <p><strong><?php echo esc_html($enabled_count); ?></strong> fiche(s) publiable(s) actuellement disponibles.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_save_settings">
                        <?php wp_nonce_field('parcs_ht_faq_save_settings'); ?>
                        <label><input type="checkbox" name="faq[enabled]" value="1" <?php checked((string)$settings['enabled'], '1'); ?>> Activer la FAQ publique</label><br>
                        <label><input type="checkbox" name="faq[show_search]" value="1" <?php checked((string)$settings['show_search'], '1'); ?>> Afficher la recherche</label><br>
                        <label><input type="checkbox" name="faq[show_categories]" value="1" <?php checked((string)$settings['show_categories'], '1'); ?>> Afficher les filtres par catégorie</label>

                        <input type="hidden" name="faq[_complete]" value="1">
                        <p><button type="submit" class="button button-primary">Enregistrer l’affichage FAQ</button></p>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_save_connection">
                        <?php wp_nonce_field('parcs_ht_faq_save_connection'); ?>
                        <h3>Connexion Google Sheet</h3>
                        <p class="description">Le site ne lit jamais Google pour afficher la FAQ. Google sert uniquement à préparer un aperçu d’import dans l’administration.</p>
                        <label class="htp-faq-field"><span>Lien du Google Sheet à utiliser</span><input type="url" name="google[sheet_url]" value="<?php echo esc_attr((string)$google['sheet_url']); ?>" placeholder="https://docs.google.com/spreadsheets/d/…"></label>
                        <label class="htp-faq-field"><span>URL Web App Apps Script</span><input type="url" name="google[endpoint]" value="<?php echo esc_attr((string)$google['endpoint']); ?>" placeholder="https://script.google.com/macros/s/…/exec"></label>
                        <label class="htp-faq-field"><span>Clé secrète</span><input type="password" name="google[secret]" value="" autocomplete="new-password" placeholder="Laisser vide pour conserver la clé actuelle"><small><?php echo !empty($google['secret']) ? 'Une clé est enregistrée.' : 'Aucune clé enregistrée.'; ?></small></label>
                        <label class="htp-faq-field"><span>Parc</span><select name="google[park_code]"><option value="MDS" <?php selected((string)$google['park_code'], 'MDS'); ?>>Montagne des Singes (MDS)</option><option value="FDS" <?php selected((string)$google['park_code'], 'FDS'); ?>>Forêt des Singes (FDS)</option></select></label>
                        <label class="htp-faq-field"><span>Nom exact de l’onglet</span><input type="text" name="google[tab]" value="<?php echo esc_attr((string)$google['tab']); ?>"></label>
                        <input type="hidden" name="google[_complete]" value="1">
                        <p><button type="submit" class="button button-primary">Tester et utiliser ce Google Sheet</button></p>
                        <p class="description">La nouvelle connexion n’est enregistrée que si le test réussit. Les fiches publiées restent inchangées ; leur import se fait séparément.</p>
                    </form>

                    <?php if (!empty($google['sheet_url'])) : ?><p><a class="button" href="<?php echo esc_url((string)$google['sheet_url']); ?>" target="_blank" rel="noopener noreferrer">Ouvrir le Google Sheet</a></p><?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_check_google">
                        <?php wp_nonce_field('parcs_ht_faq_check_google'); ?>
                        <p><button type="submit" class="button button-secondary">Vérifier le Google Sheet</button></p>
                    </form>
                </section>

                <section class="htp-faq-card">
                    <h2>Shortcodes</h2>
                    <p>La FAQ peut être insérée comme les autres modules. Le shortcode sans suffixe utilise la langue courante du site.</p>
                    <code>[parc_faq]</code><br><code>[parc_faq_fr]</code><br><code>[parc_faq_en]</code><br><code>[parc_faq_de]</code>
                    <p class="description">Exemple de filtre : <code>[parc_faq_fr categorie="billets"]</code>. Options : <code>recherche="0"</code>, <code>categories="0"</code>, <code>titre="0"</code>.</p>
                    <h3>Sécurité d’import</h3>
                    <ul>
                        <li>Aucune suppression automatique ;</li>
                        <li>seules les lignes au statut « Validé » et destinées à la « FAQ publique » peuvent être appliquées ;</li>
                        <li>une révision est créée avant chaque import ;</li>
                        <li>les données FAQ restent séparées des saisons, horaires, tarifs et devis.</li>
                    </ul>
                </section>
            </div>

            <?php if (!empty($preview['rows'])) : ?>
                <section class="htp-faq-card htp-faq-preview">
                    <h2>Aperçu Google Sheet — aucune modification n’est encore appliquée</h2>
                    <?php $counts = (array)($preview['counts'] ?? array()); ?>
                    <p><strong><?php echo esc_html((int)($counts['new'] ?? 0)); ?></strong> nouvelle(s) · <strong><?php echo esc_html((int)($counts['modified'] ?? 0)); ?></strong> modifiée(s) · <strong><?php echo esc_html((int)($counts['unchanged'] ?? 0)); ?></strong> identique(s) · <strong><?php echo esc_html((int)($counts['blocked'] ?? 0)); ?></strong> non publiable(s) · <strong><?php echo esc_html((int)($counts['invalid'] ?? 0)); ?></strong> invalide(s)</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="parcs_ht_faq_apply_import">
                        <?php wp_nonce_field('parcs_ht_faq_apply_import'); ?>
                        <div class="htp-faq-table-wrap"><table class="widefat striped"><thead><tr><th>Appliquer</th><th>ID</th><th>État</th><th>Catégorie</th><th>Question FR</th><th>Langues</th><th>Statut source</th></tr></thead><tbody>
                        <?php foreach ((array)$preview['rows'] as $preview_row) : $item = (array)($preview_row['item'] ?? array()); $publishable = !empty($preview_row['publishable']); $change = (string)($preview_row['change'] ?? ''); ?>
                            <tr>
                                <td><?php if ($publishable && $change !== 'unchanged') : ?><input type="checkbox" name="selected[]" value="<?php echo esc_attr((string)$preview_row['id']); ?>" checked><?php else : ?>—<?php endif; ?></td>
                                <td><code><?php echo esc_html((string)$preview_row['id']); ?></code></td>
                                <td><?php echo esc_html(self::change_label($change)); ?></td>
                                <td><?php echo esc_html((string)($item['category'] ?? '')); ?></td>
                                <td><?php echo esc_html((string)($item['question']['fr'] ?? '')); ?></td>
                                <td><?php echo !empty($item['question']['fr']) && !empty($item['answer']['fr']) ? 'FR ' : ''; ?><?php echo !empty($item['question']['en']) && !empty($item['answer']['en']) ? 'EN ' : ''; ?><?php echo !empty($item['question']['de']) && !empty($item['answer']['de']) ? 'DE' : ''; ?></td>
                                <td><?php echo esc_html((string)($item['status'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                        <p><button type="submit" class="button button-primary">Appliquer les modifications cochées</button> <span class="description">Les lignes non cochées et les lignes absentes du Sheet restent inchangées.</span></p>
                    </form>
                </section>
            <?php endif; ?>

            <section class="htp-faq-card">
                <h2>Fiches actuellement enregistrées</h2>
                <?php if (!$items) : ?><p>Aucune fiche n’a encore été importée.</p><?php else : ?>
                    <div class="htp-faq-table-wrap"><table class="widefat striped"><thead><tr><th>ID</th><th>Priorité</th><th>Catégorie</th><th>Question FR</th><th>FR</th><th>EN</th><th>DE</th><th>Publication</th><th>Vérifié le</th></tr></thead><tbody>
                    <?php foreach ($items as $item) : if (!is_array($item)) continue; ?>
                        <tr><td><code><?php echo esc_html((string)($item['id'] ?? '')); ?></code></td><td><?php echo esc_html((string)($item['priority'] ?? '')); ?></td><td><?php echo esc_html((string)($item['category'] ?? '')); ?></td><td><?php echo esc_html((string)($item['question']['fr'] ?? '')); ?></td><td><?php echo !empty($item['answer']['fr']) ? '✓' : '—'; ?></td><td><?php echo !empty($item['answer']['en']) ? '✓' : '—'; ?></td><td><?php echo !empty($item['answer']['de']) ? '✓' : '—'; ?></td><td><?php echo (string)($item['enabled'] ?? '0') === '1' ? 'Publiable' : 'Interne'; ?></td><td><?php echo esc_html((string)($item['verified'] ?? '')); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>

            <?php if ($revisions) : ?>
                <section class="htp-faq-card">
                    <h2>Révisions de sécurité</h2>
                    <p class="description">Les 10 dernières sauvegardes automatiques de la base FAQ sont conservées. Elles ne contiennent pas la clé Google.</p>
                    <table class="widefat striped"><thead><tr><th>Date</th><th>Motif</th><th>Fiches</th><th></th></tr></thead><tbody>
                    <?php foreach ($revisions as $revision) : if (!is_array($revision)) continue; ?>
                        <tr><td><?php echo esc_html(wp_date('d/m/Y H:i', (int)($revision['created_at'] ?? time()))); ?></td><td><?php echo esc_html((string)($revision['reason'] ?? '')); ?></td><td><?php echo esc_html(count((array)($revision['items'] ?? array()))); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_faq_restore_revision"><input type="hidden" name="revision_id" value="<?php echo esc_attr((string)($revision['id'] ?? '')); ?>"><?php wp_nonce_field('parcs_ht_faq_restore_revision'); ?><button type="submit" class="button" onclick="return confirm('Restaurer cette version de la FAQ ? Une sauvegarde de l’état actuel sera créée avant la restauration.');">Restaurer</button></form></td></tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </section>
            <?php endif; ?>

            <section class="htp-faq-card">
                <h2>Mise en place du pont Google</h2>
                <p>Une seule installation Google est nécessaire. Ensuite, pour changer de fichier, renseignez son lien ci-dessus puis cliquez sur <strong>Tester et utiliser ce Google Sheet</strong>. Le compte qui déploie le script doit avoir accès au nouveau fichier. Conservez le projet Apps Script initial.</p>
                <ol>
                    <li>Dans votre Google Sheet, ouvrez <strong>Extensions → Apps Script</strong>. Collez le script ci-dessous dans <code>Code.gs</code> et enregistrez.</li>
                    <li>Dans les paramètres du projet, cochez <strong>Afficher le fichier manifeste appsscript.json</strong>. Remplacez son contenu par le manifeste ci-dessous pour limiter les autorisations à la lecture des tableurs.</li>
                    <li>Exécutez <code>installerConfiguration</code>, autorisez la lecture, puis copiez la propriété <code>FAQ_SHARED_SECRET</code> depuis <strong>Paramètres du projet → Propriétés du script</strong>.</li>
                    <li>Choisissez <strong>Déployer → Nouveau déploiement → Application Web</strong>, exécution en tant que <strong>Moi</strong>, accès <strong>Tout le monde</strong>. Sans la clé, le script ne renvoie aucune donnée.</li>
                    <li>Copiez l’URL <code>/exec</code>, la clé et le lien du Sheet dans la connexion ci-dessus. Choisissez le parc et son onglet, puis testez.</li>
                    <li>Cliquez sur <strong>Vérifier le Google Sheet</strong>, relisez l’aperçu puis appliquez les fiches choisies. Activez la FAQ publique séparément.</li>
                </ol>
                <p>Onglets : <code>Montagne des Singes</code> (MDS) ou <code>Forêt des Singes</code> (FDS). Colonnes requises : <code>ID stable</code>, <code>Question canonique FR</code>, <code>Réponse courte FR</code>, <code>Statut</code>, <code>Usage / visibilité</code>. Au moins une fiche complète du parc choisi est nécessaire au test.</p>
                <details><summary>Afficher le script à copier</summary>
                    <p><button type="button" class="button" data-faq-copy="htp-faq-script">Copier le script</button></p>
                    <textarea id="htp-faq-script" class="large-text code" rows="16" readonly aria-label="Script Google Apps Script"><?php echo esc_textarea((string)file_get_contents(PARCS_HT_DIR . 'assets/faq-google-sheet-apps-script.txt')); ?></textarea>
                </details>
                <details><summary>Afficher le manifeste en lecture seule</summary>
                    <p><button type="button" class="button" data-faq-copy="htp-faq-manifest">Copier le manifeste</button></p>
                    <textarea id="htp-faq-manifest" class="large-text code" rows="8" readonly aria-label="Manifeste appsscript.json"><?php echo esc_textarea((string)file_get_contents(PARCS_HT_DIR . 'assets/faq-google-sheet-manifest.txt')); ?></textarea>
                </details>
                <p data-faq-copy-status role="status" aria-live="polite"></p>
                <p>Pour remplacer une ancienne version du script, collez le nouveau code et le manifeste puis choisissez <strong>Déployer → Gérer les déploiements → Modifier → Nouvelle version</strong>, afin de conserver la même URL. Pour changer la clé, exécutez <code>regenererCleSecrete</code>, copiez la nouvelle propriété dans WordPress et testez à nouveau. Une panne Google ne modifie pas la FAQ déjà importée.</p>
            </section>
        </div>
        <style>
        .htp-faq-admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;margin:20px 0}.htp-faq-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px;margin:18px 0}.htp-faq-admin-grid .htp-faq-card{margin:0}.htp-faq-field{display:block;margin:12px 0}.htp-faq-field span{display:block;font-weight:600;margin-bottom:5px}.htp-faq-field input,.htp-faq-field select{width:100%;max-width:720px}.htp-faq-field small{display:block;margin-top:4px;color:#646970}.htp-faq-table-wrap{overflow:auto}.htp-faq-card code{white-space:nowrap}
        </style>
        <?php
    }
}
