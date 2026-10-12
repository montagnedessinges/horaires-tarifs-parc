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
        .parcs-ht-auto-modal{position:fixed;z-index:1000000;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;background:<?php echo esc_html($overlay); ?>}.parcs-ht-auto-dialog{position:relative;width:min(620px,100%);max-height:90vh;overflow:auto;padding:30px 26px 26px;border:<?php echo (int)$border_width; ?>px solid <?php echo esc_html($border); ?>;border-radius:<?php echo (int)$radius; ?>px;background:<?php echo esc_html($bg); ?>;color:<?php echo esc_html($text); ?>;<?php echo $shadow ? 'box-shadow:0 18px 55px rgba(0,0,0,.3);' : 'box-shadow:none;'; ?>font-family:inherit;text-align:center}.parcs-ht-auto-dialog h2{margin:0 38px 12px;color:<?php echo esc_html($title_color); ?>;font:inherit;font-size:<?php echo esc_html($popup_title_size ? ((int)$popup_title_size . 'px') : 'clamp(24px,4vw,34px)'); ?>;font-weight:800;line-height:1.15}.parcs-ht-auto-image{display:block;width:min(100%,426px);aspect-ratio:16/9;object-fit:cover;margin:0 auto 16px;border-radius:calc(<?php echo (int)$radius; ?>px * .55)}.parcs-ht-auto-dialog p{margin:0;color:<?php echo esc_html($text); ?>;font:inherit;font-size:<?php echo esc_html($popup_text_size ? ((int)$popup_text_size . 'px') : '16px'); ?>;line-height:1.55;white-space:pre-line}.parcs-ht-auto-close{appearance:none;position:absolute;right:10px;top:10px;width:36px;height:36px;border:0;border-radius:50%;background:<?php echo esc_html($close_bg); ?>;color:<?php echo esc_html($close_text); ?>;font-size:24px;line-height:1;cursor:pointer}.parcs-ht-auto-link{display:inline-block;margin-top:20px;padding:11px 20px;border:2px solid <?php echo esc_html($button_border); ?>;border-radius:999px;background:<?php echo esc_html($button_bg); ?>;color:<?php echo esc_html($button_text); ?>!important;font-weight:800;font-size:<?php echo esc_html($popup_button_size ? ((int)$popup_button_size . 'px') : 'inherit'); ?>;text-decoration:none!important}@media(max-width:600px){.parcs-ht-auto-dialog{padding:28px 18px 22px}.parcs-ht-auto-dialog h2{margin-right:34px;font-size:<?php echo esc_html($popup_title_size ? ((int)$popup_title_size . 'px') : '25px'); ?>}.parcs-ht-auto-image{width:min(100%,320px)}}
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

        return false;
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
