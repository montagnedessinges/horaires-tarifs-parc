<?php

$root = dirname(__DIR__);

function write_file_checked($path, $content) {
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "Unable to write {$path}\n");
        exit(1);
    }
}

function replace_exact_checked($path, $old, $new) {
    $content = file_get_contents($path);
    if ($content === false || strpos($content, $old) === false) {
        fwrite(STDERR, "Expected block not found in {$path}\n");
        exit(1);
    }
    $updated = str_replace($old, $new, $content, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Expected exactly one replacement in {$path}, got {$count}\n");
        exit(1);
    }
    write_file_checked($path, $updated);
}

function replace_between_checked($path, $start, $end, $replacement) {
    $content = file_get_contents($path);
    if ($content === false) {
        fwrite(STDERR, "Unable to read {$path}\n");
        exit(1);
    }
    $a = strpos($content, $start);
    if ($a === false) {
        fwrite(STDERR, "Start marker not found in {$path}: {$start}\n");
        exit(1);
    }
    $b = strpos($content, $end, $a + strlen($start));
    if ($b === false) {
        fwrite(STDERR, "End marker not found in {$path}: {$end}\n");
        exit(1);
    }
    $updated = substr($content, 0, $a) . $replacement . substr($content, $b);
    write_file_checked($path, $updated);
}

$settings_class = <<<'PHP'
<?php

if (!defined('ABSPATH')) { exit; }

