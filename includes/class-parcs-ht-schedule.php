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
            'last_entry_minutes', 'accent_color', 'event_legend_label',
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
                'opensToday' => 'Ouverture aujourd’hui à {open}', 'openToday' => 'Ouvert aujourd’hui de {open} à {close}',
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
                'opensToday' => 'Opens today at {open}', 'openToday' => 'Open today from {open} to {close}',
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
                'opensToday' => 'Öffnet heute um {open}', 'openToday' => 'Heute geöffnet von {open} bis {close}',
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
