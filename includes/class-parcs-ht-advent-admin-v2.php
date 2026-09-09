<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Administration compacte du Calendrier de l’Avent. */
final class Parcs_HT_Advent_Admin {
    const PAGE = 'parcs-ht-advent';
    const IMPORT_TRANSIENT_PREFIX = 'parcs_ht_advent_import_';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('admin_post_parcs_ht_advent_create_campaign', array(__CLASS__, 'create_campaign'));
        add_action('admin_post_parcs_ht_advent_save_campaign', array(__CLASS__, 'save_campaign'));
        add_action('admin_post_parcs_ht_advent_save_content', array(__CLASS__, 'save_content'));
        add_action('admin_post_parcs_ht_advent_save_partner', array(__CLASS__, 'save_partner'));
        add_action('admin_post_parcs_ht_advent_save_result', array(__CLASS__, 'save_result'));
        add_action('admin_post_parcs_ht_advent_import_csv', array(__CLASS__, 'import_csv'));
        add_action('admin_post_parcs_ht_advent_apply_import', array(__CLASS__, 'apply_import'));
        add_action('admin_post_parcs_ht_advent_csv_template', array(__CLASS__, 'csv_template'));
    }

    public static function menu() {
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Calendrier de l’Avent',
            'Calendrier de l’Avent',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    public static function assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Lecture du slug uniquement pour limiter les assets.
        if ($page === self::PAGE) {
            wp_enqueue_media();
            wp_enqueue_style('parcs-ht-advent-admin', PARCS_HT_URL . 'assets/advent-admin.css', array(), PARCS_HT_VERSION);
            wp_enqueue_script('parcs-ht-advent-admin', PARCS_HT_URL . 'assets/advent-admin.js', array('jquery'), PARCS_HT_VERSION, true);
        }
        if ($hook === 'toplevel_page_parcs-horaires-tarifs') {
            wp_enqueue_script('parcs-ht-advent-shortcodes-admin', PARCS_HT_URL . 'assets/advent-shortcodes-admin.js', array(), PARCS_HT_VERSION, true);
        }
    }

    private static function require_admin() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.');
        }
    }

    private static function campaign_id_from_request($source = 'get') {
        if ($source === 'post') {
            return isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        }
        return isset($_GET['campaign']) ? sanitize_key(wp_unslash($_GET['campaign'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection de campagne en lecture seule.
    }

    private static function current_view() {
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'campaign'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’onglet en lecture seule.
        return in_array($view, array('campaign','teasers','calendar','grand','partners','results','import'), true) ? $view : 'campaign';
    }

    private static function admin_url($campaign_id = '', $view = 'campaign', $extra = array()) {
        $args = array('page'=>self::PAGE, 'view'=>$view);
        if ($campaign_id !== '') $args['campaign'] = $campaign_id;
        if (is_array($extra)) $args = array_merge($args, $extra);
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function redirect($campaign_id, $view, $args = array()) {
        wp_safe_redirect(self::admin_url($campaign_id, $view, $args));
        exit;
    }

    private static function campaigns_for_installation() {
        $park = Parcs_HT_Advent::installation_park_code();
        $store = Parcs_HT_Advent::store();
        $campaigns = array();
        foreach ((array)$store['campaigns'] as $campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park) continue;
            $id = sanitize_key((string)($campaign['campagne_id'] ?? ''));
            if ($id !== '') $campaigns[$id] = $campaign;
        }
        uasort($campaigns, static function ($a, $b) {
            $cmp = ((int)($b['annee'] ?? 0)) <=> ((int)($a['annee'] ?? 0));
            return $cmp !== 0 ? $cmp : strcmp((string)($a['campagne_id'] ?? ''), (string)($b['campagne_id'] ?? ''));
        });
        return $campaigns;
    }

    private static function first_key($items) {
        foreach ((array)$items as $key => $unused) return (string)$key;
        return '';
    }

    public static function page() {
        self::require_admin();
        $park = Parcs_HT_Advent::installation_park_code();
        $campaigns = self::campaigns_for_installation();
        $campaign_id = self::campaign_id_from_request();
        if ($campaign_id === '') $campaign_id = self::first_key($campaigns);
        $campaign = isset($campaigns[$campaign_id]) ? $campaigns[$campaign_id] : null;
        $view = self::current_view();
        ?>
        <div class="wrap htp-advent-admin">
            <h1>Calendrier de l’Avent</h1>
            <p class="description">Prototype schéma <?php echo esc_html((string)Parcs_HT_Advent::SCHEMA_VERSION); ?>. Les données restent isolées par installation et campagne.</p>
            <?php self::notices(); ?>
            <?php if ($park === '') : ?>
                <div class="notice notice-error"><p>Le type de parc de cette installation doit être configuré sur <code>mds</code> ou <code>fds</code> avant d’utiliser le module.</p></div>
            <?php else : ?>
                <?php self::campaign_switcher($park, $campaigns, $campaign_id, $view); ?>
                <?php if (is_array($campaign)) : ?>
                    <?php self::navigation($campaign_id, $view); ?>
                    <?php
                    if ($view === 'campaign') self::campaign_view($campaign);
                    elseif ($view === 'teasers') self::teasers_view($campaign);
                    elseif ($view === 'calendar') self::calendar_view($campaign);
                    elseif ($view === 'grand') self::grand_view($campaign);
                    elseif ($view === 'partners') self::partners_view($campaign);
                    elseif ($view === 'results') self::results_view($campaign);
                    else self::import_view($campaign);
                    ?>
                <?php else : ?>
                    <section class="htp-advent-card"><p>Aucune campagne n’existe encore. Créez une campagne brouillon pour initialiser les 24 journées.</p></section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function notices() {
        $notice = isset($_GET['advent_notice']) ? sanitize_key(wp_unslash($_GET['advent_notice'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message de retour en lecture seule.
        $messages = array(
            'created'=>'La campagne a été créée avec ses 24 journées.',
            'saved'=>'Les réglages ont été enregistrés.',
            'content_saved'=>'Le contenu a été enregistré.',
            'partner_saved'=>'Le partenaire a été enregistré.',
            'result_saved'=>'Le résultat a été enregistré sans publication automatique.',
            'result_published'=>'Le résultat est publié ; il restera masqué jusqu’à son heure de révélation.',
            'imported'=>'La mise à jour intelligente a été appliquée.',
            'import_stale'=>'La campagne a changé depuis l’aperçu de l’import. Relancez l’analyse.',
        );
        if (isset($messages[$notice])) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$notice]) . '</p></div>';
        $error = isset($_GET['advent_error']) ? sanitize_text_field(wp_unslash($_GET['advent_error'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message de retour en lecture seule.
        if ($error !== '') echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    }

    private static function campaign_switcher($park, $campaigns, $campaign_id, $view) {
        ?>
        <section class="htp-advent-card htp-advent-campaign-switcher">
            <div><strong>Installation :</strong> <code><?php echo esc_html($park); ?></code>
            <?php if ($campaigns) : ?>
                <label for="htp-advent-campaign-select">Campagne</label>
                <select id="htp-advent-campaign-select" data-advent-campaign-select data-base-url="<?php echo esc_url(self::admin_url('', $view)); ?>">
                    <?php foreach ($campaigns as $id => $row) : ?><option value="<?php echo esc_attr($id); ?>" <?php selected($campaign_id, $id); ?>><?php echo esc_html(($row['nom_campagne'] ?: $id) . ' · ' . ($row['annee'] ?: '—') . ' · ' . $row['statut_campagne']); ?></option><?php endforeach; ?>
                </select>
            <?php endif; ?></div>
            <details><summary>Créer une campagne</summary>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-advent-inline-form">
                    <input type="hidden" name="action" value="parcs_ht_advent_create_campaign">
                    <?php wp_nonce_field('parcs_ht_advent_create_campaign'); ?>
                    <label><span>ID stable</span><input name="campaign_id" pattern="[a-z0-9_-]+" required></label>
                    <label><span>Année</span><input type="number" name="year" min="2020" max="2100" required></label>
                    <label><span>Nom</span><input name="name" required></label>
                    <button class="button button-primary" type="submit">Créer</button>
                </form>
            </details>
        </section>
        <?php
    }

    private static function navigation($campaign_id, $active) {
        $tabs = array('campaign'=>'Campagne','teasers'=>'Teasings sociaux','calendar'=>'Calendrier','grand'=>'Grand jeu','partners'=>'Partenaires','results'=>'Résultats','import'=>'Import / export');
        echo '<nav class="nav-tab-wrapper htp-advent-tabs" aria-label="Sections du Calendrier de l’Avent">';
        foreach ($tabs as $view => $label) {
            echo '<a class="nav-tab' . ($view === $active ? ' nav-tab-active' : '') . '" href="' . esc_url(self::admin_url($campaign_id, $view)) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }

    private static function form_start($action, $campaign_id, $view) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
        echo '<input type="hidden" name="campaign_id" value="' . esc_attr($campaign_id) . '">';
        echo '<input type="hidden" name="return_view" value="' . esc_attr($view) . '">';
        wp_nonce_field($action . '_' . $campaign_id);
    }

    private static function input($name, $value, $label, $type = 'text', $attrs = '') {
        echo '<label class="htp-advent-field"><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string)$value) . '" ' . $attrs . '></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attrs est fourni uniquement par le code interne.
    }

    private static function textarea($name, $value, $label, $rows = 4) {
        echo '<label class="htp-advent-field htp-advent-field-wide"><span>' . esc_html($label) . '</span><textarea name="' . esc_attr($name) . '" rows="' . esc_attr((string)$rows) . '">' . esc_textarea((string)$value) . '</textarea></label>';
    }

    private static function select($name, $value, $label, $options) {
        echo '<label class="htp-advent-field"><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '">';
        foreach ($options as $key => $text) echo '<option value="' . esc_attr($key) . '" ' . selected((string)$value, (string)$key, false) . '>' . esc_html($text) . '</option>';
        echo '</select></label>';
    }

    private static function checkbox($name, $checked, $label, $checked_value = '1', $unchecked_value = '0', $data = '') {
        echo '<label class="htp-advent-field htp-advent-check"><span>' . esc_html($label) . '</span><span><input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($unchecked_value) . '"><input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr($checked_value) . '" ' . checked((string)$checked, (string)$checked_value, false) . ' ' . $data . '></span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $data est fourni uniquement par le code interne.
    }

    private static function media_field($source_name, $url_name, $source, $url, $label) {
        ?>
        <div class="htp-advent-field htp-advent-field-wide" data-advent-media-field>
            <span><?php echo esc_html($label); ?></span>
            <input type="hidden" name="<?php echo esc_attr($source_name); ?>" value="<?php echo esc_attr((string)$source); ?>" data-advent-media-source>
            <div class="htp-advent-media-row"><input type="url" name="<?php echo esc_attr($url_name); ?>" value="<?php echo esc_attr((string)$url); ?>" data-advent-media-url><button type="button" class="button" data-advent-media-select data-media-kind="image">Choisir dans WordPress</button><button type="button" class="button" data-advent-media-clear>Retirer</button></div>
            <div class="htp-advent-media-preview <?php echo $url !== '' ? 'has-media' : 'is-empty'; ?>" data-advent-media-preview><?php if ($url !== '') : ?><img src="<?php echo esc_url($url); ?>" alt=""><?php else : ?><span>Visuel 4:5 — à ajouter</span><?php endif; ?></div>
        </div>
        <?php
    }

    private static function copy_box($title, $text) {
        echo '<div class="htp-advent-copy-box"><strong>' . esc_html($title) . '</strong><textarea readonly data-advent-copy-source rows="8">' . esc_textarea((string)$text) . '</textarea><button type="button" class="button" data-advent-copy-button>Copier le texte</button></div>';
    }

    private static function campaign_view($campaign) {
        self::form_start('parcs_ht_advent_save_campaign', $campaign['campagne_id'], 'campaign');
        ?>
        <section class="htp-advent-card"><h2>Campagne</h2><div class="htp-advent-grid-fields">
            <?php self::input('campaign[nom_campagne]', $campaign['nom_campagne'], 'Nom interne'); ?>
            <?php self::input('campaign[annee]', $campaign['annee'], 'Année', 'number', 'min="2020" max="2100"'); ?>
            <?php self::select('campaign[statut_campagne]', $campaign['statut_campagne'], 'Statut', array('brouillon'=>'Brouillon','active'=>'Active','archivee'=>'Archivée')); ?>
            <?php self::input('campaign[timezone]', $campaign['timezone'], 'Fuseau horaire'); ?>
            <?php self::input('campaign[langue_sociale]', $campaign['langue_sociale'], 'Langue sociale'); ?>
            <?php self::input('campaign[langues_site]', $campaign['langues_site'], 'Langues du site'); ?>
            <?php self::select('campaign[fallback_traduction]', $campaign['fallback_traduction'], 'Traduction manquante', array('fr'=>'Reprendre le français','masquer'=>'Masquer','strict'=>'Exiger la traduction')); ?>
            <?php self::input('campaign[date_ouverture_calendrier]', $campaign['date_ouverture_calendrier'], 'Date d’ouverture', 'date'); ?>
            <?php self::input('campaign[date_fin_calendrier]', $campaign['date_fin_calendrier'], 'Date de fin / archive', 'date'); ?>
            <?php self::input('campaign[heure_ouverture_globale]', $campaign['heure_ouverture_globale'], 'Heure d’ouverture globale', 'time'); ?>
            <?php self::input('campaign[page_calendrier_url]', $campaign['page_calendrier_url'], 'URL page calendrier', 'url'); ?>
            <?php self::input('campaign[page_reglement_url]', $campaign['page_reglement_url'], 'URL page règlement', 'url'); ?>
            <?php self::input('campaign[titre_bloc_calendrier_fr]', $campaign['titre_bloc_calendrier_fr'], 'Titre du bloc'); ?>
            <?php self::textarea('campaign[texte_intro_calendrier_fr]', $campaign['texte_intro_calendrier_fr'], 'Introduction'); ?>
            <?php self::input('campaign[libelle_comment_participer_fr]', $campaign['libelle_comment_participer_fr'], 'Libellé « Comment participer ? »'); ?>
            <?php self::textarea('campaign[texte_comment_participer_fr]', $campaign['texte_comment_participer_fr'], 'Explication courte'); ?>
            <?php self::input('campaign[libelle_reglement_complet_fr]', $campaign['libelle_reglement_complet_fr'], 'Libellé règlement complet'); ?>
            <?php self::textarea('campaign[reglement_complet_fr]', $campaign['reglement_complet_fr'], 'Règlement complet', 12); ?>
            <?php self::textarea('campaign[texte_tirage_non_effectue_fr]', $campaign['texte_tirage_non_effectue_fr'], 'Texte si tirage non publié'); ?>
            <?php self::textarea('campaign[reglement_quotidien_fr]', $campaign['reglement_quotidien_fr'], 'Rappel participation quotidien'); ?>
            <?php self::textarea('campaign[texte_rappel_grand_jeu_fr]', $campaign['texte_rappel_grand_jeu_fr'], 'Rappel du mot mystère'); ?>
            <?php self::textarea('campaign[texte_lien_calendrier_social_fr]', $campaign['texte_lien_calendrier_social_fr'], 'Texte du lien calendrier'); ?>
            <?php self::input('campaign[instagram_parc]', $campaign['instagram_parc'], 'Compte Instagram'); ?>
            <?php self::input('campaign[facebook_slug]', $campaign['facebook_slug'], 'Identifiant Facebook'); ?>
            <?php self::input('campaign[facebook_url_override]', $campaign['facebook_url_override'], 'URL Facebook spécifique', 'url'); ?>
            <?php self::textarea('campaign[hashtags_defaut]', $campaign['hashtags_defaut'], 'Hashtags par défaut'); ?>
            <?php self::media_field('campaign[teasing_site_visuel_source]', 'campaign[teasing_site_visuel_url]', $campaign['teasing_site_visuel_source'], $campaign['teasing_site_visuel_url'], 'Visuel teasing public 4:5'); ?>
            <?php self::input('campaign[teasing_site_visuel_alt_fr]', $campaign['teasing_site_visuel_alt_fr'], 'Texte alternatif teasing'); ?>
        </div><p><button class="button button-primary" type="submit">Enregistrer la campagne</button></p></section></form>
        <?php
    }

    private static function grand_view($campaign) {
        self::form_start('parcs_ht_advent_save_campaign', $campaign['campagne_id'], 'grand'); ?>
        <section class="htp-advent-card"><h2>Grand jeu final</h2><p class="description">Le lot du grand jeu est indépendant du lot quotidien du jour 24. Le mot et le shortcode du formulaire restent côté serveur.</p><div class="htp-advent-grid-fields">
            <?php self::input('campaign[grand_jeu_date_ouverture]', $campaign['grand_jeu_date_ouverture'], 'Ouverture', 'date'); ?>
            <?php self::input('campaign[grand_jeu_heure_ouverture]', $campaign['grand_jeu_heure_ouverture'], 'Heure d’ouverture', 'time'); ?>
            <?php self::input('campaign[grand_jeu_date_fermeture]', $campaign['grand_jeu_date_fermeture'], 'Fermeture', 'date'); ?>
            <?php self::input('campaign[grand_jeu_heure_fermeture]', $campaign['grand_jeu_heure_fermeture'], 'Heure de fermeture', 'time'); ?>
            <?php self::input('campaign[grand_jeu_date_revelation]', $campaign['grand_jeu_date_revelation'], 'Date de révélation finale', 'date'); ?>
            <?php self::input('campaign[mot_mystere]', $campaign['mot_mystere'], 'Mot mystère'); ?>
            <?php self::input('campaign[grand_jeu_lot_fr]', $campaign['grand_jeu_lot_fr'], 'Grand lot'); ?>
            <?php self::input('campaign[grand_jeu_pictogramme_url]', $campaign['grand_jeu_pictogramme_url'], 'URL pictogramme', 'url'); ?>
            <?php self::textarea('campaign[grand_jeu_texte_saisie_fr]', $campaign['grand_jeu_texte_saisie_fr'], 'Texte de saisie'); ?>
            <?php self::textarea('campaign[grand_jeu_texte_erreur_fr]', $campaign['grand_jeu_texte_erreur_fr'], 'Texte mot faux'); ?>
            <?php self::textarea('campaign[grand_jeu_texte_succes_fr]', $campaign['grand_jeu_texte_succes_fr'], 'Texte mot correct'); ?>
            <?php self::input('campaign[grand_jeu_formulaire_shortcode]', $campaign['grand_jeu_formulaire_shortcode'], 'Shortcode du formulaire final'); ?>
        </div><p><button class="button button-primary" type="submit">Enregistrer le grand jeu</button></p></section></form>
        <?php
    }

    private static function sorted_days($campaign) {
        $days = array();
        foreach ((array)$campaign['contents'] as $content) if (is_array($content) && (string)($content['type_contenu'] ?? '') === 'JOUR') $days[] = $content;
        usort($days, static function ($a, $b) { return ((int)$a['jour_numero']) <=> ((int)$b['jour_numero']); });
        return $days;
    }

    private static function calendar_view($campaign) {
        $selected = isset($_GET['day']) ? sanitize_key(wp_unslash($_GET['day'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’un éditeur en lecture seule.
        $days = self::sorted_days($campaign);
        echo '<section class="htp-advent-card"><h2>Calendrier — 24 jours</h2><div class="htp-advent-day-grid">';
        foreach ($days as $day) {
            $id = (string)$day['contenu_id'];
            $classes = 'htp-advent-day-card' . ($selected === $id ? ' is-selected' : '');
            $meta = array();
            $meta[] = !empty($day['visuel_url']) ? 'visuel ✓' : 'visuel manquant';
            if ((string)$day['indice_actif'] === 'oui') $meta[] = 'indice';
            if (!empty($day['partenaire_id'])) $meta[] = 'partenaire';
            echo '<a class="' . esc_attr($classes) . '" href="' . esc_url(self::admin_url($campaign['campagne_id'], 'calendar', array('day'=>$id))) . '"><strong>Jour ' . esc_html((string)$day['jour_numero']) . '</strong><span>' . esc_html(implode(' · ', $meta)) . '</span></a>';
        }
        echo '</div></section>';
        if ($selected !== '' && isset($campaign['contents'][$selected])) self::content_editor($campaign, $campaign['contents'][$selected], 'calendar');
    }

    private static function teasers_view($campaign) {
        $selected = isset($_GET['teaser']) ? sanitize_key(wp_unslash($_GET['teaser'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection en lecture seule.
        $teasers = array();
        foreach ((array)$campaign['contents'] as $content) if (is_array($content) && (string)($content['type_contenu'] ?? '') === 'TEASING') $teasers[$content['contenu_id']] = $content;
        echo '<section class="htp-advent-card"><h2>Teasings sociaux</h2><p>Nombre libre. Ils servent au planning et aux textes sociaux ; le site utilise le visuel teasing public unique de la campagne.</p><p><a class="button" href="' . esc_url(self::admin_url($campaign['campagne_id'], 'teasers', array('teaser'=>'new'))) . '">Nouveau teasing</a></p><div class="htp-advent-list">';
        foreach ($teasers as $id => $row) echo '<a href="' . esc_url(self::admin_url($campaign['campagne_id'], 'teasers', array('teaser'=>$id))) . '"><strong>' . esc_html($row['titre_fr'] ?: $id) . '</strong><span>' . esc_html(trim($row['date_publication'] . ' ' . $row['heure_publication'])) . '</span></a>';
        echo '</div></section>';
        if ($selected === 'new') self::content_editor($campaign, Parcs_HT_Advent::default_content(), 'teasers');
        elseif ($selected !== '' && isset($teasers[$selected])) self::content_editor($campaign, $teasers[$selected], 'teasers');
    }

    private static function content_editor($campaign, $content, $view) {
        self::form_start('parcs_ht_advent_save_content', $campaign['campagne_id'], $view);
        $is_day = (string)$content['type_contenu'] === 'JOUR';
        ?>
        <section class="htp-advent-card" data-advent-clue-admin><h2><?php echo esc_html($is_day ? 'Édition du jour ' . $content['jour_numero'] : 'Édition du teasing'); ?></h2><div class="htp-advent-grid-fields">
            <?php self::input('content[contenu_id]', $content['contenu_id'], 'Identifiant stable'); ?>
            <?php self::select('content[type_contenu]', $content['type_contenu'], 'Type', array('JOUR'=>'Jour','TEASING'=>'Teasing')); ?>
            <?php self::input('content[jour_numero]', $content['jour_numero'], 'Numéro du jour', 'number', 'min="0" max="24"'); ?>
            <?php self::input('content[date_publication]', $content['date_publication'], 'Date principale', 'date'); ?>
            <?php self::input('content[heure_publication]', $content['heure_publication'], 'Heure principale', 'time'); ?>
            <?php self::input('content[heure_ouverture]', $content['heure_ouverture'], 'Heure d’ouverture spécifique', 'time'); ?>
            <?php self::checkbox('content[facebook_actif]', $content['facebook_actif'], 'Facebook actif'); ?>
            <?php self::input('content[facebook_date_publication]', $content['facebook_date_publication'], 'Date Facebook spécifique', 'date'); ?>
            <?php self::input('content[facebook_heure_publication]', $content['facebook_heure_publication'], 'Heure Facebook spécifique', 'time'); ?>
            <?php self::checkbox('content[instagram_actif]', $content['instagram_actif'], 'Instagram actif'); ?>
            <?php self::input('content[instagram_date_publication]', $content['instagram_date_publication'], 'Date Instagram spécifique', 'date'); ?>
            <?php self::input('content[instagram_heure_publication]', $content['instagram_heure_publication'], 'Heure Instagram spécifique', 'time'); ?>
            <?php self::input('content[phase]', $content['phase'], 'Phase'); ?>
            <?php self::input('content[titre_fr]', $content['titre_fr'], 'Titre'); ?>
            <?php self::input('content[partenaire_id]', $content['partenaire_id'], 'ID partenaire'); ?>
            <?php self::input('content[lot_fr]', $content['lot_fr'], 'Lot du jour'); ?>
            <?php self::textarea('content[intro_partenaire_fr]', $content['intro_partenaire_fr'], 'Introduction partenaire — 1 à 2 phrases'); ?>
            <?php self::textarea('content[intro_question_fr]', $content['intro_question_fr'], 'Introduction question — courte'); ?>
            <?php self::select('content[format_jeu]', $content['format_jeu'], 'Format du jeu', array('QCM'=>'QCM','VRAI_FAUX'=>'Vrai / faux','CHOIX_MULTIPLE'=>'Choix multiples')); ?>
            <?php self::textarea('content[question_fr]', $content['question_fr'], 'Question'); ?>
            <?php foreach (array('a'=>'A','b'=>'B','c'=>'C','d'=>'D') as $key=>$letter) self::input('content[reponse_' . $key . '_fr]', $content['reponse_' . $key . '_fr'], 'Réponse ' . $letter); ?>
            <?php self::input('content[bonne_reponse_code]', $content['bonne_reponse_code'], 'Bonne réponse — code serveur'); ?>
            <?php self::input('content[bonne_reponse_texte_fr]', $content['bonne_reponse_texte_fr'], 'Bonne réponse — texte serveur'); ?>
            <?php self::textarea('content[explication_reponse_fr]', $content['explication_reponse_fr'], 'Explication de la réponse'); ?>
            <?php self::media_field('content[visuel_source]', 'content[visuel_url]', $content['visuel_source'], $content['visuel_url'], 'Visuel 4:5'); ?>
            <?php self::input('content[visuel_alt_fr]', $content['visuel_alt_fr'], 'Texte alternatif public'); ?>
            <?php self::checkbox('content[indice_actif]', $content['indice_actif'], 'Ce jour contient un indice', 'oui', 'non', 'data-advent-clue-toggle'); ?>
            <div class="htp-advent-clue-fields htp-advent-field-wide" data-advent-clue-fields <?php echo (string)$content['indice_actif'] === 'oui' ? '' : 'hidden'; ?>><div class="htp-advent-grid-fields">
                <?php self::input('content[indice_lettre]', $content['indice_lettre'], 'Lettre secrète'); ?>
                <?php self::input('content[indice_position]', $content['indice_position'], 'Position secrète', 'number', 'min="1"'); ?>
                <?php self::select('content[afficher_rappel_grand_jeu]', $content['afficher_rappel_grand_jeu'], 'Rappel du grand jeu', array('auto'=>'Automatique','oui'=>'Oui','non'=>'Non')); ?>
                <?php self::textarea('content[rappel_grand_jeu_override_fr]', $content['rappel_grand_jeu_override_fr'], 'Rappel spécifique'); ?>
            </div></div>
            <?php self::textarea('content[texte_post_override_fr]', $content['texte_post_override_fr'], 'Texte social manuel — override'); ?>
            <?php self::input('content[facebook_post_url]', $content['facebook_post_url'], 'URL exacte du post Facebook', 'url'); ?>
            <?php self::input('content[instagram_post_url]', $content['instagram_post_url'], 'URL exacte du post Instagram', 'url'); ?>
            <?php self::input('content[statut]', $content['statut'], 'Statut éditorial'); ?>
        </div>
        <div class="htp-advent-copy-grid"><?php self::copy_box('Aperçu Facebook', Parcs_HT_Advent::social_post($campaign, $content, 'facebook', 'fr')); self::copy_box('Aperçu Instagram', Parcs_HT_Advent::social_post($campaign, $content, 'instagram', 'fr')); ?></div>
        <p><button class="button button-primary" type="submit">Enregistrer ce contenu</button></p></section></form>
        <?php
    }

    private static function partners_view($campaign) {
        $selected = isset($_GET['partner']) ? sanitize_key(wp_unslash($_GET['partner'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection en lecture seule.
        echo '<section class="htp-advent-card"><h2>Partenaires</h2><p><a class="button" href="' . esc_url(self::admin_url($campaign['campagne_id'], 'partners', array('partner'=>'new'))) . '">Nouveau partenaire</a></p><div class="htp-advent-list">';
        foreach ((array)$campaign['partners'] as $id=>$partner) echo '<a href="' . esc_url(self::admin_url($campaign['campagne_id'], 'partners', array('partner'=>$id))) . '"><strong>' . esc_html($partner['nom'] ?: $id) . '</strong><span>' . esc_html($id) . '</span></a>';
        echo '</div></section>';
        if ($selected === '') return;
        $partner = $selected === 'new' ? Parcs_HT_Advent::default_partner() : ($campaign['partners'][$selected] ?? null);
        if (!is_array($partner)) return;
        self::form_start('parcs_ht_advent_save_partner', $campaign['campagne_id'], 'partners'); ?>
        <section class="htp-advent-card"><h2>Édition partenaire</h2><div class="htp-advent-grid-fields">
            <?php self::input('partner[partenaire_id]', $partner['partenaire_id'], 'Identifiant stable'); ?>
            <?php self::input('partner[nom]', $partner['nom'], 'Nom'); ?>
            <?php self::select('partner[type_partenaire]', $partner['type_partenaire'], 'Type', array('externe'=>'Externe','parc'=>'Parc')); ?>
            <?php self::input('partner[instagram_handle]', $partner['instagram_handle'], 'Instagram'); ?>
            <?php self::input('partner[instagram_url_override]', $partner['instagram_url_override'], 'URL Instagram spécifique', 'url'); ?>
            <?php self::input('partner[facebook_slug]', $partner['facebook_slug'], 'Facebook'); ?>
            <?php self::input('partner[facebook_url_override]', $partner['facebook_url_override'], 'URL Facebook spécifique', 'url'); ?>
            <?php self::input('partner[site_url]', $partner['site_url'], 'Site web', 'url'); ?>
            <?php self::textarea('partner[description_fr]', $partner['description_fr'], 'Présentation courte'); ?>
            <?php self::textarea('partner[hashtags]', $partner['hashtags'], 'Hashtags'); ?>
            <?php self::media_field('partner[logo_source]', 'partner[logo_url]', $partner['logo_source'], $partner['logo_url'], 'Logo'); ?>
        </div><p><button class="button button-primary" type="submit">Enregistrer le partenaire</button></p></section></form>
        <?php
    }

    private static function result_for_content($campaign, $content_id) {
        foreach ((array)$campaign['results'] as $result) {
            if (is_array($result) && (string)($result['contenu_id'] ?? '') === (string)$content_id) return $result;
        }
        return Parcs_HT_Advent::default_result($content_id);
    }

    private static function results_view($campaign) {
        $selected = isset($_GET['result']) ? sanitize_key(wp_unslash($_GET['result'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection en lecture seule.
        $days = self::sorted_days($campaign);
        echo '<section class="htp-advent-card"><h2>Résultats</h2><div class="htp-advent-day-grid">';
        foreach ($days as $day) {
            $result = self::result_for_content($campaign, $day['contenu_id']);
            echo '<a class="htp-advent-day-card' . ($selected === $result['resultat_id'] ? ' is-selected' : '') . '" href="' . esc_url(self::admin_url($campaign['campagne_id'], 'results', array('result'=>$result['resultat_id']))) . '"><strong>Jour ' . esc_html((string)$day['jour_numero']) . '</strong><span>' . esc_html($result['statut_resultat']) . '</span></a>';
        }
        echo '</div></section>';
        if ($selected === '' || !isset($campaign['results'][$selected])) return;
        $result = $campaign['results'][$selected];
        $content = $campaign['contents'][$result['contenu_id']] ?? null;
        if (!is_array($content)) return;
        self::form_start('parcs_ht_advent_save_result', $campaign['campagne_id'], 'results'); ?>
        <section class="htp-advent-card"><h2>Résultat — jour <?php echo esc_html((string)$content['jour_numero']); ?></h2><div class="htp-advent-grid-fields">
            <input type="hidden" name="result[resultat_id]" value="<?php echo esc_attr($result['resultat_id']); ?>"><input type="hidden" name="result[contenu_id]" value="<?php echo esc_attr($result['contenu_id']); ?>">
            <?php self::input('result[date_revelation_resultat]', $result['date_revelation_resultat'], 'Date de révélation', 'date'); ?>
            <?php self::input('result[heure_revelation_resultat]', $result['heure_revelation_resultat'], 'Heure de révélation', 'time'); ?>
            <?php self::input('result[gagnant_facebook]', $result['gagnant_facebook'], 'Gagnant Facebook'); ?>
            <?php self::input('result[gagnant_instagram]', $result['gagnant_instagram'], 'Gagnant Instagram'); ?>
            <?php self::textarea('result[texte_resultat_override_fr]', $result['texte_resultat_override_fr'], 'Override commentaire résultat'); ?>
            <?php self::textarea('result[story_resultat_override_fr]', $result['story_resultat_override_fr'], 'Override Story'); ?>
            <?php self::select('result[statut_resultat]', $result['statut_resultat'], 'Statut', array('brouillon'=>'Brouillon','pret'=>'Prêt','publie'=>'Publié')); ?>
        </div><div class="htp-advent-copy-grid">
            <?php self::copy_box('Résultat Facebook', Parcs_HT_Advent::social_result($campaign,$content,$result,'facebook','fr')); ?>
            <?php self::copy_box('Résultat Instagram', Parcs_HT_Advent::social_result($campaign,$content,$result,'instagram','fr')); ?>
            <?php self::copy_box('Story résultat', Parcs_HT_Advent::story_result($campaign,$content,$result,'fr')); ?>
        </div><p><button class="button" type="submit">Enregistrer sans publier</button> <button class="button button-primary" type="submit" name="publish_result" value="1">Publier le résultat</button></p></section></form>
        <?php
    }

    private static function import_view($campaign) {
        $token = isset($_GET['import_token']) ? sanitize_key(wp_unslash($_GET['import_token'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection du rapport temporaire en lecture seule.
        $report = $token !== '' ? get_transient(self::import_transient_key($token)) : false;
        ?>
        <section class="htp-advent-card"><h2>Import / export — prototype CSV</h2><p>Le CSV utilise exactement les noms de champs du schéma 3. L’analyse n’écrit rien : elle produit d’abord un rapport créations / modifications / inchangés / avertissements / erreurs.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-advent-inline-form"><input type="hidden" name="action" value="parcs_ht_advent_csv_template"><input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['campagne_id']); ?>"><?php wp_nonce_field('parcs_ht_advent_csv_template_' . $campaign['campagne_id']); ?><button class="button" type="submit">Télécharger le modèle CSV</button></form>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_advent_import_csv"><input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['campagne_id']); ?>"><?php wp_nonce_field('parcs_ht_advent_import_csv_' . $campaign['campagne_id']); ?><p><input type="file" name="advent_csv" accept=".csv,text/csv" required> <button class="button button-primary" type="submit">Analyser sans écrire</button></p></form>
            <details><summary>Format attendu / aide IA</summary><p>Le fichier doit utiliser <code>schema_version = 3</code>, le <code>parc_code</code> de cette installation et le même <code>campagne_id</code>. Les identifiants stables pilotent les mises à jour. Un visuel vide conserve le visuel déjà choisi dans WordPress.</p><textarea readonly rows="5">Crée un CSV UTF-8 séparé par des points-virgules conforme au référentiel Calendrier de l’Avent schema_version 3. Utilise exactement les colonnes du modèle officiel et conserve les identifiants stables campagne_id, partenaire_id, contenu_id et resultat_id.</textarea></details>
        <?php if (is_array($report)) : $counts = $report['counts']; ?>
            <div class="htp-advent-import-report"><h3>Aperçu avant validation</h3><p><strong><?php echo esc_html((string)$counts['create']); ?></strong> créations · <strong><?php echo esc_html((string)$counts['modify']); ?></strong> modifications · <strong><?php echo esc_html((string)$counts['same']); ?></strong> inchangés</p>
                <?php if ($report['warnings']) : ?><h4>Avertissements</h4><ul><?php foreach ($report['warnings'] as $message) echo '<li>' . esc_html($message) . '</li>'; ?></ul><?php endif; ?>
                <?php if ($report['errors']) : ?><h4>Erreurs</h4><ul><?php foreach ($report['errors'] as $message) echo '<li>' . esc_html($message) . '</li>'; ?></ul><?php else : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_advent_apply_import"><input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['campagne_id']); ?>"><input type="hidden" name="import_token" value="<?php echo esc_attr($token); ?>"><?php wp_nonce_field('parcs_ht_advent_apply_import_' . $campaign['campagne_id']); ?><button class="button button-primary" type="submit">Appliquer la mise à jour intelligente</button></form>
                <?php endif; ?>
            </div>
        <?php endif; ?></section>
        <?php
    }

    public static function create_campaign() {
        self::require_admin();
        check_admin_referer('parcs_ht_advent_create_campaign');
        $park = Parcs_HT_Advent::installation_park_code();
        if ($park === '') wp_die('Installation non configurée.');
        $id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        if ($id === '' || !preg_match('/^20\d{2}$/', $year)) wp_die('Campagne invalide.');
        $store = Parcs_HT_Advent::store();
        if (isset($store['campaigns'][$id])) wp_die('Cet identifiant de campagne existe déjà.');
        $store['campaigns'][$id] = Parcs_HT_Advent::default_campaign($id, $park, $year, $name);
        Parcs_HT_Advent::save_store($store);
        self::redirect($id, 'campaign', array('advent_notice'=>'created'));
    }

    public static function save_campaign() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_save_campaign_' . $campaign_id);
        $current = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$current) wp_die('Campagne introuvable.');
        $raw = isset($_POST['campaign']) && is_array($_POST['campaign']) ? wp_unslash($_POST['campaign']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé par sanitize_campaign().
        $raw['campagne_id'] = $campaign_id;
        $raw['parc_code'] = Parcs_HT_Advent::installation_park_code();
        $clean = Parcs_HT_Advent::sanitize_campaign($raw, $current);
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id] = $clean;
        Parcs_HT_Advent::save_store($store);
        $view = isset($_POST['return_view']) ? sanitize_key(wp_unslash($_POST['return_view'])) : 'campaign';
        self::redirect($campaign_id, $view, array('advent_notice'=>'saved'));
    }

    public static function save_content() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_save_content_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
        $raw = isset($_POST['content']) && is_array($_POST['content']) ? wp_unslash($_POST['content']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé par sanitize_content().
        $id = sanitize_key((string)($raw['contenu_id'] ?? ''));
        if ($id === '') wp_die('Identifiant de contenu obligatoire.');
        $existing = isset($campaign['contents'][$id]) ? $campaign['contents'][$id] : Parcs_HT_Advent::default_content();
        $clean = Parcs_HT_Advent::sanitize_content($raw, $existing);
        if ($clean['type_contenu'] === 'JOUR') {
            if ($clean['jour_numero'] < 1 || $clean['jour_numero'] > 24) wp_die('Numéro de jour invalide.');
            foreach ((array)$campaign['contents'] as $other_id=>$other) {
                if ($other_id !== $id && is_array($other) && (string)($other['type_contenu'] ?? '') === 'JOUR' && (int)($other['jour_numero'] ?? 0) === (int)$clean['jour_numero']) wp_die('Ce numéro de jour existe déjà.');
            }
        }
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id]['contents'][$id] = $clean;
        if ($clean['type_contenu'] === 'JOUR') {
            $result_found = false;
            foreach ((array)$store['campaigns'][$campaign_id]['results'] as $result) {
                if (is_array($result) && (string)($result['contenu_id'] ?? '') === $id) $result_found = true;
            }
            if (!$result_found) {
                $result = Parcs_HT_Advent::default_result($id, $clean['jour_numero']);
                $store['campaigns'][$campaign_id]['results'][$result['resultat_id']] = $result;
            }
        }
        Parcs_HT_Advent::save_store($store);
        $view = isset($_POST['return_view']) ? sanitize_key(wp_unslash($_POST['return_view'])) : 'calendar';
        $args = $clean['type_contenu'] === 'JOUR' ? array('day'=>$id) : array('teaser'=>$id);
        $args['advent_notice'] = 'content_saved';
        self::redirect($campaign_id, $view, $args);
    }

    public static function save_partner() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_save_partner_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
        $raw = isset($_POST['partner']) && is_array($_POST['partner']) ? wp_unslash($_POST['partner']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé par sanitize_partner().
        $id = sanitize_key((string)($raw['partenaire_id'] ?? ''));
        if ($id === '') wp_die('Identifiant partenaire obligatoire.');
        $existing = isset($campaign['partners'][$id]) ? $campaign['partners'][$id] : Parcs_HT_Advent::default_partner();
        $clean = Parcs_HT_Advent::sanitize_partner($raw, $existing);
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id]['partners'][$id] = $clean;
        Parcs_HT_Advent::save_store($store);
        self::redirect($campaign_id, 'partners', array('partner'=>$id,'advent_notice'=>'partner_saved'));
    }

    public static function save_result() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_save_result_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
        $raw = isset($_POST['result']) && is_array($_POST['result']) ? wp_unslash($_POST['result']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé par sanitize_result().
        $id = sanitize_key((string)($raw['resultat_id'] ?? ''));
        if ($id === '') wp_die('Résultat invalide.');
        $existing = isset($campaign['results'][$id]) ? $campaign['results'][$id] : Parcs_HT_Advent::default_result();
        $clean = Parcs_HT_Advent::sanitize_result($raw, $existing);
        $publish_value = isset($_POST['publish_result']) ? sanitize_text_field(wp_unslash($_POST['publish_result'])) : '';
        $published = $publish_value === '1';
        if ($published) $clean['statut_resultat'] = 'publie';
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id]['results'][$id] = $clean;
        Parcs_HT_Advent::save_store($store);
        self::redirect($campaign_id, 'results', array('result'=>$id,'advent_notice'=>$published ? 'result_published' : 'result_saved'));
    }

    private static function csv_headers() {
        return array_values(array_unique(array_merge(Parcs_HT_Advent::campaign_field_names(),Parcs_HT_Advent::partner_field_names(),Parcs_HT_Advent::content_field_names(),Parcs_HT_Advent::result_field_names(),Parcs_HT_Advent::translation_field_names())));
    }

    public static function csv_template() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_csv_template_' . $campaign_id);
        if (!Parcs_HT_Advent::campaign($campaign_id, true)) wp_die('Campagne introuvable.');
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="calendrier-avent-schema-3.csv"');
        $output = fopen('php://output', 'w');
        if ($output === false) wp_die('Impossible de générer le modèle.');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, self::csv_headers(), ';');
        fclose($output);
        exit;
    }

    private static function import_transient_key($token) {
        return self::IMPORT_TRANSIENT_PREFIX . get_current_user_id() . '_' . sanitize_key($token);
    }

    public static function import_csv() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_import_csv_' . $campaign_id);
        $current = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$current) wp_die('Campagne introuvable.');
        $file = isset($_FILES['advent_csv']) && is_array($_FILES['advent_csv']) ? $_FILES['advent_csv'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chaque métadonnée utile est nettoyée séparément ci-dessous.
        $name = isset($file['name']) ? sanitize_file_name(wp_unslash((string)$file['name'])) : '';
        $size = isset($file['size']) ? absint(wp_unslash($file['size'])) : 0;
        $error = isset($file['error']) ? absint(wp_unslash($file['error'])) : UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) self::redirect($campaign_id, 'import', array('advent_error'=>'Le téléversement du CSV a échoué.'));
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') self::redirect($campaign_id, 'import', array('advent_error'=>'Le prototype attend un fichier .csv.'));
        if ($size > 5 * 1024 * 1024) self::redirect($campaign_id, 'import', array('advent_error'=>'Le fichier dépasse 5 Mo.'));
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chemin temporaire système contrôlé par is_uploaded_file().
        if ($tmp === '' || !is_uploaded_file($tmp)) self::redirect($campaign_id, 'import', array('advent_error'=>'Fichier temporaire invalide.'));
        $rows = self::read_csv($tmp);
        if (is_wp_error($rows)) self::redirect($campaign_id, 'import', array('advent_error'=>$rows->get_error_message()));
        $report = self::analyze_import($rows, $current);
        $token = sanitize_key(wp_generate_password(16, false, false));
        set_transient(self::import_transient_key($token), $report, 30 * MINUTE_IN_SECONDS);
        self::redirect($campaign_id, 'import', array('import_token'=>$token));
    }

    private static function read_csv($path) {
        $handle = fopen($path, 'r');
        if ($handle === false) return new WP_Error('advent_csv_open','Impossible de lire le CSV.');
        $header = fgetcsv($handle, 0, ';');
        if (!is_array($header) || !$header) { fclose($handle); return new WP_Error('advent_csv_header','En-tête CSV introuvable.'); }
        if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
        $header = array_map('sanitize_key', $header);
        $rows = array();
        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            if (!array_filter($line, 'strlen')) continue;
            $line = array_pad($line, count($header), '');
            $row = array();
            foreach ($header as $index=>$key) if ($key !== '') $row[$key] = isset($line[$index]) ? (string)$line[$index] : '';
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private static function nonempty_subset($row, $fields) {
        $out = array();
        foreach ($fields as $field) if (array_key_exists($field, $row) && $row[$field] !== '') $out[$field] = $row[$field];
        return $out;
    }

    private static function count_change(&$counts, $before, $after, $exists) {
        if (!$exists) $counts['create']++;
        elseif (wp_json_encode($before) === wp_json_encode($after)) $counts['same']++;
        else $counts['modify']++;
    }

    private static function analyze_import($rows, $current) {
        $candidate = $current;
        $counts = array('create'=>0,'modify'=>0,'same'=>0);
        $warnings = array();
        $errors = array();
        $park = Parcs_HT_Advent::installation_park_code();
        foreach ((array)$rows as $index=>$row) {
            $line_no = $index + 2;
            if (!is_array($row)) continue;
            if (isset($row['schema_version']) && $row['schema_version'] !== '' && (int)$row['schema_version'] !== Parcs_HT_Advent::SCHEMA_VERSION) $errors[] = 'Ligne ' . $line_no . ' : schema_version incompatible.';
            if (isset($row['parc_code']) && $row['parc_code'] !== '' && sanitize_key($row['parc_code']) !== $park) $errors[] = 'Ligne ' . $line_no . ' : parc_code différent de cette installation.';
            if (isset($row['campagne_id']) && $row['campagne_id'] !== '' && sanitize_key($row['campagne_id']) !== $current['campagne_id']) $errors[] = 'Ligne ' . $line_no . ' : campagne_id différent de la campagne sélectionnée.';

            $campaign_values = self::nonempty_subset($row, Parcs_HT_Advent::campaign_field_names());
            unset($campaign_values['schema_version'],$campaign_values['campagne_id'],$campaign_values['parc_code']);
            if ($campaign_values) {
                $before = $candidate;
                $payload = array_merge($candidate, $campaign_values, array('campagne_id'=>$current['campagne_id'],'parc_code'=>$park));
                $updated = Parcs_HT_Advent::sanitize_campaign($payload, $candidate);
                $updated['partners'] = $candidate['partners']; $updated['contents'] = $candidate['contents']; $updated['results'] = $candidate['results']; $updated['translations'] = $candidate['translations'];
                $candidate = $updated;
                self::count_change($counts, self::nonempty_subset($before,array_keys($campaign_values)), self::nonempty_subset($candidate,array_keys($campaign_values)), true);
            }

            $partner_id = isset($row['partenaire_id']) ? sanitize_key($row['partenaire_id']) : '';
            if ($partner_id !== '') {
                $exists = isset($candidate['partners'][$partner_id]);
                $before = $exists ? $candidate['partners'][$partner_id] : Parcs_HT_Advent::default_partner();
                $payload = self::nonempty_subset($row, Parcs_HT_Advent::partner_field_names());
                $payload['partenaire_id'] = $partner_id;
                $after = Parcs_HT_Advent::sanitize_partner($payload, $before);
                if ((!isset($row['logo_url']) || $row['logo_url'] === '') && !empty($before['logo_url'])) { $after['logo_url']=$before['logo_url']; $after['logo_source']=$before['logo_source']; }
                $candidate['partners'][$partner_id] = $after;
                self::count_change($counts,$before,$after,$exists);
            }

            $content_id = isset($row['contenu_id']) ? sanitize_key($row['contenu_id']) : '';
            if ($content_id !== '') {
                $exists = isset($candidate['contents'][$content_id]);
                $before = $exists ? $candidate['contents'][$content_id] : Parcs_HT_Advent::default_content();
                $payload = self::nonempty_subset($row, Parcs_HT_Advent::content_field_names());
                $payload['contenu_id'] = $content_id;
                $after = Parcs_HT_Advent::sanitize_content($payload, $before);
                if ((!isset($row['visuel_url']) || $row['visuel_url'] === '') && !empty($before['visuel_url'])) { $after['visuel_url']=$before['visuel_url']; $after['visuel_source']=$before['visuel_source']; }
                $candidate['contents'][$content_id] = $after;
                self::count_change($counts,$before,$after,$exists);
            }

            $result_id = isset($row['resultat_id']) ? sanitize_key($row['resultat_id']) : '';
            if ($result_id !== '') {
                $exists = isset($candidate['results'][$result_id]);
                $before = $exists ? $candidate['results'][$result_id] : Parcs_HT_Advent::default_result();
                $payload = self::nonempty_subset($row, Parcs_HT_Advent::result_field_names());
                $payload['resultat_id'] = $result_id;
                $after = Parcs_HT_Advent::sanitize_result($payload,$before);
                $candidate['results'][$result_id] = $after;
                self::count_change($counts,$before,$after,$exists);
            }

            if (!empty($row['reference_id']) && !empty($row['champ']) && !empty($row['langue'])) {
                $translation = Parcs_HT_Advent::sanitize_translation($row);
                if ($translation['langue'] !== '') {
                    $key = $translation['reference_id'] . '|' . $translation['champ'] . '|' . $translation['langue'];
                    $map = array(); foreach ($candidate['translations'] as $i=>$item) if (is_array($item)) $map[$item['reference_id'].'|'.$item['champ'].'|'.$item['langue']]=$i;
                    $exists = isset($map[$key]);
                    $before = $exists ? $candidate['translations'][$map[$key]] : array();
                    if ($exists) $candidate['translations'][$map[$key]] = $translation; else $candidate['translations'][] = $translation;
                    self::count_change($counts,$before,$translation,$exists);
                }
            }
        }

        $seen = array();
        foreach ((array)$candidate['contents'] as $content) {
            if (!is_array($content) || (string)($content['type_contenu'] ?? '') !== 'JOUR') continue;
            $day = (int)($content['jour_numero'] ?? 0);
            if ($day < 1 || $day > 24) $errors[] = 'Une journée possède un numéro hors de 1 à 24.';
            if (isset($seen[$day])) $errors[] = 'Le jour ' . $day . ' est présent plusieurs fois.';
            $seen[$day] = true;
            $partner_id = sanitize_key((string)($content['partenaire_id'] ?? ''));
            if ($partner_id !== '' && !isset($candidate['partners'][$partner_id])) $warnings[] = 'Le partenaire « ' . $partner_id . ' » référencé par un jour n’existe pas encore.';
        }
        if (count($seen) !== 24) $errors[] = 'La campagne doit contenir exactement les jours 1 à 24.';
        foreach ((array)$candidate['results'] as $result) {
            if (is_array($result) && !empty($result['contenu_id']) && !isset($candidate['contents'][$result['contenu_id']])) $errors[] = 'Un résultat référence un contenu inexistant.';
        }
        return array('candidate'=>$candidate,'counts'=>$counts,'warnings'=>array_values(array_unique($warnings)),'errors'=>array_values(array_unique($errors)),'original_hash'=>hash('sha256',wp_json_encode($current)));
    }

    public static function apply_import() {
        self::require_admin();
        $campaign_id = self::campaign_id_from_request('post');
        check_admin_referer('parcs_ht_advent_apply_import_' . $campaign_id);
        $token = isset($_POST['import_token']) ? sanitize_key(wp_unslash($_POST['import_token'])) : '';
        $key = self::import_transient_key($token);
        $report = get_transient($key);
        if (!is_array($report) || !empty($report['errors']) || !isset($report['candidate'])) wp_die('Rapport d’import invalide ou expiré.');
        $current = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$current || !hash_equals((string)$report['original_hash'], hash('sha256',wp_json_encode($current)))) self::redirect($campaign_id,'import',array('advent_notice'=>'import_stale'));
        $candidate = $report['candidate'];
        if (!is_array($candidate) || (string)$candidate['campagne_id'] !== $campaign_id || (string)$candidate['parc_code'] !== Parcs_HT_Advent::installation_park_code()) wp_die('Campagne importée invalide.');
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id] = $candidate;
        Parcs_HT_Advent::save_store($store);
        delete_transient($key);
        self::redirect($campaign_id,'import',array('advent_notice'=>'imported'));
    }
}
