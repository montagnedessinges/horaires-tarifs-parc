<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Module CSE indépendant : aucune réécriture des tarifs visiteurs/groupes.
 * Les données sont isolées par année et par installation WordPress (donc par parc).
 */
final class Parcs_HT_CSE {
    const OPTION = 'parcs_ht_cse_settings';
    const PAGE = 'parcs-ht-cse';
    private static $instance = 0;
    private static $printed_styles = false;

    public static function init() {
        foreach (array('parc_tarifs_cse', 'parcs_tarifs_cse', 'parc_lien_cse') as $base) {
            add_shortcode($base, array(__CLASS__, $base === 'parc_lien_cse' ? 'shortcode_link' : 'shortcode'));
            foreach (array('fr','en','de') as $lang) {
                add_shortcode($base . '_' . $lang, static function ($atts = array()) use ($base, $lang) {
                    return $base === 'parc_lien_cse'
                        ? Parcs_HT_CSE::render_link($lang)
                        : Parcs_HT_CSE::render($lang, is_array($atts) ? $atts : array());
                });
            }
        }
        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'admin_menu'), 63);
            add_action('admin_post_parcs_ht_save_cse', array(__CLASS__, 'save'));
        }
    }

    private static function t($lang, $fr, $en, $de) {
        return $lang === 'en' ? $en : ($lang === 'de' ? $de : $fr);
    }

    private static function language($lang) {
        return in_array($lang, array('fr','en','de'), true) ? $lang : 'fr';
    }

    private static function tr($values, $lang, $fallback = '') {
        $values = is_array($values) ? $values : array();
        $localized = trim((string)($values[$lang] ?? ''));
        if ($localized !== '') return $localized;
        $french = trim((string)($values['fr'] ?? ''));
        return $french !== '' ? $french : $fallback;
    }

    private static function initial($year) {
        return array(
            'enabled'=>'0', 'show_table'=>'1', 'show_public'=>'1',
            'show_login'=>'1', 'show_signup'=>'1', 'show_online'=>'1',
            'show_pdf'=>'1', 'show_transition'=>'1', 'show_link'=>'0',
            'page_url'=>array('fr'=>'','en'=>'','de'=>''),
            'login_url'=>array('fr'=>'','en'=>'','de'=>''),
            'signup_url'=>array('fr'=>'','en'=>'','de'=>''),
            'online_url'=>array('fr'=>'','en'=>'','de'=>''),
            'pdf_url'=>array('fr'=>'','en'=>'','de'=>''),
            'contact_email'=>'',
            'intro'=>array('fr'=>'','en'=>'','de'=>''),
            'transition'=>array(
                'fr'=>'Notre billetterie CSE dématérialisée est déjà disponible. Après commande, vos billets peuvent être téléchargés en PDF prêts à l’emploi ou sous forme de codes à intégrer à vos propres supports de billetterie. Pour vous laisser le temps de vous adapter, nous maintenons temporairement le bon de commande PDF. À terme, les commandes seront exclusivement en ligne.',
                'en'=>'Our digital CSE ticketing is already available. Tickets can be downloaded as ready-to-use PDFs or as codes to add to your own tickets. The PDF order form remains temporarily available to give you time to adapt. Eventually, orders will be online only.',
                'de'=>'Unser digitales CSE-Ticketsystem ist bereits verfügbar. Tickets können als fertige PDFs oder als Codes für Ihre eigenen Tickets heruntergeladen werden. Das PDF-Bestellformular bleibt für eine Übergangszeit verfügbar. Später erfolgen Bestellungen ausschließlich online.',
            ),
            'rows'=>array(
                array('enabled'=>'1','label'=>array('fr'=>'Adulte','en'=>'Adult','de'=>'Erwachsene'),'cse_price'=>'','public_row_id'=>''),
                array('enabled'=>'1','label'=>array('fr'=>'Enfant','en'=>'Child','de'=>'Kind'),'cse_price'=>'','public_row_id'=>''),
            ),
        );
    }

    public static function all() {
        $raw = get_option(self::OPTION, array());
        return is_array($raw) ? $raw : array();
    }

    public static function year_settings($year) {
        $raw = self::all();
        $saved = isset($raw[$year]) && is_array($raw[$year]) ? $raw[$year] : array();
        return array_replace(self::initial($year), $saved);
    }

    private static function years() {
        $settings = Parcs_HT_Defaults::all_settings();
        $years = array();
        foreach (array_keys((array)($settings['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) $years[] = (string)$year;
        }
        sort($years, SORT_STRING);
        return $years;
    }

    private static function chosen_year() {
        $years = self::years();
        $request = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection de l'année seulement
        if (in_array($request, $years, true)) return $request;
        $current = wp_date('Y');
        if (in_array($current, $years, true)) return $current;
        return $years ? (string)end($years) : '';
    }

    private static function public_rows($year) {
        $settings = Parcs_HT_Defaults::all_settings();
        return (array)($settings['seasons'][$year]['tariffs']['individual'] ?? array());
    }

    private static function source_options($year) {
        $out = array();
        foreach (self::public_rows($year) as $row) {
            if (!is_array($row)) continue;
            $id = sanitize_key((string)($row['id'] ?? ''));
            if ($id === '' || !preg_match('/^tariff_row_[0-9]{6,}$/', $id)) continue;
            $label = self::tr($row['label'] ?? array(), 'fr', $id);
            $detail = self::tr($row['subtitle'] ?? ($row['detail'] ?? array()), 'fr', '');
            $out[$id] = trim($label . ($detail !== '' ? ' — ' . $detail : ''));
        }
        return $out;
    }

    private static function source_price($year, $row_id) {
        if (!preg_match('/^tariff_row_[0-9]{6,}$/', (string)$row_id)) return '';
        if (class_exists('Parcs_HT_Public_Visibility') && !in_array((string)$year, Parcs_HT_Public_Visibility::retail_years(), true)) return '';
        $all = Parcs_HT_Defaults::all_settings();
        $tariffs = (array)($all['seasons'][$year]['tariffs'] ?? array());
        $columns = (array)($tariffs['columns']['individual'] ?? array());
        $onsite = array();
        $available = array();
        foreach ($columns as $column) {
            if (!is_array($column) || (string)($column['visible'] ?? '1') === '0') continue;
            $id = sanitize_key((string)($column['id'] ?? ''));
            if ($id === '') continue;
            $available[] = $id;
            $label = strtolower(remove_accents(self::tr($column['label'] ?? array(), 'fr', '')));
            if (strpos($label, 'sur place') !== false || strpos($label, 'onsite') !== false || strpos($label, 'guichet') !== false) $onsite[] = $id;
        }
        // Une colonne unique constitue le tarif public de référence. Pour plusieurs
        // canaux, seule une colonne explicitement « sur place » est sûre.
        $ids = $onsite ? $onsite : (count($available) === 1 ? $available : array());
        foreach (self::public_rows($year) as $row) {
            if (!is_array($row) || (string)($row['id'] ?? '') !== $row_id || (string)($row['enabled'] ?? '0') !== '1') continue;
            $cells = (array)($row['cells'] ?? array());
            foreach (array_merge($ids, array('onsite','price')) as $id) {
                if (isset($cells[$id]['value']) && trim((string)$cells[$id]['value']) !== '') return trim((string)$cells[$id]['value']);
            }
            // « price » est le stockage historique des anciennes saisons mono-colonne.
            if (!$cells || count($available) <= 1) return trim((string)($row['price'] ?? ''));
            return '';
        }
        return '';
    }

    private static function numeric_price($text) {
        if (!preg_match('/\d+(?:[.,]\d{1,2})?/', (string)$text, $match)) return null;
        return (float)str_replace(',', '.', $match[0]);
    }

    private static function localized_urls($raw) {
        $out = array();
        foreach (array('fr','en','de') as $lang) {
            $out[$lang] = esc_url_raw(is_array($raw) && is_scalar($raw[$lang] ?? null) ? (string)$raw[$lang] : '');
        }
        return $out;
    }

    private static function translations($raw, $multiline = false) {
        $out = array();
        foreach (array('fr','en','de') as $lang) {
            $value = is_array($raw) && is_scalar($raw[$lang] ?? null) ? (string)$raw[$lang] : '';
            $out[$lang] = $multiline ? sanitize_textarea_field($value) : sanitize_text_field($value);
        }
        return $out;
    }

    /** Sauvegarde exclusive de l'année CSE ciblée ; les autres années restent intactes. */
    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.', '', array('response'=>403));
        $year = isset($_POST['season']) ? sanitize_text_field(wp_unslash($_POST['season'])) : '';
        if (!preg_match('/^20\d{2}$/', $year) || !in_array($year, self::years(), true)) wp_die('Année inconnue.', '', array('response'=>400));
        check_admin_referer('parcs_ht_save_cse_' . $year);
        $raw = isset($_POST['cse']) && is_array($_POST['cse']) ? wp_unslash($_POST['cse']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Formulaire protégé par nonce et capacité, chaque champ est ensuite validé et assaini selon son type.
        $old = self::year_settings($year);
        $next = $old;
        foreach (array('enabled','show_table','show_public','show_login','show_signup','show_online','show_pdf','show_transition','show_link') as $flag) {
            $next[$flag] = isset($raw[$flag]) && (string)$raw[$flag] === '1' ? '1' : '0';
        }
        foreach (array('page_url','login_url','signup_url','online_url','pdf_url') as $key) {
            $next[$key] = self::localized_urls($raw[$key] ?? array());
        }
        $next['contact_email'] = sanitize_email(is_scalar($raw['contact_email'] ?? null) ? (string)$raw['contact_email'] : '');
        $next['intro'] = self::translations($raw['intro'] ?? array(), true);
        $next['transition'] = self::translations($raw['transition'] ?? array(), true);

        $allowed_sources = self::source_options($year);
        if (isset($_POST['cse_rows_present'])) {
            $next['rows'] = array();
            foreach (array_slice((array)($raw['rows'] ?? array()), 0, 30) as $item) {
                if (!is_array($item) || !empty($item['delete'])) continue;
                $label = self::translations($item['label'] ?? array());
                $price = sanitize_text_field(is_scalar($item['cse_price'] ?? null) ? (string)$item['cse_price'] : '');
                $source = sanitize_key(is_scalar($item['public_row_id'] ?? null) ? (string)$item['public_row_id'] : '');
                if (!isset($allowed_sources[$source])) $source = '';
                if ($label['fr'] === '' && $label['en'] === '' && $label['de'] === '' && $price === '') continue;
                $next['rows'][] = array(
                    'enabled'=>isset($item['enabled']) && (string)$item['enabled'] === '1' ? '1' : '0',
                    'label'=>$label, 'cse_price'=>$price, 'public_row_id'=>$source,
                );
            }
        }
        $all = self::all();
        $all[$year] = $next;
        update_option(self::OPTION, $all, false);
        // Vérification explicite de la persistance avant message de succès.
        $saved = get_option(self::OPTION, array());
        $ok = is_array($saved) && isset($saved[$year]) && $saved[$year] === $next;
        $url = add_query_arg(array('page'=>self::PAGE,'season'=>$year,'cse_saved'=>$ok ? '1' : '0'), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    public static function admin_menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(Parcs_HT_Admin::PAGE, 'Tarifs CSE', 'Tarifs CSE', 'manage_options', self::PAGE, array(__CLASS__, 'admin_page'));
    }

    private static function field($key, $label, $values, $type = 'url') {
        echo '<fieldset class="htp-cse-field"><legend><strong>' . esc_html($label) . '</strong></legend>';
        foreach (array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$name) {
            $value = (string)($values[$lang] ?? '');
            echo '<label>' . esc_html($name) . ' ';
            if ($type === 'textarea') {
                echo '<textarea rows="3" name="cse[' . esc_attr($key) . '][' . esc_attr($lang) . ']">' . esc_textarea($value) . '</textarea>';
            } else {
                echo '<input type="url" name="cse[' . esc_attr($key) . '][' . esc_attr($lang) . ']" value="' . esc_attr($value) . '" placeholder="https://">';
            }
            echo '</label>';
        }
        echo '</fieldset>';
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) return;
        $year = self::chosen_year();
        $settings = $year !== '' ? self::year_settings($year) : array();
        echo '<div class="wrap htp-cse-admin"><h1>Tarifs et billetterie CSE</h1><p>Offre indépendante des tarifs visiteurs et groupes. Chaque année et chaque parc possèdent leurs propres réglages.</p>';
        if ($year === '') {
            echo '<p>Créez d’abord une année dans Administration générale → Saisons.</p></div>';
            return;
        }
        if (class_exists('Parcs_HT_Admin_Navigation')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year);
        if (isset($_GET['cse_saved'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- indicateur d'état de sauvegarde en lecture seule
            echo '<div class="notice ' . (sanitize_text_field(wp_unslash($_GET['cse_saved'])) === '1' ? 'notice-success' : 'notice-error') . '"><p>' . (sanitize_text_field(wp_unslash($_GET['cse_saved'])) === '1' ? 'Réglages CSE enregistrés.' : 'La sauvegarde n’a pas pu être confirmée.') . '</p></div>';
        }
        echo '<style>.htp-cse-admin{max-width:1200px}.htp-cse-admin .htp-cse-section{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:20px;margin:18px 0}.htp-cse-admin .htp-cse-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}.htp-cse-admin .htp-cse-field{margin:12px 0;border:1px solid #ddd;padding:10px}.htp-cse-admin .htp-cse-field label{display:block;margin:7px 0}.htp-cse-admin input[type=url],.htp-cse-admin textarea{width:100%;max-width:600px}.htp-cse-admin .htp-cse-table input[type=text]{width:100%;min-width:105px}.htp-cse-admin .htp-cse-table select{max-width:260px}.htp-cse-admin .htp-cse-table th{white-space:nowrap}.htp-cse-admin .htp-cse-flags label{display:block;margin:8px 0}@media(max-width:780px){.htp-cse-admin .htp-cse-table{display:block;overflow:auto}}</style>';
        echo '<p><code>[parc_tarifs_cse]</code> (alias : <code>[parcs_tarifs_cse]</code>) — versions FR/EN/DE disponibles. <code>[parc_lien_cse]</code> affiche seulement le renvoi vers la page CSE.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="parcs_ht_save_cse"><input type="hidden" name="season" value="' . esc_attr($year) . '">';
        wp_nonce_field('parcs_ht_save_cse_' . $year);
        echo '<section class="htp-cse-section"><h2>Visibilité — ' . esc_html($year) . '</h2><div class="htp-cse-grid htp-cse-flags">';
        $flags = array(
            'enabled'=>'Publier l’offre CSE pour cette année',
            'show_table'=>'Afficher le tableau des tarifs',
            'show_public'=>'Afficher la colonne tarif public barré',
            'show_login'=>'Afficher le bouton de connexion',
            'show_signup'=>'Afficher le bouton de création de compte',
            'show_online'=>'Afficher le bouton billetterie en ligne',
            'show_pdf'=>'Afficher le téléchargement du bon de commande PDF',
            'show_transition'=>'Afficher le message de transition numérique',
            'show_link'=>'Afficher un renvoi CSE sous les tarifs Groupes de la page Horaires et Tarifs',
        );
        foreach ($flags as $flag=>$label) {
            echo '<label><input type="checkbox" name="cse[' . esc_attr($flag) . ']" value="1" ' . checked((string)($settings[$flag] ?? '0'), '1', false) . '> ' . esc_html($label) . '</label>';
        }
        echo '</div></section>';
        echo '<section class="htp-cse-section"><h2>Tarifs CSE</h2><p>Le tarif public est issu de la ligne correspondante des tarifs individuels de ' . esc_html($year) . '. Seuls les prix réellement disponibles sont affichés. Pour ajouter une ligne, remplissez une ligne vide ; pour la supprimer, cochez Supprimer.</p>';
        echo '<input type="hidden" name="cse_rows_present" value="1"><table class="widefat striped htp-cse-table"><thead><tr><th>Afficher</th><th>Billet FR</th><th>EN</th><th>DE</th><th>Tarif CSE</th><th>Tarif public (source)</th><th>Supprimer</th></tr></thead><tbody>';
        $rows = array_values((array)($settings['rows'] ?? array()));
        for ($j=0;$j<3;$j++) $rows[] = array('label'=>array('fr'=>'','en'=>'','de'=>''),'cse_price'=>'','public_row_id'=>'','enabled'=>'1');
        $sources = self::source_options($year);
        foreach ($rows as $index=>$row) {
            $labels = (array)($row['label'] ?? array());
            echo '<tr><td><input type="checkbox" name="cse[rows][' . (int)$index . '][enabled]" value="1" ' . checked((string)($row['enabled'] ?? '1'), '1', false) . '></td>';
            foreach (array('fr','en','de') as $lang) {
                echo '<td><input type="text" name="cse[rows][' . (int)$index . '][label][' . esc_attr($lang) . ']" value="' . esc_attr($labels[$lang] ?? '') . '"></td>';
            }
            echo '<td><input type="text" name="cse[rows][' . (int)$index . '][cse_price]" value="' . esc_attr($row['cse_price'] ?? '') . '" placeholder="10,50 €"></td>';
            echo '<td><select name="cse[rows][' . (int)$index . '][public_row_id]"><option value="">— Ne pas comparer —</option>';
            foreach ($sources as $id=>$label) echo '<option value="' . esc_attr($id) . '"' . selected((string)($row['public_row_id'] ?? ''), $id, false) . '>' . esc_html($label) . '</option>';
            echo '</select></td><td><input type="checkbox" name="cse[rows][' . (int)$index . '][delete]" value="1" aria-label="Supprimer cette ligne"></td></tr>';
        }
        echo '</tbody></table></section>';
        echo '<section class="htp-cse-section"><h2>Parcours de commande et boutons</h2><p>Si le lien d’une langue est vide, le lien français est utilisé. Aucun lien n’est inventé. Séparez bien connexion, inscription, billetterie et bon de commande.</p>';
        self::field('page_url', 'Adresse de la page CSE (pour le renvoi public)', $settings['page_url']);
        self::field('login_url', 'Connexion au compte CSE', $settings['login_url']);
        self::field('signup_url', 'Création de compte', $settings['signup_url']);
        self::field('online_url', 'Billetterie dématérialisée', $settings['online_url']);
        self::field('pdf_url', 'Bon de commande PDF (temporaire)', $settings['pdf_url']);
        echo '<label><strong>Adresse e-mail pour l’activation des comptes CSE</strong> <input type="email" name="cse[contact_email]" value="' . esc_attr($settings['contact_email']) . '" class="regular-text"></label>';
        echo '</section><section class="htp-cse-section"><h2>Textes de la page CSE</h2>';
        self::field('intro', 'Introduction facultative', $settings['intro'], 'textarea');
        self::field('transition', 'Message sur la billetterie dématérialisée', $settings['transition'], 'textarea');
        echo '</section>';
        submit_button('Enregistrer les réglages CSE ' . $year);
        echo '</form></div>';
    }

    private static function assets() {
        wp_enqueue_style('parcs-ht-cse', PARCS_HT_URL . 'assets/cse.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-tariff-ui', PARCS_HT_URL . 'assets/tariffs-ui.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-tariff-ui', PARCS_HT_URL . 'assets/tariffs-ui.js', array(), PARCS_HT_VERSION, true);
        if (!self::$printed_styles && did_action('wp_head')) {
            self::$printed_styles = true;
            ob_start();
            wp_print_styles(array('parcs-ht-cse','parcs-ht-tariff-ui'));
            return ob_get_clean();
        }
        return '';
    }

    private static function url($values, $lang) {
        $url = self::tr($values, $lang);
        return $url !== '' ? esc_url($url) : '';
    }

    private static function action_button($url, $text, $secondary = false) {
        return $url === '' ? '' : '<a class="parcs-ht-cse__button' . ($secondary ? ' is-secondary' : '') . '" href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
    }

    private static function enabled_years() {
        $all = self::all();
        $out = array();
        foreach (self::years() as $year) {
            if ((string)($all[$year]['enabled'] ?? '0') === '1' &&
                (!class_exists('Parcs_HT_Public_Visibility') || Parcs_HT_Public_Visibility::in_window($year))) $out[] = $year;
        }
        return $out;
    }

    public static function shortcode($atts = array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function shortcode_link($atts = array()) {
        return self::render_link(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    private static function render_year($lang, $year) {
        $s = self::year_settings($year);
        $html = '<section class="parcs-ht-cse__year"><h2>' . esc_html(self::t($lang, 'Tarifs CSE ', 'CSE rates ', 'CSE-Tarife ') . $year) . '</h2>';
        $intro = self::tr($s['intro'], $lang);
        if ($intro !== '') $html .= '<p>' . nl2br(esc_html($intro)) . '</p>';
        $rows = array();
        foreach ((array)($s['rows'] ?? array()) as $row) {
            if (!is_array($row) || (string)($row['enabled'] ?? '0') !== '1' || trim((string)($row['cse_price'] ?? '')) === '') continue;
            $rows[] = $row;
        }
        if ($s['show_table'] === '1' && $rows) {
            $html .= '<div class="parcs-ht-cse__table-wrap"><table class="parcs-ht-cse__table"><thead><tr><th scope="col">' . esc_html(self::t($lang, 'Billet', 'Ticket', 'Ticket')) . '</th><th scope="col">' . esc_html(self::t($lang, 'Tarif CSE', 'CSE rate', 'CSE-Preis')) . '</th>';
            if ($s['show_public'] === '1') $html .= '<th scope="col">' . esc_html(self::t($lang, 'Tarif public', 'Public rate', 'Regulärer Preis')) . '</th>';
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $name = self::tr($row['label'] ?? array(), $lang, self::t($lang, 'Billet', 'Ticket', 'Ticket'));
                $html .= '<tr><th scope="row">' . esc_html($name) . '</th><td><strong>' . esc_html((string)$row['cse_price']) . '</strong></td>';
                if ($s['show_public'] === '1') {
                    $public = self::source_price($year, (string)($row['public_row_id'] ?? ''));
                    $comparison = '—';
                    if ($public !== '') {
                        $public_amount = self::numeric_price($public);
                        $cse_amount = self::numeric_price($row['cse_price']);
                        // Ne pas barrer un tarif public inférieur ou égal au tarif CSE.
                        $comparison = $public_amount !== null && $cse_amount !== null && $public_amount > $cse_amount
                            ? '<del>' . esc_html($public) . '</del>'
                            : esc_html($public);
                    }
                    $html .= '<td>' . $comparison . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '<div class="parcs-ht-cse__paths"><div class="parcs-ht-cse__path"><h3>' . esc_html(self::t($lang, 'Vous avez déjà un compte CSE ?', 'Already have a CSE account?', 'Sie haben bereits ein CSE-Konto?')) . '</h3>';
        $html .= '<p>' . esc_html(self::t($lang, 'Connectez-vous avec vos identifiants habituels. Si votre statut CSE est activé, vos tarifs professionnels sont accessibles.', 'Sign in using your usual credentials. If your CSE status is active, you can access your professional rates.', 'Melden Sie sich mit Ihren Zugangsdaten an. Nach Freischaltung des CSE-Status sehen Sie Ihre CSE-Tarife.')) . '</p>';
        if ($s['show_login'] === '1') $html .= self::action_button(self::url($s['login_url'], $lang), self::t($lang, 'Me connecter', 'Sign in', 'Anmelden'));
        $html .= '</div><div class="parcs-ht-cse__path"><h3>' . esc_html(self::t($lang, 'Vous n’avez pas encore de compte ?', 'New to CSE ticketing?', 'Noch kein CSE-Konto?')) . '</h3>';
        $html .= '<p>' . esc_html(self::t($lang, 'Créez votre compte sur la billetterie, puis demandez par e-mail l’activation de votre statut CSE. Une fois celui-ci activé, reconnectez-vous pour commander.', 'Create an account on the ticketing website, then email us to request CSE activation. Once activated, sign back in to order.', 'Erstellen Sie ein Konto im Ticketshop und beantragen Sie per E-Mail die CSE-Freischaltung. Melden Sie sich danach an, um zu bestellen.')) . '</p>';
        if ($s['show_signup'] === '1') $html .= self::action_button(self::url($s['signup_url'], $lang), self::t($lang, 'Créer mon compte', 'Create an account', 'Konto erstellen'));
        $email = sanitize_email((string)$s['contact_email']);
        if ($email !== '') $html .= '<p class="parcs-ht-cse__contact">' . esc_html(self::t($lang, 'Activation CSE : ', 'CSE activation: ', 'CSE-Freischaltung: ')) . '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
        $html .= '</div></div>';
        $html .= '<div class="parcs-ht-cse__actions">';
        if ($s['show_online'] === '1') $html .= self::action_button(self::url($s['online_url'], $lang), self::t($lang, 'Accéder à la billetterie CSE', 'Open CSE ticketing', 'Zum CSE-Ticketshop'));
        if ($s['show_pdf'] === '1') $html .= self::action_button(self::url($s['pdf_url'], $lang), self::t($lang, 'Télécharger le bon de commande PDF', 'Download PDF order form', 'PDF-Bestellformular herunterladen'), true);
        $html .= '</div>';
        if ($s['show_transition'] === '1') {
            $message = self::tr($s['transition'], $lang);
            if ($message !== '') $html .= '<aside class="parcs-ht-cse__notice"><strong>' . esc_html(self::t($lang, 'La billetterie CSE évolue !', 'CSE ticketing is evolving!', 'Das CSE-Ticketsystem entwickelt sich weiter!')) . '</strong><p>' . nl2br(esc_html($message)) . '</p></aside>';
        }
        return $html . '</section>';
    }

    public static function render($lang, $atts = array()) {
        $lang = self::language($lang);
        $years = self::enabled_years();
        $requested = isset($atts['annee']) ? sanitize_text_field((string)$atts['annee']) : '';
        if ($requested !== '') $years = in_array($requested, $years, true) ? array($requested) : array();
        if (!$years) return '';
        $selected = in_array(wp_date('Y'), $years, true) ? wp_date('Y') : (string)end($years);
        self::$instance++;
        $id = 'parcs-ht-cse-' . self::$instance;
        $html = self::assets() . '<div class="parcs-ht-cse" id="' . esc_attr($id) . '" data-htp-ui-years>';
        if (count($years) > 1) {
            $html .= '<div class="parcs-ht-tariff-year-tabs-ui" role="tablist" aria-label="' . esc_attr(self::t($lang, 'Année des tarifs CSE', 'CSE rate year', 'CSE-Tarifjahr')) . '">';
            foreach ($years as $year) $html .= '<button type="button" class="parcs-ht-tariff-year-tab-ui' . ($selected === $year ? ' is-active' : '') . '" role="tab" data-htp-ui-year="' . esc_attr($year) . '" aria-selected="' . ($selected === $year ? 'true' : 'false') . '">' . esc_html($year) . '</button>';
            $html .= '</div>';
        }
        foreach ($years as $year) $html .= '<div class="parcs-ht-tariff-year-panel-ui" data-htp-ui-year-panel="' . esc_attr($year) . '"' . ($selected !== $year ? ' hidden' : '') . '>' . self::render_year($lang, $year) . '</div>';
        return $html . '</div>';
    }

    public static function render_link($lang, $atts = array()) {
        $lang = self::language($lang);
        $year = isset($atts['annee']) ? sanitize_text_field((string)$atts['annee']) : '';
        if ($year === '') {
            $years = self::enabled_years();
            $year = in_array(wp_date('Y'), $years, true) ? wp_date('Y') : ($years ? (string)end($years) : '');
        }
        if ($year === '' || !in_array($year, self::enabled_years(), true)) return '';
        $s = self::year_settings($year);
        if ($s['show_link'] !== '1') return '';
        $url = self::url($s['page_url'], $lang);
        if ($url === '') return '';
        $html = self::assets();
        return $html . '<div class="parcs-ht-cse-referral"><strong>' .
            esc_html(self::t($lang, 'Vous représentez un CSE ou une amicale du personnel ?', 'Do you represent a works council or staff association?', 'Vertreten Sie einen Betriebsrat oder eine Personalvereinigung?')) .
            '</strong><p>' .
            esc_html(self::t($lang, 'Découvrez notre offre de billetterie réservée aux professionnels.', 'Discover our dedicated professional ticketing offer.', 'Entdecken Sie unser Ticketangebot für berufliche Organisationen.')) .
            '</p><a href="' . esc_url($url) . '">' . esc_html(self::t($lang, 'Découvrir les offres CSE', 'Discover CSE offers', 'CSE-Angebote entdecken')) . '</a></div>';
    }
}