/** Réglages publics propres aux tarifs groupes, enregistrés par saison. */
final class Parcs_HT_Group_Tariff_Settings {
    const OPTION = 'parcs_ht_group_tariff_settings';
    const STORE_VERSION = 2;

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
        }
        return $saved;
    }

    public static function store() {
        $saved = get_option(self::OPTION, array());
        $migrated = self::migrate_store($saved);
        if (!is_array($saved) || wp_json_encode($saved) !== wp_json_encode($migrated)) {
            update_option(self::OPTION, $migrated, false);
        }
        if (!isset($migrated['seasons']) || !is_array($migrated['seasons'])) $migrated['seasons'] = array();
        $migrated['version'] = self::STORE_VERSION;
        return $migrated;
    }

    public static function ensure_store() {
        if (!current_user_can('manage_options')) return;
        self::store();
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
PHP;
write_file_checked($root . '/includes/class-parcs-ht-group-tariff-settings.php', $settings_class);

$admin_path = $root . '/includes/class-parcs-ht-admin-groups.php';
$admin_start = "        \$raw = array(\n";
$admin_end = "        if (!Parcs_HT_Group_Tariff_Settings::save";
$admin_replacement = <<<'PHP'
        $payment_methods = array();
        if (isset($_POST['payment_methods_json'])) {
            $decoded = json_decode(wp_unslash($_POST['payment_methods_json']), true);
            if (is_array($decoded)) $payment_methods = $decoded;
        }
        $info_blocks = array();
        if (isset($_POST['info_blocks_json'])) {
            $decoded = json_decode(wp_unslash($_POST['info_blocks_json']), true);
            if (is_array($decoded)) $info_blocks = $decoded;
        }
        $raw = array(
            'published'=>isset($_POST['published']) ? sanitize_text_field(wp_unslash($_POST['published'])) : '0',
            'show_heading'=>isset($_POST['show_heading']) ? sanitize_text_field(wp_unslash($_POST['show_heading'])) : '0',
            'show_future_notice'=>isset($_POST['show_future_notice']) ? sanitize_text_field(wp_unslash($_POST['show_future_notice'])) : '0',
            'future_year'=>isset($_POST['future_year']) ? sanitize_text_field(wp_unslash($_POST['future_year'])) : '',
            'show_quote_button'=>isset($_POST['show_quote_button']) ? sanitize_text_field(wp_unslash($_POST['show_quote_button'])) : '0',
            'show_payment_methods'=>isset($_POST['show_payment_methods']) ? sanitize_text_field(wp_unslash($_POST['show_payment_methods'])) : '0',
            'show_info_blocks'=>isset($_POST['show_info_blocks']) ? sanitize_text_field(wp_unslash($_POST['show_info_blocks'])) : '0',
            'payment_methods'=>$payment_methods,
            'info_blocks'=>$info_blocks,
            'title'=>array(),'intro'=>array(),'future_notice'=>array(),'button_label'=>array(),'button_url'=>array(),'payment_title'=>array(),
        );
        foreach (array('fr','en','de') as $lang) {
            foreach (array('title','intro','future_notice','button_label','button_url','payment_title') as $field) {
                $key = $field . '_' . $lang;
                if (!isset($_POST[$key])) {
                    $raw[$field][$lang] = '';
                } elseif (in_array($field, array('intro','future_notice'), true)) {
                    $raw[$field][$lang] = sanitize_textarea_field(wp_unslash($_POST[$key]));
                } elseif ($field === 'button_url') {
                    $raw[$field][$lang] = esc_url_raw(wp_unslash($_POST[$key]));
                } else {
                    $raw[$field][$lang] = sanitize_text_field(wp_unslash($_POST[$key]));
                }
            }
        }
PHP;
replace_between_checked($admin_path, $admin_start, $admin_end, $admin_replacement . $admin_end);

$shortcode_path = $root . '/includes/class-parcs-ht-group-tariffs.php';
replace_between_checked(
    $shortcode_path,
    "    private static function is_mds(\$settings) {",
    "    private static function columns(\$tariffs) {",
    <<<'PHP'
    private static function display_settings($year) {
        if (class_exists('Parcs_HT_Group_Tariff_Settings')) {
            $settings = Parcs_HT_Group_Tariff_Settings::settings($year);
            if (is_array($settings)) return $settings;
        }
        return array(
            'show_heading'=>'1','title'=>array(),'intro'=>array(),
            'show_quote_button'=>'1','button_label'=>array(),'button_url'=>array(),
            'show_payment_methods'=>'0','payment_title'=>array(),'payment_methods'=>array(),
            'show_info_blocks'=>'0','info_blocks'=>array(),
        );
    }

PHP
);

replace_between_checked(
    $shortcode_path,
    "    /**\n     * Moyens de paiement MDS pour les visites de groupes.",
    "    private static function style_variables(\$general) {",
    <<<'PHP'
    private static function default_payment_title($language) {
        if ($language === 'en') return 'Payment methods';
        if ($language === 'de') return 'Zahlungsmöglichkeiten';
        return 'Moyens de paiement';
    }

    private static function payment_icon_svg($icon) {
        $common = 'viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"';
        switch ($icon) {
            case 'cash':
                return '<svg ' . $common . '><rect x="2.5" y="5.5" width="15.5" height="10.5" rx="1.8"/><circle cx="10.2" cy="10.8" r="2.2"/><circle cx="18" cy="16.7" r="3.3"/><path d="M16.7 16.7h2.6"/></svg>';
            case 'cheque':
                return '<svg ' . $common . '><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M5.5 9h7M5.5 12.5h4.5M14.5 14.2l1.7 1.7 3.1-3.4"/></svg>';
            case 'document':
                return '<svg ' . $common . '><path d="M6 2.8h8l4 4V21H6z"/><path d="M14 2.8V7h4M9 11h6M9 14.5h6M9 18h4"/></svg>';
            case 'chorus':
                return '<svg ' . $common . '><path d="M3 20.5h18M5 20.5V9.5h14v11M8 20.5v-7h3v7M14 20.5v-7h3v7M4 9.5 12 4l8 5.5"/></svg>';
            case 'bank':
                return '<svg ' . $common . '><path d="M3 20h18M5 20V9h14v11M8 20v-7M12 20v-7M16 20v-7M3.5 9 12 4l8.5 5"/></svg>';
            case 'online':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>';
            case 'other':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>';
            case 'card':
            default:
                return '<svg ' . $common . '><rect x="2.5" y="4.8" width="19" height="14.4" rx="2.4"/><path d="M2.5 9.1h19"/><rect x="6" y="13" width="4.6" height="2.4" rx=".5" fill="currentColor" stroke="none"/><path d="M13.4 14.2h4.2"/></svg>';
        }
    }

    private static function payment_strip($display, $language) {
        if ((string)($display['show_payment_methods'] ?? '0') !== '1') return '';
        $items = isset($display['payment_methods']) && is_array($display['payment_methods']) ? $display['payment_methods'] : array();
        $rendered = array();
        foreach ($items as $item) {
            if (!is_array($item) || (isset($item['enabled']) && (string)$item['enabled'] === '0')) continue;
            $label = self::translation($item['label'] ?? array(), $language, '');
            if ($label === '') continue;
            $rendered[] = array('icon'=>sanitize_key((string)($item['icon'] ?? 'other')),'label'=>$label);
        }
        if (!$rendered) return '';
        $title = self::translation($display['payment_title'] ?? array(), $language, self::default_payment_title($language));
        if ($title === '') $title = self::default_payment_title($language);
        ob_start(); ?>
        <div class="parcs-ht-payment-strip parcs-ht-group-payment-strip" aria-label="<?php echo esc_attr($title); ?>">
            <strong class="parcs-ht-payment-title"><?php echo esc_html($title); ?></strong>
            <div class="parcs-ht-payment-icons">
                <?php foreach ($rendered as $item) : ?>
                    <span class="parcs-ht-payment-item">
                        <span class="parcs-ht-payment-icon" aria-hidden="true"><?php echo wp_kses(self::payment_icon_svg($item['icon']), Parcs_HT_Defaults::svg_allowed_tags()); ?></span>
                        <span><?php echo esc_html($item['label']); ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    private static function information_blocks($display, $language) {
        if ((string)($display['show_info_blocks'] ?? '0') !== '1') return '';
        $blocks = isset($display['info_blocks']) && is_array($display['info_blocks']) ? $display['info_blocks'] : array();
        $html = '';
        foreach ($blocks as $block) {
            if (!is_array($block) || (isset($block['enabled']) && (string)$block['enabled'] === '0')) continue;
            $title = self::translation($block['title'] ?? array(), $language, '');
            $text = self::translation($block['text'] ?? array(), $language, '');
            if ($title === '' && $text === '') continue;
            $html .= '<article class="parcs-ht-quote-info">';
            if ($title !== '') $html .= '<div class="parcs-ht-quote-info-title" role="heading" aria-level="3">' . esc_html($title) . '</div>';
            if ($text !== '') $html .= '<p>' . nl2br(esc_html($text)) . '</p>';
            $html .= '</article>';
        }
        return $html === '' ? '' : '<div class="parcs-ht-quote-info-grid parcs-ht-group-info-grid">' . $html . '</div>';
    }

PHP
);

replace_exact_checked(
    $shortcode_path,
    "        \$show_head = count(\$columns) > 1;\n        \$is_mds = self::is_mds(\$settings);\n\n        \$groups_url = isset(\$general['groups_url'][\$language]) ? (string)\$general['groups_url'][\$language] : '';\n        \$groups_booking_note = self::translation(\$general['groups_booking_note'] ?? array(), \$language, '');\n        \$groups_button_label = self::translation(\$general['groups_button_label'] ?? array(), \$language, '');\n        if (\$groups_button_label === '') {\n            \$groups_button_label = \$language === 'en' ? 'Request a quote' : (\$language === 'de' ? 'Angebot anfordern' : 'Faire une demande de devis');\n        }\n",
    "        \$show_head = count(\$columns) > 1;\n        \$display = self::display_settings(\$year);\n        \$show_heading = (string)(\$display['show_heading'] ?? '1') === '1';\n        \$custom_title = self::translation(\$display['title'] ?? array(), \$language, '');\n        if (\$custom_title !== '') \$title = \$custom_title;\n        \$intro = self::translation(\$display['intro'] ?? array(), \$language, '');\n        \$payment_html = self::payment_strip(\$display, \$language);\n        \$info_html = self::information_blocks(\$display, \$language);\n\n        \$fallback_url = isset(\$general['groups_url'][\$language]) ? (string)\$general['groups_url'][\$language] : '';\n        \$configured_url = isset(\$display['button_url'][\$language]) ? (string)\$display['button_url'][\$language] : '';\n        \$groups_url = \$configured_url !== '' ? \$configured_url : \$fallback_url;\n        \$groups_booking_note = self::translation(\$general['groups_booking_note'] ?? array(), \$language, '');\n        \$groups_button_label = self::translation(\$display['button_label'] ?? array(), \$language, '');\n        if (\$groups_button_label === '') \$groups_button_label = self::translation(\$general['groups_button_label'] ?? array(), \$language, '');\n        if (\$groups_button_label === '') {\n            \$groups_button_label = \$language === 'en' ? 'Request a quote' : (\$language === 'de' ? 'Angebot anfordern' : 'Faire une demande de devis');\n        }\n        \$show_quote_button = (string)(\$display['show_quote_button'] ?? '1') === '1';\n"
);

replace_exact_checked(
    $shortcode_path,
    "            <header class=\"parcs-ht-heading parcs-ht-tariff-heading\">\n                <div class=\"parcs-ht-title\" role=\"heading\" aria-level=\"2\"><?php echo esc_html(\$title); ?></div>\n            </header>\n            <?php if (\$is_mds) echo self::mds_payment_strip(\$language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne entièrement échappé. ?>\n",
    "            <?php if (\$show_heading || \$intro !== '') : ?>\n                <header class=\"parcs-ht-heading parcs-ht-tariff-heading\">\n                    <?php if (\$show_heading) : ?><div class=\"parcs-ht-title\" role=\"heading\" aria-level=\"2\"><?php echo esc_html(\$title); ?></div><?php endif; ?>\n                    <?php if (\$intro !== '') : ?><p class=\"parcs-ht-group-tariffs-intro\"><?php echo nl2br(esc_html(\$intro)); ?></p><?php endif; ?>\n                </header>\n            <?php endif; ?>\n            <?php echo \$payment_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne entièrement échappé. ?>\n"
);

replace_exact_checked(
    $shortcode_path,
    "                <?php if (\$is_mds) : ?>\n                    <?php echo self::mds_information_blocks(\$language); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne entièrement échappé. ?>\n                <?php elseif (\$groups_booking_note !== '') : ?>\n                    <p class=\"parcs-ht-groups-booking-note\"><?php echo nl2br(esc_html(\$groups_booking_note)); ?></p>\n                <?php endif; ?>\n                <?php if (\$groups_url !== '') : ?><div class=\"parcs-ht-panel-actions\"><a class=\"parcs-ht-button\" href=\"<?php echo esc_url(\$groups_url); ?>\"><?php echo esc_html(\$groups_button_label); ?></a></div><?php endif; ?>",
    "                <?php if (\$info_html !== '') : ?>\n                    <?php echo \$info_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML interne entièrement échappé. ?>\n                <?php elseif (\$groups_booking_note !== '') : ?>\n                    <p class=\"parcs-ht-groups-booking-note\"><?php echo nl2br(esc_html(\$groups_booking_note)); ?></p>\n                <?php endif; ?>\n                <?php if (\$show_quote_button && \$groups_url !== '') : ?><div class=\"parcs-ht-panel-actions\"><a class=\"parcs-ht-button\" href=\"<?php echo esc_url(\$groups_url); ?>\"><?php echo esc_html(\$groups_button_label); ?></a></div><?php endif; ?>"
);

$js_path = $root . '/assets/admin-groups.js';
$js_new_panel = <<<'JS'
    function groupListTranslations(prefix,label,multiline,values){
        values=values||{};
        var html='<div class="htp-field"><span>'+escaped(label)+'</span><div class="htp-grid htp-grid-3">';
        ['fr','en','de'].forEach(function(lang){
            var value=values[lang]||'';
            html+='<label><small>'+lang.toUpperCase()+'</small>'+(multiline?'<textarea rows="3" data-list-field="'+prefix+'_'+lang+'">'+escaped(value)+'</textarea>':'<input type="text" data-list-field="'+prefix+'_'+lang+'" value="'+escaped(value)+'">')+'</label>';
        });
        return html+'</div></div>';
    }

    function paymentMethodRow(item){
        item=item||{};var label=item.label||{};
        var icons={card:'Carte',cash:'Espèces',cheque:'Chèque',document:'Document / bon',chorus:'Administration / portail',bank:'Banque / virement',online:'En ligne',other:'Autre'};
        var options='';Object.keys(icons).forEach(function(key){options+='<option value="'+key+'"'+(String(item.icon||'other')===key?' selected':'')+'>'+icons[key]+'</option>';});
        return '<div class="htp-subsection" data-gt-payment-row><div class="htp-row-head"><strong>Moyen de paiement</strong><span><button type="button" class="button button-small" data-gt-move="up">↑</button> <button type="button" class="button button-small" data-gt-move="down">↓</button> <button type="button" class="button button-small button-link-delete" data-gt-remove>Supprimer</button></span></div><label><input type="checkbox" data-list-enabled '+(String(item.enabled||'1')!=='0'?'checked':'')+'> Afficher</label><label class="htp-field"><span>Icône</span><select data-list-icon>'+options+'</select></label>'+groupListTranslations('label','Libellé',false,label)+'</div>';
    }

    function infoBlockRow(item){
        item=item||{};
        return '<div class="htp-subsection" data-gt-info-row><div class="htp-row-head"><strong>Bloc d’information</strong><span><button type="button" class="button button-small" data-gt-move="up">↑</button> <button type="button" class="button button-small" data-gt-move="down">↓</button> <button type="button" class="button button-small button-link-delete" data-gt-remove>Supprimer</button></span></div><label><input type="checkbox" data-list-enabled '+(String(item.enabled||'1')!=='0'?'checked':'')+'> Afficher</label>'+groupListTranslations('title','Titre',false,item.title||{})+groupListTranslations('text','Texte',true,item.text||{})+'</div>';
    }

    function ensureGroupSettingsPanel(){
        var $group=$('#htp-tariffs [data-htp-tariff-group="groups"]');
        if(!$group.length||$('#htp-tariffs [data-htp-group-tariff-settings]').length)return;
        var g=cfg.group_tariff||{};
        var html='<div class="htp-subsection" data-htp-group-tariff-settings data-htp-group-context hidden><h3>Publication et affichage des tarifs groupes</h3><p class="description">Ces réglages sont propres à cette installation et à la saison '+escaped(cfg.year||'')+'. Le contenu peut donc être différent à la Montagne des Singes, à la Forêt des Singes ou sur un autre site utilisant l’extension.</p><div class="htp-check-list"><label><input type="checkbox" data-gt="published"> Publier les tarifs groupes de cette année</label><label><input type="checkbox" data-gt="show_heading"> Afficher le titre du bloc</label><label><input type="checkbox" data-gt="show_future_notice"> Afficher un message pour une année future non disponible</label><label><input type="checkbox" data-gt="show_quote_button"> Afficher le bouton de devis</label><label><input type="checkbox" data-gt="show_payment_methods"> Afficher les moyens de paiement</label><label><input type="checkbox" data-gt="show_info_blocks"> Afficher les blocs d’information</label></div><div class="htp-grid htp-grid-2">'+groupTranslationFields('title','Titre du bloc (vide = titre automatique)',false)+groupTranslationFields('intro','Texte d’introduction facultatif',true)+'<label class="htp-field"><span>Année future annoncée</span><input type="number" min="2020" max="2100" data-gt="future_year"></label>'+groupTranslationFields('future_notice','Message année future (vide = texte automatique)',true)+groupTranslationFields('button_label','Texte du bouton devis',false)+groupTranslationFields('button_url','Lien du bouton devis',false)+groupTranslationFields('payment_title','Titre des moyens de paiement',false)+'</div><details class="htp-advanced" open><summary>Moyens de paiement</summary><div class="htp-advanced-content"><p class="description">Ajoutez uniquement les moyens acceptés sur ce site. L’ordre ci-dessous est l’ordre d’affichage public.</p><div data-gt-payment-list></div><p><button type="button" class="button" data-gt-add-payment>Ajouter un moyen de paiement</button></p></div></details><details class="htp-advanced" open><summary>Informations pratiques sous les tarifs</summary><div class="htp-advanced-content"><p class="description">Ces blocs sont libres : paiement, facturation, réservation, justificatifs ou toute autre information utile. Ils sont traduisibles et réordonnables.</p><div data-gt-info-list></div><p><button type="button" class="button" data-gt-add-info>Ajouter un bloc d’information</button></p></div></details><p><button type="button" class="button button-primary" data-gt-save>Enregistrer l’affichage groupes</button> <span data-gt-status></span></p></div>';
        var $panel=$(html);
        ['published','show_heading','show_future_notice','show_quote_button','show_payment_methods','show_info_blocks'].forEach(function(k){$panel.find('[data-gt="'+k+'"]').prop('checked',String(g[k])==='1');});
        $panel.find('[data-gt="future_year"]').val(g.future_year||((parseInt(cfg.year,10)||new Date().getFullYear())+1));
        ['title','intro','future_notice','button_label','button_url','payment_title'].forEach(function(field){['fr','en','de'].forEach(function(lang){$panel.find('[data-gt-field="'+field+'_'+lang+'"]').val((g[field]&&g[field][lang])||'');});});
        $panel.on('click','[data-gt-trans] [data-gt-lang]',function(){var $wrap=$(this).closest('[data-gt-trans]'),lang=$(this).data('gt-lang');$wrap.find('[data-gt-lang]').removeClass('button-primary');$(this).addClass('button-primary');$wrap.find('[data-gt-field]').attr('hidden',true);$wrap.find('[data-gt-field$="_'+lang+'"]').removeAttr('hidden');});
        (g.payment_methods||[]).forEach(function(item){$panel.find('[data-gt-payment-list]').append(paymentMethodRow(item));});
        (g.info_blocks||[]).forEach(function(item){$panel.find('[data-gt-info-list]').append(infoBlockRow(item));});
        $panel.on('click','[data-gt-add-payment]',function(){$panel.find('[data-gt-payment-list]').append(paymentMethodRow({enabled:'1',icon:'card',label:{fr:'',en:'',de:''}}));});
        $panel.on('click','[data-gt-add-info]',function(){$panel.find('[data-gt-info-list]').append(infoBlockRow({enabled:'1',title:{fr:'',en:'',de:''},text:{fr:'',en:'',de:''}}));});
        $panel.on('click','[data-gt-remove]',function(){$(this).closest('[data-gt-payment-row],[data-gt-info-row]').remove();});
        $panel.on('click','[data-gt-move]',function(){var $row=$(this).closest('[data-gt-payment-row],[data-gt-info-row]');if($(this).data('gt-move')==='up')$row.prev().before($row);else $row.next().after($row);});
        $panel.insertAfter($group);
    }
JS;
replace_between_checked($js_path, "    function ensureGroupSettingsPanel(){", "    function showGroupTariffs(){", $js_new_panel . "\n\n    function showGroupTariffs(){");

$js_save = <<<'JS'
    $('#htp-tariffs').on('click','[data-gt-save]',function(){
        var $panel=$(this).closest('[data-htp-group-tariff-settings]');
        var data={action:'parcs_ht_save_group_tariff_settings',nonce:cfg.group_tariff_nonce,year:cfg.year};
        ['published','show_heading','show_future_notice','show_quote_button','show_payment_methods','show_info_blocks'].forEach(function(k){data[k]=$panel.find('[data-gt="'+k+'"]').is(':checked')?'1':'0';});
        data.future_year=$panel.find('[data-gt="future_year"]').val();
        ['title','intro','future_notice','button_label','button_url','payment_title'].forEach(function(field){['fr','en','de'].forEach(function(lang){data[field+'_'+lang]=$panel.find('[data-gt-field="'+field+'_'+lang+'"]').val();});});
        var payments=[];$panel.find('[data-gt-payment-row]').each(function(){var $row=$(this),labels={};['fr','en','de'].forEach(function(lang){labels[lang]=$row.find('[data-list-field="label_'+lang+'"]').val()||'';});payments.push({enabled:$row.find('[data-list-enabled]').is(':checked')?'1':'0',icon:$row.find('[data-list-icon]').val()||'other',label:labels});});
        var blocks=[];$panel.find('[data-gt-info-row]').each(function(){var $row=$(this),title={},text={};['fr','en','de'].forEach(function(lang){title[lang]=$row.find('[data-list-field="title_'+lang+'"]').val()||'';text[lang]=$row.find('[data-list-field="text_'+lang+'"]').val()||'';});blocks.push({enabled:$row.find('[data-list-enabled]').is(':checked')?'1':'0',title:title,text:text});});
        data.payment_methods_json=JSON.stringify(payments);data.info_blocks_json=JSON.stringify(blocks);
        var $status=$panel.find('[data-gt-status]').text('Enregistrement…');
        $.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');if(res&&res.success){cfg.group_tariff=cfg.group_tariff||{};cfg.group_tariff.payment_methods=payments;cfg.group_tariff.info_blocks=blocks;}}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});
    });
JS;
replace_between_checked($js_path, "    $('#htp-tariffs').on('click','[data-gt-save]'", "    var requested='';", $js_save . "\n\n    var requested='';");

$runtime_test = <<<'PHP'
<?php

define('ABSPATH', __DIR__);
define('PARCS_HT_URL', 'https://example.test/wp-content/plugins/horaires-tarifs-parc/');
define('PARCS_HT_VERSION', 'test');

$GLOBALS['parcs_ht_test_shortcodes'] = array();
$GLOBALS['parcs_ht_test_settings'] = array();
$GLOBALS['parcs_ht_test_group_display'] = array();
$GLOBALS['parcs_ht_tariff_selector_called'] = 0;

function add_shortcode($tag, $callback) { $GLOBALS['parcs_ht_test_shortcodes'][$tag] = $callback; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function sanitize_hex_color($value) { return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? (string)$value : null; }
function wp_style_is($handle, $state = 'enqueued') { return false; }
function wp_register_style($handle, $src, $deps = array(), $version = false) { return true; }
function wp_enqueue_style($handle) { return true; }
function did_action($hook) { return 0; }
function wp_print_styles($handles = false) { return array(); }
function wp_date($format, $timestamp = null, $timezone = null) { return $format === 'Y-m-d' ? '2026-09-07' : date($format, $timestamp ?: time()); }
function esc_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return (string)$value; }
function wp_kses($value, $allowed_html) { return (string)$value; }

final class Parcs_HT_Defaults {
    public static function settings() { return $GLOBALS['parcs_ht_test_settings']; }
    public static function all_settings() { return $GLOBALS['parcs_ht_test_settings']; }
    public static function svg_allowed_tags() { return array('svg'=>array(),'rect'=>array(),'path'=>array(),'circle'=>array()); }
}
final class Parcs_HT_Tariff_Seasons {
    public static function select_season_tariffs($settings, $public = false) { $GLOBALS['parcs_ht_tariff_selector_called']++; return $settings; }
}
final class Parcs_HT_Schedule {
    public static function language() { return 'fr'; }
    public static function timezone($settings = array()) { return 'Europe/Paris'; }
    public static function translation($value, $language, $fallback = '') {
        if (is_array($value) && isset($value[$language]) && (string)$value[$language] !== '') return (string)$value[$language];
        if (is_array($value)) foreach (array('fr','en','de') as $lang) if (!empty($value[$lang])) return (string)$value[$lang];
        return (string)$fallback;
    }
}
final class Parcs_HT_Group_Tariff_Settings {
    public static function settings($year) { return $GLOBALS['parcs_ht_test_group_display']; }
}

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-tariffs.php';

function group_runtime_assert($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

Parcs_HT_Group_Tariffs::init();
foreach (array('parc_tarifs_groupes','parc_tarifs_groupes_fr','parc_tarifs_groupes_en','parc_tarifs_groupes_de') as $tag) group_runtime_assert(isset($GLOBALS['parcs_ht_test_shortcodes'][$tag]), 'registered shortcode ' . $tag);

$GLOBALS['parcs_ht_test_settings'] = array(
    'site_type'=>'fds',
    'general'=>array(
        'year'=>'2026',
        'groups_booking_note'=>array('fr'=>'Note générique de réservation.'),
        'groups_button_label'=>array('fr'=>'Bouton général'),
        'groups_url'=>array('fr'=>'https://example.test/general'),
        'primary_color'=>'#006757','payment_item_bg_color'=>'#006757','payment_item_text_color'=>'#ffffff','payment_icon_color'=>'#ffffff',
    ),
    'tariffs'=>array(
        'columns'=>array('groups'=>array(array('id'=>'tariff_col_000001','visible'=>'1','label'=>array('fr'=>'Tarif')))),
        'individual'=>array(array('enabled'=>'1','label'=>array('fr'=>'Individuel à ne pas afficher'),'cells'=>array('price'=>array('value'=>'99 €')))),
        'groups'=>array(
            array('id'=>'tariff_row_000001','enabled'=>'1','label'=>array('fr'=>'Senior'),'subtitle'=>array('fr'=>'Groupe senior'),'cells'=>array('tariff_col_000001'=>array('value'=>'9 €','old_value'=>''))),
            array('id'=>'tariff_row_000002','enabled'=>'0','label'=>array('fr'=>'Ligne masquée'),'cells'=>array('tariff_col_000001'=>array('value'=>'1 €','old_value'=>''))),
        ),
    ),
);
$GLOBALS['parcs_ht_test_group_display'] = array(
    'show_heading'=>'1','title'=>array('fr'=>'Tarifs groupes personnalisés'),'intro'=>array('fr'=>'Introduction personnalisable.'),
    'show_payment_methods'=>'1','payment_title'=>array('fr'=>'Comment régler ?'),
    'payment_methods'=>array(
        array('enabled'=>'1','icon'=>'card','label'=>array('fr'=>'Carte test')),
        array('enabled'=>'1','icon'=>'bank','label'=>array('fr'=>'Virement test')),
    ),
    'show_info_blocks'=>'1','info_blocks'=>array(
        array('enabled'=>'1','title'=>array('fr'=>'Conditions test'),'text'=>array('fr'=>'Texte entièrement configurable.')),
    ),
    'show_quote_button'=>'1','button_label'=>array('fr'=>'Demander maintenant'),'button_url'=>array('fr'=>'https://example.test/custom'),
);

$html = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert($GLOBALS['parcs_ht_tariff_selector_called'] > 0, 'public tariff season selector is used');
group_runtime_assert(strpos($html, 'Tarifs groupes personnalisés') !== false, 'custom heading is rendered');
group_runtime_assert(strpos($html, 'Introduction personnalisable.') !== false, 'custom intro is rendered');
group_runtime_assert(strpos($html, 'Senior') !== false && strpos($html, '9 €') !== false, 'canonical group row and price are rendered');
group_runtime_assert(strpos($html, 'Individuel à ne pas afficher') === false && strpos($html, 'Ligne masquée') === false, 'non-group and disabled rows stay hidden');
group_runtime_assert(strpos($html, 'Comment régler ?') !== false && strpos($html, 'Carte test') !== false && strpos($html, 'Virement test') !== false, 'configured payment methods are rendered');
group_runtime_assert(strpos($html, 'Conditions test') !== false && strpos($html, 'Texte entièrement configurable.') !== false, 'configured information block is rendered');
group_runtime_assert(strpos($html, 'https://example.test/custom') !== false && strpos($html, 'Demander maintenant') !== false, 'configured quote button is rendered');
$payment_pos = strpos($html, 'parcs-ht-group-payment-strip');$prices_pos = strpos($html, 'parcs-ht-price-list');$info_pos = strpos($html, 'Conditions test');
group_runtime_assert($payment_pos !== false && $prices_pos !== false && $payment_pos < $prices_pos && $info_pos > $prices_pos, 'visual order is payments then prices then information');

// Le site_type ne pilote plus ce contenu : chaque installation affiche uniquement ses propres réglages.
$GLOBALS['parcs_ht_test_settings']['site_type'] = 'other';
$GLOBALS['parcs_ht_test_group_display']['payment_methods'][0]['label']['fr'] = 'Paiement autre site';
$html_other = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_other, 'Paiement autre site') !== false, 'another site can use its own configured payment method');
group_runtime_assert(strpos($html_other, 'Carte test') === false, 'presentation updates are read directly from site settings');

$GLOBALS['parcs_ht_test_group_display']['show_payment_methods'] = '0';
$GLOBALS['parcs_ht_test_group_display']['show_info_blocks'] = '0';
$html_minimal = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_minimal, 'parcs-ht-group-payment-strip') === false, 'payment section can be disabled');
group_runtime_assert(strpos($html_minimal, 'Conditions test') === false, 'information section can be disabled');
group_runtime_assert(strpos($html_minimal, 'Note générique de réservation.') !== false, 'generic booking note remains as fallback when custom information is disabled');

