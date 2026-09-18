<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Référentiel d'apparence globale introduit en 1.17.1.
 *
 * Les valeurs restent stockées dans `parcs_ht_settings[general]` afin de conserver
 * une seule source de vérité. Les modules peuvent ensuite appliquer la cascade :
 * élément > module > apparence globale.
 */
final class Parcs_HT_Global_Appearance {
    public static function init() {
        add_filter('pre_update_option_' . Parcs_HT_Defaults::OPTION, array(__CLASS__, 'preserve_new_fields'), 999, 3);
    }

    public static function new_fields() {
        return array(
            'link_color','focus_color',
            'button_primary_bg_color','button_primary_text_color','button_primary_border_color',
            'button_secondary_bg_color','button_secondary_text_color','button_secondary_border_color','button_radius',
            'card_bg_color','card_border_color','card_radius','card_shadow',
            'tab_bg_color','tab_text_color','tab_active_bg_color','tab_active_text_color',
            'chip_bg_color','chip_text_color','chip_border_color','chip_radius',
        );
    }

    public static function editable_fields() {
        return array_merge(array(
            'primary_color','secondary_color','accent_color','highlight_color',
            'body_text_color','heading_text_color','border_color','block_spacing','block_border_enabled',
        ), self::new_fields());
    }

    public static function defaults() {
        return array(
            'primary_color'=>'#006757',
            'secondary_color'=>'#31ad81',
            'accent_color'=>'#ef7b5b',
            'highlight_color'=>'#e7c55b',
            'body_text_color'=>'',
            'heading_text_color'=>'',
            'border_color'=>'',
            'link_color'=>'',
            'focus_color'=>'',
            'button_primary_bg_color'=>'',
            'button_primary_text_color'=>'#ffffff',
            'button_primary_border_color'=>'',
            'button_secondary_bg_color'=>'',
            'button_secondary_text_color'=>'',
            'button_secondary_border_color'=>'',
            'button_radius'=>'8',
            'card_bg_color'=>'',
            'card_border_color'=>'',
            'card_radius'=>'10',
            'card_shadow'=>'none',
            'tab_bg_color'=>'',
            'tab_text_color'=>'',
            'tab_active_bg_color'=>'',
            'tab_active_text_color'=>'#ffffff',
            'chip_bg_color'=>'',
            'chip_text_color'=>'',
            'chip_border_color'=>'',
            'chip_radius'=>'999',
            'block_spacing'=>'8',
            'block_border_enabled'=>'0',
        );
    }

    private static function optional_color($value, $fallback = '') {
        $value = trim((string)$value);
        if ($value === '') return '';
        $color = sanitize_hex_color($value);
        return $color ? $color : $fallback;
    }

    private static function required_color($value, $fallback) {
        $color = sanitize_hex_color((string)$value);
        return $color ? $color : $fallback;
    }

    private static function number($value, $fallback, $min, $max) {
        if ($value === '' || !is_numeric($value)) return (string)$fallback;
        $value = (int)$value;
        if ($value < $min) $value = $min;
        if ($value > $max) $value = $max;
        return (string)$value;
    }

    public static function general($settings = null) {
        if (!is_array($settings)) $settings = Parcs_HT_Defaults::all_settings();
        $general = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        return array_merge(self::defaults(), $general);
    }

