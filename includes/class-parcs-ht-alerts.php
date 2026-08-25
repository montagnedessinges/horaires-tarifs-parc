<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Parcs_HT_Alerts {
    public static function init() {
        add_action('wp_footer', array(__CLASS__, 'render_auto_popup'), 30);
    }

    public static function render_auto_popup() {
        if (is_admin()) return;

        // Ce hook s'exécute sur tout le site. Avant de reconstruire les réglages complets,
        // on vérifie directement dans l'option brute qu'un pop-up est réellement susceptible d'être utilisé.
        // Sur un site sans pop-up actif, le coût de l'extension sur wp_footer reste ainsi minimal.
        if (!self::has_popup_source()) return;

        $settings = Parcs_HT_Defaults::settings();
        $rows = isset($settings['alerts']) && is_array($settings['alerts']) ? $settings['alerts'] : array();
        $alerts = array();
        $g = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        $timezone = Parcs_HT_Schedule::timezone($settings);
        $default_reappear = isset($g['alert_reappear_hours']) ? max(1, min(720, (int)$g['alert_reappear_hours'])) : 1;

        foreach ($rows as $index => $row) {
            if (!is_array($row) || !isset($row['enabled']) || (string) $row['enabled'] !== '1' || empty($row['start'])) continue;
            $published = array_key_exists('published', $row) ? (string)$row['published'] === '1' : true;
            if (!$published) continue;
            $title = isset($row['title']) && is_array($row['title']) ? $row['title'] : array();
            $message = isset($row['message']) && is_array($row['message']) ? $row['message'] : array();
            if (empty(array_filter($title)) && empty(array_filter($message))) continue;
            $alerts[] = array(
                'id' => substr(md5($index . '|' . $row['start'] . '|' . (isset($row['end']) ? $row['end'] : '') . '|' . wp_json_encode($title) . '|' . wp_json_encode($message)), 0, 16),
                'start' => $row['start'],
                'end' => isset($row['end']) ? $row['end'] : '',
                'reappearHours' => $default_reappear,
                'title' => array(
                    'fr' => isset($title['fr']) ? $title['fr'] : '',
                    'en' => isset($title['en']) ? $title['en'] : '',
                    'de' => isset($title['de']) ? $title['de'] : '',
                ),
                'message' => array(
                    'fr' => isset($message['fr']) ? $message['fr'] : '',
                    'en' => isset($message['en']) ? $message['en'] : '',
                    'de' => isset($message['de']) ? $message['de'] : '',
                ),
                'button_label' => isset($row['button_label']) && is_array($row['button_label']) ? array(
                    'fr' => isset($row['button_label']['fr']) ? $row['button_label']['fr'] : '',
                    'en' => isset($row['button_label']['en']) ? $row['button_label']['en'] : '',
                    'de' => isset($row['button_label']['de']) ? $row['button_label']['de'] : '',
                ) : array('fr'=>'','en'=>'','de'=>''),
                'button_url' => self::url_translations(isset($row['button_url']) ? $row['button_url'] : array()),
                'show_button' => isset($row['show_button']) ? (string)$row['show_button'] : '0',
                'image_url' => '',
            );
        }


        // 1.2.8 : un horaire/une fermeture exceptionnelle peut déclencher le même pop-up.
        // settings() contient déjà toutes les saisons : évite un second chargement complet.
        $all_settings = $settings;
        $seasons = isset($all_settings['seasons']) && is_array($all_settings['seasons']) ? $all_settings['seasons'] : array();
        foreach ($seasons as $year => $season) {
            if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;
            $exceptions = isset($season['exceptions']) && is_array($season['exceptions']) ? $season['exceptions'] : array();
            foreach ($exceptions as $index => $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['show_popup'] ?? '0') !== '1') continue;
                if (empty($row['start']) || empty($row['end'])) continue;
                $title = array('fr'=>'','en'=>'','de'=>'');
                $message = array('fr'=>'','en'=>'','de'=>'');
                foreach (array('fr','en','de') as $lang) {
                    $public_title = self::translation(isset($row['title']) ? $row['title'] : array(), $lang);
                    $context = self::translation(isset($row['context']) ? $row['context'] : array(), $lang);
                    $public_message = self::translation(isset($row['message']) ? $row['message'] : array(), $lang);

                    $title[$lang] = $public_title !== '' ? $public_title : self::exception_default_title($row, $lang);

                    $parts = array();
                    if ($context !== '') $parts[] = $context;
                    if ($public_message !== '') $parts[] = $public_message;
                    $practical = self::exception_practical_message(
                        $row,
                        $lang,
                        (string)($row['popup_show_dates'] ?? '1') === '1',
                        (string)($row['popup_show_hours'] ?? '1') === '1'
                    );
                    if ($practical !== '') $parts[] = $practical;
                    $message[$lang] = implode("\n", $parts);
                }
                if (empty(array_filter($title)) && empty(array_filter($message))) continue;
                $alerts[] = array(
                    'id' => substr(md5('exception|' . $year . '|' . $index . '|' . $row['start'] . '|' . $row['end'] . '|' . wp_json_encode($title) . '|' . wp_json_encode($message)), 0, 16),
                    'start' => self::popup_start($row, $row['start'], $timezone),
                    'end' => self::popup_end($row, $row['end']),
                    'reappearHours' => $default_reappear,
                    'title' => $title,
                    'message' => $message,
                    'button_label' => isset($row['popup_button_label']) && is_array($row['popup_button_label']) ? array(
                        'fr' => isset($row['popup_button_label']['fr']) ? $row['popup_button_label']['fr'] : '',
                        'en' => isset($row['popup_button_label']['en']) ? $row['popup_button_label']['en'] : '',
                        'de' => isset($row['popup_button_label']['de']) ? $row['popup_button_label']['de'] : '',
                    ) : array('fr'=>'','en'=>'','de'=>''),
                    'button_url' => self::url_translations(isset($row['popup_button_url']) ? $row['popup_button_url'] : array()),
                    'show_button' => isset($row['popup_show_button']) ? (string)$row['popup_show_button'] : '0',
                    'image_url' => '',
                );
            }
        }


        // 1.3.0 : les périodes spécifiques / événements utilisent le même moteur de pop-up.
        foreach ($seasons as $year => $season) {
            if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;
            $events = isset($season['special_periods']) && is_array($season['special_periods']) ? $season['special_periods'] : array();
            foreach ($events as $index => $row) {
                if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || (string)($row['show_popup'] ?? '0') !== '1') continue;
                if (empty($row['start']) || empty($row['end'])) continue;
                $title = array('fr'=>'','en'=>'','de'=>'');
                $message = array('fr'=>'','en'=>'','de'=>'');
                foreach (array('fr','en','de') as $lang) {
                    $title[$lang] = self::translation(isset($row['popup_title']) ? $row['popup_title'] : array(), $lang);
                    if ($title[$lang] === '') $title[$lang] = self::translation(isset($row['title']) ? $row['title'] : array(), $lang);
                    $message[$lang] = self::translation(isset($row['popup_message']) ? $row['popup_message'] : array(), $lang);
                    if ($message[$lang] === '') $message[$lang] = self::translation(isset($row['message']) ? $row['message'] : array(), $lang);
                }
                if (empty(array_filter($title)) && empty(array_filter($message))) continue;
                $button_label = isset($row['popup_button_label']) && is_array($row['popup_button_label']) ? $row['popup_button_label'] : (isset($row['button_label']) && is_array($row['button_label']) ? $row['button_label'] : array());
                $button_url = !empty($row['popup_button_url']) ? self::url_translations($row['popup_button_url']) : self::url_translations(isset($row['button_url']) ? $row['button_url'] : array());
                $show_button = isset($row['popup_show_button']) ? (string)$row['popup_show_button'] : (isset($row['show_button']) ? (string)$row['show_button'] : '0');
                $alerts[] = array(
                    'id' => substr(md5('event|' . $year . '|' . $index . '|' . $row['start'] . '|' . $row['end'] . '|' . wp_json_encode($title)), 0, 16),
                    'start' => self::popup_start($row, $row['start'], $timezone),
                    'end' => self::popup_end($row, $row['end']),
                    'reappearHours' => $default_reappear,
                    'title' => $title,
                    'message' => $message,
                    'button_label' => array(
                        'fr' => isset($button_label['fr']) ? $button_label['fr'] : '',
                        'en' => isset($button_label['en']) ? $button_label['en'] : '',
                        'de' => isset($button_label['de']) ? $button_label['de'] : '',
                    ),
                    'button_url' => $button_url,
                    'show_button' => $show_button,
                    'image_url' => isset($row['popup_image_url']) ? $row['popup_image_url'] : '',
                );
            }
        }

        if (empty($alerts)) return;

        $bg = self::color($g, 'alert_bg_color', '#006757');
        $title_color = self::color($g, 'alert_title_color', '#ffffff');
        $text = self::color($g, 'alert_text_color', '#ffffff');
        $border = self::color($g, 'alert_border_color', '#ef7b5b');
        $button_bg = self::color($g, 'alert_button_bg_color', '#ef7b5b');
        $button_text = self::color($g, 'alert_button_text_color', '#ffffff');
        $button_border = self::color($g, 'alert_button_border_color', '#ef7b5b');
        $close_bg = self::color($g, 'alert_close_bg_color', '#ffffff');
        $close_text = self::color($g, 'alert_close_text_color', '#222222');
        $overlay = self::rgba(self::color($g, 'alert_overlay_color', '#000000'), isset($g['alert_overlay_opacity']) ? (int)$g['alert_overlay_opacity'] : 68);
        $border_width = isset($g['alert_border_width']) ? max(0, min(12, (int)$g['alert_border_width'])) : 3;
        $radius = isset($g['alert_radius']) ? max(0, min(40, (int)$g['alert_radius'])) : 16;
        $shadow = !isset($g['alert_shadow']) || (string)$g['alert_shadow'] === '1';
        $popup_title_size = !empty($g['font_alert_title_size']) ? max(10, min(80, (int)$g['font_alert_title_size'])) : 0;
        $popup_text_size = !empty($g['font_alert_text_size']) ? max(8, min(50, (int)$g['font_alert_text_size'])) : 0;
        $popup_button_size = !empty($g['font_alert_button_size']) ? max(8, min(40, (int)$g['font_alert_button_size'])) : 0;
        $payload = array('alerts' => $alerts, 'currentLanguage' => Parcs_HT_Schedule::language(), 'timezone'=>Parcs_HT_Schedule::timezone($settings));
        ?>
        <style id="parcs-ht-auto-alert-css">
        .parcs-ht-auto-modal{position:fixed;z-index:1000000;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;background:<?php echo esc_html($overlay); ?>}.parcs-ht-auto-dialog{position:relative;width:min(620px,100%);max-height:90vh;overflow:auto;padding:30px 26px 26px;border:<?php echo (int)$border_width; ?>px solid <?php echo esc_html($border); ?>;border-radius:<?php echo (int)$radius; ?>px;background:<?php echo esc_html($bg); ?>;color:<?php echo esc_html($text); ?>;<?php echo $shadow ? 'box-shadow:0 18px 55px rgba(0,0,0,.3);' : 'box-shadow:none;'; ?>font-family:inherit;text-align:center}.parcs-ht-auto-dialog h2{margin:0 38px 12px;color:<?php echo esc_html($title_color); ?>;font:inherit;font-size:<?php echo $popup_title_size ? ((int)$popup_title_size . 'px') : 'clamp(24px,4vw,34px)'; ?>;font-weight:800;line-height:1.15}.parcs-ht-auto-image{display:block;width:min(100%,426px);aspect-ratio:16/9;object-fit:cover;margin:0 auto 16px;border-radius:calc(<?php echo (int)$radius; ?>px * .55)}.parcs-ht-auto-dialog p{margin:0;color:<?php echo esc_html($text); ?>;font:inherit;font-size:<?php echo $popup_text_size ? ((int)$popup_text_size . 'px') : '16px'; ?>;line-height:1.55;white-space:pre-line}.parcs-ht-auto-close{appearance:none;position:absolute;right:10px;top:10px;width:36px;height:36px;border:0;border-radius:50%;background:<?php echo esc_html($close_bg); ?>;color:<?php echo esc_html($close_text); ?>;font-size:24px;line-height:1;cursor:pointer}.parcs-ht-auto-link{display:inline-block;margin-top:20px;padding:11px 20px;border:2px solid <?php echo esc_html($button_border); ?>;border-radius:999px;background:<?php echo esc_html($button_bg); ?>;color:<?php echo esc_html($button_text); ?>!important;font-weight:800;font-size:<?php echo $popup_button_size ? ((int)$popup_button_size . 'px') : 'inherit'; ?>;text-decoration:none!important}@media(max-width:600px){.parcs-ht-auto-dialog{padding:28px 18px 22px}.parcs-ht-auto-dialog h2{margin-right:34px;font-size:<?php echo $popup_title_size ? ((int)$popup_title_size . 'px') : '25px'; ?>}.parcs-ht-auto-image{width:min(100%,320px)}}
        </style>
        <script id="parcs-ht-auto-alert-data" type="application/json"><?php echo wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <script id="parcs-ht-auto-alert-js">
        (function(){'use strict';var node=document.getElementById('parcs-ht-auto-alert-data');if(!node)return;var data;try{data=JSON.parse(node.textContent||'{}')}catch(e){return}var alerts=data.alerts||[];if(!alerts.length)return;function nowLocal(){var p=new Intl.DateTimeFormat('fr-CA',{timeZone:data.timezone||'Europe/Paris',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date()),v={};p.forEach(function(x){v[x.type]=x.value});return v.year+'-'+v.month+'-'+v.day+'T'+v.hour+':'+v.minute}function lang(){var q=String(data.currentLanguage||'').slice(0,2).toLowerCase();if(q==='fr'||q==='en'||q==='de')return q;var m=window.location.pathname.match(/^\/(en|de)(?:\/|$)/i);if(m)return m[1].toLowerCase();var h=(document.documentElement.getAttribute('lang')||'').slice(0,2).toLowerCase();if(h==='fr'||h==='en'||h==='de')return h;return 'fr'}function tr(o,l){return o&&typeof o==='object'?(o[l]||o.fr||''):''}function exact(o,l){return o&&typeof o==='object'?(o[l]||''):''}var now=nowLocal(),a=alerts.find(function(x){return x.start&&now>=x.start&&(!x.end||now<=x.end)});if(!a)return;var l=lang(),key='parcs_ht_alert_'+a.id+'_'+l;try{var last=parseInt(localStorage.getItem(key)||'0',10),wait=Math.max(1,parseInt(a.reappearHours||1,10))*3600000;if(last&&Date.now()-last<wait)return}catch(e){}var title=tr(a.title,l),message=tr(a.message,l),button=tr(a.button_label,l),buttonUrl=exact(a.button_url,l);if(!title&&!message)return;var previousFocus=document.activeElement,modal=document.createElement('div');modal.className='parcs-ht-auto-modal';modal.setAttribute('role','dialog');modal.setAttribute('aria-modal','true');modal.setAttribute('aria-label',title||message);var dialog=document.createElement('div');dialog.className='parcs-ht-auto-dialog';var close=document.createElement('button');close.type='button';close.className='parcs-ht-auto-close';close.setAttribute('aria-label',l==='en'?'Close':(l==='de'?'Schließen':'Fermer'));close.textContent='×';dialog.appendChild(close);if(a.image_url){var img=document.createElement('img');img.className='parcs-ht-auto-image';img.src=a.image_url;img.alt=title||'';dialog.appendChild(img)}if(title){var h=document.createElement('h2');h.textContent=title;dialog.appendChild(h)}if(message){var p=document.createElement('p');p.textContent=message;dialog.appendChild(p)}if(String(a.show_button||'0')==='1'&&button&&buttonUrl){var link=document.createElement('a');link.className='parcs-ht-auto-link';link.href=buttonUrl;link.textContent=button;dialog.appendChild(link)}modal.appendChild(dialog);document.body.appendChild(modal);document.body.style.overflow='hidden';function dismiss(){document.removeEventListener('keydown',keys);document.body.style.overflow='';modal.remove();if(previousFocus&&typeof previousFocus.focus==='function')previousFocus.focus();try{localStorage.setItem(key,String(Date.now()))}catch(e){}}function keys(e){if(e.key==='Escape'){dismiss();return}if(e.key!=='Tab')return;var f=modal.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])');if(!f.length){e.preventDefault();close.focus();return}var first=f[0],last=f[f.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}}close.addEventListener('click',dismiss);modal.addEventListener('click',function(e){if(e.target===modal)dismiss()});document.addEventListener('keydown',keys);close.focus()})();
        </script>
        <?php
    }

    private static function has_popup_source() {
        $saved = get_option(Parcs_HT_Defaults::OPTION, array());
        if (!is_array($saved)) return false;

        $alerts = isset($saved['alerts']) && is_array($saved['alerts']) ? $saved['alerts'] : array();
        foreach ($alerts as $row) {
            if (!is_array($row)) continue;
            if ((string)($row['enabled'] ?? '0') !== '1') continue;
            if (array_key_exists('published', $row) && (string)$row['published'] !== '1') continue;
            if (empty($row['start'])) continue;
            return true;
        }

        $seasons = isset($saved['seasons']) && is_array($saved['seasons']) ? $saved['seasons'] : array();
        foreach ($seasons as $season) {
            if (!is_array($season) || (string)($season['published'] ?? '0') !== '1') continue;

            $exceptions = isset($season['exceptions']) && is_array($season['exceptions']) ? $season['exceptions'] : array();
            foreach ($exceptions as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1' && !empty($row['start']) && !empty($row['end'])) {
                    return true;
                }
            }

            $periods = isset($season['special_periods']) && is_array($season['special_periods']) ? $season['special_periods'] : array();
            foreach ($periods as $row) {
                if (is_array($row) && (string)($row['enabled'] ?? '0') === '1' && (string)($row['show_popup'] ?? '0') === '1' && !empty($row['start']) && !empty($row['end'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function popup_start($row, $event_start, $timezone = 'Europe/Paris') {
        $mode = isset($row['popup_lead_mode']) ? (string)$row['popup_lead_mode'] : 'same';
        if ($mode === 'custom' && !empty($row['popup_start'])) return (string)$row['popup_start'];
        if ($mode === 'days_before') {
            $days = isset($row['popup_days_before']) ? max(0, min(365, (int)$row['popup_days_before'])) : 0;
            try {
                $date = new DateTimeImmutable((string)$event_start . ' 00:00:00', new DateTimeZone($timezone));
                return $date->modify('-' . $days . ' days')->format('Y-m-d\\TH:i');
            } catch (Exception $e) {}
        }
        return (string)$event_start . 'T00:00';
    }

    private static function popup_end($row, $event_end) {
        if (!empty($row['popup_end'])) return (string)$row['popup_end'];
        return (string)$event_end . 'T23:59';
    }

    private static function url_translations($value) {
        if (is_array($value)) {
            return array(
                'fr' => isset($value['fr']) ? (string)$value['fr'] : '',
                'en' => isset($value['en']) ? (string)$value['en'] : '',
                'de' => isset($value['de']) ? (string)$value['de'] : '',
            );
        }
        $legacy = is_string($value) ? $value : '';
        return array('fr'=>$legacy,'en'=>$legacy,'de'=>$legacy);
    }

    private static function translation($value, $language) {
        if (!is_array($value)) return is_string($value) ? $value : '';
        if (isset($value[$language]) && $value[$language] !== '') return (string)$value[$language];
        return isset($value['fr']) ? (string)$value['fr'] : '';
    }

    private static function exception_default_title($row, $language) {
        $closed = isset($row['type']) && $row['type'] === 'closed';
        if ($language === 'en') return $closed ? 'Exceptional closure' : 'Exceptional opening hours';
        if ($language === 'de') return $closed ? 'Außergewöhnliche Schließung' : 'Außergewöhnliche Öffnungszeiten';
        return $closed ? 'Fermeture exceptionnelle' : 'Horaires exceptionnels';
    }


    private static function exception_practical_message($row, $language, $show_dates, $show_hours) {
        $closed = isset($row['type']) && $row['type'] === 'closed';
        $start = isset($row['start']) ? self::format_date($row['start'], $language) : '';
        $end = isset($row['end']) ? self::format_date($row['end'], $language) : '';
        $open = isset($row['open']) ? self::format_time($row['open'], $language) : '';
        $close = isset($row['close']) ? self::format_time($row['close'], $language) : '';
        $open2 = isset($row['open2']) ? self::format_time($row['open2'], $language) : '';
        $close2 = isset($row['close2']) ? self::format_time($row['close2'], $language) : '';

        $parts = array();
        if ($show_dates && $start !== '') {
            if ($language === 'en') $parts[] = ($start === $end ? 'Date: ' . $start : 'Dates: ' . $start . ' to ' . $end);
            elseif ($language === 'de') $parts[] = ($start === $end ? 'Datum: ' . $start : 'Zeitraum: ' . $start . ' bis ' . $end);
            else $parts[] = ($start === $end ? 'Date : ' . $start : 'Dates : du ' . $start . ' au ' . $end);
        }
        if ($show_hours && !$closed && $open !== '' && $close !== '') {
            $hours = $open . ' – ' . $close;
            if ($open2 !== '' && $close2 !== '') $hours .= ' / ' . $open2 . ' – ' . $close2;
            if ($language === 'en') $parts[] = 'Opening hours: ' . $hours;
            elseif ($language === 'de') $parts[] = 'Öffnungszeiten: ' . $hours;
            else $parts[] = 'Horaires : ' . $hours;
        }
        return implode("\n", $parts);
    }

    private static function exception_auto_message($row, $language) {
        $start = isset($row['start']) ? self::format_date($row['start'], $language) : '';
        $end = isset($row['end']) ? self::format_date($row['end'], $language) : '';
        $open = isset($row['open']) ? self::format_time($row['open'], $language) : '';
        $close = isset($row['close']) ? self::format_time($row['close'], $language) : '';
        $open2 = isset($row['open2']) ? self::format_time($row['open2'], $language) : '';
        $close2 = isset($row['close2']) ? self::format_time($row['close2'], $language) : '';
        $hours = $open . ' to ' . $close;
        if ($open2 !== '' && $close2 !== '') $hours .= ' / ' . $open2 . ' to ' . $close2;
        $closed = isset($row['type']) && $row['type'] === 'closed';
        if ($language === 'en') {
            if ($closed) return 'The park is closed from ' . $start . ' to ' . $end . '.';
            return 'From ' . $start . ' to ' . $end . ', the park is open ' . $hours . '.';
        }
        if ($language === 'de') {
            if ($closed) return 'Der Park ist vom ' . $start . ' bis ' . $end . ' geschlossen.';
            return 'Vom ' . $start . ' bis ' . $end . ' ist der Park geöffnet: ' . str_replace(' to ', ' – ', $hours) . '.';
        }
        if ($closed) return 'Le parc est fermé du ' . $start . ' au ' . $end . '.';
        return 'Du ' . $start . ' au ' . $end . ', le parc est ouvert : ' . str_replace(' to ', ' – ', $hours) . '.';
    }

    private static function format_date($date, $language) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$date, $m)) return (string)$date;
        $timestamp = gmmktime(12, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
        if (class_exists('IntlDateFormatter')) {
            $locale = $language === 'en' ? 'en_GB' : ($language === 'de' ? 'de_DE' : 'fr_FR');
            $fmt = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Europe/Paris', IntlDateFormatter::GREGORIAN, 'd MMMM yyyy');
            $formatted = $fmt->format($timestamp);
            if ($formatted !== false) return $formatted;
        }
        return sprintf('%02d/%02d/%04d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }

    private static function format_time($time, $language) {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', (string)$time, $m)) return (string)$time;
        $h = (int)$m[1]; $min = (int)$m[2];
        if ($language === 'en') {
            $suffix = $h >= 12 ? 'PM' : 'AM'; $display = $h % 12; if ($display === 0) $display = 12;
            return $display . ($min ? ':' . sprintf('%02d', $min) : '') . ' ' . $suffix;
        }
        if ($language === 'de') return $h . ($min ? ':' . sprintf('%02d', $min) : '') . ' Uhr';
        return $h . ' h' . ($min ? ' ' . sprintf('%02d', $min) : '');
    }

    private static function color($g, $key, $fallback) {
        return isset($g[$key]) && sanitize_hex_color($g[$key]) ? sanitize_hex_color($g[$key]) : $fallback;
    }

    private static function rgba($hex, $opacity) {
        $hex = ltrim((string)$hex, '#');
        if (strlen($hex) !== 6) return 'rgba(0,0,0,.68)';
        $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
        $a = max(0, min(100, (int)$opacity)) / 100;
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . rtrim(rtrim(number_format($a, 2, '.', ''), '0'), '.') . ')';
    }
}