$GLOBALS['parcs_ht_test_settings']['tariffs']['groups'][0]['cells']['tariff_col_000001']['value'] = '10 €';
$html_updated = Parcs_HT_Group_Tariffs::render('fr');
group_runtime_assert(strpos($html_updated, '10 €') !== false && strpos($html_updated, '9 €') === false, 'updated canonical group price is read directly');

$source = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
group_runtime_assert(strpos($source, 'Parcs_HT_Defaults::settings()') !== false, 'shortcode reads canonical tariff settings');
group_runtime_assert(strpos($source, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') !== false, 'shortcode uses the same public season selection as the main tariff table');
group_runtime_assert(strpos($source, 'Parcs_HT_Group_Tariff_Settings::settings') !== false, 'shortcode reads separate presentation settings only');
group_runtime_assert(strpos($source, 'is_mds(') === false && strpos($source, 'Bon de commande / voucher') === false, 'renderer contains no MDS-specific display rule or payment text');

echo "Group tariff shortcode runtime: OK\n";
PHP;
write_file_checked($root . '/tests/group-tariff-shortcode-runtime.php', $runtime_test);

$contract_test = <<<'PHP'
<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$settings = file_get_contents($root . '/includes/class-parcs-ht-group-tariff-settings.php');
$seasons = file_get_contents($root . '/includes/class-parcs-ht-tariff-seasons.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-group-quotes.php');
$shortcode = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
$admin = file_get_contents($root . '/assets/admin-groups.js');

function group_publication_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

group_publication_check(strpos($settings, "const OPTION = 'parcs_ht_group_tariff_settings'") !== false, 'group publication and presentation keep their own settings store');
group_publication_check(strpos($settings, 'const STORE_VERSION = 2') !== false, 'group display settings store migrated to version 2');
group_publication_check(strpos($settings, 'public static function is_published') !== false, 'group publication exposes an explicit per-year status');
group_publication_check(strpos($settings, "(string)(\$season['published'] ?? '0') !== '1'") !== false, 'group tariffs can only publish inside a published season');
group_publication_check(strpos($seasons, 'hide_unpublished_groups') !== false && strpos($seasons, "\$tariffs['groups'] = array();") !== false, 'general public tariff rendering cannot expose unpublished group rates');
group_publication_check(strpos($quotes, 'Parcs_HT_Group_Tariff_Settings::is_published($year)') !== false, 'quote calculation requires group rates published for the visit year');

group_publication_check(strpos($shortcode, 'Parcs_HT_Defaults::settings()') !== false, 'dedicated group shortcode reads canonical tariff settings');
group_publication_check(strpos($shortcode, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') !== false, 'dedicated group shortcode uses the public tariff season selector');
group_publication_check(strpos($shortcode, "\$rows = isset(\$tariffs['groups'])") !== false, 'group prices still come from canonical tariffs.groups');
group_publication_check(strpos($shortcode, 'Parcs_HT_Group_Tariff_Settings::settings') !== false, 'presentation comes from the dedicated display settings');
group_publication_check(strpos($shortcode, 'is_mds(') === false && strpos($shortcode, 'site_type') === false, 'public renderer is site-agnostic');
group_publication_check(strpos($shortcode, 'Bon de commande / voucher') === false && strpos($shortcode, 'Chorus Pro') === false, 'public renderer contains no hardcoded MDS payment content');
group_publication_check(strpos($shortcode, "do_shortcode('[parc_tableau_tarifs") === false, 'dedicated group shortcode renders its own visual instead of hiding the full tariff table');

group_publication_check(strpos($settings, "'payment_methods'=>array()") !== false && strpos($settings, "'info_blocks'=>array()") !== false, 'generic installations start with editable empty presentation lists');
group_publication_check(strpos($settings, 'legacy_mds_payment_methods') !== false && strpos($settings, 'version < 2') !== false, '1.13.6 MDS content is preserved only through a one-time migration');
group_publication_check(strpos($admin, 'Ajouter un moyen de paiement') !== false, 'admin can add payment methods');
group_publication_check(strpos($admin, 'Ajouter un bloc d’information') !== false, 'admin can add information blocks');
group_publication_check(strpos($admin, "data-gt-move=\"up\"") !== false && strpos($admin, 'data-gt-remove') !== false, 'admin can reorder and remove configurable group content');
group_publication_check(strpos($admin, 'Montagne des Singes, à la Forêt des Singes ou sur un autre site') !== false, 'admin explains that presentation settings are installation-specific');

echo "Group tariff publication contract: OK\n";
PHP;
write_file_checked($root . '/tests/group-tariff-publication-contract.php', $contract_test);

$migration_test = <<<'PHP'
<?php

define('ABSPATH', __DIR__);
$GLOBALS['opts'] = array();
function add_action($hook, $callback, $priority = 10) {}
function current_user_can($cap) { return true; }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['opts']) ? $GLOBALS['opts'][$key] : $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['opts'][$key] = $value; return true; }
function wp_date($format) { return $format === 'Y' ? '2026' : '2026'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function sanitize_textarea_field($value) { return trim(strip_tags((string)$value)); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function esc_url_raw($value) { return (string)$value; }
function wp_json_encode($value) { return json_encode($value); }
final class Parcs_HT_Defaults { const OPTION = 'parcs_ht_settings'; }

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
require_once $root . '/includes/class-parcs-ht-group-tariff-settings.php';
function setting_assert($condition, $message) { if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); } echo '[OK] ' . $message . PHP_EOL; }

$GLOBALS['opts'][Parcs_HT_Defaults::OPTION] = array('site_type'=>'mds','general'=>array(),'seasons'=>array('2026'=>array('published'=>'1','tariffs'=>array('groups'=>array(array('enabled'=>'1'))))));
$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION] = array('version'=>1,'seasons'=>array('2026'=>array('published'=>'1','show_heading'=>'1','show_quote_button'=>'1')));
$mds = Parcs_HT_Group_Tariff_Settings::settings('2026');
setting_assert((int)$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION]['version'] === 2, 'legacy store is migrated to version 2');
setting_assert($mds['show_payment_methods'] === '1' && count($mds['payment_methods']) === 5, 'existing MDS 1.13.6 payment content is preserved by migration');
setting_assert($mds['show_info_blocks'] === '1' && count($mds['info_blocks']) === 2, 'existing MDS 1.13.6 information content is preserved by migration');