    public static function sanitize_submission($raw, $current = array()) {
        $raw = is_array($raw) ? $raw : array();
        $current = array_merge(self::defaults(), is_array($current) ? $current : array());
        $clean = array();

        foreach (array('primary_color','secondary_color','accent_color','highlight_color') as $key) {
            $clean[$key] = self::required_color($raw[$key] ?? $current[$key], $current[$key]);
        }
        foreach (array(
            'body_text_color','heading_text_color','border_color','link_color','focus_color',
            'button_primary_bg_color','button_primary_text_color','button_primary_border_color',
            'button_secondary_bg_color','button_secondary_text_color','button_secondary_border_color',
            'card_bg_color','card_border_color','tab_bg_color','tab_text_color','tab_active_bg_color','tab_active_text_color',
            'chip_bg_color','chip_text_color','chip_border_color'
        ) as $key) {
            $clean[$key] = self::optional_color($raw[$key] ?? $current[$key], $current[$key]);
        }

        $clean['button_radius'] = self::number($raw['button_radius'] ?? $current['button_radius'], $current['button_radius'], 0, 80);
        $clean['card_radius'] = self::number($raw['card_radius'] ?? $current['card_radius'], $current['card_radius'], 0, 80);
        $clean['chip_radius'] = self::number($raw['chip_radius'] ?? $current['chip_radius'], $current['chip_radius'], 0, 999);
        $clean['block_spacing'] = self::number($raw['block_spacing'] ?? $current['block_spacing'], $current['block_spacing'], 0, 60);
        $clean['block_border_enabled'] = isset($raw['block_border_enabled']) && (string)$raw['block_border_enabled'] === '1' ? '1' : '0';

        $shadow = isset($raw['card_shadow']) ? sanitize_key((string)$raw['card_shadow']) : (string)$current['card_shadow'];
        $clean['card_shadow'] = in_array($shadow, array('none','soft','medium'), true) ? $shadow : 'none';

        return $clean;
    }

    public static function merge_general($general, $raw) {
        $general = is_array($general) ? $general : array();
        return array_merge($general, self::sanitize_submission($raw, $general));
    }

    /**
     * Empêche l'ancien écran détaillé de supprimer les nouveaux champs qu'il ne
     * connaît pas encore. Une valeur explicitement présente dans la nouvelle
     * sauvegarde reste toujours prioritaire.
     */
    public static function preserve_new_fields($new_value, $old_value, $option) {
        unset($option);
        if (!is_array($new_value) || !is_array($old_value)) return $new_value;
        if (!isset($new_value['general']) || !is_array($new_value['general'])) $new_value['general'] = array();
        $old_general = isset($old_value['general']) && is_array($old_value['general']) ? $old_value['general'] : array();
        foreach (self::new_fields() as $key) {
            if (!array_key_exists($key, $new_value['general']) && array_key_exists($key, $old_general)) {
                $new_value['general'][$key] = $old_general[$key];
            }
        }
        return $new_value;
    }

    public static function tokens($settings = null) {
        $g = self::general($settings);
        $primary = $g['primary_color'];
        $highlight = $g['highlight_color'];
        $border = $g['border_color'] !== '' ? $g['border_color'] : 'currentColor';
        $shadow = 'none';
        if ($g['card_shadow'] === 'soft') $shadow = '0 2px 10px rgba(0,0,0,.08)';
        if ($g['card_shadow'] === 'medium') $shadow = '0 6px 20px rgba(0,0,0,.14)';

        return array(
            'color.primary'=>$primary,
            'color.secondary'=>$g['secondary_color'],
            'color.accent'=>$g['accent_color'],
            'color.highlight'=>$highlight,
            'text.primary'=>$g['body_text_color'] !== '' ? $g['body_text_color'] : 'inherit',
            'text.heading'=>$g['heading_text_color'] !== '' ? $g['heading_text_color'] : 'inherit',
            'border.default'=>$border,
            'link.color'=>$g['link_color'] !== '' ? $g['link_color'] : $primary,
            'focus.color'=>$g['focus_color'] !== '' ? $g['focus_color'] : $highlight,
            'button.primary.bg'=>$g['button_primary_bg_color'] !== '' ? $g['button_primary_bg_color'] : $primary,
            'button.primary.text'=>$g['button_primary_text_color'] !== '' ? $g['button_primary_text_color'] : '#ffffff',
            'button.primary.border'=>$g['button_primary_border_color'] !== '' ? $g['button_primary_border_color'] : $primary,
            'button.secondary.bg'=>$g['button_secondary_bg_color'] !== '' ? $g['button_secondary_bg_color'] : 'transparent',
            'button.secondary.text'=>$g['button_secondary_text_color'] !== '' ? $g['button_secondary_text_color'] : $primary,
            'button.secondary.border'=>$g['button_secondary_border_color'] !== '' ? $g['button_secondary_border_color'] : $primary,
            'button.radius'=>$g['button_radius'] . 'px',
            'card.bg'=>$g['card_bg_color'] !== '' ? $g['card_bg_color'] : 'transparent',
            'card.border'=>$g['card_border_color'] !== '' ? $g['card_border_color'] : $border,
            'card.radius'=>$g['card_radius'] . 'px',
            'card.shadow'=>$shadow,
            'tab.bg'=>$g['tab_bg_color'] !== '' ? $g['tab_bg_color'] : 'transparent',
            'tab.text'=>$g['tab_text_color'] !== '' ? $g['tab_text_color'] : 'inherit',
            'tab.active_bg'=>$g['tab_active_bg_color'] !== '' ? $g['tab_active_bg_color'] : $primary,
            'tab.active_text'=>$g['tab_active_text_color'] !== '' ? $g['tab_active_text_color'] : '#ffffff',
            'chip.bg'=>$g['chip_bg_color'] !== '' ? $g['chip_bg_color'] : 'transparent',
            'chip.text'=>$g['chip_text_color'] !== '' ? $g['chip_text_color'] : 'inherit',
            'chip.border'=>$g['chip_border_color'] !== '' ? $g['chip_border_color'] : $border,
            'chip.radius'=>$g['chip_radius'] . 'px',
            'spacing.block'=>$g['block_spacing'] . 'px',
        );
    }

