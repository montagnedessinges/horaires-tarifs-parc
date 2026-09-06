<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Admin {
    const PAGE = 'parcs-horaires-tarifs';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save', array(__CLASS__, 'save'));
        add_action('admin_post_parcs_ht_check_updates', array(__CLASS__, 'check_updates'));
        add_action('admin_post_parcs_ht_add_season', array(__CLASS__, 'add_season'));
        add_action('admin_post_parcs_ht_duplicate_season', array(__CLASS__, 'duplicate_season'));
        add_action('admin_post_parcs_ht_delete_season', array(__CLASS__, 'delete_season'));
        add_action('admin_post_parcs_ht_restore_revision', array(__CLASS__, 'restore_revision'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
    }

    public static function menu() {
        add_menu_page(
            'Horaires et tarifs du parc',
            'Horaires du parc',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page'),
            'dashicons-calendar-alt',
            31
        );
    }

    public static function assets($hook) {
        if ($hook !== 'toplevel_page_' . self::PAGE) {
            return;
        }
        wp_enqueue_style('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-preview-engine', PARCS_HT_URL . 'assets/frontend.js', array(), PARCS_HT_VERSION, true);
        $preview_year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’aperçu en lecture seule ; aucun enregistrement.
        wp_add_inline_script('parcs-ht-preview-engine', 'window.ParcsHTPData=' . wp_json_encode(array('settings'=>Parcs_HT_Schedule::public_settings(Parcs_HT_Defaults::settings($preview_year)),'dictionary'=>Parcs_HT_Schedule::dictionaries())) . ';', 'before');
        wp_enqueue_script('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.js', array('parcs-ht-preview-engine','jquery-ui-sortable'), PARCS_HT_VERSION, true);
        wp_add_inline_script(
            'parcs-ht-admin',
            'window.ParcsHTAdmin=' . wp_json_encode(array('language' => 'fr')) . ';',
            'before'
        );
    }

    public static function page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $requested_year = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sélection d’aperçu en lecture seule ; aucun enregistrement.
        $settings = Parcs_HT_Defaults::settings($requested_year);
        $warnings = self::warnings($settings);
        $audit = Parcs_HT_Schedule::audit_season($settings);
        $runtime_errors = get_option('parcs_ht_runtime_errors', array());
        if (is_array($runtime_errors)) $runtime_errors = array_filter($runtime_errors, static function ($error) { return time() - (int)($error['time'] ?? 0) <= 7 * DAY_IN_SECONDS; });
        $all_settings = Parcs_HT_Defaults::all_settings();
        $active_year = isset($settings['active_season_year']) ? $settings['active_season_year'] : '';
        ?>
        <div class="wrap htp-admin">
            <h1>Horaires et tarifs du parc</h1>
            <p class="description">La même extension peut être installée sur chaque site. Cette installation ne contient que les données de ce parc.</p>

            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?>
                <div class="notice notice-success is-dismissible"><p>Les réglages ont été enregistrés et le cache du site a été purgé.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['preserved'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?>
                <div class="notice notice-warning is-dismissible"><p>Une partie du formulaire n’a pas été reçue complètement par WordPress/PHP. Les anciennes valeurs de la section concernée ont été conservées pour éviter toute perte d’horaires ou de tarifs.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['update-check'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?>
                <div class="notice notice-info is-dismissible"><p>La vérification des mises à jour WordPress et GitHub vient d’être relancée.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['restored'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?>
                <div class="notice notice-success is-dismissible"><p>La configuration sélectionnée a été restaurée et les caches ont été invalidés.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['duplicated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Paramètre de présentation en lecture seule ; aucune modification de données. */ ?>
                <div class="notice notice-success is-dismissible"><p>La nouvelle saison brouillon a été créée et toutes les dates ont été décalées automatiquement. Vérifiez-la dans le diagnostic avant de la publier.</p></div>
            <?php endif; ?>

            <?php if ($warnings) : ?>
                <div class="notice notice-warning"><p><strong>Points à vérifier :</strong></p><ul><?php foreach ($warnings as $warning) : ?><li><?php echo esc_html($warning); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if (is_array($runtime_errors) && $runtime_errors) : ?>
                <div class="notice notice-error"><p><strong>Erreurs techniques récentes :</strong></p><ul><?php foreach (array_slice($runtime_errors, 0, 5) as $error) : ?><li><?php echo esc_html((string)($error['message'] ?? 'Erreur inconnue')); ?><?php if (!empty($error['time'])) echo ' — '.esc_html(wp_date('d/m/Y H:i', (int)$error['time'])); ?></li><?php endforeach; ?></ul><p>Les détails sont aussi disponibles dans <a href="<?php echo esc_url(admin_url('site-health.php')); ?>">Santé du site</a>.</p></div>
            <?php endif; ?>

            <section class="htp-card htp-season-manager">
                <h2>Saisons</h2>
                <p>Une année n’apparaît sur le site que si vous l’avez créée et publiée.</p>
                <div class="htp-season-tabs">
                    <?php foreach ($all_settings['seasons'] as $year => $season) : ?>
                        <div class="htp-season-item">
                            <a class="button <?php echo $year === $active_year ? 'button-primary' : ''; ?>" href="<?php echo esc_url(add_query_arg(array('page'=>self::PAGE,'season'=>$year), admin_url('admin.php'))); ?>"><?php echo esc_html($year); ?><?php echo ((string)($season['published'] ?? '0') === '1') ? '' : ' · brouillon'; ?></a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-inline-form"><input type="hidden" name="action" value="parcs_ht_duplicate_season"><input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>"><?php wp_nonce_field('parcs_ht_duplicate_season_' . $year); ?><button type="submit" class="button button-small" title="Créer une saison brouillon en décalant automatiquement toutes les dates">Préparer l’année suivante</button></form>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-inline-form htp-delete-season-form"><input type="hidden" name="action" value="parcs_ht_delete_season"><input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>"><?php wp_nonce_field('parcs_ht_delete_season_' . $year); ?><button type="submit" class="button button-small button-link-delete">Supprimer</button></form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="htp-add-season-form">
                    <input type="hidden" name="action" value="parcs_ht_add_season">
                    <?php wp_nonce_field('parcs_ht_add_season'); ?>
                    <label class="htp-field"><span>Nouvelle année</span><input type="number" min="2020" max="2100" name="season_year" placeholder="2027" required></label>
                    <button type="submit" class="button">Ajouter une saison</button>
                </form>
            </section>

            <nav class="htp-section-nav nav-tab-wrapper" role="tablist" aria-label="Sections de l’extension" data-htp-admin-tabs>
                <button type="button" class="nav-tab nav-tab-active htp-admin-tab" role="tab" aria-selected="true" data-htp-admin-tab="htp-general">Parc & apparence</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-regular">Horaires & calendrier</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-holidays">Périodes & événements</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-domain">Accès limité</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-exceptions">Exceptions</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-alerts">Pop-up</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-tariffs">Tarifs</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-quote">Devis groupe</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-preview">Aperçu</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-updates">Mises à jour</button>
                <button type="button" class="nav-tab htp-admin-tab" role="tab" aria-selected="false" data-htp-admin-tab="htp-shortcodes">Shortcodes</button>
            </nav>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($active_year); ?>">
                <input type="hidden" name="htp_active_tab" value="htp-general" data-htp-active-tab-input>
                <?php wp_nonce_field('parcs_ht_save'); ?>

                <?php self::general_section($settings); ?>
                <?php self::regular_section($settings); ?>
                <?php self::holidays_section($settings); ?>
                <?php self::domain_section($settings); ?>
                <?php self::exceptions_section($settings); ?>
                <?php self::alerts_section($settings); ?>
                <?php self::tariffs_section($settings); ?>
                <?php self::quote_section($settings); ?>
                <?php self::preview_section($audit); ?>
                <?php self::updates_section($settings); ?>
                <?php self::shortcodes_section(); ?>

                <div class="htp-sticky-save"><?php submit_button('Enregistrer cet onglet', 'primary', 'htp_save_active', false); ?> <?php submit_button('Enregistrer tous les réglages', 'secondary', 'htp_save_all', false); ?></div>
            </form>
        </div>
        <?php
    }

    private static function general_section($settings) {
        $g = $settings['general'];
        ?>
        <section id="htp-general" class="htp-card">
            <h2>Parc, saison et liens</h2>
            <div class="htp-grid htp-grid-3">
                <?php self::translated_input('settings[general][park_name]', $g['park_name'], 'Nom du parc'); ?>
                <label class="htp-field"><span>Année de la saison</span><input type="text" value="<?php echo esc_attr($g['year']); ?>" readonly></label>
                <label class="htp-field"><span>Publication</span><span><?php self::checkbox('settings[general][published]', isset($g['published']) ? $g['published'] : '0', 'Afficher cette saison sur le site'); ?></span></label>
                <?php self::input('settings[general][block_spacing]', $g['block_spacing'] ?? '8', 'Espacement vertical entre shortcodes (px)', 'number'); ?>
                <label class="htp-field"><span>Bordure générale des blocs</span><span><?php self::checkbox('settings[general][block_border_enabled]', $g['block_border_enabled'] ?? '0', 'Afficher une bordure autour des blocs'); ?></span></label>
                <?php self::input('settings[general][last_entry_minutes]', $g['last_entry_minutes'], 'Dernière entrée — minutes avant fermeture', 'number'); ?>
                <?php self::input('settings[general][season_start]', $g['season_start'], 'Début de saison', 'date'); ?>
                <?php self::input('settings[general][season_end]', $g['season_end'], 'Fin de saison', 'date'); ?>
                <label class="htp-field"><span>Fuseau horaire</span><select name="settings[timezone]"><?php echo function_exists('wp_timezone_choice') ? wp_timezone_choice($settings['timezone'] ?? 'Europe/Paris', get_user_locale()) : '<option value="Europe/Paris">Europe/Paris</option>'; ?></select></label>
                <?php self::translated_input('settings[general][tickets_url]', $g['tickets_url'], 'Lien du bouton Acheter les billets', 'url'); ?>
                <?php self::input('settings[general][primary_color]', $g['primary_color'], 'Couleur principale', 'color'); ?>
                <?php self::input('settings[general][secondary_color]', $g['secondary_color'], 'Couleur secondaire', 'color'); ?>
                <?php self::input('settings[general][accent_color]', $g['accent_color'], 'Couleur des exceptions', 'color'); ?>
                <?php self::input('settings[general][highlight_color]', $g['highlight_color'], 'Couleur de mise en valeur', 'color'); ?>
            </div>
            <h3>Surveillance et notifications</h3>
            <div class="htp-grid htp-grid-3">
                <label class="htp-field"><span>Alertes automatiques</span><span><?php self::checkbox('settings[general][health_notifications_enabled]', $g['health_notifications_enabled'] ?? '1', 'Envoyer un e-mail si le planning contient une erreur ou un nouveau conflit'); ?></span></label>
                <?php self::input('settings[general][health_notification_email]', $g['health_notification_email'] ?? '', 'Adresse e-mail (vide = administrateur WordPress)', 'email'); ?>
            </div>
            <p class="description">Les problèmes identiques sont regroupés et ne sont pas renvoyés pendant sept jours afin d’éviter les e-mails répétés.</p>
            <h3>Apparence générale</h3>
            <p>Ces trois couleurs servent uniquement de valeurs globales. Les couleurs propres aux horaires, jours fériés, alertes et tarifs se règlent directement dans leurs sections respectives.</p>
            <div class="htp-grid htp-grid-3">
                <?php self::optional_color_input('settings[general][body_text_color]', $g['body_text_color'] ?? '', 'Texte principal (vide = thème)'); ?>
                <?php self::optional_color_input('settings[general][heading_text_color]', $g['heading_text_color'] ?? '', 'Titres généraux (vide = thème)'); ?>
                <?php self::optional_color_input('settings[general][border_color]', $g['border_color'] ?? '', 'Bordures générales (vide = thème)'); ?>
            </div>
            <h3>Lisibilité / taille des textes</h3>
            <p>Choisissez simplement un niveau de lecture. Les réglages en pixels restent disponibles dans « Réglages avancés » uniquement si vous en avez besoin.</p>
            <div class="htp-grid htp-grid-3">
                <?php self::select('settings[general][font_profile]', $g['font_profile'] ?? 'inherit', 'Profil de typographie', array('inherit'=>'Hériter du thème','caltons'=>'Optimisé pour Caltons Typeface','custom'=>'Personnalisé')); ?>
                <?php self::select('settings[general][typography_preset]', $g['typography_preset'] ?? 'standard', 'Taille générale', array('compact'=>'Compacte','standard'=>'Standard','large'=>'Grande lecture')); ?>
                <?php self::select('settings[general][calendar_detail_preset]', $g['calendar_detail_preset'] ?? 'large', 'Date sélectionnée du calendrier', array('standard'=>'Standard','large'=>'Grande','xlarge'=>'Très grande')); ?>
                <?php self::select('settings[general][calendar_mobile_size]', $g['calendar_mobile_size'] ?? 'medium', 'Taille de la grille sur mobile', array('small'=>'Compacte','medium'=>'Standard','large'=>'Grande')); ?>
            </div>
            <p class="description"><strong>Hériter du thème</strong> conserve la typographie du site. <strong>Caltons</strong> agrandit légèrement les titres et resserre leur interligne, sans charger ni imposer la police. <strong>Personnalisé</strong> utilise les réglages avancés ci-dessous.</p>
            <details class="htp-advanced">
                <summary>Réglages avancés en pixels</summary>
                <p class="description">Facultatif. Une valeur vide laisse le préréglage ci-dessus décider de la taille.</p>
                <div class="htp-grid htp-grid-4">
                    <?php self::input('settings[general][font_body_size]', $g['font_body_size'] ?? '', 'Texte courant (px)', 'number'); ?>
                    <?php self::input('settings[general][font_heading_size]', $g['font_heading_size'] ?? '', 'Titres généraux (px)', 'number'); ?>
                    <?php self::input('settings[general][font_kicker_size]', $g['font_kicker_size'] ?? '', 'Petits titres (px)', 'number'); ?>
                    <?php self::input('settings[general][font_button_size]', $g['font_button_size'] ?? '', 'Boutons (px)', 'number'); ?>
                    <?php self::input('settings[general][font_today_title_size]', $g['font_today_title_size'] ?? '', 'Aujourd’hui — surtitre (px)', 'number'); ?>
                    <?php self::input('settings[general][font_today_status_size]', $g['font_today_status_size'] ?? '', 'Aujourd’hui — OUVERT/FERMÉ (px)', 'number'); ?>
                    <?php self::input('settings[general][font_today_detail_size]', $g['font_today_detail_size'] ?? '', 'Aujourd’hui — horaire (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_title_size]', $g['font_calendar_title_size'] ?? '', 'Calendrier — titre (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_month_size]', $g['font_calendar_month_size'] ?? '', 'Calendrier — mois (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_summary_size]', $g['font_calendar_summary_size'] ?? '', 'Horaires du mois (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_weekday_size]', $g['font_calendar_weekday_size'] ?? '', 'Jours L/M/M… (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_day_size]', $g['font_calendar_day_size'] ?? '', 'Numéros des jours (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_detail_title_size]', $g['font_calendar_detail_title_size'] ?? '', 'Date sélectionnée (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_detail_hours_size]', $g['font_calendar_detail_hours_size'] ?? '', 'Horaire sélectionné (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_detail_last_size]', $g['font_calendar_detail_last_size'] ?? '', 'Dernière entrée (px)', 'number'); ?>
                    <?php self::input('settings[general][font_calendar_legend_size]', $g['font_calendar_legend_size'] ?? '', 'Légende (px)', 'number'); ?>
                    <?php self::input('settings[general][font_tariff_title_size]', $g['font_tariff_title_size'] ?? '', 'Tarifs — titre (px)', 'number'); ?>
                    <?php self::input('settings[general][font_tariff_tab_size]', $g['font_tariff_tab_size'] ?? '', 'Tarifs — onglets (px)', 'number'); ?>
                    <?php self::input('settings[general][font_tariff_label_size]', $g['font_tariff_label_size'] ?? '', 'Tarifs — libellés (px)', 'number'); ?>
                    <?php self::input('settings[general][font_tariff_detail_size]', $g['font_tariff_detail_size'] ?? '', 'Tarifs — précisions (px)', 'number'); ?>
                    <?php self::input('settings[general][font_tariff_note_size]', $g['font_tariff_note_size'] ?? '', 'Tarifs — texte secondaire (px)', 'number'); ?>
                    <?php self::input('settings[general][font_tariff_price_size]', $g['font_tariff_price_size'] ?? '', 'Tarifs — prix (px)', 'number'); ?>
                    <?php self::input('settings[general][font_payment_title_size]', $g['font_payment_title_size'] ?? '', 'Paiement — titre (px)', 'number'); ?>
                    <?php self::input('settings[general][font_payment_item_size]', $g['font_payment_item_size'] ?? '', 'Paiement — moyens (px)', 'number'); ?>
                    <?php self::input('settings[general][font_groups_note_size]', $g['font_groups_note_size'] ?? '', 'Groupes — messages (px)', 'number'); ?>
                    <?php self::input('settings[general][font_alert_title_size]', $g['font_alert_title_size'] ?? '', 'Pop-up — titre (px)', 'number'); ?>
                    <?php self::input('settings[general][font_alert_text_size]', $g['font_alert_text_size'] ?? '', 'Pop-up — message (px)', 'number'); ?>
                    <?php self::input('settings[general][font_alert_button_size]', $g['font_alert_button_size'] ?? '', 'Pop-up — bouton (px)', 'number'); ?>
                </div>
            </details>
            <input type="hidden" name="settings[_complete][general]" value="1">
        </section>
        <?php
    }

    private static function regular_section($settings) {
        $g = $settings['general'];
        ?>
        <section id="htp-regular" class="htp-card">
            <h2>Horaires habituels</h2>
            <p>Ajoutez autant de périodes que nécessaire. Les jours non couverts sont considérés comme fermés.</p>
            <div class="htp-repeater" data-template="htp-template-period">
                <div class="htp-repeater-rows">
                    <?php foreach ($settings['regular_periods'] as $index => $row) self::period_row($index, $row); ?>
                </div>
                <button type="button" class="button htp-add-row">Ajouter une période habituelle</button>
            </div>
            <script type="text/html" id="htp-template-period"><?php self::period_row('__INDEX__', array()); ?></script>

            <h3>Couleurs de l’affichage des horaires et du calendrier</h3>
            <p>Les couleurs laissées vides héritent du thème du site. Pour les jours ouverts, vous pouvez choisir un fond transparent ou un fond uni personnalisable.</p>
            <div class="htp-grid htp-grid-3">
                <?php self::optional_color_input('settings[general][today_title_color]', $g['today_title_color'] ?? '', 'Aujourd’hui — texte'); ?>
                <?php self::input('settings[general][today_title_bg_color]', $g['today_title_bg_color'] ?? '#ffffff', 'Aujourd’hui — fond', 'color'); ?>
                <label class="htp-field"><span>Aujourd’hui — fond transparent</span><span><?php self::checkbox('settings[general][today_title_bg_transparent]', $g['today_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
                <?php self::optional_color_input('settings[general][today_status_color]', $g['today_status_color'] ?? '#16843d', 'Statut OUVERT'); ?>
                <?php self::optional_color_input('settings[general][today_closed_color]', $g['today_closed_color'] ?? '#9b2c2c', 'Statut FERMÉ'); ?>
                <?php self::optional_color_input('settings[general][today_detail_color]', $g['today_detail_color'] ?? '', 'Dernière entrée / détail'); ?>
                <?php self::optional_color_input('settings[general][calendar_title_color]', $g['calendar_title_color'] ?? '', 'Calendrier — titre'); ?>
                <?php self::input('settings[general][calendar_title_bg_color]', $g['calendar_title_bg_color'] ?? '#ffffff', 'Calendrier — fond du titre', 'color'); ?>
                <label class="htp-field"><span>Calendrier — fond du titre transparent</span><span><?php self::checkbox('settings[general][calendar_title_bg_transparent]', $g['calendar_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
                <?php self::optional_color_input('settings[general][calendar_weekday_color]', $g['calendar_weekday_color'] ?? '', 'Jours de semaine'); ?>
                <?php self::input('settings[general][calendar_day_bg_color]', $g['calendar_day_bg_color'] ?? '#ffffff', 'Jours ouverts — fond', 'color'); ?>
                <label class="htp-field"><span>Jours ouverts — fond transparent</span><span><?php self::checkbox('settings[general][calendar_day_bg_transparent]', $g['calendar_day_bg_transparent'] ?? '0', 'Transparent'); ?></span></label>
                <?php self::input('settings[general][calendar_nav_bg_color]', $g['calendar_nav_bg_color'] ?? '#006757', 'Boutons des mois — fond', 'color'); ?>
                <?php self::input('settings[general][calendar_nav_text_color]', $g['calendar_nav_text_color'] ?? '#ffffff', 'Boutons des mois — texte', 'color'); ?>
                <?php self::input('settings[general][calendar_nav_active_bg_color]', $g['calendar_nav_active_bg_color'] ?? '#e7c55b', 'Mois actif — fond', 'color'); ?>
                <?php self::input('settings[general][calendar_nav_active_text_color]', $g['calendar_nav_active_text_color'] ?? '#27342f', 'Mois actif — texte', 'color'); ?>
                <?php self::optional_color_input('settings[general][calendar_detail_text_color]', $g['calendar_detail_text_color'] ?? '', 'Détail journée — texte'); ?>
                <?php self::optional_color_input('settings[general][calendar_detail_border_color]', $g['calendar_detail_border_color'] ?? '', 'Détail journée — bordure'); ?>
                <?php self::input('settings[general][calendar_closed_bg_color]', $g['calendar_closed_bg_color'] ?? '#e3e5e4', 'Jour fermé — fond', 'color'); ?>
                <?php self::input('settings[general][calendar_closed_text_color]', $g['calendar_closed_text_color'] ?? '#616765', 'Jour fermé — texte', 'color'); ?>
                <?php self::input('settings[general][calendar_selected_color]', $g['calendar_selected_color'] ?? '#006757', 'Jour sélectionné — contour', 'color'); ?>
            </div>
            <input type="hidden" name="settings[_complete][regular_periods]" value="1">
        </section>
        <?php
    }

    private static function period_row($index, $row) {
        $row = wp_parse_args($row, array('enabled' => '1', 'label' => '', 'start' => '', 'end' => '', 'weekdays' => array('1','2','3','4','5','6','7'), 'open' => '', 'close' => '', 'open2' => '', 'close2' => '', 'last_entry_minutes' => '', 'color' => '#9AAA8B'));
        $base = 'settings[regular_periods][' . $index . ']';
        ?>
        <div class="htp-repeat-row">
            <div class="htp-row-head"><strong>Période d’ouverture</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::input($base . '[label]', $row['label'], 'Nom interne'); ?>
                <?php self::input($base . '[start]', $row['start'], 'Du', 'date'); ?>
                <?php self::input($base . '[end]', $row['end'], 'Au', 'date'); ?>
                <?php self::input($base . '[open]', $row['open'], 'Ouverture', 'time'); ?>
                <?php self::input($base . '[close]', $row['close'], 'Fermeture créneau 1', 'time'); ?>
                <?php self::input($base . '[open2]', $row['open2'], 'Ouverture créneau 2 (facultatif)', 'time'); ?>
                <?php self::input($base . '[close2]', $row['close2'], 'Fermeture créneau 2 (facultatif)', 'time'); ?>
                <?php self::input($base . '[last_entry_minutes]', $row['last_entry_minutes'], 'Dernière entrée spécifique (minutes)', 'number'); ?>
                <?php self::input($base . '[color]', $row['color'], 'Couleur calendrier', 'color'); ?>
                <?php self::weekdays($base . '[weekdays]', $row['weekdays']); ?>
            </div>
        </div>
        <?php
    }

    private static function holidays_section($settings) {
        $g = $settings['general'];
        ?>
        <section id="htp-holidays" class="htp-card">
            <h2>Périodes repères & événements</h2>
            <p class="description">Les périodes repères servent à gérer les vacances scolaires ou autres périodes utiles. Elles peuvent rester internes ou être affichées sur le calendrier. Les vrais événements restent séparés.</p>

            <div class="htp-subsection">
                <h3>Périodes repères</h3>
                <p class="description">Vacances scolaires, grand week-end ou autre période de référence. Une période peut aussi servir uniquement à modifier une règle interne sans être visible au public.</p>
                <div class="htp-repeater" data-template="htp-template-special-period"><div class="htp-repeater-rows">
                    <?php foreach (($settings['special_periods'] ?? array()) as $index => $row) if (($row['kind'] ?? 'event') !== 'event') self::special_period_row($index, $row); ?>
                </div><button type="button" class="button htp-add-row" data-default-kind="other">Ajouter une période</button></div>
            </div>

            <div class="htp-subsection">
                <h3>Événements</h3>
                <p class="description">Un événement peut durer un jour ou plusieurs mois : concours photo, fête, animation, opération spéciale…</p>
                <div class="htp-repeater" data-template="htp-template-event"><div class="htp-repeater-rows">
                    <?php foreach (($settings['special_periods'] ?? array()) as $index => $row) if (($row['kind'] ?? 'event') === 'event') self::special_period_row($index, $row, true); ?>
                </div><button type="button" class="button htp-add-row" data-default-kind="event">Ajouter un événement</button></div>
            </div>

            <script type="text/html" id="htp-template-special-period"><?php self::special_period_row('__INDEX__', array('kind'=>'other')); ?></script>
            <script type="text/html" id="htp-template-event"><?php self::special_period_row('__INDEX__', array('kind'=>'event'), true); ?></script>

            <details class="htp-advanced">
                <summary>Jours fériés & apparence avancée</summary>
                <div class="htp-advanced-content">
                    <h3>Jours fériés ou journées particulières</h3>
                    <div class="htp-repeater" data-template="htp-template-public-holiday"><div class="htp-repeater-rows">
                        <?php foreach ($settings['public_holidays'] as $index => $row) self::public_holiday_row($index, $row); ?>
                    </div><button type="button" class="button htp-add-row">Ajouter une journée</button></div>
                    <script type="text/html" id="htp-template-public-holiday"><?php self::public_holiday_row('__INDEX__', array()); ?></script>
                    <div class="htp-check-list">
                        <?php self::checkbox('settings[general][show_public_holidays]', $g['show_public_holidays'] ?? '0', 'Afficher les jours fériés sur le calendrier'); ?>
                    </div>
                    <div class="htp-grid htp-grid-3">
                        <?php self::input('settings[general][holiday_border_color]', $g['holiday_border_color'] ?? '#e7c55b', 'Couleur du cadre', 'color'); ?>
                        <?php self::input('settings[general][holiday_border_width]', $g['holiday_border_width'] ?? '3', 'Épaisseur du cadre', 'number'); ?>
                        <?php self::translated_input('settings[general][holiday_message]', $g['holiday_message'] ?? array('fr'=>'Jour férié','en'=>'Public holiday','de'=>'Feiertag'), 'Message après clic'); ?>
                    </div>
                    <?php self::translated_input('settings[general][period_legend_label]', $g['period_legend_label'] ?? array('fr'=>'Période spécifique','en'=>'Special period','de'=>'Besonderer Zeitraum'), 'Libellé de la légende période'); ?>
                    <?php self::translated_input('settings[general][event_legend_label]', $g['event_legend_label'] ?? array('fr'=>'Événement','en'=>'Event','de'=>'Veranstaltung'), 'Libellé de la légende événement'); ?>
                </div>
            </details>
            <input type="hidden" name="settings[_complete][holidays]" value="1">
        </section>
        <?php
    }

    private static function special_period_row($index, $row, $force_event = false) {
        $row = wp_parse_args($row, array(
            'enabled'=>'1','kind'=>$force_event?'event':'other','internal_label'=>'','title'=>array('fr'=>'','en'=>'','de'=>''),
            'start'=>'','end'=>'','color'=>'#e7c55b','icon'=>'star','display_mode'=>'spot','message'=>array('fr'=>'','en'=>'','de'=>''),
            'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>'1','skip_domain_rules'=>'0',
            'show_popup'=>'0','popup_lead_mode'=>'days_before','popup_days_before'=>'14','popup_start'=>'','popup_end'=>'',
            'popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),
            'popup_button_label'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_url'=>array('fr'=>'','en'=>'','de'=>''),'popup_show_button'=>'0','popup_image_url'=>''
        ));
        if ($force_event) $row['kind'] = 'event';
        $is_event = ($row['kind'] === 'event');
        $base = 'settings[special_periods][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-special-row" data-htp-special-kind="<?php echo esc_attr($row['kind']); ?>">
            <?php if ($is_event): ?><input type="hidden" name="<?php echo esc_attr($base . '[kind]'); ?>" value="event"><?php endif; ?>
            <div class="htp-row-head"><strong><?php echo $is_event ? 'Événement' : 'Période spécifique'; ?></strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::input($base . '[internal_label]', $row['internal_label'], 'Nom interne'); ?>
                <?php self::input($base . '[start]', $row['start'], 'Du', 'date'); ?>
                <?php self::input($base . '[end]', $row['end'], 'Au', 'date'); ?>
                <?php if ($is_event) { ?>
                    <input type="hidden" name="<?php echo esc_attr($base . '[icon]'); ?>" value="star">
                    <input type="hidden" name="<?php echo esc_attr($base . '[color]'); ?>" value="#e7c55b">
                    <?php self::select($base . '[display_mode]', $row['display_mode'], 'Affichage dans le calendrier', array('spot'=>'Événement ponctuel — pastille jaune visible','long'=>'Événement long — point jaune discret')); ?>
                <?php } else {
                    self::select($base . '[kind]', $row['kind'], 'Type de période', array('school_holiday'=>'Vacances scolaires','other'=>'Autre période repère'));
                    self::input($base . '[color]', $row['color'], 'Couleur du bandeau', 'color');
                } ?>
            </div>
            <div class="htp-check-list"><?php self::checkbox($base . '[show_on_calendar]', $row['show_on_calendar'], $is_event ? 'Afficher le marqueur de cet événement dans le calendrier' : 'Afficher cette période sur le calendrier'); ?><?php if (!$is_event) self::checkbox($base . '[skip_domain_rules]', $row['skip_domain_rules'], 'Ignorer « Accès temporairement limité » pendant cette période'); ?></div>
            <details class="htp-row-details" <?php echo $is_event ? 'open' : ''; ?>>
                <summary>Contenu affiché au public</summary>
                <div class="htp-grid htp-grid-2 htp-details-content">
                    <?php self::translated_input($base . '[title]', $row['title'], $is_event ? 'Nom de l’événement' : 'Nom affiché au clic'); ?>
                    <?php self::translated_textarea($base . '[message]', $row['message'], 'Message (facultatif)'); ?>
                    <div class="htp-button-block" data-htp-button-block>
                        <div class="htp-check-list"><?php self::checkbox($base . '[show_button]', $row['show_button'], 'Afficher un bouton'); ?></div>
                        <div class="htp-button-settings" data-htp-button-settings <?php echo $row['show_button']==='1'?'':'hidden'; ?>>
                            <?php self::translated_input($base . '[button_label]', $row['button_label'], 'Texte du bouton'); ?>
                            <?php self::translated_input($base . '[button_url]', $row['button_url'], 'Lien du bouton', 'url'); ?>
                        </div>
                    </div>
                </div>
            </details>
            <div class="htp-popup-block" data-htp-popup-block>
                <div class="htp-check-list htp-popup-toggle"><?php self::checkbox($base . '[show_popup]', $row['show_popup'], 'Activer le pop-up'); ?></div>
                <div class="htp-popup-settings" data-htp-popup-settings <?php echo $row['show_popup']==='1'?'':'hidden'; ?>>
                    <div class="htp-grid htp-grid-4">
                        <?php self::select($base . '[popup_lead_mode]', $row['popup_lead_mode'], 'Début d’affichage', array('same'=>'Au début','days_before'=>'X jours avant','custom'=>'Date/heure personnalisée')); ?>
                        <?php self::input($base . '[popup_days_before]', $row['popup_days_before'], 'Jours avant', 'number'); ?>
                        <?php self::input($base . '[popup_start]', $row['popup_start'], 'Début personnalisé', 'datetime-local'); ?>
                        <?php self::input($base . '[popup_end]', $row['popup_end'], 'Fin personnalisée', 'datetime-local'); ?>
                        <?php self::input($base . '[popup_image_url]', $row['popup_image_url'], 'Image du pop-up (URL, facultatif)', 'url'); ?>
                    </div>
                    <p class="description htp-popup-image-help"><strong>Taille recommandée : 800 × 450 px – format 16:9.</strong> L’image sera automatiquement adaptée aux ordinateurs et aux mobiles.</p>
                    <div class="htp-grid htp-grid-2">
                        <?php self::translated_input($base . '[popup_title]', $row['popup_title'], 'Titre du pop-up'); ?>
                        <?php self::translated_textarea($base . '[popup_message]', $row['popup_message'], 'Message du pop-up'); ?>
                        <div class="htp-button-block" data-htp-button-block>
                            <div class="htp-check-list"><?php self::checkbox($base . '[popup_show_button]', $row['popup_show_button'], 'Afficher un bouton dans le pop-up'); ?></div>
                            <div class="htp-button-settings" data-htp-button-settings <?php echo $row['popup_show_button']==='1'?'':'hidden'; ?>>
                                <?php self::translated_input($base . '[popup_button_label]', $row['popup_button_label'], 'Texte du bouton'); ?>
                                <?php self::translated_input($base . '[popup_button_url]', $row['popup_button_url'], 'Lien du bouton', 'url'); ?>
                            </div>
                        </div>
                    </div>
                    <?php self::popup_preview_controls(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private static function public_holiday_row($index, $row) {
        $row = wp_parse_args($row, array('enabled' => '1', 'label' => '', 'date' => ''));
        $base = 'settings[public_holidays][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-compact-row"><div class="htp-row-head"><strong>Journée particulière</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div><div class="htp-grid htp-grid-2">
            <?php self::input($base . '[label]', $row['label'], 'Nom interne'); self::input($base . '[date]', $row['date'], 'Date', 'date'); ?>
        </div></div>
        <?php
    }

    private static function domain_section($settings) {
        ?>
        <section id="htp-domain" class="htp-card">
            <h2>Accès temporairement limité</h2>
            <p>Module générique pour une zone dont l’accès peut être temporairement interrompu. Rien n’indique que le parc entier est fermé.</p>
            <div class="htp-repeater" data-template="htp-template-domain"><div class="htp-repeater-rows">
                <?php foreach ($settings['domain_rules'] as $index => $row) self::domain_row($index, $row); ?>
            </div><button type="button" class="button htp-add-row">Ajouter une règle d’accès</button></div>
            <script type="text/html" id="htp-template-domain"><?php self::domain_row('__INDEX__', array()); ?></script>
            <input type="hidden" name="settings[_complete][domain_rules]" value="1">
        </section>
        <?php
    }

    private static function domain_row($index, $row) {
        $row = wp_parse_args($row, array('enabled' => '0', 'label' => '', 'public_title' => array('fr'=>'Accès temporairement limité','en'=>'Temporarily limited access','de'=>'Vorübergehend eingeschränkter Zugang'), 'start' => '', 'end' => '', 'weekdays' => array('1','2','3','4','5'), 'pause_start' => '', 'resume' => '', 'last_entry' => '', 'exclude_weekends' => '0', 'exclude_school_holidays' => '0', 'exclude_public_holidays' => '0', 'auto_details' => '1', 'show_tooltip' => '1', 'tooltip_text' => array('fr'=>'Cette interruption temporaire permet à nos équipes de prendre leur pause. Le reste du parc reste accessible pendant ce temps.','en'=>'This temporary interruption allows our teams to take their break. The rest of the park remains accessible during this time.','de'=>'Diese vorübergehende Unterbrechung ermöglicht unserem Team eine Pause. Der übrige Park bleibt während dieser Zeit zugänglich.'), 'info' => array('fr'=>'','en'=>'','de'=>'')));
        $base = 'settings[domain_rules][' . $index . ']';
        ?>
        <div class="htp-repeat-row">
            <div class="htp-row-head"><strong>Règle d’accès</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::input($base . '[label]', $row['label'], 'Nom interne'); self::input($base . '[start]', $row['start'], 'Du', 'date'); self::input($base . '[end]', $row['end'], 'Au', 'date'); ?>
                <?php self::translated_input($base . '[public_title]', $row['public_title'], 'Titre public'); ?>
                <?php self::input($base . '[pause_start]', $row['pause_start'], 'Interruption à', 'time'); self::input($base . '[resume]', $row['resume'], 'Reprise à', 'time'); self::input($base . '[last_entry]', $row['last_entry'], 'Dernière entrée avant interruption', 'time'); ?>
                <?php self::weekdays($base . '[weekdays]', $row['weekdays']); ?>
                <div class="htp-check-list">
                    <?php self::checkbox($base . '[exclude_weekends]', $row['exclude_weekends'], 'Ne pas appliquer le week-end'); ?>
                    <?php self::checkbox($base . '[exclude_school_holidays]', $row['exclude_school_holidays'], 'Ne pas appliquer pendant les vacances saisies'); ?>
                    <?php self::checkbox($base . '[exclude_public_holidays]', $row['exclude_public_holidays'], 'Ne pas appliquer les jours fériés saisis'); ?>
                </div>
            </div>
            <div class="htp-check-list"><?php self::checkbox($base . '[auto_details]', $row['auto_details'], 'Afficher automatiquement les horaires d’interruption / reprise'); ?></div>
            <div class="htp-domain-tooltip-admin" data-htp-domain-tooltip-block>
                <div class="htp-check-list">
                    <?php self::checkbox($base . '[show_tooltip]', $row['show_tooltip'], 'Afficher le petit point d’explication « ! » à côté du titre'); ?>
                </div>
                <div class="htp-domain-tooltip-settings" data-htp-domain-tooltip-settings <?php echo $row['show_tooltip']==='1'?'':'hidden'; ?>>
                    <?php self::translated_textarea($base . '[tooltip_text]', $row['tooltip_text'], 'Texte de la bulle d’information'); ?>
                    <p class="description">Ce texte explique la raison de l’interruption. Il s’affiche uniquement après un clic ou un toucher sur le petit « ! ».</p>
                </div>
            </div>
            <?php self::translated_textarea($base . '[info]', $row['info'], 'Message complémentaire facultatif'); ?>
        </div>
        <?php
    }

    private static function exceptions_section($settings) {
        ?>
        <section id="htp-exceptions" class="htp-card">
            <h2>Horaires et fermetures exceptionnels</h2>
            <p>Priorité : fermeture exceptionnelle, puis horaires exceptionnels, puis horaires habituels. À priorité égale, une fermeture l’emporte.</p>
            <div class="htp-repeater" data-template="htp-template-exception"><div class="htp-repeater-rows">
                <?php foreach ($settings['exceptions'] as $index => $row) self::exception_row($index, $row); ?>
            </div><button type="button" class="button htp-add-row">Ajouter une exception</button></div>
            <script type="text/html" id="htp-template-exception"><?php self::exception_row('__INDEX__', array()); ?></script>
            <input type="hidden" name="settings[_complete][exceptions]" value="1">
        </section>
        <?php
    }

    private static function exception_row($index, $row) {
        $row = wp_parse_args($row, array('enabled' => '1', 'type' => 'hours', 'label' => '', 'start' => '', 'end' => '', 'open' => '', 'close' => '', 'open2' => '', 'close2' => '', 'last_entry_minutes' => '', 'priority' => '100', 'apply_domain_rules' => '1', 'show_public_marker' => '1', 'context' => array('fr'=>'','en'=>'','de'=>''), 'title' => array('fr'=>'','en'=>'','de'=>''), 'message' => array('fr'=>'','en'=>'','de'=>''), 'show_popup' => '0', 'popup_show_dates' => '1', 'popup_show_hours' => '1', 'popup_mode' => 'auto', 'popup_title' => array('fr'=>'','en'=>'','de'=>''), 'popup_message' => array('fr'=>'','en'=>'','de'=>''), 'popup_button_label' => array('fr'=>'','en'=>'','de'=>''), 'popup_button_url' => array('fr'=>'','en'=>'','de'=>''), 'popup_show_button' => '0', 'popup_lead_mode'=>'days_before','popup_days_before'=>'1','popup_start'=>'','popup_end'=>''));
        $base = 'settings[exceptions][' . $index . ']';
        ?>
        <div class="htp-repeat-row">
            <div class="htp-row-head"><strong>Exception</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::select($base . '[type]', $row['type'], 'Type', array('hours'=>'Horaires exceptionnels','closed'=>'Fermeture exceptionnelle')); ?>
                <?php self::input($base . '[label]', $row['label'], 'Nom interne'); self::input($base . '[start]', $row['start'], 'Du', 'date'); self::input($base . '[end]', $row['end'], 'Au', 'date'); ?>
                <?php self::input($base . '[open]', $row['open'], 'Ouverture créneau 1', 'time'); self::input($base . '[close]', $row['close'], 'Fermeture créneau 1', 'time'); self::input($base . '[open2]', $row['open2'], 'Ouverture créneau 2 (facultatif)', 'time'); self::input($base . '[close2]', $row['close2'], 'Fermeture créneau 2 (facultatif)', 'time'); self::input($base . '[last_entry_minutes]', $row['last_entry_minutes'], 'Dernière entrée spécifique (minutes)', 'number'); self::input($base . '[priority]', $row['priority'], 'Priorité', 'number'); ?>
            </div>
            <div class="htp-check-list">
                <?php self::checkbox($base . '[apply_domain_rules]', $row['apply_domain_rules'], 'Prendre en compte « Accès temporairement limité »'); ?>
                <?php self::checkbox($base . '[show_public_marker]', $row['show_public_marker'], 'Afficher le pictogramme / la mention « Horaire exceptionnel » au public'); ?>
            </div>
            <div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[context]', $row['context'], 'Contexte / motif facultatif'); self::translated_input($base . '[title]', $row['title'], 'Titre public facultatif'); self::translated_textarea($base . '[message]', $row['message'], 'Message public facultatif'); ?></div>
            <div class="htp-popup-block" data-htp-popup-block>
                <div class="htp-check-list htp-popup-toggle"><?php self::checkbox($base . '[show_popup]', $row['show_popup'], 'Activer le pop-up'); ?></div>
                <div class="htp-popup-settings" data-htp-popup-settings <?php echo $row['show_popup']==='1'?'':'hidden'; ?>>
                    <p class="description">Le pop-up reprend automatiquement le contexte / motif, le titre public et le message public saisis ci-dessus. Vous choisissez seulement si les dates et les horaires doivent être ajoutés.</p>
                    <div class="htp-check-list">
                        <?php self::checkbox($base . '[popup_show_dates]', $row['popup_show_dates'], 'Afficher les dates dans le pop-up'); ?>
                        <?php self::checkbox($base . '[popup_show_hours]', $row['popup_show_hours'], 'Afficher les horaires dans le pop-up'); ?>
                    </div>
                    <div class="htp-grid htp-grid-4">
                        <?php self::select($base . '[popup_lead_mode]', $row['popup_lead_mode'], 'Début d’affichage', array('same'=>'Au début de l’exception','days_before'=>'X jours avant','custom'=>'Date/heure personnalisée')); ?>
                        <?php self::input($base . '[popup_days_before]', $row['popup_days_before'], 'Jours avant', 'number'); ?>
                        <?php self::input($base . '[popup_start]', $row['popup_start'], 'Début personnalisé', 'datetime-local'); ?>
                        <?php self::input($base . '[popup_end]', $row['popup_end'], 'Fin personnalisée', 'datetime-local'); ?>
                    </div>
                    <div class="htp-button-block" data-htp-button-block>
                        <div class="htp-check-list"><?php self::checkbox($base . '[popup_show_button]', $row['popup_show_button'], 'Afficher un bouton dans le pop-up'); ?></div>
                        <div class="htp-button-settings" data-htp-button-settings <?php echo $row['popup_show_button']==='1'?'':'hidden'; ?>>
                            <div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[popup_button_label]', $row['popup_button_label'], 'Texte du bouton'); self::translated_input($base . '[popup_button_url]', $row['popup_button_url'], 'Lien du bouton', 'url'); ?></div>
                        </div>
                    </div>
                    <?php self::popup_preview_controls(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private static function alerts_section($settings) {
        $g = $settings['general'];
        ?>
        <section id="htp-alerts" class="htp-card">
            <h2>Alertes et pop-up automatique</h2>
            <p>Préparez une alerte en brouillon, testez-la dans l’administration, puis publiez-la. Une alerte publiée peut se réafficher après le délai choisi lorsqu’un visiteur l’a fermée.</p>
            <div class="htp-repeater" data-template="htp-template-alert"><div class="htp-repeater-rows">
                <?php foreach ($settings['alerts'] as $index => $row) self::alert_row($index, $row); ?>
            </div><button type="button" class="button htp-add-row">Ajouter une alerte</button></div>
            <script type="text/html" id="htp-template-alert"><?php self::alert_row('__INDEX__', array()); ?></script>

            <h3>Apparence du pop-up</h3>
            <p class="description">Ces couleurs s’appliquent au pop-up automatique et à la prévisualisation. Les textes FR / EN / DE restent gérés dans chaque alerte.</p>
            <div class="htp-grid htp-grid-3">
                <?php self::input('settings[general][alert_bg_color]', $g['alert_bg_color'] ?? '#006757', 'Fenêtre — fond', 'color'); ?>
                <?php self::input('settings[general][alert_title_color]', $g['alert_title_color'] ?? '#ffffff', 'Titre — texte', 'color'); ?>
                <?php self::input('settings[general][alert_text_color]', $g['alert_text_color'] ?? '#ffffff', 'Message — texte', 'color'); ?>
                <?php self::input('settings[general][alert_border_color]', $g['alert_border_color'] ?? '#ef7b5b', 'Fenêtre — bordure', 'color'); ?>
                <?php self::input('settings[general][alert_border_width]', $g['alert_border_width'] ?? '3', 'Bordure — épaisseur (px)', 'number'); ?>
                <?php self::input('settings[general][alert_radius]', $g['alert_radius'] ?? '16', 'Angles arrondis (px)', 'number'); ?>
                <?php self::input('settings[general][alert_button_bg_color]', $g['alert_button_bg_color'] ?? '#ef7b5b', 'Bouton — fond', 'color'); ?>
                <?php self::input('settings[general][alert_button_text_color]', $g['alert_button_text_color'] ?? '#ffffff', 'Bouton — texte', 'color'); ?>
                <?php self::input('settings[general][alert_button_border_color]', $g['alert_button_border_color'] ?? '#ef7b5b', 'Bouton — bordure', 'color'); ?>
                <?php self::input('settings[general][alert_close_bg_color]', $g['alert_close_bg_color'] ?? '#ffffff', 'Fermer × — fond', 'color'); ?>
                <?php self::input('settings[general][alert_close_text_color]', $g['alert_close_text_color'] ?? '#222222', 'Fermer × — couleur', 'color'); ?>
                <?php self::input('settings[general][alert_overlay_color]', $g['alert_overlay_color'] ?? '#000000', 'Arrière-plan écran — couleur', 'color'); ?>
                <?php self::input('settings[general][alert_overlay_opacity]', $g['alert_overlay_opacity'] ?? '68', 'Arrière-plan écran — opacité (%)', 'number'); ?>
                <?php self::input('settings[general][alert_reappear_hours]', $g['alert_reappear_hours'] ?? '1', 'Réafficher après fermeture (heures)', 'number'); ?>
                <label class="htp-field"><span>Ombre</span><span><?php self::checkbox('settings[general][alert_shadow]', $g['alert_shadow'] ?? '1', 'Afficher une ombre'); ?></span></label>
            </div>
            <input type="hidden" name="settings[_complete][alerts]" value="1">
        </section>
        <?php
    }

    private static function alert_row($index, $row) {
        $row = wp_parse_args($row, array('enabled' => '1', 'published' => '', 'start' => '', 'end' => '', 'title' => array('fr'=>'','en'=>'','de'=>''), 'message' => array('fr'=>'','en'=>'','de'=>''), 'button_label' => array('fr'=>'','en'=>'','de'=>''), 'button_url' => array('fr'=>'','en'=>'','de'=>''), 'show_button' => '0'));
        if ($row['published'] === '') $row['published'] = $row['enabled'];
        $base = 'settings[alerts][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-alert-row" data-htp-alert-row>
            <div class="htp-row-head"><strong>Alerte</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::select($base . '[published]', $row['published'], 'Statut', array('0'=>'Brouillon','1'=>'Publiée')); ?>
                <?php self::input($base . '[start]', $row['start'], 'Début', 'datetime-local'); ?>
                <?php self::input($base . '[end]', $row['end'], 'Fin automatique facultative', 'datetime-local'); ?>
            </div>
            <div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[title]', $row['title'], 'Titre'); self::translated_textarea($base . '[message]', $row['message'], 'Message'); ?></div>
            <div class="htp-button-block" data-htp-button-block>
                <div class="htp-check-list"><?php self::checkbox($base . '[show_button]', $row['show_button'], 'Afficher un bouton'); ?></div>
                <div class="htp-button-settings" data-htp-button-settings <?php echo $row['show_button']==='1'?'':'hidden'; ?>>
                    <div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[button_label]', $row['button_label'], 'Texte du bouton'); self::translated_input($base . '[button_url]', $row['button_url'], 'Lien du bouton', 'url'); ?></div>
                </div>
            </div>
            <?php self::popup_preview_controls('Prévisualiser sans publier :'); ?>
        </div>
        <?php
    }

    private static function popup_preview_controls($label = 'Tester le pop-up :') {
        ?>
        <div class="htp-popup-preview-actions">
            <span><?php echo esc_html($label); ?></span>
            <label class="screen-reader-text">Langue de prévisualisation</label>
            <select class="htp-popup-preview-language" data-htp-popup-preview-language aria-label="Langue de prévisualisation">
                <option value="fr">FR</option>
                <option value="en">EN</option>
                <option value="de">DE</option>
            </select>
            <button type="button" class="button button-secondary" data-htp-popup-test>Tester le pop-up</button>
        </div>
        <?php
    }

    private static function tariffs_section($settings) {
        $tariffs = $settings['tariffs'];
        $g = $settings['general'];
        $group_titles = array('individual'=>'Individuels','reduced'=>'Tarifs réduits','groups'=>'Groupes');
        $order = isset($tariffs['group_order']) && is_array($tariffs['group_order']) ? $tariffs['group_order'] : array_keys($group_titles);
        $order = array_values(array_unique(array_filter($order, static function($key) use ($group_titles) { return isset($group_titles[$key]); })));
        foreach (array_keys($group_titles) as $key) if (!in_array($key, $order, true)) $order[] = $key;
        ?>
        <section id="htp-tariffs" class="htp-card">
            <h2>Tableau des tarifs</h2>
            <p>Vous pouvez maintenant réorganiser les onglets tarifaires, les lignes et les colonnes. Utilisez la poignée ↕ pour glisser-déposer, ou les flèches ↑ ↓ si vous préférez.</p>


            <?php $print = isset($tariffs['print']) && is_array($tariffs['print']) ? $tariffs['print'] : array(); ?>
            <details class="htp-advanced htp-print-settings">
                <summary>Impression / PDF des tarifs</summary>
                <div class="htp-advanced-content">
                    <p class="description">Le document est généré automatiquement à partir des tarifs actuellement publiés. Aucune mise en page PDF manuelle n’est nécessaire.</p>
                    <div class="htp-grid htp-grid-2">
                        <label class="htp-field"><span>Téléchargement PDF</span><span><?php self::checkbox('settings[tariffs][print][pdf_enabled]', $print['pdf_enabled'] ?? '1', 'Afficher le lien discret « Télécharger les tarifs en PDF »'); ?></span></label>
                        <?php self::translated_input('settings[tariffs][print][title]', $print['title'] ?? array('fr'=>'','en'=>'','de'=>''), 'Titre du document (vide = Tarifs + année)'); ?>
                        <?php self::translated_textarea('settings[tariffs][print][footer]', $print['footer'] ?? array('fr'=>'','en'=>'','de'=>''), 'Texte en bas du document'); ?>
                        <label class="htp-field"><span>Date de génération</span><span><?php self::checkbox('settings[tariffs][print][show_generation_date]', $print['show_generation_date'] ?? '1', 'Afficher la date de génération'); ?></span></label>
                        <?php self::select('settings[tariffs][print][orientation]', $print['orientation'] ?? 'portrait', 'Format du PDF', array('portrait'=>'A4 portrait','landscape'=>'A4 paysage')); ?>
                    </div>
                </div>
            </details>

            <h3>Informations et réservation des groupes</h3>
            <p>Ces textes sont entièrement modifiables et peuvent être traduits champ par champ. Ils ne sont jamais imposés par l’extension.</p>
            <div class="htp-grid htp-grid-2">
                <?php self::translated_textarea('settings[general][groups_booking_note]', $g['groups_booking_note'] ?? array('fr'=>'','en'=>'','de'=>''), 'Message affiché dans l’onglet Groupes'); ?>
                <?php self::translated_input('settings[general][groups_button_label]', $g['groups_button_label'] ?? array('fr'=>'','en'=>'','de'=>''), 'Texte du bouton de demande de devis'); ?>
                <?php self::translated_input('settings[general][groups_url]', $g['groups_url'] ?? array('fr'=>'','en'=>'','de'=>''), 'Lien du bouton Demande de devis', 'url'); ?>
                <?php self::input('settings[general][groups_email]', $g['groups_email'] ?? '', 'E-mail de contact groupes', 'email'); ?>
                <?php self::translated_textarea('settings[general][groups_closed_note]', $g['groups_closed_note'] ?? array('fr'=>'','en'=>'','de'=>''), 'Message supplémentaire lorsque le parc est fermé'); ?>
            </div>

            <details class="htp-advanced">
                <summary>Apparence des tarifs</summary>
                <div class="htp-advanced-content">
                    <div class="htp-grid htp-grid-3">
                        <?php self::optional_color_input('settings[general][tariff_kicker_color]', $g['tariff_kicker_color'] ?? '', 'Petit titre TARIFS — texte'); ?>
                        <?php self::optional_color_input('settings[general][tariff_title_color]', $g['tariff_title_color'] ?? '', 'Titre Tarifs — texte'); ?>
                        <?php self::input('settings[general][tariff_title_bg_color]', $g['tariff_title_bg_color'] ?? '#ffffff', 'Titre Tarifs — fond', 'color'); ?>
                        <label class="htp-field"><span>Titre Tarifs — fond transparent</span><span><?php self::checkbox('settings[general][tariff_title_bg_transparent]', $g['tariff_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
                        <?php self::optional_color_input('settings[general][payment_title_color]', $g['payment_title_color'] ?? '', 'Moyens de paiement — titre'); ?>
                        <?php self::input('settings[general][payment_title_bg_color]', $g['payment_title_bg_color'] ?? '#ffffff', 'Moyens de paiement — fond du titre', 'color'); ?>
                        <label class="htp-field"><span>Moyens de paiement — fond du titre transparent</span><span><?php self::checkbox('settings[general][payment_title_bg_transparent]', $g['payment_title_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
                        <?php self::input('settings[general][payment_item_bg_color]', $g['payment_item_bg_color'] ?? '#006757', 'Pictogrammes — fond', 'color'); ?>
                        <?php self::input('settings[general][payment_item_text_color]', $g['payment_item_text_color'] ?? '#ffffff', 'Pictogrammes — texte', 'color'); ?>
                        <?php self::input('settings[general][payment_icon_color]', $g['payment_icon_color'] ?? '#ffffff', 'Pictogrammes — icône', 'color'); ?>
                        <?php self::optional_color_input('settings[general][payment_border_color]', $g['payment_border_color'] ?? '', 'Moyens de paiement — bordure'); ?>
                        <label class="htp-field"><span>Bordure du bloc paiement</span><span><?php self::checkbox('settings[general][payment_border_enabled]', $g['payment_border_enabled'] ?? '0', 'Afficher la bordure'); ?></span></label>
                        <?php self::input('settings[general][tab_bg_color]', $g['tab_bg_color'] ?? '#006757', 'Onglets — fond', 'color'); ?>
                        <?php self::input('settings[general][tab_text_color]', $g['tab_text_color'] ?? '#ffffff', 'Onglets — texte'); ?>
                        <?php self::input('settings[general][tab_active_bg_color]', $g['tab_active_bg_color'] ?? '#e7c55b', 'Onglet actif — fond', 'color'); ?>
                        <?php self::input('settings[general][tab_active_text_color]', $g['tab_active_text_color'] ?? '#27342f', 'Onglet actif — texte'); ?>
                        <?php self::optional_color_input('settings[general][panel_text_color]', $g['panel_text_color'] ?? '', 'Panneaux — texte par défaut'); ?>
                        <?php self::optional_color_input('settings[general][panel_border_color]', $g['panel_border_color'] ?? '', 'Panneaux — bordure'); ?>
                        <label class="htp-field"><span>Bordure des panneaux tarifaires</span><span><?php self::checkbox('settings[general][panel_border_enabled]', $g['panel_border_enabled'] ?? '0', 'Afficher la bordure'); ?></span></label>
                        <?php self::input('settings[general][panel_bg_color]', $g['panel_bg_color'] ?? '#ffffff', 'Panneaux — fond', 'color'); ?>
                        <label class="htp-field"><span>Panneaux — fond transparent</span><span><?php self::checkbox('settings[general][panel_bg_transparent]', $g['panel_bg_transparent'] ?? '1', 'Transparent'); ?></span></label>
                        <?php self::optional_color_input('settings[general][price_color]', $g['price_color'] ?? '', 'Prix — couleur par défaut'); ?>
                        <?php self::optional_color_input('settings[general][tariff_note_text_color]', $g['tariff_note_text_color'] ?? '', 'Note tarifs réduits — texte'); ?>
                        <?php self::optional_color_input('settings[general][tariff_note_border_color]', $g['tariff_note_border_color'] ?? '', 'Note tarifs réduits — bordure'); ?>
                        <?php self::optional_color_input('settings[general][groups_note_text_color]', $g['groups_note_text_color'] ?? '', 'Message groupes — texte'); ?>
                        <?php self::optional_color_input('settings[general][groups_note_border_color]', $g['groups_note_border_color'] ?? '', 'Message groupes — bordure'); ?>
                        <?php self::input('settings[general][button_bg_color]', $g['button_bg_color'] ?? '#006757', 'Bouton devis — fond', 'color'); ?>
                        <?php self::input('settings[general][button_text_color]', $g['button_text_color'] ?? '#ffffff', 'Bouton devis — texte', 'color'); ?>
                        <?php self::input('settings[general][primary_button_bg_color]', $g['primary_button_bg_color'] ?? '#ef7b5b', 'Bouton Acheter — fond', 'color'); ?>
                        <?php self::input('settings[general][primary_button_text_color]', $g['primary_button_text_color'] ?? '#ffffff', 'Bouton Acheter — texte', 'color'); ?>
                    </div>
                </div>
            </details>

            <div class="htp-tariff-groups" data-htp-tariff-groups>
            <?php foreach ($order as $group) :
                $title = $group_titles[$group];
                $columns = isset($tariffs['columns'][$group]) && is_array($tariffs['columns'][$group]) ? $tariffs['columns'][$group] : array();
                if (empty($columns)) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
            ?>
                <section class="htp-tariff-group" data-htp-tariff-group="<?php echo esc_attr($group); ?>">
                    <div class="htp-tariff-group-head">
                        <button type="button" class="button htp-sort-handle htp-sort-handle-group" title="Glisser pour déplacer" aria-label="Déplacer le bloc">↕</button>
                        <h3><?php echo esc_html($title); ?></h3>
                        <div class="htp-order-buttons">
                            <button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button>
                            <button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button>
                        </div>
                        <input type="hidden" name="settings[tariffs][group_order][]" value="<?php echo esc_attr($group); ?>">
                    </div>

                    <div class="htp-tariff-columns-manager">
                        <div class="htp-tariff-columns-title"><strong>Colonnes de prix</strong><span class="description">La colonne du nom du tarif reste toujours présente. Les colonnes ci-dessous peuvent être ajoutées, supprimées et réorganisées.</span></div>
                        <div class="htp-tariff-columns-list" data-htp-tariff-columns>
                            <?php foreach ($columns as $col_index => $column) self::tariff_column_row($group, $col_index, $column); ?>
                        </div>
                        <button type="button" class="button" data-htp-add-column>Ajouter une colonne</button>
                        <script type="text/html" class="htp-template-tariff-column"><?php self::tariff_column_row($group, '__COLINDEX__', array('id'=>'__COLID__','label'=>array('fr'=>'Nouvelle colonne','en'=>'New column','de'=>'Neue Spalte'))); ?></script>
                    </div>

                    <div class="htp-repeater htp-tariff-repeater" data-template="htp-template-tariff-<?php echo esc_attr($group); ?>" data-htp-tariff-row-repeater>
                        <div class="htp-repeater-rows">
                            <?php foreach ($tariffs[$group] as $index => $row) self::tariff_row($group, $index, $row, $columns); ?>
                        </div>
                        <button type="button" class="button htp-add-row">Ajouter une ligne</button>
                    </div>
                    <script type="text/html" id="htp-template-tariff-<?php echo esc_attr($group); ?>"><?php self::tariff_row($group, '__INDEX__', array(), $columns); ?></script>
                </section>
            <?php endforeach; ?>
            </div>

            <div class="htp-grid htp-grid-2">
                <?php self::translated_textarea('settings[tariffs][notes]', $tariffs['notes'], 'Précisions et justificatifs des tarifs réduits'); ?>
            </div>
            <?php self::payment_items_field(isset($tariffs['payment_items']) ? $tariffs['payment_items'] : array()); ?>
            <input type="hidden" name="settings[_complete][tariffs]" value="1">
        </section>
        <?php
    }

    private static function quote_section($settings) {
        $q = isset($settings['quote_page']) && is_array($settings['quote_page']) ? $settings['quote_page'] : array();
        $q = array_replace_recursive(array('enabled'=>'0','form_shortcode'=>'','title'=>array(),'intro'=>array(),'form_title'=>array(),'quick_links_title'=>array(),'quick_links_intro'=>array(),'important_messages'=>array(),'quick_links'=>array(),'info_blocks'=>array(),'accordions'=>array()), $q);
        ?>
        <section id="htp-quote" class="htp-card">
            <h2>Devis groupe</h2>
            <p class="description">L’extension habille la page autour de votre formulaire existant. Contact Form 7 reste inchangé : collez simplement son shortcode ici, puis placez <code>[parc_devis_groupe]</code> dans la page WordPress. <code>[parc_devis]</code> reste compatible.</p>
            <div class="htp-grid htp-grid-2">
                <div class="htp-field"><span>Shortcode de la page</span><p class="description"><code>[parc_devis_groupe]</code> suffit pour afficher le module. Il n’y a plus d’interrupteur d’activation séparé.</p></div>
                <?php self::input('settings[quote_page][form_shortcode]', $q['form_shortcode'], 'Shortcode du formulaire Contact Form 7'); ?>
                <?php
                $cf7_shortcode = isset($q['form_shortcode']) ? trim((string)$q['form_shortcode']) : '';
                $cf7_active = shortcode_exists('contact-form-7');
                $cf7_format_ok = $cf7_shortcode !== '' && preg_match('/^\\[contact-form-7(?:\\s+[^\\]]*)?\\s*\\/?\\]$/i', $cf7_shortcode);
                $cf7_render_ok = null;
                if ($cf7_active && $cf7_format_ok) {
                    $cf7_test_html = function_exists('apply_shortcodes') ? apply_shortcodes($cf7_shortcode) : do_shortcode($cf7_shortcode);
                    $cf7_test_html = is_string($cf7_test_html) ? trim($cf7_test_html) : '';
                    $cf7_render_ok = ($cf7_test_html !== '' && strpos($cf7_test_html, '[contact-form-7') === false);
                }
                ?>
                <div class="htp-field htp-cf7-status">
                    <span>Diagnostic formulaire</span>
                    <p class="description">
                        <?php if (!$cf7_active) : ?>
                            <strong style="color:#b32d2e">Contact Form 7 n’est pas actif ou son shortcode n’est pas chargé.</strong>
                        <?php elseif (!$cf7_format_ok) : ?>
                            <strong style="color:#b32d2e">Le shortcode enregistré n’est pas reconnu comme un shortcode Contact Form 7 valide.</strong>
                        <?php elseif ($cf7_render_ok === false) : ?>
                            <strong style="color:#b32d2e">Contact Form 7 reconnaît le shortcode, mais ne produit pas le formulaire.</strong>
                        <?php else : ?>
                            <strong style="color:#008a20">Contact Form 7 actif — shortcode reconnu — formulaire rendu.</strong>
                        <?php endif; ?>
                    </p>
                </div>
                <?php self::translated_input('settings[quote_page][title]', $q['title'], 'Titre de la page'); ?>
                <?php self::translated_input('settings[quote_page][form_title]', $q['form_title'], 'Titre au-dessus du formulaire'); ?>
            </div>
            <?php self::translated_textarea('settings[quote_page][intro]', $q['intro'], 'Introduction courte'); ?>

            <div class="htp-subsection">
                <h3>Messages importants avant le formulaire</h3>
                <p class="description">Informations à lire avant de faire une demande. Couleur personnalisable, textes FR / EN / DE et ordre par glisser-déposer.</p>
                <div class="htp-repeater htp-quote-repeater" data-template="htp-template-quote-important"><div class="htp-repeater-rows htp-quote-sortable" data-htp-quote-sortable>
                    <?php foreach ($q['important_messages'] as $index=>$row) self::quote_important_row($index, $row); ?>
                </div><button type="button" class="button htp-add-row">Ajouter un message important</button></div>
                <script type="text/html" id="htp-template-quote-important"><?php self::quote_important_row('__INDEX__', array()); ?></script>
            </div>

            <div class="htp-subsection">
                <h3>Préparer votre visite — liens rapides</h3>
                <p class="description">Boutons visibles avant le formulaire pour renvoyer vers les pages utiles. Les libellés et liens sont modifiables en FR / EN / DE.</p>
                <?php self::translated_input('settings[quote_page][quick_links_title]', $q['quick_links_title'], 'Titre du bloc'); ?>
                <?php self::translated_textarea('settings[quote_page][quick_links_intro]', $q['quick_links_intro'], 'Texte court'); ?>
                <div class="htp-repeater htp-quote-repeater" data-template="htp-template-quote-quick-link"><div class="htp-repeater-rows htp-quote-sortable" data-htp-quote-sortable>
                    <?php foreach ($q['quick_links'] as $index=>$row) self::quote_quick_link_row($index, $row); ?>
                </div><button type="button" class="button htp-add-row">Ajouter un lien rapide</button></div>
                <script type="text/html" id="htp-template-quote-quick-link"><?php self::quote_quick_link_row('__INDEX__', array()); ?></script>
            </div>

            <div class="htp-subsection">
                <h3>Blocs complémentaires</h3>
                <p class="description">Facultatif : informations visibles avant ou après le formulaire. Les messages importants et liens rapides ci-dessus suffisent dans la plupart des cas.</p>
                <div class="htp-repeater htp-quote-repeater" data-template="htp-template-quote-info"><div class="htp-repeater-rows htp-quote-sortable" data-htp-quote-sortable>
                    <?php foreach ($q['info_blocks'] as $index=>$row) self::quote_info_row($index, $row); ?>
                </div><button type="button" class="button htp-add-row">Ajouter un bloc d’information</button></div>
                <script type="text/html" id="htp-template-quote-info"><?php self::quote_info_row('__INDEX__', array()); ?></script>
            </div>

            <div class="htp-subsection">
                <h3>Accordéons</h3>
                <p class="description">Pour les explications secondaires : paiement, facturation, accessibilité, préparation de la visite, etc.</p>
                <div class="htp-repeater htp-quote-repeater" data-template="htp-template-quote-accordion"><div class="htp-repeater-rows htp-quote-sortable" data-htp-quote-sortable>
                    <?php foreach ($q['accordions'] as $index=>$row) self::quote_accordion_row($index, $row); ?>
                </div><button type="button" class="button htp-add-row">Ajouter un accordéon</button></div>
                <script type="text/html" id="htp-template-quote-accordion"><?php self::quote_accordion_row('__INDEX__', array()); ?></script>
            </div>
            <input type="hidden" name="settings[_complete][quote_page]" value="1">
        </section>
        <?php
    }

    private static function quote_important_row($index, $row) {
        $row = wp_parse_args($row, array('enabled'=>'1','color'=>'#ef7658','position'=>'before','title'=>array('fr'=>'','en'=>'','de'=>''),'text'=>array('fr'=>'','en'=>'','de'=>'')));
        $base='settings[quote_page][important_messages]['.$index.']'; ?>
        <div class="htp-repeat-row htp-quote-row">
            <div class="htp-row-head"><span class="htp-sort-handle-quote" title="Déplacer">⋮⋮</span><strong>Message important</strong><?php self::enabled($base.'[enabled]',$row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-2">
                <?php self::translated_input($base.'[title]',$row['title'],'Titre'); ?>
                <label class="htp-field"><span>Position</span><select name="<?php echo esc_attr($base.'[position]'); ?>"><option value="before" <?php selected($row['position'],'before'); ?>>Au-dessus du formulaire</option><option value="after" <?php selected($row['position'],'after'); ?>>Sous le formulaire</option></select></label>
            </div>
            <div class="htp-grid htp-grid-2">
                <label class="htp-field"><span>Couleur du repère</span><input type="color" name="<?php echo esc_attr($base.'[color]'); ?>" value="<?php echo esc_attr(sanitize_hex_color($row['color']) ?: '#ef7658'); ?>"></label>
                <div class="htp-field"><span>Ordre</span><p class="description">Faites glisser ce bloc avec ⋮⋮ pour modifier son ordre dans sa zone.</p></div>
            </div>
            <?php self::translated_textarea($base.'[text]',$row['text'],'Texte court'); ?>
        </div><?php
    }

    private static function quote_quick_link_row($index, $row) {
        $row = wp_parse_args($row, array('enabled'=>'1','icon'=>'','label'=>array('fr'=>'','en'=>'','de'=>''),'url'=>array('fr'=>'','en'=>'','de'=>'')));
        $base='settings[quote_page][quick_links]['.$index.']'; ?>
        <div class="htp-repeat-row htp-quote-row">
            <div class="htp-row-head"><span class="htp-sort-handle-quote" title="Déplacer">⋮⋮</span><strong>Lien rapide</strong><?php self::enabled($base.'[enabled]',$row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-2">
                <label class="htp-field"><span>Picto / emoji</span><input type="text" maxlength="8" name="<?php echo esc_attr($base.'[icon]'); ?>" value="<?php echo esc_attr($row['icon']); ?>" placeholder="🕒"></label>
                <?php self::translated_input($base.'[label]',$row['label'],'Texte du bouton'); ?>
            </div>
            <?php self::translated_input($base.'[url]',$row['url'],'Lien','url'); ?>
        </div><?php
    }

    private static function quote_info_row($index, $row) {
        $row = wp_parse_args($row, array('enabled'=>'1','title'=>array('fr'=>'','en'=>'','de'=>''),'text'=>array('fr'=>'','en'=>'','de'=>''),'position'=>'before','show_button'=>'0','button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>'')));
        $base='settings[quote_page][info_blocks]['.$index.']'; ?>
        <div class="htp-repeat-row htp-quote-row">
            <div class="htp-row-head"><span class="htp-sort-handle-quote" title="Déplacer">⋮⋮</span><strong>Bloc d’information</strong><?php self::enabled($base.'[enabled]',$row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-2">
                <?php self::translated_input($base.'[title]',$row['title'],'Titre'); ?>
                <?php self::select($base.'[position]',$row['position'],'Position',array('before'=>'Avant le formulaire','after'=>'Après le formulaire')); ?>
            </div>
            <?php self::translated_textarea($base.'[text]',$row['text'],'Texte court'); ?>
            <div class="htp-grid htp-grid-2"><label class="htp-field"><span>Bouton</span><span><?php self::checkbox($base.'[show_button]',$row['show_button'],'Afficher un bouton dans ce bloc'); ?></span></label><?php self::translated_input($base.'[button_label]',$row['button_label'],'Texte du bouton'); ?></div>
            <?php self::translated_input($base.'[button_url]',$row['button_url'],'Lien du bouton','url'); ?>
        </div><?php
    }

    private static function quote_accordion_row($index, $row) {
        $row = wp_parse_args($row, array('enabled'=>'1','title'=>array('fr'=>'','en'=>'','de'=>''),'text'=>array('fr'=>'','en'=>'','de'=>''),'position'=>'after','show_button'=>'0','button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>'')));
        $base='settings[quote_page][accordions]['.$index.']'; ?>
        <div class="htp-repeat-row htp-quote-row">
            <div class="htp-row-head"><span class="htp-sort-handle-quote" title="Déplacer">⋮⋮</span><strong>Accordéon</strong><?php self::enabled($base.'[enabled]',$row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-2">
                <?php self::translated_input($base.'[title]',$row['title'],'Titre'); ?>
                <?php self::select($base.'[position]',$row['position'],'Position',array('before'=>'Avant le formulaire','after'=>'Après le formulaire')); ?>
            </div>
            <?php self::translated_textarea($base.'[text]',$row['text'],'Contenu'); ?>
            <div class="htp-grid htp-grid-2"><label class="htp-field"><span>Bouton</span><span><?php self::checkbox($base.'[show_button]',$row['show_button'],'Afficher un bouton dans cet accordéon'); ?></span></label><?php self::translated_input($base.'[button_label]',$row['button_label'],'Texte du bouton'); ?></div>
            <?php self::translated_input($base.'[button_url]',$row['button_url'],'Lien du bouton','url'); ?>
        </div><?php
    }

    private static function tariff_column_row($group, $index, $column) {
        $column = wp_parse_args($column, array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
        $raw_id = isset($column['id']) ? (string)$column['id'] : '';
        $id = $raw_id === '__COLID__' ? '__COLID__' : sanitize_key($raw_id);
        if ($id === '') $id = 'price';
        $base = 'settings[tariffs][columns][' . $group . '][' . $index . ']';
        ?>
        <div class="htp-tariff-column-row" data-htp-tariff-column data-col-id="<?php echo esc_attr($id); ?>">
            <button type="button" class="button button-small htp-sort-handle htp-sort-handle-column" title="Glisser pour déplacer" aria-label="Déplacer la colonne">↕</button>
            <input type="hidden" name="<?php echo esc_attr($base . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" data-htp-column-id-input>
            <div class="htp-tariff-column-label"><?php self::translated_input($base . '[label]', $column['label'], 'Nom de la colonne'); ?></div>
            <div class="htp-order-buttons">
                <button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button>
                <button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button>
            </div>
            <button type="button" class="button-link-delete" data-htp-remove-column>Supprimer</button>
        </div>
        <?php
    }


    private static function payment_items_field($items) {
        $items = is_array($items) ? $items : array();
        ?>
        <div class="htp-payment-admin">
            <h3>Moyens de paiement</h3>
            <p class="description">Chaque moyen est indépendant : nom traduisible, langues d’affichage, pictogramme et couleurs. Vous pouvez en ajouter ou en supprimer.</p>
            <div class="htp-repeater" data-template="htp-template-payment"><div class="htp-repeater-rows">
                <?php foreach ($items as $index => $row) self::payment_item_row($index, $row); ?>
            </div><button type="button" class="button htp-add-row">Ajouter un moyen de paiement</button></div>
            <script type="text/html" id="htp-template-payment"><?php self::payment_item_row('__INDEX__', array()); ?></script>
        </div>
        <?php
    }

    private static function payment_item_row($index, $row) {
        $row = wp_parse_args($row, array('enabled'=>'1','icon'=>'card','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'','en'=>'','de'=>''),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'));
        $base = 'settings[tariffs][payment_items][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-payment-admin-row">
            <div class="htp-row-head"><strong>Moyen de paiement</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-3">
                <?php self::select($base . '[icon]', $row['icon'], 'Pictogramme', array('card'=>'Carte bancaire (SVG intégré)','cash'=>'Espèces (SVG intégré)','custom'=>'SVG personnalisé','none'=>'Aucun pictogramme')); ?>
                <?php self::translated_input($base . '[label]', $row['label'], 'Nom affiché'); ?>
                <label class="htp-field htp-svg-field"><span>SVG personnalisé</span><textarea rows="5" name="<?php echo esc_attr($base . '[custom_svg]'); ?>" placeholder="<svg viewBox=...>...</svg>"><?php echo esc_textarea($row['custom_svg']); ?></textarea><small>Utilisé uniquement avec « SVG personnalisé ». Le code est nettoyé avant enregistrement ; scripts, liens et attributs dangereux sont supprimés.</small></label>
                <label class="htp-field"><span>Couleur du SVG personnalisé</span><span><?php self::checkbox($base . '[custom_svg_force_color]', $row['custom_svg_force_color'], 'Forcer la couleur choisie ci-dessous'); ?></span></label>
                <fieldset class="htp-field"><span>Afficher dans les langues</span><div class="htp-lang-visibility"><?php foreach(array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$lab): ?><label><input type="hidden" name="<?php echo esc_attr($base . '[visible][' . $lang . ']'); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($base . '[visible][' . $lang . ']'); ?>" value="1" <?php checked(isset($row['visible'][$lang]) ? $row['visible'][$lang] : '0', '1'); ?>> <?php echo esc_html($lab); ?></label><?php endforeach; ?></div></fieldset>
                <?php self::input($base . '[bg_color]', $row['bg_color'], 'Fond', 'color'); ?>
                <label class="htp-field"><span>Fond transparent</span><span><?php self::checkbox($base . '[bg_transparent]', $row['bg_transparent'], 'Transparent'); ?></span></label>
                <?php self::input($base . '[icon_color]', $row['icon_color'], 'Pictogramme', 'color'); ?>
                <?php self::input($base . '[text_color]', $row['text_color'], 'Texte', 'color'); ?>
                <?php self::input($base . '[border_color]', $row['border_color'], 'Bordure', 'color'); ?>
                <label class="htp-field"><span>Bordure</span><span><?php self::checkbox($base . '[border_enabled]', $row['border_enabled'], 'Afficher'); ?></span></label>
            </div>
        </div>
        <?php
    }

    private static function tariff_row($group, $index, $row, $columns = array()) {
        $row = wp_parse_args($row, array(
            'enabled'=>'1','label'=>array('fr'=>'','en'=>'','de'=>''),'detail'=>array('fr'=>'','en'=>'','de'=>''),'subtitle'=>array('fr'=>'','en'=>'','de'=>''),'note'=>array('fr'=>'','en'=>'','de'=>''),'price'=>'','cells'=>array(),
            'row_type'=>'standard','special_badge'=>array('fr'=>'','en'=>'','de'=>''),'valid_from'=>'','valid_to'=>'','display_from'=>'','display_to'=>'','sale_channel'=>'both','purchase_url'=>array('fr'=>'','en'=>'','de'=>''),'show_special_dot'=>'1',
            'label_color'=>'','detail_color'=>'','subtitle_color'=>'','note_color'=>'','price_color'=>'','row_bg_color'=>'#ffffff','row_bg_transparent'=>'1','row_border_color'=>''
        ));
        if (empty($columns)) $columns = array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')));
        $base = 'settings[tariffs][' . $group . '][' . $index . ']';
        $is_special = $row['row_type'] === 'special';
        ?>
        <div class="htp-repeat-row htp-tariff-row" data-htp-tariff-row>
            <div class="htp-row-head">
                <button type="button" class="button button-small htp-sort-handle htp-sort-handle-row" title="Glisser pour déplacer" aria-label="Déplacer la ligne">↕</button>
                <strong>Ligne tarifaire</strong>
                <?php self::enabled($base . '[enabled]', $row['enabled']); ?>
                <div class="htp-order-buttons">
                    <button type="button" class="button button-small" data-htp-move="up" title="Monter">↑</button>
                    <button type="button" class="button button-small" data-htp-move="down" title="Descendre">↓</button>
                </div>
                <button type="button" class="button button-small" data-htp-duplicate-tariff-row>Dupliquer</button>
                <button type="button" class="button-link-delete htp-remove-row">Supprimer</button>
            </div>
            <div class="htp-grid htp-grid-3">
                <?php self::translated_input($base . '[label]', $row['label'], 'Libellé'); ?>
                <?php self::translated_input($base . '[subtitle]', isset($row['subtitle']) ? $row['subtitle'] : $row['detail'], 'Sous-titre / âge / précision'); ?>
                <?php self::translated_textarea($base . '[note]', isset($row['note']) ? $row['note'] : array('fr'=>'','en'=>'','de'=>''), 'Texte secondaire facultatif'); ?>
                <?php self::select($base . '[row_type]', $row['row_type'], 'Type de ligne', array('standard'=>'Tarif classique','special'=>'Offre / billet spécial')); ?>
            </div>

            <div class="htp-tariff-cell-grid" data-htp-tariff-cells>
                <?php foreach ($columns as $column) :
                    $col_id = sanitize_key($column['id'] ?? ''); if ($col_id === '') continue;
                    $cell = isset($row['cells'][$col_id]) && is_array($row['cells'][$col_id]) ? $row['cells'][$col_id] : array();
                    $value = isset($cell['value']) ? $cell['value'] : ($col_id === 'price' ? $row['price'] : '');
                    $old_value = isset($cell['old_value']) ? $cell['old_value'] : '';
                    $col_label = isset($column['label']['fr']) && $column['label']['fr'] !== '' ? $column['label']['fr'] : 'Prix';
                ?>
                    <div class="htp-tariff-cell-fields" data-htp-tariff-cell data-col-id="<?php echo esc_attr($col_id); ?>">
                        <strong class="htp-tariff-cell-title"><?php echo esc_html($col_label); ?></strong>
                        <?php self::input($base . '[cells][' . $col_id . '][value]', $value, 'Prix / valeur'); ?>
                        <div data-htp-old-price-field <?php echo $is_special ? '' : 'hidden'; ?>><?php self::input($base . '[cells][' . $col_id . '][old_value]', $old_value, 'Ancien prix à barrer (facultatif)'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="htp-special-offer-settings" data-htp-special-offer-settings <?php echo $is_special ? '' : 'hidden'; ?>>
                <h4>Billet / offre spéciale</h4>
                <p class="description">Exemple : Billet Juin – Adulte avec ancien prix barré, nouveau prix, période de validité et vente uniquement en ligne.</p>
                <div class="htp-grid htp-grid-3">
                    <label class="htp-field"><span>Repère visuel</span><span><?php self::checkbox($base . '[show_special_dot]', $row['show_special_dot'], 'Afficher un petit point devant le billet'); ?></span></label>
                    <?php self::translated_input($base . '[special_badge]', $row['special_badge'], 'Petit badge facultatif (ex. Offre web)'); ?>
                    <?php self::select($base . '[sale_channel]', $row['sale_channel'], 'Canal de vente', array('both'=>'En ligne + sur place','online'=>'En ligne uniquement','onsite'=>'Sur place uniquement')); ?>
                    <?php self::input($base . '[valid_from]', $row['valid_from'], 'Billet valable à partir du', 'date'); ?>
                    <?php self::input($base . '[valid_to]', $row['valid_to'], 'Billet valable jusqu’au', 'date'); ?>
                    <?php self::input($base . '[display_from]', $row['display_from'], 'Afficher l’offre à partir du (facultatif)', 'date'); ?>
                    <?php self::input($base . '[display_to]', $row['display_to'], 'Masquer l’offre après le (facultatif)', 'date'); ?>
                    <?php self::translated_input($base . '[purchase_url]', $row['purchase_url'], 'Lien d’achat spécifique (facultatif)', 'url'); ?>
                </div>
            </div>

            <details class="htp-row-details">
                <summary>Couleurs de cette ligne</summary>
                <div class="htp-details-content htp-grid htp-grid-3">
                    <?php self::optional_color_input($base . '[label_color]', $row['label_color'], 'Nom du tarif — couleur'); ?>
                    <?php self::optional_color_input($base . '[subtitle_color]', $row['subtitle_color'] ?? ($row['detail_color'] ?? ''), 'Sous-titre — couleur'); ?>
                    <?php self::optional_color_input($base . '[note_color]', $row['note_color'] ?? '', 'Texte secondaire — couleur'); ?>
                    <?php self::optional_color_input($base . '[price_color]', $row['price_color'], 'Prix — couleur'); ?>
                    <?php self::input($base . '[row_bg_color]', $row['row_bg_color'], 'Fond de la ligne', 'color'); ?>
                    <label class="htp-field"><span>Fond de la ligne transparent</span><span><?php self::checkbox($base . '[row_bg_transparent]', $row['row_bg_transparent'], 'Transparent'); ?></span></label>
                    <?php self::optional_color_input($base . '[row_border_color]', $row['row_border_color'], 'Séparateur / bordure'); ?>
                </div>
            </details>
        </div>
        <?php
    }


    private static function shortcodes_section() {
        $shortcodes = array(
            array('label'=>'Page complète','auto'=>'[parc_horaires_tarifs]','fr'=>'[parc_horaires_tarifs_fr]','en'=>'[parc_horaires_tarifs_en]','de'=>'[parc_horaires_tarifs_de]'),
            array('label'=>'Horaire du jour','auto'=>'[parc_horaires_aujourdhui]','fr'=>'[parc_horaires_aujourdhui_fr]','en'=>'[parc_horaires_aujourdhui_en]','de'=>'[parc_horaires_aujourdhui_de]'),
            array('label'=>'Calendrier interactif','auto'=>'[parc_calendrier]','fr'=>'[parc_calendrier_fr]','en'=>'[parc_calendrier_en]','de'=>'[parc_calendrier_de]'),
            array('label'=>'Tableau des tarifs','auto'=>'[parc_tableau_tarifs]','fr'=>'[parc_tableau_tarifs_fr]','en'=>'[parc_tableau_tarifs_en]','de'=>'[parc_tableau_tarifs_de]'),
            array('label'=>'Tarifs groupes uniquement','auto'=>'[parc_tarifs_groupes]','fr'=>'[parc_tarifs_groupes_fr]','en'=>'[parc_tarifs_groupes_en]','de'=>'[parc_tarifs_groupes_de]'),
            array('label'=>'Alerte de fermeture','auto'=>'[parc_fermeture_exceptionnelle]','fr'=>'[parc_fermeture_exceptionnelle_fr]','en'=>'[parc_fermeture_exceptionnelle_en]','de'=>'[parc_fermeture_exceptionnelle_de]'),
            array('label'=>'Texte horaire dynamique pour l’en-tête','auto'=>'[parc_horaire]','fr'=>'[parc_horaire_fr]','en'=>'[parc_horaire_en]','de'=>'[parc_horaire_de]'),
            array('label'=>'Statut OUVERT / FERMÉ pour l’en-tête','auto'=>'[parc_statut]','fr'=>'[parc_statut_fr]','en'=>'[parc_statut_en]','de'=>'[parc_statut_de]'),
            array('label'=>'Horaire d’accueil','auto'=>'[parc_horaire_accueil]','fr'=>'[parc_horaire_accueil_fr]','en'=>'[parc_horaire_accueil_en]','de'=>'[parc_horaire_accueil_de]'),
            array('label'=>'Devis groupe autour du formulaire Contact Form 7','auto'=>'[parc_devis_groupe]','fr'=>'[parc_devis_groupe_fr]','en'=>'[parc_devis_groupe_en]','de'=>'[parc_devis_groupe_de]'),
            array('label'=>'Alias compatible du module Devis groupe','auto'=>'[parc_devis]','fr'=>'[parc_devis_fr]','en'=>'[parc_devis_en]','de'=>'[parc_devis_de]'),
            array('label'=>'Guides pédagogiques','auto'=>'[parc_guides_pedagogiques]','fr'=>'[parc_guides_pedagogiques_fr]','en'=>'[parc_guides_pedagogiques_en]','de'=>'[parc_guides_pedagogiques_de]'),
        );
        ?>
        <section id="htp-shortcodes" class="htp-card">
            <h2>Shortcodes</h2>
            <p>Tous les shortcodes utilisables sont listés ci-dessous. Copiez celui dont vous avez besoin. La version « Automatique » suit la langue de la page ; les versions FR, EN et DE forcent la langue.</p>
            <table class="widefat striped">
                <thead><tr><th>Module</th><th>Automatique</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                <tbody>
                    <?php foreach ($shortcodes as $row) : ?>
                        <tr>
                            <th><?php echo esc_html($row['label']); ?></th>
                            <td><code><?php echo esc_html($row['auto']); ?></code></td>
                            <td><code><?php echo esc_html($row['fr']); ?></code></td>
                            <td><code><?php echo esc_html($row['en']); ?></code></td>
                            <td><code><?php echo esc_html($row['de']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Les alertes configurées dans l’onglet « Alertes » s’affichent automatiquement en pop-up sur le site. Les shortcodes d’alerte restent disponibles pour compatibilité ou affichage manuel.</p>
        </section>
        <?php
    }

    private static function updates_section($settings) {
        $has_token = class_exists('Parcs_HT_Updater') && Parcs_HT_Updater::has_token();
        $source = class_exists('Parcs_HT_Updater') ? Parcs_HT_Updater::token_source() : 'none';
        $auto_update = class_exists('Parcs_HT_Updater') && Parcs_HT_Updater::auto_update_enabled();
        $latest_version = '';
        $latest_error = '';
        $last_check = 0;
        if ($has_token && class_exists('Parcs_HT_Updater')) {
            $latest = Parcs_HT_Updater::latest_version();
            if (is_wp_error($latest)) $latest_error = $latest->get_error_message();
            else $latest_version = (string) $latest;
            $last_check = Parcs_HT_Updater::last_check();
        }
        if (!$has_token) {
            $status = 'Clé GitHub absente';
        } elseif ($latest_error !== '') {
            $status = 'Impossible de vérifier';
        } elseif ($latest_version !== '' && version_compare($latest_version, PARCS_HT_VERSION, '>')) {
            $status = 'Mise à jour disponible';
        } elseif ($latest_version !== '') {
            $status = 'À jour';
        } else {
            $status = 'Impossible de vérifier';
        }
        $check_url = wp_nonce_url(
            admin_url('admin-post.php?action=parcs_ht_check_updates'),
            'parcs_ht_check_updates'
        );
        ?>
        <section id="htp-updates" class="htp-card">
            <h2>Mises à jour de l’extension</h2>
            <p>Cette extension peut recevoir ses nouvelles versions depuis le dépôt GitHub privé. Vous pouvez configurer l’accès directement ici, sans modifier <code>wp-config.php</code>.</p>
            <?php if ($has_token) : ?>
                <div class="notice notice-success inline"><p><strong>Clé GitHub configurée.</strong> <?php echo $source === 'wordpress' ? 'Elle est enregistrée dans WordPress.' : 'Elle est fournie par la configuration du serveur.'; ?></p></div>
            <?php else : ?>
                <div class="notice notice-warning inline"><p>Aucune clé GitHub n’est configurée : WordPress ne peut pas encore lire les releases privées.</p></div>
            <?php endif; ?>
            <?php if ($source !== 'constant' && $source !== 'environment') : ?>
                <div class="htp-grid htp-grid-2">
                    <label class="htp-field"><span>Clé GitHub privée</span><input type="password" name="github_token_new" value="" autocomplete="new-password" placeholder="<?php echo $has_token ? esc_attr('Clé déjà enregistrée — laisser vide pour la conserver') : esc_attr('github_pat_…'); ?>"><small>Collez ici un token GitHub limité au dépôt <code>montagnedessinges/horaires-tarifs-parc</code>, avec la permission <code>Contents: Read-only</code>. La clé enregistrée n’est jamais réaffichée dans l’administration.</small></label>
                    <?php if ($has_token) : ?><label class="htp-field"><span>Supprimer la clé</span><span><?php self::checkbox('github_token_remove', '0', 'Supprimer la clé GitHub enregistrée lors de la sauvegarde'); ?></span></label><?php endif; ?>
                </div>
            <?php else : ?>
                <p class="description">La clé est actuellement fournie par le serveur. Le champ WordPress est désactivé afin de ne pas créer deux sources concurrentes.</p>
            <?php endif; ?>
            <hr>
            <h3>État des mises à jour</h3>
            <p><strong>Version installée :</strong> <?php echo esc_html(PARCS_HT_VERSION); ?><br>
            <strong>Dernière version GitHub :</strong> <?php echo $latest_version !== '' ? esc_html($latest_version) : 'Non disponible'; ?><br>
            <strong>État :</strong> <?php echo esc_html($status); ?><br>
            <strong>Dernière vérification :</strong> <?php echo $last_check ? esc_html(wp_date('d/m/Y à H:i', $last_check)) : 'Jamais'; ?><br>
            <?php if ($latest_error !== '') : ?><span class="notice notice-error inline"><span style="display:block;padding:8px 12px"><?php echo esc_html($latest_error); ?></span></span><?php endif; ?></p>
            <p><a class="button button-secondary" href="<?php echo esc_url($check_url); ?>">Vérifier les mises à jour maintenant</a></p>
            <?php if ($has_token) : ?>
                <label class="htp-field"><span>Mises à jour automatiques</span><span><?php self::checkbox('github_auto_update', $auto_update ? '1' : '0', 'Installer automatiquement les nouvelles versions de cette extension lorsqu’elles sont détectées'); ?></span><small>Si cette option est activée, WordPress pourra installer automatiquement les prochaines releases GitHub lors de ses vérifications planifiées. Le site doit avoir WP-Cron fonctionnel et la clé GitHub doit rester valide.</small></label>
            <?php endif; ?>
            <p class="description">Après configuration, les nouvelles versions apparaissent dans <strong>Extensions</strong> et <strong>Tableau de bord → Mises à jour</strong>, comme les autres extensions WordPress. Si l’option automatique ci-dessus reste désactivée, l’installation est manuelle.</p>
            <hr>
            <h3>Données lors d’une désinstallation</h3>
            <label class="htp-field"><span>Nettoyage facultatif</span><span><?php self::checkbox('settings[general][delete_data_on_uninstall]', $settings['general']['delete_data_on_uninstall'] ?? '0', 'Supprimer définitivement les horaires, tarifs, caches et historiques si l’extension est désinstallée'); ?></span><small>La valeur par défaut conserve toutes les données. Une simple désactivation ou une mise à jour ne supprime jamais les réglages.</small></label>
        </section>
        <?php
    }

    private static function preview_section($audit) {
        $summary = is_array($audit['summary'] ?? null) ? $audit['summary'] : array();
        $audit_problems = array_merge((array)($audit['errors'] ?? array()), (array)($audit['warnings'] ?? array()));
        $revisions = class_exists('Parcs_HT_Health') ? Parcs_HT_Health::revisions() : array();
        ?>
        <section id="htp-preview" class="htp-card">
            <h2>Diagnostic annuel et aperçu</h2>
            <div class="htp-audit-summary <?php echo $audit_problems ? 'has-problems' : 'is-ok'; ?>">
                <p><strong><?php echo $audit_problems ? 'Le planning demande une vérification.' : 'Le planning est cohérent.'; ?></strong></p>
                <p><?php echo esc_html(sprintf(
                    '%d jours analysés · %d ouverts · %d fermés · %d horaires exceptionnels · %d fermetures exceptionnelles · %d jours avec accès limité',
                    (int)($summary['days'] ?? 0), (int)($summary['open'] ?? 0), (int)($summary['closed'] ?? 0),
                    (int)($summary['exceptional_hours'] ?? 0), (int)($summary['exceptional_closures'] ?? 0), (int)($summary['domain_limited'] ?? 0)
                )); ?></p>
                <?php if ($audit_problems) : ?><ul><?php foreach (array_slice($audit_problems, 0, 30) as $problem) : ?><li><?php echo esc_html($problem); ?></li><?php endforeach; ?></ul><?php endif; ?>
            </div>
            <p>Enregistrez d’abord les modifications, puis choisissez une date pour vérifier la priorité des horaires, fermetures et règles du domaine.</p>
            <div class="htp-preview-controls"><label class="htp-field"><span>Date à tester</span><input type="date" data-htp-preview-date></label><button type="button" class="button button-secondary" data-htp-preview-button>Afficher le résultat</button></div>
            <div class="htp-preview-result" data-htp-preview-result aria-live="polite">Sélectionnez une date.</div>
            <h3>Historique de sécurité</h3>
            <p>Les dix dernières configurations différentes sont conservées avant enregistrement.</p>
            <?php if ($revisions) : ?><div class="htp-revisions"><?php foreach (array_slice($revisions, 0, 10) as $index=>$revision) : ?>
                <div class="htp-revision-row"><span><?php echo esc_html(wp_date('d/m/Y H:i', (int)($revision['created_at'] ?? 0))); ?> · saison <?php echo esc_html((string)($revision['year'] ?? '')); ?></span>
                    <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action'=>'parcs_ht_restore_revision','revision'=>$index), admin_url('admin-post.php')), 'parcs_ht_restore_revision_'.$index)); ?>">Restaurer</a>
                </div>
            <?php endforeach; ?></div><?php else : ?><p class="description">Aucune révision n’est encore enregistrée.</p><?php endif; ?>
        </section>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.');
        }
        check_admin_referer('parcs_ht_save');
        $raw = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Structure nettoyée champ par champ par self::sanitize ci-dessous, SVG inclus.
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année de saison invalide.');

        if (class_exists('Parcs_HT_Updater') && !in_array(Parcs_HT_Updater::token_source(), array('constant','environment'), true)) {
            $remove_token = isset($_POST['github_token_remove']) && sanitize_text_field(wp_unslash($_POST['github_token_remove'])) === '1';
            $new_token = isset($_POST['github_token_new']) ? trim(sanitize_text_field(wp_unslash($_POST['github_token_new']))) : '';
            if ($remove_token) {
                delete_option(Parcs_HT_Updater::TOKEN_OPTION);
                Parcs_HT_Updater::clear_cache();
            } elseif ($new_token !== '') {
                if (strlen($new_token) < 20 || preg_match('/\s/', $new_token)) {
                    wp_die('La clé GitHub saisie ne semble pas valide.');
                }
                Parcs_HT_Updater::store_token($new_token);
                Parcs_HT_Updater::clear_cache();
            }
        }

        if (class_exists('Parcs_HT_Updater')) {
            $auto_update = isset($_POST['github_auto_update']) && sanitize_text_field(wp_unslash($_POST['github_auto_update'])) === '1';
            Parcs_HT_Updater::set_auto_update($auto_update);
        }

        $current = Parcs_HT_Defaults::settings($year);
        $scoped_save = isset($_POST['htp_save_active']);
        $incomplete = self::incomplete_sections($raw);
        $clean = self::sanitize($raw, $current);
        $all = Parcs_HT_Defaults::all_settings();
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::store_revision($all, $year);
        if (!isset($all['seasons'][$year])) $all['seasons'][$year] = Parcs_HT_Defaults::empty_season($year);
        $all['general'] = $clean['general'];
        $all['timezone'] = $clean['timezone'];
        unset($all['general']['year'], $all['general']['season_start'], $all['general']['season_end'], $all['general']['published']);
        $all['alerts'] = $clean['alerts'];
        $all['tariffs'] = $clean['tariffs'];
        $all['quote_page'] = $clean['quote_page'];
        $all['seasons'][$year] = array(
            'year' => $year,
            'published' => isset($clean['season_published']) ? $clean['season_published'] : '0',
            'season_start' => isset($clean['season_start']) ? $clean['season_start'] : '',
            'season_end' => isset($clean['season_end']) ? $clean['season_end'] : '',
            'regular_periods' => $clean['regular_periods'],
            'school_holidays' => $clean['school_holidays'],
            'special_periods' => $clean['special_periods'],
            'public_holidays' => $clean['public_holidays'],
            'domain_rules' => $clean['domain_rules'],
            'exceptions' => $clean['exceptions'],
        );
        $all['schema_version'] = Parcs_HT_Defaults::SCHEMA_VERSION;
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        $clean['active_season_year'] = $year;
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::notify_audit($clean, 'enregistrement de la saison '.$year);
        do_action('litespeed_purge_all');
        $redirect = array('page' => self::PAGE, 'season' => $year, 'updated' => '1');
        $allowed_tabs = array('htp-general','htp-regular','htp-holidays','htp-domain','htp-exceptions','htp-alerts','htp-tariffs','htp-quote','htp-preview','htp-updates','htp-shortcodes');
        $active_tab = isset($_POST['htp_active_tab']) ? sanitize_key(wp_unslash($_POST['htp_active_tab'])) : '';
        if (in_array($active_tab, $allowed_tabs, true)) $redirect['tab'] = $active_tab;
        if (!empty($incomplete) && !$scoped_save) $redirect['preserved'] = '1';
        wp_safe_redirect(add_query_arg($redirect, admin_url('admin.php')));
        exit;
    }

    public static function check_updates() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.');
        }
        check_admin_referer('parcs_ht_check_updates');

        if (class_exists('Parcs_HT_Updater')) {
            Parcs_HT_Updater::clear_cache();
        }
        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        wp_update_plugins();

        wp_safe_redirect(add_query_arg(array(
            'page' => self::PAGE,
            'update-check' => '1',
        ), admin_url('admin.php')) . '#htp-updates');
        exit;
    }

    public static function restore_revision() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $index = isset($_REQUEST['revision']) ? absint(wp_unslash($_REQUEST['revision'])) : -1;
        check_admin_referer('parcs_ht_restore_revision_'.$index);
        $revisions = class_exists('Parcs_HT_Health') ? Parcs_HT_Health::revisions() : array();
        if (!isset($revisions[$index]['settings']) || !is_array($revisions[$index]['settings'])) {
            wp_die('Cette révision n’existe plus.');
        }
        $current = Parcs_HT_Defaults::all_settings();
        $year = (string)($revisions[$index]['year'] ?? '');
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::store_revision($current, $year, 'Avant restauration');
        update_option(Parcs_HT_Defaults::OPTION, $revisions[$index]['settings'], false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'season'=>$year,'restored'=>'1','tab'=>'htp-preview'), admin_url('admin.php')));
        exit;
    }

    public static function add_season() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_add_season');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || (int)$year < 2020 || (int)$year > 2100) wp_die('Année invalide.');
        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year])) {
            $all['seasons'][$year] = Parcs_HT_Defaults::empty_season($year);
            ksort($all['seasons'], SORT_NUMERIC);
            update_option(Parcs_HT_Defaults::OPTION, $all, false);
        }
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'season'=>$year), admin_url('admin.php')));
        exit;
    }


    public static function duplicate_season() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_duplicate_season_' . $year);
        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year])) wp_die('Saison introuvable.');
        $target = (int)$year + 1;
        while (isset($all['seasons'][(string)$target]) && $target <= 2100) $target++;
        if ($target > 2100) wp_die('Aucune année disponible pour la duplication.');
        $copy = $all['seasons'][$year];
        $copy['year'] = (string)$target;
        $copy['published'] = '0';
        $year_offset = $target - (int)$year;
        $copy['season_start'] = self::shift_date_years((string)($copy['season_start'] ?? ''), $year_offset);
        $copy['season_end'] = self::shift_date_years((string)($copy['season_end'] ?? ''), $year_offset);
        foreach (array('regular_periods','school_holidays','special_periods','public_holidays','domain_rules','exceptions') as $list) {
            foreach ($copy[$list] as &$row) {
                foreach (array('start','end','date','popup_start','popup_end') as $date_key) {
                    if (isset($row[$date_key])) $row[$date_key] = self::shift_date_years((string)$row[$date_key], $year_offset);
                }
            }
            unset($row);
        }
        $all['seasons'][(string)$target] = $copy;
        ksort($all['seasons'], SORT_NUMERIC);
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'season'=>(string)$target,'duplicated'=>1), admin_url('admin.php'))); exit;
    }

    private static function shift_date_years($value, $years) {
        if ($value === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})(T\d{2}:\d{2})?$/', $value, $parts)) return $value;
        $target_year = (int)$parts[1] + (int)$years;
        $month = (int)$parts[2];
        $day = (int)$parts[3];
        while ($day > 28 && !checkdate($month, $day, $target_year)) $day--;
        return sprintf('%04d-%02d-%02d', $target_year, $month, $day).($parts[4] ?? '');
    }

    public static function delete_season() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_delete_season_' . $year);
        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year])) wp_die('Saison introuvable.');
        $published = 0; foreach ($all['seasons'] as $s) if ((string)($s['published'] ?? '0') === '1') $published++;
        if ((string)($all['seasons'][$year]['published'] ?? '0') === '1' && $published <= 1) wp_die('Impossible de supprimer la dernière saison publiée. Passez d’abord une autre saison en statut publié.');
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::store_revision($all, $year, 'Avant suppression de saison');
        unset($all['seasons'][$year]);
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        $next = Parcs_HT_Defaults::select_season_year($all);
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'season'=>$next,'deleted'=>1), admin_url('admin.php'))); exit;
    }

    private static function sanitize($raw, $current = array()) {
        $defaults = Parcs_HT_Defaults::get();
        $complete = isset($raw['_complete']) && is_array($raw['_complete']) ? $raw['_complete'] : array();

        if (!isset($complete['general']) && isset($current['general'])) {
            $posted_general = isset($raw['general']) && is_array($raw['general']) ? $raw['general'] : array();
            $raw['general'] = array_replace_recursive($current['general'], $posted_general);
        }
        if (!isset($complete['regular_periods']) && isset($current['regular_periods'])) $raw['regular_periods'] = $current['regular_periods'];
        if (!isset($complete['holidays'])) {
            if (isset($current['school_holidays'])) $raw['school_holidays'] = $current['school_holidays'];
            if (isset($current['special_periods'])) $raw['special_periods'] = $current['special_periods'];
            if (isset($current['public_holidays'])) $raw['public_holidays'] = $current['public_holidays'];
        }
        if (!isset($complete['domain_rules']) && isset($current['domain_rules'])) $raw['domain_rules'] = $current['domain_rules'];
        if (!isset($complete['exceptions']) && isset($current['exceptions'])) $raw['exceptions'] = $current['exceptions'];
        if (!isset($complete['alerts']) && isset($current['alerts'])) $raw['alerts'] = $current['alerts'];
        if (!isset($complete['tariffs']) && isset($current['tariffs'])) $raw['tariffs'] = $current['tariffs'];
        if (!isset($complete['quote_page']) && isset($current['quote_page'])) $raw['quote_page'] = $current['quote_page'];
        $clean = array(
            'schema_version' => Parcs_HT_Defaults::SCHEMA_VERSION,
            'site_type' => $defaults['site_type'],
            'timezone' => self::sanitize_timezone(isset($raw['timezone']) ? $raw['timezone'] : ($current['timezone'] ?? 'Europe/Paris')),
            'languages' => array('fr','en','de'),
            'general' => array(), 'season_start'=>'', 'season_end'=>'', 'season_published'=>'0',
            'regular_periods' => array(), 'school_holidays' => array(), 'special_periods' => array(), 'public_holidays' => array(),
            'domain_rules' => array(), 'exceptions' => array(), 'alerts' => array(),
            'quote_page' => array(),
            'tariffs' => array('group_order'=>array(),'columns'=>array('individual'=>array(),'reduced'=>array(),'groups'=>array()),'individual'=>array(),'reduced'=>array(),'groups'=>array(),'notes'=>array(),'payment_methods'=>array(),'payment_icons'=>array(),'payment_styles'=>array(),'print'=>array()),
        );
        $g = isset($raw['general']) && is_array($raw['general']) ? $raw['general'] : array();
        // Les réglages généraux sont volontairement répartis dans les sections où ils sont utilisés.
        // Si PHP tronque un grand formulaire, toute clé absente conserve sa valeur enregistrée au lieu d'être réinitialisée.
        if (isset($current['general']) && is_array($current['general'])) {
            $g = array_replace_recursive($current['general'], $g);
        }
        $clean['general'] = array(
            'park_name' => self::sanitize_translations(isset($g['park_name']) ? $g['park_name'] : array()),
            'last_entry_minutes' => self::number($g, 'last_entry_minutes', 0, 1440),
            'tickets_url' => self::sanitize_url_translations(isset($g['tickets_url']) ? $g['tickets_url'] : array()),
            'groups_url' => self::sanitize_url_translations(isset($g['groups_url']) ? $g['groups_url'] : array()),
            'groups_email' => isset($g['groups_email']) ? sanitize_email($g['groups_email']) : '',
            'groups_closed_note' => self::sanitize_translations(isset($g['groups_closed_note']) ? $g['groups_closed_note'] : array(), true),
            'groups_booking_note' => self::sanitize_translations(isset($g['groups_booking_note']) ? $g['groups_booking_note'] : array(), true),
            'groups_button_label' => self::sanitize_translations(isset($g['groups_button_label']) ? $g['groups_button_label'] : array()),
            'health_notifications_enabled' => array_key_exists('health_notifications_enabled', $g) ? self::bool($g, 'health_notifications_enabled') : '1',
            'health_notification_email' => isset($g['health_notification_email']) ? sanitize_email($g['health_notification_email']) : '',
            'delete_data_on_uninstall' => array_key_exists('delete_data_on_uninstall', $g) ? self::bool($g, 'delete_data_on_uninstall') : '0',
            'primary_color' => self::color($g, 'primary_color', '#006757'), 'secondary_color' => self::color($g, 'secondary_color', '#31ad81'),
            'accent_color' => self::color($g, 'accent_color', '#ef7b5b'), 'highlight_color' => self::color($g, 'highlight_color', '#e7c55b'),
            'body_text_color' => self::optional_color($g, 'body_text_color'), 'heading_text_color' => self::optional_color($g, 'heading_text_color'), 'border_color' => self::optional_color($g, 'border_color'), 'block_spacing' => self::number($g, 'block_spacing', 0, 60), 'block_border_enabled' => self::bool($g, 'block_border_enabled'),
            'font_profile'=>(in_array(isset($g['font_profile'])?(string)$g['font_profile']:'inherit',array('inherit','caltons','custom'),true)?(string)$g['font_profile']:'inherit'), 'typography_preset'=>(in_array(isset($g['typography_preset'])?(string)$g['typography_preset']:'standard',array('compact','standard','large'),true)?(string)$g['typography_preset']:'standard'), 'calendar_detail_preset'=>(in_array(isset($g['calendar_detail_preset'])?(string)$g['calendar_detail_preset']:'large',array('standard','large','xlarge'),true)?(string)$g['calendar_detail_preset']:'large'), 'period_legend_label'=>self::sanitize_translations(isset($g['period_legend_label'])?$g['period_legend_label']:array()), 'event_legend_label'=>self::sanitize_translations(isset($g['event_legend_label'])?$g['event_legend_label']:array()), 'font_body_size'=>self::optional_number($g,'font_body_size',8,80), 'font_heading_size'=>self::optional_number($g,'font_heading_size',10,100), 'font_kicker_size'=>self::optional_number($g,'font_kicker_size',8,50), 'font_button_size'=>self::optional_number($g,'font_button_size',8,50),
            'font_today_title_size'=>self::optional_number($g,'font_today_title_size',8,60), 'font_today_status_size'=>self::optional_number($g,'font_today_status_size',12,100), 'font_today_detail_size'=>self::optional_number($g,'font_today_detail_size',8,60),
            'font_calendar_title_size'=>self::optional_number($g,'font_calendar_title_size',10,80), 'font_calendar_month_size'=>self::optional_number($g,'font_calendar_month_size',8,40), 'font_calendar_summary_size'=>self::optional_number($g,'font_calendar_summary_size',8,50), 'font_calendar_weekday_size'=>self::optional_number($g,'font_calendar_weekday_size',8,30), 'font_calendar_day_size'=>self::optional_number($g,'font_calendar_day_size',8,40), 'font_calendar_detail_title_size'=>self::optional_number($g,'font_calendar_detail_title_size',10,60), 'font_calendar_detail_hours_size'=>self::optional_number($g,'font_calendar_detail_hours_size',10,60), 'font_calendar_detail_last_size'=>self::optional_number($g,'font_calendar_detail_last_size',8,50), 'font_calendar_legend_size'=>self::optional_number($g,'font_calendar_legend_size',8,40),
            'font_tariff_title_size'=>self::optional_number($g,'font_tariff_title_size',10,80), 'font_tariff_tab_size'=>self::optional_number($g,'font_tariff_tab_size',8,40), 'font_tariff_label_size'=>self::optional_number($g,'font_tariff_label_size',8,50), 'font_tariff_detail_size'=>self::optional_number($g,'font_tariff_detail_size',8,40), 'font_tariff_note_size'=>self::optional_number($g,'font_tariff_note_size',8,40), 'font_tariff_price_size'=>self::optional_number($g,'font_tariff_price_size',10,60),
            'font_payment_title_size'=>self::optional_number($g,'font_payment_title_size',8,50), 'font_payment_item_size'=>self::optional_number($g,'font_payment_item_size',8,40), 'font_groups_note_size'=>self::optional_number($g,'font_groups_note_size',8,40), 'font_alert_title_size'=>self::optional_number($g,'font_alert_title_size',10,80), 'font_alert_text_size'=>self::optional_number($g,'font_alert_text_size',8,50), 'font_alert_button_size'=>self::optional_number($g,'font_alert_button_size',8,40),
            'today_title_color' => self::optional_color($g, 'today_title_color'), 'today_title_bg_color' => self::color($g, 'today_title_bg_color', '#ffffff'), 'today_title_bg_transparent' => self::bool($g, 'today_title_bg_transparent'), 'today_status_color' => self::optional_color($g, 'today_status_color'), 'today_closed_color' => self::optional_color($g, 'today_closed_color'), 'today_detail_color' => self::optional_color($g, 'today_detail_color'),
            'calendar_title_color' => self::optional_color($g, 'calendar_title_color'), 'calendar_title_bg_color' => self::color($g, 'calendar_title_bg_color', '#ffffff'), 'calendar_title_bg_transparent' => self::bool($g, 'calendar_title_bg_transparent'), 'calendar_day_bg_color' => self::color($g, 'calendar_day_bg_color', '#ffffff'), 'calendar_day_bg_transparent' => self::bool($g, 'calendar_day_bg_transparent'), 'calendar_mobile_size' => (in_array(isset($g['calendar_mobile_size']) ? (string)$g['calendar_mobile_size'] : 'medium', array('small','medium','large'), true) ? (string)$g['calendar_mobile_size'] : 'medium'), 'calendar_nav_bg_color' => self::color($g, 'calendar_nav_bg_color', '#006757'), 'calendar_nav_text_color' => self::color($g, 'calendar_nav_text_color', '#ffffff'), 'calendar_nav_active_bg_color' => self::color($g, 'calendar_nav_active_bg_color', '#e7c55b'), 'calendar_nav_active_text_color' => self::color($g, 'calendar_nav_active_text_color', '#27342f'), 'calendar_weekday_color' => self::optional_color($g, 'calendar_weekday_color'), 'calendar_detail_text_color' => self::optional_color($g, 'calendar_detail_text_color'), 'calendar_detail_border_color' => self::optional_color($g, 'calendar_detail_border_color'), 'calendar_closed_bg_color' => self::color($g, 'calendar_closed_bg_color', '#e3e5e4'), 'calendar_closed_text_color' => self::color($g, 'calendar_closed_text_color', '#616765'), 'calendar_selected_color' => self::color($g, 'calendar_selected_color', '#006757'), 'show_public_holidays' => self::bool($g, 'show_public_holidays'), 'holiday_border_color' => self::color($g, 'holiday_border_color', '#e7c55b'), 'holiday_border_width' => self::number($g, 'holiday_border_width', 1, 8), 'holiday_message' => self::sanitize_translations(isset($g['holiday_message']) ? $g['holiday_message'] : array()), 'school_holiday_marker_color' => self::color($g, 'school_holiday_marker_color', '#7b61a8'), 'school_holiday_message' => self::sanitize_translations(isset($g['school_holiday_message']) ? $g['school_holiday_message'] : array()),
            'tariff_kicker_color' => self::optional_color($g, 'tariff_kicker_color'), 'tariff_title_color' => self::optional_color($g, 'tariff_title_color'), 'tariff_title_bg_color' => self::color($g, 'tariff_title_bg_color', '#ffffff'), 'tariff_title_bg_transparent' => self::bool($g, 'tariff_title_bg_transparent'),
            'payment_title_color' => self::optional_color($g, 'payment_title_color'), 'payment_title_bg_color' => self::color($g, 'payment_title_bg_color', '#ffffff'), 'payment_title_bg_transparent' => self::bool($g, 'payment_title_bg_transparent'), 'payment_item_bg_color' => self::color($g, 'payment_item_bg_color', '#006757'), 'payment_item_text_color' => self::color($g, 'payment_item_text_color', '#ffffff'), 'payment_icon_color' => self::color($g, 'payment_icon_color', '#ffffff'), 'payment_border_color' => self::optional_color($g, 'payment_border_color'), 'payment_border_enabled' => self::bool($g, 'payment_border_enabled'),
            'tab_bg_color' => self::color($g, 'tab_bg_color', '#006757'), 'tab_text_color' => self::color($g, 'tab_text_color', '#ffffff'), 'tab_active_bg_color' => self::color($g, 'tab_active_bg_color', '#e7c55b'), 'tab_active_text_color' => self::color($g, 'tab_active_text_color', '#27342f'), 'panel_text_color' => self::optional_color($g, 'panel_text_color'), 'panel_border_color' => self::optional_color($g, 'panel_border_color'), 'panel_border_enabled' => self::bool($g, 'panel_border_enabled'), 'panel_bg_color' => self::color($g, 'panel_bg_color', '#ffffff'), 'panel_bg_transparent' => self::bool($g, 'panel_bg_transparent'), 'price_color' => self::optional_color($g, 'price_color'), 'tariff_note_text_color' => self::optional_color($g, 'tariff_note_text_color'), 'tariff_note_border_color' => self::optional_color($g, 'tariff_note_border_color'), 'groups_note_text_color' => self::optional_color($g, 'groups_note_text_color'), 'groups_note_border_color' => self::optional_color($g, 'groups_note_border_color'),
            'button_bg_color' => self::color($g, 'button_bg_color', '#006757'), 'button_text_color' => self::color($g, 'button_text_color', '#ffffff'), 'primary_button_bg_color' => self::color($g, 'primary_button_bg_color', '#ef7b5b'), 'primary_button_text_color' => self::color($g, 'primary_button_text_color', '#ffffff'),
            'alert_bg_color' => self::color($g, 'alert_bg_color', '#006757'), 'alert_title_color' => self::color($g, 'alert_title_color', '#ffffff'), 'alert_text_color' => self::color($g, 'alert_text_color', '#ffffff'), 'alert_border_color' => self::color($g, 'alert_border_color', '#ef7b5b'), 'alert_button_bg_color' => self::color($g, 'alert_button_bg_color', '#ef7b5b'), 'alert_button_text_color' => self::color($g, 'alert_button_text_color', '#ffffff'), 'alert_button_border_color' => self::color($g, 'alert_button_border_color', '#ef7b5b'), 'alert_close_bg_color' => self::color($g, 'alert_close_bg_color', '#ffffff'), 'alert_close_text_color' => self::color($g, 'alert_close_text_color', '#222222'), 'alert_overlay_color' => self::color($g, 'alert_overlay_color', '#000000'), 'alert_overlay_opacity' => self::number($g, 'alert_overlay_opacity', 0, 100), 'alert_border_width' => self::number($g, 'alert_border_width', 0, 12), 'alert_radius' => self::number($g, 'alert_radius', 0, 40), 'alert_shadow' => self::bool($g, 'alert_shadow'), 'alert_reappear_hours' => self::number($g, 'alert_reappear_hours', 1, 720),
        );
        $clean['season_start'] = self::date($g, 'season_start');
        $clean['season_end'] = self::date($g, 'season_end');
        $clean['season_published'] = self::bool($g, 'published');

        foreach (isset($raw['regular_periods']) ? $raw['regular_periods'] : array() as $idx => $row) {
            if (!is_array($row)) continue;
            $days = self::resolve_weekdays_for_save($row, isset($current['regular_periods'][$idx]['weekdays']) ? $current['regular_periods'][$idx]['weekdays'] : array(), array('1','2','3','4','5','6','7'));
            $clean['regular_periods'][] = array('enabled'=>self::bool($row,'enabled'),'label'=>self::text($row,'label'),'start'=>self::date($row,'start'),'end'=>self::date($row,'end'),'weekdays'=>$days,'open'=>self::time($row,'open'),'close'=>self::time($row,'close'),'open2'=>self::time($row,'open2'),'close2'=>self::time($row,'close2'),'last_entry_minutes'=>self::optional_number($row,'last_entry_minutes',0,1440),'color'=>self::color($row,'color','#9AAA8B'));
        }
        // Les périodes visibles et les vacances scolaires restent deux notions distinctes.
        // Les périodes de type « vacances scolaires » alimentent uniquement le référentiel interne des vacances, utilisé par les règles d’accès.
        foreach (isset($raw['special_periods']) ? $raw['special_periods'] : array() as $row) {
            if (!is_array($row)) continue;
            $kind = isset($row['kind']) && in_array((string)$row['kind'], array('event','school_holiday','other'), true) ? (string)$row['kind'] : 'event';
            $event = array(
                'enabled'=>self::bool($row,'enabled'),
                'kind'=>$kind,
                'internal_label'=>self::text($row,'internal_label'),
                'title'=>self::sanitize_translations(isset($row['title'])?$row['title']:array()),
                'start'=>self::date($row,'start'),
                'end'=>self::date($row,'end'),
                'color'=>$kind==='event' ? '#e7c55b' : self::color($row,'color','#7b61a8'),
                'icon'=>$kind==='event' ? 'star' : 'star',
                'display_mode'=>(isset($row['display_mode'])&&in_array((string)$row['display_mode'],array('spot','long'),true)?(string)$row['display_mode']:'spot'),
                'message'=>self::sanitize_translations(isset($row['message'])?$row['message']:array(), true),
                'button_label'=>self::sanitize_translations(isset($row['button_label'])?$row['button_label']:array()),
                'button_url'=>self::sanitize_url_translations(isset($row['button_url'])?$row['button_url']:array()),
                'show_button'=>array_key_exists('show_button',$row)?self::bool($row,'show_button'):'0',
                'show_on_calendar'=>array_key_exists('show_on_calendar',$row)?self::bool($row,'show_on_calendar'):'1',
                'skip_domain_rules'=>array_key_exists('skip_domain_rules',$row)?self::bool($row,'skip_domain_rules'):'0',
                'show_popup'=>self::bool($row,'show_popup'),
                'popup_lead_mode'=>(isset($row['popup_lead_mode'])&&in_array((string)$row['popup_lead_mode'],array('same','days_before','custom'),true)?(string)$row['popup_lead_mode']:'days_before'),
                'popup_days_before'=>self::number($row,'popup_days_before',0,365),
                'popup_start'=>self::datetime($row,'popup_start'),
                'popup_end'=>self::datetime($row,'popup_end'),
                'popup_title'=>self::sanitize_translations(isset($row['popup_title'])?$row['popup_title']:array()),
                'popup_message'=>self::sanitize_translations(isset($row['popup_message'])?$row['popup_message']:array(), true),
                'popup_button_label'=>self::sanitize_translations(isset($row['popup_button_label'])?$row['popup_button_label']:array()),
                'popup_button_url'=>self::sanitize_url_translations(isset($row['popup_button_url'])?$row['popup_button_url']:array()),
                'popup_show_button'=>array_key_exists('popup_show_button',$row)?self::bool($row,'popup_show_button'):'0',
                'popup_image_url'=>isset($row['popup_image_url'])?esc_url_raw(wp_unslash($row['popup_image_url'])):'',
            );
            $clean['special_periods'][] = $event;
            if ($event['enabled']==='1' && $event['kind']==='school_holiday') {
                $clean['school_holidays'][] = array(
                    'enabled'=>'1',
                    'label'=>$event['internal_label'],
                    'start'=>$event['start'],
                    'end'=>$event['end']
                );
            }
        }
        // Si une ancienne version est enregistrée sans nouveau champ, conserver les vacances historiques.
        if (empty($clean['special_periods'])) {
            foreach (isset($raw['school_holidays']) ? $raw['school_holidays'] : array() as $row) {
                if (!is_array($row)) continue;
                $clean['school_holidays'][] = array('enabled'=>self::bool($row,'enabled'),'label'=>self::text($row,'label'),'start'=>self::date($row,'start'),'end'=>self::date($row,'end'));
            }
        }
        foreach (isset($raw['public_holidays']) ? $raw['public_holidays'] : array() as $row) {
            if (!is_array($row)) continue;
            $clean['public_holidays'][] = array('enabled'=>self::bool($row,'enabled'),'label'=>self::text($row,'label'),'date'=>self::date($row,'date'));
        }
        foreach (isset($raw['domain_rules']) ? $raw['domain_rules'] : array() as $idx => $row) {
            if (!is_array($row)) continue;
            $days = self::resolve_weekdays_for_save($row, isset($current['domain_rules'][$idx]['weekdays']) ? $current['domain_rules'][$idx]['weekdays'] : array(), array('1','2','3','4','5'));
            $clean['domain_rules'][] = array('enabled'=>self::bool($row,'enabled'),'label'=>self::text($row,'label'),'public_title'=>self::sanitize_translations(isset($row['public_title'])?$row['public_title']:array()),'start'=>self::date($row,'start'),'end'=>self::date($row,'end'),'weekdays'=>$days,'pause_start'=>self::time($row,'pause_start'),'resume'=>self::time($row,'resume'),'last_entry'=>self::time($row,'last_entry'),'exclude_weekends'=>self::bool($row,'exclude_weekends'),'exclude_school_holidays'=>self::bool($row,'exclude_school_holidays'),'exclude_public_holidays'=>self::bool($row,'exclude_public_holidays'),'auto_details'=>array_key_exists('auto_details',$row)?self::bool($row,'auto_details'):'1','show_tooltip'=>array_key_exists('show_tooltip',$row)?self::bool($row,'show_tooltip'):'0','tooltip_text'=>self::sanitize_translations(isset($row['tooltip_text'])?$row['tooltip_text']:array(), true),'info'=>self::sanitize_translations(isset($row['info'])?$row['info']:array(), true));
        }
        foreach (isset($raw['exceptions']) ? $raw['exceptions'] : array() as $row) {
            if (!is_array($row)) continue;
            $type = isset($row['type']) && $row['type'] === 'closed' ? 'closed' : 'hours';
            $clean['exceptions'][] = array('enabled'=>self::bool($row,'enabled'),'type'=>$type,'label'=>self::text($row,'label'),'start'=>self::date($row,'start'),'end'=>self::date($row,'end'),'open'=>self::time($row,'open'),'close'=>self::time($row,'close'),'open2'=>self::time($row,'open2'),'close2'=>self::time($row,'close2'),'last_entry_minutes'=>self::optional_number($row,'last_entry_minutes',0,1440),'priority'=>self::number($row,'priority',0,9999),'apply_domain_rules'=>array_key_exists('apply_domain_rules',$row)?self::bool($row,'apply_domain_rules'):'1','show_public_marker'=>array_key_exists('show_public_marker',$row)?self::bool($row,'show_public_marker'):'1','context'=>self::sanitize_translations(isset($row['context'])?$row['context']:array()),'title'=>self::sanitize_translations(isset($row['title'])?$row['title']:array()),'message'=>self::sanitize_translations(isset($row['message'])?$row['message']:array()),'show_popup'=>array_key_exists('show_popup',$row)?self::bool($row,'show_popup'):'0','popup_show_dates'=>array_key_exists('popup_show_dates',$row)?self::bool($row,'popup_show_dates'):'0','popup_show_hours'=>array_key_exists('popup_show_hours',$row)?self::bool($row,'popup_show_hours'):'0','popup_mode'=>(isset($row['popup_mode'])&&$row['popup_mode']==='custom'?'custom':'auto'),'popup_title'=>self::sanitize_translations(isset($row['popup_title'])?$row['popup_title']:array()),'popup_message'=>self::sanitize_translations(isset($row['popup_message'])?$row['popup_message']:array()),'popup_button_label'=>self::sanitize_translations(isset($row['popup_button_label'])?$row['popup_button_label']:array()),'popup_button_url'=>self::sanitize_url_translations(isset($row['popup_button_url'])?$row['popup_button_url']:array()),
                'popup_show_button'=>array_key_exists('popup_show_button',$row)?self::bool($row,'popup_show_button'):'0','popup_lead_mode'=>(isset($row['popup_lead_mode'])&&in_array((string)$row['popup_lead_mode'],array('same','days_before','custom'),true)?(string)$row['popup_lead_mode']:'days_before'),'popup_days_before'=>self::number($row,'popup_days_before',0,365),'popup_start'=>self::datetime($row,'popup_start'),'popup_end'=>self::datetime($row,'popup_end'));
        }
        foreach (isset($raw['alerts']) ? $raw['alerts'] : array() as $row) {
            if (!is_array($row)) continue;
            $clean['alerts'][] = array('enabled'=>self::bool($row,'enabled'),'published'=>self::bool($row,'published'),'start'=>self::datetime($row,'start'),'end'=>self::datetime($row,'end'),'title'=>self::sanitize_translations(isset($row['title'])?$row['title']:array()),'message'=>self::sanitize_translations(isset($row['message'])?$row['message']:array()),'button_label'=>self::sanitize_translations(isset($row['button_label'])?$row['button_label']:array()),'button_url'=>self::sanitize_url_translations(isset($row['button_url'])?$row['button_url']:array()),'show_button'=>array_key_exists('show_button',$row)?self::bool($row,'show_button'):'0');
        }
        $tariffs = isset($raw['tariffs']) && is_array($raw['tariffs']) ? $raw['tariffs'] : array();
        $allowed_groups = array('individual','reduced','groups');
        $group_order = isset($tariffs['group_order']) && is_array($tariffs['group_order']) ? $tariffs['group_order'] : $allowed_groups;
        foreach ($group_order as $group_key) {
            $group_key = sanitize_key($group_key);
            if (in_array($group_key, $allowed_groups, true) && !in_array($group_key, $clean['tariffs']['group_order'], true)) $clean['tariffs']['group_order'][] = $group_key;
        }
        foreach ($allowed_groups as $group_key) if (!in_array($group_key, $clean['tariffs']['group_order'], true)) $clean['tariffs']['group_order'][] = $group_key;

        $posted_columns = isset($tariffs['columns']) && is_array($tariffs['columns']) ? $tariffs['columns'] : array();
        foreach ($allowed_groups as $group) {
            $seen_columns = array();
            foreach (isset($posted_columns[$group]) && is_array($posted_columns[$group]) ? $posted_columns[$group] : array() as $column) {
                if (!is_array($column)) continue;
                $id = sanitize_key(isset($column['id']) ? $column['id'] : '');
                if ($id === '' || isset($seen_columns[$id])) continue;
                $seen_columns[$id] = true;
                $clean['tariffs']['columns'][$group][] = array(
                    'id'=>$id,
                    'label'=>self::sanitize_translations(isset($column['label']) ? $column['label'] : array()),
                );
            }
            // Compatibilité : si un ancien formulaire est envoyé sans colonne, conserver une colonne tarif.
            if (empty($clean['tariffs']['columns'][$group])) {
                $clean['tariffs']['columns'][$group][] = array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'));
            }

            foreach (isset($tariffs[$group]) && is_array($tariffs[$group]) ? $tariffs[$group] : array() as $row) {
                if (!is_array($row)) continue;
                $cells = array();
                $posted_cells = isset($row['cells']) && is_array($row['cells']) ? $row['cells'] : array();
                foreach ($clean['tariffs']['columns'][$group] as $column) {
                    $col_id = $column['id'];
                    $cell = isset($posted_cells[$col_id]) && is_array($posted_cells[$col_id]) ? $posted_cells[$col_id] : array();
                    $legacy_value = ($col_id === 'price' && isset($row['price'])) ? sanitize_text_field($row['price']) : '';
                    $cells[$col_id] = array(
                        'value'=>isset($cell['value']) ? sanitize_text_field($cell['value']) : $legacy_value,
                        'old_value'=>isset($cell['old_value']) ? sanitize_text_field($cell['old_value']) : '',
                    );
                }
                $legacy_price = isset($cells['price']['value']) ? $cells['price']['value'] : '';
                if ($legacy_price === '' && !empty($cells)) {
                    $first_cell = reset($cells);
                    $legacy_price = isset($first_cell['value']) ? (string)$first_cell['value'] : '';
                }
                $row_type = isset($row['row_type']) && $row['row_type'] === 'special' ? 'special' : 'standard';
                $sale_channel = isset($row['sale_channel']) ? sanitize_key($row['sale_channel']) : 'both';
                if (!in_array($sale_channel, array('both','online','onsite'), true)) $sale_channel = 'both';
                $clean['tariffs'][$group][] = array(
                    'enabled'=>self::bool($row,'enabled'),
                    'label'=>self::sanitize_translations(isset($row['label'])?$row['label']:array()),
                    'detail'=>self::sanitize_translations(isset($row['subtitle'])?$row['subtitle']:(isset($row['detail'])?$row['detail']:array())),
                    'subtitle'=>self::sanitize_translations(isset($row['subtitle'])?$row['subtitle']:(isset($row['detail'])?$row['detail']:array())),
                    'note'=>self::sanitize_translations(isset($row['note'])?$row['note']:array(), true),
                    'price'=>$legacy_price,
                    'cells'=>$cells,
                    'row_type'=>$row_type,
                    'special_badge'=>self::sanitize_translations(isset($row['special_badge'])?$row['special_badge']:array()),
                    'valid_from'=>self::date($row,'valid_from'),
                    'valid_to'=>self::date($row,'valid_to'),
                    'display_from'=>self::date($row,'display_from'),
                    'display_to'=>self::date($row,'display_to'),
                    'sale_channel'=>$sale_channel,
                    'purchase_url'=>self::sanitize_url_translations(isset($row['purchase_url'])?$row['purchase_url']:array()),
                    'show_special_dot'=>array_key_exists('show_special_dot',$row)?self::bool($row,'show_special_dot'):'1',
                    'label_color'=>self::optional_color($row,'label_color'),
                    'detail_color'=>self::optional_color($row,'subtitle_color'),
                    'subtitle_color'=>self::optional_color($row,'subtitle_color'),
                    'note_color'=>self::optional_color($row,'note_color'),
                    'price_color'=>self::optional_color($row,'price_color'),
                    'row_bg_color'=>self::color($row,'row_bg_color','#ffffff'),
                    'row_bg_transparent'=>self::bool($row,'row_bg_transparent'),
                    'row_border_color'=>self::optional_color($row,'row_border_color')
                );
            }
        }
        $print = isset($tariffs['print']) && is_array($tariffs['print']) ? $tariffs['print'] : array();
        $orientation = isset($print['orientation']) && $print['orientation'] === 'landscape' ? 'landscape' : 'portrait';
        $clean['tariffs']['print'] = array(
            'enabled'=>array_key_exists('enabled',$print)?self::bool($print,'enabled'):'1',
            'pdf_enabled'=>array_key_exists('pdf_enabled',$print)?self::bool($print,'pdf_enabled'):'1',
            'title'=>self::sanitize_translations(isset($print['title'])?$print['title']:array()),
            'footer'=>self::sanitize_translations(isset($print['footer'])?$print['footer']:array(), true),
            'show_generation_date'=>array_key_exists('show_generation_date',$print)?self::bool($print,'show_generation_date'):'1',
            'orientation'=>$orientation,
        );
        $clean['tariffs']['notes'] = self::sanitize_translations(isset($tariffs['notes'])?$tariffs['notes']:array(), true);
        $clean['tariffs']['payment_methods'] = self::sanitize_translations(isset($tariffs['payment_methods'])?$tariffs['payment_methods']:array(), true);
        $clean['tariffs']['payment_items'] = array();
        foreach (isset($tariffs['payment_items']) && is_array($tariffs['payment_items']) ? $tariffs['payment_items'] : array() as $row) {
            if (!is_array($row)) continue;
            $visible = array(); foreach (array('fr','en','de') as $lang) $visible[$lang] = isset($row['visible'][$lang]) && (string)$row['visible'][$lang] === '1' ? '1' : '0';
            $icon = isset($row['icon']) ? sanitize_key($row['icon']) : 'card';
            if (!in_array($icon, array('card','cash','custom','none'), true)) $icon = 'card';
            $clean['tariffs']['payment_items'][] = array(
                'enabled'=>self::bool($row,'enabled'),'icon'=>$icon,'custom_svg'=>self::sanitize_svg(isset($row['custom_svg'])?$row['custom_svg']:''),'custom_svg_force_color'=>self::bool($row,'custom_svg_force_color'),'label'=>self::sanitize_translations(isset($row['label'])?$row['label']:array()),'visible'=>$visible,
                'bg_color'=>self::color($row,'bg_color','#006757'),'bg_transparent'=>self::bool($row,'bg_transparent'),'icon_color'=>self::color($row,'icon_color','#ffffff'),'text_color'=>self::color($row,'text_color','#ffffff'),'border_color'=>self::color($row,'border_color','#006757'),'border_enabled'=>self::bool($row,'border_enabled')
            );
        }
        // Champs historiques conservés pour compatibilité avec les anciennes versions.
        $clean['tariffs']['payment_icons'] = array();
        $clean['tariffs']['payment_styles'] = array();

        $quote = isset($raw['quote_page']) && is_array($raw['quote_page']) ? $raw['quote_page'] : array();
        if (isset($current['quote_page']) && is_array($current['quote_page'])) $quote = array_replace_recursive($current['quote_page'], $quote);
        $clean['quote_page'] = array(
            'enabled' => self::bool($quote, 'enabled'),
            'form_shortcode' => self::sanitize_cf7_shortcode(isset($quote['form_shortcode']) ? $quote['form_shortcode'] : ''),
            'title' => self::sanitize_translations(isset($quote['title']) ? $quote['title'] : array()),
            'intro' => self::sanitize_translations(isset($quote['intro']) ? $quote['intro'] : array(), true),
            'form_title' => self::sanitize_translations(isset($quote['form_title']) ? $quote['form_title'] : array()),
            'quick_links_title' => self::sanitize_translations(isset($quote['quick_links_title']) ? $quote['quick_links_title'] : array()),
            'quick_links_intro' => self::sanitize_translations(isset($quote['quick_links_intro']) ? $quote['quick_links_intro'] : array(), true),
            'important_messages' => array(),
            'quick_links' => array(),
            'info_blocks' => array(),
            'accordions' => array(),
        );
        foreach (isset($quote['important_messages']) && is_array($quote['important_messages']) ? $quote['important_messages'] : array() as $row) {
            if (!is_array($row)) continue;
            $clean['quote_page']['important_messages'][] = array(
                'enabled'=>self::bool($row,'enabled'),
                'color'=>self::color($row,'color','#ef7658'),
                'position'=>(isset($row['position']) && $row['position']==='after') ? 'after' : 'before',
                'title'=>self::sanitize_translations(isset($row['title'])?$row['title']:array()),
                'text'=>self::sanitize_translations(isset($row['text'])?$row['text']:array(), true),
            );
        }
        foreach (isset($quote['quick_links']) && is_array($quote['quick_links']) ? $quote['quick_links'] : array() as $row) {
            if (!is_array($row)) continue;
            $clean['quote_page']['quick_links'][] = array(
                'enabled'=>self::bool($row,'enabled'),
                'icon'=>isset($row['icon']) ? sanitize_text_field(wp_unslash((string)$row['icon'])) : '',
                'label'=>self::sanitize_translations(isset($row['label'])?$row['label']:array()),
                'url'=>self::sanitize_translation_urls(isset($row['url'])?$row['url']:array()),
            );
        }
        foreach (isset($quote['info_blocks']) && is_array($quote['info_blocks']) ? $quote['info_blocks'] : array() as $row) {
            if (!is_array($row)) continue;
            $position = isset($row['position']) && in_array((string)$row['position'], array('before','after'), true) ? (string)$row['position'] : 'before';
            $clean['quote_page']['info_blocks'][] = array(
                'enabled'=>self::bool($row,'enabled'),
                'title'=>self::sanitize_translations(isset($row['title'])?$row['title']:array()),
                'text'=>self::sanitize_translations(isset($row['text'])?$row['text']:array(), true),
                'position'=>$position,
                'show_button'=>self::bool($row,'show_button'),
                'button_label'=>self::sanitize_translations(isset($row['button_label'])?$row['button_label']:array()),
                'button_url'=>self::sanitize_translation_urls(isset($row['button_url'])?$row['button_url']:array()),
            );
        }
        foreach (isset($quote['accordions']) && is_array($quote['accordions']) ? $quote['accordions'] : array() as $row) {
            if (!is_array($row)) continue;
            $position = isset($row['position']) && in_array((string)$row['position'], array('before','after'), true) ? (string)$row['position'] : 'after';
            $clean['quote_page']['accordions'][] = array(
                'enabled'=>self::bool($row,'enabled'),
                'title'=>self::sanitize_translations(isset($row['title'])?$row['title']:array()),
                'text'=>self::sanitize_translations(isset($row['text'])?$row['text']:array(), true),
                'position'=>$position,
                'show_button'=>self::bool($row,'show_button'),
                'button_label'=>self::sanitize_translations(isset($row['button_label'])?$row['button_label']:array()),
                'button_url'=>self::sanitize_translation_urls(isset($row['button_url'])?$row['button_url']:array()),
            );
        }
        return $clean;
    }

    private static function sanitize_cf7_shortcode($value) {
        $value = trim(wp_unslash((string) $value));
        if ($value === '') return '';

        $value = html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'));
        $value = wp_strip_all_tags($value);
        $value = preg_replace('/[\r\n\t]+/', ' ', $value);
        $value = trim($value);

        // Liste blanche : uniquement Contact Form 7.
        // Accepte les variantes standards et le slash final éventuel.
        if (!preg_match('/^\[contact-form-7(?:\s+[^\]]*)?\s*\/?\]$/i', $value)) {
            return '';
        }

        return $value;
    }

    private static function sanitize_translation_urls($values) {
        $clean = array('fr'=>'','en'=>'','de'=>'');
        if (!is_array($values)) return $clean;
        foreach ($clean as $lang=>$unused) $clean[$lang] = isset($values[$lang]) ? esc_url_raw(wp_unslash((string)$values[$lang])) : '';
        return $clean;
    }

    private static function incomplete_sections($raw) {
        $complete = isset($raw['_complete']) && is_array($raw['_complete']) ? $raw['_complete'] : array();
        $expected = array('general','regular_periods','holidays','domain_rules','exceptions','alerts','tariffs','quote_page');
        $missing = array();
        foreach ($expected as $section) {
            if (!isset($complete[$section]) || (string) $complete[$section] !== '1') $missing[] = $section;
        }
        return $missing;
    }

    private static function warnings($settings) {
        $warnings = array();
        if (empty($settings['general']['season_start']) || empty($settings['general']['season_end'])) $warnings[] = 'La saison n’a pas de dates complètes.';
        if ($settings['general']['season_start'] && $settings['general']['season_end'] && $settings['general']['season_end'] < $settings['general']['season_start']) $warnings[] = 'La fin de saison précède le début de saison.';
        foreach ($settings['regular_periods'] as $row) {
            if ($row['enabled'] !== '1') continue;
            if (!$row['start'] || !$row['end'] || !$row['open'] || !$row['close']) $warnings[] = 'Une période habituelle active est incomplète.';
            if (empty($row['weekdays'])) $warnings[] = 'Une période habituelle active ne contient aucun jour concerné.';
            if ($row['start'] && $row['end'] && $row['end'] < $row['start']) $warnings[] = 'Une période habituelle se termine avant de commencer.';
        }
        for ($i = 0; $i < count($settings['regular_periods']); $i++) {
            $a = $settings['regular_periods'][$i]; if ($a['enabled'] !== '1') continue;
            for ($j = $i + 1; $j < count($settings['regular_periods']); $j++) {
                $b = $settings['regular_periods'][$j]; if ($b['enabled'] !== '1') continue;
                $dates_overlap = $a['start'] && $a['end'] && $b['start'] && $b['end'] && $a['start'] <= $b['end'] && $b['start'] <= $a['end'];
                if ($dates_overlap && array_intersect((array)$a['weekdays'], (array)$b['weekdays'])) $warnings[] = 'Deux périodes habituelles actives se chevauchent sur les mêmes jours.';
            }
        }
        foreach ($settings['school_holidays'] as $row) if ($row['enabled'] === '1' && $row['start'] && $row['end'] && $row['end'] < $row['start']) $warnings[] = 'Une période de vacances se termine avant de commencer.';
        foreach ($settings['domain_rules'] as $row) if ($row['enabled'] === '1' && $row['exclude_school_holidays'] === '1' && empty($settings['school_holidays'])) $warnings[] = 'Une règle du domaine exclut les vacances scolaires, mais aucune période de vacances n’est renseignée.';
        foreach ($settings['domain_rules'] as $row) if ($row['enabled'] === '1') foreach (array('fr','en','de') as $lang) if (empty($row['info'][$lang])) $warnings[] = 'Une règle active du domaine n’est pas traduite en ' . strtoupper($lang) . '.';
        foreach ($settings['exceptions'] as $row) {
            if ($row['enabled'] !== '1') continue;
            if (!$row['start'] || !$row['end']) $warnings[] = 'Une exception active n’a pas de période complète.';
            if ($row['start'] && $row['end'] && $row['end'] < $row['start']) $warnings[] = 'Une exception se termine avant de commencer.';
            if ($row['type'] === 'hours' && (!$row['open'] || !$row['close'])) $warnings[] = 'Des horaires exceptionnels actifs n’ont pas d’heures complètes.';
        }
        for ($i = 0; $i < count($settings['exceptions']); $i++) {
            $a = $settings['exceptions'][$i]; if ($a['enabled'] !== '1') continue;
            for ($j = $i + 1; $j < count($settings['exceptions']); $j++) {
                $b = $settings['exceptions'][$j]; if ($b['enabled'] !== '1') continue;
                if ($a['start'] && $a['end'] && $b['start'] && $b['end'] && $a['start'] <= $b['end'] && $b['start'] <= $a['end'] && (int)$a['priority'] === (int)$b['priority']) $warnings[] = 'Deux exceptions de même priorité se chevauchent ; une fermeture l’emportera.';
            }
        }
        foreach ($settings['alerts'] as $row) if ($row['enabled'] === '1') foreach (array('fr','en','de') as $lang) if (empty($row['title'][$lang]) || empty($row['message'][$lang])) $warnings[] = 'Une alerte active n’est pas entièrement traduite en ' . strtoupper($lang) . '.';
        foreach ($settings['alerts'] as $row) if ($row['enabled'] === '1' && $row['start'] && $row['end'] && $row['end'] < $row['start']) $warnings[] = 'Une alerte se termine avant de commencer.';
        foreach (array('individual','reduced','groups') as $group) foreach ($settings['tariffs'][$group] as $row) if ($row['enabled'] === '1') {
            $has_value = false;
            if (!empty($row['cells']) && is_array($row['cells'])) {
                foreach ($row['cells'] as $cell) {
                    if (is_array($cell) && trim((string)($cell['value'] ?? '')) !== '') { $has_value = true; break; }
                }
            }
            if (!$has_value && trim((string)($row['price'] ?? '')) === '') $warnings[] = 'Une ligne tarifaire active ne contient aucune valeur de prix.';
            foreach (array('fr','en','de') as $lang) if (empty($row['label'][$lang])) $warnings[] = 'Une ligne tarifaire active n’est pas traduite en ' . strtoupper($lang) . '.';
        }
        return array_values(array_unique($warnings));
    }

    private static function input($name, $value, $label, $type = 'text') { ?><label class="htp-field"><span><?php echo esc_html($label); ?></span><input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>"></label><?php }
    private static function sanitize_timezone($value) {
        $value = sanitize_text_field((string)$value);
        try {
            new DateTimeZone($value);
            return $value;
        } catch (Exception $e) {
            return 'Europe/Paris';
        }
    }
    private static function optional_color_input($name, $value, $label) {
        $color = sanitize_hex_color($value);
        $inherit = !$color;
        ?>
        <label class="htp-field"><span><?php echo esc_html($label); ?></span>
            <span class="htp-optional-color">
                <input type="color" class="htp-optional-color-picker" value="<?php echo esc_attr($color ?: '#000000'); ?>" <?php disabled($inherit); ?>>
                <input type="hidden" class="htp-optional-color-value" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($color ?: ''); ?>">
                <span class="htp-optional-color-inherit-wrap"><input type="checkbox" class="htp-optional-color-inherit" <?php checked($inherit); ?>> Hériter du thème</span>
            </span>
        </label>
        <?php
    }
    private static function translated_input($name, $values, $label, $type = 'text') { ?>
        <div class="htp-field htp-local-translation" data-htp-local-lang="fr"><span><?php echo esc_html($label); ?></span><div class="htp-mini-lang" role="tablist" aria-label="Langue du champ"><?php foreach(array('fr','en','de') as $lang): ?><button type="button" class="button button-small <?php echo $lang==='fr'?'button-primary':''; ?>" data-htp-local-language="<?php echo esc_attr($lang); ?>"><?php echo esc_html(strtoupper($lang)); ?></button><?php endforeach; ?></div><?php foreach(array('fr','en','de') as $lang): ?><input class="htp-local-lang-field" data-lang="<?php echo esc_attr($lang); ?>" type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name . '[' . $lang . ']'); ?>" value="<?php echo esc_attr(isset($values[$lang])?$values[$lang]:''); ?>" <?php echo $lang==='fr'?'':'hidden'; ?>><?php endforeach; ?></div><?php }
    private static function translated_textarea($name, $values, $label) { ?>
        <div class="htp-field htp-local-translation" data-htp-local-lang="fr"><span><?php echo esc_html($label); ?></span><div class="htp-mini-lang" role="tablist" aria-label="Langue du champ"><?php foreach(array('fr','en','de') as $lang): ?><button type="button" class="button button-small <?php echo $lang==='fr'?'button-primary':''; ?>" data-htp-local-language="<?php echo esc_attr($lang); ?>"><?php echo esc_html(strtoupper($lang)); ?></button><?php endforeach; ?></div><?php foreach(array('fr','en','de') as $lang): ?><textarea class="htp-local-lang-field" data-lang="<?php echo esc_attr($lang); ?>" rows="4" name="<?php echo esc_attr($name . '[' . $lang . ']'); ?>" <?php echo $lang==='fr'?'':'hidden'; ?>><?php echo esc_textarea(isset($values[$lang])?$values[$lang]:''); ?></textarea><?php endforeach; ?></div><?php }
    private static function enabled($name, $value) { ?><label class="htp-enabled"><input type="hidden" name="<?php echo esc_attr($name); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($value, '1'); ?>> Actif</label><?php }
    private static function checkbox($name, $value, $label) { ?><label><input type="hidden" name="<?php echo esc_attr($name); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($value, '1'); ?>> <?php echo esc_html($label); ?></label><?php }
    private static function select($name, $value, $label, $options) { ?><label class="htp-field"><span><?php echo esc_html($label); ?></span><select name="<?php echo esc_attr($name); ?>"><?php foreach ($options as $key=>$text) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($value,$key); ?>><?php echo esc_html($text); ?></option><?php endforeach; ?></select></label><?php }
    private static function weekdays($name, $selected) {
        $days=array('1'=>'L','2'=>'M','3'=>'M','4'=>'J','5'=>'V','6'=>'S','7'=>'D');
        $selected=self::normalize_weekdays($selected);
        $present_name=preg_replace('/\[weekdays\]$/','[weekdays_present]',$name);
        $csv_name=preg_replace('/\[weekdays\]$/','[weekdays_csv]',$name);
        $touched_name=preg_replace('/\[weekdays\]$/','[weekdays_touched]',$name);
        ?><fieldset class="htp-weekdays" data-htp-weekdays><legend>Jours concernés</legend><input type="hidden" name="<?php echo esc_attr($present_name); ?>" value="1"><input type="hidden" class="htp-weekdays-csv" name="<?php echo esc_attr($csv_name); ?>" value="<?php echo esc_attr(implode(',', $selected)); ?>"><input type="hidden" class="htp-weekdays-touched" name="<?php echo esc_attr($touched_name); ?>" value="0"><?php foreach($days as $value=>$label): ?><label><input type="checkbox" name="<?php echo esc_attr($name); ?>[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value,$selected,true)); ?>><?php echo esc_html($label); ?></label><?php endforeach; ?></fieldset><?php
    }

    private static function sanitize_svg($svg) {
        $svg = trim((string)$svg);
        if ($svg === '') return '';
        // Un SVG personnalisé est du contenu actif : on autorise uniquement les
        // primitives graphiques nécessaires et aucun script, lien, style ou événement.
        $allowed = Parcs_HT_Defaults::svg_allowed_tags();
        $clean = wp_kses($svg, $allowed);
        return stripos($clean, '<svg') !== false ? $clean : '';
    }

    private static function sanitize_translations($values, $textarea = false) { $out=array(); foreach(array('fr','en','de') as $lang){$value=isset($values[$lang])?$values[$lang]:'';$out[$lang]=$textarea?sanitize_textarea_field($value):sanitize_text_field($value);} return $out; }
    private static function sanitize_url_translations($values) { $out=array(); foreach(array('fr','en','de') as $lang){$out[$lang]=isset($values[$lang])?esc_url_raw($values[$lang]):'';} return $out; }
    private static function optional_color($row,$key){$v=isset($row[$key])?trim((string)$row[$key]):'';return $v===''?'':(sanitize_hex_color($v)?:'');}
    private static function text($row,$key){return isset($row[$key])?sanitize_text_field($row[$key]):'';}
    private static function bool($row,$key){return isset($row[$key]) && (string)$row[$key]==='1'?'1':'0';}
    private static function date($row,$key){$v=self::text($row,$key);return preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)?$v:'';}
    private static function time($row,$key){$v=self::text($row,$key);return preg_match('/^\d{2}:\d{2}$/',$v)?$v:'';}
    private static function datetime($row,$key){$v=self::text($row,$key);return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$v)?$v:'';}
    private static function number($row,$key,$min,$max){$v=isset($row[$key])?(int)$row[$key]:0;return (string)max($min,min($max,$v));}
    private static function optional_number($row,$key,$min,$max){if(!isset($row[$key])||$row[$key]==='')return '';$v=(int)$row[$key];return (string)max($min,min($max,$v));}
    private static function color($row,$key,$fallback){$v=isset($row[$key])?sanitize_hex_color($row[$key]):'';return $v?$v:$fallback;}

    private static function resolve_weekdays_for_save($row, $current_days, $default_days = array()) {
        $current_days = self::normalize_weekdays($current_days);
        $default_days = self::normalize_weekdays($default_days);
        $touched = isset($row['weekdays_touched']) && (string)$row['weekdays_touched'] === '1';

        // Tant que l'utilisateur n'a pas modifié les jours dans cette ligne,
        // une autre sauvegarde ne doit jamais les effacer.
        if (!$touched && !empty($current_days)) {
            return $current_days;
        }

        // Nouvelle ligne : utiliser la valeur transmise par le champ compact,
        // ou les jours par défaut si rien n'a encore été envoyé.
        if (!$touched && empty($current_days)) {
            $posted = self::weekdays_clean($row);
            return !empty($posted) ? $posted : $default_days;
        }

        // L'utilisateur a réellement touché au sélecteur : accepter son choix,
        // y compris volontairement aucun jour.
        return self::weekdays_clean($row);
    }

    private static function normalize_weekdays($value) {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') return array();
            $value = preg_split('/[^1-7]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        } elseif (!is_array($value)) {
            $value = (array)$value;
        }
        $allowed = array('1','2','3','4','5','6','7');
        $out = array_values(array_unique(array_intersect($allowed, array_map('strval', $value))));
        sort($out, SORT_NUMERIC);
        return $out;
    }

    private static function weekdays_clean($row){
        if (isset($row['weekdays_csv'])) {
            $days = sanitize_text_field((string)$row['weekdays_csv']);
        } else {
            $days = isset($row['weekdays']) ? $row['weekdays'] : array();
        }
        return self::normalize_weekdays($days);
    }
}