$GLOBALS['opts'][Parcs_HT_Defaults::OPTION] = array('site_type'=>'fds','general'=>array(),'seasons'=>array('2026'=>array('published'=>'1','tariffs'=>array('groups'=>array(array('enabled'=>'1'))))));
$GLOBALS['opts'][Parcs_HT_Group_Tariff_Settings::OPTION] = array('version'=>1,'seasons'=>array('2026'=>array('published'=>'1','show_heading'=>'1','show_quote_button'=>'1')));
$fds = Parcs_HT_Group_Tariff_Settings::settings('2026');
setting_assert($fds['show_payment_methods'] === '0' && $fds['payment_methods'] === array(), 'FDS does not inherit MDS payment rules');
setting_assert($fds['show_info_blocks'] === '0' && $fds['info_blocks'] === array(), 'FDS does not inherit MDS information rules');

$custom = $fds;
$custom['show_payment_methods'] = '1';
$custom['payment_title'] = array('fr'=>'Règlements FDS','en'=>'FDS payments','de'=>'FDS Zahlung');
$custom['payment_methods'] = array(array('enabled'=>'1','icon'=>'bank','label'=>array('fr'=>'Virement FDS','en'=>'FDS transfer','de'=>'FDS Überweisung')));
$custom['show_info_blocks'] = '1';
$custom['info_blocks'] = array(array('enabled'=>'1','title'=>array('fr'=>'Règle FDS'),'text'=>array('fr'=>'Texte FDS')));
setting_assert(Parcs_HT_Group_Tariff_Settings::save('2026', $custom), 'custom site presentation settings save successfully');
$saved = Parcs_HT_Group_Tariff_Settings::settings('2026');
setting_assert($saved['payment_methods'][0]['label']['fr'] === 'Virement FDS', 'custom payment method is stored per installation');
setting_assert($saved['info_blocks'][0]['text']['fr'] === 'Texte FDS', 'custom information block is stored per installation');

