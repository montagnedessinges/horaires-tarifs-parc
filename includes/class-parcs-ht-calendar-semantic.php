<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendu sémantique serveur du calendrier public.
 *
 * Cette couche ne possède aucun stockage. Elle lit uniquement les saisons canoniques
 * de Gestion du parc afin que les informations importantes restent compréhensibles
 * dans le HTML initial, même sans exécution JavaScript. Le même rendu pourra servir
 * de base aux améliorations d'accessibilité ultérieures.
 */
final class Parcs_HT_Calendar_Semantic {
    public static function init() {
        add_filter('do_shortcode_tag', array(__CLASS__, 'append_to_calendar_shortcodes'), 85, 4);
    }

    private static function language_from_tag($tag) {
        foreach (array('fr', 'en', 'de') as $language) {
            if (substr((string)$tag, -3) === '_' . $language) return $language;
        }
        return class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::language() : 'fr';
    }

    private static function labels($language) {
        $labels = array(
            'fr' => array(
                'summary' => 'Détails des horaires et du calendrier',
                'season' => 'Calendrier %s',
                'season_range' => 'Saison du %s au %s',
                'regular' => 'Horaires habituels',
                'exceptions' => 'Horaires exceptionnels',
                'closures' => 'Fermetures exceptionnelles',
                'events' => 'Événements',
                'periods' => 'Périodes repères',
                'holidays' => 'Jours fériés',
                'event' => 'Événement',
                'period' => 'Période',
                'holiday' => 'Jour férié',
                'closed' => 'Fermé',
                'from_to' => 'Du %s au %s',
                'on' => 'Le %s',
            ),
            'en' => array(
                'summary' => 'Detailed opening hours and calendar',
                'season' => '%s calendar',
                'season_range' => 'Season from %s to %s',
                'regular' => 'Regular opening hours',
                'exceptions' => 'Exceptional opening hours',
                'closures' => 'Exceptional closures',
                'events' => 'Events',
                'periods' => 'Reference periods',
                'holidays' => 'Public holidays',
                'event' => 'Event',
                'period' => 'Period',
                'holiday' => 'Public holiday',
                'closed' => 'Closed',
                'from_to' => 'From %s to %s',
                'on' => 'On %s',
            ),
            'de' => array(
                'summary' => 'Detaillierte Öffnungszeiten und Kalender',
                'season' => 'Kalender %s',
                'season_range' => 'Saison vom %s bis %s',
                'regular' => 'Reguläre Öffnungszeiten',
                'exceptions' => 'Außergewöhnliche Öffnungszeiten',
                'closures' => 'Außergewöhnliche Schließungen',
                'events' => 'Veranstaltungen',
                'periods' => 'Hinweiszeiträume',
                'holidays' => 'Feiertage',
                'event' => 'Veranstaltung',
                'period' => 'Zeitraum',
                'holiday' => 'Feiertag',
                'closed' => 'Geschlossen',
                'from_to' => 'Vom %s bis %s',
                'on' => 'Am %s',
            ),
        );
        return $labels[$language] ?? $labels['fr'];
    }

    private static function weekday_labels($language) {
        $rows = array(
            'fr' => array('1'=>'lundi','2'=>'mardi','3'=>'mercredi','4'=>'jeudi','5'=>'vendredi','6'=>'samedi','7'=>'dimanche'),
            'en' => array('1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday'),
            'de' => array('1'=>'Montag','2'=>'Dienstag','3'=>'Mittwoch','4'=>'Donnerstag','5'=>'Freitag','6'=>'Samstag','7'=>'Sonntag'),
        );
        return $rows[$language] ?? $rows['fr'];
    }

    private static function enabled($row) {
        return is_array($row) && (string)($row['enabled'] ?? '0') === '1';
    }

