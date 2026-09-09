<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Administration du prototype Calendrier de l’Avent. */
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

    public static function assets() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Lecture du slug de page uniquement pour limiter les assets admin.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== self::PAGE) return;
        wp_enqueue_media();
        wp_enqueue_style('parcs-ht-advent-admin', PARCS_HT_URL . 'assets/advent-admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-advent-admin', PARCS_HT_URL . 'assets/advent-admin.js', array('jquery'), PARCS_HT_VERSION, true);
    }

    private static function require_admin() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
    }

    private static function current_campaign_id() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection de campagne en lecture seule.
        return isset($_GET['campaign']) ? sanitize_key(wp_unslash($_GET['campaign'])) : '';
    }

    private static function current_view() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’onglet en lecture seule.
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'campaign';
        return in_array($view, array('campaign','teasers','calendar','grand','partners','results','import'), true) ? $view : 'campaign';
    }

    private static function admin_url($campaign_id = '', $view = 'campaign', $extra = array()) {
        $args = array('page'=>self::PAGE, 'view'=>$view);
        if ($campaign_id !== '') $args['campaign'] = $campaign_id;
        if (is_array($extra)) $args = array_merge($args, $extra);
        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function redirect($campaign_id = '', $view = 'campaign', $extra = array()) {
        wp_safe_redirect(self::admin_url($campaign_id, $view, $extra));
        exit;
    }

    private static function campaigns_for_installation() {
        $park = Parcs_HT_Advent::installation_park_code();
        $store = Parcs_HT_Advent::store();
        $campaigns = array();
        foreach ($store['campaigns'] as $campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park) continue;
            $campaigns[$campaign['campagne_id']] = $campaign;
        }
        uasort($campaigns, static function ($a, $b) {
            $year_compare = ((int)($b['annee'] ?? 0)) <=> ((int)($a['annee'] ?? 0));
            return $year_compare !== 0 ? $year_compare : strcmp((string)$a['campagne_id'], (string)$b['campagne_id']);
        });
        return $campaigns;
    }

    public static function page() {
        self::require_admin();
        $park = Parcs_HT_Advent::installation_park_code();
        $campaigns = self::campaigns_for_installation();
        $campaign_id = self::current_campaign_id();
        if ($campaign_id === '' && $campaigns) $campaign_id = (string)array_key_first($campaigns);
        $campaign = $campaign_id !== '' && isset($campaigns[$campaign_id]) ? $campaigns[$campaign_id] : null;
        $view = self::current_view();
        ?>
        <div class="wrap htp-advent-admin">
            <h1>Calendrier de l’Avent</h1>
            <p class="description">Prototype du module Avent — schéma d’import <?php echo esc_html((string)Parcs_HT_Advent::SCHEMA_VERSION); ?>. Les campagnes restent propres à cette installation et aucune donnée d’un autre parc n’est chargée automatiquement.</p>

            <?php self::notices(); ?>

            <?php if ($park === '') : ?>
                <div class="notice notice-error"><p>Le type de parc de cette installation n’est pas configuré. Le module Avent ne peut pas créer ni importer de campagne tant que ce réglage n’est pas disponible.</p></div>
            <?php else : ?>
                <section class="htp-advent-card htp-advent-campaign-switcher">
                    <div>
                        <strong>Installation :</strong> <code><?php echo esc_html($park); ?></code>
                        <?php if ($campaigns) : ?>
                            <label for="htp-advent-campaign-select">Campagne</label>
                            <select id="htp-advent-campaign-select" data-advent-campaign-select data-base-url="<?php echo esc_url(self::admin_url('', $view)); ?>">
                                <?php foreach ($campaigns as $id => $row) : ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected($campaign_id, $id); ?>><?php echo esc_html(($row['nom_campagne'] ?: $id) . ' · ' . ($row['annee'] ?: 'sans année') . ' · ' . $row['statut_campagne']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <details>
                        <summary>Créer une campagne</summary>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-advent-inline-form">
                            <input type="hidden" name="action" value="parcs_ht_advent_create_campaign">
                            <?php wp_nonce_field('parcs_ht_advent_create_campaign'); ?>
                            <label><span>ID stable</span><input type="text" name="campaign_id" pattern="[a-z0-9_-]+" required placeholder="avent-annee"></label>
                            <label><span>Année</span><input type="number" name="year" min="2020" max="2100" required></label>
                            <label><span>Nom</span><input type="text" name="name" required></label>
                            <button type="submit" class="button button-primary">Créer la campagne brouillon</button>
                        </form>
                    </details>
                </section>

                <?php if ($campaign) : ?>
                    <?php self::navigation($campaign_id, $view); ?>
                    <?php
                    if ($view === 'campaign') self::campaign_view($campaign);
                    elseif ($view === 'teasers') self::teasers_view($campaign);
                    elseif ($view === 'calendar') self::calendar_view($campaign);
                    elseif ($view === 'grand') self::grand_view($campaign);
                    elseif ($view === 'partners') self::partners_view($campaign);
                    elseif ($view === 'results') self::results_view($campaign);
                    elseif ($view === 'import') self::import_view($campaign);
                    ?>
                <?php elseif (!$campaigns) : ?>
                    <section class="htp-advent-card"><p>Aucune campagne n’existe encore sur cette installation. Créez d’abord une campagne brouillon ou utilisez l’import CSV du prototype après avoir créé sa campagne de destination.</p></section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function notices() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Messages de retour en lecture seule.
        $notice = isset($_GET['advent_notice']) ? sanitize_key(wp_unslash($_GET['advent_notice'])) : '';
        $messages = array(
            'created'=>'La campagne a été créée.',
            'saved'=>'Les réglages de la campagne ont été enregistrés.',
            'content_saved'=>'Le contenu a été enregistré.',
            'partner_saved'=>'Le partenaire a été enregistré.',
            'result_saved'=>'Le résultat a été enregistré sans être publié automatiquement.',
            'result_published'=>'Le résultat est marqué comme publié. Le serveur continuera à le masquer jusqu’à sa date/heure de révélation.',
            'imported'=>'L’import a été appliqué.',
            'import_stale'=>'La campagne a changé depuis l’analyse. Relancez l’analyse du fichier avant d’importer.',
        );
        if (isset($messages[$notice])) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$notice]) . '</p></div>';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Message d’erreur de retour en lecture seule.
        $error = isset($_GET['advent_error']) ? sanitize_text_field(wp_unslash($_GET['advent_error'])) : '';
        if ($error !== '') echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    }

    private static function navigation($campaign_id, $active) {
        $items = array(
            'campaign'=>'Campagne',
            'teasers'=>'Teasings sociaux',
            'calendar'=>'Calendrier',
            'grand'=>'Grand jeu',
            'partners'=>'Partenaires',
            'results'=>'Résultats',
            'import'=>'Import / export',
        );
        echo '<nav class="nav-tab-wrapper htp-advent-tabs" aria-label="Sections du Calendrier de l’Avent">';
        foreach ($items as $view => $label) {
            $class = 'nav-tab' . ($active === $view ? ' nav-tab-active' : '');
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url(self::admin_url($campaign_id, $view)) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }

    private static function form_open($action, $campaign_id, $view) {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
            <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_id); ?>">
            <input type="hidden" name="return_view" value="<?php echo esc_attr($view); ?>">
            <?php wp_nonce_field($action . '_' . $campaign_id); ?>
        <?php
    }

    private static function input($name, $value, $label, $type = 'text', $attrs = '') {
        ?><label class="htp-advent-field"><span><?php echo esc_html($label); ?></span><input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string)$value); ?>" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributs statiques internes uniquement. ?>></label><?php
    }

    private static function textarea($name, $value, $label, $rows = 4) {
        ?><label class="htp-advent-field htp-advent-field-wide"><span><?php echo esc_html($label); ?></span><textarea name="<?php echo esc_attr($name); ?>" rows="<?php echo esc_attr((string)$rows); ?>"><?php echo esc_textarea((string)$value); ?></textarea></label><?php
    }

    private static function select($name, $value, $label, $options) {
        ?><label class="htp-advent-field"><span><?php echo esc_html($label); ?></span><select name="<?php echo esc_attr($name); ?>"><?php foreach ($options as $option => $text) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected((string)$value, (string)$option); ?>><?php echo esc_html($text); ?></option><?php endforeach; ?></select></label><?php
    }

    private static function media_field($base, $source, $url, $label, $kind = 'image') {
        ?>
        <div class="htp-advent-field htp-advent-field-wide" data-advent-media-field>
            <span><?php echo esc_html($label); ?></span>
            <input type="hidden" name="<?php echo esc_attr($base . '[source]'); ?>" value="<?php echo esc_attr((string)$source); ?>" data-advent-media-source>
            <div class="htp-advent-media-row">
                <input type="url" name="<?php echo esc_attr($base . '[url]'); ?>" value="<?php echo esc_attr((string)$url); ?>" data-advent-media-url>
                <button type="button" class="button" data-advent-media-select data-media-kind="<?php echo esc_attr($kind); ?>">Choisir dans WordPress</button>
                <button type="button" class="button" data-advent-media-clear>Retirer</button>
            </div>
            <div class="htp-advent-media-preview <?php echo $url !== '' ? 'has-media' : 'is-empty'; ?>" data-advent-media-preview>
                <?php if ($url !== '') : ?><img src="<?php echo esc_url($url); ?>" alt=""><?php else : ?><span>Visuel 4:5 — à ajouter</span><?php endif; ?>
            </div>
        </div>
        <?php
    }

    private static function campaign_view($campaign) {
        self::form_open('parcs_ht_advent_save_campaign', $campaign['campagne_id'], 'campaign');
        ?>
        <section class="htp-advent-card">
            <h2>Campagne</h2>
            <div class="htp-advent-grid-fields">
                <?php self::input('campaign[nom_campagne]', $campaign['nom_campagne'], 'Nom interne'); ?>
                <?php self::input('campaign[annee]', $campaign['annee'], 'Année', 'number', 'min="2020" max="2100"'); ?>
                <?php self::select('campaign[statut_campagne]', $campaign['statut_campagne'], 'Statut', array('brouillon'=>'Brouillon','active'=>'Active','archivee'=>'Archivée')); ?>
                <?php self::input('campaign[timezone]', $campaign['timezone'], 'Fuseau horaire'); ?>
                <?php self::input('campaign[date_ouverture_calendrier]', $campaign['date_ouverture_calendrier'], 'Ouverture du calendrier', 'date'); ?>
                <?php self::input('campaign[date_fin_calendrier]', $campaign['date_fin_calendrier'], 'Fin du calendrier', 'date'); ?>
                <?php self::input('campaign[heure_ouverture_globale]', $campaign['heure_ouverture_globale'], 'Heure d’ouverture globale', 'time'); ?>
                <?php self::input('campaign[page_calendrier_url]', $campaign['page_calendrier_url'], 'URL de la page calendrier', 'url'); ?>
                <?php self::input('campaign[page_reglement_url]', $campaign['page_reglement_url'], 'URL de la page règlement', 'url'); ?>
                <?php self::select('campaign[fallback_traduction]', $campaign['fallback_traduction'], 'Traduction manquante', array('fr'=>'Fallback français','masquer'=>'Masquer le texte','strict'=>'Strict / pas de fallback')); ?>
            </div>
        </section>

        <section class="htp-advent-card">
            <h2>Bloc public</h2>
            <div class="htp-advent-grid-fields">
                <?php self::input('campaign[titre_bloc_calendrier_fr]', $campaign['titre_bloc_calendrier_fr'], 'Titre interne du bloc'); ?>
                <?php self::textarea('campaign[texte_intro_calendrier_fr]', $campaign['texte_intro_calendrier_fr'], 'Introduction'); ?>
                <?php self::input('campaign[libelle_comment_participer_fr]', $campaign['libelle_comment_participer_fr'], 'Libellé « Comment participer ? »'); ?>
                <?php self::textarea('campaign[texte_comment_participer_fr]', $campaign['texte_comment_participer_fr'], 'Explication courte « Comment participer ? »'); ?>
                <?php self::input('campaign[libelle_reglement_complet_fr]', $campaign['libelle_reglement_complet_fr'], 'Libellé du bouton règlement'); ?>
                <?php self::textarea('campaign[reglement_complet_fr]', $campaign['reglement_complet_fr'], 'Règlement complet', 10); ?>
                <?php self::textarea('campaign[texte_tirage_non_effectue_fr]', $campaign['texte_tirage_non_effectue_fr'], 'Texte si le résultat est dû mais non publié'); ?>
                <?php self::media_field('campaign[teasing_site_visuel]', $campaign['teasing_site_visuel_source'], $campaign['teasing_site_visuel_url'], 'Visuel teasing public unique'); ?>
                <?php self::input('campaign[teasing_site_visuel_alt_fr]', $campaign['teasing_site_visuel_alt_fr'], 'Texte alternatif du teasing'); ?>
            </div>
        </section>

        <section class="htp-advent-card">
            <h2>Textes sociaux par défaut</h2>
            <div class="htp-advent-grid-fields">
                <?php self::textarea('campaign[reglement_quotidien_fr]', $campaign['reglement_quotidien_fr'], 'Règle quotidienne à insérer dans les posts'); ?>
                <?php self::textarea('campaign[texte_rappel_grand_jeu_fr]', $campaign['texte_rappel_grand_jeu_fr'], 'Rappel automatique du mot mystère'); ?>
                <?php self::textarea('campaign[texte_lien_calendrier_social_fr]', $campaign['texte_lien_calendrier_social_fr'], 'Texte avant le lien vers le calendrier'); ?>
                <?php self::textarea('campaign[hashtags_defaut]', $campaign['hashtags_defaut'], 'Hashtags par défaut'); ?>
                <?php self::input('campaign[instagram_parc]', $campaign['instagram_parc'], 'Compte Instagram du parc'); ?>
                <?php self::input('campaign[facebook_slug]', $campaign['facebook_slug'], 'Identifiant Facebook'); ?>
                <?php self::input('campaign[facebook_url_override]', $campaign['facebook_url_override'], 'URL Facebook personnalisée', 'url'); ?>
            </div>
        </section>

        <section class="htp-advent-card">
            <details>
                <summary>Microcopies publiques</summary>
                <p class="description">Ces libellés ne font pas partie du fichier d’import principal, mais restent modifiables dans WordPress.</p>
                <div class="htp-advent-grid-fields">
                    <?php foreach ((array)$campaign['microcopies'] as $key => $translations) self::input('campaign[microcopies][' . $key . '][fr]', $translations['fr'] ?? '', str_replace('_', ' ', ucfirst($key))); ?>
                </div>
            </details>
        </section>
        <p><button type="submit" class="button button-primary">Enregistrer la campagne</button></p>
        </form>
        <?php
    }

    private static function grand_view($campaign) {
        self::form_open('parcs_ht_advent_save_campaign', $campaign['campagne_id'], 'grand');
        ?>
        <section class="htp-advent-card">
            <h2>Grand jeu final</h2>
            <p class="description">Le mot et le shortcode du formulaire restent côté serveur. Le shortcode final n’est exécuté qu’après validation correcte du mot.</p>
            <div class="htp-advent-grid-fields">
                <?php self::input('campaign[grand_jeu_date_ouverture]', $campaign['grand_jeu_date_ouverture'], 'Date d’ouverture', 'date'); ?>
                <?php self::input('campaign[grand_jeu_heure_ouverture]', $campaign['grand_jeu_heure_ouverture'], 'Heure d’ouverture', 'time'); ?>
                <?php self::input('campaign[grand_jeu_date_fermeture]', $campaign['grand_jeu_date_fermeture'], 'Date de fermeture', 'date'); ?>
                <?php self::input('campaign[grand_jeu_heure_fermeture]', $campaign['grand_jeu_heure_fermeture'], 'Heure de fermeture', 'time'); ?>
                <?php self::input('campaign[grand_jeu_date_revelation]', $campaign['grand_jeu_date_revelation'], 'Date de révélation finale du mot', 'date'); ?>
                <?php self::input('campaign[mot_mystere]', $campaign['mot_mystere'], 'Mot mystère'); ?>
                <?php self::input('campaign[grand_jeu_lot_fr]', $campaign['grand_jeu_lot_fr'], 'Grand lot'); ?>
                <?php self::input('campaign[grand_jeu_pictogramme_url]', $campaign['grand_jeu_pictogramme_url'], 'URL du pictogramme', 'url'); ?>
                <?php self::textarea('campaign[grand_jeu_texte_saisie_fr]', $campaign['grand_jeu_texte_saisie_fr'], 'Texte de saisie du mot'); ?>
                <?php self::textarea('campaign[grand_jeu_texte_erreur_fr]', $campaign['grand_jeu_texte_erreur_fr'], 'Texte si mot incorrect'); ?>
                <?php self::textarea('campaign[grand_jeu_texte_succes_fr]', $campaign['grand_jeu_texte_succes_fr'], 'Texte après mot correct'); ?>
                <?php self::textarea('campaign[grand_jeu_formulaire_shortcode]', $campaign['grand_jeu_formulaire_shortcode'], 'Shortcode du formulaire final', 3); ?>
            </div>
        </section>
        <p><button type="submit" class="button button-primary">Enregistrer le grand jeu</button></p>
        </form>
        <?php
    }

    private static function calendar_view($campaign) {
        $days = self::sorted_days($campaign);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’un jour en lecture seule.
        $selected_id = isset($_GET['day']) ? sanitize_key(wp_unslash($_GET['day'])) : '';
        if ($selected_id === '' && $days) $selected_id = $days[0]['contenu_id'];
        $selected = isset($campaign['contents'][$selected_id]) ? $campaign['contents'][$selected_id] : null;
        ?>
        <section class="htp-advent-card">
            <h2>Calendrier — 24 jours</h2>
            <div class="htp-advent-day-grid-admin">
                <?php foreach ($days as $day) :
                    $partner = isset($campaign['partners'][$day['partenaire_id']]) ? $campaign['partners'][$day['partenaire_id']]['nom'] : '';
                    $classes = 'htp-advent-day-admin' . ($day['contenu_id'] === $selected_id ? ' is-selected' : '');
                    ?>
                    <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url(self::admin_url($campaign['campagne_id'], 'calendar', array('day'=>$day['contenu_id']))); ?>">
                        <strong><?php echo esc_html((string)$day['jour_numero']); ?></strong>
                        <span><?php echo esc_html($day['date_publication'] ?: 'date à définir'); ?></span>
                        <small><?php echo esc_html($day['statut'] ?: 'brouillon'); ?></small>
                        <small><?php echo $day['visuel_url'] ? 'Visuel ✓' : 'Visuel —'; ?></small>
                        <small><?php echo $day['indice_actif'] === 'oui' ? 'Indice ✓' : 'Indice —'; ?></small>
                        <?php if ($partner !== '') : ?><small><?php echo esc_html($partner); ?></small><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php if ($selected) self::content_editor($campaign, $selected, 'calendar'); ?>
        <?php
    }

    private static function teasers_view($campaign) {
        $teasers = array_values(array_filter((array)$campaign['contents'], static function ($content) { return is_array($content) && (string)($content['type_contenu'] ?? '') === 'TEASING'; }));
        usort($teasers, static function ($a, $b) { return strcmp((string)($a['date_publication'] ?? ''), (string)($b['date_publication'] ?? '')); });
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’un teaser en lecture seule.
        $selected_id = isset($_GET['teaser']) ? sanitize_key(wp_unslash($_GET['teaser'])) : '';
        $selected = $selected_id !== '' && isset($campaign['contents'][$selected_id]) ? $campaign['contents'][$selected_id] : null;
        ?>
        <section class="htp-advent-card">
            <h2>Teasings sociaux</h2>
            <p class="description">Les teasings sociaux sont séparés du visuel teasing public unique de la page calendrier.</p>
            <?php if ($teasers) : ?><div class="htp-advent-list"><?php foreach ($teasers as $teaser) : ?><a href="<?php echo esc_url(self::admin_url($campaign['campagne_id'], 'teasers', array('teaser'=>$teaser['contenu_id']))); ?>"><strong><?php echo esc_html($teaser['titre_fr'] ?: $teaser['contenu_id']); ?></strong><span><?php echo esc_html(trim($teaser['date_publication'] . ' ' . $teaser['heure_publication'])); ?></span></a><?php endforeach; ?></div><?php else : ?><p>Aucun teasing social.</p><?php endif; ?>
            <details>
                <summary>Ajouter un teasing</summary>
                <?php self::form_open('parcs_ht_advent_save_content', $campaign['campagne_id'], 'teasers'); ?>
                <input type="hidden" name="content[type_contenu]" value="TEASING">
                <div class="htp-advent-grid-fields">
                    <?php self::input('content[contenu_id]', '', 'ID stable', 'text', 'required pattern="[a-z0-9_-]+"'); ?>
                    <?php self::input('content[titre_fr]', '', 'Titre'); ?>
                    <?php self::input('content[date_publication]', '', 'Date prévue', 'date'); ?>
                    <?php self::input('content[heure_publication]', '', 'Heure prévue', 'time'); ?>
                </div>
                <p><button type="submit" class="button">Créer le teasing</button></p>
                </form>
            </details>
        </section>
        <?php if ($selected && (string)$selected['type_contenu'] === 'TEASING') self::content_editor($campaign, $selected, 'teasers'); ?>
        <?php
    }

    private static function content_editor($campaign, $content, $return_view) {
        self::form_open('parcs_ht_advent_save_content', $campaign['campagne_id'], $return_view);
        ?>
        <input type="hidden" name="content[contenu_id]" value="<?php echo esc_attr($content['contenu_id']); ?>">
        <input type="hidden" name="content[type_contenu]" value="<?php echo esc_attr($content['type_contenu']); ?>">
        <input type="hidden" name="content[jour_numero]" value="<?php echo esc_attr((string)$content['jour_numero']); ?>">
        <section class="htp-advent-card">
            <h2><?php echo $content['type_contenu'] === 'JOUR' ? 'Jour ' . esc_html((string)$content['jour_numero']) : 'Teasing'; ?> — <code><?php echo esc_html($content['contenu_id']); ?></code></h2>
            <div class="htp-advent-grid-fields">
                <?php self::input('content[date_publication]', $content['date_publication'], 'Date principale', 'date'); ?>
                <?php self::input('content[heure_publication]', $content['heure_publication'], 'Heure principale', 'time'); ?>
                <?php self::input('content[heure_ouverture]', $content['heure_ouverture'], 'Override heure d’ouverture', 'time'); ?>
                <?php self::select('content[statut]', $content['statut'], 'Statut éditorial', array('brouillon'=>'Brouillon','pret'=>'Prêt','publie'=>'Publié / prêt public')); ?>
                <?php self::input('content[titre_fr]', $content['titre_fr'], 'Titre'); ?>
                <?php self::select('content[partenaire_id]', $content['partenaire_id'], 'Partenaire', self::partner_options($campaign)); ?>
                <?php self::input('content[lot_fr]', $content['lot_fr'], 'Lot'); ?>
                <?php self::textarea('content[intro_partenaire_fr]', $content['intro_partenaire_fr'], 'Introduction partenaire'); ?>
                <?php self::textarea('content[intro_question_fr]', $content['intro_question_fr'], 'Introduction question'); ?>
            </div>

            <?php if ($content['type_contenu'] === 'JOUR') : ?>
                <div class="htp-advent-grid-fields">
                    <?php self::select('content[format_jeu]', $content['format_jeu'], 'Format du jeu', array('QCM'=>'QCM','VRAI_FAUX'=>'Vrai / faux','CHOIX_MULTIPLE'=>'Choix multiples')); ?>
                    <?php self::textarea('content[question_fr]', $content['question_fr'], 'Question'); ?>
                    <?php self::input('content[reponse_a_fr]', $content['reponse_a_fr'], 'Réponse A'); ?>
                    <?php self::input('content[reponse_b_fr]', $content['reponse_b_fr'], 'Réponse B'); ?>
                    <?php self::input('content[reponse_c_fr]', $content['reponse_c_fr'], 'Réponse C'); ?>
                    <?php self::input('content[reponse_d_fr]', $content['reponse_d_fr'], 'Réponse D'); ?>
                    <?php self::input('content[bonne_reponse_code]', $content['bonne_reponse_code'], 'Code de la bonne réponse'); ?>
                    <?php self::input('content[bonne_reponse_texte_fr]', $content['bonne_reponse_texte_fr'], 'Texte de la bonne réponse'); ?>
                    <?php self::textarea('content[explication_reponse_fr]', $content['explication_reponse_fr'], 'Explication de la réponse'); ?>
                </div>
            <?php endif; ?>

            <?php self::media_field('content[visuel]', $content['visuel_source'], $content['visuel_url'], $content['type_contenu'] === 'JOUR' ? 'Visuel du jour 4:5' : 'Visuel du teasing'); ?>
            <?php self::input('content[visuel_alt_fr]', $content['visuel_alt_fr'], 'Texte alternatif du visuel'); ?>

            <?php if ($content['type_contenu'] === 'JOUR') : ?>
                <div class="htp-advent-clue-admin" data-advent-clue-admin>
                    <label><input type="hidden" name="content[indice_actif]" value="non"><input type="checkbox" name="content[indice_actif]" value="oui" <?php checked($content['indice_actif'], 'oui'); ?> data-advent-clue-toggle> Ce jour contient un indice du mot mystère</label>
                    <div class="htp-advent-grid-fields" data-advent-clue-fields <?php echo $content['indice_actif'] === 'oui' ? '' : 'hidden'; ?>>
                        <?php self::input('content[indice_lettre]', $content['indice_lettre'], 'Lettre'); ?>
                        <?php self::input('content[indice_position]', $content['indice_position'], 'Position', 'number', 'min="1"'); ?>
                        <?php self::select('content[afficher_rappel_grand_jeu]', $content['afficher_rappel_grand_jeu'], 'Rappel grand jeu dans le post', array('auto'=>'Automatique','oui'=>'Oui','non'=>'Non')); ?>
                        <?php self::textarea('content[rappel_grand_jeu_override_fr]', $content['rappel_grand_jeu_override_fr'], 'Rappel spécifique pour ce jour'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="htp-advent-grid-fields">
                <?php self::textarea('content[texte_post_override_fr]', $content['texte_post_override_fr'], 'Override complet du texte social'); ?>
                <?php self::input('content[facebook_post_url]', $content['facebook_post_url'], 'URL exacte du post Facebook', 'url'); ?>
                <?php self::input('content[instagram_post_url]', $content['instagram_post_url'], 'URL exacte du post Instagram', 'url'); ?>
            </div>

            <h3>Textes générés à copier</h3>
            <div class="htp-advent-copy-grid">
                <?php self::copy_box('Facebook', Parcs_HT_Advent::social_post($campaign, $content, 'facebook', 'fr')); ?>
                <?php self::copy_box('Instagram', Parcs_HT_Advent::social_post($campaign, $content, 'instagram', 'fr')); ?>
            </div>
        </section>
        <p><button type="submit" class="button button-primary">Enregistrer ce contenu</button></p>
        </form>
        <?php
    }

    private static function partner_options($campaign) {
        $options = array(''=>'— Aucun —');
        foreach ((array)$campaign['partners'] as $id => $partner) $options[$id] = $partner['nom'] ?: $id;
        return $options;
    }

    private static function partners_view($campaign) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’un partenaire en lecture seule.
        $selected_id = isset($_GET['partner']) ? sanitize_key(wp_unslash($_GET['partner'])) : '';
        $selected = $selected_id !== '' && isset($campaign['partners'][$selected_id]) ? $campaign['partners'][$selected_id] : null;
        ?>
        <section class="htp-advent-card">
            <h2>Partenaires</h2>
            <?php if ($campaign['partners']) : ?><div class="htp-advent-list"><?php foreach ($campaign['partners'] as $id => $partner) : ?><a href="<?php echo esc_url(self::admin_url($campaign['campagne_id'], 'partners', array('partner'=>$id))); ?>"><strong><?php echo esc_html($partner['nom'] ?: $id); ?></strong><span><code><?php echo esc_html($id); ?></code></span></a><?php endforeach; ?></div><?php else : ?><p>Aucun partenaire enregistré.</p><?php endif; ?>
            <p><a class="button" href="<?php echo esc_url(self::admin_url($campaign['campagne_id'], 'partners', array('partner'=>'new'))); ?>">Ajouter un partenaire</a></p>
        </section>
        <?php
        if ($selected_id === 'new') $selected = Parcs_HT_Advent::default_partner();
        if ($selected) self::partner_editor($campaign, $selected, $selected_id === 'new');
    }

    private static function partner_editor($campaign, $partner, $is_new) {
        self::form_open('parcs_ht_advent_save_partner', $campaign['campagne_id'], 'partners');
        ?>
        <section class="htp-advent-card">
            <h2><?php echo $is_new ? 'Nouveau partenaire' : 'Modifier le partenaire'; ?></h2>
            <div class="htp-advent-grid-fields">
                <?php self::input('partner[partenaire_id]', $partner['partenaire_id'], 'ID stable', 'text', $is_new ? 'required pattern="[a-z0-9_-]+"' : 'readonly'); ?>
                <?php self::input('partner[nom]', $partner['nom'], 'Nom'); ?>
                <?php self::select('partner[type_partenaire]', $partner['type_partenaire'], 'Type', array('externe'=>'Partenaire externe','parc'=>'Parc')); ?>
                <?php self::input('partner[instagram_handle]', $partner['instagram_handle'], 'Instagram'); ?>
                <?php self::input('partner[instagram_url_override]', $partner['instagram_url_override'], 'URL Instagram override', 'url'); ?>
                <?php self::input('partner[facebook_slug]', $partner['facebook_slug'], 'Facebook'); ?>
                <?php self::input('partner[facebook_url_override]', $partner['facebook_url_override'], 'URL Facebook override', 'url'); ?>
                <?php self::input('partner[site_url]', $partner['site_url'], 'Site web', 'url'); ?>
                <?php self::textarea('partner[description_fr]', $partner['description_fr'], 'Présentation courte'); ?>
                <?php self::textarea('partner[hashtags]', $partner['hashtags'], 'Hashtags'); ?>
                <?php self::media_field('partner[logo]', $partner['logo_source'], $partner['logo_url'], 'Logo'); ?>
            </div>
        </section>
        <p><button type="submit" class="button button-primary">Enregistrer le partenaire</button></p>
        </form>
        <?php
    }

    private static function results_view($campaign) {
        $days = self::sorted_days($campaign);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’un résultat en lecture seule.
        $selected_id = isset($_GET['result']) ? sanitize_key(wp_unslash($_GET['result'])) : '';
        $selected_content = null;
        $selected_result = null;
        foreach ($days as $day) {
            $result = self::result_for_content($campaign, $day['contenu_id']);
            if ($selected_id === '' && !$selected_result) $selected_id = $result['resultat_id'];
            if ($result['resultat_id'] === $selected_id) { $selected_content = $day; $selected_result = $result; }
        }
        ?>
        <section class="htp-advent-card">
            <h2>Résultats</h2>
            <div class="htp-advent-results-list">
                <?php foreach ($days as $day) : $result = self::result_for_content($campaign, $day['contenu_id']); ?>
                    <a class="<?php echo $result['resultat_id'] === $selected_id ? 'is-selected' : ''; ?>" href="<?php echo esc_url(self::admin_url($campaign['campagne_id'], 'results', array('result'=>$result['resultat_id']))); ?>">
                        <strong>Jour <?php echo esc_html((string)$day['jour_numero']); ?></strong>
                        <span><?php echo esc_html($result['statut_resultat']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php if ($selected_content && $selected_result) self::result_editor($campaign, $selected_content, $selected_result); ?>
        <?php
    }

    private static function result_editor($campaign, $content, $result) {
        self::form_open('parcs_ht_advent_save_result', $campaign['campagne_id'], 'results');
        ?>
        <input type="hidden" name="result[resultat_id]" value="<?php echo esc_attr($result['resultat_id']); ?>">
        <input type="hidden" name="result[contenu_id]" value="<?php echo esc_attr($content['contenu_id']); ?>">
        <section class="htp-advent-card">
            <h2>Résultat — jour <?php echo esc_html((string)$content['jour_numero']); ?></h2>
            <p><strong>Bonne réponse enregistrée :</strong> <?php echo esc_html($content['bonne_reponse_texte_fr'] ?: $content['bonne_reponse_code']); ?></p>
            <div class="htp-advent-grid-fields">
                <?php self::input('result[date_revelation_resultat]', $result['date_revelation_resultat'], 'Date de révélation', 'date'); ?>
                <?php self::input('result[heure_revelation_resultat]', $result['heure_revelation_resultat'], 'Heure de révélation', 'time'); ?>
                <?php self::input('result[gagnant_facebook]', $result['gagnant_facebook'], 'Gagnant Facebook'); ?>
                <?php self::input('result[gagnant_instagram]', $result['gagnant_instagram'], 'Gagnant Instagram'); ?>
                <?php self::select('result[statut_resultat]', $result['statut_resultat'] === 'publie' ? 'pret' : $result['statut_resultat'], 'État de travail', array('brouillon'=>'Brouillon','pret'=>'Prêt')); ?>
                <?php self::textarea('result[texte_resultat_override_fr]', $result['texte_resultat_override_fr'], 'Override commentaire résultat'); ?>
                <?php self::textarea('result[story_resultat_override_fr]', $result['story_resultat_override_fr'], 'Override Story résultat'); ?>
            </div>
            <?php if ($result['statut_resultat'] === 'publie') : ?><p class="htp-advent-published-badge">Résultat actuellement publié.</p><?php endif; ?>
            <h3>Textes générés à copier</h3>
            <div class="htp-advent-copy-grid">
                <?php self::copy_box('Résultat Facebook', Parcs_HT_Advent::social_result($campaign, $content, $result, 'facebook', 'fr')); ?>
                <?php self::copy_box('Résultat Instagram', Parcs_HT_Advent::social_result($campaign, $content, $result, 'instagram', 'fr')); ?>
                <?php self::copy_box('Story résultat', Parcs_HT_Advent::story_result($campaign, $content, $result, 'fr')); ?>
            </div>
        </section>
        <p class="htp-advent-result-actions"><button type="submit" class="button button-primary" name="save_result" value="1">Enregistrer sans publier</button> <button type="submit" class="button" name="publish_result" value="1">Publier le résultat</button></p>
        </form>
        <?php
    }

    private static function copy_box($label, $text) {
        $id = 'copy-' . wp_rand(1000, 999999);
        ?><div class="htp-advent-copy-box"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label><textarea id="<?php echo esc_attr($id); ?>" rows="9" readonly data-advent-copy-source><?php echo esc_textarea((string)$text); ?></textarea><button type="button" class="button" data-advent-copy-button>Copier le texte</button></div><?php
    }

    private static function import_view($campaign) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Jeton de rapport temporaire en lecture seule.
        $token = isset($_GET['import_token']) ? sanitize_key(wp_unslash($_GET['import_token'])) : '';
        $report = $token !== '' ? get_transient(self::import_transient_key($token)) : false;
        ?>
        <section class="htp-advent-card">
            <h2>Import / export — prototype schéma 3</h2>
            <p>Le premier prototype importe un CSV UTF-8 dont l’en-tête est l’union des champs canoniques. Chaque ligne est reconnue par son identifiant stable : <code>campagne_id</code>, <code>partenaire_id</code>, <code>contenu_id</code>, <code>resultat_id</code> ou <code>reference_id</code> pour une traduction.</p>
            <p>Le fichier est d’abord analysé. Aucune donnée n’est écrite avant confirmation du rapport.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-advent-inline-form">
                <input type="hidden" name="action" value="parcs_ht_advent_csv_template">
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['campagne_id']); ?>">
                <?php wp_nonce_field('parcs_ht_advent_csv_template_' . $campaign['campagne_id']); ?>
                <button type="submit" class="button">Télécharger le modèle CSV schéma 3</button>
            </form>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-advent-import-form">
                <input type="hidden" name="action" value="parcs_ht_advent_import_csv">
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['campagne_id']); ?>">
                <?php wp_nonce_field('parcs_ht_advent_import_csv_' . $campaign['campagne_id']); ?>
                <label><span>Fichier CSV</span><input type="file" name="advent_csv" accept=".csv,text/csv" required></label>
                <button type="submit" class="button button-primary">Analyser le CSV</button>
            </form>
        </section>
        <?php if (is_array($report)) self::import_report($campaign, $token, $report); ?>
        <?php
    }

    private static function import_report($campaign, $token, $report) {
        ?>
        <section class="htp-advent-card">
            <h2>Rapport avant écriture</h2>
            <div class="htp-advent-report-counts">
                <span>Créations <strong><?php echo esc_html((string)($report['counts']['create'] ?? 0)); ?></strong></span>
                <span>Modifications <strong><?php echo esc_html((string)($report['counts']['update'] ?? 0)); ?></strong></span>
                <span>Inchangés <strong><?php echo esc_html((string)($report['counts']['same'] ?? 0)); ?></strong></span>
                <span>Avertissements <strong><?php echo esc_html((string)count((array)($report['warnings'] ?? array()))); ?></strong></span>
                <span>Erreurs <strong><?php echo esc_html((string)count((array)($report['errors'] ?? array()))); ?></strong></span>
            </div>
            <?php if (!empty($report['warnings'])) : ?><h3>Avertissements</h3><ul><?php foreach ($report['warnings'] as $warning) : ?><li><?php echo esc_html($warning); ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if (!empty($report['errors'])) : ?><h3>Erreurs bloquantes</h3><ul><?php foreach ($report['errors'] as $error) : ?><li><?php echo esc_html($error); ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if (empty($report['errors'])) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="parcs_ht_advent_apply_import">
                    <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['campagne_id']); ?>">
                    <input type="hidden" name="import_token" value="<?php echo esc_attr($token); ?>">
                    <?php wp_nonce_field('parcs_ht_advent_apply_import_' . $campaign['campagne_id']); ?>
                    <button type="submit" class="button button-primary">Confirmer et appliquer l’import</button>
                </form>
            <?php endif; ?>
        </section>
        <?php
    }

    public static function create_campaign() {
        self::require_admin();
        check_admin_referer('parcs_ht_advent_create_campaign');
        $park = Parcs_HT_Advent::installation_park_code();
        if ($park === '') wp_die('Type de parc indisponible.');
        $id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        $year = isset($_POST['year']) ? sanitize_text_field(wp_unslash($_POST['year'])) : '';
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        if ($id === '' || !preg_match('/^20\d{2}$/', $year)) self::redirect('', 'campaign', array('advent_error'=>'Campagne invalide.'));
        $store = Parcs_HT_Advent::store();
        if (isset($store['campaigns'][$id])) self::redirect($id, 'campaign', array('advent_error'=>'Cet identifiant de campagne existe déjà.'));
        $store['campaigns'][$id] = Parcs_HT_Advent::default_campaign($id, $park, $year, $name);
        Parcs_HT_Advent::save_store($store);
        self::redirect($id, 'campaign', array('advent_notice'=>'created'));
    }

    public static function save_campaign() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_save_campaign_' . $campaign_id);
        $current = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$current) wp_die('Campagne introuvable.');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé champ par champ par Parcs_HT_Advent::sanitize_campaign().
        $raw = isset($_POST['campaign']) && is_array($_POST['campaign']) ? wp_unslash($_POST['campaign']) : array();
        $raw['campagne_id'] = $campaign_id;
        $raw['parc_code'] = $current['parc_code'];
        if (isset($raw['teasing_site_visuel']) && is_array($raw['teasing_site_visuel'])) {
            $raw['teasing_site_visuel_source'] = $raw['teasing_site_visuel']['source'] ?? '';
            $raw['teasing_site_visuel_url'] = $raw['teasing_site_visuel']['url'] ?? '';
            unset($raw['teasing_site_visuel']);
        }
        $clean = Parcs_HT_Advent::sanitize_campaign($raw, $current);
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id] = $clean;
        Parcs_HT_Advent::save_store($store);
        $view = isset($_POST['return_view']) ? sanitize_key(wp_unslash($_POST['return_view'])) : 'campaign';
        self::redirect($campaign_id, $view, array('advent_notice'=>'saved'));
    }

    public static function save_content() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_save_content_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé champ par champ par sanitize_content().
        $raw = isset($_POST['content']) && is_array($_POST['content']) ? wp_unslash($_POST['content']) : array();
        $content_id = sanitize_key((string)($raw['contenu_id'] ?? ''));
        if ($content_id === '') self::redirect($campaign_id, 'calendar', array('advent_error'=>'Identifiant de contenu manquant.'));
        $existing = isset($campaign['contents'][$content_id]) ? $campaign['contents'][$content_id] : Parcs_HT_Advent::default_content();
        if (isset($raw['visuel']) && is_array($raw['visuel'])) {
            $raw['visuel_source'] = $raw['visuel']['source'] ?? '';
            $raw['visuel_url'] = $raw['visuel']['url'] ?? '';
            unset($raw['visuel']);
        }
        $clean = Parcs_HT_Advent::sanitize_content($raw, $existing);
        if ($clean['type_contenu'] === 'JOUR' && ($clean['jour_numero'] < 1 || $clean['jour_numero'] > 24)) wp_die('Numéro de jour invalide.');
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id]['contents'][$content_id] = $clean;
        if ($clean['type_contenu'] === 'JOUR') {
            $has_result = false;
            foreach ($store['campaigns'][$campaign_id]['results'] as $result) if ((string)($result['contenu_id'] ?? '') === $content_id) { $has_result = true; break; }
            if (!$has_result) {
                $result = Parcs_HT_Advent::default_result($content_id, $clean['jour_numero']);
                $store['campaigns'][$campaign_id]['results'][$result['resultat_id']] = $result;
            }
        }
        Parcs_HT_Advent::save_store($store);
        $view = isset($_POST['return_view']) ? sanitize_key(wp_unslash($_POST['return_view'])) : 'calendar';
        $extra = $clean['type_contenu'] === 'JOUR' ? array('day'=>$content_id) : array('teaser'=>$content_id);
        $extra['advent_notice'] = 'content_saved';
        self::redirect($campaign_id, $view, $extra);
    }

    public static function save_partner() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_save_partner_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé champ par champ par sanitize_partner().
        $raw = isset($_POST['partner']) && is_array($_POST['partner']) ? wp_unslash($_POST['partner']) : array();
        $partner_id = sanitize_key((string)($raw['partenaire_id'] ?? ''));
        if ($partner_id === '') self::redirect($campaign_id, 'partners', array('advent_error'=>'Identifiant partenaire manquant.'));
        $existing = isset($campaign['partners'][$partner_id]) ? $campaign['partners'][$partner_id] : Parcs_HT_Advent::default_partner();
        if (isset($raw['logo']) && is_array($raw['logo'])) {
            $raw['logo_source'] = $raw['logo']['source'] ?? '';
            $raw['logo_url'] = $raw['logo']['url'] ?? '';
            unset($raw['logo']);
        }
        $clean = Parcs_HT_Advent::sanitize_partner($raw, $existing);
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id]['partners'][$partner_id] = $clean;
        Parcs_HT_Advent::save_store($store);
        self::redirect($campaign_id, 'partners', array('partner'=>$partner_id,'advent_notice'=>'partner_saved'));
    }

    public static function save_result() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_save_result_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Tableau imbriqué nettoyé par sanitize_result().
        $raw = isset($_POST['result']) && is_array($_POST['result']) ? wp_unslash($_POST['result']) : array();
        $result_id = sanitize_key((string)($raw['resultat_id'] ?? ''));
        if ($result_id === '') wp_die('Résultat invalide.');
        $existing = isset($campaign['results'][$result_id]) ? $campaign['results'][$result_id] : Parcs_HT_Advent::default_result();
        $clean = Parcs_HT_Advent::sanitize_result($raw, $existing);
        $published = isset($_POST['publish_result']) && (string)wp_unslash($_POST['publish_result']) === '1';
        if ($published) $clean['statut_resultat'] = 'publie';
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id]['results'][$result_id] = $clean;
        Parcs_HT_Advent::save_store($store);
        self::redirect($campaign_id, 'results', array('result'=>$result_id,'advent_notice'=>$published ? 'result_published' : 'result_saved'));
    }

    private static function sorted_days($campaign) {
        $days = array_values(array_filter((array)$campaign['contents'], static function ($content) { return is_array($content) && (string)($content['type_contenu'] ?? '') === 'JOUR'; }));
        usort($days, static function ($a, $b) { return ((int)($a['jour_numero'] ?? 0)) <=> ((int)($b['jour_numero'] ?? 0)); });
        return $days;
    }

    private static function result_for_content($campaign, $content_id) {
        foreach ((array)$campaign['results'] as $result) if (is_array($result) && (string)($result['contenu_id'] ?? '') === (string)$content_id) return $result;
        return Parcs_HT_Advent::default_result($content_id);
    }

    private static function import_transient_key($token) {
        return self::IMPORT_TRANSIENT_PREFIX . get_current_user_id() . '_' . sanitize_key($token);
    }

    private static function csv_headers() {
        return array_values(array_unique(array_merge(
            Parcs_HT_Advent::campaign_field_names(),
            Parcs_HT_Advent::partner_field_names(),
            Parcs_HT_Advent::content_field_names(),
            Parcs_HT_Advent::result_field_names(),
            Parcs_HT_Advent::translation_field_names()
        )));
    }

    public static function csv_template() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_csv_template_' . $campaign_id);
        $campaign = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$campaign) wp_die('Campagne introuvable.');
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

    public static function import_csv() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_import_csv_' . $campaign_id);
        $current = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$current) wp_die('Campagne introuvable.');
        if (empty($_FILES['advent_csv']) || !is_array($_FILES['advent_csv'])) self::redirect($campaign_id, 'import', array('advent_error'=>'Fichier CSV manquant.'));
        $name = sanitize_file_name((string)($_FILES['advent_csv']['name'] ?? ''));
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') self::redirect($campaign_id, 'import', array('advent_error'=>'Le prototype attend un fichier .csv.'));
        if ((int)($_FILES['advent_csv']['size'] ?? 0) > 5 * 1024 * 1024) self::redirect($campaign_id, 'import', array('advent_error'=>'Le fichier dépasse 5 Mo.'));
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Chemin temporaire fourni par PHP, utilisé uniquement en lecture après contrôle du fichier uploadé.
        $tmp = isset($_FILES['advent_csv']['tmp_name']) ? (string)$_FILES['advent_csv']['tmp_name'] : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) self::redirect($campaign_id, 'import', array('advent_error'=>'Fichier temporaire invalide.'));
        $rows = self::read_csv($tmp);
        if (is_wp_error($rows)) self::redirect($campaign_id, 'import', array('advent_error'=>$rows->get_error_message()));
        $report = self::analyze_import($rows, $current);
        $token = sanitize_key(wp_generate_password(12, false, false));
        set_transient(self::import_transient_key($token), $report, 30 * MINUTE_IN_SECONDS);
        self::redirect($campaign_id, 'import', array('import_token'=>$token));
    }

    private static function read_csv($path) {
        $handle = fopen($path, 'r');
        if ($handle === false) return new WP_Error('advent_csv_open', 'Impossible de lire le CSV.');
        $header = fgetcsv($handle, 0, ';');
        if (!is_array($header) || !$header) { fclose($handle); return new WP_Error('advent_csv_header', 'En-tête CSV introuvable.'); }
        if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
        $header = array_map('sanitize_key', $header);
        $rows = array();
        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            if (!array_filter($line, 'strlen')) continue;
            $line = array_pad($line, count($header), '');
            $row = array();
            foreach ($header as $index => $key) if ($key !== '') $row[$key] = isset($line[$index]) ? (string)$line[$index] : '';
            $rows[] = $row;
        }
        fclose($handle);
        if (!$rows) return new WP_Error('advent_csv_empty', 'Le CSV ne contient aucune donnée.');
        return $rows;
    }

    private static function analyze_import($rows, $current) {
        $report = array('counts'=>array('create'=>0,'update'=>0,'same'=>0),'warnings'=>array(),'errors'=>array(),'candidate'=>null,'original_hash'=>md5(wp_json_encode($current)));
        $campaign_row = null;
        $partner_rows = array();
        $content_rows = array();
        $result_rows = array();
        $translation_rows = array();

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            if (!is_array($row)) continue;
            if (!empty($row['reference_id']) && !empty($row['champ']) && !empty($row['langue'])) { $translation_rows[] = $row; continue; }
            if (!empty($row['resultat_id'])) { $result_rows[] = $row; continue; }
            if (!empty($row['contenu_id'])) { $content_rows[] = $row; continue; }
            if (!empty($row['partenaire_id'])) { $partner_rows[] = $row; continue; }
            if (!empty($row['campagne_id'])) {
                if ($campaign_row !== null) $report['errors'][] = 'Plusieurs lignes CAMPAGNE détectées.';
                $campaign_row = $row;
                continue;
            }
            $report['warnings'][] = 'Ligne ' . $line . ' ignorée : aucun identifiant canonique reconnu.';
        }

        if ($campaign_row === null) $report['errors'][] = 'Aucune ligne CAMPAGNE détectée.';
        if ($campaign_row !== null) {
            if ((int)($campaign_row['schema_version'] ?? 0) !== Parcs_HT_Advent::SCHEMA_VERSION) $report['errors'][] = 'schema_version doit être égal à 3.';
            $import_id = sanitize_key((string)($campaign_row['campagne_id'] ?? ''));
            if ($import_id !== (string)$current['campagne_id']) $report['errors'][] = 'Le campagne_id du CSV ne correspond pas à la campagne sélectionnée.';
            $park = sanitize_key((string)($campaign_row['parc_code'] ?? ''));
            if ($park !== Parcs_HT_Advent::installation_park_code()) $report['errors'][] = 'Le parc_code du CSV ne correspond pas à cette installation.';
        }
        if ($report['errors']) return $report;

        $candidate = $current;
        $campaign_raw = $campaign_row;
        $campaign_raw['campagne_id'] = $current['campagne_id'];
        $campaign_raw['parc_code'] = $current['parc_code'];
        $clean_campaign = Parcs_HT_Advent::sanitize_campaign($campaign_raw, $current);
        if (trim((string)($campaign_row['teasing_site_visuel_url'] ?? '')) === '') {
            $clean_campaign['teasing_site_visuel_url'] = $current['teasing_site_visuel_url'];
            $clean_campaign['teasing_site_visuel_source'] = $current['teasing_site_visuel_source'];
        }
        $candidate = array_replace($candidate, $clean_campaign);
        self::count_diff($report, $current, $candidate);

        foreach ($partner_rows as $row) {
            $id = sanitize_key((string)($row['partenaire_id'] ?? ''));
            if ($id === '') { $report['errors'][] = 'Un partenaire n’a pas de partenaire_id.'; continue; }
            $existing = isset($candidate['partners'][$id]) ? $candidate['partners'][$id] : Parcs_HT_Advent::default_partner();
            $clean = Parcs_HT_Advent::sanitize_partner($row, $existing);
            if (trim((string)($row['logo_url'] ?? '')) === '' && !empty($existing['logo_url'])) { $clean['logo_url'] = $existing['logo_url']; $clean['logo_source'] = $existing['logo_source']; }
            self::count_diff($report, isset($candidate['partners'][$id]) ? $candidate['partners'][$id] : null, $clean);
            $candidate['partners'][$id] = $clean;
        }

        foreach ($content_rows as $row) {
            $id = sanitize_key((string)($row['contenu_id'] ?? ''));
            if ($id === '') { $report['errors'][] = 'Un contenu n’a pas de contenu_id.'; continue; }
            $existing = isset($candidate['contents'][$id]) ? $candidate['contents'][$id] : Parcs_HT_Advent::default_content();
            $clean = Parcs_HT_Advent::sanitize_content($row, $existing);
            if (trim((string)($row['visuel_url'] ?? '')) === '' && !empty($existing['visuel_url'])) { $clean['visuel_url'] = $existing['visuel_url']; $clean['visuel_source'] = $existing['visuel_source']; }
            self::count_diff($report, isset($candidate['contents'][$id]) ? $candidate['contents'][$id] : null, $clean);
            $candidate['contents'][$id] = $clean;
        }

        foreach ($result_rows as $row) {
            $id = sanitize_key((string)($row['resultat_id'] ?? ''));
            if ($id === '') { $report['errors'][] = 'Un résultat n’a pas de resultat_id.'; continue; }
            $existing = isset($candidate['results'][$id]) ? $candidate['results'][$id] : Parcs_HT_Advent::default_result();
            $clean = Parcs_HT_Advent::sanitize_result($row, $existing);
            self::count_diff($report, isset($candidate['results'][$id]) ? $candidate['results'][$id] : null, $clean);
            $candidate['results'][$id] = $clean;
        }

        $translations = array();
        foreach ((array)$candidate['translations'] as $translation) {
            $key = self::translation_key($translation);
            if ($key !== '') $translations[$key] = $translation;
        }
        foreach ($translation_rows as $row) {
            $clean = Parcs_HT_Advent::sanitize_translation($row);
            $key = self::translation_key($clean);
            if ($key === '') { $report['errors'][] = 'Une traduction est incomplète.'; continue; }
            self::count_diff($report, isset($translations[$key]) ? $translations[$key] : null, $clean);
            $translations[$key] = $clean;
        }
        $candidate['translations'] = array_values($translations);

        $seen_days = array();
        foreach ((array)$candidate['contents'] as $content) {
            if (!is_array($content) || (string)($content['type_contenu'] ?? '') !== 'JOUR') continue;
            $day = (int)($content['jour_numero'] ?? 0);
            if ($day < 1 || $day > 24) { $report['errors'][] = 'Un contenu JOUR possède un jour_numero invalide.'; continue; }
            if (isset($seen_days[$day])) $report['errors'][] = 'Le jour ' . $day . ' apparaît plusieurs fois après fusion.';
            $seen_days[$day] = true;
        }
        for ($day = 1; $day <= 24; $day++) if (!isset($seen_days[$day])) $report['errors'][] = 'Le jour ' . $day . ' manque après fusion.';

        foreach ((array)$candidate['contents'] as $content) {
            if (!is_array($content) || empty($content['partenaire_id'])) continue;
            if (!isset($candidate['partners'][$content['partenaire_id']])) $report['warnings'][] = 'Le contenu ' . $content['contenu_id'] . ' référence un partenaire absent : ' . $content['partenaire_id'] . '.';
        }
        foreach ((array)$candidate['results'] as $result) {
            if (!is_array($result) || empty($result['contenu_id'])) continue;
            if (!isset($candidate['contents'][$result['contenu_id']])) $report['errors'][] = 'Le résultat ' . $result['resultat_id'] . ' référence un contenu absent.';
        }
        $report['candidate'] = $candidate;
        return $report;
    }

    private static function count_diff(&$report, $before, $after) {
        if ($before === null) $report['counts']['create']++;
        elseif (wp_json_encode($before) === wp_json_encode($after)) $report['counts']['same']++;
        else $report['counts']['update']++;
    }

    private static function translation_key($translation) {
        if (!is_array($translation)) return '';
        $reference = sanitize_key((string)($translation['reference_id'] ?? ''));
        $field = sanitize_key((string)($translation['champ'] ?? ''));
        $lang = sanitize_key((string)($translation['langue'] ?? ''));
        if ($reference === '' || $field === '' || !in_array($lang, array('en','de'), true)) return '';
        return $reference . '|' . $field . '|' . $lang;
    }

    public static function apply_import() {
        self::require_admin();
        $campaign_id = isset($_POST['campaign_id']) ? sanitize_key(wp_unslash($_POST['campaign_id'])) : '';
        check_admin_referer('parcs_ht_advent_apply_import_' . $campaign_id);
        $token = isset($_POST['import_token']) ? sanitize_key(wp_unslash($_POST['import_token'])) : '';
        $report = $token !== '' ? get_transient(self::import_transient_key($token)) : false;
        if (!is_array($report) || !empty($report['errors']) || empty($report['candidate'])) self::redirect($campaign_id, 'import', array('advent_error'=>'Rapport d’import invalide ou expiré.'));
        $current = Parcs_HT_Advent::campaign($campaign_id, true);
        if (!$current || md5(wp_json_encode($current)) !== (string)($report['original_hash'] ?? '')) {
            delete_transient(self::import_transient_key($token));
            self::redirect($campaign_id, 'import', array('advent_notice'=>'import_stale'));
        }
        $candidate = $report['candidate'];
        if (!is_array($candidate) || (string)($candidate['parc_code'] ?? '') !== Parcs_HT_Advent::installation_park_code()) wp_die('Import refusé : parc incorrect.');
        $store = Parcs_HT_Advent::store();
        $store['campaigns'][$campaign_id] = $candidate;
        Parcs_HT_Advent::save_store($store);
        delete_transient(self::import_transient_key($token));
        self::redirect($campaign_id, 'import', array('advent_notice'=>'imported'));
    }
}
