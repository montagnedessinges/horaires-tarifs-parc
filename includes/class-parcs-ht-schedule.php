<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Schedule {
    private static function enabled_rows($rows) {
        if (!is_array($rows)) return array();
        return array_values(array_filter($rows, static function ($row) {
            return is_array($row) && (string)($row['enabled'] ?? '0') === '1';
        }));
    }
    public static function language() {
        if (function_exists('qtranxf_getLanguage')) {
            $language = qtranxf_getLanguage();
            if (in_array($language, array('fr', 'en', 'de'), true)) {
                return $language;
            }
        }
        $locale = substr((string) determine_locale(), 0, 2);
        return in_array($locale, array('fr', 'en', 'de'), true) ? $locale : 'fr';
    }

    public static function translation($value, $language, $fallback = '') {
        if (!is_array($value)) {
            return is_string($value) ? $value : $fallback;
        }
        if (isset($value[$language]) && $value[$language] !== '') {
            return $value[$language];
        }
        return isset($value['fr']) ? $value['fr'] : $fallback;
    }

    public static function timezone($settings = null) {
        $name = is_array($settings) && !empty($settings['timezone']) ? (string) $settings['timezone'] : '';
        if ($name === '' && function_exists('wp_timezone_string')) {
            $name = (string) wp_timezone_string();
        }
        if ($name === '') $name = 'Europe/Paris';
        try {
            new DateTimeZone($name);
        } catch (Exception $e) {
            $name = 'Europe/Paris';
        }
        return $name;
    }

    private static function valid_date($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) return false;
        $parts = array_map('intval', explode('-', (string) $date));
        return count($parts) === 3 && checkdate($parts[1], $parts[2], $parts[0]);
    }

    private static function row_in_range($row, $date) {
        return is_array($row) && (string)($row['enabled'] ?? '0') === '1'
            && !empty($row['start']) && !empty($row['end'])
            && $date >= (string)$row['start'] && $date <= (string)$row['end'];
    }

    private static function weekday($date, $timezone) {
        try {
            return (string) (new DateTimeImmutable($date, new DateTimeZone($timezone)))->format('N');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Résout l'état effectif d'une journée. Cet ordre est la règle métier canonique :
     * exception prioritaire (fermeture avant horaires à priorité égale), puis horaire
     * habituel, sinon fermeture.
     */
    public static function resolve_day($season, $general, $date, $timezone = 'Europe/Paris') {
        $season = is_array($season) ? $season : array();
        $general = is_array($general) ? $general : array();
        $timezone = self::timezone(array('timezone' => $timezone));
        $closed = array(
            'in_season' => true, 'open' => false, 'exceptional' => false,
            'type' => 'closed', 'color' => '#eeeeee', 'slots' => array(),
            'openTime' => '', 'closeTime' => '', 'lastEntryMinutes' => '',
        );
        if (!self::valid_date($date)) {
            $closed['in_season'] = false;
            $closed['type'] = 'invalid';
            return $closed;
        }
        $start = (string)($season['season_start'] ?? '');
        $end = (string)($season['season_end'] ?? '');
        if (($start !== '' && $date < $start) || ($end !== '' && $date > $end)) {
            $closed['in_season'] = false;
            $closed['type'] = 'outside';
            return $closed;
        }

        $exceptions = array_values(array_filter((array)($season['exceptions'] ?? array()), static function ($row) use ($date) {
            return Parcs_HT_Schedule::row_in_range($row, $date);
        }));
        usort($exceptions, static function ($a, $b) {
            $priority = (int)($b['priority'] ?? 0) - (int)($a['priority'] ?? 0);
            if ($priority !== 0) return $priority;
            $a_type = (string)($a['type'] ?? 'hours');
            $b_type = (string)($b['type'] ?? 'hours');
            if ($a_type === $b_type) return 0;
            return $a_type === 'closed' ? -1 : 1;
        });
        if ($exceptions) {
            $exception = $exceptions[0];
            if ((string)($exception['type'] ?? 'hours') === 'closed') {
                $closed['exceptional'] = true;
                $closed['exception'] = $exception;
                $closed['source'] = $exception;
                return $closed;
            }
            if (!empty($exception['open']) && !empty($exception['close'])) {
                $slots = array(array('open'=>(string)$exception['open'], 'close'=>(string)$exception['close']));
                if (!empty($exception['open2']) && !empty($exception['close2'])) {
                    $slots[] = array('open'=>(string)$exception['open2'], 'close'=>(string)$exception['close2']);
                }
                return array(
                    'in_season'=>true, 'open'=>true, 'exceptional'=>true, 'type'=>'hours',
                    'color'=>(string)($general['accent_color'] ?? '#ef7b5b'), 'slots'=>$slots,
                    'openTime'=>(string)$exception['open'], 'closeTime'=>(string)$exception['close'],
                    'lastEntryMinutes'=>(string)($exception['last_entry_minutes'] ?? ''),
                    'exception'=>$exception, 'source'=>$exception,
                );
            }
        }

        $weekday = self::weekday($date, $timezone);
        foreach ((array)($season['regular_periods'] ?? array()) as $period) {
            if (!self::row_in_range($period, $date)) continue;
            $days = array_map('strval', is_array($period['weekdays'] ?? null) ? $period['weekdays'] : array());
            if (!in_array($weekday, $days, true)) continue;
            if (empty($period['open']) || empty($period['close'])) return $closed;
            $slots = array(array('open'=>(string)$period['open'], 'close'=>(string)$period['close']));
            if (!empty($period['open2']) && !empty($period['close2'])) {
                $slots[] = array('open'=>(string)$period['open2'], 'close'=>(string)$period['close2']);
            }
            return array(
                'in_season'=>true, 'open'=>true, 'exceptional'=>false, 'type'=>'regular',
                'color'=>(string)($period['color'] ?? '#9AAA8B'), 'slots'=>$slots,
                'openTime'=>(string)$period['open'], 'closeTime'=>(string)$period['close'],
                'lastEntryMinutes'=>(string)($period['last_entry_minutes'] ?? ''),
                'period'=>$period, 'source'=>$period,
            );
        }
        return $closed;
    }

    public static function calendar_items($season, $date, $language) {
        $out = array();
        foreach ((array)($season['special_periods'] ?? array()) as $row) {
            if (!self::row_in_range($row, $date) || (string)($row['show_on_calendar'] ?? '1') === '0') continue;
            $title = self::translation($row['title'] ?? array(), $language, (string)($row['internal_label'] ?? ''));
            if ($title === '') continue;
            $out[] = array(
                'title'=>$title, 'kind'=>(string)($row['kind'] ?? 'event'),
                'color'=>(string)($row['color'] ?? '#e7c55b'), 'source'=>$row,
            );
        }
        return $out;
    }

    public static function in_school_holiday($season, $date) {
        foreach ((array)($season['school_holidays'] ?? array()) as $row) {
            if (self::row_in_range($row, $date)) return true;
        }
        return false;
    }

    public static function is_public_holiday($season, $date) {
        foreach ((array)($season['public_holidays'] ?? array()) as $row) {
            if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['date'] ?? '') === $date) return true;
        }
        return false;
    }

    public static function domain_rule($season, $date, $status, $timezone = 'Europe/Paris') {
        if (empty($status['open'])) return false;
        foreach ((array)($season['special_periods'] ?? array()) as $period) {
            if (!self::row_in_range($period, $date) || (string)($period['kind'] ?? '') === 'event') continue;
            if ((string)($period['skip_domain_rules'] ?? '0') === '1') return false;
        }
        $source = is_array($status['source'] ?? null) ? $status['source'] : array();
        if (!empty($status['exceptional']) && (string)($source['apply_domain_rules'] ?? '1') === '0') return false;
        $weekday = self::weekday($date, self::timezone(array('timezone'=>$timezone)));
        foreach ((array)($season['domain_rules'] ?? array()) as $rule) {
            if (!self::row_in_range($rule, $date)) continue;
            $days = array_map('strval', is_array($rule['weekdays'] ?? null) ? $rule['weekdays'] : array());
            if (!in_array($weekday, $days, true)) continue;
            if ((string)($rule['exclude_weekends'] ?? '0') === '1' && in_array($weekday, array('6','7'), true)) continue;
            if ((string)($rule['exclude_school_holidays'] ?? '0') === '1' && self::in_school_holiday($season, $date)) continue;
            if ((string)($rule['exclude_public_holidays'] ?? '0') === '1' && self::is_public_holiday($season, $date)) continue;
            return $rule;
        }
        return false;
    }

    /**
     * Analyse exhaustive d'une saison, utilisée dans l'administration et les tests.
     */
    public static function audit_season($settings) {
        $settings = is_array($settings) ? $settings : array();
        $season = array(
            'season_start'=>(string)($settings['general']['season_start'] ?? $settings['season_start'] ?? ''),
            'season_end'=>(string)($settings['general']['season_end'] ?? $settings['season_end'] ?? ''),
            'regular_periods'=>(array)($settings['regular_periods'] ?? array()),
            'school_holidays'=>(array)($settings['school_holidays'] ?? array()),
            'special_periods'=>(array)($settings['special_periods'] ?? array()),
            'public_holidays'=>(array)($settings['public_holidays'] ?? array()),
            'domain_rules'=>(array)($settings['domain_rules'] ?? array()),
            'exceptions'=>(array)($settings['exceptions'] ?? array()),
        );
        $timezone = self::timezone($settings);
        $report = array(
            'valid'=>true, 'errors'=>array(), 'warnings'=>array(),
            'summary'=>array('days'=>0,'open'=>0,'closed'=>0,'exceptional_hours'=>0,'exceptional_closures'=>0,'events'=>0,'reference_periods'=>0,'domain_limited'=>0),
        );
        if (!self::valid_date($season['season_start']) || !self::valid_date($season['season_end']) || $season['season_start'] > $season['season_end']) {
            $report['valid'] = false;
            $report['errors'][] = 'Les dates de début et de fin de saison sont invalides ou inversées.';
            return $report;
        }
        try {
            $date = new DateTimeImmutable($season['season_start'], new DateTimeZone($timezone));
            $last = new DateTimeImmutable($season['season_end'], new DateTimeZone($timezone));
        } catch (Exception $e) {
            $report['valid'] = false;
            $report['errors'][] = 'Impossible d’analyser les dates de cette saison.';
            return $report;
        }
        $conflict_dates = array();
        for ($guard=0; $date <= $last && $guard < 740; $guard++, $date=$date->modify('+1 day')) {
            $ymd = $date->format('Y-m-d');
            $report['summary']['days']++;
            $status = self::resolve_day($season, (array)($settings['general'] ?? array()), $ymd, $timezone);
            $report['summary'][$status['open'] ? 'open' : 'closed']++;
            if (!empty($status['exceptional'])) {
                $report['summary'][$status['open'] ? 'exceptional_hours' : 'exceptional_closures']++;
            }
            foreach (self::calendar_items($season, $ymd, 'fr') as $item) {
                $report['summary'][(string)($item['kind'] ?? '') === 'event' ? 'events' : 'reference_periods']++;
            }
            if (self::domain_rule($season, $ymd, $status, $timezone)) $report['summary']['domain_limited']++;

            $weekday = self::weekday($ymd, $timezone);
            $regular_matches = 0;
            foreach ($season['regular_periods'] as $row) {
                if (!self::row_in_range($row, $ymd)) continue;
                if (in_array($weekday, array_map('strval', (array)($row['weekdays'] ?? array())), true)) $regular_matches++;
            }
            if ($regular_matches > 1 && count($conflict_dates) < 20) $conflict_dates[] = $ymd;

            $priorities = array();
            foreach ($season['exceptions'] as $row) {
                if (!self::row_in_range($row, $ymd)) continue;
                $priority = (string)(int)($row['priority'] ?? 0);
                $priorities[$priority] = ($priorities[$priority] ?? 0) + 1;
            }
            foreach ($priorities as $priority => $count) {
                if ($count > 1) {
                    $message = 'Plusieurs exceptions de priorité '.$priority.' se chevauchent le '.$ymd.'.';
                    if (!in_array($message, $report['warnings'], true) && count($report['warnings']) < 40) $report['warnings'][] = $message;
                }
            }
        }
        if ($date <= $last) {
            $report['valid'] = false;
            $report['errors'][] = 'La saison dépasse la limite de 740 jours prise en charge.';
        }
        if ($conflict_dates) {
            $report['warnings'][] = 'Horaires habituels superposés aux dates suivantes : '.implode(', ', $conflict_dates).(count($conflict_dates) === 20 ? '…' : '').'.';
        }
        foreach ($season['special_periods'] as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['show_on_calendar'] ?? '1') === '0') continue;
            $titles = is_array($row['title'] ?? null) ? $row['title'] : array();
            foreach (array('fr','en','de') as $language) {
                if (trim((string)($titles[$language] ?? '')) === '') {
                    $report['warnings'][] = 'Une période publique n’a pas de titre en '.strtoupper($language).'.';
                    break;
                }
            }
        }
        $report['valid'] = empty($report['errors']);
        return $report;
    }

    public static function public_settings($settings) {
        $all = (is_array($settings) && isset($settings['seasons']) && is_array($settings['seasons'])) ? $settings : Parcs_HT_Defaults::all_settings();
        $published = array();
        foreach ($all['seasons'] as $year => $season) {
            if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;
            $published[(string)$year] = array(
                'year' => (string)$year,
                'season_start' => isset($season['season_start']) ? $season['season_start'] : '',
                'season_end' => isset($season['season_end']) ? $season['season_end'] : '',
                'regularPeriods' => self::enabled_rows(isset($season['regular_periods']) ? $season['regular_periods'] : array()),
                'schoolHolidays' => self::enabled_rows(isset($season['school_holidays']) ? $season['school_holidays'] : array()),
                'specialPeriods' => self::enabled_rows(isset($season['special_periods']) ? $season['special_periods'] : array()),
                'publicHolidays' => self::enabled_rows(isset($season['public_holidays']) ? $season['public_holidays'] : array()),
                'domainRules' => self::enabled_rows(isset($season['domain_rules']) ? $season['domain_rules'] : array()),
                'exceptions' => self::enabled_rows(isset($season['exceptions']) ? $season['exceptions'] : array()),
            );
        }
        // Le navigateur n'a besoin que d'une petite partie des réglages généraux.
        // Les ~100 réglages de mise en forme restent utilisés côté PHP mais ne sont plus
        // sérialisés dans window.ParcsHTPData sur chaque page avec shortcode.
        $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
        $public_general = array();
        foreach (array(
            'last_entry_minutes', 'accent_color', 'calendar_hours_title', 'event_legend_label',
            'show_public_holidays', 'holiday_message', 'holiday_border_color',
            'holiday_border_width'
        ) as $key) {
            if (array_key_exists($key, $general)) $public_general[$key] = $general[$key];
        }

        return array(
            'timezone' => isset($all['timezone']) ? $all['timezone'] : 'Europe/Paris',
            'general' => $public_general,
            'seasons' => $published,
            'alerts' => self::enabled_rows(isset($all['alerts']) ? $all['alerts'] : array()),
            'activeSeasonYear' => Parcs_HT_Defaults::select_season_year($all),
        );
    }

    public static function dictionaries() {
        return array(
            'fr' => array(
                'today' => 'Aujourd’hui', 'openNow' => 'OUVERT',
                'opensToday' => 'Ouverture aujourd’hui à {open}', 'reopensToday' => 'Réouverture aujourd’hui à {open}', 'openToday' => 'Ouvert aujourd’hui de {open} à {close}',
                'closedToday' => 'Fermé aujourd’hui', 'closedForToday' => 'Fermé pour aujourd’hui', 'opensTomorrowAt' => 'Ouverture demain à {time}', 'opensOnAt' => 'Ouverture le {date} à {time}',
                'lastEntry' => 'Dernière entrée à {time}', 'lastEntryCompact' => 'Dernière entrée : {time}', 'fromTime' => 'À partir de {time}', 'openingAt' => 'Ouverture à {time}', 'seeYouTomorrow' => 'À demain !', 'nextOpeningLabel' => 'Prochaine ouverture', 'nextOpeningCompact' => '{date} à {time}', 'openTodayCompact' => 'Ouvert aujourd’hui', 'nextOpening' => 'Prochaine ouverture : {date} à {time}',
                'calendar' => 'Calendrier', 'monthHours' => 'Horaires du mois : {hours}', 'closed' => 'Fermé',
                'exceptionalHours' => 'Horaires exceptionnels', 'exceptionalClosure' => 'Fermeture exceptionnelle',
                'selectDate' => 'Sélectionnez une journée pour afficher ses horaires.',
                'individual' => 'Individuels', 'reduced' => 'Tarifs réduits', 'groups' => 'Groupes',
                'prices' => 'Tarifs', 'tickets' => 'Acheter vos billets', 'quote' => 'Faire une demande de devis',
                'payments' => 'Moyens de paiement', 'publicHoliday' => 'Jour férié', 'schoolHoliday' => 'Vacances scolaires', 'event' => 'Événement', 'closedShort' => 'Fermé', 'exceptionallyClosedShort' => 'Fermé exceptionnellement', 'reopensOn' => 'Réouverture le {date}', 'reopensIn' => 'Réouverture dans {days} jours', 'reopensTomorrow' => 'Réouverture demain', 'disabilityNote' => 'Tarif sur présentation d’un justificatif. Si le justificatif mentionne un besoin d’accompagnement, l’accompagnateur bénéficie également du tarif réduit.', 'closePopup' => 'Fermer', 'notAvailable' => 'Les dates et horaires ne sont pas encore disponibles.',
            ),
            'en' => array(
                'today' => 'Today', 'openNow' => 'OPEN',
                'opensToday' => 'Opens today at {open}', 'reopensToday' => 'Reopens today at {open}', 'openToday' => 'Open today from {open} to {close}',
                'closedToday' => 'Closed today', 'closedForToday' => 'Closed for today', 'opensTomorrowAt' => 'Open tomorrow at {time}', 'opensOnAt' => 'Open on {date} at {time}',
                'lastEntry' => 'Last admission at {time}', 'lastEntryCompact' => 'Last admission: {time}', 'fromTime' => 'From {time}', 'openingAt' => 'Opens at {time}', 'seeYouTomorrow' => 'See you tomorrow!', 'nextOpeningLabel' => 'Next opening', 'nextOpeningCompact' => '{date} at {time}', 'openTodayCompact' => 'Open today', 'nextOpening' => 'Next opening: {date} at {time}',
                'calendar' => 'Calendar', 'monthHours' => 'Opening hours this month: {hours}', 'closed' => 'Closed',
                'exceptionalHours' => 'Exceptional opening hours', 'exceptionalClosure' => 'Exceptional closure',
                'selectDate' => 'Select a day to view its opening hours.',
                'individual' => 'Individuals', 'reduced' => 'Reduced rates', 'groups' => 'Groups',
                'prices' => 'Prices', 'tickets' => 'Buy tickets', 'quote' => 'Request a quote',
                'payments' => 'Payment methods', 'publicHoliday' => 'Public holiday', 'schoolHoliday' => 'School holidays', 'event' => 'Event', 'closedShort' => 'Closed', 'exceptionallyClosedShort' => 'Exceptionally closed', 'reopensOn' => 'Reopens on {date}', 'reopensIn' => 'Reopens in {days} days', 'reopensTomorrow' => 'Reopens tomorrow', 'disabilityNote' => 'Reduced rate on presentation of valid proof. If the document states that assistance is required, the companion also receives the reduced rate.', 'closePopup' => 'Close', 'notAvailable' => 'Dates and opening hours are not available yet.',
            ),
            'de' => array(
                'today' => 'Heute', 'openNow' => 'GEÖFFNET',
                'opensToday' => 'Öffnet heute um {open}', 'reopensToday' => 'Öffnet heute wieder um {open}', 'openToday' => 'Heute geöffnet von {open} bis {close}',
                'closedToday' => 'Heute geschlossen', 'closedForToday' => 'Für heute geschlossen', 'opensTomorrowAt' => 'Morgen ab {time} geöffnet', 'opensOnAt' => 'Geöffnet am {date} ab {time}',
                'lastEntry' => 'Letzter Einlass um {time}', 'lastEntryCompact' => 'Letzter Einlass: {time}', 'fromTime' => 'Ab {time}', 'openingAt' => 'Öffnung um {time}', 'seeYouTomorrow' => 'Bis morgen!', 'nextOpeningLabel' => 'Nächste Öffnung', 'nextOpeningCompact' => '{date} um {time}', 'openTodayCompact' => 'Heute geöffnet', 'nextOpening' => 'Nächste Öffnung: {date} um {time}',
                'calendar' => 'Kalender', 'monthHours' => 'Öffnungszeiten in diesem Monat: {hours}', 'closed' => 'Geschlossen',
                'exceptionalHours' => 'Außergewöhnliche Öffnungszeiten', 'exceptionalClosure' => 'Außergewöhnliche Schließung',
                'selectDate' => 'Wählen Sie einen Tag, um die Öffnungszeiten anzuzeigen.',
                'individual' => 'Einzelbesucher', 'reduced' => 'Ermäßigte Tarife', 'groups' => 'Gruppen',
                'prices' => 'Preise', 'tickets' => 'Tickets kaufen', 'quote' => 'Angebot anfordern',
                'payments' => 'Zahlungsmittel', 'publicHoliday' => 'Feiertag', 'schoolHoliday' => 'Schulferien', 'event' => 'Veranstaltung', 'closedShort' => 'Geschlossen', 'exceptionallyClosedShort' => 'Ausnahmsweise geschlossen', 'reopensOn' => 'Wiedereröffnung am {date}', 'reopensIn' => 'Wiedereröffnung in {days} Tagen', 'reopensTomorrow' => 'Wiedereröffnung morgen', 'disabilityNote' => 'Ermäßigter Tarif gegen Vorlage eines gültigen Nachweises. Wenn darin ein Begleitbedarf angegeben ist, erhält auch die Begleitperson den ermäßigten Tarif.', 'closePopup' => 'Schließen', 'notAvailable' => 'Termine und Öffnungszeiten sind noch nicht verfügbar.',
            ),
        );
    }
}
