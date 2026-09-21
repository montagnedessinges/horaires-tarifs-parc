<?php

if (!defined('ABSPATH')) { exit; }

/** Administration Communication 1.17.9 : Pop-up et Calendrier de l’Avent séparés. */
final class Parcs_HT_Admin_Communication_1179 {
    const POPUP_PAGE = 'parcs-ht-popup-1179';
    const ADVENT_PAGE = 'parcs-ht-advent-1179';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'route_legacy'), 2);
        add_action('admin_menu', array(__CLASS__, 'menu'), 66);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'), 80);
    }

    public static function menu() {
        add_submenu_page(null, 'Pop-up', 'Pop-up', 'manage_options', self::POPUP_PAGE, array(__CLASS__, 'popup_page'));
        add_submenu_page(null, 'Calendrier de l’Avent', 'Calendrier de l’Avent', 'manage_options', self::ADVENT_PAGE, array(__CLASS__, 'advent_page'));
    }

    private static function page_url($page, $args = array()) {
        return add_query_arg(array_merge(array('page'=>$page), (array)$args), admin_url('admin.php'));
    }

    private static function copy_query_args($allowed) {
        $out = array();
        foreach ((array)$allowed as $key) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- paramètres de navigation/retour uniquement.
            if (!isset($_GET[$key])) continue;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- paramètres de navigation/retour uniquement.
            $value = sanitize_text_field(wp_unslash($_GET[$key]));
            if ($value !== '') $out[$key] = $value;
        }
        return $out;
    }

    public static function route_legacy() {
        if (!is_admin() || !current_user_can('manage_options') || !class_exists('Parcs_HT_Admin')) return;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- routage de compatibilité en lecture seule.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ($page !== Parcs_HT_Admin::PAGE) return;

        if ($tab === 'htp-alerts') {
            wp_safe_redirect(self::page_url(self::POPUP_PAGE, self::copy_query_args(array('updated','preserved'))));
            exit;
        }
        if ($tab === 'htp-advent') {
            $args = self::copy_query_args(array('campaign','advent_view','day','teaser','partner','result','import_token','advent_notice','advent_error','advent_fragment'));
            wp_safe_redirect(self::page_url(self::ADVENT_PAGE, $args));
            exit;
        }
    }

    public static function assets($hook) {
        if ($hook === 'admin_page_' . self::POPUP_PAGE) {
            wp_enqueue_style('parcs-ht-admin-communication-1179', PARCS_HT_URL . 'assets/admin-communication-1179.css', array(), PARCS_HT_VERSION);
            wp_enqueue_script('parcs-ht-admin-communication-1179', PARCS_HT_URL . 'assets/admin-communication-1179.js', array('jquery'), PARCS_HT_VERSION, true);
            return;
        }
        if ($hook !== 'admin_page_' . self::ADVENT_PAGE) return;

        wp_enqueue_media();
        wp_enqueue_style('parcs-ht-advent-admin', PARCS_HT_URL . 'assets/advent-admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-admin-communication-1179', PARCS_HT_URL . 'assets/admin-communication-1179.css', array('parcs-ht-advent-admin'), PARCS_HT_VERSION);
        // Le handle historique est conservé pour que l’apparence Avent garde sa dépendance sans charger l’ancien script de navigation.
        wp_enqueue_script('parcs-ht-advent-admin', PARCS_HT_URL . 'assets/admin-advent-1179.js', array('jquery'), PARCS_HT_VERSION, true);
        if (class_exists('Parcs_HT_Advent_Appearance')) {
            Parcs_HT_Advent_Appearance::admin_assets('toplevel_page_parcs-horaires-tarifs');
        }
    }

    private static function active_year() {
        $settings = Parcs_HT_Defaults::settings();
        $year = isset($settings['active_season_year']) ? (string)$settings['active_season_year'] : '';
        if (preg_match('/^20\d{2}$/', $year)) return $year;
        $all = Parcs_HT_Defaults::all_settings();
        foreach ((array)($all['seasons'] ?? array()) as $candidate=>$unused) {
            if (preg_match('/^20\d{2}$/', (string)$candidate)) return (string)$candidate;
        }
        return wp_date('Y');
    }

    private static function communication_nav($active) {
        echo '<nav class="htp-1179-nav" aria-label="Communication">';
        echo '<a class="button' . ($active === 'popup' ? ' button-primary' : '') . '" href="' . esc_url(self::page_url(self::POPUP_PAGE)) . '">Pop-up</a>';
        echo '<a class="button' . ($active === 'advent' ? ' button-primary' : '') . '" href="' . esc_url(self::page_url(self::ADVENT_PAGE)) . '">Calendrier de l’Avent</a>';
        echo '</nav>';
    }

    private static function popup_source_counts() {
        $all = Parcs_HT_Defaults::all_settings();
        $counts = array('alerts'=>0,'exceptions'=>0,'events'=>0);
        foreach ((array)($all['alerts'] ?? array()) as $row) {
            if (is_array($row) && (string)($row['enabled'] ?? '0') === '1') $counts['alerts']++;
        }
        foreach ((array)($all['seasons'] ?? array()) as $season) {
            if (!is_array($season)) continue;
            foreach ((array)($season['exceptions'] ?? array()) as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1') $counts['exceptions']++;
            }
            foreach ((array)($season['special_periods'] ?? array()) as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1') $counts['events']++;
            }
        }
        return $counts;
    }

    private static function notices() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- messages de confirmation uniquement.
        if (isset($_GET['updated'])) echo '<div class="notice notice-success is-dismissible"><p>Les réglages du pop-up ont été enregistrés.</p></div>';
        if (isset($_GET['preserved'])) echo '<div class="notice notice-warning is-dismissible"><p>Une section incomplète a été préservée automatiquement afin d’éviter toute perte de données.</p></div>';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    public static function popup_page() {
        if (!current_user_can('manage_options')) return;
        $settings = Parcs_HT_Defaults::settings(self::active_year());
        $alerts = isset($settings['alerts']) && is_array($settings['alerts']) ? $settings['alerts'] : array();
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $counts = self::popup_source_counts();
        $periods_url = class_exists('Parcs_HT_Admin_Navigation')
            ? self::page_url(Parcs_HT_Admin_Navigation::PERIODS_PAGE)
            : admin_url('admin.php');
        ?>
        <div class="wrap htp-1179 htp-1179-popup">
            <h1>Pop-up</h1>
            <p class="description">Gérez ici uniquement les alertes autonomes. Les pop-up d’événements et d’horaires exceptionnels restent attachés à leur contenu d’origine afin d’éviter toute duplication.</p>
            <?php self::notices(); self::communication_nav('popup'); ?>

            <section class="htp-1179-card">
                <div class="htp-1179-card-head"><div><h2>Sources du moteur de pop-up</h2><p>Le moteur public commun reste inchangé.</p></div></div>
                <div class="htp-1179-stats">
                    <div><strong><?php echo (int)$counts['alerts']; ?></strong><span>alertes autonomes actives</span></div>
                    <div><strong><?php echo (int)$counts['events']; ?></strong><span>événements avec pop-up</span></div>
                    <div><strong><?php echo (int)$counts['exceptions']; ?></strong><span>exceptions avec pop-up</span></div>
                </div>
                <p><a class="button" href="<?php echo esc_url($periods_url); ?>">Gérer les événements et exceptions</a></p>
            </section>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-htp-popup-1179>
                <input type="hidden" name="action" value="parcs_ht_save">
                <input type="hidden" name="season_year" value="<?php echo esc_attr(self::active_year()); ?>">
                <input type="hidden" name="htp_active_tab" value="htp-alerts">
                <input type="hidden" name="htp_save_active" value="1">
                <input type="hidden" name="settings[_complete][alerts]" value="1">
                <?php wp_nonce_field('parcs_ht_save'); ?>

                <section class="htp-1179-card">
                    <div class="htp-1179-card-head"><div><h2>Alertes autonomes</h2><p>Une alerte désactivée reste enregistrée, mais tous ses réglages détaillés sont masqués.</p></div><button type="button" class="button button-primary" data-popup-add>Ajouter un pop-up</button></div>
                    <div class="htp-1179-popup-list" data-popup-list>
                        <?php foreach ($alerts as $index=>$row) self::popup_row($index, $row); ?>
                    </div>
                    <template data-popup-template><?php self::popup_row('__INDEX__', array('enabled'=>'0','published'=>'0','start'=>'','end'=>'','title'=>array(),'message'=>array(),'button_label'=>array(),'button_url'=>array(),'show_button'=>'0'), true); ?></template>
                </section>

                <?php self::popup_appearance($general); ?>
                <div class="htp-1179-save"><?php submit_button('Enregistrer les pop-up', 'primary', 'submit', false); ?></div>
            </form>
        </div>
        <?php
    }

    private static function popup_row($index, $row, $template = false) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'enabled'=>'0','published'=>'0','start'=>'','end'=>'','title'=>array(),'message'=>array(),
            'button_label'=>array(),'button_url'=>array(),'show_button'=>'0',
        ));
        $base = 'settings[alerts][' . $index . ']';
        $enabled = (string)$row['enabled'] === '1';
        $title = trim((string)(is_array($row['title']) ? ($row['title']['fr'] ?? '') : ''));
        if ($title === '') $title = 'Pop-up sans titre';
        ?>
        <article class="htp-1179-popup-row<?php echo $template ? ' is-template' : ''; ?>" data-popup-row>
            <div class="htp-1179-popup-head">
                <label class="htp-1179-toggle"><input type="hidden" name="<?php echo esc_attr($base . '[enabled]'); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($base . '[enabled]'); ?>" value="1" data-popup-enabled <?php checked($enabled); ?>> <span>Activer ce pop-up</span></label>
                <div class="htp-1179-popup-summary"><strong data-popup-summary-title><?php echo esc_html($title); ?></strong><small data-popup-summary-state><?php echo esc_html($enabled ? ((string)$row['published'] === '1' ? 'Actif · publié' : 'Actif · brouillon') : 'Désactivé'); ?></small></div>
                <button type="button" class="button-link-delete" data-popup-remove>Supprimer</button>
            </div>
            <div class="htp-1179-popup-details" data-popup-details <?php echo $enabled ? '' : 'hidden'; ?>>
                <div class="htp-1179-grid htp-1179-grid-3">
                    <label><span>Statut</span><select name="<?php echo esc_attr($base . '[published]'); ?>" data-popup-published><option value="0" <?php selected((string)$row['published'], '0'); ?>>Brouillon</option><option value="1" <?php selected((string)$row['published'], '1'); ?>>Publié</option></select></label>
                    <label><span>Début d’affichage</span><input type="datetime-local" name="<?php echo esc_attr($base . '[start]'); ?>" value="<?php echo esc_attr($row['start']); ?>"></label>
                    <label><span>Fin automatique facultative</span><input type="datetime-local" name="<?php echo esc_attr($base . '[end]'); ?>" value="<?php echo esc_attr($row['end']); ?>"></label>
                </div>
                <div class="htp-1179-languages">
                    <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?>
                        <fieldset><legend><?php echo esc_html($label); ?></legend>
                            <label><span>Titre</span><input type="text" data-popup-title="<?php echo esc_attr($lang); ?>" name="<?php echo esc_attr($base . '[title][' . $lang . ']'); ?>" value="<?php echo esc_attr(is_array($row['title']) ? ($row['title'][$lang] ?? '') : ''); ?>"></label>
                            <label><span>Message</span><textarea rows="4" data-popup-message="<?php echo esc_attr($lang); ?>" name="<?php echo esc_attr($base . '[message][' . $lang . ']'); ?>"><?php echo esc_textarea(is_array($row['message']) ? ($row['message'][$lang] ?? '') : ''); ?></textarea></label>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                <div class="htp-1179-button-block">
                    <label class="htp-1179-toggle"><input type="hidden" name="<?php echo esc_attr($base . '[show_button]'); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr($base . '[show_button]'); ?>" value="1" data-popup-button-enabled <?php checked((string)$row['show_button'], '1'); ?>> <span>Afficher un bouton</span></label>
                    <div class="htp-1179-languages htp-1179-button-details" data-popup-button-details <?php echo (string)$row['show_button'] === '1' ? '' : 'hidden'; ?>>
                        <?php foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label) : ?>
                            <fieldset><legend><?php echo esc_html($label); ?></legend>
                                <label><span>Texte du bouton</span><input type="text" data-popup-button-label="<?php echo esc_attr($lang); ?>" name="<?php echo esc_attr($base . '[button_label][' . $lang . ']'); ?>" value="<?php echo esc_attr(is_array($row['button_label']) ? ($row['button_label'][$lang] ?? '') : ''); ?>"></label>
                                <label><span>Lien</span><input type="url" name="<?php echo esc_attr($base . '[button_url][' . $lang . ']'); ?>" value="<?php echo esc_attr(is_array($row['button_url']) ? ($row['button_url'][$lang] ?? '') : ''); ?>"></label>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="htp-1179-preview-actions"><label>Langue <select data-popup-preview-language><option value="fr">FR</option><option value="en">EN</option><option value="de">DE</option></select></label><button type="button" class="button" data-popup-preview>Prévisualiser</button></div>
            </div>
        </article>
        <?php
    }

    private static function popup_appearance($g) {
        ?>
        <section class="htp-1179-card">
            <div class="htp-1179-card-head"><div><h2>Apparence commune du pop-up</h2><p>Ces réglages s’appliquent aux alertes autonomes, aux événements et aux exceptions qui utilisent le même moteur.</p></div></div>
            <div class="htp-1179-grid htp-1179-grid-4">
                <?php self::appearance_color('alert_bg_color', 'Fenêtre — fond', $g['alert_bg_color'] ?? '#006757'); ?>
                <?php self::appearance_color('alert_title_color', 'Titre — texte', $g['alert_title_color'] ?? '#ffffff'); ?>
                <?php self::appearance_color('alert_text_color', 'Message — texte', $g['alert_text_color'] ?? '#ffffff'); ?>
                <?php self::appearance_color('alert_button_bg_color', 'Bouton — fond', $g['alert_button_bg_color'] ?? '#ef7b5b'); ?>
                <?php self::appearance_color('alert_button_text_color', 'Bouton — texte', $g['alert_button_text_color'] ?? '#ffffff'); ?>
                <label><span>Réafficher après fermeture (heures)</span><input type="number" min="1" max="720" name="settings[general][alert_reappear_hours]" value="<?php echo esc_attr($g['alert_reappear_hours'] ?? '1'); ?>"></label>
            </div>
            <details class="htp-1179-advanced">
                <summary>Réglages avancés d’apparence</summary>
                <div class="htp-1179-grid htp-1179-grid-4">
                    <?php self::appearance_color('alert_border_color', 'Fenêtre — bordure', $g['alert_border_color'] ?? '#ef7b5b'); ?>
                    <label><span>Bordure — épaisseur (px)</span><input type="number" min="0" max="12" name="settings[general][alert_border_width]" value="<?php echo esc_attr($g['alert_border_width'] ?? '3'); ?>"></label>
                    <label><span>Angles arrondis (px)</span><input type="number" min="0" max="40" name="settings[general][alert_radius]" value="<?php echo esc_attr($g['alert_radius'] ?? '16'); ?>"></label>
                    <?php self::appearance_color('alert_button_border_color', 'Bouton — bordure', $g['alert_button_border_color'] ?? '#ef7b5b'); ?>
                    <?php self::appearance_color('alert_close_bg_color', 'Fermer × — fond', $g['alert_close_bg_color'] ?? '#ffffff'); ?>
                    <?php self::appearance_color('alert_close_text_color', 'Fermer × — couleur', $g['alert_close_text_color'] ?? '#222222'); ?>
                    <?php self::appearance_color('alert_overlay_color', 'Arrière-plan écran', $g['alert_overlay_color'] ?? '#000000'); ?>
                    <label><span>Opacité arrière-plan (%)</span><input type="number" min="0" max="100" name="settings[general][alert_overlay_opacity]" value="<?php echo esc_attr($g['alert_overlay_opacity'] ?? '68'); ?>"></label>
                    <label class="htp-1179-toggle"><input type="hidden" name="settings[general][alert_shadow]" value="0"><input type="checkbox" name="settings[general][alert_shadow]" value="1" <?php checked((string)($g['alert_shadow'] ?? '1'), '1'); ?>> <span>Afficher une ombre</span></label>
                </div>
            </details>
        </section>
        <?php
    }

    private static function appearance_color($key, $label, $value) {
        echo '<label><span>' . esc_html($label) . '</span><input type="color" name="settings[general][' . esc_attr($key) . ']" value="' . esc_attr($value) . '"></label>';
    }

    private static function selected_advent_campaign() {
        if (!class_exists('Parcs_HT_Advent')) return null;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection de campagne en lecture seule.
        $requested = isset($_GET['campaign']) ? sanitize_key(wp_unslash($_GET['campaign'])) : '';
        if ($requested !== '') {
            $campaign = Parcs_HT_Advent::campaign($requested, true);
            if (is_array($campaign)) return $campaign;
        }
        $store = Parcs_HT_Advent::store();
        $park = Parcs_HT_Advent::installation_park_code();
        $rows = array();
        foreach ((array)($store['campaigns'] ?? array()) as $id=>$campaign) {
            if (!is_array($campaign) || (string)($campaign['parc_code'] ?? '') !== $park) continue;
            $rows[(string)$id] = $campaign;
        }
        uasort($rows, static function ($a, $b) {
            return ((int)($b['annee'] ?? 0)) <=> ((int)($a['annee'] ?? 0));
        });
        return $rows ? reset($rows) : null;
    }

    public static function advent_page() {
        if (!current_user_can('manage_options')) return;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- mode fragment de navigation interne en lecture seule.
        $fragment = isset($_GET['advent_fragment']) && sanitize_text_field(wp_unslash($_GET['advent_fragment'])) === '1';
        if ($fragment) {
            if (class_exists('Parcs_HT_Advent_Admin')) Parcs_HT_Advent_Admin::render_workspace();
            return;
        }
        $campaign = self::selected_advent_campaign();
        ?>
        <div class="wrap htp-1179 htp-1179-advent">
            <h1>Calendrier de l’Avent</h1>
            <p class="description">Chaque calendrier reste une campagne indépendante avec ses propres données, dates, apparence et shortcodes. Aucun contexte d’année de saison n’est appliqué à ce module.</p>
            <?php self::communication_nav('advent'); ?>
            <?php if (is_array($campaign)) : $id = (string)($campaign['campagne_id'] ?? ''); ?>
                <section class="htp-1179-card htp-1179-advent-context">
                    <div class="htp-1179-card-head"><div><h2><?php echo esc_html((string)($campaign['nom_campagne'] ?? $id)); ?></h2><p>Campagne <?php echo esc_html((string)($campaign['annee'] ?? '')); ?> · <?php echo esc_html((string)($campaign['statut_campagne'] ?? '')); ?></p></div></div>
                    <div class="htp-1179-shortcodes"><code>[parc_calendrier_avent campagne="<?php echo esc_attr($id); ?>"]</code><code>[parc_reglement_avent campagne="<?php echo esc_attr($id); ?>"]</code></div>
                </section>
            <?php endif; ?>
            <?php if (class_exists('Parcs_HT_Advent_Admin')) Parcs_HT_Advent_Admin::render_workspace(); ?>
        </div>
        <?php
    }
}
