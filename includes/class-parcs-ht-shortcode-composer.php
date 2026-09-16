<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Assembleur des shortcodes publics composés.
 *
 * Chaque bloc conserve son moteur historique. Le shortcode complet assemble les
 * composants existants ; le portail groupes ne crée pas un second calendrier.
 */
final class Parcs_HT_Shortcode_Composer {
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_horaires_tarifs', array(__CLASS__, 'shortcode_page'));
        add_shortcode('parc_groupes_horaires_tarifs', array(__CLASS__, 'shortcode_groups'));

        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Shortcode_Composer::render_page($language, is_array($atts) ? $atts : array());
            });
            add_shortcode('parc_groupes_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Shortcode_Composer::render_groups($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode_page($atts = array()) {
        return self::render_page(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function shortcode_groups($atts = array()) {
        return self::render_groups(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function language($language) {
        return in_array($language, array('fr','en','de'), true) ? $language : 'fr';
    }

    private static function child($base, $language) {
        return do_shortcode('[' . $base . '_' . self::language($language) . ']');
    }

    private static function text($language, $fr, $en, $de) {
        return $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
    }

    private static function requested_group_year() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- sélection publique en lecture seule.
        $year = isset($_GET['htp_group_year']) ? sanitize_text_field(wp_unslash($_GET['htp_group_year'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function enabled_rows($rows) {
        return array_values(array_filter((array)$rows, static function ($row) {
            return is_array($row) && (string)($row['enabled'] ?? '0') === '1';
        }));
    }

    /** Données du calendrier historique, limitées aux années autorisées aux groupes. */
    private static function group_schedule_payload($years) {
        $out = array();
        foreach ((array)$years as $year) {
            $year = (string)$year;
            $season = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::raw_season($year) : array();
            if (!$season) continue;
            $out[$year] = array(
                'year'=>$year,
                'season_start'=>(string)($season['season_start'] ?? ''),
                'season_end'=>(string)($season['season_end'] ?? ''),
                'regularPeriods'=>self::enabled_rows($season['regular_periods'] ?? array()),
                'schoolHolidays'=>self::enabled_rows($season['school_holidays'] ?? array()),
                'specialPeriods'=>self::enabled_rows($season['special_periods'] ?? array()),
                'publicHolidays'=>self::enabled_rows($season['public_holidays'] ?? array()),
                'domainRules'=>self::enabled_rows($season['domain_rules'] ?? array()),
                'exceptions'=>self::enabled_rows($season['exceptions'] ?? array()),
            );
        }
        return $out;
    }

    /**
     * Shortcode public complet : statut du jour + calendrier historique + tarifs.
     */
    public static function render_page($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        self::$instance++;
        $id = 'parcs-ht-composed-page-' . self::$instance;

        $today = self::child('parc_horaires_aujourdhui', $language);
        $calendar = self::child('parc_calendrier', $language);
        $tariffs = self::child('parc_tableau_tarifs', $language);

        return '<div id="' . esc_attr($id) . '" class="parcs-ht-page parcs-ht-composed-page" data-htp-lang="' . esc_attr($language) . '">' .
            $today . $calendar . $tariffs .
            '</div>';
    }

    /**
     * Portail groupes : les tarifs groupes et le calendrier historique partagent
     * un seul sélecteur d'année. Les années horaires sont indépendantes du calendrier
     * visiteurs et proviennent de `groups_schedule_visible` + dates automatiques.
     */
    public static function render_groups($language, $atts = array()) {
        unset($atts);
        $language = self::language($language);
        self::$instance++;
        $id = 'parcs-ht-composed-groups-' . self::$instance;

        $tariff_years = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::group_tariff_years() : array();
        $schedule_years = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::group_schedule_years() : array();
        $years = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::group_portal_years()
            : array_values(array_unique(array_merge($tariff_years, $schedule_years)));

        $requested = self::requested_group_year();
        $selected = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::default_year($years, $requested)
            : ($years ? (string)$years[0] : '');

        $tariffs = self::child('parc_tarifs_groupes', $language);
        $calendar = self::child('parc_calendrier', $language);

        // Le calendrier reste exactement le moteur historique. On remplace seulement
        // son jeu de saisons sur cette page par les saisons autorisées aux groupes.
        $group_seasons = self::group_schedule_payload($schedule_years);
        if ($group_seasons && wp_script_is('parcs-ht-frontend', 'enqueued')) {
            $calendar_year = in_array($selected, $schedule_years, true) ? $selected : (string)$schedule_years[0];
            wp_add_inline_script(
                'parcs-ht-frontend',
                'window.ParcsHTPData=window.ParcsHTPData||{};window.ParcsHTPData.settings=window.ParcsHTPData.settings||{};window.ParcsHTPData.settings.seasons=' . wp_json_encode($group_seasons) . ';window.ParcsHTPData.settings.activeSeasonYear=' . wp_json_encode($calendar_year) . ';',
                'before'
            );
        }

        wp_enqueue_script('parcs-ht-group-portal-sync', PARCS_HT_URL . 'assets/group-portal-sync.js', array('parcs-ht-frontend'), PARCS_HT_VERSION, true);

        $tariff_available = $selected !== '' && in_array($selected, $tariff_years, true);
        $schedule_available = $selected !== '' && in_array($selected, $schedule_years, true);

        ob_start(); ?>
        <div id="<?php echo esc_attr($id); ?>"
             class="parcs-ht-group-portal parcs-ht-composed-groups"
             data-group-portal
             data-group-selected-year="<?php echo esc_attr($selected); ?>"
             data-group-tariff-years="<?php echo esc_attr(implode(',', $tariff_years)); ?>"
             data-group-schedule-years="<?php echo esc_attr(implode(',', $schedule_years)); ?>">
            <div class="parcs-ht-group-portal-tabs" role="tablist">
                <button type="button" class="is-active" data-group-main-tab="tariffs" aria-selected="true"><?php echo esc_html(self::text($language, 'Tarifs groupes', 'Group rates', 'Gruppentarife')); ?></button>
                <button type="button" data-group-main-tab="hours" aria-selected="false"><?php echo esc_html(self::text($language, 'Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten')); ?></button>
            </div>

            <?php if (count($years) > 1) : ?>
                <div class="parcs-ht-group-portal-years" role="tablist" aria-label="<?php echo esc_attr(self::text($language, 'Année', 'Year', 'Jahr')); ?>">
                    <?php foreach ($years as $year) : $active = (string)$year === (string)$selected; ?>
                        <button type="button" class="parcs-ht-group-portal-year<?php echo $active ? ' is-active' : ''; ?>" data-group-year="<?php echo esc_attr($year); ?>" aria-selected="<?php echo $active ? 'true' : 'false'; ?>"><?php echo esc_html($year); ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div data-group-main-panel="tariffs">
                <div data-group-tariff-content<?php echo $tariff_available ? '' : ' hidden'; ?>><?php echo $tariffs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu du shortcode tarifs existant. ?></div>
                <p class="parcs-ht-group-empty" data-group-tariff-empty<?php echo $tariff_available ? ' hidden' : ''; ?>><?php echo esc_html(self::text($language, 'Les tarifs groupes ne sont pas disponibles pour cette année.', 'Group rates are not available for this year.', 'Gruppentarife sind für dieses Jahr nicht verfügbar.')); ?></p>
            </div>
            <div data-group-main-panel="hours" hidden>
                <div data-group-calendar-content<?php echo $schedule_available ? '' : ' hidden'; ?>><?php echo $calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- calendrier historique existant. ?></div>
                <p class="parcs-ht-group-empty" data-group-calendar-empty<?php echo $schedule_available ? ' hidden' : ''; ?>><?php echo esc_html(self::text($language, 'Les horaires d’ouverture ne sont pas disponibles pour cette année.', 'Opening hours are not available for this year.', 'Öffnungszeiten sind für dieses Jahr nicht verfügbar.')); ?></p>
            </div>
        </div>
        <style>
        #<?php echo esc_attr($id); ?>{background:transparent}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;margin:0 0 16px;padding:2px 0 5px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-year{flex:0 0 auto;min-height:44px;border:1px solid var(--htp-primary,#006757);background:transparent;color:var(--htp-primary,#006757);border-radius:999px;padding:9px 16px;font:inherit;font-size:15px;font-weight:800;line-height:1.15;white-space:nowrap;cursor:pointer}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button.is-active,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-year.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-tariff-years-ui>.parcs-ht-tariff-year-tabs-ui,#<?php echo esc_attr($id); ?> .parcs-ht-calendar .parcs-ht-year-list{display:none!important}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-empty{margin:18px 0;font-size:1rem}
        </style>
        <?php return ob_get_clean();
    }
}
