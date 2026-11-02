<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Projection en lecture seule du calendrier canonique vers Google Business Profile.
 * Aucune donnée d'horaire n'est recréée ou enregistrée ici.
 */
final class Parcs_HT_GBP_Plan {
    const HORIZON_DAYS = 35;
    private static $days = array(1=>'MONDAY',2=>'TUESDAY',3=>'WEDNESDAY',4=>'THURSDAY',5=>'FRIDAY',6=>'SATURDAY',7=>'SUNDAY');

    private static function season_for_date($all, $date) {
        foreach ((array)($all['seasons'] ?? array()) as $season) {
            if (!is_array($season)) continue;
            $start = (string)($season['season_start'] ?? '');
            $end = (string)($season['season_end'] ?? '');
            if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $start) || !preg_match('/^20\d{2}-\d{2}-\d{2}$/', $end)) continue;
            if ($date >= $start && $date <= $end) return $season;
        }
        return null;
    }

    private static function slots($all, $date, $tz) {
        $season = self::season_for_date($all, $date);
        if ($season === null) return array();
        $day = Parcs_HT_Schedule::resolve_day($season, (array)($all['general'] ?? array()), $date, $tz);
        return empty($day['open']) ? array() : (array)($day['slots'] ?? array());
    }

    private static function time($value) {
        if (!is_string($value) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value, $m)) {
            return new WP_Error('gbp_invalid_time', 'Horaire incompatible avec Google Business Profile : '.(string)$value);
        }
        return array('hours'=>(int)$m[1], 'minutes'=>(int)$m[2]);
    }

    private static function normalized_slots($slots) {
        $out = array();
        foreach ((array)$slots as $slot) {
            $start = isset($slot['open']) ? (string)$slot['open'] : '';
            $end = isset($slot['close']) ? (string)$slot['close'] : '';
            $a = self::time($start); $b = self::time($end);
            if (is_wp_error($a)) return $a;
            if (is_wp_error($b)) return $b;
            if ($start >= $end) return new WP_Error('gbp_overnight', 'Une plage traverse minuit ou ses heures sont inversées : '.$start.'–'.$end.'.');
            $out[] = array('open'=>$start,'close'=>$end);
        }
        usort($out, static function ($a,$b) { return strcmp($a['open'], $b['open']); });
        for ($i=1; $i<count($out); $i++) {
            if ($out[$i]['open'] < $out[$i-1]['close']) return new WP_Error('gbp_overlap', 'Deux plages horaires se chevauchent.');
        }
        return $out;
    }

    private static function date_object($date) {
        return array('year'=>(int)substr($date,0,4),'month'=>(int)substr($date,5,2),'day'=>(int)substr($date,8,2));
    }

    public static function build($all, $today) {
        if (!is_array($all) || empty($all['seasons']) || !is_array($all['seasons'])) {
            return new WP_Error('gbp_no_calendar','Aucun calendrier de saisons n’est disponible.');
        }
        $tz = Parcs_HT_Schedule::timezone($all);
        try {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $today, new DateTimeZone($tz));
        } catch (Exception $e) { $date = false; }
        if (!$date || $date->format('Y-m-d') !== $today) return new WP_Error('gbp_date','Date de synchronisation invalide.');
        $has_valid = false;
        foreach ($all['seasons'] as $season) {
            if (is_array($season) && !empty($season['season_start']) && !empty($season['season_end'])) $has_valid = true;
        }
        if (!$has_valid) return new WP_Error('gbp_no_season','Aucune saison définie avec début et fin.');
        if (self::season_for_date($all, $today) === null) {
            return array('mode'=>'temporary_closed', 'date'=>$today, 'reason'=>'Hors des dates de saison définies dans WordPress.');
        }

        // Les sept prochains jours fournissent une base hebdomadaire, les variations
        // futures sont ensuite projetées en horaires exceptionnels.
        $baseline = array(); $regular = array();
        for ($i=0; $i<7; $i++) {
            $d = $date->modify('+'.$i.' days');
            $n = (int)$d->format('N');
            $slots = self::normalized_slots(self::slots($all, $d->format('Y-m-d'), $tz));
            if (is_wp_error($slots)) return $slots;
            $baseline[$n] = $slots;
            foreach ($slots as $slot) {
                $regular[] = array(
                    'openDay'=>self::$days[$n], 'openTime'=>self::time($slot['open']),
                    'closeDay'=>self::$days[$n], 'closeTime'=>self::time($slot['close'])
                );
            }
        }
        if (!$regular) {
            return array('mode'=>'temporary_closed','date'=>$today,'reason'=>'Aucune ouverture durant les sept prochains jours.');
        }

        $special = array();
        for ($i=0; $i<self::HORIZON_DAYS; $i++) {
            $d = $date->modify('+'.$i.' days');
            $ymd = $d->format('Y-m-d');
            $slots = self::normalized_slots(self::slots($all, $ymd, $tz));
            if (is_wp_error($slots)) return $slots;
            $base = $baseline[(int)$d->format('N')];
            if ($slots === $base) continue;
            if (!$slots) {
                $special[] = array('startDate'=>self::date_object($ymd),'closed'=>true);
                continue;
            }
            foreach ($slots as $slot) {
                $special[] = array(
                    'startDate'=>self::date_object($ymd),
                    'openTime'=>self::time($slot['open']),
                    'closeTime'=>self::time($slot['close']),
                    'closed'=>false,
                );
            }
        }
        return array(
            'mode'=>'open', 'date'=>$today, 'horizon_days'=>self::HORIZON_DAYS,
            'regularHours'=>array('periods'=>$regular),
            'specialHours'=>array('specialHourPeriods'=>$special),
        );
    }
}
