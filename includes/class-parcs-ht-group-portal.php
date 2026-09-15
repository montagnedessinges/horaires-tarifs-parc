<?php

if (!defined('ABSPATH')) { exit; }

/** Bloc groupes : horaires + tarifs, avec années indépendantes du grand public. */
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

    private static function date_label($date, $language) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$date, $m)) return (string)$date;
        if ($language === 'en') return $m[2] . '/' . $m[3] . '/' . $m[1];
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private static function weekdays($row, $language) {
        $days = array_map('strval', is_array($row['weekdays'] ?? null) ? $row['weekdays'] : array());
        $names = array(
            'fr'=>array('1'=>'lun.','2'=>'mar.','3'=>'mer.','4'=>'jeu.','5'=>'ven.','6'=>'sam.','7'=>'dim.'),
            'en'=>array('1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun'),
            'de'=>array('1'=>'Mo','2'=>'Di','3'=>'Mi','4'=>'Do','5'=>'Fr','6'=>'Sa','7'=>'So'),
        );
        $labels = array();
        foreach ($days as $day) if (isset($names[$language][$day])) $labels[] = $names[$language][$day];
        return implode(', ', $labels);
    }

    private static function slots($row) {
        $parts = array();
        if (!empty($row['open']) && !empty($row['close'])) $parts[] = (string)$row['open'] . '–' . (string)$row['close'];
        if (!empty($row['open2']) && !empty($row['close2'])) $parts[] = (string)$row['open2'] . '–' . (string)$row['close2'];
        return implode(' / ', $parts);
    }

    private static function schedule_year($year, $language) {
        $season = Parcs_HT_Display_Policy::raw_season($year);
        if (!$season) return '';
        $rows = array();
        foreach ((array)($season['regular_periods'] ?? array()) as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || empty($row['start']) || empty($row['end'])) continue;
            $hours = self::slots($row);
            if ($hours === '') continue;
            $rows[] = '<tr><td>' . esc_html(self::date_label($row['start'], $language) . ' → ' . self::date_label($row['end'], $language)) . '</td><td>' . esc_html(self::weekdays($row, $language)) . '</td><td><strong>' . esc_html($hours) . '</strong></td></tr>';
        }
        foreach ((array)($season['exceptions'] ?? array()) as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || empty($row['start']) || empty($row['end'])) continue;
            $label = (string)($row['type'] ?? 'hours') === 'closed'
                ? self::text($language, 'Fermé', 'Closed', 'Geschlossen')
                : self::slots($row);
            if ($label === '') continue;
            $rows[] = '<tr class="is-exception"><td>' . esc_html(self::date_label($row['start'], $language) . ' → ' . self::date_label($row['end'], $language)) . '</td><td>' . esc_html(self::text($language, 'Exception', 'Exception', 'Ausnahme')) . '</td><td><strong>' . esc_html($label) . '</strong></td></tr>';
        }
        $title = self::text($language, 'Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten') . ' ' . $year;
        $season_range = '';
        if (!empty($season['season_start']) && !empty($season['season_end'])) {
            $season_range = self::text($language, 'Saison : ', 'Season: ', 'Saison: ') . self::date_label($season['season_start'], $language) . ' → ' . self::date_label($season['season_end'], $language);
        }
        return '<section class="parcs-ht-group-schedule"><h3>' . esc_html($title) . '</h3>' . ($season_range !== '' ? '<p>' . esc_html($season_range) . '</p>' : '') . '<div class="parcs-ht-group-schedule-table-wrap"><table class="parcs-ht-group-schedule-table"><thead><tr><th>' . esc_html(self::text($language, 'Dates', 'Dates', 'Daten')) . '</th><th>' . esc_html(self::text($language, 'Jours', 'Days', 'Tage')) . '</th><th>' . esc_html(self::text($language, 'Horaires', 'Hours', 'Zeiten')) . '</th></tr></thead><tbody>' . implode('', $rows) . '</tbody></table></div></section>';
    }

    private static function year_tabs($years, $prefix, $active, $language) {
        if (count($years) < 2) return '';
        $html = '<div class="parcs-ht-group-portal-years" role="tablist" aria-label="' . esc_attr(self::text($language, 'Année', 'Year', 'Jahr')) . '">';
        foreach ($years as $year) {
            $html .= '<button type="button" role="tab" data-group-year-tab="' . esc_attr($prefix . '-' . $year) . '" class="parcs-ht-year-tab' . ($year === $active ? ' is-active' : '') . '" aria-selected="' . ($year === $active ? 'true' : 'false') . '">' . esc_html($year) . '</button>';
        }
        return $html . '</div>';
    }

    public static function render($language, $atts = array()) {
        unset($atts);
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $schedule_years = class_exists('Parcs_HT_Display_Policy') ? Parcs_HT_Display_Policy::group_schedule_years() : array();
        $tariff_years = class_exists('Parcs_HT_Group_Tariff_Settings') ? Parcs_HT_Group_Tariff_Settings::published_years() : array();
        self::$instance++;
        $id = 'parcs-ht-group-portal-' . self::$instance;
        $schedule_active = $schedule_years ? (string)end($schedule_years) : '';
        $tariff_active = $tariff_years ? (string)end($tariff_years) : '';

        ob_start(); ?>
        <div id="<?php echo esc_attr($id); ?>" class="parcs-ht-group-portal" data-group-portal>
            <div class="parcs-ht-group-portal-tabs" role="tablist">
                <button type="button" class="is-active" data-group-main-tab="hours" aria-selected="true"><?php echo esc_html(self::text($language, 'Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten')); ?></button>
                <button type="button" data-group-main-tab="tariffs" aria-selected="false"><?php echo esc_html(self::text($language, 'Tarifs groupes', 'Group rates', 'Gruppentarife')); ?></button>
            </div>
            <div data-group-main-panel="hours">
                <?php echo self::year_tabs($schedule_years, 'hours', $schedule_active, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé localement. ?>
                <?php if (!$schedule_years) : ?><p><?php echo esc_html(self::text($language, 'Aucun horaire groupe publié pour le moment.', 'No group opening hours are published yet.', 'Derzeit sind keine Gruppenöffnungszeiten veröffentlicht.')); ?></p><?php endif; ?>
                <?php foreach ($schedule_years as $year) : ?><div data-group-year-panel="<?php echo esc_attr('hours-' . $year); ?>" <?php if ($year !== $schedule_active) echo 'hidden'; ?>><?php echo self::schedule_year($year, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne échappé. ?></div><?php endforeach; ?>
            </div>
            <div data-group-main-panel="tariffs" hidden>
                <?php echo self::year_tabs($tariff_years, 'tariffs', $tariff_active, $language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé localement. ?>
                <?php if (!$tariff_years) : ?><p><?php echo esc_html(self::text($language, 'Aucun tarif groupe publié pour le moment.', 'No group rates are published yet.', 'Derzeit sind keine Gruppentarife veröffentlicht.')); ?></p><?php endif; ?>
                <?php foreach ($tariff_years as $year) :
                    Parcs_HT_Display_Policy::begin_group_tariff_year($year);
                    $tariff_html = Parcs_HT_Group_Tariffs::render($language, array());
                    Parcs_HT_Display_Policy::end_group_tariff_year();
                ?><div data-group-year-panel="<?php echo esc_attr('tariffs-' . $year); ?>" <?php if ($year !== $tariff_active) echo 'hidden'; ?>><?php echo $tariff_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendu interne du shortcode groupes. ?></div><?php endforeach; ?>
            </div>
        </div>
        <style>#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years button{border:1px solid #d7d7d7;background:transparent;color:inherit;border-radius:999px;padding:9px 16px;font-weight:600;cursor:pointer}#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-tabs button.is-active,#<?php echo esc_attr($id); ?> .parcs-ht-group-portal-years button.is-active{background:var(--htp-primary,#006757);border-color:var(--htp-primary,#006757);color:#fff}#<?php echo esc_attr($id); ?> .parcs-ht-group-schedule-table-wrap{overflow-x:auto}#<?php echo esc_attr($id); ?> .parcs-ht-group-schedule-table{width:100%;border-collapse:collapse}#<?php echo esc_attr($id); ?> .parcs-ht-group-schedule-table th,#<?php echo esc_attr($id); ?> .parcs-ht-group-schedule-table td{text-align:left;padding:10px;border-bottom:1px solid rgba(127,127,127,.22)}#<?php echo esc_attr($id); ?> .parcs-ht-group-schedule-table tr.is-exception{font-weight:600}</style>
        <script>(function(){var root=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!root)return;root.querySelectorAll('[data-group-main-tab]').forEach(function(btn){btn.addEventListener('click',function(){var key=btn.getAttribute('data-group-main-tab');root.querySelectorAll('[data-group-main-tab]').forEach(function(b){var on=b===btn;b.classList.toggle('is-active',on);b.setAttribute('aria-selected',on?'true':'false');});root.querySelectorAll('[data-group-main-panel]').forEach(function(p){p.hidden=p.getAttribute('data-group-main-panel')!==key;});});});root.querySelectorAll('[data-group-year-tab]').forEach(function(btn){btn.addEventListener('click',function(){var key=btn.getAttribute('data-group-year-tab'),prefix=key.split('-')[0];root.querySelectorAll('[data-group-year-tab^="'+prefix+'-"]').forEach(function(b){var on=b===btn;b.classList.toggle('is-active',on);b.setAttribute('aria-selected',on?'true':'false');});root.querySelectorAll('[data-group-year-panel^="'+prefix+'-"]').forEach(function(p){p.hidden=p.getAttribute('data-group-year-panel')!==key;});});});}());</script>
        <?php return ob_get_clean();
    }
}