    public static function css_variables($settings = null) {
        $tokens = self::tokens($settings);
        $map = array(
            'color.primary'=>'--htp-primary', 'color.secondary'=>'--htp-secondary', 'color.accent'=>'--htp-accent', 'color.highlight'=>'--htp-highlight',
            'text.primary'=>'--htp-body-text', 'text.heading'=>'--htp-heading-text', 'border.default'=>'--htp-border',
            'link.color'=>'--htp-link', 'focus.color'=>'--htp-focus',
            'button.primary.bg'=>'--htp-button-primary-bg', 'button.primary.text'=>'--htp-button-primary-text', 'button.primary.border'=>'--htp-button-primary-border',
            'button.secondary.bg'=>'--htp-button-secondary-bg', 'button.secondary.text'=>'--htp-button-secondary-text', 'button.secondary.border'=>'--htp-button-secondary-border', 'button.radius'=>'--htp-button-radius',
            'card.bg'=>'--htp-card-bg', 'card.border'=>'--htp-card-border', 'card.radius'=>'--htp-card-radius', 'card.shadow'=>'--htp-card-shadow',
            'tab.bg'=>'--htp-tab-bg', 'tab.text'=>'--htp-tab-text', 'tab.active_bg'=>'--htp-tab-active-bg', 'tab.active_text'=>'--htp-tab-active-text',
            'chip.bg'=>'--htp-chip-bg', 'chip.text'=>'--htp-chip-text', 'chip.border'=>'--htp-chip-border', 'chip.radius'=>'--htp-chip-radius',
            'spacing.block'=>'--htp-block-spacing',
        );
        $out = array();
        foreach ($map as $token => $variable) $out[$variable] = $tokens[$token];
        return $out;
    }

    /** Cascade commune destinée aux prochaines catégories. */
    public static function resolve($settings, $token, $module_overrides = array(), $element_overrides = array()) {
        if (is_array($element_overrides) && array_key_exists($token, $element_overrides) && $element_overrides[$token] !== '') return $element_overrides[$token];
        if (is_array($module_overrides) && array_key_exists($token, $module_overrides) && $module_overrides[$token] !== '') return $module_overrides[$token];
        $tokens = self::tokens($settings);
        return array_key_exists($token, $tokens) ? $tokens[$token] : '';
    }

    /** Un module sans réglage explicite hérite toujours du global. */
    public static function uses_global($module_settings) {
        if (!is_array($module_settings) || !isset($module_settings['appearance_mode'])) return true;
        return (string)$module_settings['appearance_mode'] !== 'custom';
    }
}