    private static function valid_date($value) {
        $value = (string)$value;
        if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value)) return false;
        $parts = array_map('intval', explode('-', $value));
        return count($parts) === 3 && checkdate($parts[1], $parts[2], $parts[0]);
    }

    private static function valid_time($value) {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string)$value) === 1;
    }

    private static function exact_translation($value, $language) {
        if (is_array($value)) return trim((string)($value[$language] ?? ''));
        return is_string($value) ? trim($value) : '';
    }

    private static function date_label($date, $language) {
        if (!self::valid_date($date)) return (string)$date;
        try {
            $value = new DateTimeImmutable((string)$date);
        } catch (Exception $e) {
            return (string)$date;
        }
        if ($language === 'de') return $value->format('d.m.Y');
        return $value->format('d/m/Y');
    }

    private static function date_html($date, $language) {
        if (!self::valid_date($date)) return esc_html((string)$date);
        return '<time datetime="' . esc_attr((string)$date) . '">' . esc_html(self::date_label($date, $language)) . '</time>';
    }

    private static function range_html($start, $end, $language, $labels) {
        if (!self::valid_date($start)) return '';
        if (!self::valid_date($end) || $end === $start) {
            return sprintf($labels['on'], self::date_html($start, $language));
        }
        return sprintf($labels['from_to'], self::date_html($start, $language), self::date_html($end, $language));
    }

    private static function slots_label($row) {
        $slots = array();
        if (self::valid_time($row['open'] ?? '') && self::valid_time($row['close'] ?? '')) {
            $slots[] = (string)$row['open'] . '–' . (string)$row['close'];
        }
        if (self::valid_time($row['open2'] ?? '') && self::valid_time($row['close2'] ?? '')) {
            $slots[] = (string)$row['open2'] . '–' . (string)$row['close2'];
        }
        return implode(' / ', $slots);
    }

    private static function weekdays_label($days, $language) {
        $dictionary = self::weekday_labels($language);
        $out = array();
        foreach ((array)$days as $day) {
            $key = (string)$day;
            if (isset($dictionary[$key])) $out[] = $dictionary[$key];
        }
        return implode(', ', array_values(array_unique($out)));
    }

    private static function public_season($year, $season) {
        if (!is_array($season)) return false;
        if (class_exists('Parcs_HT_Public_Visibility')) {
            $state = Parcs_HT_Public_Visibility::scheduled_state((string)$year);
            if ($state === 'on') return true;
            if ($state === 'off') return false;
        }
        if (array_key_exists('calendar_visible', $season)) return (string)$season['calendar_visible'] === '1';
        return (string)($season['published'] ?? '0') === '1';
    }

    private static function regular_items($season, $language, $labels) {
        $items = array();
        foreach ((array)($season['regular_periods'] ?? array()) as $row) {
            if (!self::enabled($row)) continue;
            $slots = self::slots_label($row);
            $weekdays = self::weekdays_label($row['weekdays'] ?? array(), $language);
            if ($slots === '' || $weekdays === '') continue;
            $start = self::valid_date($row['start'] ?? '') ? (string)$row['start'] : (string)($season['season_start'] ?? '');
            $end = self::valid_date($row['end'] ?? '') ? (string)$row['end'] : (string)($season['season_end'] ?? '');
            $range = self::range_html($start, $end, $language, $labels);
            if ($range === '') continue;
            $items[] = $range . ' — ' . esc_html($weekdays) . ' — ' . esc_html($slots);
        }
        return $items;
    }

    private static function exception_items($season, $language, $labels, $closed) {
        $items = array();
        foreach ((array)($season['exceptions'] ?? array()) as $row) {
            if (!self::enabled($row)) continue;
            $is_closed = (string)($row['type'] ?? 'hours') === 'closed';
            if ($is_closed !== $closed) continue;
            $start = (string)($row['start'] ?? '');
            $end = (string)($row['end'] ?? $start);
            $range = self::range_html($start, $end, $language, $labels);
            if ($range === '') continue;
            $context = '';
            if ((string)($row['show_public_marker'] ?? '1') !== '0') {
                $context = self::exact_translation($row['context'] ?? array(), $language);
            }
            if ($is_closed) {
                $line = $range . ' — ' . esc_html($labels['closed']);
            } else {
                $slots = self::slots_label($row);
                if ($slots === '') continue;
                $line = $range . ' — ' . esc_html($slots);
            }
            if ($context !== '') $line .= ' — ' . esc_html($context);
            $items[] = $line;
        }
        return $items;
    }

    private static function period_items($season, $language, $labels, $events) {
        $items = array();
        foreach ((array)($season['special_periods'] ?? array()) as $row) {
            if (!self::enabled($row) || (string)($row['show_on_calendar'] ?? '1') === '0') continue;
            $is_event = (string)($row['kind'] ?? '') === 'event';
            if ($is_event !== $events) continue;
            $start = (string)($row['start'] ?? '');
            $end = (string)($row['end'] ?? $start);
            $range = self::range_html($start, $end, $language, $labels);
            if ($range === '') continue;
            $title = self::exact_translation($row['title'] ?? array(), $language);
            $type = $is_event ? $labels['event'] : $labels['period'];
            $line = '<strong>' . esc_html($title !== '' ? $title : $type) . '</strong> — ' . $range;
            $items[] = $line;
        }
        return $items;
    }

    private static function holiday_items($season, $general, $language, $labels) {
        if ((string)($general['show_public_holidays'] ?? '0') !== '1') return array();
        $items = array();
        foreach ((array)($season['public_holidays'] ?? array()) as $row) {
            if (!self::enabled($row) || !self::valid_date($row['date'] ?? '')) continue;
            $title = self::exact_translation($row['title'] ?? array(), $language);
            if ($title === '') $title = self::exact_translation($row['name'] ?? array(), $language);
            if ($title === '') $title = self::exact_translation($row['label'] ?? array(), $language);
            if ($title === '') $title = $labels['holiday'];
            $items[] = '<strong>' . esc_html($title) . '</strong> — ' . self::date_html((string)$row['date'], $language);
        }
        return $items;
    }

    private static function list_html($title, $items, $class_name) {
        if (!$items) return '';
        $html = '<section class="parcs-ht-calendar-semantic-group ' . esc_attr($class_name) . '"><h4>' . esc_html($title) . '</h4><ul>';
        foreach ($items as $item) {
            $html .= '<li>' . wp_kses($item, array('strong'=>array(), 'time'=>array('datetime'=>true))) . '</li>';
        }
        return $html . '</ul></section>';
    }

    public static function render($language = '') {
        if (!class_exists('Parcs_HT_Defaults')) return '';
        $language = in_array($language, array('fr', 'en', 'de'), true) ? $language : (class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::language() : 'fr');
        $labels = self::labels($language);
        $all = Parcs_HT_Defaults::all_settings();
        $seasons = is_array($all['seasons'] ?? null) ? $all['seasons'] : array();
        if (!$seasons) return '';
        ksort($seasons, SORT_STRING);
        $general = is_array($all['general'] ?? null) ? $all['general'] : array();
        $season_html = '';

        foreach ($seasons as $year => $season) {
            if (!self::public_season($year, $season)) continue;
            $regular = self::regular_items($season, $language, $labels);
            $exceptions = self::exception_items($season, $language, $labels, false);
            $closures = self::exception_items($season, $language, $labels, true);
            $events = self::period_items($season, $language, $labels, true);
            $periods = self::period_items($season, $language, $labels, false);
            $holidays = self::holiday_items($season, $general, $language, $labels);
            if (!$regular && !$exceptions && !$closures && !$events && !$periods && !$holidays) continue;

            $season_html .= '<section class="parcs-ht-calendar-semantic-season" data-htp-semantic-year="' . esc_attr((string)$year) . '">';
            $season_html .= '<h3>' . esc_html(sprintf($labels['season'], (string)$year)) . '</h3>';
            $start = (string)($season['season_start'] ?? '');
            $end = (string)($season['season_end'] ?? '');
            if (self::valid_date($start) && self::valid_date($end)) {
                $season_html .= '<p>' . sprintf($labels['season_range'], self::date_html($start, $language), self::date_html($end, $language)) . '</p>';
            }
            $season_html .= self::list_html($labels['regular'], $regular, 'is-regular');
            $season_html .= self::list_html($labels['exceptions'], $exceptions, 'is-exception');
            $season_html .= self::list_html($labels['closures'], $closures, 'is-closure');
            $season_html .= self::list_html($labels['events'], $events, 'is-event');
            $season_html .= self::list_html($labels['periods'], $periods, 'is-period');
            $season_html .= self::list_html($labels['holidays'], $holidays, 'is-holiday');
            $season_html .= '</section>';
        }

        if ($season_html === '') return '';
        return '<section class="parcs-ht-official-info parcs-ht-calendar-semantic" data-htp-component="calendar-semantic"><details><summary>' . esc_html($labels['summary']) . '</summary><div class="parcs-ht-calendar-semantic-content">' . $season_html . '</div></details></section>';
    }

    public static function append_to_calendar_shortcodes($output, $tag, $attr, $m) {
        unset($attr, $m);
        if (is_admin()) return $output;
        $allowed = array(
            'parc_calendrier', 'parc_calendrier_fr', 'parc_calendrier_en', 'parc_calendrier_de',
            'parc_horaires_tarifs', 'parc_horaires_tarifs_fr', 'parc_horaires_tarifs_en', 'parc_horaires_tarifs_de',
        );
        if (!in_array((string)$tag, $allowed, true)) return $output;
        if (!apply_filters('parcs_ht_calendar_semantic_enabled', true, $tag)) return $output;
        static $printed = false;
        if ($printed) return $output;
        $printed = true;
        return $output . self::render(self::language_from_tag($tag));
    }
}
