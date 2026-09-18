<?php

if (!defined('ABSPATH')) { exit; }

/** Écran métier Horaires & calendrier introduit en 1.17.3. */
final class Parcs_HT_Admin_Schedule {
    const PAGE = 'parcs-ht-schedule';

    public static function init() {
        add_action('admin_post_parcs_ht_save_schedule_1173', array(__CLASS__, 'save'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
    }

    private static function selected_year($all = null) {
        if ($all === null) $all = Parcs_HT_Defaults::all_settings();
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        if ($requested !== '' && preg_match('/^20\d{2}$/', $requested) && isset($all['seasons'][$requested])) return $requested;
        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? '');
        if ($active !== '' && isset($all['seasons'][$active])) return $active;
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) if (preg_match('/^20\d{2}$/', (string)$year)) return (string)$year;
        return '';
    }

    public static function assets() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d’écran en lecture seule.
        if ($page !== self::PAGE) return;
        wp_enqueue_style('parcs-ht-admin-schedule-1173', PARCS_HT_URL . 'assets/admin-schedule-1173.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-admin-schedule-1173', PARCS_HT_URL . 'assets/admin-schedule-1173.js', array(), PARCS_HT_VERSION, true);
    }

    private static function field($name, $value, $label, $type = 'text', $attrs = '') {
        echo '<label class="htp-1173-field"><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string)$value) . '" ' . $attrs . '></label>';
    }

    private static function select($name, $value, $label, $options) {
        echo '<label class="htp-1173-field"><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '">';
        foreach ($options as $key => $text) echo '<option value="' . esc_attr($key) . '" ' . selected((string)$value, (string)$key, false) . '>' . esc_html($text) . '</option>';
        echo '</select></label>';
    }

    private static function toggle($name, $value, $label) {
        echo '<label class="htp-1173-toggle"><input type="hidden" name="' . esc_attr($name) . '" value="0"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked((string)$value, '1', false) . '><span>' . esc_html($label) . '</span></label>';
    }

    private static function weekdays($base, $selected) {
        $selected = array_map('strval', is_array($selected) ? $selected : array());
        $labels = array('1'=>'Lun','2'=>'Mar','3'=>'Mer','4'=>'Jeu','5'=>'Ven','6'=>'Sam','7'=>'Dim');
        echo '<fieldset class="htp-1173-weekdays"><legend>Jours concernés</legend>';
        foreach ($labels as $day => $label) echo '<label><input type="checkbox" name="' . esc_attr($base . '[weekdays][]') . '" value="' . esc_attr($day) . '" ' . checked(in_array($day, $selected, true), true, false) . '><span>' . esc_html($label) . '</span></label>';
        echo '</fieldset>';
    }

    private static function period_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'enabled'=>'1','label'=>'','start'=>'','end'=>'','weekdays'=>array('1','2','3','4','5','6','7'),
            'open'=>'','close'=>'','open2'=>'','close2'=>'','last_entry_minutes'=>'','color'=>'#9AAA8B',
        ));
        $base = 'regular_periods[' . $index . ']';
        ?>
        <article class="htp-1173-period" data-htp-period-row>
            <header>
                <div><strong>Période d’ouverture</strong><small><?php echo esc_html($row['label'] !== '' ? $row['label'] : 'Sans nom interne'); ?></small></div>
                <div class="htp-1173-row-actions"><?php self::toggle($base . '[enabled]', $row['enabled'], 'Active'); ?><button type="button" class="button-link-delete" data-htp-remove-period>Supprimer</button></div>
            </header>
            <div class="htp-1173-grid htp-1173-grid-3">
                <?php self::field($base . '[label]', $row['label'], 'Nom interne'); ?>
                <?php self::field($base . '[start]', $row['start'], 'Du', 'date'); ?>
                <?php self::field($base . '[end]', $row['end'], 'Au', 'date'); ?>
            </div>
            <div class="htp-1173-slots">
                <div class="htp-1173-slot"><h4>Créneau 1</h4><div class="htp-1173-grid htp-1173-grid-2"><?php self::field($base . '[open]', $row['open'], 'Ouverture', 'time'); ?><?php self::field($base . '[close]', $row['close'], 'Fermeture', 'time'); ?></div></div>
                <div class="htp-1173-slot htp-1173-slot-optional"><h4>Créneau 2 <span>facultatif</span></h4><div class="htp-1173-grid htp-1173-grid-2"><?php self::field($base . '[open2]', $row['open2'], 'Réouverture', 'time'); ?><?php self::field($base . '[close2]', $row['close2'], 'Fermeture', 'time'); ?></div></div>
            </div>
            <details class="htp-1173-advanced">
                <summary>Options avancées de cette période</summary>
                <?php self::weekdays($base, $row['weekdays']); ?>
                <div class="htp-1173-grid htp-1173-grid-2">
                    <?php self::field($base . '[last_entry_minutes]', $row['last_entry_minutes'], 'Dernière entrée spécifique — minutes avant fermeture', 'number', 'min="0" max="1440"'); ?>
                    <?php self::field($base . '[color]', $row['color'], 'Couleur de la période dans le calendrier', 'color'); ?>
                </div>
                <p class="description">La couleur de période reste locale au calendrier : elle n’est pas remplacée par l’apparence globale.</p>
            </details>
        </article>
        <?php
    }

    private static function color_field($key, $general, $label, $fallback) {
        self::field('calendar[' . $key . ']', isset($general[$key]) ? $general[$key] : $fallback, $label, 'color');
    }

    private static function optional_color_field($key, $general, $label, $fallback = '') {
        $value = isset($general[$key]) ? $general[$key] : $fallback;
        self::field('calendar[' . $key . ']', $value, $label . ' (vide = thème)', 'text', 'placeholder="#000000" pattern="^$|#[0-9A-Fa-f]{6}$"');
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        if ($year === '' || !isset($all['seasons'][$year])) {
            echo '<div class="wrap"><h1>Horaires & calendrier</h1><div class="notice notice-warning"><p>Aucune saison disponible.</p></div></div>';
            return;
        }
        $season = isset($all['seasons'][$year]) && is_array($all['seasons'][$year]) ? $all['seasons'][$year] : array();
        $general = isset($all['general']) && is_array($all['general']) ? $all['general'] : array();
        $periods = isset($season['regular_periods']) && is_array($season['regular_periods']) ? $season['regular_periods'] : array();
        $content_url = add_query_arg(array('page'=>class_exists('Parcs_HT_Public_Content') ? Parcs_HT_Public_Content::PAGE : 'parcs-ht-public-content'), admin_url('admin.php'));
        ?>
        <div class="wrap htp-1173-schedule">
            <div class="htp-1173-title"><div><h1>Horaires & calendrier</h1><p class="description">Réglez ici uniquement les horaires habituels et la présentation du calendrier public. Les périodes, événements et exceptions restent dans leur rubrique dédiée.</p></div><span class="htp-1173-version">Interface 1.17.3</span></div>
            <?php if (class_exists('Parcs_HT_Admin_Navigation') && method_exists('Parcs_HT_Admin_Navigation', 'render_year_context')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year); ?>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les horaires et réglages du calendrier ont été enregistrés.</p></div><?php endif; ?>
            <?php if (isset($_GET['csv_imported'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Le CSV horaires & calendrier a été importé. Une révision de sécurité a été créée avant l’import.</p></div><?php endif; ?>

            <section class="htp-1173-summary">
                <div><span>Année administrée</span><strong><?php echo esc_html($year); ?></strong></div>
                <div><span>Début de saison</span><strong><?php echo esc_html((string)($season['season_start'] ?? '—')); ?></strong></div>
                <div><span>Fin de saison</span><strong><?php echo esc_html((string)($season['season_end'] ?? '—')); ?></strong></div>
                <div><span>Périodes habituelles</span><strong><?php echo esc_html((string)count($periods)); ?></strong></div>
            </section>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_schedule_1173">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <?php wp_nonce_field('parcs_ht_save_schedule_1173_' . $year); ?>

                <section class="htp-1173-card">
                    <div class="htp-1173-card-head"><div><h2>Horaires habituels</h2><p>Une période peut contenir un seul créneau ou deux. Le second créneau reste facultatif.</p></div><button type="button" class="button button-primary" data-htp-add-period>Ajouter une période</button></div>
                    <div class="htp-1173-periods" data-htp-periods>
                        <?php foreach ($periods as $index => $row) self::period_row($index, $row); ?>
                    </div>
                    <template id="htp-1173-period-template"><?php self::period_row('__INDEX__', array()); ?></template>
                    <p class="description">Les jours non couverts par une période active restent considérés comme fermés. Les horaires exceptionnels continuent d’être prioritaires lorsqu’ils existent.</p>
                </section>

                <section class="htp-1173-card">
                    <div class="htp-1173-card-head"><div><h2>Calendrier public</h2><p>Les cases du calendrier conservent leurs couleurs fonctionnelles. Les blocs qui entourent le calendrier restent transparents par défaut.</p></div></div>
                    <div class="htp-1173-grid htp-1173-grid-2">
                        <?php self::select('calendar[calendar_mobile_size]', $general['calendar_mobile_size'] ?? 'medium', 'Taille de la grille sur mobile', array('small'=>'Compacte','medium'=>'Standard','large'=>'Grande')); ?>
                        <?php self::select('calendar[calendar_detail_preset]', $general['calendar_detail_preset'] ?? 'large', 'Détail de la date sélectionnée', array('standard'=>'Standard','large'=>'Grand','xlarge'=>'Très grand')); ?>
                    </div>
                    <details class="htp-1173-advanced">
                        <summary>Apparence avancée des horaires et du calendrier</summary>
                        <p class="description">Tous les réglages visuels de l’ancien écran Horaires sont conservés ici. Les couleurs d’état et de période restent locales au calendrier.</p>
                        <h3>État du jour</h3>
                        <div class="htp-1173-grid htp-1173-grid-3">
                            <?php self::optional_color_field('today_title_color', $general, 'Aujourd’hui — texte'); ?>
                            <?php self::color_field('today_title_bg_color', $general, 'Aujourd’hui — fond', '#ffffff'); ?>
                            <?php self::optional_color_field('today_status_color', $general, 'Statut OUVERT', '#16843d'); ?>
                            <?php self::optional_color_field('today_closed_color', $general, 'Statut FERMÉ', '#9b2c2c'); ?>
                            <?php self::optional_color_field('today_detail_color', $general, 'Dernière entrée / détail'); ?>
                        </div>
                        <div class="htp-1173-toggle-row"><?php self::toggle('calendar[today_title_bg_transparent]', $general['today_title_bg_transparent'] ?? '1', 'Fond Aujourd’hui transparent'); ?></div>

                        <h3>Calendrier</h3>
                        <div class="htp-1173-grid htp-1173-grid-3">
                            <?php self::optional_color_field('calendar_title_color', $general, 'Calendrier — titre'); ?>
                            <?php self::color_field('calendar_title_bg_color', $general, 'Calendrier — fond du titre', '#ffffff'); ?>
                            <?php self::optional_color_field('calendar_weekday_color', $general, 'Jours de semaine'); ?>
                            <?php self::color_field('calendar_nav_bg_color', $general, 'Boutons des mois — fond', '#006757'); ?>
                            <?php self::color_field('calendar_nav_text_color', $general, 'Boutons des mois — texte', '#ffffff'); ?>
                            <?php self::color_field('calendar_nav_active_bg_color', $general, 'Mois actif — fond', '#e7c55b'); ?>
                            <?php self::color_field('calendar_nav_active_text_color', $general, 'Mois actif — texte', '#27342f'); ?>
                            <?php self::color_field('calendar_day_bg_color', $general, 'Jour ouvert — fond', '#ffffff'); ?>
                            <?php self::optional_color_field('calendar_detail_text_color', $general, 'Détail journée — texte'); ?>
                            <?php self::optional_color_field('calendar_detail_border_color', $general, 'Détail journée — bordure'); ?>
                            <?php self::color_field('calendar_closed_bg_color', $general, 'Jour fermé — fond', '#e3e5e4'); ?>
                            <?php self::color_field('calendar_closed_text_color', $general, 'Jour fermé — texte', '#616765'); ?>
                            <?php self::color_field('calendar_selected_color', $general, 'Jour sélectionné — contour', '#006757'); ?>
                        </div>
                        <div class="htp-1173-toggle-row">
                            <?php self::toggle('calendar[calendar_title_bg_transparent]', $general['calendar_title_bg_transparent'] ?? '1', 'Fond du titre transparent'); ?>
                            <?php self::toggle('calendar[calendar_day_bg_transparent]', $general['calendar_day_bg_transparent'] ?? '0', 'Fond des jours ouverts transparent'); ?>
                        </div>
                    </details>
                </section>

                <section class="htp-1173-card htp-1173-content-card">
                    <div><h2>Textes publics FR / EN / DE</h2><p>Les libellés du calendrier, les statuts d’ouverture, les mois, les messages de fermeture et les boutons sont centralisés dans « Contenus & traductions ». Ils ne sont plus dupliqués ici.</p></div>
                    <a class="button" href="<?php echo esc_url($content_url); ?>">Ouvrir Contenus & traductions</a>
                </section>

                <?php if (class_exists('Parcs_HT_Schedule_CSV')) : ?>
                <section class="htp-1173-card">
                    <div class="htp-1173-card-head"><div><h2>Import / export CSV</h2><p>Le moteur CSV existant reste inchangé et utilise toujours la saison sélectionnée.</p></div></div>
                    <?php Parcs_HT_Schedule_CSV::render_controls($year); ?>
                </section>
                <?php endif; ?>

                <div class="htp-1173-save"><button type="submit" class="button button-primary button-large">Enregistrer Horaires & calendrier</button></div>
            </form>
            <?php if (class_exists('Parcs_HT_Schedule_CSV')) Parcs_HT_Schedule_CSV::render_external_form($year); ?>
        </div>
        <?php
    }

    private static function clean_date($value) {
        $value = sanitize_text_field((string)$value);
        if (!preg_match('/^(20\d{2})-(\d{2})-(\d{2})$/', $value, $m)) return '';
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $value : '';
    }

    private static function clean_time($value) {
        $value = sanitize_text_field((string)$value);
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : '';
    }

    private static function clean_color($value, $fallback = '') {
        $color = sanitize_hex_color((string)$value);
        return $color !== null ? $color : $fallback;
    }

    private static function clean_periods($raw) {
        $out = array();
        foreach (is_array($raw) ? $raw : array() as $row) {
            if (!is_array($row)) continue;
            $days = array();
            foreach ((array)($row['weekdays'] ?? array()) as $day) {
                $day = (string)$day;
                if (in_array($day, array('1','2','3','4','5','6','7'), true) && !in_array($day, $days, true)) $days[] = $day;
            }
            $last = isset($row['last_entry_minutes']) ? trim((string)$row['last_entry_minutes']) : '';
            if ($last !== '') $last = (string)max(0, min(1440, (int)$last));
            $out[] = array(
                'enabled'=>(isset($row['enabled']) && (string)$row['enabled'] === '1') ? '1' : '0',
                'label'=>sanitize_text_field((string)($row['label'] ?? '')),
                'start'=>self::clean_date($row['start'] ?? ''),
                'end'=>self::clean_date($row['end'] ?? ''),
                'weekdays'=>$days,
                'open'=>self::clean_time($row['open'] ?? ''),
                'close'=>self::clean_time($row['close'] ?? ''),
                'open2'=>self::clean_time($row['open2'] ?? ''),
                'close2'=>self::clean_time($row['close2'] ?? ''),
                'last_entry_minutes'=>$last,
                'color'=>self::clean_color($row['color'] ?? '', '#9AAA8B'),
            );
        }
        return $out;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_save_schedule_1173_' . $year);

        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_die('Saison introuvable.');
        $raw_periods = isset($_POST['regular_periods']) && is_array($_POST['regular_periods']) ? wp_unslash($_POST['regular_periods']) : array();
        $all['seasons'][$year]['regular_periods'] = self::clean_periods($raw_periods);

        if (!isset($all['general']) || !is_array($all['general'])) $all['general'] = array();
        $calendar = isset($_POST['calendar']) && is_array($_POST['calendar']) ? wp_unslash($_POST['calendar']) : array();
        if (isset($calendar['calendar_mobile_size']) && in_array((string)$calendar['calendar_mobile_size'], array('small','medium','large'), true)) $all['general']['calendar_mobile_size'] = (string)$calendar['calendar_mobile_size'];
        if (isset($calendar['calendar_detail_preset']) && in_array((string)$calendar['calendar_detail_preset'], array('standard','large','xlarge'), true)) $all['general']['calendar_detail_preset'] = (string)$calendar['calendar_detail_preset'];

        foreach (array(
            'today_title_bg_color'=>'#ffffff',
            'calendar_title_bg_color'=>'#ffffff',
            'calendar_nav_bg_color'=>'#006757','calendar_nav_text_color'=>'#ffffff','calendar_nav_active_bg_color'=>'#e7c55b','calendar_nav_active_text_color'=>'#27342f',
            'calendar_day_bg_color'=>'#ffffff','calendar_closed_bg_color'=>'#e3e5e4','calendar_closed_text_color'=>'#616765','calendar_selected_color'=>'#006757',
        ) as $key=>$fallback) if (array_key_exists($key, $calendar)) $all['general'][$key] = self::clean_color($calendar[$key], $fallback);

        foreach (array('today_title_color','today_status_color','today_closed_color','today_detail_color','calendar_title_color','calendar_weekday_color','calendar_detail_text_color','calendar_detail_border_color') as $key) {
            if (array_key_exists($key, $calendar)) $all['general'][$key] = self::clean_color($calendar[$key], '');
        }
        foreach (array('today_title_bg_transparent','calendar_title_bg_transparent','calendar_day_bg_transparent') as $key) {
            if (array_key_exists($key, $calendar)) $all['general'][$key] = (string)$calendar[$key] === '1' ? '1' : '0';
        }

        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'season'=>$year,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }
}
