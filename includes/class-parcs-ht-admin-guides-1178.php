<?php

if (!defined('ABSPATH')) { exit; }

/** Écran métier Guides pédagogiques 1.17.8. */
final class Parcs_HT_Admin_Guides_1178 {
    const PAGE = 'parcs-ht-guides-1178';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'route_legacy'), 3);
        add_action('admin_menu', array(__CLASS__, 'menu'), 64);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 70);
    }

    private static function valid_year($year) {
        $year = trim((string)$year);
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function requested_year() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
        return isset($_GET['season']) ? self::valid_year(sanitize_text_field(wp_unslash($_GET['season']))) : '';
    }

    private static function selected_year($all = null) {
        if ($all === null) $all = Parcs_HT_Defaults::all_settings();
        $requested = self::requested_year();
        if ($requested !== '' && isset($all['seasons'][$requested])) return $requested;
        $years = array_keys((array)($all['seasons'] ?? array()));
        $years = array_values(array_filter(array_map('strval', $years), static function ($year) {
            return (bool)preg_match('/^20\d{2}$/', $year);
        }));
        sort($years, SORT_NUMERIC);
        $current = wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        return $years ? (string)end($years) : '';
    }

    private static function page_url($year = '', $extra = array()) {
        $args = array('page'=>self::PAGE);
        if (self::valid_year($year) !== '') $args['season'] = $year;
        foreach ((array)$extra as $key=>$value) {
            if ($value !== '' && $value !== null) $args[sanitize_key($key)] = (string)$value;
        }
        return add_query_arg($args, admin_url('admin.php'));
    }

    public static function route_legacy() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- routage et indicateurs visuels en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $legacy = isset($_GET['legacy_guides']) && sanitize_text_field(wp_unslash($_GET['legacy_guides'])) === '1';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ($legacy) return;

        $is_old_tab = $page === Parcs_HT_Admin::PAGE && $tab === 'htp-guides';
        $is_old_page = class_exists('Parcs_HT_Pedagogical_Guides') && $page === Parcs_HT_Pedagogical_Guides::PAGE;
        if (!$is_old_tab && !$is_old_page) return;

        $extra = array();
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- indicateurs de confirmation et filtre statistique uniquement.
        foreach (array('guides-updated','guide-appearance-updated') as $notice) {
            if (isset($_GET[$notice])) $extra[$notice] = '1';
        }
        if (isset($_GET['guide_stats_range'])) {
            $range = sanitize_key(wp_unslash($_GET['guide_stats_range']));
            if (in_array($range, array('7','30','season','all'), true)) $extra['guide_stats_range'] = $range;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        wp_safe_redirect(self::page_url(self::requested_year(), $extra));
        exit;
    }

    public static function menu() {
        add_submenu_page(null, 'Guides pédagogiques', 'Guides pédagogiques', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    public static function assets($hook) {
        if ($hook !== 'admin_page_' . self::PAGE) return;
        wp_enqueue_media();
        wp_enqueue_style('parcs-ht-pedagogical-guides-admin', PARCS_HT_URL . 'assets/pedagogical-guides-admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-admin-guides-1178', PARCS_HT_URL . 'assets/admin-guides-1178.css', array('parcs-ht-pedagogical-guides-admin'), PARCS_HT_VERSION);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('parcs-ht-admin-guides-1178', PARCS_HT_URL . 'assets/admin-guides-1178.js', array('jquery','jquery-ui-sortable'), PARCS_HT_VERSION, true);
    }

    private static function content_text($key, $language, $fallback) {
        if (class_exists('Parcs_HT_Public_Content')) return Parcs_HT_Public_Content::text($key, $language, $fallback);
        return (string)$fallback;
    }

    private static function cycles() {
        return array(
            'cycle1'=>array('label'=>'Cycle 1','detail'=>'Maternelle – 3 à 6 ans'),
            'cycle2'=>array('label'=>'Cycle 2','detail'=>'CP au CE2 – 6 à 9 ans'),
            'cycle3'=>array('label'=>'Cycle 3','detail'=>'CM1 à la 6e – 9 à 12 ans'),
            'cycle4'=>array('label'=>'Cycle 4','detail'=>'5e à la 3e – 12 à 15 ans'),
            'multi'=>array('label'=>'Multiniveaux','detail'=>'Dossier adaptable à plusieurs niveaux'),
        );
    }

    private static function cycle_label($cycle, $detail = false) {
        $catalog = self::cycles();
        if (!isset($catalog[$cycle])) $cycle = 'cycle1';
        $part = $detail ? 'detail' : 'label';
        $fallback = $catalog[$cycle][$part];
        return self::content_text('guides.' . $cycle . '.' . $part, 'fr', $fallback);
    }

    private static function language_label($language) {
        $fallback = array('fr'=>'Français','de'=>'Allemand','en'=>'Anglais');
        return self::content_text('guides.language.' . $language, 'fr', $fallback[$language] ?? strtoupper($language));
    }

    private static function language_flag($language) {
        $flags = array('fr'=>'🇫🇷','de'=>'🇩🇪','en'=>'🇬🇧');
        return $flags[$language] ?? '';
    }

    private static function status_label($status) {
        $labels = array('available'=>'Disponible','new'=>'Nouveau','coming'=>'À venir');
        return $labels[$status] ?? $labels['available'];
    }

    private static function group_navigation($year) {
        $group_url = class_exists('Parcs_HT_Admin_Group_Tariffs')
            ? add_query_arg(array('page'=>Parcs_HT_Admin_Group_Tariffs::PAGE,'season'=>$year), admin_url('admin.php'))
            : admin_url('admin.php');
        $quote_url = class_exists('Parcs_HT_Admin_Group_Quotes_1177')
            ? add_query_arg(array('page'=>Parcs_HT_Admin_Group_Quotes_1177::PAGE,'season'=>$year), admin_url('admin.php'))
            : admin_url('admin.php');
        echo '<nav class="htp-1178-family" aria-label="Rubrique Groupes">';
        echo '<a class="button" href="' . esc_url($group_url) . '">Tarifs groupes</a>';
        echo '<a class="button" href="' . esc_url($quote_url) . '">Devis groupes</a>';
        echo '<a class="button button-primary" href="' . esc_url(self::page_url($year)) . '">Guides pédagogiques</a>';
        echo '</nav>';
    }

    private static function content_url() {
        if (!class_exists('Parcs_HT_Public_Content')) return admin_url('admin.php');
        return add_query_arg(array('page'=>Parcs_HT_Public_Content::PAGE), admin_url('admin.php'));
    }

    private static function notice() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- messages de confirmation uniquement.
        if (isset($_GET['guides-updated'])) echo '<div class="notice notice-success is-dismissible"><p>Les guides pédagogiques ont été enregistrés.</p></div>';
        if (isset($_GET['guide-appearance-updated'])) echo '<div class="notice notice-success is-dismissible"><p>L’apparence des guides a été enregistrée.</p></div>';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    private static function summary($guides) {
        $enabled = 0;
        $cycles = array();
        $languages = array();
        $badges = array('available'=>0,'new'=>0,'coming'=>0);
        foreach ((array)$guides as $guide) {
            if (!is_array($guide)) continue;
            if ((string)($guide['enabled'] ?? '0') === '1') $enabled++;
            $cycle = sanitize_key($guide['cycle'] ?? '');
            if ($cycle !== '') $cycles[$cycle] = true;
            foreach ((array)($guide['languages'] ?? array()) as $language) $languages[sanitize_key($language)] = true;
            $status = sanitize_key($guide['status'] ?? 'available');
            if (isset($badges[$status])) $badges[$status]++;
        }
        echo '<div class="htp-1178-summary">';
        echo '<div><strong>' . count((array)$guides) . '</strong><span>document(s)</span></div>';
        echo '<div><strong>' . (int)$enabled . '</strong><span>affiché(s)</span></div>';
        echo '<div><strong>' . count($cycles) . '</strong><span>cycle(s) utilisé(s)</span></div>';
        echo '<div><strong>' . count($languages) . '</strong><span>langue(s) utilisée(s)</span></div>';
        echo '<div><strong>' . (int)$badges['new'] . '</strong><span>badge(s) Nouveau</span></div>';
        echo '<div><strong>' . (int)$badges['coming'] . '</strong><span>badge(s) À venir</span></div>';
        echo '</div>';
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        if ($year === '') {
            echo '<div class="wrap"><h1>Guides pédagogiques</h1><p>Aucune saison annuelle n’est disponible.</p></div>';
            return;
        }
        $library = class_exists('Parcs_HT_Pedagogical_Guides') ? Parcs_HT_Pedagogical_Guides::settings($year) : array('guides'=>array());
        $guides = isset($library['guides']) && is_array($library['guides']) ? $library['guides'] : array();
        ?>
        <div class="wrap htp-1178-guides">
            <h1>Guides pédagogiques</h1>
            <p class="description">Gérez les documents, leurs cycles, langues, badges et fichiers depuis un seul écran. Le stockage, les identifiants statistiques et les shortcodes publics existants sont conservés.</p>
            <?php self::notice(); self::group_navigation($year); ?>
            <?php if (class_exists('Parcs_HT_Admin_Navigation')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year); ?>

            <section class="htp-1178-card">
                <div class="htp-1178-card-head"><div><h2>Bibliothèque <?php echo esc_html($year); ?></h2><p>Vue synthétique des documents de la saison sélectionnée.</p></div><a class="button" href="<?php echo esc_url(self::content_url()); ?>">Contenus & traductions</a></div>
                <?php self::summary($guides); ?>
                <div class="htp-1178-separation-note"><strong>Données structurelles</strong> : cycle associé, langues disponibles, statut, ordre, PDF, couverture et ID statistique restent propres au document. <strong>Libellés publics</strong> : titres de rubrique, noms des cycles/niveaux et noms des langues sont centralisés dans « Contenus & traductions » FR / EN / DE.</div>
            </section>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-1178-library-form" data-htp-guides-1178>
                <input type="hidden" name="action" value="parcs_ht_save_pedagogical_guides">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_pedagogical_guides'); ?>
                <section class="htp-1178-card">
                    <div class="htp-1178-card-head"><div><h2>Documents pédagogiques</h2><p>Chaque document est replié pour alléger l’écran. Ouvrez uniquement celui que vous souhaitez modifier.</p></div><button type="button" class="button button-primary" data-add-guide>Ajouter un guide</button></div>
                    <div class="htp-1178-guide-list" data-guide-list>
                        <?php foreach ($guides as $index=>$guide) self::guide_row($index, $guide); ?>
                    </div>
                    <template data-guide-template><?php self::guide_row('__INDEX__', array('id'=>'','enabled'=>'0','cycle'=>'cycle1','languages'=>array('fr'),'status'=>'coming','title'=>array(),'description'=>array(),'pdf_url'=>'','cover_url'=>'','order'=>0), true); ?></template>
                    <?php submit_button('Enregistrer les guides', 'primary', 'submit', false); ?>
                </section>
            </form>

            <section class="htp-1178-card">
                <div class="htp-1178-card-head"><div><h2>Textes publics et libellés</h2><p>Les textes communs ne sont plus à dupliquer dans cet écran.</p></div><a class="button" href="<?php echo esc_url(self::content_url()); ?>">Modifier les textes FR / EN / DE</a></div>
                <p>Le titre « Dossiers pédagogiques », les filtres, les badges, les boutons, les noms et détails des cycles ainsi que les noms des langues utilisent le référentiel « Contenus & traductions ». Les codes internes <code>cycle1</code> à <code>cycle4</code>, <code>multi</code> et <code>fr/de/en</code> restent stables et ne sont pas éditoriaux.</p>
                <p class="description">Shortcodes conservés : <code>[parc_guides_pedagogiques]</code>, <code>[parc_guides_pedagogiques_fr]</code>, <code>[parc_guides_pedagogiques_en]</code> et <code>[parc_guides_pedagogiques_de]</code>.</p>
            </section>

            <?php self::appearance_panel($year); ?>
            <?php if (class_exists('Parcs_HT_Guide_Stats')) Parcs_HT_Guide_Stats::render_admin_panel($year); ?>
        </div>
        <?php
    }

    private static function guide_row($index, $guide, $template = false) {
        $guide = wp_parse_args(is_array($guide) ? $guide : array(), array(
            'id'=>'','enabled'=>'1','cycle'=>'cycle1','languages'=>array('fr'),'status'=>'available',
            'title'=>array(),'description'=>array(),'pdf_url'=>'','cover_url'=>'','order'=>0,
        ));
        $base = 'guides[items][' . $index . ']';
        $title = trim((string)(is_array($guide['title']) ? ($guide['title']['fr'] ?? '') : ''));
        if ($title === '') {
            foreach (array('en','de') as $language) {
                $candidate = trim((string)(is_array($guide['title']) ? ($guide['title'][$language] ?? '') : ''));
                if ($candidate !== '') { $title = $candidate; break; }
            }
        }
        if ($title === '') $title = 'Nouveau guide';
        $cycle = isset(self::cycles()[$guide['cycle']]) ? (string)$guide['cycle'] : 'cycle1';
        $status = in_array((string)$guide['status'], array('available','new','coming'), true) ? (string)$guide['status'] : 'available';
        $languages = array_values(array_intersect(array('fr','de','en'), (array)$guide['languages']));
        if (!$languages) $languages = array('fr');
        ?>
        <details class="htp-1178-guide<?php echo $template ? ' is-template' : ''; ?>" data-guide-row>
            <summary>
                <span class="htp-1178-handle" data-guide-handle aria-hidden="true">↕</span>
                <span class="htp-1178-guide-summary-main"><strong data-guide-summary-title><?php echo esc_html($title); ?></strong><small><?php echo esc_html(self::cycle_label($cycle)); ?> · <?php echo esc_html(self::status_label($status)); ?></small></span>
                <span class="htp-1178-guide-summary-languages" data-guide-summary-languages><?php foreach ($languages as $language) echo '<span title="' . esc_attr(self::language_label($language)) . '">' . esc_html(self::language_flag($language)) . '</span>'; ?></span>
                <?php if ((string)$guide['id'] !== '') : ?><code><?php echo esc_html($guide['id']); ?></code><?php else : ?><span class="description">ID attribué à l’enregistrement</span><?php endif; ?>
            </summary>
            <div class="htp-1178-guide-content">
                <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr($guide['id']); ?>">

                <div class="htp-1178-block">
                    <h3>Classement et affichage</h3>
                    <div class="htp-1178-fields htp-1178-fields-4">
                        <label class="htp-1178-check"><input type="checkbox" name="<?php echo esc_attr($base . '[enabled]'); ?>" value="1" <?php checked((string)$guide['enabled'], '1'); ?>> <span>Afficher ce document</span></label>
                        <label><span>Cycle / niveau</span><select name="<?php echo esc_attr($base . '[cycle]'); ?>" data-guide-cycle><?php foreach (self::cycles() as $cycle_id=>$meta) : ?><option value="<?php echo esc_attr($cycle_id); ?>" <?php selected($cycle, $cycle_id); ?>><?php echo esc_html(self::cycle_label($cycle_id) . ' — ' . self::cycle_label($cycle_id, true)); ?></option><?php endforeach; ?></select></label>
                        <label><span>Badge / statut</span><select name="<?php echo esc_attr($base . '[status]'); ?>" data-guide-status><option value="available" <?php selected($status, 'available'); ?>>Disponible</option><option value="new" <?php selected($status, 'new'); ?>>Nouveau</option><option value="coming" <?php selected($status, 'coming'); ?>>À venir</option></select></label>
                        <label><span>Ordre d’affichage</span><input type="number" class="small-text" data-guide-order name="<?php echo esc_attr($base . '[order]'); ?>" value="<?php echo (int)$guide['order']; ?>"></label>
                    </div>
                </div>

                <div class="htp-1178-block">
                    <h3>Langues du document</h3>
                    <div class="htp-1178-language-checks"><?php foreach (array('fr','de','en') as $language) : ?><label><input type="checkbox" name="<?php echo esc_attr($base . '[languages][' . $language . ']'); ?>" value="1" data-guide-language="<?php echo esc_attr($language); ?>" <?php checked(in_array($language, $languages, true)); ?>> <?php echo esc_html(self::language_flag($language) . ' ' . self::language_label($language)); ?></label><?php endforeach; ?></div>
                    <p class="description">Ces cases décrivent les langues réellement disponibles dans le PDF. Les noms publics des langues se modifient dans « Contenus & traductions ».</p>
                </div>

                <div class="htp-1178-block">
                    <h3>Titre et description du document</h3>
                    <div class="htp-1178-translation-grid">
                        <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $language=>$label) : ?>
                            <fieldset><legend><?php echo esc_html($label); ?></legend><label><span>Titre</span><input type="text" data-guide-title="<?php echo esc_attr($language); ?>" name="<?php echo esc_attr($base . '[title][' . $language . ']'); ?>" value="<?php echo esc_attr(is_array($guide['title']) ? ($guide['title'][$language] ?? '') : ''); ?>"></label><label><span>Description</span><textarea rows="3" name="<?php echo esc_attr($base . '[description][' . $language . ']'); ?>"><?php echo esc_textarea(is_array($guide['description']) ? ($guide['description'][$language] ?? '') : ''); ?></textarea></label></fieldset>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="htp-1178-block">
                    <h3>Fichiers et visuel</h3>
                    <div class="htp-1178-fields htp-1178-fields-2">
                        <label><span>PDF</span><div class="htp-1178-media-field"><input type="url" name="<?php echo esc_attr($base . '[pdf_url]'); ?>" value="<?php echo esc_attr($guide['pdf_url']); ?>"><button type="button" class="button" data-media-field="pdf">Choisir</button></div></label>
                        <label><span>Couverture</span><div class="htp-1178-media-field"><input type="url" name="<?php echo esc_attr($base . '[cover_url]'); ?>" value="<?php echo esc_attr($guide['cover_url']); ?>"><button type="button" class="button" data-media-field="image">Choisir</button></div><small>Taille recommandée : 900 × 1200 px — format vertical 3:4.</small></label>
                    </div>
                </div>

                <div class="htp-1178-guide-actions"><button type="button" class="button-link-delete" data-remove-guide>Supprimer ce guide</button></div>
            </div>
        </details>
        <?php
    }

    private static function appearance_panel($year) {
        if (!class_exists('Parcs_HT_Guide_Appearance')) return;
        $settings = Parcs_HT_Guide_Appearance::settings();
        ?>
        <section class="htp-1178-card">
            <div class="htp-1178-card-head"><div><h2>Apparence des guides</h2><p>Les réglages visuels historiques sont conservés. La 1.17.8 ne force pas de raccordement au socle global afin d’éviter une régression visuelle.</p></div></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_guide_appearance">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_guide_appearance'); ?>
                <div class="htp-1178-fields htp-1178-fields-2 htp-1178-appearance-main">
                    <?php self::appearance_field('primary_button_background', 'Fond du bouton principal', $settings['primary_button_background']); ?>
                    <?php self::appearance_field('primary_button_text', 'Texte du bouton principal', $settings['primary_button_text']); ?>
                </div>
                <details class="htp-1178-advanced">
                    <summary>Réglages avancés d’apparence</summary>
                    <p class="description">À modifier seulement si le rendu du thème l’exige. Les valeurs <code>inherit</code> et <code>transparent</code> restent compatibles avec les réglages historiques.</p>
                    <div class="htp-1178-fields htp-1178-fields-3">
                        <?php self::appearance_field('card_background', 'Fond de la carte', $settings['card_background'], true); ?>
                        <?php self::appearance_field('text_color', 'Couleur du texte', $settings['text_color'], true); ?>
                        <?php self::appearance_field('title_color', 'Couleur des titres', $settings['title_color'], true); ?>
                        <?php self::appearance_field('category_color', 'Couleur catégorie / niveau', $settings['category_color'], true); ?>
                        <?php self::appearance_field('secondary_button_color', 'Bouton secondaire', $settings['secondary_button_color'], true); ?>
                    </div>
                </details>
                <?php submit_button('Enregistrer l’apparence', 'secondary', 'submit', false); ?>
            </form>
        </section>
        <?php
    }

    private static function appearance_field($key, $label, $value, $allow_special = false) {
        $type = preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? 'color' : 'text';
        ?><label class="htp-1178-appearance-field"><span><?php echo esc_html($label); ?></span><div><input type="<?php echo esc_attr($type); ?>" name="appearance[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>"><?php if ($allow_special) : ?><small>Hex, inherit ou transparent</small><?php endif; ?></div></label><?php
    }
}
