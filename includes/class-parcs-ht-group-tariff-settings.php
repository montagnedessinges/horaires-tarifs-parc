<?php

if (!defined('ABSPATH')) { exit; }

/** Réglages publics propres aux tarifs groupes, enregistrés par saison. */
final class Parcs_HT_Group_Tariff_Settings {
    const OPTION = 'parcs_ht_group_tariff_settings';
    const STORE_VERSION = 3;

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'ensure_store'), 6);
    }

    private static function clean_translations($value, $textarea = false) {
        $value = is_array($value) ? $value : array();
        $out = array('fr'=>'','en'=>'','de'=>'');
        foreach ($out as $lang => $unused) {
            $raw = (string)($value[$lang] ?? '');
            $out[$lang] = $textarea ? sanitize_textarea_field(wp_unslash($raw)) : sanitize_text_field(wp_unslash($raw));
        }
        return $out;
    }

    private static function clean_urls($value) {
        $value = is_array($value) ? $value : array();
        $out = array('fr'=>'','en'=>'','de'=>'');
        foreach ($out as $lang => $unused) $out[$lang] = esc_url_raw((string)($value[$lang] ?? ''));
        return $out;
    }

    private static function icon_choices() {
        return array('card','cash','cheque','document','chorus','bank','online','other');
    }

    private static function clean_payment_methods($value) {
        $out = array();
        foreach (array_slice(is_array($value) ? $value : array(), 0, 20) as $item) {
            if (!is_array($item)) continue;
            $icon = sanitize_key((string)($item['icon'] ?? 'other'));
            if (!in_array($icon, self::icon_choices(), true)) $icon = 'other';
            $labels = self::clean_translations($item['label'] ?? array());
            if ($labels['fr'] === '' && $labels['en'] === '' && $labels['de'] === '') continue;
            $out[] = array(
                'enabled'=>isset($item['enabled']) && (string)$item['enabled'] === '0' ? '0' : '1',
                'icon'=>$icon,
                'label'=>$labels,
            );
        }
        return $out;
    }

    private static function clean_info_blocks($value) {
        $out = array();
        foreach (array_slice(is_array($value) ? $value : array(), 0, 20) as $item) {
            if (!is_array($item)) continue;
            $title = self::clean_translations($item['title'] ?? array());
            $text = self::clean_translations($item['text'] ?? array(), true);
            if ($title['fr'] === '' && $title['en'] === '' && $title['de'] === '' && $text['fr'] === '' && $text['en'] === '' && $text['de'] === '') continue;
            $out[] = array(
                'enabled'=>isset($item['enabled']) && (string)$item['enabled'] === '0' ? '0' : '1',
                'title'=>$title,
                'text'=>$text,
            );
        }
        return $out;
    }

    private static function appearance_defaults() {
        return array(
            'tariff_title_color'=>'',
            'tariff_title_bg_color'=>'#ffffff',
            'tariff_title_bg_transparent'=>'1',
            'payment_title_color'=>'',
            'payment_title_bg_color'=>'#ffffff',
            'payment_title_bg_transparent'=>'1',
            'payment_item_bg_color'=>'#006757',
            'payment_item_text_color'=>'#ffffff',
            'payment_icon_color'=>'#ffffff',
            'payment_border_color'=>'',
            'payment_border_enabled'=>'0',
            'panel_text_color'=>'',
            'panel_border_color'=>'',
            'panel_border_enabled'=>'0',
            'panel_bg_color'=>'#ffffff',
            'panel_bg_transparent'=>'1',
            'price_color'=>'',
            'groups_note_text_color'=>'',
            'groups_note_border_color'=>'',
            'button_bg_color'=>'#006757',
            'button_text_color'=>'#ffffff',
        );
    }

    private static function clean_appearance($value) {
        $value = is_array($value) ? $value : array();
        $defaults = self::appearance_defaults();
        $toggles = array('tariff_title_bg_transparent','payment_title_bg_transparent','payment_border_enabled','panel_border_enabled','panel_bg_transparent');
        $out = array();
        foreach ($defaults as $key => $fallback) {
            if (in_array($key, $toggles, true)) {
                $out[$key] = isset($value[$key]) && (string)$value[$key] === '1' ? '1' : '0';
                continue;
            }
            $raw = array_key_exists($key, $value) ? (string)$value[$key] : (string)$fallback;
            if ($raw === '') {
                $out[$key] = '';
                continue;
            }
            $color = sanitize_hex_color($raw);
            $out[$key] = $color ?: (sanitize_hex_color((string)$fallback) ?: '');
        }
        return $out;
    }

    private static function appearance_from_general($general) {
        $general = is_array($general) ? $general : array();
        return self::clean_appearance(array_replace(self::appearance_defaults(), array_intersect_key($general, self::appearance_defaults())));
    }

    private static function row_style_defaults() {
        return array(
            'label_color'=>'','subtitle_color'=>'','note_color'=>'','price_color'=>'',
            'row_bg_color'=>'#ffffff','row_bg_transparent'=>'1','row_border_color'=>'',
        );
    }

    private static function clean_row_style($value) {
        $value = is_array($value) ? $value : array();
        $defaults = self::row_style_defaults();
        $out = array();
        foreach ($defaults as $key => $fallback) {
            if ($key === 'row_bg_transparent') {
                $out[$key] = isset($value[$key]) && (string)$value[$key] === '1' ? '1' : '0';
                continue;
            }
            $raw = array_key_exists($key, $value) ? (string)$value[$key] : (string)$fallback;
            if ($raw === '') {
                $out[$key] = '';
                continue;
            }
            $color = sanitize_hex_color($raw);
            $out[$key] = $color ?: (sanitize_hex_color((string)$fallback) ?: '');
        }
        return $out;
    }

    private static function clean_row_styles($value) {
        $out = array();
        foreach (is_array($value) ? $value : array() as $row_id => $style) {
            $row_id = sanitize_key((string)$row_id);
            if (!preg_match('/^tariff_row_\d{6,}$/', $row_id) || !is_array($style)) continue;
            $out[$row_id] = self::clean_row_style($style);
        }
        return $out;
    }

    private static function row_styles_from_rows($rows) {
        $out = array();
        foreach (is_array($rows) ? $rows : array() as $row) {
            if (!is_array($row)) continue;
            $row_id = sanitize_key((string)($row['id'] ?? ''));
            if (!preg_match('/^tariff_row_\d{6,}$/', $row_id)) continue;
            $source = array(
                'label_color'=>(string)($row['label_color'] ?? ''),
                'subtitle_color'=>(string)($row['subtitle_color'] ?? ($row['detail_color'] ?? '')),
                'note_color'=>(string)($row['note_color'] ?? ''),
                'price_color'=>(string)($row['price_color'] ?? ''),
                'row_bg_color'=>(string)($row['row_bg_color'] ?? '#ffffff'),
                'row_bg_transparent'=>(string)($row['row_bg_transparent'] ?? '1'),
                'row_border_color'=>(string)($row['row_border_color'] ?? ''),
            );
            $out[$row_id] = self::clean_row_style($source);
        }
        return $out;
    }

    public static function defaults($year = '') {
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : (string)wp_date('Y');
        return array(
            'published'=>'0',
            'show_heading'=>'1',
            'title'=>array('fr'=>'','en'=>'','de'=>''),
            'intro'=>array('fr'=>'','en'=>'','de'=>''),
            'show_future_notice'=>'1',
            'future_year'=>(string)((int)$year + 1),
            'future_notice'=>array('fr'=>'','en'=>'','de'=>''),
            'show_quote_button'=>'1',
            'button_label'=>array(
                'fr'=>'Faire une demande de devis',
                'en'=>'Request a quote',
                'de'=>'Angebot anfordern',
            ),
            'button_url'=>array('fr'=>'','en'=>'','de'=>''),
            'show_payment_methods'=>'0',
            'payment_title'=>array(
                'fr'=>'Moyens de paiement',
                'en'=>'Payment methods',
                'de'=>'Zahlungsmöglichkeiten',
            ),
            'payment_methods'=>array(),
            'show_info_blocks'=>'0',
            'info_blocks'=>array(),
            'appearance'=>self::appearance_defaults(),
            'row_styles'=>array(),
        );
    }

    /**
     * Contenu historique de la 1.13.6, utilisé uniquement pour migrer les installations MDS
     * existantes vers les nouveaux réglages modifiables. Le rendu public ne dépend plus du site_type.
     */
    private static function legacy_mds_payment_methods() {
        return array(
            array('enabled'=>'1','icon'=>'card','label'=>array('fr'=>'Carte bancaire','en'=>'Bank card','de'=>'Bankkarte')),
            array('enabled'=>'1','icon'=>'cash','label'=>array('fr'=>'Espèces','en'=>'Cash','de'=>'Bargeld')),
            array('enabled'=>'1','icon'=>'cheque','label'=>array('fr'=>'Chèque','en'=>'Cheque','de'=>'Scheck')),
            array('enabled'=>'1','icon'=>'document','label'=>array('fr'=>'Bon de commande / voucher','en'=>'Purchase order / voucher','de'=>'Bestellschein / Voucher')),
            array('enabled'=>'1','icon'=>'chorus','label'=>array('fr'=>'Chorus Pro','en'=>'Chorus Pro','de'=>'Chorus Pro')),
        );
    }

    private static function legacy_mds_info_blocks() {
        return array(
            array(
                'enabled'=>'1',
                'title'=>array('fr'=>'Paiement et facturation','en'=>'Payment and invoicing','de'=>'Zahlung und Rechnung'),
                'text'=>array(
                    'fr'=>'Paiement sur place : carte bancaire, espèces ou chèque. Pour un règlement différé, présentez le jour de la visite un devis signé, un bon de commande ou un voucher. Les structures publiques peuvent régler via Chorus Pro et doivent prévoir le numéro SIRET, l’adresse complète de facturation, un contact administratif et le code service. La facture est établie le jour de la visite selon le nombre réel de participants présents. Aucun paiement n’est demandé avant la visite et il n’est pas nécessaire de signaler un changement d’effectif.',
                    'en'=>'On-site payment: bank card, cash or cheque. For deferred payment, please present a signed quote, purchase order or voucher on the day of your visit. Public-sector organisations may pay via Chorus Pro and must provide their SIRET number, full billing address, administrative contact and service code. The invoice is issued on the day of the visit according to the actual number of participants present. No payment is requested before the visit and there is no need to report a change in group size.',
                    'de'=>'Zahlung vor Ort: Bankkarte, Bargeld oder Scheck. Für eine spätere Zahlung legen Sie am Besuchstag ein unterschriebenes Angebot, einen Bestellschein oder einen Voucher vor. Öffentliche Einrichtungen können über Chorus Pro zahlen und müssen ihre SIRET-Nummer, die vollständige Rechnungsadresse, einen Verwaltungskontakt und den Servicecode bereithalten. Die Rechnung wird am Besuchstag anhand der tatsächlich anwesenden Teilnehmerzahl erstellt. Vor dem Besuch ist keine Zahlung erforderlich; Änderungen der Gruppengröße müssen nicht gemeldet werden.',
                ),
            ),
            array(
                'enabled'=>'1',
                'title'=>array('fr'=>'Devis et réservation','en'=>'Quote and booking','de'=>'Angebot und Reservierung'),
                'text'=>array(
                    'fr'=>'La réservation est obligatoire pour bénéficier des tarifs groupes. Le devis est généré automatiquement après la demande et envoyé par e-mail. Pour confirmer la réservation, renvoyez-le signé avec la mention « Bon pour accord ». Le devis imprimé doit être présenté à l’accueil le jour de la visite.',
                    'en'=>'Booking is required to benefit from group rates. The quote is generated automatically after your request and sent by email. To confirm the booking, return the signed quote with the wording “Bon pour accord”. The printed quote must be presented at reception on the day of the visit.',
                    'de'=>'Eine Reservierung ist erforderlich, um die Gruppentarife in Anspruch zu nehmen. Das Angebot wird nach der Anfrage automatisch erstellt und per E-Mail versandt. Zur Bestätigung der Reservierung senden Sie das unterschriebene Angebot mit dem Vermerk „Bon pour accord“ zurück. Das ausgedruckte Angebot muss am Besuchstag am Empfang vorgelegt werden.',
                ),
            ),
        );
    }

    private static function raw_all_settings() {
        $saved = get_option(Parcs_HT_Defaults::OPTION, array());
        return is_array($saved) ? $saved : array();
    }

    private static function initial_store() {
        $all = self::raw_all_settings();
        $store = array('version'=>1,'seasons'=>array());
        foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
            if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
            $row = self::defaults((string)$year);
            $has_groups = !empty($season['tariffs']['groups']) && is_array($season['tariffs']['groups']);
            $row['published'] = ((string)($season['published'] ?? '0') === '1' && $has_groups) ? '1' : '0';
            $general = is_array($all['general'] ?? null) ? $all['general'] : array();
            if (isset($general['groups_url']) && is_array($general['groups_url'])) $row['button_url'] = self::clean_urls($general['groups_url']);
            if (isset($general['groups_button_label']) && is_array($general['groups_button_label'])) {
                $labels = self::clean_translations($general['groups_button_label']);
                foreach ($labels as $lang => $label) if ($label !== '') $row['button_label'][$lang] = $label;
            }
            $row['appearance'] = self::appearance_from_general($general);
            $row['row_styles'] = self::row_styles_from_rows($season['tariffs']['groups'] ?? array());
            $store['seasons'][(string)$year] = $row;
        }
        return $store;
    }

    private static function migrate_store($saved) {
        $saved = is_array($saved) ? $saved : array();
        $version = (int)($saved['version'] ?? 0);
        if ($version < 1 || !isset($saved['seasons']) || !is_array($saved['seasons'])) {
            $saved = self::initial_store();
            $version = 1;
        }
        $migrated_from_pre_v2 = $version < 2;
        if ($version < 2) {
            $all = self::raw_all_settings();
            $site_type = sanitize_key((string)($all['site_type'] ?? ''));
            foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
                if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
                $old = isset($saved['seasons'][$year]) && is_array($saved['seasons'][$year]) ? $saved['seasons'][$year] : array();
                $row = array_replace_recursive(self::defaults((string)$year), $old);
                if ($site_type === 'mds' && !array_key_exists('show_payment_methods', $old)) {
                    $row['show_payment_methods'] = '1';
                    $row['payment_methods'] = self::legacy_mds_payment_methods();
                    $row['show_info_blocks'] = '1';
                    $row['info_blocks'] = self::legacy_mds_info_blocks();
                }
                $saved['seasons'][(string)$year] = $row;
            }
            $saved['version'] = 2;
            $version = 2;
        }
        if ($version < 3) {
            $all = self::raw_all_settings();
            $general = is_array($all['general'] ?? null) ? $all['general'] : array();
            foreach ((array)($all['seasons'] ?? array()) as $year => $season) {
                if (!preg_match('/^20\d{2}$/', (string)$year) || !is_array($season)) continue;
                $old = isset($saved['seasons'][$year]) && is_array($saved['seasons'][$year]) ? $saved['seasons'][$year] : array();
                $row = array_replace_recursive(self::defaults((string)$year), $old);
                if ($migrated_from_pre_v2 || !isset($old['appearance']) || !is_array($old['appearance'])) {
                    $row['appearance'] = self::appearance_from_general($general);
                } else {
                    $row['appearance'] = self::clean_appearance($old['appearance']);
                }
                if ($migrated_from_pre_v2 || !isset($old['row_styles']) || !is_array($old['row_styles'])) {
                    $row['row_styles'] = self::row_styles_from_rows($season['tariffs']['groups'] ?? array());
                } else {
                    $row['row_styles'] = self::clean_row_styles($old['row_styles']);
                }
                $saved['seasons'][(string)$year] = $row;
            }
            $saved['version'] = 3;
        }
        return $saved;
    }

    public static function store($persist = false) {
        $saved = get_option(self::OPTION, array());
        $migrated = self::migrate_store($saved);
        if ($persist && (!is_array($saved) || wp_json_encode($saved) !== wp_json_encode($migrated))) {
            update_option(self::OPTION, $migrated, false);
        }
        if (!isset($migrated['seasons']) || !is_array($migrated['seasons'])) $migrated['seasons'] = array();
        $migrated['version'] = self::STORE_VERSION;
        return $migrated;
    }

    public static function ensure_store() {
        if (!current_user_can('manage_options')) return;
        self::store(true);
    }

    public static function settings($year) {
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : (string)wp_date('Y');
        $store = self::store();
        $saved = isset($store['seasons'][$year]) && is_array($store['seasons'][$year]) ? $store['seasons'][$year] : array();
        return array_replace_recursive(self::defaults($year), $saved);
    }

    public static function save($year, $raw) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return false;
        $raw = is_array($raw) ? $raw : array();
        $future_year = isset($raw['future_year']) ? sanitize_text_field(wp_unslash($raw['future_year'])) : (string)((int)$year + 1);
        if (!preg_match('/^20\d{2}$/', $future_year)) $future_year = (string)((int)$year + 1);
        $clean = array(
            'published'=>isset($raw['published']) && (string)$raw['published'] === '1' ? '1' : '0',
            'show_heading'=>isset($raw['show_heading']) && (string)$raw['show_heading'] === '1' ? '1' : '0',
            'title'=>self::clean_translations($raw['title'] ?? array()),
            'intro'=>self::clean_translations($raw['intro'] ?? array(), true),
            'show_future_notice'=>isset($raw['show_future_notice']) && (string)$raw['show_future_notice'] === '1' ? '1' : '0',
            'future_year'=>$future_year,
            'future_notice'=>self::clean_translations($raw['future_notice'] ?? array(), true),
            'show_quote_button'=>isset($raw['show_quote_button']) && (string)$raw['show_quote_button'] === '1' ? '1' : '0',
            'button_label'=>self::clean_translations($raw['button_label'] ?? array()),
            'button_url'=>self::clean_urls($raw['button_url'] ?? array()),
            'show_payment_methods'=>isset($raw['show_payment_methods']) && (string)$raw['show_payment_methods'] === '1' ? '1' : '0',
            'payment_title'=>self::clean_translations($raw['payment_title'] ?? array()),
            'payment_methods'=>self::clean_payment_methods($raw['payment_methods'] ?? array()),
            'show_info_blocks'=>isset($raw['show_info_blocks']) && (string)$raw['show_info_blocks'] === '1' ? '1' : '0',
            'info_blocks'=>self::clean_info_blocks($raw['info_blocks'] ?? array()),
            'appearance'=>self::clean_appearance($raw['appearance'] ?? array()),
            'row_styles'=>self::clean_row_styles($raw['row_styles'] ?? array()),
        );
        $store = self::store();
        $store['version'] = self::STORE_VERSION;
        $store['seasons'][$year] = $clean;
        update_option(self::OPTION, $store, false);
        $stored = self::settings($year);
        return wp_json_encode($clean) === wp_json_encode(array_intersect_key($stored, $clean));
    }

    public static function is_published($year) {
        $year = (string)$year;
        if (!preg_match('/^20\d{2}$/', $year)) return false;
        $all = self::raw_all_settings();
        $season = isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : null;
        if (!$season || (string)($season['published'] ?? '0') !== '1') return false;
        if (empty($season['tariffs']['groups']) || !is_array($season['tariffs']['groups'])) return false;
        return (string)(self::settings($year)['published'] ?? '0') === '1';
    }

    public static function published_years() {
        $all = self::raw_all_settings();
        $years = array();
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) if (self::is_published((string)$year)) $years[] = (string)$year;
        sort($years, SORT_NUMERIC);
        return $years;
    }

    public static function public_year() {
        $years = self::published_years();
        if (!$years) return '';
        $current = (string)wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        $past = array_values(array_filter($years, static function ($year) use ($current) { return (int)$year <= (int)$current; }));
        if ($past) return (string)end($past);
        return (string)$years[0];
    }

    public static function default_title($language, $year) {
        $labels = array('fr'=>'Tarifs groupes','en'=>'Group rates','de'=>'Gruppentarife');
        return ($labels[$language] ?? $labels['fr']) . ($year !== '' ? ' ' . $year : '');
    }

    public static function default_future_notice($language, $year) {
        if ($language === 'en') return 'Group rates for ' . $year . ' are not available yet. They will be displayed here as soon as they are published.';
        if ($language === 'de') return 'Die Gruppentarife für ' . $year . ' sind noch nicht verfügbar. Sie werden hier angezeigt, sobald sie veröffentlicht sind.';
        return 'Les tarifs groupes ' . $year . ' ne sont pas encore disponibles. Ils seront affichés ici dès leur publication.';
    }
}