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

    private static function text($language, $key, $fr, $en, $de) {
        $fallback = $language === 'en' ? $en : ($language === 'de' ? $de : $fr);
        return class_exists('Parcs_HT_Public_Content')
            ? Parcs_HT_Public_Content::text($key, $language, $fallback)
            : $fallback;
    }

    private static function requested_year() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- sélection publique en lecture seule.
        $year = isset($_GET['htp_group_year']) ? sanitize_text_field(wp_unslash($_GET['htp_group_year'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return preg_match('/^20\d{2}$/', $year) ? $year : '';
    }

    private static function public_row($row, $fields) {
        if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') return array();
        $out = array('enabled'=>'1');
        foreach ((array)$fields as $field) {
            if (array_key_exists($field, $row)) $out[$field] = $row[$field];
        }
        return $out;
    }

    private static function public_rows($rows, $fields) {
        $out = array();
        foreach ((array)$rows as $row) {
            $item = self::public_row($row, $fields);
            if ($item) $out[] = $item;
        }
        return $out;
    }

    /**
     * Projection publique minimale pour le moteur du calendrier groupes.
     * Les libellés internes et champs d'administration ne quittent jamais PHP.
     */
    private static function schedule_payload($years) {
        $payload = array();
        foreach ((array)$years as $year) {
            $year = (string)$year;
            $season = class_exists('Parcs_HT_Public_Visibility') ? Parcs_HT_Public_Visibility::raw_season($year) : Parcs_HT_Display_Policy::raw_season($year);
            if (!$season) continue;

            $special = array();
            foreach ((array)($season['special_periods'] ?? array()) as $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
                $fields = array('start','end','kind','show_on_calendar','skip_domain_rules');
                if ((string)($row['show_on_calendar'] ?? '1') !== '0') {
                    $fields = array_merge($fields, array('title','color','icon','show_button','button_label','button_url','message'));
                }
                $item = self::public_row($row, $fields);
                if ($item) $special[] = $item;
            }

            $exceptions = array();
            foreach ((array)($season['exceptions'] ?? array()) as $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
                $fields = array('type','start','end','priority','open','close','open2','close2','last_entry_minutes','apply_domain_rules','show_public_marker');
                if ((string)($row['show_public_marker'] ?? '1') !== '0') {
                    $fields = array_merge($fields, array('context','title','message'));
                }
                $item = self::public_row($row, $fields);
                if ($item) $exceptions[] = $item;
            }

            $payload[$year] = array(
                'year'=>$year,
                'season_start'=>(string)($season['season_start'] ?? ''),
                'season_end'=>(string)($season['season_end'] ?? ''),
                'regularPeriods'=>self::public_rows($season['regular_periods'] ?? array(), array('start','end','weekdays','open','close','open2','close2','last_entry_minutes','color')),
                'schoolHolidays'=>self::public_rows($season['school_holidays'] ?? array(), array('start','end')),
                'specialPeriods'=>$special,
                'publicHolidays'=>self::public_rows($season['public_holidays'] ?? array(), array('date')),
                'domainRules'=>self::public_rows($season['domain_rules'] ?? array(), array(
                    'start','end','weekdays','exclude_weekends','exclude_school_holidays','exclude_public_holidays',
                    'show_tooltip','tooltip_text','color','public_title','auto_details','pause_start','resume','last_entry',
                    'access_message','details_message','info'
                )),
                'exceptions'=>$exceptions,
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
        if (class_exists('Parcs_HT_Tariff_Display')) return Parcs_HT_Tariff_Display::render_group($language, array(), $year);
        Parcs_HT_Display_Policy::begin_group_tariff_year($year);
        $html = Parcs_HT_Group_Tariffs::render($language, array());
        Parcs_HT_Display_Policy::end_group_tariff_year();
        return $html;
    }

    private static function calendar($language, $years) {
        if (!$years) return '';
        $tag = 'parc_calendrier_' . $language;
        $filter = static function ($value) use ($years) {
            unset($value);
            return array_values(array_map('strval', $years));
        };
        add_filter('parcs_ht_calendar_semantic_years', $filter, 10, 1);
        $html = do_shortcode('[' . $tag . ']');
        remove_filter('parcs_ht_calendar_semantic_years', $filter, 10);
        self::inject_group_schedule_payload($years);
        return $html;
    }

    private static function year_url($year) {
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL nettoyée avant sortie.
        $base = remove_query_arg('htp_group_year', $uri ?: '/');
        return add_query_arg('htp_group_year', rawurlencode((string)$year), $base);
    }

    private static function year_tabs($years, $active, $language) {
        if (count($years) < 2) return '';
        $label = self::text($language, 'groups.portal.year_label', 'Année', 'Year', 'Jahr');
        $html = '<div class="parcs-ht-group-portal-years" role="tablist" aria-label="' . esc_attr($label) . '">';
        foreach ($years as $year) {
            $on = (string)$year === (string)$active;
            $html .= '<a href="' . esc_url(self::year_url($year)) . '" role="tab" data-group-year="' . esc_attr($year) . '" class="parcs-ht-year-tab' . ($on ? ' is-active' : '') . '" aria-selected="' . ($on ? 'true' : 'false') . '"' . ($on ? ' aria-current="page"' : '') . '>' . esc_html($year) . '</a>';
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
        $requested = self::requested_year();
        $active_year = class_exists('Parcs_HT_Public_Visibility')
            ? Parcs_HT_Public_Visibility::default_year($years, $requested)
            : (($requested !== '' && in_array($requested, $years, true)) ? $requested : ($years ? (string)$years[0] : ''));

        $has_tariffs = !empty($tariff_years);
        $has_hours = !empty($schedule_years);
        $default_tab = $has_tariffs ? 'tariffs' : 'hours';
        $show_main_tabs = $has_tariffs && $has_hours;

        self::$instance++;
        $id = 'parcs-ht-group-portal-' . self::$instance;
        $calendar = $has_hours ? self::calendar($language, $schedule_years) : '';
        $schedule_map = array_fill_keys(array_map('strval', $schedule_years), true);
        $active_has_hours = isset($schedule_map[(string)$active_year]);
        $hours_missing_possible = (bool)array_diff(array_map('strval', $years), array_map('strval', $schedule_years));

        $tariffs_tab = self::text($language, 'groups.portal.tariffs_tab', 'Tarifs groupes', 'Group rates', 'Gruppentarife');
        $hours_tab = self::text($language, 'groups.portal.hours_tab', 'Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten');
        $hours_unavailable = self::text($language, 'groups.portal.hours_unavailable', 'Les horaires d’ouverture ne sont pas disponibles pour cette année.', 'Opening hours are not available for this year.', 'Für dieses Jahr sind keine Öffnungszeiten verfügbar.');
        $empty = self::text($language, 'groups.portal.empty', 'Aucun tarif groupe ni horaire disponible pour le moment.', 'No group rates or opening hours are available at the moment.', 'Derzeit sind keine Gruppentarife oder Öffnungszeiten verfügbar.');

        ob_start(); ?>
        <div id="<?php echo esc_attr($id); ?>" class="parcs-ht-group-portal" data-group-portal data-group-active-year="<?php echo esc_attr($active_year); ?>" data-group-hours-unavailable-text="<?php echo esc_attr($hours_unavailable); ?>">
            <?php if ($show_main_tabs) : ?>
                <div class="parcs-ht-group-portal-tabs" role="tablist">
                    <button type="button" class="is-active" data-group-main-tab="tariffs" aria-selected="true"><?php echo esc_html($tariffs_tab); ?></button>
                    <button type="button" data-group-main-tab="hours" aria-selected="false"><?php echo esc_html($hours_tab); ?></button>
                </div>
            <?php endif; ?>

            <?php echo self::year_tabs($years, $active_year, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé localement. ?>

            <?php if ($has_tariffs) : ?>
                <div data-group-main-panel="tariffs" <?php if ($default_tab !== 'tariffs') echo 'hidden'; ?>>
                    <?php foreach ($tariff_years as $year) : ?>
                        <div data-group-tariff-year="<?php echo esc_attr($year); ?>" <?php if ((string)$year !== (string)$active_year) echo 'hidden'; ?>><?php echo self::group_tariffs($year, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu interne échappé. ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_hours) : ?>
                <div data-group-main-panel="hours" <?php if ($default_tab !== 'hours') echo 'hidden'; ?>>
                    <div data-group-calendar-wrap<?php if (!$active_has_hours) echo ' hidden'; ?>><?php echo $calendar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- calendrier interne déjà échappé. ?></div>
                    <?php if ($hours_missing_possible) : ?>
                        <p class="parcs-ht-group-year-unavailable" data-group-hours-unavailable<?php if ($active_has_hours) echo ' hidden'; ?>><?php if (!$active_has_hours) echo esc_html($hours_unavailable); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$has_tariffs && !$has_hours) : ?>
                <p><?php echo esc_html($empty); ?></p>
            <?php endif; ?>
        </div>
        <style>
        #<?php echo esc_attr($id); ?>{--htp-primary:#006757;--htp-highlight:#e7c55b;background:transparent}
        #<?php echo esc_attr($id); ?> [hidden]{display:none!important}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs,
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;margin:0 0 14px;padding:2px 0 5px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;scrollbar-width:thin}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button,
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years .parcs-ht-year-tab{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;min-height:44px;border:1px solid var(--htp-primary,#006757);background:transparent;color:var(--htp-primary,#006757);border-radius:999px;padding:9px 16px;font:inherit;font-size:15px;font-weight:800;line-height:1.15;white-space:nowrap;cursor:pointer;text-decoration:none}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button.is-active,
        #<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years .parcs-ht-year-tab.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}
        #<?php echo esc_attr($id); ?> [data-group-calendar-wrap] .parcs-ht-year-list{display:none!important}
        #<?php echo esc_attr($id); ?> .parcs-ht-group-year-unavailable{margin:12px 0;font-size:15px;color:#4b5a55}
        @media(max-width:600px){#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years .parcs-ht-year-tab{min-height:44px;padding:9px 13px;font-size:14px}}
        </style>
        <script>
        (function(){
            var root=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!root)return;
            var scheduleYears=<?php echo wp_json_encode($schedule_map); ?>;
            var active=<?php echo wp_json_encode($active_year); ?>;
            function syncCalendar(year){
                var wrap=root.querySelector('[data-group-calendar-wrap]'),missing=root.querySelector('[data-group-hours-unavailable]');
                if(!wrap)return;
                var available=!!scheduleYears[year];wrap.hidden=!available;
                if(missing){missing.hidden=available;missing.textContent=available?'':(root.getAttribute('data-group-hours-unavailable-text')||'');}
                if(!available)return;
                var button=wrap.querySelector('[data-htp-year="'+year+'"]');if(button)button.click();
            }
            function selectYear(year){
                active=String(year||'');root.setAttribute('data-group-active-year',active);
                root.querySelectorAll('[data-group-year]').forEach(function(btn){var on=btn.getAttribute('data-group-year')===active;btn.classList.toggle('is-active',on);btn.setAttribute('aria-selected',on?'true':'false');if(on)btn.setAttribute('aria-current','page');else btn.removeAttribute('aria-current');});
                root.querySelectorAll('[data-group-tariff-year]').forEach(function(panel){panel.hidden=panel.getAttribute('data-group-tariff-year')!==active;});
                setTimeout(function(){syncCalendar(active);},0);
            }
            root.querySelectorAll('[data-group-main-tab]').forEach(function(btn){btn.addEventListener('click',function(){var key=btn.getAttribute('data-group-main-tab');root.querySelectorAll('[data-group-main-tab]').forEach(function(b){var on=b===btn;b.classList.toggle('is-active',on);b.setAttribute('aria-selected',on?'true':'false');});root.querySelectorAll('[data-group-main-panel]').forEach(function(p){p.hidden=p.getAttribute('data-group-main-panel')!==key;});if(key==='hours')setTimeout(function(){syncCalendar(active);},0);});});
            root.querySelectorAll('[data-group-year]').forEach(function(btn){btn.addEventListener('click',function(event){if(event)event.preventDefault();selectYear(btn.getAttribute('data-group-year'));});});
            function afterBoot(){setTimeout(function(){selectYear(active);},0);}
            if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',afterBoot);else afterBoot();
        }());
        </script>
        <?php return ob_get_clean();
    }
}