echo "Group tariff display settings runtime: OK\n";
PHP;
write_file_checked($root . '/tests/group-tariff-display-settings-runtime.php', $migration_test);

// Version metadata.
replace_exact_checked($root . '/horaires-tarifs-parc.php', 'Version: 1.13.6', 'Version: 1.13.7');
replace_exact_checked($root . '/horaires-tarifs-parc.php', "define('PARCS_HT_VERSION', '1.13.6');", "define('PARCS_HT_VERSION', '1.13.7');");
replace_exact_checked($root . '/readme.txt', 'Stable tag: 1.13.6', 'Stable tag: 1.13.7');

$changelog = $root . '/CHANGELOG.md';
$entry = <<<'MD'
## 1.13.7
- Généralisation complète du contenu public du shortcode `[parc_tarifs_groupes]` : les moyens de paiement et les blocs d’information ne dépendent plus de `site_type` dans le rendu.
- Ajout dans Groupes → Tarifs de réglages modifiables par saison et par installation pour activer/masquer les moyens de paiement, modifier leur titre, ajouter/supprimer/réordonner les moyens, choisir une icône et saisir les libellés FR/EN/DE.
- Ajout d’une liste libre de blocs d’information sous les tarifs, chacun activable, supprimable, réordonnable et entièrement éditable en FR/EN/DE.
- Le titre, l’introduction et le bouton de devis du shortcode utilisent désormais réellement les réglages d’affichage groupes existants, sans créer de deuxième source de tarifs.
- Migration automatique du contenu MDS introduit en 1.13.6 vers les nouveaux réglages éditables afin de conserver l’affichage actuel après mise à jour.
- Les installations FDS et les autres sites ne reçoivent aucune condition MDS par défaut ; ils peuvent définir leurs propres moyens de paiement et informations.
- Les prix restent exclusivement issus de la grille canonique `tariffs.groups` de la saison publique ; les réglages d’affichage ne contiennent aucun prix.
- Ajout de tests couvrant la migration MDS, l’absence de fuite vers FDS, la personnalisation d’un autre site, l’ordre d’affichage et la source tarifaire unique.

MD;
$content = file_get_contents($changelog);
if (strpos($content, '## 1.13.7') === false) {
    replace_exact_checked($changelog, "# Historique des versions\n\n", "# Historique des versions\n\n" . $entry);
}

echo "Prepared 1.13.7\n";
