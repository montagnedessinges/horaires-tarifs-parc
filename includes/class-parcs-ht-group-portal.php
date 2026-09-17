<?php

if (!defined('ABSPATH')) { exit; }

/** Bloc groupes : tarifs + vrai calendrier public, synchronisés par année. */
final class Parcs_HT_Group_Portal {
    private static $instance = 0;

    public static function init() {
        add_shortcode('parc_groupes_horaires_tarifs', array(__CLASS__, 'shortcode'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_groupes_horaires_tarifs_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Group_Portal::render($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function shortcode($atts = array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function text($language, $fr, $en, $de) {
        return $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
    }

    private static function enabled_rows($rows) {
        $out = array();
        foreach ((array)$rows as $row) if (is_array($row) && (string)($row['enabled'] ?? '0') === '1') $out[] = $row;
        return $out;
    }

    private static function schedule_payload($years) {
        $payload = array();
        foreach ((array)$years as $year) {
            $season = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::raw_season($year) : Parcs_HT_Display_Policy::raw_season($year);
            if (!$season) continue;
            $payload[(string)$year] = array(
                'year'=>(string)$year,
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
        return $payload;
    }

    private static function inject_group_schedule_payload($years) {
        if (!$years || !wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        $payload = self::schedule_payload($years);
        if (!$payload) return;
        $script = '(function(){window.ParcsHTPData=window.ParcsHTPData||{};window.ParcsHTPData.settings=window.ParcsHTPData.settings||{};window.ParcsHTPData.settings.seasons=window.ParcsHTPData.settings.seasons||{};Object.assign(window.ParcsHTPData.settings.seasons,' . wp_json_encode($payload) . ');}());';
        wp_add_inline_script('parcs-ht-frontend', $script, 'before');
    }

    private static function group_tariffs($year, $language) {
        if (class_exists('Parcs_HT_Tariff_Public_Fixes')) return Parcs_HT_Tariff_Public_Fixes::render_group_year($language, $year);
        if (class_exists('Parcs_HT_Tariff_Display')) return Parcs_HT_Tariff_Display::render_group($language, array(), $year);
        Parcs_HT_Display_Policy::begin_group_tariff_year($year);
        $html = Parcs_HT_Group_Tariffs::render($language, array());
        Parcs_HT_Display_Policy::end_group_tariff_year();
        return $html;
    }

    private static function calendar($language, $years) {
        if (!$years || !class_exists('Parcs_HT_Shortcodes')) return '';
        $html = Parcs_HT_Shortcodes::render('calendar', $language, array());
        self::inject_group_schedule_payload($years);
        return $html;
    }

    private static function year_tabs($years, $active, $language) {
        if (count($years) < 2) return '';
        $html = '<div class="parcs-ht-group-portal-years" role="tablist" aria-label="' . esc_attr(self::text($language, 'Année', 'Year', 'Jahr')) . '">';
        foreach ($years as $year) {
            $on = (string)$year === (string)$active;
            $html .= '<button type="button" role="tab" data-group-year="' . esc_attr($year) . '" class="parcs-ht-year-tab' . ($on ? ' is-active' : '') . '" aria-selected="' . ($on ? 'true' : 'false') . '">' . esc_html($year) . '</button>';
        }
        return $html . '</div>';
    }

    public static function render($language, $atts = array()) {
        unset($atts);
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';

        $schedule_years = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::group_schedule_years()
            : (class_exists('Parcs_HT_Display_Policy') ? Parcs_HT_Display_Policy::group_schedule_years() : array());
        $tariff_years = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::group_tariff_years()
            : (class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::public_years() : array());
        $years = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::order_years(array_merge($tariff_years, $schedule_years))
            : array_values(array_unique(array_merge($tariff_years, $schedule_years)));
        if (!class_exists('Parcs_HT_Public_Visibility')) sort($years, SORT_NUMERIC);
        $active_year = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::default_year($years) : ($years ? (string)$years[0] : '');

        $has_tariffs = !empty($tariff_years);
        $has_hours = !empty($schedule_years);
        $default_tab = $has_tariffs ? 'tariffs' : 'hours';
        $show_main_tabs = $has_tariffs && $has_hours;

        self::$instance++;
        $id = 'parcs-ht-group-portal-' . self::$instance;
        $calendar = $has_hours ? self::calendar($language, $schedule_years) : '';
        $schedule_map = array_fill_keys(array_map('strval', $schedule_years), true);
        $tariff_map = array_fill_keys(array_map('strval', $tariff_years), true);

        ob_start(); ?>
        <div id="<?php echo esc_attr($id); ?>" class="parcs-ht-group-portal" data-group-portal data-group-active-year="<?php echo esc_attr($active_year); ?>">
            <?php if ($show_main_tabs) : ?>
                <div class="parcs-ht-group-portal-tabs" role="tablist">
                    <button type="button" class="is-active" data-group-main-tab="tariffs" aria-selected="true"><?php echo esc_html(self::text($language, 'Tarifs groupes', 'Group rates', 'Gruppentarife')); ?></button>
                    <button type="button" data-group-main-tab="hours" aria-selected="false"><?php echo esc_html(self::text($language, 'Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten')); ?></button>
                </div>
            <?php endif; ?>

            <?php echo self::year_tabs($years, $active_year, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé localement. ?>

            <?php if ($has_tariffs) : ?>
                <div data-group-main-panel="tariffs" <?php if ($default_tab !== 'tariffs') echo 'hidden'; ?>>
                    <?php foreach ($tariff_years as $year) : ?>
                        <div data-group-tariff-year="<?php echo esc_attr($year); ?>" <?php if ($year !== $active_year) echo 'hidden'; ?>><?php echo self::group_tariffs($year, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu interne échappé. ?></div>
                    <?php endforeach; ?>
                    <p class="parcs-ht-group-year-unavailable" data-group-tariff-unavailable hidden><?php echo esc_html(self::text($language, 'Les tarifs groupes ne sont pas disponibles pour cette année.', 'Group rates are not available for this year.', 'Für dieses Jahr sind keine Gruppentarife verfügbar.')); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($has_hours) : ?>
                <div data-group-main-panel="hours" <?php if ($default_tab !== 'hours') echo 'hidden'; ?>>
                    <div data-group-calendar-wrap><?php echo $calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- calendrier interne déjà échappé. ?></div>
                    <p class="parcs-ht-group-year-unavailable" data-group-hours-unavailable hidden><?php echo esc_html(self::text($language, 'Les horaires d’ouverture ne sont pas disponibles pour cette année.', 'Opening hours are not available for this year.', 'Für dieses Jahr sind keine Öffnungszeiten verfügbar.')); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$has_tariffs && !$has_hours) : ?>
                <p><?php echo esc_html(self::text($language, 'Aucun tarif groupe ni horaire disponible pour le moment.', 'No group rates or opening hours are available at the moment.', 'Derzeit sind keine Gruppentarife oder Öffnungszeiten verfügbar.')); ?></p>
            <?php endif; ?>
        </div>
        <style>
        #<?php echo esc_attr($id); ?>{--htp-primary:#006757;--htp-highlight:#e7c55b;background:transparent}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs,
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;margin:0 0 14px;padding:2px 0 5px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;scrollbar-width:thin}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button,
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years button{flex:0 0 auto;min-height:44px;border:1px solid var(--htp-primary,#006757);background:transparent;color:var(--htp-primary,#006757);border-radius:999px;padding:9px 16px;font:inherit;font-size:15px;font-weight:800;line-height:1.15;white-space:nowrap;cursor:pointer}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button.is-active,
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years button.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}
        #<?php echo esc_attr($id); ?> [data-group-calendar-wrap] .parcs-ht-year-list{display:none!important}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-year-unavailable{margin:12px 0;font-size:15px;color:#4b5a55}
        @media(max-width:600px){#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years button{min-height:44px;padding:9px 13px;font-size:14px}}
        </style>
        <script>
        (function(){
            var root=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!root)return;
            var scheduleYears=<?php echo wp_json_encode($schedule_map); ?>;
            var tariffYears=<?php echo wp_json_encode($tariff_map); ?>;
            var active=<?php echo wp_json_encode($active_year); ?>;
            function syncCalendar(year){
                var wrap=root.querySelector('[data-group-calendar-wrap]'),missing=root.querySelector('[data-group-hours-unavailable]');
                if(!wrap)return;
                var available=!!scheduleYears[year];wrap.hidden=!available;if(missing)missing.hidden=available;
                if(!available)return;
                var button=wrap.querySelector('[data-htp-year="'+year+'"]');if(button)button.click();
            }
            function selectYear(year){
                active=String(year||'');root.setAttribute('data-group-active-year',active);
                root.querySelectorAll('[data-group-year]').forEach(function(btn){var on=btn.getAttribute('data-group-year')===active;btn.classList.toggle('is-active',on);btn.setAttribute('aria-selected',on?'true':'false');});
                var foundTariff=false;root.querySelectorAll('[data-group-tariff-year]').forEach(function(panel){var on=panel.getAttribute('data-group-tariff-year')===active;panel.hidden=!on;if(on)foundTariff=true;});
                var tariffMissing=root.querySelector('[data-group-tariff-unavailable]');if(tariffMissing)tariffMissing.hidden=foundTariff||!!tariffYears[active];
                setTimeout(function(){syncCalendar(active);},0);
            }
            root.querySelectorAll('[data-group-main-tab]').forEach(function(btn){btn.addEventListener('click',function(){var key=btn.getAttribute('data-group-main-tab');root.querySelectorAll('[data-group-main-tab]').forEach(function(b){var on=b===btn;b.classList.toggle('is-active',on);b.setAttribute('aria-selected',on?'true':'false');});root.querySelectorAll('[data-group-main-panel]').forEach(function(p){p.hidden=p.getAttribute('data-group-main-panel')!==key;});if(key==='hours')setTimeout(function(){syncCalendar(active);},0);});});
            root.querySelectorAll('[data-group-year]').forEach(function(btn){btn.addEventListener('click',function(){selectYear(btn.getAttribute('data-group-year'));});});
            function afterBoot(){setTimeout(function(){selectYear(active);},0);}
            if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',afterBoot);else afterBoot();
        }());
        </script>
        <?php return ob_get_clean();
    }
}
