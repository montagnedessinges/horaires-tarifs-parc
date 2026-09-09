<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Moteur public et stockage du Calendrier de l’Avent.
 *
 * Les campagnes restent dans une option dédiée afin de ne pas mélanger les
 * données Avent avec les saisons horaires/tarifs. Les données sensibles ne
 * sont jamais injectées dans les payloads publics avant leur date de révélation.
 */
final class Parcs_HT_Advent {
    const OPTION = 'parcs_ht_advent';
    const SCHEMA_VERSION = 3;
    const PUBLIC_NONCE_ACTION = 'parcs_ht_advent_public';
    const PREVIEW_NONCE_ACTION = 'parcs_ht_advent_preview';
    const AJAX_DAY = 'parcs_ht_advent_day';
    const AJAX_WORD = 'parcs_ht_advent_validate_word';

    private static $preview_moment = null;
    private static $assets_localized = false;

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));

        add_action('wp_ajax_' . self::AJAX_DAY, array(__CLASS__, 'ajax_day'));
        add_action('wp_ajax_nopriv_' . self::AJAX_DAY, array(__CLASS__, 'ajax_day'));
        add_action('wp_ajax_' . self::AJAX_WORD, array(__CLASS__, 'ajax_validate_word'));
        add_action('wp_ajax_nopriv_' . self::AJAX_WORD, array(__CLASS__, 'ajax_validate_word'));

        self::register_shortcodes();
    }

    private static function register_shortcodes() {
        add_shortcode('parc_calendrier_avent', static function ($atts = array()) {
            return Parcs_HT_Advent::render_calendar(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
        });
        add_shortcode('parc_reglement_avent', static function ($atts = array()) {
            return Parcs_HT_Advent::render_rules(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
        });

        foreach (array('fr', 'en', 'de') as $language) {
            add_shortcode('parc_calendrier_avent_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Advent::render_calendar($language, is_array($atts) ? $atts : array());
            });
            add_shortcode('parc_reglement_avent_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Advent::render_rules($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function register_assets() {
        wp_register_style(
            'parcs-ht-advent',
            PARCS_HT_URL . 'assets/advent.css',
            array(),
            PARCS_HT_VERSION
        );
        wp_register_script(
            'parcs-ht-advent',
            PARCS_HT_URL . 'assets/advent.js',
            array(),
            PARCS_HT_VERSION,
            true
        );
    }

    private static function enqueue_assets() {
        if (!wp_style_is('parcs-ht-advent', 'registered') || !wp_script_is('parcs-ht-advent', 'registered')) {
            self::register_assets();
        }
        wp_enqueue_style('parcs-ht-advent');
        wp_enqueue_script('parcs-ht-advent');

        if (self::$assets_localized) return;
        $data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'dayAction' => self::AJAX_DAY,
            'wordAction' => self::AJAX_WORD,
            'nonce' => wp_create_nonce(self::PUBLIC_NONCE_ACTION),
            'previewDate' => '',
            'previewTime' => '',
            'previewNonce' => '',
        );
        if (self::$preview_moment instanceof DateTimeImmutable && current_user_can('manage_options')) {
            $data['previewDate'] = self::$preview_moment->format('Y-m-d');
            $data['previewTime'] = self::$preview_moment->format('H:i');
            $data['previewNonce'] = wp_create_nonce(self::PREVIEW_NONCE_ACTION);
        }
        wp_add_inline_script(
            'parcs-ht-advent',
            'window.ParcsHTAdvent=' . wp_json_encode($data) . ';',
            'before'
        );
        self::$assets_localized = true;
    }

    public static function set_preview_datetime($date, $time, $timezone = 'Europe/Paris') {
        $date = (string) $date;
        $time = (string) $time;
        if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) return false;
        try {
            $zone = new DateTimeZone($timezone ?: 'Europe/Paris');
            $moment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $zone);
            if (!$moment || $moment->format('Y-m-d H:i') !== $date . ' ' . $time) return false;
            self::$preview_moment = $moment;
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    private static function apply_preview_request() {
        if (!current_user_can('manage_options')) return;
        $nonce = isset($_POST['preview_nonce']) ? sanitize_text_field(wp_unslash($_POST['preview_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, self::PREVIEW_NONCE_ACTION)) return;
        $date = isset($_POST['preview_date']) ? sanitize_text_field(wp_unslash($_POST['preview_date'])) : '';
        $time = isset($_POST['preview_time']) ? sanitize_text_field(wp_unslash($_POST['preview_time'])) : '';
        self::set_preview_datetime($date, $time, wp_timezone_string() ?: 'Europe/Paris');
    }

    public static function installation_park_code() {
        $settings = get_option(Parcs_HT_Defaults::OPTION, array());
        $code = is_array($settings) ? sanitize_key((string)($settings['site_type'] ?? '')) : '';
        return in_array($code, array('mds', 'fds'), true) ? $code : '';
    }

    public static function empty_store() {
        return array(
            'schema_version' => self::SCHEMA_VERSION,
            'campaigns' => array(),
        );
    }

    public static function store() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) $saved = array();
        $campaigns = isset($saved['campaigns']) && is_array($saved['campaigns']) ? $saved['campaigns'] : array();
        $out = self::empty_store();
        foreach ($campaigns as $id => $campaign) {
            if (!is_array($campaign)) continue;
            $id = sanitize_key((string)($campaign['campagne_id'] ?? $id));
            if ($id === '') continue;
            $out['campaigns'][$id] = self::normalize_campaign($campaign, $id);
        }
        return $out;
    }

    public static function save_store($store) {
        $store = is_array($store) ? $store : self::empty_store();
        $store['schema_version'] = self::SCHEMA_VERSION;
        if (!isset($store['campaigns']) || !is_array($store['campaigns'])) $store['campaigns'] = array();
        update_option(self::OPTION, $store, false);
    }

    public static function campaign($campaign_id, $allow_non_active = false) {
        $campaign_id = sanitize_key((string)$campaign_id);
        if ($campaign_id === '') return null;
        $store = self::store();
        if (!isset($store['campaigns'][$campaign_id])) return null;
        $campaign = $store['campaigns'][$campaign_id];
        $park = self::installation_park_code();
        if ($park === '' || (string)($campaign['parc_code'] ?? '') !== $park) return null;
        if (!$allow_non_active && (string)($campaign['statut_campagne'] ?? '') !== 'active') return null;
        return $campaign;
    }

    public static function current_campaign($allow_preview = false) {
        $store = self::store();
        $park = self::installation_park_code();
        if ($park === '') return null;
        $active = array();
        $fallback = array();
        foreach ($store['campaigns'] as $campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park) continue;
            $year = (int)($campaign['annee'] ?? 0);
            if ((string)($campaign['statut_campagne'] ?? '') === 'active') $active[$year . '|' . $campaign['campagne_id']] = $campaign;
            if ($allow_preview) $fallback[$year . '|' . $campaign['campagne_id']] = $campaign;
        }
        if ($active) {
            krsort($active, SORT_NATURAL);
            return reset($active);
        }
        if ($allow_preview && $fallback) {
            krsort($fallback, SORT_NATURAL);
            return reset($fallback);
        }
        return null;
    }

    public static function campaign_field_names() {
        return array(
            'schema_version','campagne_id','parc_code','annee','nom_campagne','statut_campagne','langue_sociale','langues_site','fallback_traduction','timezone',
            'date_ouverture_calendrier','date_fin_calendrier','heure_ouverture_globale','page_calendrier_url','page_reglement_url',
            'titre_bloc_calendrier_fr','texte_intro_calendrier_fr','libelle_comment_participer_fr','texte_comment_participer_fr','libelle_reglement_complet_fr','reglement_complet_fr','texte_tirage_non_effectue_fr',
            'teasing_site_visuel_source','teasing_site_visuel_url','teasing_site_visuel_alt_fr','reglement_quotidien_fr','texte_rappel_grand_jeu_fr','texte_lien_calendrier_social_fr',
            'grand_jeu_date_ouverture','grand_jeu_heure_ouverture','grand_jeu_date_fermeture','grand_jeu_heure_fermeture','grand_jeu_date_revelation','mot_mystere','grand_jeu_lot_fr','grand_jeu_pictogramme_url',
            'grand_jeu_texte_saisie_fr','grand_jeu_texte_erreur_fr','grand_jeu_texte_succes_fr','grand_jeu_formulaire_shortcode','instagram_parc','facebook_slug','facebook_url_override','hashtags_defaut',
        );
    }

    public static function partner_field_names() {
        return array('partenaire_id','nom','type_partenaire','instagram_handle','instagram_url_override','facebook_slug','facebook_url_override','site_url','description_fr','hashtags','logo_source','logo_url');
    }

    public static function content_field_names() {
        return array(
            'contenu_id','type_contenu','jour_numero','date_publication','heure_publication','heure_ouverture','facebook_actif','facebook_date_publication','facebook_heure_publication','instagram_actif','instagram_date_publication','instagram_heure_publication','phase','titre_fr',
            'intro_partenaire_fr','intro_question_fr','partenaire_id','lot_fr','format_jeu','question_fr','reponse_a_fr','reponse_b_fr','reponse_c_fr','reponse_d_fr','bonne_reponse_code','bonne_reponse_texte_fr','explication_reponse_fr',
            'indice_actif','indice_lettre','indice_position','afficher_rappel_grand_jeu','rappel_grand_jeu_override_fr','visuel_source','visuel_url','visuel_alt_fr','texte_post_override_fr','facebook_post_url','instagram_post_url','statut',
        );
    }

    public static function result_field_names() {
        return array('resultat_id','contenu_id','date_revelation_resultat','heure_revelation_resultat','gagnant_facebook','gagnant_instagram','texte_resultat_override_fr','story_resultat_override_fr','statut_resultat');
    }

    public static function translation_field_names() {
        return array('reference_id','champ','langue','texte');
    }

    public static function default_microcopies() {
        return array(
            'selection_prompt' => array('fr'=>'Choisissez une case ouverte pour découvrir son contenu.','en'=>'Choose an open day to discover its content.','de'=>'Wählen Sie ein geöffnetes Türchen, um den Inhalt zu entdecken.'),
            'locked_day' => array('fr'=>'Cette case n’est pas encore ouverte.','en'=>'This day is not open yet.','de'=>'Dieses Türchen ist noch nicht geöffnet.'),
            'partner_label' => array('fr'=>'Partenaire','en'=>'Partner','de'=>'Partner'),
            'prize_label' => array('fr'=>'Lot du jour','en'=>'Today’s prize','de'=>'Tagesgewinn'),
            'question_label' => array('fr'=>'Question du jour','en'=>'Question of the day','de'=>'Frage des Tages'),
            'result_label' => array('fr'=>'Résultat','en'=>'Result','de'=>'Ergebnis'),
            'winner_facebook_label' => array('fr'=>'Gagnant Facebook','en'=>'Facebook winner','de'=>'Facebook-Gewinner'),
            'winner_instagram_label' => array('fr'=>'Gagnant Instagram','en'=>'Instagram winner','de'=>'Instagram-Gewinner'),
            'clue_label' => array('fr'=>'Indice du jour','en'=>'Clue of the day','de'=>'Hinweis des Tages'),
            'clue_marker' => array('fr'=>'Un indice se cache dans le visuel.','en'=>'A clue is hidden in the visual.','de'=>'Im Bild ist ein Hinweis versteckt.'),
            'enlarge_image' => array('fr'=>'Agrandir le visuel','en'=>'Enlarge image','de'=>'Bild vergrößern'),
            'final_submit' => array('fr'=>'Valider le mot','en'=>'Check the word','de'=>'Wort prüfen'),
            'final_closed' => array('fr'=>'Le grand jeu est terminé.','en'=>'The final game is closed.','de'=>'Das Finalspiel ist beendet.'),
            'loading' => array('fr'=>'Chargement…','en'=>'Loading…','de'=>'Wird geladen…'),
            'error' => array('fr'=>'Impossible de charger ce contenu pour le moment.','en'=>'This content cannot be loaded right now.','de'=>'Dieser Inhalt kann derzeit nicht geladen werden.'),
        );
    }

    public static function default_campaign($campaign_id, $park_code, $year, $name = '') {
        $campaign_id = sanitize_key((string)$campaign_id);
        $park_code = in_array($park_code, array('mds','fds'), true) ? $park_code : '';
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
        $campaign = array(
            'schema_version' => self::SCHEMA_VERSION,
            'campagne_id' => $campaign_id,
            'parc_code' => $park_code,
            'annee' => $year,
            'nom_campagne' => sanitize_text_field((string)$name),
            'statut_campagne' => 'brouillon',
            'langue_sociale' => 'fr',
            'langues_site' => 'fr,en,de',
            'fallback_traduction' => 'fr',
            'timezone' => wp_timezone_string() ?: 'Europe/Paris',
            'date_ouverture_calendrier' => '',
            'date_fin_calendrier' => '',
            'heure_ouverture_globale' => '',
            'page_calendrier_url' => '',
            'page_reglement_url' => '',
            'titre_bloc_calendrier_fr' => 'Calendrier de l’Avent',
            'texte_intro_calendrier_fr' => 'Découvrez les cases du calendrier au fil de leur ouverture.',
            'libelle_comment_participer_fr' => 'Comment participer ?',
            'texte_comment_participer_fr' => 'Découvrez chaque jour le jeu, le lot et les conditions de participation indiquées dans la publication correspondante. Certains jours peuvent contenir un indice utile pour le grand jeu final.',
            'libelle_reglement_complet_fr' => 'Consulter le règlement complet',
            'reglement_complet_fr' => '',
            'texte_tirage_non_effectue_fr' => 'Le tirage au sort n’a pas encore été effectué.',
            'teasing_site_visuel_source' => 'aucun',
            'teasing_site_visuel_url' => '',
            'teasing_site_visuel_alt_fr' => '',
            'reglement_quotidien_fr' => '',
            'texte_rappel_grand_jeu_fr' => 'Un indice se cache dans le visuel : repérez la lettre et son numéro pour reconstituer le mot mystère du grand jeu final.',
            'texte_lien_calendrier_social_fr' => 'Retrouvez le calendrier et les journées précédentes :',
            'grand_jeu_date_ouverture' => '',
            'grand_jeu_heure_ouverture' => '',
            'grand_jeu_date_fermeture' => '',
            'grand_jeu_heure_fermeture' => '',
            'grand_jeu_date_revelation' => '',
            'mot_mystere' => '',
            'grand_jeu_lot_fr' => '',
            'grand_jeu_pictogramme_url' => '',
            'grand_jeu_texte_saisie_fr' => 'Vous avez reconstitué le mot mystère ? Saisissez-le pour accéder au grand jeu final.',
            'grand_jeu_texte_erreur_fr' => 'Ce mot ne correspond pas au mot mystère.',
            'grand_jeu_texte_succes_fr' => 'Mot correct. Vous pouvez maintenant participer au grand jeu final.',
            'grand_jeu_formulaire_shortcode' => '',
            'instagram_parc' => '',
            'facebook_slug' => '',
            'facebook_url_override' => '',
            'hashtags_defaut' => '',
            'microcopies' => self::default_microcopies(),
            'partners' => array(),
            'contents' => array(),
            'results' => array(),
            'translations' => array(),
        );

        for ($day = 1; $day <= 24; $day++) {
            $content = self::default_content($day);
            $campaign['contents'][$content['contenu_id']] = $content;
            $result = self::default_result($content['contenu_id'], $day);
            $campaign['results'][$result['resultat_id']] = $result;
        }
        return $campaign;
    }

    public static function default_content($day_number = 0) {
        $day_number = (int)$day_number;
        $id = $day_number >= 1 && $day_number <= 24 ? sprintf('jour_%02d', $day_number) : '';
        return array(
            'contenu_id'=>$id,'type_contenu'=>$day_number ? 'JOUR' : 'TEASING','jour_numero'=>$day_number,'date_publication'=>'','heure_publication'=>'','heure_ouverture'=>'',
            'facebook_actif'=>'1','facebook_date_publication'=>'','facebook_heure_publication'=>'','instagram_actif'=>'1','instagram_date_publication'=>'','instagram_heure_publication'=>'','phase'=>'','titre_fr'=>'',
            'intro_partenaire_fr'=>'','intro_question_fr'=>'','partenaire_id'=>'','lot_fr'=>'','format_jeu'=>'QCM','question_fr'=>'','reponse_a_fr'=>'','reponse_b_fr'=>'','reponse_c_fr'=>'','reponse_d_fr'=>'',
            'bonne_reponse_code'=>'','bonne_reponse_texte_fr'=>'','explication_reponse_fr'=>'','indice_actif'=>'non','indice_lettre'=>'','indice_position'=>'','afficher_rappel_grand_jeu'=>'auto','rappel_grand_jeu_override_fr'=>'',
            'visuel_source'=>'aucun','visuel_url'=>'','visuel_alt_fr'=>'','texte_post_override_fr'=>'','facebook_post_url'=>'','instagram_post_url'=>'','statut'=>'brouillon',
        );
    }

    public static function default_result($content_id = '', $day_number = 0) {
        $content_id = sanitize_key((string)$content_id);
        $result_id = $content_id !== '' ? 'resultat_' . $content_id : '';
        if ($result_id === '' && $day_number) $result_id = sprintf('resultat_jour_%02d', (int)$day_number);
        return array(
            'resultat_id'=>$result_id,'contenu_id'=>$content_id,'date_revelation_resultat'=>'','heure_revelation_resultat'=>'','gagnant_facebook'=>'','gagnant_instagram'=>'',
            'texte_resultat_override_fr'=>'','story_resultat_override_fr'=>'','statut_resultat'=>'brouillon',
        );
    }

    public static function default_partner() {
        return array('partenaire_id'=>'','nom'=>'','type_partenaire'=>'externe','instagram_handle'=>'','instagram_url_override'=>'','facebook_slug'=>'','facebook_url_override'=>'','site_url'=>'','description_fr'=>'','hashtags'=>'','logo_source'=>'aucun','logo_url'=>'');
    }

    public static function sanitize_campaign($raw, $existing = array()) {
        $raw = is_array($raw) ? $raw : array();
        $existing = is_array($existing) ? $existing : array();
        $out = array();
        $out['schema_version'] = self::SCHEMA_VERSION;
        $out['campagne_id'] = sanitize_key((string)($raw['campagne_id'] ?? ($existing['campagne_id'] ?? '')));
        $park = sanitize_key((string)($raw['parc_code'] ?? ($existing['parc_code'] ?? '')));
        $out['parc_code'] = in_array($park, array('mds','fds'), true) ? $park : '';
        $year = sanitize_text_field((string)($raw['annee'] ?? ($existing['annee'] ?? '')));
        $out['annee'] = preg_match('/^20\d{2}$/', $year) ? $year : '';
        $out['nom_campagne'] = sanitize_text_field((string)($raw['nom_campagne'] ?? ($existing['nom_campagne'] ?? '')));
        $status = sanitize_key((string)($raw['statut_campagne'] ?? ($existing['statut_campagne'] ?? 'brouillon')));
        $out['statut_campagne'] = in_array($status, array('brouillon','active','archivee'), true) ? $status : 'brouillon';
        $out['langue_sociale'] = sanitize_key((string)($raw['langue_sociale'] ?? ($existing['langue_sociale'] ?? 'fr')));
        $out['langues_site'] = sanitize_text_field((string)($raw['langues_site'] ?? ($existing['langues_site'] ?? 'fr,en,de')));
        $fallback = sanitize_key((string)($raw['fallback_traduction'] ?? ($existing['fallback_traduction'] ?? 'fr')));
        $out['fallback_traduction'] = in_array($fallback, array('fr','masquer','strict'), true) ? $fallback : 'fr';
        $timezone = sanitize_text_field((string)($raw['timezone'] ?? ($existing['timezone'] ?? 'Europe/Paris')));
        try { new DateTimeZone($timezone); $out['timezone'] = $timezone; } catch (Exception $exception) { $out['timezone'] = 'Europe/Paris'; }

        foreach (array('date_ouverture_calendrier','date_fin_calendrier','grand_jeu_date_ouverture','grand_jeu_date_fermeture','grand_jeu_date_revelation') as $field) {
            $out[$field] = self::sanitize_date($raw[$field] ?? ($existing[$field] ?? ''));
        }
        foreach (array('heure_ouverture_globale','grand_jeu_heure_ouverture','grand_jeu_heure_fermeture') as $field) {
            $out[$field] = self::sanitize_time($raw[$field] ?? ($existing[$field] ?? ''));
        }
        foreach (array('page_calendrier_url','page_reglement_url','teasing_site_visuel_url','grand_jeu_pictogramme_url','facebook_url_override') as $field) {
            $out[$field] = esc_url_raw((string)($raw[$field] ?? ($existing[$field] ?? '')));
        }
        foreach (array('titre_bloc_calendrier_fr','libelle_comment_participer_fr','libelle_reglement_complet_fr','teasing_site_visuel_alt_fr','grand_jeu_lot_fr','grand_jeu_texte_saisie_fr','grand_jeu_texte_erreur_fr','grand_jeu_texte_succes_fr','instagram_parc','facebook_slug') as $field) {
            $out[$field] = sanitize_text_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        }
        foreach (array('texte_intro_calendrier_fr','texte_comment_participer_fr','reglement_complet_fr','texte_tirage_non_effectue_fr','reglement_quotidien_fr','texte_rappel_grand_jeu_fr','texte_lien_calendrier_social_fr','hashtags_defaut') as $field) {
            $out[$field] = sanitize_textarea_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        }
        $source = sanitize_key((string)($raw['teasing_site_visuel_source'] ?? ($existing['teasing_site_visuel_source'] ?? 'aucun')));
        $out['teasing_site_visuel_source'] = in_array($source, array('aucun','url','wordpress'), true) ? $source : 'aucun';
        $out['mot_mystere'] = sanitize_text_field((string)($raw['mot_mystere'] ?? ($existing['mot_mystere'] ?? '')));
        $out['grand_jeu_formulaire_shortcode'] = self::sanitize_single_shortcode($raw['grand_jeu_formulaire_shortcode'] ?? ($existing['grand_jeu_formulaire_shortcode'] ?? ''));

        $microcopies = isset($existing['microcopies']) && is_array($existing['microcopies']) ? $existing['microcopies'] : self::default_microcopies();
        if (isset($raw['microcopies']) && is_array($raw['microcopies'])) {
            foreach (self::default_microcopies() as $key => $defaults) {
                if (!isset($microcopies[$key]) || !is_array($microcopies[$key])) $microcopies[$key] = $defaults;
                foreach (array('fr','en','de') as $lang) {
                    if (isset($raw['microcopies'][$key][$lang])) $microcopies[$key][$lang] = sanitize_text_field((string)$raw['microcopies'][$key][$lang]);
                }
            }
        }
        $out['microcopies'] = $microcopies;
        $out['partners'] = isset($existing['partners']) && is_array($existing['partners']) ? $existing['partners'] : array();
        $out['contents'] = isset($existing['contents']) && is_array($existing['contents']) ? $existing['contents'] : array();
        $out['results'] = isset($existing['results']) && is_array($existing['results']) ? $existing['results'] : array();
        $out['translations'] = isset($existing['translations']) && is_array($existing['translations']) ? $existing['translations'] : array();
        return $out;
    }

    public static function sanitize_partner($raw, $existing = array()) {
        $raw = is_array($raw) ? $raw : array();
        $existing = is_array($existing) ? $existing : self::default_partner();
        $out = self::default_partner();
        $out['partenaire_id'] = sanitize_key((string)($raw['partenaire_id'] ?? ($existing['partenaire_id'] ?? '')));
        foreach (array('nom','instagram_handle','facebook_slug') as $field) $out[$field] = sanitize_text_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        $type = sanitize_key((string)($raw['type_partenaire'] ?? ($existing['type_partenaire'] ?? 'externe')));
        $out['type_partenaire'] = in_array($type, array('externe','parc'), true) ? $type : 'externe';
        foreach (array('instagram_url_override','facebook_url_override','site_url','logo_url') as $field) $out[$field] = esc_url_raw((string)($raw[$field] ?? ($existing[$field] ?? '')));
        foreach (array('description_fr','hashtags') as $field) $out[$field] = sanitize_textarea_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        $source = sanitize_key((string)($raw['logo_source'] ?? ($existing['logo_source'] ?? 'aucun')));
        $out['logo_source'] = in_array($source, array('aucun','url','wordpress'), true) ? $source : 'aucun';
        return $out;
    }

    public static function sanitize_content($raw, $existing = array()) {
        $raw = is_array($raw) ? $raw : array();
        $existing = is_array($existing) ? $existing : self::default_content();
        $out = self::default_content();
        $out['contenu_id'] = sanitize_key((string)($raw['contenu_id'] ?? ($existing['contenu_id'] ?? '')));
        $type = strtoupper(sanitize_key((string)($raw['type_contenu'] ?? ($existing['type_contenu'] ?? 'TEASING'))));
        $out['type_contenu'] = in_array($type, array('TEASING','JOUR'), true) ? $type : 'TEASING';
        $day = (int)($raw['jour_numero'] ?? ($existing['jour_numero'] ?? 0));
        $out['jour_numero'] = ($out['type_contenu'] === 'JOUR' && $day >= 1 && $day <= 24) ? $day : 0;
        foreach (array('date_publication','facebook_date_publication','instagram_date_publication') as $field) $out[$field] = self::sanitize_date($raw[$field] ?? ($existing[$field] ?? ''));
        foreach (array('heure_publication','heure_ouverture','facebook_heure_publication','instagram_heure_publication') as $field) $out[$field] = self::sanitize_time($raw[$field] ?? ($existing[$field] ?? ''));
        foreach (array('facebook_actif','instagram_actif') as $field) $out[$field] = self::yes_no($raw[$field] ?? ($existing[$field] ?? '1'));
        foreach (array('phase','titre_fr','partenaire_id','lot_fr','question_fr','reponse_a_fr','reponse_b_fr','reponse_c_fr','reponse_d_fr','bonne_reponse_code','bonne_reponse_texte_fr','indice_lettre','visuel_alt_fr','statut') as $field) {
            $out[$field] = sanitize_text_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        }
        foreach (array('intro_partenaire_fr','intro_question_fr','explication_reponse_fr','rappel_grand_jeu_override_fr','texte_post_override_fr') as $field) {
            $out[$field] = sanitize_textarea_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        }
        $format = strtoupper(sanitize_key((string)($raw['format_jeu'] ?? ($existing['format_jeu'] ?? 'QCM'))));
        $out['format_jeu'] = in_array($format, array('QCM','VRAI_FAUX','CHOIX_MULTIPLE'), true) ? $format : 'QCM';
        $out['indice_actif'] = self::yes_no_word($raw['indice_actif'] ?? ($existing['indice_actif'] ?? 'non'));
        $position = (int)($raw['indice_position'] ?? ($existing['indice_position'] ?? 0));
        $out['indice_position'] = $position > 0 ? (string)$position : '';
        $reminder = sanitize_key((string)($raw['afficher_rappel_grand_jeu'] ?? ($existing['afficher_rappel_grand_jeu'] ?? 'auto')));
        $out['afficher_rappel_grand_jeu'] = in_array($reminder, array('oui','non','auto'), true) ? $reminder : 'auto';
        $source = sanitize_key((string)($raw['visuel_source'] ?? ($existing['visuel_source'] ?? 'aucun')));
        $out['visuel_source'] = in_array($source, array('aucun','url','wordpress'), true) ? $source : 'aucun';
        foreach (array('visuel_url','facebook_post_url','instagram_post_url') as $field) $out[$field] = esc_url_raw((string)($raw[$field] ?? ($existing[$field] ?? '')));
        return $out;
    }

    public static function sanitize_result($raw, $existing = array()) {
        $raw = is_array($raw) ? $raw : array();
        $existing = is_array($existing) ? $existing : self::default_result();
        $out = self::default_result();
        $out['resultat_id'] = sanitize_key((string)($raw['resultat_id'] ?? ($existing['resultat_id'] ?? '')));
        $out['contenu_id'] = sanitize_key((string)($raw['contenu_id'] ?? ($existing['contenu_id'] ?? '')));
        $out['date_revelation_resultat'] = self::sanitize_date($raw['date_revelation_resultat'] ?? ($existing['date_revelation_resultat'] ?? ''));
        $out['heure_revelation_resultat'] = self::sanitize_time($raw['heure_revelation_resultat'] ?? ($existing['heure_revelation_resultat'] ?? ''));
        foreach (array('gagnant_facebook','gagnant_instagram') as $field) $out[$field] = sanitize_text_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        foreach (array('texte_resultat_override_fr','story_resultat_override_fr') as $field) $out[$field] = sanitize_textarea_field((string)($raw[$field] ?? ($existing[$field] ?? '')));
        $status = sanitize_key((string)($raw['statut_resultat'] ?? ($existing['statut_resultat'] ?? 'brouillon')));
        $out['statut_resultat'] = in_array($status, array('brouillon','pret','publie'), true) ? $status : 'brouillon';
        return $out;
    }

    public static function sanitize_translation($raw) {
        $raw = is_array($raw) ? $raw : array();
        $lang = sanitize_key((string)($raw['langue'] ?? ''));
        return array(
            'reference_id' => sanitize_key((string)($raw['reference_id'] ?? '')),
            'champ' => sanitize_key(str_replace('.', '_', (string)($raw['champ'] ?? ''))),
            'langue' => in_array($lang, array('en','de'), true) ? $lang : '',
            'texte' => sanitize_textarea_field((string)($raw['texte'] ?? '')),
        );
    }

    private static function normalize_campaign($campaign, $campaign_id) {
        $base = self::default_campaign($campaign_id, sanitize_key((string)($campaign['parc_code'] ?? '')), (string)($campaign['annee'] ?? ''), (string)($campaign['nom_campagne'] ?? ''));
        $normalized = self::sanitize_campaign($campaign, $base);
        $normalized['partners'] = array();
        foreach ((array)($campaign['partners'] ?? array()) as $id => $partner) {
            if (!is_array($partner)) continue;
            $partner = self::sanitize_partner($partner, self::default_partner());
            if ($partner['partenaire_id'] === '') $partner['partenaire_id'] = sanitize_key((string)$id);
            if ($partner['partenaire_id'] !== '') $normalized['partners'][$partner['partenaire_id']] = $partner;
        }
        $normalized['contents'] = array();
        foreach ((array)($campaign['contents'] ?? array()) as $id => $content) {
            if (!is_array($content)) continue;
            $content = self::sanitize_content($content, self::default_content());
            if ($content['contenu_id'] === '') $content['contenu_id'] = sanitize_key((string)$id);
            if ($content['contenu_id'] !== '') $normalized['contents'][$content['contenu_id']] = $content;
        }
        $normalized['results'] = array();
        foreach ((array)($campaign['results'] ?? array()) as $id => $result) {
            if (!is_array($result)) continue;
            $result = self::sanitize_result($result, self::default_result());
            if ($result['resultat_id'] === '') $result['resultat_id'] = sanitize_key((string)$id);
            if ($result['resultat_id'] !== '') $normalized['results'][$result['resultat_id']] = $result;
        }
        $normalized['translations'] = array();
        foreach ((array)($campaign['translations'] ?? array()) as $translation) {
            $translation = self::sanitize_translation($translation);
            if ($translation['reference_id'] !== '' && $translation['champ'] !== '' && $translation['langue'] !== '') $normalized['translations'][] = $translation;
        }
        return $normalized;
    }

    private static function sanitize_date($value) {
        $value = sanitize_text_field((string)$value);
        if ($value === '') return '';
        return preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function sanitize_time($value) {
        $value = sanitize_text_field((string)$value);
        if ($value === '') return '';
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : '';
    }

    private static function yes_no($value) {
        $value = strtolower(trim((string)$value));
        return in_array($value, array('1','oui','yes','true','on'), true) ? '1' : '0';
    }

    private static function yes_no_word($value) {
        return self::yes_no($value) === '1' ? 'oui' : 'non';
    }

    private static function sanitize_single_shortcode($value) {
        $value = trim(wp_unslash((string)$value));
        if ($value === '') return '';
        $value = wp_strip_all_tags(html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset')));
        $value = preg_replace('/[\r\n\t]+/', ' ', $value);
        $value = trim($value);
        if (!preg_match('/^\[[a-zA-Z0-9_-]+(?:\s+[^\]]*)?\]$/', $value)) return '';
        return $value;
    }

    private static function timezone($campaign) {
        $timezone = isset($campaign['timezone']) ? (string)$campaign['timezone'] : 'Europe/Paris';
        try { return new DateTimeZone($timezone ?: 'Europe/Paris'); } catch (Exception $exception) { return new DateTimeZone('Europe/Paris'); }
    }

    private static function now($campaign) {
        if (self::$preview_moment instanceof DateTimeImmutable) {
            return self::$preview_moment->setTimezone(self::timezone($campaign));
        }
        return new DateTimeImmutable('now', self::timezone($campaign));
    }

    private static function moment($date, $time, $campaign) {
        $date = self::sanitize_date($date);
        $time = self::sanitize_time($time);
        if ($date === '' || $time === '') return null;
        $moment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, self::timezone($campaign));
        if (!$moment || $moment->format('Y-m-d H:i') !== $date . ' ' . $time) return null;
        return $moment;
    }

    private static function day_open_at($campaign, $content) {
        $time = (string)($content['heure_ouverture'] ?? '');
        if ($time === '') $time = (string)($content['heure_publication'] ?? '');
        if ($time === '') $time = (string)($campaign['heure_ouverture_globale'] ?? '');
        return self::moment((string)($content['date_publication'] ?? ''), $time, $campaign);
    }

    private static function sorted_days($campaign) {
        $days = array_values(array_filter((array)($campaign['contents'] ?? array()), static function ($content) {
            return is_array($content) && (string)($content['type_contenu'] ?? '') === 'JOUR' && (int)($content['jour_numero'] ?? 0) >= 1 && (int)($content['jour_numero'] ?? 0) <= 24;
        }));
        usort($days, static function ($a, $b) { return ((int)$a['jour_numero']) <=> ((int)$b['jour_numero']); });
        return $days;
    }

    private static function result_for_content($campaign, $content_id) {
        foreach ((array)($campaign['results'] ?? array()) as $result) {
            if (is_array($result) && (string)($result['contenu_id'] ?? '') === (string)$content_id) return $result;
        }
        return self::default_result((string)$content_id);
    }

    private static function result_reveal_at($campaign, $content) {
        $result = self::result_for_content($campaign, (string)($content['contenu_id'] ?? ''));
        $explicit = self::moment((string)($result['date_revelation_resultat'] ?? ''), (string)($result['heure_revelation_resultat'] ?? ''), $campaign);
        if ($explicit instanceof DateTimeImmutable) return $explicit;
        $day_number = (int)($content['jour_numero'] ?? 0);
        foreach (self::sorted_days($campaign) as $day) {
            if ((int)($day['jour_numero'] ?? 0) === $day_number + 1) return self::day_open_at($campaign, $day);
        }
        return null;
    }

    private static function calendar_start_at($campaign) {
        $time = (string)($campaign['heure_ouverture_globale'] ?? '');
        $explicit = self::moment((string)($campaign['date_ouverture_calendrier'] ?? ''), $time, $campaign);
        if ($explicit instanceof DateTimeImmutable) return $explicit;
        foreach (self::sorted_days($campaign) as $day) {
            $moment = self::day_open_at($campaign, $day);
            if ($moment instanceof DateTimeImmutable) return $moment;
        }
        return null;
    }

    private static function grand_open_at($campaign) {
        return self::moment((string)($campaign['grand_jeu_date_ouverture'] ?? ''), (string)($campaign['grand_jeu_heure_ouverture'] ?? ''), $campaign);
    }

    private static function grand_close_at($campaign) {
        return self::moment((string)($campaign['grand_jeu_date_fermeture'] ?? ''), (string)($campaign['grand_jeu_heure_fermeture'] ?? ''), $campaign);
    }

    private static function translation($campaign, $reference_id, $field, $language, $fallback) {
        if ($language === 'fr') return (string)$fallback;
        $reference_id = sanitize_key((string)$reference_id);
        $field = sanitize_key(str_replace('.', '_', (string)$field));
        foreach ((array)($campaign['translations'] ?? array()) as $row) {
            if (!is_array($row)) continue;
            if ((string)($row['reference_id'] ?? '') !== $reference_id) continue;
            if ((string)($row['champ'] ?? '') !== $field) continue;
            if ((string)($row['langue'] ?? '') !== $language) continue;
            $text = trim((string)($row['texte'] ?? ''));
            if ($text !== '') return $text;
        }
        return (string)($campaign['fallback_traduction'] ?? 'fr') === 'fr' ? (string)$fallback : '';
    }

    private static function campaign_text($campaign, $field, $language) {
        $fallback = (string)($campaign[$field] ?? '');
        return self::translation($campaign, (string)$campaign['campagne_id'], $field, $language, $fallback);
    }

    private static function content_text($campaign, $content, $field, $language) {
        $fallback = (string)($content[$field] ?? '');
        return self::translation($campaign, (string)$content['contenu_id'], $field, $language, $fallback);
    }

    private static function partner_text($campaign, $partner, $field, $language) {
        $fallback = (string)($partner[$field] ?? '');
        return self::translation($campaign, (string)$partner['partenaire_id'], $field, $language, $fallback);
    }

    private static function microcopy($campaign, $key, $language) {
        $defaults = self::default_microcopies();
        $value = isset($campaign['microcopies'][$key][$language]) ? trim((string)$campaign['microcopies'][$key][$language]) : '';
        if ($value !== '') return $value;
        if ($language !== 'fr' && (string)($campaign['fallback_traduction'] ?? 'fr') !== 'fr') return '';
        $fr = isset($campaign['microcopies'][$key]['fr']) ? trim((string)$campaign['microcopies'][$key]['fr']) : '';
        if ($fr !== '') return $fr;
        return isset($defaults[$key][$language]) ? $defaults[$key][$language] : (isset($defaults[$key]['fr']) ? $defaults[$key]['fr'] : '');
    }

    private static function partner($campaign, $partner_id) {
        $partner_id = sanitize_key((string)$partner_id);
        return ($partner_id !== '' && isset($campaign['partners'][$partner_id]) && is_array($campaign['partners'][$partner_id])) ? $campaign['partners'][$partner_id] : null;
    }

    private static function public_campaign($campaign_id = '') {
        $allow_preview = self::$preview_moment instanceof DateTimeImmutable && current_user_can('manage_options');
        if ($campaign_id !== '') return self::campaign($campaign_id, $allow_preview);
        return self::current_campaign($allow_preview);
    }

    public static function render_calendar($language = 'fr', $atts = array()) {
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $atts = is_array($atts) ? $atts : array();
        $campaign_id = isset($atts['campagne']) ? sanitize_key((string)$atts['campagne']) : '';
        $campaign = self::public_campaign($campaign_id);
        if (!$campaign) return '';
        self::enqueue_assets();

        $now = self::now($campaign);
        $start = self::calendar_start_at($campaign);
        $before_start = $start instanceof DateTimeImmutable ? $now < $start : true;
        $days = self::sorted_days($campaign);
        $title = self::campaign_text($campaign, 'titre_bloc_calendrier_fr', $language);
        $intro = self::campaign_text($campaign, 'texte_intro_calendrier_fr', $language);
        $participate_label = self::campaign_text($campaign, 'libelle_comment_participer_fr', $language);
        $participate_text = self::campaign_text($campaign, 'texte_comment_participer_fr', $language);
        $rules_label = self::campaign_text($campaign, 'libelle_reglement_complet_fr', $language);
        $rules_url = (string)($campaign['page_reglement_url'] ?? '');
        $instance = 'parcs-ht-advent-' . wp_rand(1000, 999999);

        ob_start();
        ?>
        <section id="<?php echo esc_attr($instance); ?>" class="parcs-ht-advent" data-advent-root data-campaign-id="<?php echo esc_attr($campaign['campagne_id']); ?>" data-language="<?php echo esc_attr($language); ?>">
            <?php if ($title !== '') : ?><h2 class="parcs-ht-advent-title"><?php echo esc_html($title); ?></h2><?php endif; ?>
            <?php if ($intro !== '') : ?><div class="parcs-ht-advent-intro"><?php echo wp_kses_post(wpautop($intro)); ?></div><?php endif; ?>
            <?php if ($participate_label !== '' && $participate_text !== '') : ?>
                <div class="parcs-ht-advent-participation">
                    <button type="button" class="parcs-ht-advent-secondary" data-advent-participation-toggle aria-expanded="false" aria-controls="<?php echo esc_attr($instance . '-participation'); ?>"><?php echo esc_html($participate_label); ?></button>
                    <div id="<?php echo esc_attr($instance . '-participation'); ?>" class="parcs-ht-advent-participation-panel" data-advent-participation-panel hidden>
                        <?php echo wp_kses_post(wpautop($participate_text)); ?>
                        <?php if ($rules_url !== '' && $rules_label !== '') : ?><p><a class="parcs-ht-advent-link" href="<?php echo esc_url($rules_url); ?>"><?php echo esc_html($rules_label); ?></a></p><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="parcs-ht-advent-grid" role="list" aria-label="<?php echo esc_attr($title !== '' ? $title : 'Calendrier de l’Avent'); ?>">
                <?php foreach ($days as $day) :
                    $open_at = self::day_open_at($campaign, $day);
                    $is_open = !$before_start && $open_at instanceof DateTimeImmutable && $now >= $open_at;
                    $is_today = $open_at instanceof DateTimeImmutable && $now->format('Y-m-d') === $open_at->format('Y-m-d');
                    $classes = 'parcs-ht-advent-day' . ($is_open ? ' is-open' : ' is-locked') . ($is_today ? ' is-today' : '');
                    ?>
                    <button type="button" class="<?php echo esc_attr($classes); ?>" role="listitem" data-advent-day data-content-id="<?php echo esc_attr($day['contenu_id']); ?>" <?php disabled(!$is_open); ?> aria-label="<?php echo esc_attr(sprintf('Jour %d', (int)$day['jour_numero'])); ?>">
                        <span class="parcs-ht-advent-day-number"><?php echo esc_html((string)(int)$day['jour_numero']); ?></span>
                        <?php if (!$is_open) : ?><span class="parcs-ht-advent-lock" aria-hidden="true">🔒</span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="parcs-ht-advent-detail" data-advent-detail aria-live="polite">
                <?php if ($before_start && !empty($campaign['teasing_site_visuel_url'])) : ?>
                    <figure class="parcs-ht-advent-teaser">
                        <img src="<?php echo esc_url($campaign['teasing_site_visuel_url']); ?>" alt="<?php echo esc_attr(self::campaign_text($campaign, 'teasing_site_visuel_alt_fr', $language)); ?>" loading="lazy">
                    </figure>
                <?php else : ?>
                    <p class="parcs-ht-advent-placeholder"><?php echo esc_html(self::microcopy($campaign, 'selection_prompt', $language)); ?></p>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function render_rules($language = 'fr', $atts = array()) {
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $atts = is_array($atts) ? $atts : array();
        $campaign_id = isset($atts['campagne']) ? sanitize_key((string)$atts['campagne']) : '';
        $campaign = self::public_campaign($campaign_id);
        if (!$campaign) return '';
        $rules = self::campaign_text($campaign, 'reglement_complet_fr', $language);
        if ($rules === '') return '';
        return '<div class="parcs-ht-advent-rules">' . wp_kses_post(wpautop($rules)) . '</div>';
    }

    private static function render_day_detail($campaign, $content, $language) {
        $now = self::now($campaign);
        $open_at = self::day_open_at($campaign, $content);
        if (!$open_at || $now < $open_at) return '';

        $partner = self::partner($campaign, (string)($content['partenaire_id'] ?? ''));
        $result = self::result_for_content($campaign, (string)$content['contenu_id']);
        $reveal_at = self::result_reveal_at($campaign, $content);
        $result_due = $reveal_at instanceof DateTimeImmutable && $now >= $reveal_at;
        $result_published = $result_due && (string)($result['statut_resultat'] ?? '') === 'publie';
        $choices = array();
        foreach (array('a','b','c','d') as $letter) {
            $text = self::content_text($campaign, $content, 'reponse_' . $letter . '_fr', $language);
            if ($text !== '') $choices[strtoupper($letter)] = $text;
        }

        ob_start();
        ?>
        <article class="parcs-ht-advent-day-detail" data-advent-day-detail>
            <div class="parcs-ht-advent-day-heading">
                <span class="parcs-ht-advent-day-kicker"><?php echo esc_html(sprintf('Jour %d', (int)$content['jour_numero'])); ?></span>
                <?php $day_title = self::content_text($campaign, $content, 'titre_fr', $language); if ($day_title !== '') : ?><h3><?php echo esc_html($day_title); ?></h3><?php endif; ?>
            </div>

            <?php if (!empty($content['visuel_url'])) : ?>
                <figure class="parcs-ht-advent-visual">
                    <button type="button" class="parcs-ht-advent-visual-button" data-advent-expand-image aria-label="<?php echo esc_attr(self::microcopy($campaign, 'enlarge_image', $language)); ?>">
                        <img src="<?php echo esc_url($content['visuel_url']); ?>" alt="<?php echo esc_attr(self::content_text($campaign, $content, 'visuel_alt_fr', $language)); ?>" loading="lazy">
                    </button>
                </figure>
            <?php endif; ?>

            <div class="parcs-ht-advent-day-copy">
                <?php if ($partner) : ?>
                    <div class="parcs-ht-advent-partner">
                        <strong><?php echo esc_html(self::microcopy($campaign, 'partner_label', $language)); ?> :</strong>
                        <?php echo esc_html((string)$partner['nom']); ?>
                        <?php $partner_intro = self::content_text($campaign, $content, 'intro_partenaire_fr', $language); if ($partner_intro === '') $partner_intro = self::partner_text($campaign, $partner, 'description_fr', $language); if ($partner_intro !== '') : ?><div><?php echo wp_kses_post(wpautop($partner_intro)); ?></div><?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php $lot = self::content_text($campaign, $content, 'lot_fr', $language); if ($lot !== '') : ?><p class="parcs-ht-advent-prize"><strong><?php echo esc_html(self::microcopy($campaign, 'prize_label', $language)); ?> :</strong> <?php echo esc_html($lot); ?></p><?php endif; ?>
                <?php $intro_question = self::content_text($campaign, $content, 'intro_question_fr', $language); if ($intro_question !== '') : ?><div class="parcs-ht-advent-question-intro"><?php echo wp_kses_post(wpautop($intro_question)); ?></div><?php endif; ?>
                <?php $question = self::content_text($campaign, $content, 'question_fr', $language); if ($question !== '') : ?><div class="parcs-ht-advent-question"><strong><?php echo esc_html(self::microcopy($campaign, 'question_label', $language)); ?> :</strong> <?php echo esc_html($question); ?></div><?php endif; ?>
                <?php if ($choices) : ?><ul class="parcs-ht-advent-choices"><?php foreach ($choices as $letter => $choice) : ?><li><strong><?php echo esc_html($letter); ?>.</strong> <?php echo esc_html($choice); ?></li><?php endforeach; ?></ul><?php endif; ?>

                <?php if ((string)($content['indice_actif'] ?? '') === 'oui') : ?><p class="parcs-ht-advent-clue-marker"><span aria-hidden="true">🔎</span> <?php echo esc_html(self::microcopy($campaign, 'clue_marker', $language)); ?></p><?php endif; ?>

                <?php if (!empty($content['facebook_post_url']) || !empty($content['instagram_post_url'])) : ?><div class="parcs-ht-advent-social-links">
                    <?php if (!empty($content['facebook_post_url'])) : ?><a href="<?php echo esc_url($content['facebook_post_url']); ?>" target="_blank" rel="noopener noreferrer">Facebook</a><?php endif; ?>
                    <?php if (!empty($content['instagram_post_url'])) : ?><a href="<?php echo esc_url($content['instagram_post_url']); ?>" target="_blank" rel="noopener noreferrer">Instagram</a><?php endif; ?>
                </div><?php endif; ?>

                <?php if ($result_due) : ?>
                    <section class="parcs-ht-advent-result">
                        <h4><?php echo esc_html(self::microcopy($campaign, 'result_label', $language)); ?></h4>
                        <?php if (!$result_published) : ?>
                            <p><?php echo esc_html(self::campaign_text($campaign, 'texte_tirage_non_effectue_fr', $language)); ?></p>
                        <?php else : ?>
                            <?php $answer = self::content_text($campaign, $content, 'bonne_reponse_texte_fr', $language); if ($answer === '') $answer = (string)($content['bonne_reponse_code'] ?? ''); if ($answer !== '') : ?><p><strong><?php echo esc_html($answer); ?></strong></p><?php endif; ?>
                            <?php $explanation = self::content_text($campaign, $content, 'explication_reponse_fr', $language); if ($explanation !== '') : ?><div><?php echo wp_kses_post(wpautop($explanation)); ?></div><?php endif; ?>
                            <?php if (!empty($result['gagnant_facebook'])) : ?><p><strong><?php echo esc_html(self::microcopy($campaign, 'winner_facebook_label', $language)); ?> :</strong> <?php echo esc_html($result['gagnant_facebook']); ?></p><?php endif; ?>
                            <?php if (!empty($result['gagnant_instagram'])) : ?><p><strong><?php echo esc_html(self::microcopy($campaign, 'winner_instagram_label', $language)); ?> :</strong> <?php echo esc_html($result['gagnant_instagram']); ?></p><?php endif; ?>
                            <?php if ((string)($content['indice_actif'] ?? '') === 'oui' && !empty($content['indice_lettre']) && !empty($content['indice_position'])) : ?><p class="parcs-ht-advent-clue-reveal"><strong><?php echo esc_html(self::microcopy($campaign, 'clue_label', $language)); ?> :</strong> <?php echo esc_html($content['indice_lettre'] . $content['indice_position']); ?></p><?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ((int)$content['jour_numero'] === 24) echo self::render_final_area($campaign, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne déjà échappé. ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    private static function render_final_area($campaign, $language) {
        $open = self::grand_open_at($campaign);
        if (!$open) return '';
        $now = self::now($campaign);
        if ($now < $open) return '';
        $close = self::grand_close_at($campaign);
        if ($close instanceof DateTimeImmutable && $now > $close) {
            return '<section class="parcs-ht-advent-final"><p>' . esc_html(self::microcopy($campaign, 'final_closed', $language)) . '</p></section>';
        }

        $token = self::authorized_cookie_token((string)$campaign['campagne_id']);
        if ($token !== '' && self::verify_authorization_token($token, (string)$campaign['campagne_id'])) {
            return self::render_final_form($campaign, $language);
        }

        $prompt = self::campaign_text($campaign, 'grand_jeu_texte_saisie_fr', $language);
        ob_start(); ?>
        <section class="parcs-ht-advent-final" data-advent-final>
            <?php if (!empty($campaign['grand_jeu_lot_fr'])) : ?><p class="parcs-ht-advent-final-prize"><?php echo esc_html(self::campaign_text($campaign, 'grand_jeu_lot_fr', $language)); ?></p><?php endif; ?>
            <?php if ($prompt !== '') : ?><p><?php echo esc_html($prompt); ?></p><?php endif; ?>
            <form data-advent-word-form>
                <label><span class="screen-reader-text"><?php echo esc_html($prompt !== '' ? $prompt : 'Mot mystère'); ?></span><input type="text" name="word" autocomplete="off" required></label>
                <button type="submit" class="parcs-ht-advent-primary"><?php echo esc_html(self::microcopy($campaign, 'final_submit', $language)); ?></button>
            </form>
            <div data-advent-word-message aria-live="polite"></div>
        </section>
        <?php return ob_get_clean();
    }

    private static function render_final_form($campaign, $language) {
        $shortcode = (string)($campaign['grand_jeu_formulaire_shortcode'] ?? '');
        if ($shortcode === '') return '';
        $success = self::campaign_text($campaign, 'grand_jeu_texte_succes_fr', $language);
        $form = do_shortcode($shortcode);
        ob_start(); ?>
        <section class="parcs-ht-advent-final is-authorized">
            <?php if ($success !== '') : ?><p class="parcs-ht-advent-final-success"><?php echo esc_html($success); ?></p><?php endif; ?>
            <div class="parcs-ht-advent-final-form"><?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sortie d’un shortcode administrateur exécuté uniquement après autorisation serveur. ?></div>
        </section>
        <?php return ob_get_clean();
    }

    public static function ajax_day() {
        check_ajax_referer(self::PUBLIC_NONCE_ACTION, 'nonce');
        self::apply_preview_request();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        $content_id = isset($_POST['content_id']) ? sanitize_key(wp_unslash($_POST['content_id'])) : '';
        $language = isset($_POST['language']) ? sanitize_key(wp_unslash($_POST['language'])) : 'fr';
        if (!in_array($language, array('fr','en','de'), true)) $language = 'fr';
        $campaign = self::public_campaign($campaign_id);
        if (!$campaign || !isset($campaign['contents'][$content_id]) || !is_array($campaign['contents'][$content_id])) {
            wp_send_json_error(array('message'=>'Contenu indisponible.'), 404);
        }
        $content = $campaign['contents'][$content_id];
        if ((string)($content['type_contenu'] ?? '') !== 'JOUR') wp_send_json_error(array('message'=>'Contenu indisponible.'), 404);
        $open_at = self::day_open_at($campaign, $content);
        if (!$open_at || self::now($campaign) < $open_at) wp_send_json_error(array('message'=>self::microcopy($campaign, 'locked_day', $language)), 403);
        $html = self::render_day_detail($campaign, $content, $language);
        wp_send_json_success(array('html'=>$html));
    }

    public static function ajax_validate_word() {
        check_ajax_referer(self::PUBLIC_NONCE_ACTION, 'nonce');
        self::apply_preview_request();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        $language = isset($_POST['language']) ? sanitize_key(wp_unslash($_POST['language'])) : 'fr';
        $word = isset($_POST['word']) ? sanitize_text_field(wp_unslash($_POST['word'])) : '';
        if (!in_array($language, array('fr','en','de'), true)) $language = 'fr';
        $campaign = self::public_campaign($campaign_id);
        if (!$campaign) wp_send_json_error(array('message'=>'Grand jeu indisponible.'), 404);

        $open = self::grand_open_at($campaign);
        $close = self::grand_close_at($campaign);
        $now = self::now($campaign);
        if (!$open || $now < $open || ($close instanceof DateTimeImmutable && $now > $close)) {
            wp_send_json_error(array('message'=>self::microcopy($campaign, 'final_closed', $language)), 403);
        }
        if (self::rate_limit_reached($campaign_id)) {
            wp_send_json_error(array('message'=>'Trop de tentatives. Merci de réessayer dans quelques minutes.'), 429);
        }

        $expected = self::normalize_word((string)($campaign['mot_mystere'] ?? ''));
        $provided = self::normalize_word($word);
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            self::increment_rate_limit($campaign_id);
            wp_send_json_error(array('message'=>self::campaign_text($campaign, 'grand_jeu_texte_erreur_fr', $language)), 400);
        }

        self::clear_rate_limit($campaign_id);
        $token = self::authorization_token($campaign_id, time() + 30 * MINUTE_IN_SECONDS);
        self::set_authorization_cookie($campaign_id, $token, time() + 30 * MINUTE_IN_SECONDS);
        $html = self::render_final_form($campaign, $language);
        wp_send_json_success(array('html'=>$html));
    }

    private static function normalize_word($word) {
        $word = remove_accents((string)$word);
        $word = function_exists('mb_strtoupper') ? mb_strtoupper($word, 'UTF-8') : strtoupper($word);
        return preg_replace('/[^A-Z0-9]/', '', $word);
    }

    private static function client_fingerprint($campaign_id) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        return substr(hash_hmac('sha256', $ip . '|' . sanitize_key($campaign_id), wp_salt('nonce')), 0, 40);
    }

    private static function rate_limit_key($campaign_id) {
        return 'parcs_ht_adv_rl_' . self::client_fingerprint($campaign_id);
    }

    private static function rate_limit_reached($campaign_id) {
        $state = get_transient(self::rate_limit_key($campaign_id));
        return is_array($state) && (int)($state['count'] ?? 0) >= 8;
    }

    private static function increment_rate_limit($campaign_id) {
        $key = self::rate_limit_key($campaign_id);
        $state = get_transient($key);
        $count = is_array($state) ? (int)($state['count'] ?? 0) : 0;
        set_transient($key, array('count'=>$count + 1), 10 * MINUTE_IN_SECONDS);
    }

    private static function clear_rate_limit($campaign_id) {
        delete_transient(self::rate_limit_key($campaign_id));
    }

    private static function authorization_token($campaign_id, $expires) {
        $payload = wp_json_encode(array('cid'=>sanitize_key($campaign_id),'exp'=>(int)$expires,'rnd'=>wp_generate_password(12, false, false)));
        $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, wp_salt('auth'));
        return $encoded . '.' . $signature;
    }

    private static function verify_authorization_token($token, $campaign_id) {
        $parts = explode('.', (string)$token, 2);
        if (count($parts) !== 2) return false;
        list($encoded, $signature) = $parts;
        $expected = hash_hmac('sha256', $encoded, wp_salt('auth'));
        if (!hash_equals($expected, $signature)) return false;
        $padding = strlen($encoded) % 4;
        if ($padding) $encoded .= str_repeat('=', 4 - $padding);
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false) return false;
        $payload = json_decode($decoded, true);
        if (!is_array($payload)) return false;
        if ((string)($payload['cid'] ?? '') !== sanitize_key($campaign_id)) return false;
        return (int)($payload['exp'] ?? 0) >= time();
    }

    private static function authorization_cookie_name($campaign_id) {
        return 'parcs_ht_advent_auth_' . substr(hash('sha256', sanitize_key($campaign_id)), 0, 16);
    }

    private static function set_authorization_cookie($campaign_id, $token, $expires) {
        $name = self::authorization_cookie_name($campaign_id);
        $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
        setcookie($name, $token, (int)$expires, $path, $domain, is_ssl(), true);
        $_COOKIE[$name] = $token;
    }

    private static function authorized_cookie_token($campaign_id) {
        $name = self::authorization_cookie_name($campaign_id);
        return isset($_COOKIE[$name]) ? sanitize_text_field(wp_unslash($_COOKIE[$name])) : '';
    }

    public static function social_post($campaign, $content, $network = 'facebook', $language = 'fr') {
        if (!is_array($campaign) || !is_array($content)) return '';
        $network = $network === 'instagram' ? 'instagram' : 'facebook';
        $override = self::content_text($campaign, $content, 'texte_post_override_fr', $language);
        $lines = array();
        if ($override !== '') {
            $lines[] = $override;
        } else {
            $title = self::content_text($campaign, $content, 'titre_fr', $language);
            if ($title !== '') $lines[] = $title;
            $partner = self::partner($campaign, (string)($content['partenaire_id'] ?? ''));
            if ($partner && !empty($partner['nom'])) $lines[] = 'Partenaire : ' . $partner['nom'];
            $lot = self::content_text($campaign, $content, 'lot_fr', $language);
            if ($lot !== '') $lines[] = 'À gagner : ' . $lot;
            $intro = self::content_text($campaign, $content, 'intro_question_fr', $language);
            if ($intro !== '') $lines[] = $intro;
            $question = self::content_text($campaign, $content, 'question_fr', $language);
            if ($question !== '') $lines[] = $question;
            foreach (array('a'=>'A','b'=>'B','c'=>'C','d'=>'D') as $key => $letter) {
                $answer = self::content_text($campaign, $content, 'reponse_' . $key . '_fr', $language);
                if ($answer !== '') $lines[] = $letter . '. ' . $answer;
            }
            $rules = self::campaign_text($campaign, 'reglement_quotidien_fr', $language);
            if ($rules !== '') $lines[] = $rules;
        }
        if ((string)($content['type_contenu'] ?? '') === 'JOUR' && (string)($content['indice_actif'] ?? '') === 'oui' && (string)($content['afficher_rappel_grand_jeu'] ?? 'auto') !== 'non') {
            $reminder = self::content_text($campaign, $content, 'rappel_grand_jeu_override_fr', $language);
            if ($reminder === '') $reminder = self::campaign_text($campaign, 'texte_rappel_grand_jeu_fr', $language);
            if ($reminder !== '') $lines[] = $reminder;
        }
        if ((string)($content['type_contenu'] ?? '') === 'JOUR' && !empty($campaign['page_calendrier_url'])) {
            $link_text = self::campaign_text($campaign, 'texte_lien_calendrier_social_fr', $language);
            $lines[] = trim($link_text . ' ' . $campaign['page_calendrier_url']);
        }
        if (!empty($campaign['hashtags_defaut'])) $lines[] = trim((string)$campaign['hashtags_defaut']);
        return trim(implode("\n\n", array_values(array_filter($lines, 'strlen'))));
    }

    public static function social_result($campaign, $content, $result, $network = 'facebook', $language = 'fr') {
        if (!is_array($campaign) || !is_array($content) || !is_array($result)) return '';
        $network = $network === 'instagram' ? 'instagram' : 'facebook';
        $override = self::translation($campaign, (string)$result['resultat_id'], 'texte_resultat_override_fr', $language, (string)($result['texte_resultat_override_fr'] ?? ''));
        if ($override !== '') return $override;
        $lines = array();
        $answer = self::content_text($campaign, $content, 'bonne_reponse_texte_fr', $language);
        if ($answer === '') $answer = (string)($content['bonne_reponse_code'] ?? '');
        if ($answer !== '') $lines[] = 'Bonne réponse : ' . $answer;
        $explanation = self::content_text($campaign, $content, 'explication_reponse_fr', $language);
        if ($explanation !== '') $lines[] = $explanation;
        $winner = $network === 'instagram' ? (string)($result['gagnant_instagram'] ?? '') : (string)($result['gagnant_facebook'] ?? '');
        if ($winner !== '') $lines[] = 'Gagnant : ' . $winner;
        $partner = self::partner($campaign, (string)($content['partenaire_id'] ?? ''));
        if ($partner && !empty($partner['nom'])) $lines[] = 'Merci à ' . $partner['nom'] . '.';
        if (!empty($campaign['page_calendrier_url'])) $lines[] = trim(self::campaign_text($campaign, 'texte_lien_calendrier_social_fr', $language) . ' ' . $campaign['page_calendrier_url']);
        return trim(implode("\n\n", array_values(array_filter($lines, 'strlen'))));
    }

    public static function story_result($campaign, $content, $result, $language = 'fr') {
        if (!is_array($campaign) || !is_array($content) || !is_array($result)) return '';
        $override = self::translation($campaign, (string)$result['resultat_id'], 'story_resultat_override_fr', $language, (string)($result['story_resultat_override_fr'] ?? ''));
        if ($override !== '') return $override;
        $answer = self::content_text($campaign, $content, 'bonne_reponse_texte_fr', $language);
        if ($answer === '') $answer = (string)($content['bonne_reponse_code'] ?? '');
        $winner_fb = (string)($result['gagnant_facebook'] ?? '');
        $winner_ig = (string)($result['gagnant_instagram'] ?? '');
        $lines = array();
        if ($answer !== '') $lines[] = 'Réponse : ' . $answer;
        if ($winner_fb !== '') $lines[] = 'Facebook : ' . $winner_fb;
        if ($winner_ig !== '') $lines[] = 'Instagram : ' . $winner_ig;
        return implode("\n", $lines);
    }
}
