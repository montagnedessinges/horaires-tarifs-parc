<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Defaults {
    const OPTION = 'parcs_ht_settings';
    const BACKUP_OPTION = 'parcs_ht_settings_backup_pre_1_1_0';
    const POPUP_FLAG_OPTION = 'parcs_ht_has_popup_source';
    const SCHEMA_VERSION = 25;

    public static function activate() {
        if (get_option(self::OPTION, null) === null) {
            add_option(self::OPTION, self::get(), '', false);
        }
        self::maybe_upgrade();
        self::refresh_popup_flag();
    }

    public static function has_popup_source_fast() {
        $flag = get_option(self::POPUP_FLAG_OPTION, null);
        if ($flag === '0' || $flag === 0 || $flag === false) return false;
        if ($flag === '1' || $flag === 1 || $flag === true) return true;
        return self::refresh_popup_flag();
    }

    public static function refresh_popup_flag($saved = null) {
        if (!is_array($saved)) $saved = get_option(self::OPTION, array());
        $has = self::settings_have_popup_source($saved);
        if (get_option(self::POPUP_FLAG_OPTION, null) === null) {
            add_option(self::POPUP_FLAG_OPTION, $has ? '1' : '0', '', true);
        } else {
            update_option(self::POPUP_FLAG_OPTION, $has ? '1' : '0', true);
        }
        return $has;
    }

    private static function settings_have_popup_source($saved) {
        if (!is_array($saved)) return false;
        foreach ((array)($saved['alerts'] ?? array()) as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1') continue;
            if (array_key_exists('published', $row) && (string)$row['published'] !== '1') continue;
            if (!empty($row['start'])) return true;
        }
        foreach ((array)($saved['seasons'] ?? array()) as $season) {
            if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;
            foreach ((array)($season['exceptions'] ?? array()) as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1' && !empty($row['start']) && !empty($row['end'])) return true;
            }
            foreach ((array)($season['special_periods'] ?? array()) as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1' && !empty($row['start']) && !empty($row['end'])) return true;
            }
        }
        return false;
    }

    public static function maybe_upgrade() {
        // Une requête WordPress peut appeler maybe_upgrade() plusieurs fois (bootstrap + lecture des réglages).
        // Une seule vérification par requête suffit et évite des lectures/fusions inutiles.
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $saved = get_option(self::OPTION, null);
        if (!is_array($saved)) {
            return;
        }
        $version = isset($saved['schema_version']) ? (int) $saved['schema_version'] : 1;
        if ($version >= self::SCHEMA_VERSION && isset($saved['seasons']) && is_array($saved['seasons'])) {
            return;
        }
        if ($version >= 3 && isset($saved['seasons']) && is_array($saved['seasons'])) {
            $old_notes = array(
                'fr' => 'Tarifs réduits sur présentation d’un justificatif valable : étudiants post-bac, titulaires d’une carte Cezam, du Guide du Routard, d’un Passeport Gîtes Bas-Rhin ou Haut-Rhin, ou d’un billet de la navette Haut-Koenigsbourg. L’accompagnateur bénéficie du tarif réduit si le justificatif mentionne un besoin d’accompagnement.',
                'en' => 'Reduced rates are available upon presentation of valid proof for post-secondary students, Cezam cardholders, Guide du Routard holders, Bas-Rhin or Haut-Rhin Gîtes passport holders, or holders of a Haut-Koenigsbourg shuttle ticket. A companion receives the reduced rate if the supporting document states that assistance is required.',
                'de' => 'Ermäßigte Tarife gelten gegen Vorlage eines gültigen Nachweises für Studierende nach dem Abitur, Inhaber einer Cezam-Karte, des Guide du Routard, eines Gîtes-Passes Bas-Rhin oder Haut-Rhin oder eines Tickets für den Haut-Koenigsbourg-Shuttle. Eine Begleitperson erhält den ermäßigten Tarif, wenn im Nachweis ein Begleitbedarf angegeben ist.',
            );
            $new_notes = self::mds_tariffs()['notes'];
            if (isset($saved['tariffs']['notes']) && is_array($saved['tariffs']['notes'])) {
                foreach (array('fr','en','de') as $lang) if (($saved['tariffs']['notes'][$lang] ?? '') === $old_notes[$lang]) $saved['tariffs']['notes'][$lang] = $new_notes[$lang];
            }
            // Répare uniquement les jours concernés qui auraient été perdus lors d'une ancienne migration.
            // Les valeurs déjà présentes ne sont jamais écrasées.
            $saved = self::restore_weekdays_from_backup($saved);
            $saved = self::upgrade_v120_structures($saved);
            $saved = self::upgrade_v133_structures($saved, $version);
            $saved = self::upgrade_v141_structures($saved, $version);
            $saved = self::upgrade_v158_structures($saved, $version);
            $saved = self::upgrade_v1510_structures($saved, $version);
            $saved = self::upgrade_v1511_structures($saved, $version);
            $saved = self::upgrade_v160_structures($saved, $version);
            $saved = self::upgrade_v171_structures($saved, $version);
            $saved = self::upgrade_v172_structures($saved, $version);
            $saved['schema_version'] = self::SCHEMA_VERSION;
            update_option(self::OPTION, $saved, false);
            return;
        }

        if (get_option(self::BACKUP_OPTION, null) === null) {
            add_option(self::BACKUP_OPTION, $saved, '', false);
        }

        $defaults = self::get();
        $site_type = isset($saved['site_type']) ? sanitize_key($saved['site_type']) : $defaults['site_type'];
        $legacy_general = isset($saved['general']) && is_array($saved['general']) ? $saved['general'] : array();
        $year = isset($legacy_general['year']) && preg_match('/^20\\d{2}$/', (string) $legacy_general['year']) ? (string) $legacy_general['year'] : ($site_type === 'mds' ? '2026' : (string) wp_date('Y'));

        $has_usable_period = false;
        $regular = isset($saved['regular_periods']) && is_array($saved['regular_periods']) ? $saved['regular_periods'] : array();
        foreach ($regular as $period) {
            if (is_array($period) && !empty($period['start']) && !empty($period['end']) && !empty($period['open']) && !empty($period['close'])) {
                $has_usable_period = true;
                break;
            }
        }
        if ($site_type === 'mds' && !$has_usable_period && $year === '2026') {
            $regular = $defaults['seasons']['2026']['regular_periods'];
        }

        $season = self::empty_season($year);
        $season['published'] = '1';
        $season['season_start'] = isset($legacy_general['season_start']) ? (string) $legacy_general['season_start'] : '';
        $season['season_end'] = isset($legacy_general['season_end']) ? (string) $legacy_general['season_end'] : '';
        $season['regular_periods'] = $regular;
        foreach (array('school_holidays','public_holidays','domain_rules','exceptions') as $key) {
            $season[$key] = isset($saved[$key]) && is_array($saved[$key]) ? $saved[$key] : array();
        }

        $global_general = isset($defaults['general']) ? $defaults['general'] : array();
        foreach (array('park_name','last_entry_minutes','tickets_url','groups_url','primary_color','secondary_color','accent_color','highlight_color') as $key) {
            if (array_key_exists($key, $legacy_general)) {
                $global_general[$key] = $legacy_general[$key];
            }
        }

        $migrated = array(
            'schema_version' => self::SCHEMA_VERSION,
            'site_type' => $site_type,
            'timezone' => isset($saved['timezone']) ? (string) $saved['timezone'] : 'Europe/Paris',
            'languages' => array('fr','en','de'),
            'general' => $global_general,
            'seasons' => array($year => $season),
            'alerts' => isset($saved['alerts']) && is_array($saved['alerts']) ? $saved['alerts'] : array(),
            'quote_page' => isset($saved['quote_page']) && is_array($saved['quote_page']) ? $saved['quote_page'] : self::quote_page_defaults(),
            'tariffs' => isset($saved['tariffs']) && is_array($saved['tariffs']) ? $saved['tariffs'] : $defaults['tariffs'],
        );
        $migrated = self::restore_weekdays_from_backup($migrated);
        $migrated = self::upgrade_v120_structures($migrated);
        $migrated = self::upgrade_v133_structures($migrated, $version);
        $migrated = self::upgrade_v141_structures($migrated, $version);
        $migrated = self::upgrade_v158_structures($migrated, $version);
        $migrated = self::upgrade_v1510_structures($migrated, $version);
        $migrated = self::upgrade_v1511_structures($migrated, $version);
        $migrated = self::upgrade_v160_structures($migrated, $version);
        update_option(self::OPTION, $migrated, false);
    }

    /**
     * Restaure les jours concernés depuis la sauvegarde pré-1.1.0 uniquement
     * quand une ligne correspondante existe mais que son tableau weekdays est vide/absent.
     * Cela évite qu'une mise à jour efface les cases cochées, sans modifier un réglage valide.
     */
    private static function restore_weekdays_from_backup($settings) {
        if (!is_array($settings) || empty($settings['seasons']) || !is_array($settings['seasons'])) return $settings;
        $backup = get_option(self::BACKUP_OPTION, null);
        if (!is_array($backup)) return $settings;

        $backup_general = isset($backup['general']) && is_array($backup['general']) ? $backup['general'] : array();
        $backup_year = isset($backup_general['year']) && preg_match('/^20\d{2}$/', (string)$backup_general['year']) ? (string)$backup_general['year'] : '';
        if ($backup_year === '') {
            foreach ($settings['seasons'] as $year => $season) { $backup_year = (string)$year; break; }
        }
        if ($backup_year === '' || !isset($settings['seasons'][$backup_year])) return $settings;

        $lists = array(
            'regular_periods' => isset($backup['regular_periods']) && is_array($backup['regular_periods']) ? $backup['regular_periods'] : array(),
            'domain_rules' => isset($backup['domain_rules']) && is_array($backup['domain_rules']) ? $backup['domain_rules'] : array(),
        );
        foreach ($lists as $list_key => $legacy_rows) {
            if (empty($legacy_rows) || empty($settings['seasons'][$backup_year][$list_key]) || !is_array($settings['seasons'][$backup_year][$list_key])) continue;
            foreach ($settings['seasons'][$backup_year][$list_key] as $i => &$row) {
                if (!is_array($row)) continue;
                if (isset($row['weekdays']) && is_array($row['weekdays']) && count($row['weekdays']) > 0) continue;
                $candidate = null;
                if (isset($legacy_rows[$i]) && is_array($legacy_rows[$i])) $candidate = $legacy_rows[$i];
                if ($candidate === null) {
                    foreach ($legacy_rows as $legacy) {
                        if (!is_array($legacy)) continue;
                        $same_label = isset($row['label'],$legacy['label']) && (string)$row['label'] !== '' && (string)$row['label'] === (string)$legacy['label'];
                        $same_dates = isset($row['start'],$row['end'],$legacy['start'],$legacy['end']) && (string)$row['start'] === (string)$legacy['start'] && (string)$row['end'] === (string)$legacy['end'];
                        if ($same_label || $same_dates) { $candidate = $legacy; break; }
                    }
                }
                if ($candidate && isset($candidate['weekdays']) && is_array($candidate['weekdays'])) {
                    $days = array_values(array_intersect(array('1','2','3','4','5','6','7'), array_map('strval', $candidate['weekdays'])));
                    if ($days) $row['weekdays'] = $days;
                }
            }
            unset($row);
        }
        return $settings;
    }


    private static function upgrade_v120_structures($settings) {
        if (!is_array($settings)) return $settings;
        if (!isset($settings['tariffs']) || !is_array($settings['tariffs'])) $settings['tariffs'] = array();
        if (isset($settings['seasons']) && is_array($settings['seasons'])) {
            foreach ($settings['seasons'] as &$season) {
                if (!is_array($season) || empty($season['exceptions']) || !is_array($season['exceptions'])) continue;
                foreach ($season['exceptions'] as &$exception) {
                    if (!is_array($exception)) continue;
                    if (!array_key_exists('apply_domain_rules', $exception)) $exception['apply_domain_rules'] = '1';
                    if (!array_key_exists('show_public_marker', $exception)) $exception['show_public_marker'] = '1';
                    if (!array_key_exists('context', $exception)) $exception['context'] = array('fr'=>'','en'=>'','de'=>'');
                    if (!array_key_exists('show_popup', $exception)) $exception['show_popup'] = '0';
                    if (!array_key_exists('popup_mode', $exception)) $exception['popup_mode'] = 'auto';
                    if (!array_key_exists('popup_title', $exception)) $exception['popup_title'] = array('fr'=>'','en'=>'','de'=>'');
                    if (!array_key_exists('popup_message', $exception)) $exception['popup_message'] = array('fr'=>'','en'=>'','de'=>'');
                    if (!array_key_exists('popup_button_label', $exception)) $exception['popup_button_label'] = array('fr'=>'','en'=>'','de'=>'');
                    if (!array_key_exists('popup_button_url', $exception)) $exception['popup_button_url'] = '';
                    if (!array_key_exists('popup_lead_mode', $exception)) $exception['popup_lead_mode'] = 'days_before';
                    if (!array_key_exists('popup_days_before', $exception)) $exception['popup_days_before'] = '1';
                    if (!array_key_exists('popup_start', $exception)) $exception['popup_start'] = '';
                    if (!array_key_exists('popup_end', $exception)) $exception['popup_end'] = '';
                }
                unset($exception);
            }
            unset($season);
        }

        // 1.3.0 : périodes spécifiques / événements génériques.
        // Les anciennes vacances scolaires restent compatibles et sont migrées
        // automatiquement vers le nouveau système sans être supprimées.
        if (isset($settings['seasons']) && is_array($settings['seasons'])) {
            foreach ($settings['seasons'] as &$season) {
                if (!is_array($season)) continue;
                if (!isset($season['special_periods']) || !is_array($season['special_periods'])) {
                    $season['special_periods'] = array();
                    $legacy_school = isset($season['school_holidays']) && is_array($season['school_holidays']) ? $season['school_holidays'] : array();
                    foreach ($legacy_school as $legacy) {
                        if (!is_array($legacy)) continue;
                        $label = isset($legacy['label']) ? (string)$legacy['label'] : '';
                        $season['special_periods'][] = array(
                            'enabled' => isset($legacy['enabled']) ? (string)$legacy['enabled'] : '1',
                            'kind' => 'school_holiday',
                            'internal_label' => $label,
                            'title' => array(
                                'fr' => $label !== '' ? $label : 'Vacances scolaires',
                                'en' => 'School holidays',
                                'de' => 'Schulferien',
                            ),
                            'start' => isset($legacy['start']) ? (string)$legacy['start'] : '',
                            'end' => isset($legacy['end']) ? (string)$legacy['end'] : '',
                            'color' => '#7b61a8',
                            'icon' => 'star',
                            'message' => array('fr'=>'','en'=>'','de'=>''),
                            'button_label' => array('fr'=>'','en'=>'','de'=>''),
                            'button_url' => '',
                            'show_on_calendar' => '1',
                            'skip_domain_rules' => '1',
                            'show_popup' => '0',
                            'popup_lead_mode' => 'days_before',
                            'popup_days_before' => '14',
                            'popup_start' => '',
                            'popup_end' => '',
                            'popup_title' => array('fr'=>'','en'=>'','de'=>''),
                            'popup_message' => array('fr'=>'','en'=>'','de'=>''),
                            'popup_button_label' => array('fr'=>'','en'=>'','de'=>''),
                            'popup_button_url' => '',
                            'popup_image_url' => '',
                        );
                    }
                }
                foreach ($season['special_periods'] as &$event) {
                    if (!is_array($event)) continue;
                    $defaults = array(
                        'enabled'=>'1','kind'=>'event','internal_label'=>'','title'=>array('fr'=>'','en'=>'','de'=>''),
                        'start'=>'','end'=>'','color'=>'#e7c55b','icon'=>'star','display_mode'=>'spot','message'=>array('fr'=>'','en'=>'','de'=>''),
                        'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>'','show_on_calendar'=>'1','skip_domain_rules'=>'0',
                        'show_popup'=>'0','popup_lead_mode'=>'days_before','popup_days_before'=>'14','popup_start'=>'','popup_end'=>'',
                        'popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),
                        'popup_button_label'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_url'=>'','popup_image_url'=>''
                    );
                    $event = array_replace($defaults, $event);
                    if (($event['kind'] ?? '') === 'busy') $event['kind'] = 'other';
                    if (!array_key_exists('show_on_calendar', $event)) $event['show_on_calendar'] = '1';
                }
                unset($event);
                if (isset($season['domain_rules']) && is_array($season['domain_rules'])) {
                    foreach ($season['domain_rules'] as &$rule) {
                        if (!is_array($rule)) continue;
                        if (!isset($rule['public_title']) || !is_array($rule['public_title'])) {
                            $rule['public_title'] = array('fr'=>'Accès temporairement limité','en'=>'Temporarily limited access','de'=>'Vorübergehend eingeschränkter Zugang');
                        }
                        if (!array_key_exists('auto_details', $rule)) $rule['auto_details'] = '1';
                    }
                    unset($rule);
                }
            }
            unset($season);
        }

        foreach (array('individual','reduced','groups') as $group) {
            if (empty($settings['tariffs'][$group]) || !is_array($settings['tariffs'][$group])) continue;
            foreach ($settings['tariffs'][$group] as &$row) {
                if (!is_array($row)) continue;
                if (!isset($row['subtitle'])) $row['subtitle'] = isset($row['detail']) && is_array($row['detail']) ? $row['detail'] : array('fr'=>'','en'=>'','de'=>'');
                if (!isset($row['note'])) $row['note'] = array('fr'=>'','en'=>'','de'=>'');
                if (!isset($row['subtitle_color'])) $row['subtitle_color'] = isset($row['detail_color']) ? $row['detail_color'] : '';
                if (!isset($row['note_color'])) $row['note_color'] = '';
                $fr_label = isset($row['label']['fr']) ? (string)$row['label']['fr'] : '';
                $fr_sub = isset($row['subtitle']['fr']) ? (string)$row['subtitle']['fr'] : '';
                if ($fr_label === 'Personne en situation de handicap' && strpos($fr_sub, 'accompagnateur bénéficie') !== false) {
                    $row['subtitle'] = array('fr'=>'Tarif réduit sur présentation d’un justificatif.','en'=>'Reduced rate on presentation of valid proof.','de'=>'Ermäßigter Tarif gegen Vorlage eines gültigen Nachweises.');
                    $row['detail'] = $row['subtitle'];
                    $row['note'] = array('fr'=>'L’accompagnateur bénéficie du tarif réduit si le justificatif mentionne un besoin d’accompagnement.','en'=>'The companion also receives the reduced rate if the supporting document states that assistance is required.','de'=>'Auch die Begleitperson erhält den ermäßigten Tarif, wenn im Nachweis ein Begleitbedarf angegeben ist.');
                }
            }
            unset($row);
        }
        if (!isset($settings['tariffs']['payment_items']) || !is_array($settings['tariffs']['payment_items'])) {
            $settings['tariffs']['payment_items'] = array();
            $labels = array(
                'card'=>array('fr'=>'Carte bancaire','en'=>'Bank card','de'=>'Bankkarte'),
                'cash'=>array('fr'=>'Espèces','en'=>'Cash','de'=>'Bargeld'),
                'holiday_voucher'=>array('fr'=>'Chèques-Vacances papier','en'=>'','de'=>''),
                'connect'=>array('fr'=>'Chèques-Vacances Connect','en'=>'','de'=>'')
            );
            $icons = isset($settings['tariffs']['payment_icons']) && is_array($settings['tariffs']['payment_icons']) ? $settings['tariffs']['payment_icons'] : array();
            $styles = isset($settings['tariffs']['payment_styles']) && is_array($settings['tariffs']['payment_styles']) ? $settings['tariffs']['payment_styles'] : array();
            foreach ($icons as $icon) {
                if (!isset($labels[$icon])) continue;
                $st = isset($styles[$icon]) && is_array($styles[$icon]) ? $styles[$icon] : array();
                $settings['tariffs']['payment_items'][] = array(
                    'enabled'=>'1','icon'=>$icon,'custom_svg'=>'','custom_svg_force_color'=>'1','label'=>$labels[$icon],
                    'visible'=>array('fr'=>'1','en'=>($labels[$icon]['en']!==''?'1':'0'),'de'=>($labels[$icon]['de']!==''?'1':'0')),
                    'bg_color'=>$st['bg_color'] ?? '#006757','bg_transparent'=>$st['bg_transparent'] ?? '0',
                    'icon_color'=>$st['icon_color'] ?? '#ffffff','text_color'=>$st['text_color'] ?? '#ffffff',
                    'border_color'=>$st['border_color'] ?? '#006757','border_enabled'=>$st['border_enabled'] ?? '0'
                );
            }
        }
        // 1.2.3 : les moyens de paiement deviennent totalement réutilisables.
        // Les données existantes sont conservées. Les anciens pictogrammes génériques
        // sont convertis en SVG personnalisés uniquement si nécessaire.
        if (isset($settings['tariffs']['payment_items']) && is_array($settings['tariffs']['payment_items'])) {
            foreach ($settings['tariffs']['payment_items'] as &$payment) {
                if (!is_array($payment)) continue;
                if (!isset($payment['custom_svg'])) $payment['custom_svg'] = '';
                if (!isset($payment['custom_svg_force_color'])) $payment['custom_svg_force_color'] = '1';
                $legacy_icon = isset($payment['icon']) ? (string)$payment['icon'] : 'card';
                if (in_array($legacy_icon, array('holiday_voucher','connect','other'), true)) {
                    if ($payment['custom_svg'] === '') $payment['custom_svg'] = self::legacy_payment_svg($legacy_icon);
                    $payment['icon'] = 'custom';
                } elseif (!in_array($legacy_icon, array('card','cash','custom','none'), true)) {
                    $payment['icon'] = 'card';
                }
            }
            unset($payment);
        }
        return $settings;
    }

    private static function upgrade_v133_structures($settings, $from_version = 1) {
        if (!is_array($settings) || (int)$from_version >= 17) return $settings;
        if (!isset($settings['seasons']) || !is_array($settings['seasons'])) return $settings;

        foreach ($settings['seasons'] as &$season) {
            if (!is_array($season)) continue;
            if (!isset($season['school_holidays']) || !is_array($season['school_holidays'])) $season['school_holidays'] = array();
            if (!isset($season['special_periods']) || !is_array($season['special_periods'])) $season['special_periods'] = array();

            $seen = array();
            foreach ($season['school_holidays'] as $holiday) {
                if (!is_array($holiday)) continue;
                $key = (string)($holiday['start'] ?? '') . '|' . (string)($holiday['end'] ?? '');
                if ($key !== '|') $seen[$key] = true;
            }

            foreach ($season['special_periods'] as &$period) {
                if (!is_array($period)) continue;
                if (($period['kind'] ?? '') === 'busy') $period['kind'] = 'other';
                unset($period['affects_domain_rules']);

                // Une période marquée explicitement « vacances scolaires » alimente le
                // référentiel interne des vacances. Les autres périodes restent indépendantes.
                if (($period['kind'] ?? '') === 'school_holiday' && (string)($period['enabled'] ?? '0') === '1') {
                    $start = (string)($period['start'] ?? '');
                    $end = (string)($period['end'] ?? '');
                    $key = $start . '|' . $end;
                    if ($start !== '' && $end !== '' && !isset($seen[$key])) {
                        $season['school_holidays'][] = array(
                            'enabled' => '1',
                            'label' => (string)($period['internal_label'] ?? ''),
                            'start' => $start,
                            'end' => $end,
                        );
                        $seen[$key] = true;
                    }
                }
            }
            unset($period);
        }
        unset($season);
        return $settings;
    }


    private static function upgrade_v141_structures($settings, $from_version = 1) {
        if (!is_array($settings) || (int)$from_version >= 18) return $settings;
        $langs = array('fr','en','de');
        if (isset($settings['alerts']) && is_array($settings['alerts'])) {
            foreach ($settings['alerts'] as &$row) {
                if (!is_array($row)) continue;
                $legacy = isset($row['button_url']) && !is_array($row['button_url']) ? (string)$row['button_url'] : '';
                if (!isset($row['button_url']) || !is_array($row['button_url'])) {
                    $row['button_url'] = array('fr'=>$legacy,'en'=>$legacy,'de'=>$legacy);
                }
                if (!array_key_exists('show_button', $row)) {
                    $has_label = !empty(array_filter(isset($row['button_label']) && is_array($row['button_label']) ? $row['button_label'] : array()));
                    $row['show_button'] = ($has_label && !empty(array_filter($row['button_url']))) ? '1' : '0';
                }
            }
            unset($row);
        }
        if (isset($settings['seasons']) && is_array($settings['seasons'])) {
            foreach ($settings['seasons'] as &$season) {
                if (!is_array($season)) continue;
                foreach (array('special_periods','exceptions') as $list) {
                    if (empty($season[$list]) || !is_array($season[$list])) continue;
                    foreach ($season[$list] as &$row) {
                        if (!is_array($row)) continue;
                        if ($list === 'special_periods') {
                            $legacy = isset($row['button_url']) && !is_array($row['button_url']) ? (string)$row['button_url'] : '';
                            if (!isset($row['button_url']) || !is_array($row['button_url'])) $row['button_url'] = array('fr'=>$legacy,'en'=>$legacy,'de'=>$legacy);
                            if (!array_key_exists('show_button', $row)) {
                                $has_label = !empty(array_filter(isset($row['button_label']) && is_array($row['button_label']) ? $row['button_label'] : array()));
                                $row['show_button'] = ($has_label && !empty(array_filter($row['button_url']))) ? '1' : '0';
                            }
                        }
                        $legacy_popup = isset($row['popup_button_url']) && !is_array($row['popup_button_url']) ? (string)$row['popup_button_url'] : '';
                        if (!isset($row['popup_button_url']) || !is_array($row['popup_button_url'])) $row['popup_button_url'] = array('fr'=>$legacy_popup,'en'=>$legacy_popup,'de'=>$legacy_popup);
                        if (!array_key_exists('popup_show_button', $row)) {
                            $has_label = !empty(array_filter(isset($row['popup_button_label']) && is_array($row['popup_button_label']) ? $row['popup_button_label'] : array()));
                            $row['popup_show_button'] = ($has_label && !empty(array_filter($row['popup_button_url']))) ? '1' : '0';
                        }
                    }
                    unset($row);
                }
            }
            unset($season);
        }
        return $settings;
    }

    private static function upgrade_v158_structures($settings, $from_version = 1) {
        if (!is_array($settings) || (int)$from_version >= 20) return $settings;

        $default_tooltip = array(
            'fr' => 'Cette interruption temporaire permet à nos équipes de prendre leur pause. Le reste du parc reste accessible pendant ce temps.',
            'en' => 'This temporary interruption allows our teams to take their break. The rest of the park remains accessible during this time.',
            'de' => 'Diese vorübergehende Unterbrechung ermöglicht unserem Team eine Pause. Der übrige Park bleibt während dieser Zeit zugänglich.',
        );

        if (isset($settings['seasons']) && is_array($settings['seasons'])) {
            foreach ($settings['seasons'] as &$season) {
                if (!is_array($season) || empty($season['domain_rules']) || !is_array($season['domain_rules'])) continue;
                foreach ($season['domain_rules'] as &$rule) {
                    if (!is_array($rule)) continue;
                    if (!array_key_exists('show_tooltip', $rule)) $rule['show_tooltip'] = '1';
                    if (!isset($rule['tooltip_text']) || !is_array($rule['tooltip_text'])) $rule['tooltip_text'] = $default_tooltip;
                }
                unset($rule);
            }
            unset($season);
        }

        return $settings;
    }

    private static function upgrade_v1510_structures($settings, $from_version = 1) {
        if (!is_array($settings) || (int)$from_version >= 21) return $settings;
        if (!isset($settings['tariffs']) || !is_array($settings['tariffs'])) $settings['tariffs'] = array();
        $tariffs =& $settings['tariffs'];
        if (!isset($tariffs['group_order']) || !is_array($tariffs['group_order'])) {
            $tariffs['group_order'] = array('individual','reduced','groups');
        }
        if (!isset($tariffs['columns']) || !is_array($tariffs['columns'])) $tariffs['columns'] = array();
        $default_labels = array(
            'fr' => 'Tarif',
            'en' => 'Price',
            'de' => 'Preis',
        );
        foreach (array('individual','reduced','groups') as $group) {
            if (!isset($tariffs['columns'][$group]) || !is_array($tariffs['columns'][$group]) || empty($tariffs['columns'][$group])) {
                $tariffs['columns'][$group] = array(array('id'=>'price','label'=>$default_labels));
            }
            if (empty($tariffs[$group]) || !is_array($tariffs[$group])) continue;
            foreach ($tariffs[$group] as &$row) {
                if (!is_array($row)) continue;
                if (!isset($row['cells']) || !is_array($row['cells'])) $row['cells'] = array();
                if (!isset($row['cells']['price']) || !is_array($row['cells']['price'])) {
                    $row['cells']['price'] = array('value'=>(string)($row['price'] ?? ''),'old_value'=>'');
                }
                if (!isset($row['row_type'])) $row['row_type'] = 'standard';
                if (!isset($row['special_badge']) || !is_array($row['special_badge'])) $row['special_badge'] = array('fr'=>'','en'=>'','de'=>'');
                if (!isset($row['valid_from'])) $row['valid_from'] = '';
                if (!isset($row['valid_to'])) $row['valid_to'] = '';
                if (!isset($row['display_from'])) $row['display_from'] = '';
                if (!isset($row['display_to'])) $row['display_to'] = '';
                if (!isset($row['sale_channel'])) $row['sale_channel'] = 'both';
                if (!isset($row['purchase_url']) || !is_array($row['purchase_url'])) $row['purchase_url'] = array('fr'=>'','en'=>'','de'=>'');
                if (!isset($row['show_special_dot'])) $row['show_special_dot'] = '1';
            }
            unset($row);
        }
        return $settings;
    }


    private static function upgrade_v1511_structures($settings, $from_version = 1) {
        if (!is_array($settings) || (int)$from_version >= 22) return $settings;
        if (!isset($settings['tariffs']) || !is_array($settings['tariffs'])) $settings['tariffs'] = array();
        if (!isset($settings['tariffs']['print']) || !is_array($settings['tariffs']['print'])) {
            $settings['tariffs']['print'] = self::tariff_print_defaults();
        } else {
            $settings['tariffs']['print'] = array_replace_recursive(self::tariff_print_defaults(), $settings['tariffs']['print']);
        }
        return $settings;
    }

    private static function upgrade_v160_structures($settings, $from_version = 1) {
        if (!is_array($settings) || (int)$from_version >= 23) return $settings;
        if (!isset($settings['quote_page']) || !is_array($settings['quote_page'])) {
            $settings['quote_page'] = self::quote_page_defaults();
        } else {
            $settings['quote_page'] = array_replace_recursive(self::quote_page_defaults(), $settings['quote_page']);
            if (isset($settings['quote_page']['important_messages']) && !is_array($settings['quote_page']['important_messages'])) $settings['quote_page']['important_messages'] = array();
            if (isset($settings['quote_page']['quick_links']) && !is_array($settings['quote_page']['quick_links'])) $settings['quote_page']['quick_links'] = array();
            if (isset($settings['quote_page']['info_blocks']) && !is_array($settings['quote_page']['info_blocks'])) $settings['quote_page']['info_blocks'] = array();
            if (isset($settings['quote_page']['accordions']) && !is_array($settings['quote_page']['accordions'])) $settings['quote_page']['accordions'] = array();
            $examples = self::quote_page_defaults();
            if (!isset($settings['quote_page']['important_messages']) || !is_array($settings['quote_page']['important_messages']) || empty($settings['quote_page']['important_messages'])) {
                $settings['quote_page']['important_messages'] = $examples['important_messages'];
            } else {
                foreach ($settings['quote_page']['important_messages'] as $i=>$row) {
                    if (!is_array($row)) continue;
                    if (!isset($settings['quote_page']['important_messages'][$i]['position'])) {
                        $settings['quote_page']['important_messages'][$i]['position'] = 'before';
                    }
                }
            }
            if (!isset($settings['quote_page']['quick_links']) || !is_array($settings['quote_page']['quick_links']) || empty($settings['quote_page']['quick_links'])) $settings['quote_page']['quick_links'] = $examples['quick_links'];
            if (empty($settings['quote_page']['accordions'])) $settings['quote_page']['accordions'] = $examples['accordions'];

            // 1.6.7 : retire seulement l'ancien bloc d'exemple "Devis automatique" devenu redondant.
            if (!empty($settings['quote_page']['info_blocks']) && is_array($settings['quote_page']['info_blocks'])) {
                $settings['quote_page']['info_blocks'] = array_values(array_filter($settings['quote_page']['info_blocks'], static function($row) {
                    if (!is_array($row)) return false;
                    $fr = isset($row['title']['fr']) ? trim(strtolower((string)$row['title']['fr'])) : '';
                    return $fr !== 'devis automatique';
                }));
            }

            // Retire l'ancienne introduction uniquement si elle n'a jamais été personnalisée.
            if (($settings['quote_page']['intro']['fr'] ?? '') === 'Préparez votre demande en quelques instants. Les informations utiles sont présentées simplement autour du formulaire.') {
                $settings['quote_page']['intro'] = array('fr'=>'','en'=>'','de'=>'');
            }
        }
        return $settings;
    }

    private static function quote_page_defaults() {
        return array(
            'enabled' => '0',
            'form_shortcode' => '',
            'title' => array('fr'=>'Demande de devis','en'=>'Quote request','de'=>'Angebotsanfrage'),
            'intro' => array('fr'=>'','en'=>'','de'=>''),
            'form_title' => array('fr'=>'Demande de devis groupe','en'=>'Group quote request','de'=>'Gruppenanfrage'),
            'quick_links_title' => array('fr'=>'Préparer votre visite','en'=>'Prepare your visit','de'=>'Besuch vorbereiten'),
            'quick_links_intro' => array(
                'fr'=>'Retrouvez rapidement les informations utiles avant votre demande.',
                'en'=>'Quickly find useful information before submitting your request.',
                'de'=>'Hier finden Sie schnell wichtige Informationen vor Ihrer Anfrage.'
            ),
            'quick_links' => array(
                array(
                    'enabled'=>'1','icon'=>'🕒',
                    'label'=>array('fr'=>'Horaires & Tarifs','en'=>'Opening hours & Prices','de'=>'Öffnungszeiten & Preise'),
                    'url'=>array('fr'=>'https://www.montagnedessinges.com/infos-pratiques/horaires-et-tarifs/','en'=>'','de'=>'')
                ),
                array(
                    'enabled'=>'1','icon'=>'📘',
                    'label'=>array('fr'=>'Guide pédagogique','en'=>'Educational guide','de'=>'Pädagogischer Leitfaden'),
                    'url'=>array('fr'=>'https://www.montagnedessinges.com/groupes-et-cse/groupes-scolaires-et-periscolaires/','en'=>'','de'=>'')
                ),
                array(
                    'enabled'=>'1','icon'=>'ℹ️',
                    'label'=>array('fr'=>'Infos pratiques','en'=>'Practical information','de'=>'Praktische Informationen'),
                    'url'=>array('fr'=>'https://www.montagnedessinges.com/infos-pratiques/','en'=>'','de'=>'')
                ),
            ),
            'important_messages' => array(
                array(
                    'enabled'=>'1','color'=>'#ef7658','position'=>'before',
                    'title'=>array('fr'=>'Tarif groupe','en'=>'Group price','de'=>'Gruppentarif'),
                    'text'=>array(
                        'fr'=>'Les tarifs groupes sont applicables à partir de 20 personnes minimum avec un paiement groupé. Ils concernent les structures scolaires et périscolaires, associations, collectivités, CSE et groupes constitués en visite organisée.',
                        'en'=>'Group prices apply from a minimum of 20 people with one group payment. They apply to school and extracurricular groups, associations, local authorities, works councils and organised groups.',
                        'de'=>'Die Gruppentarife gelten ab mindestens 20 Personen bei gemeinsamer Bezahlung. Sie gelten für Schul- und Freizeitgruppen, Vereine, öffentliche Einrichtungen, Betriebsräte und organisierte Gruppen.'
                    )
                ),
                array(
                    'enabled'=>'1','color'=>'#e4a93b','position'=>'before',
                    'title'=>array('fr'=>'Moins de 20 personnes','en'=>'Fewer than 20 people','de'=>'Weniger als 20 Personen'),
                    'text'=>array(
                        'fr'=>'Les groupes de moins de 20 personnes ne bénéficient normalement pas du tarif groupe. Certaines demandes peuvent toutefois être étudiées au cas par cas par e-mail à info@montagnedessinges.com.',
                        'en'=>'Groups of fewer than 20 people do not normally qualify for the group price. Some requests may nevertheless be considered individually by email at info@montagnedessinges.com.',
                        'de'=>'Gruppen mit weniger als 20 Personen erhalten normalerweise keinen Gruppentarif. Bestimmte Anfragen können jedoch individuell per E-Mail an info@montagnedessinges.com geprüft werden.'
                    )
                ),
                array(
                    'enabled'=>'1','color'=>'#d95f53','position'=>'before',
                    'title'=>array('fr'=>'Réservation obligatoire','en'=>'Booking required','de'=>'Reservierung erforderlich'),
                    'text'=>array(
                        'fr'=>'La réservation est obligatoire pour bénéficier du tarif groupe.',
                        'en'=>'Advance booking is required to benefit from the group price.',
                        'de'=>'Eine vorherige Reservierung ist erforderlich, um den Gruppentarif in Anspruch nehmen zu können.'
                    )
                ),
                array(
                    'enabled'=>'1','color'=>'#2d8a70','position'=>'after',
                    'title'=>array('fr'=>'Devis et validation','en'=>'Quote and confirmation','de'=>'Angebot und Bestätigung'),
                    'text'=>array(
                        'fr'=>'Après l’envoi, vous recevez automatiquement votre devis par e-mail. Votre réservation n’est pas encore confirmée : pour la valider, renvoyez le devis signé avec la mention « Bon pour accord » à info@montagnedessinges.com.',
                        'en'=>'After submitting the form, you automatically receive your quote by email. Your booking is not yet confirmed: to validate it, return the signed quote marked “Approved” to info@montagnedessinges.com.',
                        'de'=>'Nach dem Absenden erhalten Sie Ihr Angebot automatisch per E-Mail. Ihre Reservierung ist damit noch nicht bestätigt: Zur Bestätigung senden Sie das unterschriebene Angebot mit dem Vermerk „Bon pour accord“ an info@montagnedessinges.com zurück.'
                    )
                ),
            ),
            'info_blocks' => array(),
            'accordions' => array(
                array('enabled'=>'1','title'=>array('fr'=>'Paiement et facturation','en'=>'Payment and invoicing','de'=>'Zahlung und Rechnung'),'text'=>array('fr'=>'Ajoutez ici vos informations de paiement et de facturation.','en'=>'Add your payment and invoicing information here.','de'=>'Fügen Sie hier Ihre Zahlungs- und Rechnungsinformationen ein.'),'position'=>'after','show_button'=>'0','button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>'')),
                array('enabled'=>'1','title'=>array('fr'=>'Préparer votre visite','en'=>'Prepare your visit','de'=>'Besuch vorbereiten'),'text'=>array('fr'=>'Ajoutez ici les consignes et informations utiles pour les groupes.','en'=>'Add useful instructions and information for groups here.','de'=>'Fügen Sie hier Hinweise und nützliche Informationen für Gruppen ein.'),'position'=>'after','show_button'=>'0','button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>'')),
            ),
        );
    }

    private static function tariff_print_defaults() {
        return array(
            'enabled'=>'1',
            'pdf_enabled'=>'1',
            'title'=>array('fr'=>'','en'=>'','de'=>''),
            'footer'=>array(
                'fr'=>'Consultez le site internet pour les informations et tarifs les plus récents.',
                'en'=>'Please check the website for the latest information and prices.',
                'de'=>'Aktuelle Informationen und Preise finden Sie auf unserer Website.'
            ),
            'show_generation_date'=>'1',
            'orientation'=>'portrait',
        );
    }

    private static function legacy_payment_svg($icon) {
        if ($icon === 'connect') return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 6.5h4M11 18h2"/><path d="m9.5 12 1.7 1.7 3.5-3.5"/></svg>';
        if ($icon === 'other') return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M8 12h8M12 8v8"/></svg>';
        return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6.5h16v4a2 2 0 0 0 0 4v3H4v-3a2 2 0 0 0 0-4z"/><path d="M9 9.5h6M9 14.5h4"/></svg>';
    }

    public static function all_settings() {
        // Les réglages fusionnés sont immuables pendant une requête front normale.
        // Les mettre en cache ici évite de reconstruire toutes les saisons/tarifs à chaque shortcode ou pop-up.
        static $request_cache = null;
        if (is_array($request_cache)) {
            return $request_cache;
        }

        self::maybe_upgrade();
        $saved = get_option(self::OPTION, array());
        $defaults = self::get();
        if (!is_array($saved) || empty($saved)) {
            return $defaults;
        }
        $out = $defaults;
        foreach (array('schema_version','site_type','timezone','languages') as $key) {
            if (array_key_exists($key, $saved)) $out[$key] = $saved[$key];
        }
        $out['general'] = array_replace_recursive($defaults['general'], isset($saved['general']) && is_array($saved['general']) ? $saved['general'] : array());
        $out['alerts'] = isset($saved['alerts']) && is_array($saved['alerts']) ? $saved['alerts'] : array();
        $out['quote_page'] = array_replace_recursive($defaults['quote_page'], isset($saved['quote_page']) && is_array($saved['quote_page']) ? $saved['quote_page'] : array());
        foreach (array('important_messages','quick_links','info_blocks','accordions') as $quote_list) { if (isset($saved['quote_page'][$quote_list]) && is_array($saved['quote_page'][$quote_list])) $out['quote_page'][$quote_list] = $saved['quote_page'][$quote_list]; }
        $saved_tariffs = isset($saved['tariffs']) && is_array($saved['tariffs']) ? $saved['tariffs'] : array();
        $out['tariffs'] = array_replace($defaults['tariffs'], $saved_tariffs);
        foreach (array('notes','payment_methods') as $key) {
            $out['tariffs'][$key] = array_replace($defaults['tariffs'][$key], isset($saved_tariffs[$key]) && is_array($saved_tariffs[$key]) ? $saved_tariffs[$key] : array());
        }
        if (isset($saved_tariffs['payment_icons']) && is_array($saved_tariffs['payment_icons'])) $out['tariffs']['payment_icons'] = $saved_tariffs['payment_icons'];
        foreach (array('individual','reduced','groups') as $group) {
            if (isset($saved_tariffs[$group]) && is_array($saved_tariffs[$group])) $out['tariffs'][$group] = $saved_tariffs[$group];
        }
        $out['seasons'] = array();
        $seasons = isset($saved['seasons']) && is_array($saved['seasons']) ? $saved['seasons'] : array();
        foreach ($seasons as $year => $season) {
            if (!preg_match('/^20\\d{2}$/', (string) $year) || !is_array($season)) continue;
            $out['seasons'][(string)$year] = array_replace_recursive(self::empty_season((string)$year), $season);
            foreach (array('regular_periods','school_holidays','special_periods','public_holidays','domain_rules','exceptions') as $list) {
                $out['seasons'][(string)$year][$list] = isset($season[$list]) && is_array($season[$list]) ? $season[$list] : array();
            }
        }
        if (empty($out['seasons']) && !empty($defaults['seasons'])) $out['seasons'] = $defaults['seasons'];
        ksort($out['seasons'], SORT_NUMERIC);
        $out['schema_version'] = self::SCHEMA_VERSION;
        $request_cache = $out;
        return $request_cache;
    }

    public static function settings($requested_year = '') {
        // Plusieurs shortcodes peuvent être présents sur une même page. Le résultat
        // fusionné d'une saison est immuable pendant la requête : on le construit une fois.
        static $request_cache = array();
        $cache_key = (string) $requested_year;
        if (isset($request_cache[$cache_key])) {
            return $request_cache[$cache_key];
        }

        $all = self::all_settings();
        $year = self::select_season_year($all, $requested_year);
        $season = $year && isset($all['seasons'][$year]) ? $all['seasons'][$year] : self::empty_season('');
        $settings = $all;
        $settings['general'] = array_merge($all['general'], array(
            'year' => $year,
            'season_start' => isset($season['season_start']) ? $season['season_start'] : '',
            'season_end' => isset($season['season_end']) ? $season['season_end'] : '',
            'published' => isset($season['published']) ? $season['published'] : '0',
        ));
        foreach (array('regular_periods','school_holidays','special_periods','public_holidays','domain_rules','exceptions') as $list) {
            $settings[$list] = isset($season[$list]) && is_array($season[$list]) ? $season[$list] : array();
        }
        $settings['active_season_year'] = $year;
        $request_cache[$cache_key] = $settings;
        return $request_cache[$cache_key];
    }

    public static function select_season_year($settings, $requested_year = '') {
        $seasons = isset($settings['seasons']) && is_array($settings['seasons']) ? $settings['seasons'] : array();
        if ($requested_year !== '' && isset($seasons[(string)$requested_year])) return (string)$requested_year;
        $today = wp_date('Y-m-d', null, new DateTimeZone('Europe/Paris'));
        foreach ($seasons as $year => $season) {
            if ((string)($season['published'] ?? '0') !== '1') continue;
            $start = (string)($season['season_start'] ?? ''); $end = (string)($season['season_end'] ?? '');
            if ($start && $end && $today >= $start && $today <= $end) return (string)$year;
        }
        $current_year = substr($today, 0, 4);
        if (isset($seasons[$current_year]) && (string)($seasons[$current_year]['published'] ?? '0') === '1') return $current_year;
        foreach ($seasons as $year => $season) if ((string)($season['published'] ?? '0') === '1') return (string)$year;
        foreach ($seasons as $year => $season) return (string)$year;
        return '';
    }

    public static function empty_season($year) {
        return array(
            'year' => (string)$year,
            'published' => '0',
            'season_start' => '',
            'season_end' => '',
            'regular_periods' => array(),
            'school_holidays' => array(),
            'special_periods' => array(),
            'public_holidays' => array(),
            'domain_rules' => array(),
            'exceptions' => array(),
        );
    }


    private static function upgrade_v171_structures($settings, $from_version) {
        if (!is_array($settings)) return $settings;

        // Les horaires exceptionnels acceptent désormais un second créneau facultatif
        // et le pop-up réutilise les champs publics de l'exception.
        if (!empty($settings['seasons']) && is_array($settings['seasons'])) {
            foreach ($settings['seasons'] as $year => &$season) {
                if (!is_array($season)) continue;
                if (!empty($season['exceptions']) && is_array($season['exceptions'])) {
                    foreach ($season['exceptions'] as &$row) {
                        if (!is_array($row)) continue;
                        if (!array_key_exists('open2', $row)) $row['open2'] = '';
                        if (!array_key_exists('close2', $row)) $row['close2'] = '';
                        if (!array_key_exists('popup_show_dates', $row)) $row['popup_show_dates'] = '1';
                        if (!array_key_exists('popup_show_hours', $row)) $row['popup_show_hours'] = '1';
                    }
                    unset($row);
                }
            }
            unset($season);
        }

        // Nettoyage automatique uniquement de la préconfiguration Forêt des Singes 1.7.0.
        // Si l'utilisateur a déjà personnalisé les lignes, elles ne sont pas écrasées.
        if (($settings['site_type'] ?? '') === 'fds' && isset($settings['seasons']['2026'])) {
            $season =& $settings['seasons']['2026'];
            $labels = array();
            foreach (($season['regular_periods'] ?? array()) as $row) {
                if (is_array($row)) $labels[] = (string)($row['label'] ?? '');
            }
            $signature = array('21 mars au 3 avril','4 au 6 avril','7 au 30 avril','1 au 3 mai','4 au 7 mai','8 au 10 mai','11 au 13 mai','14 mai','15 au 22 mai','23 au 25 mai','26 mai au 30 juin','1 au 5 juillet','6 juillet','7 juillet au 31 août','1 au 13 septembre','14 au 25 septembre','26 et 27 septembre','28 au 30 septembre','1 et 2 octobre','3 et 4 octobre','5 au 9 octobre','10 et 11 octobre','12 au 16 octobre','17 et 18 octobre','19 au 23 octobre','24 et 25 octobre','26 octobre au 1 novembre','7 et 8 novembre','11 novembre');
            if (count($labels) === count($signature) && !array_diff($signature, $labels)) {
                $season['regular_periods'] = self::fds_2026_regular_periods();
            }
        }
        return $settings;
    }


    private static function upgrade_v172_structures($settings, $from_version) {
        if (!is_array($settings)) return $settings;

        // Migration volontairement non destructive :
        // aucun horaire, tarif, événement, exception ou période déjà enregistré n'est remplacé.
        if (!isset($settings['general']) || !is_array($settings['general'])) {
            $settings['general'] = array();
        }

        // Normalisation uniquement de la casse des noms connus.
        $park = isset($settings['general']['park_name']) && is_array($settings['general']['park_name'])
            ? $settings['general']['park_name'] : array();
        $fr = isset($park['fr']) ? trim((string)$park['fr']) : '';
        if ($fr !== '') {
            $plain = function_exists('remove_accents') ? strtolower(remove_accents($fr)) : strtolower($fr);
            if (strpos($plain, 'foret des singes') !== false) $settings['general']['park_name']['fr'] = 'La Forêt des Singes';
            if (strpos($plain, 'montagne des singes') !== false) $settings['general']['park_name']['fr'] = 'La Montagne des Singes';
        }
        return $settings;
    }

    public static function get() {
        $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
        $is_mds = strpos($host, 'montagnedessinges') !== false;
        $is_fds = strpos($host, 'foretdessinges') !== false || strpos($host, 'foret-des-singes') !== false;
        $settings = array(
            'schema_version' => self::SCHEMA_VERSION,
            'site_type' => $is_mds ? 'mds' : ($is_fds ? 'fds' : 'custom'),
            'timezone' => 'Europe/Paris',
            'languages' => array('fr','en','de'),
            'general' => array(
                'park_name' => array('fr'=>$is_fds?'La Forêt des Singes':($is_mds?'La Montagne des Singes':''),'en'=>'','de'=>''),
                'last_entry_minutes' => '45',
                'tickets_url' => array('fr'=>$is_mds?'https://ticket.montagnedessinges.com/fr/':($is_fds?'https://ticket-la-foret-des-singes.com/fr/':''),'en'=>$is_mds?'https://ticket.montagnedessinges.com/en/':($is_fds?'https://ticket-la-foret-des-singes.com/gb/':''),'de'=>$is_mds?'https://ticket.montagnedessinges.com/de/':''),
                'groups_url' => array('fr'=>$is_mds?'https://www.montagnedessinges.com/groupes-et-cse/devis-en-ligne/':($is_fds?'https://www.la-foret-des-singes.com/groupes-et-cse/devis-en-ligne/':''),'en'=>$is_fds?'https://www.la-foret-des-singes.com/en/groups-and-cse/online-quote/':'','de'=>''),
                'groups_email' => $is_mds ? 'info@montagnedessinges.com' : ($is_fds ? 'contact@la-foret-des-singes.com' : ''),
                'groups_closed_note' => array('fr'=>'Même lorsque le parc est fermé au public, une demande de groupe peut être envoyée par e-mail.','en'=>'Even when the park is closed to the public, group visit requests can still be sent by email.','de'=>'Auch wenn der Park für die Öffentlichkeit geschlossen ist, können Gruppenanfragen weiterhin per E-Mail gesendet werden.'),
                'groups_booking_note' => array('fr'=>$is_mds?'Réservation obligatoire pour bénéficier des tarifs groupes. Commencez par effectuer une demande de devis en ligne. Une fois le devis reçu, renvoyez-le avec la mention « Bon pour accord » pour confirmer votre réservation.':($is_fds?'Réservation recommandée pour les groupes, mais non obligatoire. Pour préparer votre venue, vous pouvez contacter directement l’équipe du parc.':''),'en'=>$is_fds?'Group reservations are recommended but not compulsory. You can contact the park team directly to prepare your visit.':'','de'=>''),
                'groups_button_label' => array('fr'=>$is_mds?'Faire une demande de devis':($is_fds?'Contacter le parc':''),'en'=>$is_fds?'Contact the park':'','de'=>''),
                'primary_color'=>'#006757','secondary_color'=>'#31ad81','accent_color'=>'#ef7b5b','highlight_color'=>'#e7c55b',
                'body_text_color'=>'','heading_text_color'=>'','border_color'=>'','block_spacing'=>'8','block_border_enabled'=>'0',
                // Typographie : valeurs vides = tailles historiques / thème du site.
                'font_profile'=>'inherit','typography_preset'=>'standard','calendar_detail_preset'=>'large',
                'font_body_size'=>'','font_heading_size'=>'','font_kicker_size'=>'','font_button_size'=>'',
                'font_today_title_size'=>'','font_today_status_size'=>'','font_today_detail_size'=>'',
                'font_calendar_title_size'=>'','font_calendar_month_size'=>'','font_calendar_summary_size'=>'','font_calendar_weekday_size'=>'','font_calendar_day_size'=>'','font_calendar_detail_title_size'=>'','font_calendar_detail_hours_size'=>'','font_calendar_detail_last_size'=>'','font_calendar_legend_size'=>'',
                'font_tariff_title_size'=>'','font_tariff_tab_size'=>'','font_tariff_label_size'=>'','font_tariff_detail_size'=>'','font_tariff_note_size'=>'','font_tariff_price_size'=>'',
                'font_payment_title_size'=>'','font_payment_item_size'=>'','font_groups_note_size'=>'','font_alert_title_size'=>'','font_alert_text_size'=>'','font_alert_button_size'=>'',
                'today_title_color'=>'','today_title_bg_color'=>'#ffffff','today_title_bg_transparent'=>'1','today_status_color'=>'#16843d','today_closed_color'=>'#9b2c2c','today_detail_color'=>'',
                'calendar_title_color'=>'','calendar_title_bg_color'=>'#ffffff','calendar_title_bg_transparent'=>'1','calendar_day_bg_color'=>'#ffffff','calendar_day_bg_transparent'=>'0','calendar_mobile_size'=>'medium','calendar_nav_bg_color'=>'#006757','calendar_nav_text_color'=>'#ffffff','calendar_nav_active_bg_color'=>'#e7c55b','calendar_nav_active_text_color'=>'#27342f','calendar_weekday_color'=>'','calendar_detail_text_color'=>'','calendar_detail_border_color'=>'','calendar_closed_bg_color'=>'#e3e5e4','calendar_closed_text_color'=>'#616765','calendar_selected_color'=>'#006757','period_legend_label'=>array('fr'=>'Période spécifique','en'=>'Special period','de'=>'Besonderer Zeitraum'),'event_legend_label'=>array('fr'=>'Événement','en'=>'Event','de'=>'Veranstaltung'),'show_public_holidays'=>'0','holiday_border_color'=>'#e7c55b','holiday_border_width'=>'3','holiday_message'=>array('fr'=>'Jour férié','en'=>'Public holiday','de'=>'Feiertag'),
                'tariff_kicker_color'=>'','tariff_title_color'=>'','tariff_title_bg_color'=>'#ffffff','tariff_title_bg_transparent'=>'1','payment_title_color'=>'','payment_title_bg_color'=>'#ffffff','payment_title_bg_transparent'=>'1','payment_item_bg_color'=>'#006757','payment_item_text_color'=>'#ffffff','payment_icon_color'=>'#ffffff','payment_border_color'=>'','payment_border_enabled'=>'0',
                'tab_bg_color'=>'#006757','tab_text_color'=>'#ffffff','tab_active_bg_color'=>'#e7c55b','tab_active_text_color'=>'#27342f','panel_text_color'=>'','panel_border_color'=>'','panel_border_enabled'=>'0','panel_bg_color'=>'#ffffff','panel_bg_transparent'=>'1','price_color'=>'','tariff_note_text_color'=>'','tariff_note_border_color'=>'','groups_note_text_color'=>'','groups_note_border_color'=>'',
                'button_bg_color'=>'#006757','button_text_color'=>'#ffffff','primary_button_bg_color'=>'#ef7b5b','primary_button_text_color'=>'#ffffff',
                'alert_bg_color'=>'#006757','alert_title_color'=>'#ffffff','alert_text_color'=>'#ffffff','alert_border_color'=>'#ef7b5b','alert_button_bg_color'=>'#ef7b5b','alert_button_text_color'=>'#ffffff','alert_button_border_color'=>'#ef7b5b','alert_close_bg_color'=>'#ffffff','alert_close_text_color'=>'#222222','alert_overlay_color'=>'#000000','alert_overlay_opacity'=>'68','alert_border_width'=>'3','alert_radius'=>'16','alert_shadow'=>'1','alert_reappear_hours'=>'1',
            ),
            'seasons' => array(),
            'alerts' => array(),
            'quote_page' => self::quote_page_defaults(),
            'tariffs' => array('group_order'=>array('individual','reduced','groups'),'columns'=>array('individual'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),'reduced'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),'groups'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis')))),'individual'=>array(),'reduced'=>array(),'groups'=>array(),'notes'=>array('fr'=>'','en'=>'','de'=>''),'payment_methods'=>array('fr'=>'','en'=>'','de'=>''),'payment_icons'=>array(),'payment_styles'=>array(),'payment_items'=>array(),'print'=>self::tariff_print_defaults()),
        );
        if ($is_mds) {
            $season = self::empty_season('2026');
            $season['published']='1'; $season['season_start']='2026-03-21'; $season['season_end']='2026-11-11';
            $season['regular_periods']=array(
                self::period('21 mars au 30 avril','2026-03-21','2026-04-30','10:00','17:30','#9AAA8B'),
                self::period('Mai à août','2026-05-01','2026-08-31','10:00','18:00','#E7D28A'),
                self::period('Septembre','2026-09-01','2026-09-30','10:00','17:30','#9AAA8B'),
                self::period('Octobre et novembre','2026-10-01','2026-11-11','10:00','17:00','#8A8463'),
            );
            $season['exceptions']=array(self::closure('Fermeture de novembre','2026-11-02','2026-11-06'),self::closure('Fermeture de novembre','2026-11-09','2026-11-10'));
            $season['domain_rules']=array(array('enabled'=>'0','label'=>'Interruption de midi — à activer après saisie des vacances scolaires','start'=>'2026-03-21','end'=>'2026-11-11','weekdays'=>array('1','2','3','4','5'),'pause_start'=>'12:00','resume'=>'13:00','last_entry'=>'11:30','exclude_weekends'=>'1','exclude_school_holidays'=>'1','exclude_public_holidays'=>'1','show_tooltip'=>'1','tooltip_text'=>array('fr'=>'Cette interruption temporaire permet à nos équipes de prendre leur pause. Le reste du parc reste accessible pendant ce temps.','en'=>'This temporary interruption allows our teams to take their break. The rest of the park remains accessible during this time.','de'=>'Diese vorübergehende Unterbrechung ermöglicht unserem Team eine Pause. Der übrige Park bleibt während dieser Zeit zugänglich.'),'info'=>array('fr'=>'Information : l’accès au domaine des singes est temporairement interrompu de 12 h à 13 h, avec une dernière entrée à 11 h 30. Le parc reste ouvert.','en'=>'Information: access to the monkey area is temporarily suspended from 12 noon to 1 pm, with last admission at 11:30 am. The park remains open.','de'=>'Information: Der Zugang zum Affenbereich ist von 12 bis 13 Uhr vorübergehend unterbrochen. Letzter Einlass ist um 11:30 Uhr. Der Park bleibt geöffnet.')));
            $settings['seasons']['2026']=$season;
            $settings['tariffs']=self::mds_tariffs();
        } elseif ($is_fds) {
            $season = self::empty_season('2026');
            $season['published']='1';
            $season['season_start']='2026-03-21';
            $season['season_end']='2026-11-11';

            /*
             * Horaires 2026 préremplis d'après le calendrier officiel
             * publié par La Forêt des Singes.
             * Les périodes avec coupure 12h-13h sont complétées
             * par les règles d'accès limité ci-dessous.
             */
            $season['regular_periods']=self::fds_2026_regular_periods();

            $season['domain_rules']=array();

            /*
             * Les fermetures barrées du calendrier officiel 2026.
             */
            $season['exceptions']=array(
                array('enabled'=>'1','type'=>'hours','label'=>'Fortes chaleurs','start'=>'2026-08-21','end'=>'2026-08-31','open'=>'09:00','close'=>'17:30','open2'=>'','close2'=>'','last_entry_minutes'=>'45','priority'=>'200','apply_domain_rules'=>'0','show_public_marker'=>'1','context'=>array('fr'=>'Horaires adaptés en raison des fortes chaleurs','en'=>'Adjusted opening hours due to hot weather','de'=>'Angepasste Öffnungszeiten aufgrund hoher Temperaturen'),'title'=>array('fr'=>'Horaires exceptionnels','en'=>'Exceptional opening hours','de'=>'Außergewöhnliche Öffnungszeiten'),'message'=>array('fr'=>'','en'=>'','de'=>''),'show_popup'=>'0','popup_show_dates'=>'1','popup_show_hours'=>'1'),
                self::closure('Fermeture','2026-11-02','2026-11-06'),
                self::closure('Fermeture','2026-11-09','2026-11-10')
            );


            // Vacances scolaires Zone C : périodes 2026 officiellement confirmées pour l'année scolaire 2025-2026.
            $season['special_periods']=array(
                array('enabled'=>'1','kind'=>'school_holiday','internal_label'=>'Vacances d’hiver — Zone C','title'=>array('fr'=>'Vacances scolaires — Zone C','en'=>'School holidays — Zone C','de'=>'Schulferien — Zone C'),'start'=>'2026-02-21','end'=>'2026-03-08','color'=>'#7b61a8','icon'=>'star','display_mode'=>'long','message'=>array('fr'=>'Vacances d’hiver','en'=>'Winter holidays','de'=>'Winterferien'),'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>'1','skip_domain_rules'=>'1','show_popup'=>'0'),
                array('enabled'=>'1','kind'=>'school_holiday','internal_label'=>'Vacances de printemps — Zone C','title'=>array('fr'=>'Vacances scolaires — Zone C','en'=>'School holidays — Zone C','de'=>'Schulferien — Zone C'),'start'=>'2026-04-18','end'=>'2026-05-03','color'=>'#7b61a8','icon'=>'star','display_mode'=>'long','message'=>array('fr'=>'Vacances de printemps','en'=>'Spring holidays','de'=>'Frühlingsferien'),'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>'1','skip_domain_rules'=>'1','show_popup'=>'0'),
                array('enabled'=>'1','kind'=>'school_holiday','internal_label'=>'Vacances d’été','title'=>array('fr'=>'Vacances scolaires','en'=>'School holidays','de'=>'Schulferien'),'start'=>'2026-07-04','end'=>'2026-08-31','color'=>'#7b61a8','icon'=>'star','display_mode'=>'long','message'=>array('fr'=>'Vacances d’été','en'=>'Summer holidays','de'=>'Sommerferien'),'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>'1','skip_domain_rules'=>'1','show_popup'=>'0'),
                array('enabled'=>'1','kind'=>'event','internal_label'=>'Concours photo 2026','title'=>array('fr'=>'Concours photo','en'=>'Photo contest','de'=>'Fotowettbewerb'),'start'=>'2026-07-01','end'=>'2026-09-30','color'=>'#e7c55b','icon'=>'star','display_mode'=>'long','message'=>array('fr'=>'Participez à notre concours photo.','en'=>'Take part in our photo contest.','de'=>'Nehmen Sie an unserem Fotowettbewerb teil.'),'button_label'=>array('fr'=>'Participer','en'=>'Take part','de'=>'Teilnehmen'),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>'1','skip_domain_rules'=>'0','show_popup'=>'0')
            );
            $season['school_holidays']=array(
                array('enabled'=>'1','label'=>'Vacances d’hiver — Zone C','start'=>'2026-02-21','end'=>'2026-03-08'),
                array('enabled'=>'1','label'=>'Vacances de printemps — Zone C','start'=>'2026-04-18','end'=>'2026-05-03'),
                array('enabled'=>'1','label'=>'Vacances d’été','start'=>'2026-07-04','end'=>'2026-08-31')
            );
            $season['public_holidays']=array(
                array('enabled'=>'1','label'=>'Jour de l’An','date'=>'2026-01-01'), array('enabled'=>'1','label'=>'Lundi de Pâques','date'=>'2026-04-06'),
                array('enabled'=>'1','label'=>'Fête du Travail','date'=>'2026-05-01'), array('enabled'=>'1','label'=>'Victoire 1945','date'=>'2026-05-08'),
                array('enabled'=>'1','label'=>'Ascension','date'=>'2026-05-14'), array('enabled'=>'1','label'=>'Lundi de Pentecôte','date'=>'2026-05-25'),
                array('enabled'=>'1','label'=>'Fête nationale','date'=>'2026-07-14'), array('enabled'=>'1','label'=>'Assomption','date'=>'2026-08-15'),
                array('enabled'=>'1','label'=>'Toussaint','date'=>'2026-11-01'), array('enabled'=>'1','label'=>'Armistice','date'=>'2026-11-11'),
                array('enabled'=>'1','label'=>'Noël','date'=>'2026-12-25')
            );
            $settings['general']['show_public_holidays']='0';

            $settings['seasons']['2026']=$season;
            $settings['tariffs']=self::fds_tariffs();

            /*
             * Le module Devis groupe reste disponible mais n'est pas préconfiguré
             * comme parcours principal sur l'édition Forêt des Singes.
             */
            $settings['quote_page']['enabled']='0';
        }
        return $settings;
    }

    private static function period($label, $start, $end, $open, $close, $color) {
        return array(
            'enabled' => '1', 'label' => $label, 'start' => $start, 'end' => $end,
            'weekdays' => array('1', '2', '3', '4', '5', '6', '7'),
            'open' => $open, 'close' => $close, 'open2' => '', 'close2' => '', 'last_entry_minutes' => '', 'color' => $color,
        );
    }

    private static function closure($label, $start, $end) {
        return array(
            'enabled' => '1', 'type' => 'closed', 'label' => $label, 'start' => $start, 'end' => $end,
            'open' => '', 'close' => '', 'last_entry_minutes' => '', 'priority' => '100',
            'title' => array('fr' => '', 'en' => '', 'de' => ''),
            'message' => array('fr' => '', 'en' => '', 'de' => ''),
        );
    }

    private static function tariff_row($fr, $en, $de, $detail_fr, $detail_en, $detail_de, $price, $note_fr = '', $note_en = '', $note_de = '') {
        return array(
            'enabled' => '1',
            'label' => array('fr' => $fr, 'en' => $en, 'de' => $de),
            'detail' => array('fr' => $detail_fr, 'en' => $detail_en, 'de' => $detail_de),
            'subtitle' => array('fr' => $detail_fr, 'en' => $detail_en, 'de' => $detail_de),
            'note' => array('fr' => $note_fr, 'en' => $note_en, 'de' => $note_de),
            'price' => $price,
            'cells' => array('price'=>array('value'=>$price,'old_value'=>'')),
            'row_type' => 'standard',
            'special_badge' => array('fr'=>'','en'=>'','de'=>''),
            'valid_from' => '', 'valid_to' => '', 'display_from' => '', 'display_to' => '',
            'sale_channel' => 'both', 'purchase_url' => array('fr'=>'','en'=>'','de'=>''), 'show_special_dot' => '1',
            'label_color' => '',
            'detail_color' => '',
            'subtitle_color' => '',
            'note_color' => '',
            'price_color' => '',
            'row_bg_color' => '#ffffff',
            'row_bg_transparent' => '1',
            'row_border_color' => '',
        );
    }


    private static function fds_2026_regular_periods() {
        $rows = array();

        $add = function($label,$start,$end,$open,$close,$open2,$close2,$color,$weekdays) use (&$rows) {
            $row = self::period($label,$start,$end,$open,$close,$color);
            $row['open2'] = $open2;
            $row['close2'] = $close2;
            $row['weekdays'] = $weekdays;
            $rows[] = $row;
        };

        // Configuration volontairement compacte : une même période peut cibler
        // seulement certains jours de la semaine.
        $all = array('1','2','3','4','5','6','7');
        $week = array('1','2','3','4','5');
        $weekend = array('6','7');

        $add('21 mars au 30 avril','2026-03-21','2026-04-30','10:00','12:00','13:00','17:30','#E7E39A',$all);
        $add('1 mai au 30 juin','2026-05-01','2026-06-30','10:00','12:00','13:00','18:00','#9BBB61',$all);
        $add('1 juillet au 31 août','2026-07-01','2026-08-31','09:30','18:00','','','#F9B500',$all);
        $add('1 au 13 septembre','2026-09-01','2026-09-13','10:00','12:00','13:00','17:30','#E7E39A',$all);
        $add('14 au 30 septembre — semaine','2026-09-14','2026-09-30','13:00','17:30','','','#B5AE76',$week);
        $add('14 au 30 septembre — week-end','2026-09-14','2026-09-30','10:00','12:00','13:00','17:30','#E7E39A',$weekend);
        $add('1 au 23 octobre — semaine','2026-10-01','2026-10-23','13:00','17:00','','','#E5CF91',$week);
        $add('1 au 23 octobre — week-end','2026-10-01','2026-10-23','10:00','12:00','13:00','17:00','#8E8663',$weekend);
        $add('24 octobre au 1 novembre','2026-10-24','2026-11-01','10:00','12:00','13:00','17:00','#8E8663',$all);
        $add('7 et 8 novembre','2026-11-07','2026-11-08','10:00','12:00','13:00','17:00','#8E8663',$weekend);
        $add('11 novembre','2026-11-11','2026-11-11','10:00','12:00','13:00','17:00','#8E8663',$all);

        return $rows;
    }

    private static function fds_tariffs() {
        return array(
            'group_order' => array('individual','reduced','groups'),
            'columns' => array(
                'individual'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),
                'reduced'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),
                'groups'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),
            ),
            'individual' => array(
                self::tariff_row('Adulte', 'Adult', 'Erwachsene', 'À partir de 15 ans', 'From age 15', 'Ab 15 Jahren', '12 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'De 5 à 14 ans', 'Aged 5 to 14', 'Von 5 bis 14 Jahren', '8 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'Moins de 5 ans', 'Under 5', 'Unter 5 Jahren', 'Gratuit'),
                self::tariff_row('Personne en situation de handicap', 'Visitor with a disability', 'Person mit Behinderung', 'Adulte / enfant', 'Adult / child', 'Erwachsene / Kinder', '6 €')
            ),
            'reduced' => array(
                self::tariff_row('Adulte', 'Adult', 'Erwachsene', '', '', '', '10,50 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'De 5 à 14 ans', 'Aged 5 to 14', 'Von 5 bis 14 Jahren', '7 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'Moins de 5 ans', 'Under 5', 'Unter 5 Jahren', 'Gratuit'),
                self::tariff_row('Personne en situation de handicap', 'Visitor with a disability', 'Person mit Behinderung', 'Adulte / enfant', 'Adult / child', 'Erwachsene / Kinder', '6 €')
            ),
            'groups' => array(
                self::tariff_row('Adulte', 'Adult', 'Erwachsene', '', '', '', '9,50 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'De 3 à 18 ans', 'Aged 3 to 18', 'Von 3 bis 18 Jahren', '7 €'),
                self::tariff_row('Scolaire / extrascolaire', 'School / extracurricular group', 'Schulische / außerschulische Gruppe', 'De 3 à 18 ans', 'Aged 3 to 18', 'Von 3 bis 18 Jahren', '7 €'),
                self::tariff_row('Personne en situation de handicap', 'Visitor with a disability', 'Person mit Behinderung', 'Adultes / enfants / accompagnateurs', 'Adults / children / companions', 'Erwachsene / Kinder / Begleitpersonen', '6 €')
            ),
            'notes' => array(
                'fr' => 'Tarifs réduits sur présentation d’un justificatif : étudiants post-bac, personnes en situation de handicap, cartes Cezam et PassTime. Tarifs groupes à partir de 20 personnes. Réservation recommandée pour les groupes, mais non obligatoire.',
                'en' => 'Reduced rates upon presentation of valid proof: post-secondary students, visitors with disabilities, Cezam and PassTime cardholders. Group rates apply from 20 people. Group reservations are recommended but not compulsory.',
                'de' => 'Ermäßigte Tarife gegen Vorlage eines gültigen Nachweises: Studierende nach dem Abitur, Menschen mit Behinderung sowie Inhaber einer Cezam- oder PassTime-Karte. Gruppentarife gelten ab 20 Personen. Eine Reservierung für Gruppen wird empfohlen, ist aber nicht verpflichtend.'
            ),
            'payment_methods' => array('fr'=>'','en'=>'','de'=>''),
            'payment_icons' => array('card','cash','holiday_voucher'),
            'payment_styles' => array(),
            'payment_items' => array(
                array('enabled'=>'1','icon'=>'card','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Carte bancaire','en'=>'Bank card','de'=>'Bankkarte'),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'),
                array('enabled'=>'1','icon'=>'cash','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Espèces','en'=>'Cash','de'=>'Bargeld'),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'),
                array('enabled'=>'1','icon'=>'holiday_voucher','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Chèques-Vacances ANCV','en'=>'ANCV holiday vouchers','de'=>'ANCV-Ferienschecks'),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0')
            ),
            'print'=>self::tariff_print_defaults(),
        );
    }


    private static function mds_tariffs() {
        return array(
            'group_order' => array('individual','reduced','groups'),
            'columns' => array(
                'individual'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),
                'reduced'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),
                'groups'=>array(array('id'=>'price','label'=>array('fr'=>'Tarif','en'=>'Price','de'=>'Preis'))),
            ),
            'individual' => array(
                self::tariff_row('Adulte', 'Adult', 'Erwachsene', '', '', '', '12 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'De 5 à 14 ans', 'Aged 5 to 14', 'Von 5 bis 14 Jahren', '8 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'Moins de 5 ans', 'Under 5', 'Unter 5 Jahren', 'Gratuit'),
            ),
            'reduced' => array(
                self::tariff_row('Adulte', 'Adult', 'Erwachsene', '', '', '', '10,50 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'De 5 à 14 ans', 'Aged 5 to 14', 'Von 5 bis 14 Jahren', '7 €'),
                self::tariff_row('Personne en situation de handicap', 'Visitor with a disability', 'Person mit Behinderung', 'Tarif réduit sur présentation d’un justificatif.', 'Reduced rate on presentation of valid proof.', 'Ermäßigter Tarif gegen Vorlage eines gültigen Nachweises.', '6 €', 'L’accompagnateur bénéficie du tarif réduit si le justificatif mentionne un besoin d’accompagnement.', 'The companion also receives the reduced rate if the supporting document states that assistance is required.', 'Auch die Begleitperson erhält den ermäßigten Tarif, wenn im Nachweis ein Begleitbedarf angegeben ist.'),
            ),
            'groups' => array(
                self::tariff_row('Adulte', 'Adult', 'Erwachsene', '', '', '', '8,50 €'),
                self::tariff_row('Enfant', 'Child', 'Kind', 'De 3 à 18 ans', 'Aged 3 to 18', 'Von 3 bis 18 Jahren', '6 €'),
                self::tariff_row('Scolaire / extrascolaire', 'School / extracurricular group', 'Schulische / außerschulische Gruppe', 'De 3 à 18 ans', 'Aged 3 to 18', 'Von 3 bis 18 Jahren', '6 €'),
                self::tariff_row('Personne en situation de handicap et accompagnateur', 'Visitor with a disability and companion', 'Person mit Behinderung und Begleitperson', '', '', '', '6 €'),
            ),
            'notes' => array(
                'fr' => 'Tarifs réduits sur présentation d’un justificatif valable : étudiants post-bac, titulaires d’une carte Cezam, du Guide du Routard, d’un Passeport Gîtes Bas-Rhin ou Haut-Rhin, ou d’un billet de la navette Haut-Koenigsbourg.',
                'en' => 'Reduced rates are available upon presentation of valid proof for post-secondary students, Cezam cardholders, Guide du Routard holders, Bas-Rhin or Haut-Rhin Gîtes passport holders, or holders of a Haut-Koenigsbourg shuttle ticket.',
                'de' => 'Ermäßigte Tarife gelten gegen Vorlage eines gültigen Nachweises für Studierende nach dem Abitur, Inhaber einer Cezam-Karte, des Guide du Routard, eines Gîtes-Passes Bas-Rhin oder Haut-Rhin oder eines Tickets für den Haut-Koenigsbourg-Shuttle.',
            ),
            'payment_methods' => array(
                'fr' => '',
                'en' => '',
                'de' => '',
            ),
            'payment_icons' => array('card', 'cash', 'holiday_voucher', 'connect'),
            'payment_styles' => array(),
            'payment_items' => array(
                array('enabled'=>'1','icon'=>'card','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Carte bancaire','en'=>'Bank card','de'=>'Bankkarte'),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'),
                array('enabled'=>'1','icon'=>'cash','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Espèces','en'=>'Cash','de'=>'Bargeld'),'visible'=>array('fr'=>'1','en'=>'1','de'=>'1'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'),
                array('enabled'=>'1','icon'=>'holiday_voucher','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Chèques-Vacances papier','en'=>'','de'=>''),'visible'=>array('fr'=>'1','en'=>'0','de'=>'0'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'),
                array('enabled'=>'1','icon'=>'connect','custom_svg'=>'','custom_svg_force_color'=>'1','label'=>array('fr'=>'Chèques-Vacances Connect','en'=>'','de'=>''),'visible'=>array('fr'=>'1','en'=>'0','de'=>'0'),'bg_color'=>'#006757','bg_transparent'=>'0','icon_color'=>'#ffffff','text_color'=>'#ffffff','border_color'=>'#006757','border_enabled'=>'0'),
            ),
        );
    }
}
