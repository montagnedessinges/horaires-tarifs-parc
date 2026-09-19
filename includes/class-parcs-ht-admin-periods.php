<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Écran métier Périodes, événements, exceptions & accès limité.
 *
 * L'écran réutilise le stockage et l'action de sauvegarde historiques. Il ne
 * crée aucun moteur calendrier parallèle : Parcs_HT_Schedule reste l'autorité
 * publique pour les priorités, périodes et règles d'accès.
 */
final class Parcs_HT_Admin_Periods {
    const PAGE = 'parcs-ht-periods';

    private static $event_icons = array(
        'star'     => 'Étoile',
        'camera'   => 'Appareil photo',
        'calendar' => 'Calendrier',
        'gift'     => 'Cadeau',
        'music'    => 'Musique',
        'leaf'     => 'Feuille',
        'flag'     => 'Drapeau',
        'heart'    => 'Cœur',
        'info'     => 'Information',
    );

    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_filter('pre_update_option_parcs_ht_settings', array(__CLASS__, 'preserve_event_visuals'), 10, 3);
    }

    private static function selected_year($all = null) {
        if ($all === null) $all = Parcs_HT_Defaults::all_settings();
        $requested = isset($_GET['season']) ? sanitize_text_field(wp_unslash($_GET['season'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
        if ($requested !== '' && preg_match('/^20\d{2}$/', $requested) && isset($all['seasons'][$requested])) return $requested;
        $settings = Parcs_HT_Defaults::settings();
        $active = (string)($settings['active_season_year'] ?? '');
        if ($active !== '' && isset($all['seasons'][$active])) return $active;
        foreach (array_keys((array)($all['seasons'] ?? array())) as $year) {
            if (preg_match('/^20\d{2}$/', (string)$year)) return (string)$year;
        }
        return '';
    }

    public static function assets() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sélection d'écran en lecture seule.
        if ($page !== self::PAGE) return;

        wp_enqueue_style('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-admin-periods-1174', PARCS_HT_URL . 'assets/admin-periods-1174.css', array('parcs-ht-admin'), PARCS_HT_VERSION);
        wp_enqueue_script('parcs-ht-preview-engine', PARCS_HT_URL . 'assets/frontend.js', array(), PARCS_HT_VERSION, true);
        $year = self::selected_year();
        wp_add_inline_script('parcs-ht-preview-engine', 'window.ParcsHTPData=' . wp_json_encode(array(
            'settings' => Parcs_HT_Schedule::public_settings(Parcs_HT_Defaults::settings($year)),
            'dictionary' => Parcs_HT_Schedule::dictionaries(),
        )) . ';', 'before');
        wp_enqueue_script('parcs-ht-admin', PARCS_HT_URL . 'assets/admin.js', array('parcs-ht-preview-engine', 'jquery-ui-sortable'), PARCS_HT_VERSION, true);
        wp_add_inline_script('parcs-ht-admin', 'window.ParcsHTAdmin=' . wp_json_encode(array('language'=>'fr')) . ';', 'before');
    }

    /**
     * Le sanitizer historique forçait encore les événements à une étoile jaune.
     * La 1.17.4 conserve le sanitizer canonique puis restaure uniquement les deux
     * attributs visuels explicitement autorisés par ce nouvel écran.
     */
    public static function preserve_event_visuals($value, $old_value, $option) {
        unset($old_value, $option);
        if (!is_admin() || !is_array($value)) return $value;
        $workspace = isset($_POST['parcs_ht_1174_workspace']) ? sanitize_text_field(wp_unslash($_POST['parcs_ht_1174_workspace'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- filtre exécuté pendant l'action parcs_ht_save, dont le nonce est contrôlé par Parcs_HT_Admin::save().
        if ($workspace !== '1') return $value;
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce contrôlé par l'action de sauvegarde canonique.
        if (!preg_match('/^20\d{2}$/', $year) || empty($value['seasons'][$year]['special_periods']) || !is_array($value['seasons'][$year]['special_periods'])) return $value;
        $settings = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- seules color/icon sont lues puis validées ci-dessous ; nonce contrôlé en amont.
        $rows = isset($settings['special_periods']) && is_array($settings['special_periods']) ? $settings['special_periods'] : array();
        $target = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if (!isset($value['seasons'][$year]['special_periods'][$target])) break;
            $kind = isset($row['kind']) ? sanitize_key((string)$row['kind']) : 'event';
            if ($kind === 'event') {
                $icon = isset($row['icon']) ? sanitize_key((string)$row['icon']) : 'star';
                if (!isset(self::$event_icons[$icon])) $icon = 'star';
                $color = isset($row['color']) ? sanitize_hex_color((string)$row['color']) : '';
                $value['seasons'][$year]['special_periods'][$target]['icon'] = $icon;
                $value['seasons'][$year]['special_periods'][$target]['color'] = $color ? $color : '#e7c55b';
            }
            $target++;
        }
        return $value;
    }

    private static function input($name, $value, $label, $type = 'text', $attrs = '') {
        echo '<label class="htp-field"><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string)$value) . '" ' . wp_kses_data($attrs) . '></label>';
    }

    private static function select($name, $value, $label, $options) {
        echo '<label class="htp-field"><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '">';
        foreach ($options as $key => $text) echo '<option value="' . esc_attr($key) . '" ' . selected((string)$value, (string)$key, false) . '>' . esc_html($text) . '</option>';
        echo '</select></label>';
    }

    private static function checkbox($name, $value, $label) {
        echo '<label><input type="hidden" name="' . esc_attr($name) . '" value="0"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked((string)$value, '1', false) . '> ' . esc_html($label) . '</label>';
    }

    private static function enabled($name, $value) {
        echo '<label class="htp-enabled"><input type="hidden" name="' . esc_attr($name) . '" value="0"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked((string)$value, '1', false) . '> Actif</label>';
    }

    private static function translated_input($name, $values, $label, $type = 'text') {
        $values = is_array($values) ? $values : array();
        ?>
        <div class="htp-field htp-local-translation" data-htp-local-lang="fr"><span><?php echo esc_html($label); ?></span>
            <div class="htp-mini-lang" role="tablist" aria-label="Langue du champ"><?php foreach (array('fr','en','de') as $lang) : ?><button type="button" class="button button-small <?php echo $lang === 'fr' ? 'button-primary' : ''; ?>" data-htp-local-language="<?php echo esc_attr($lang); ?>"><?php echo esc_html(strtoupper($lang)); ?></button><?php endforeach; ?></div>
            <?php foreach (array('fr','en','de') as $lang) : ?><input class="htp-local-lang-field" data-lang="<?php echo esc_attr($lang); ?>" type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name . '[' . $lang . ']'); ?>" value="<?php echo esc_attr(isset($values[$lang]) ? $values[$lang] : ''); ?>" <?php echo $lang === 'fr' ? '' : 'hidden'; ?>><?php endforeach; ?>
        </div>
        <?php
    }

    private static function translated_textarea($name, $values, $label) {
        $values = is_array($values) ? $values : array();
        ?>
        <div class="htp-field htp-local-translation" data-htp-local-lang="fr"><span><?php echo esc_html($label); ?></span>
            <div class="htp-mini-lang" role="tablist" aria-label="Langue du champ"><?php foreach (array('fr','en','de') as $lang) : ?><button type="button" class="button button-small <?php echo $lang === 'fr' ? 'button-primary' : ''; ?>" data-htp-local-language="<?php echo esc_attr($lang); ?>"><?php echo esc_html(strtoupper($lang)); ?></button><?php endforeach; ?></div>
            <?php foreach (array('fr','en','de') as $lang) : ?><textarea class="htp-local-lang-field" data-lang="<?php echo esc_attr($lang); ?>" rows="4" name="<?php echo esc_attr($name . '[' . $lang . ']'); ?>" <?php echo $lang === 'fr' ? '' : 'hidden'; ?>><?php echo esc_textarea(isset($values[$lang]) ? $values[$lang] : ''); ?></textarea><?php endforeach; ?>
        </div>
        <?php
    }

    private static function hidden_translations($name, $values) {
        $values = is_array($values) ? $values : array();
        foreach (array('fr','en','de') as $lang) echo '<input type="hidden" name="' . esc_attr($name . '[' . $lang . ']') . '" value="' . esc_attr(isset($values[$lang]) ? $values[$lang] : '') . '">';
    }

    private static function weekdays($name, $selected) {
        $days = array('1'=>'L','2'=>'M','3'=>'M','4'=>'J','5'=>'V','6'=>'S','7'=>'D');
        $selected = array_values(array_unique(array_intersect(array_keys($days), array_map('strval', is_array($selected) ? $selected : array()))));
        $present_name = preg_replace('/\[weekdays\]$/', '[weekdays_present]', $name);
        $csv_name = preg_replace('/\[weekdays\]$/', '[weekdays_csv]', $name);
        $touched_name = preg_replace('/\[weekdays\]$/', '[weekdays_touched]', $name);
        echo '<fieldset class="htp-weekdays" data-htp-weekdays><legend>Jours concernés</legend><input type="hidden" name="' . esc_attr($present_name) . '" value="1"><input type="hidden" class="htp-weekdays-csv" name="' . esc_attr($csv_name) . '" value="' . esc_attr(implode(',', $selected)) . '"><input type="hidden" class="htp-weekdays-touched" name="' . esc_attr($touched_name) . '" value="0">';
        foreach ($days as $value=>$label) echo '<label><input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($value) . '" ' . checked(in_array($value, $selected, true), true, false) . '>' . esc_html($label) . '</label>';
        echo '</fieldset>';
    }

    private static function popup_preview_controls() {
        ?>
        <div class="htp-popup-preview-actions"><span>Tester le pop-up :</span><select class="htp-popup-preview-language" data-htp-popup-preview-language aria-label="Langue de prévisualisation"><option value="fr">FR</option><option value="en">EN</option><option value="de">DE</option></select><button type="button" class="button button-secondary" data-htp-popup-test>Tester le pop-up</button></div>
        <?php
    }

    private static function special_period_row($index, $row, $force_event = false) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'enabled'=>'1','kind'=>$force_event?'event':'other','internal_label'=>'','title'=>array('fr'=>'','en'=>'','de'=>''),
            'start'=>'','end'=>'','color'=>$force_event?'#e7c55b':'#7b61a8','icon'=>'star','display_mode'=>'spot','message'=>array('fr'=>'','en'=>'','de'=>''),
            'button_label'=>array('fr'=>'','en'=>'','de'=>''),'button_url'=>array('fr'=>'','en'=>'','de'=>''),'show_button'=>'0','show_on_calendar'=>'1','skip_domain_rules'=>'0',
            'show_popup'=>'0','popup_lead_mode'=>'days_before','popup_days_before'=>'14','popup_start'=>'','popup_end'=>'',
            'popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),
            'popup_button_label'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_url'=>array('fr'=>'','en'=>'','de'=>''),'popup_show_button'=>'0','popup_image_url'=>''
        ));
        if ($force_event) $row['kind'] = 'event';
        $is_event = (string)$row['kind'] === 'event';
        $base = 'settings[special_periods][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-special-row htp-1174-row" data-htp-special-kind="<?php echo esc_attr($row['kind']); ?>">
            <?php if ($is_event) : ?><input type="hidden" name="<?php echo esc_attr($base . '[kind]'); ?>" value="event"><?php endif; ?>
            <div class="htp-row-head"><strong><?php echo esc_html($is_event ? 'Événement' : 'Période repère'); ?></strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::input($base . '[internal_label]', $row['internal_label'], 'Nom interne'); ?>
                <?php self::input($base . '[start]', $row['start'], 'Du', 'date'); ?>
                <?php self::input($base . '[end]', $row['end'], 'Au', 'date'); ?>
                <?php if ($is_event) : ?>
                    <?php self::select($base . '[icon]', $row['icon'], 'Pictogramme', self::$event_icons); ?>
                    <?php self::input($base . '[color]', $row['color'], 'Couleur du marqueur', 'color'); ?>
                    <?php self::select($base . '[display_mode]', $row['display_mode'], 'Affichage dans le calendrier', array('spot'=>'Ponctuel — marqueur visible','long'=>'Long — marqueur discret')); ?>
                <?php else : ?>
                    <?php self::select($base . '[kind]', $row['kind'], 'Type de période', array('school_holiday'=>'Vacances scolaires','other'=>'Autre période repère')); ?>
                    <?php self::input($base . '[color]', $row['color'], 'Couleur du marqueur', 'color'); ?>
                <?php endif; ?>
            </div>
            <div class="htp-check-list">
                <?php self::checkbox($base . '[show_on_calendar]', $row['show_on_calendar'], $is_event ? 'Afficher cet événement dans le calendrier' : 'Afficher cette période dans le calendrier'); ?>
                <?php if (!$is_event) self::checkbox($base . '[skip_domain_rules]', $row['skip_domain_rules'], 'Ignorer « Accès temporairement limité » pendant cette période'); ?>
            </div>
            <details class="htp-row-details">
                <summary>Contenu public FR / EN / DE</summary>
                <div class="htp-grid htp-grid-2 htp-details-content">
                    <?php self::translated_input($base . '[title]', $row['title'], $is_event ? 'Nom public de l’événement' : 'Nom public de la période'); ?>
                    <?php self::translated_textarea($base . '[message]', $row['message'], 'Message public facultatif'); ?>
                    <div class="htp-button-block" data-htp-button-block>
                        <div class="htp-check-list"><?php self::checkbox($base . '[show_button]', $row['show_button'], 'Afficher un bouton dans le détail'); ?></div>
                        <div class="htp-button-settings" data-htp-button-settings <?php echo $row['show_button'] === '1' ? '' : 'hidden'; ?>>
                            <?php self::translated_input($base . '[button_label]', $row['button_label'], 'Texte du bouton'); ?>
                            <?php self::translated_input($base . '[button_url]', $row['button_url'], 'Lien du bouton', 'url'); ?>
                        </div>
                    </div>
                </div>
            </details>
            <div class="htp-popup-block" data-htp-popup-block>
                <div class="htp-check-list htp-popup-toggle"><?php self::checkbox($base . '[show_popup]', $row['show_popup'], 'Activer le pop-up'); ?></div>
                <div class="htp-popup-settings" data-htp-popup-settings <?php echo $row['show_popup'] === '1' ? '' : 'hidden'; ?>>
                    <p class="description">Par défaut, le pop-up réutilise le nom et le message publics ci-dessus. Les champs de personnalisation restent facultatifs.</p>
                    <div class="htp-grid htp-grid-4">
                        <?php self::select($base . '[popup_lead_mode]', $row['popup_lead_mode'], 'Début d’affichage', array('same'=>'Au début','days_before'=>'X jours avant','custom'=>'Date/heure personnalisée')); ?>
                        <?php self::input($base . '[popup_days_before]', $row['popup_days_before'], 'Jours avant', 'number', 'min="0" max="365"'); ?>
                        <?php self::input($base . '[popup_start]', $row['popup_start'], 'Début personnalisé', 'datetime-local'); ?>
                        <?php self::input($base . '[popup_end]', $row['popup_end'], 'Fin personnalisée', 'datetime-local'); ?>
                        <?php self::input($base . '[popup_image_url]', $row['popup_image_url'], 'Image du pop-up (URL)', 'url'); ?>
                    </div>
                    <details class="htp-advanced"><summary>Personnaliser le contenu du pop-up</summary><div class="htp-grid htp-grid-2 htp-details-content">
                        <?php self::translated_input($base . '[popup_title]', $row['popup_title'], 'Titre du pop-up (vide = nom public)'); ?>
                        <?php self::translated_textarea($base . '[popup_message]', $row['popup_message'], 'Message du pop-up (vide = message public)'); ?>
                    </div></details>
                    <div class="htp-button-block" data-htp-button-block>
                        <div class="htp-check-list"><?php self::checkbox($base . '[popup_show_button]', $row['popup_show_button'], 'Afficher un bouton dans le pop-up'); ?></div>
                        <div class="htp-button-settings" data-htp-button-settings <?php echo $row['popup_show_button'] === '1' ? '' : 'hidden'; ?>>
                            <div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[popup_button_label]', $row['popup_button_label'], 'Texte du bouton'); ?><?php self::translated_input($base . '[popup_button_url]', $row['popup_button_url'], 'Lien du bouton', 'url'); ?></div>
                        </div>
                    </div>
                    <?php self::popup_preview_controls(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private static function public_holiday_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array('enabled'=>'1','label'=>'','date'=>''));
        $base = 'settings[public_holidays][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-compact-row htp-1174-row"><div class="htp-row-head"><strong>Journée particulière</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div><div class="htp-grid htp-grid-2"><?php self::input($base . '[label]', $row['label'], 'Nom interne'); ?><?php self::input($base . '[date]', $row['date'], 'Date', 'date'); ?></div></div>
        <?php
    }

    private static function exception_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'enabled'=>'1','type'=>'hours','label'=>'','start'=>'','end'=>'','open'=>'','close'=>'','open2'=>'','close2'=>'','last_entry_minutes'=>'','priority'=>'100','apply_domain_rules'=>'1','show_public_marker'=>'1',
            'context'=>array('fr'=>'','en'=>'','de'=>''),'title'=>array('fr'=>'','en'=>'','de'=>''),'message'=>array('fr'=>'','en'=>'','de'=>''),'show_popup'=>'0','popup_show_dates'=>'1','popup_show_hours'=>'1','popup_mode'=>'auto',
            'popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_label'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_url'=>array('fr'=>'','en'=>'','de'=>''),'popup_show_button'=>'0',
            'popup_lead_mode'=>'days_before','popup_days_before'=>'1','popup_start'=>'','popup_end'=>''
        ));
        $base = 'settings[exceptions][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-1174-row htp-1174-exception">
            <div class="htp-row-head"><strong>Exception</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4">
                <?php self::select($base . '[type]', $row['type'], 'Type', array('hours'=>'Horaires exceptionnels','closed'=>'Fermeture exceptionnelle')); ?>
                <?php self::input($base . '[label]', $row['label'], 'Nom interne'); ?>
                <?php self::input($base . '[start]', $row['start'], 'Du', 'date'); ?>
                <?php self::input($base . '[end]', $row['end'], 'Au', 'date'); ?>
            </div>
            <div class="htp-1174-slots">
                <div><h4>Créneau 1</h4><div class="htp-grid htp-grid-2"><?php self::input($base . '[open]', $row['open'], 'Ouverture', 'time'); ?><?php self::input($base . '[close]', $row['close'], 'Fermeture', 'time'); ?></div></div>
                <div><h4>Créneau 2 <small>facultatif</small></h4><div class="htp-grid htp-grid-2"><?php self::input($base . '[open2]', $row['open2'], 'Réouverture', 'time'); ?><?php self::input($base . '[close2]', $row['close2'], 'Fermeture', 'time'); ?></div></div>
            </div>
            <details class="htp-row-details" open><summary>Informations publiques</summary><div class="htp-grid htp-grid-2 htp-details-content">
                <?php self::translated_input($base . '[context]', $row['context'], 'Contexte / motif facultatif'); ?>
                <?php self::translated_input($base . '[title]', $row['title'], 'Titre public facultatif'); ?>
                <?php self::translated_textarea($base . '[message]', $row['message'], 'Message public facultatif'); ?>
            </div></details>
            <details class="htp-advanced"><summary>Options avancées de l’exception</summary><div class="htp-grid htp-grid-3 htp-details-content">
                <?php self::input($base . '[last_entry_minutes]', $row['last_entry_minutes'], 'Dernière entrée spécifique (minutes)', 'number', 'min="0" max="1440"'); ?>
                <?php self::input($base . '[priority]', $row['priority'], 'Priorité', 'number', 'min="0" max="9999"'); ?>
                <div class="htp-check-list"><?php self::checkbox($base . '[apply_domain_rules]', $row['apply_domain_rules'], 'Appliquer les règles d’accès limité'); ?><?php self::checkbox($base . '[show_public_marker]', $row['show_public_marker'], 'Afficher la mention « Horaire exceptionnel »'); ?></div>
            </div></details>
            <div class="htp-popup-block" data-htp-popup-block>
                <div class="htp-check-list htp-popup-toggle"><?php self::checkbox($base . '[show_popup]', $row['show_popup'], 'Activer le pop-up'); ?></div>
                <div class="htp-popup-settings" data-htp-popup-settings <?php echo $row['show_popup'] === '1' ? '' : 'hidden'; ?>>
                    <p class="description"><strong>Source unique :</strong> le pop-up reprend le contexte / motif, le titre public et le message public saisis ci-dessus. Aucun second texte n’est à ressaisir.</p>
                    <div class="htp-check-list"><?php self::checkbox($base . '[popup_show_dates]', $row['popup_show_dates'], 'Afficher les dates dans le pop-up'); ?><?php self::checkbox($base . '[popup_show_hours]', $row['popup_show_hours'], 'Afficher les horaires dans le pop-up'); ?></div>
                    <div class="htp-grid htp-grid-4">
                        <?php self::select($base . '[popup_lead_mode]', $row['popup_lead_mode'], 'Début d’affichage', array('same'=>'Au début de l’exception','days_before'=>'X jours avant','custom'=>'Date/heure personnalisée')); ?>
                        <?php self::input($base . '[popup_days_before]', $row['popup_days_before'], 'Jours avant', 'number', 'min="0" max="365"'); ?>
                        <?php self::input($base . '[popup_start]', $row['popup_start'], 'Début personnalisé', 'datetime-local'); ?>
                        <?php self::input($base . '[popup_end]', $row['popup_end'], 'Fin personnalisée', 'datetime-local'); ?>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr($base . '[popup_mode]'); ?>" value="<?php echo esc_attr($row['popup_mode']); ?>">
                    <?php self::hidden_translations($base . '[popup_title]', $row['popup_title']); ?>
                    <?php self::hidden_translations($base . '[popup_message]', $row['popup_message']); ?>
                    <div class="htp-button-block" data-htp-button-block><div class="htp-check-list"><?php self::checkbox($base . '[popup_show_button]', $row['popup_show_button'], 'Afficher un bouton dans le pop-up'); ?></div><div class="htp-button-settings" data-htp-button-settings <?php echo $row['popup_show_button'] === '1' ? '' : 'hidden'; ?>><div class="htp-grid htp-grid-2"><?php self::translated_input($base . '[popup_button_label]', $row['popup_button_label'], 'Texte du bouton'); ?><?php self::translated_input($base . '[popup_button_url]', $row['popup_button_url'], 'Lien du bouton', 'url'); ?></div></div></div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function domain_row($index, $row) {
        $row = wp_parse_args(is_array($row) ? $row : array(), array(
            'enabled'=>'0','label'=>'','public_title'=>array('fr'=>'Accès temporairement limité','en'=>'Temporarily limited access','de'=>'Vorübergehend eingeschränkter Zugang'),
            'start'=>'','end'=>'','weekdays'=>array('1','2','3','4','5'),'pause_start'=>'','resume'=>'','last_entry'=>'','exclude_weekends'=>'0','exclude_school_holidays'=>'0','exclude_public_holidays'=>'0','auto_details'=>'1','color'=>'#e7c55b',
            'access_message'=>array('fr'=>'Cette zone n’est pas accessible de {pause_start} à {resume}.','en'=>'This area is not accessible from {pause_start} to {resume}.','de'=>'Dieser Bereich ist von {pause_start} bis {resume} nicht zugänglich.'),
            'details_message'=>array('fr'=>'Dernière entrée : {last_entry} · Reprise des visites : {resume}','en'=>'Last admission: {last_entry} · Visits resume: {resume}','de'=>'Letzter Einlass: {last_entry} · Besuche wieder ab: {resume}'),
            'show_tooltip'=>'1','tooltip_text'=>array('fr'=>'Cette interruption temporaire permet à nos équipes de prendre leur pause. Le reste du parc reste accessible pendant ce temps.','en'=>'This temporary interruption allows our teams to take their break. The rest of the park remains accessible during this time.','de'=>'Diese vorübergehende Unterbrechung ermöglicht unserem Team eine Pause. Der übrige Park bleibt während dieser Zeit zugänglich.'),'info'=>array('fr'=>'','en'=>'','de'=>'')
        ));
        $base = 'settings[domain_rules][' . $index . ']';
        ?>
        <div class="htp-repeat-row htp-1174-row">
            <div class="htp-row-head"><strong>Règle d’accès</strong><?php self::enabled($base . '[enabled]', $row['enabled']); ?><button type="button" class="button-link-delete htp-remove-row">Supprimer</button></div>
            <div class="htp-grid htp-grid-4"><?php self::input($base . '[label]', $row['label'], 'Nom interne'); ?><?php self::input($base . '[start]', $row['start'], 'Du', 'date'); ?><?php self::input($base . '[end]', $row['end'], 'Au', 'date'); ?><?php self::input($base . '[pause_start]', $row['pause_start'], 'Interruption à', 'time'); ?><?php self::input($base . '[resume]', $row['resume'], 'Reprise à', 'time'); ?><?php self::input($base . '[last_entry]', $row['last_entry'], 'Dernière entrée avant interruption', 'time'); ?><?php self::input($base . '[color]', $row['color'], 'Couleur du bloc d’accès', 'color'); ?></div>
            <?php self::weekdays($base . '[weekdays]', $row['weekdays']); ?>
            <details class="htp-row-details" open><summary>Contenu public FR / EN / DE</summary><div class="htp-grid htp-grid-2 htp-details-content"><?php self::translated_input($base . '[public_title]', $row['public_title'], 'Titre public'); ?><?php self::translated_input($base . '[access_message]', $row['access_message'], 'Phrase d’accès — {pause_start}, {resume}, {last_entry}'); ?><?php self::translated_input($base . '[details_message]', $row['details_message'], 'Ligne dernière entrée / reprise'); ?><?php self::translated_textarea($base . '[info]', $row['info'], 'Message complémentaire facultatif'); ?></div></details>
            <details class="htp-advanced"><summary>Options avancées de la règle d’accès</summary><div class="htp-details-content"><div class="htp-check-list"><?php self::checkbox($base . '[exclude_weekends]', $row['exclude_weekends'], 'Ne pas appliquer le week-end'); ?><?php self::checkbox($base . '[exclude_school_holidays]', $row['exclude_school_holidays'], 'Ne pas appliquer pendant les vacances saisies'); ?><?php self::checkbox($base . '[exclude_public_holidays]', $row['exclude_public_holidays'], 'Ne pas appliquer les jours fériés saisis'); ?><?php self::checkbox($base . '[auto_details]', $row['auto_details'], 'Afficher automatiquement interruption / reprise'); ?></div><div class="htp-domain-tooltip-admin" data-htp-domain-tooltip-block><div class="htp-check-list"><?php self::checkbox($base . '[show_tooltip]', $row['show_tooltip'], 'Afficher le point d’explication « ! »'); ?></div><div class="htp-domain-tooltip-settings" data-htp-domain-tooltip-settings <?php echo $row['show_tooltip'] === '1' ? '' : 'hidden'; ?>><?php self::translated_textarea($base . '[tooltip_text]', $row['tooltip_text'], 'Texte de la bulle d’information'); ?></div></div></div></details>
        </div>
        <?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $all = Parcs_HT_Defaults::all_settings();
        $year = self::selected_year($all);
        if ($year === '' || !isset($all['seasons'][$year])) {
            echo '<div class="wrap"><h1>Périodes, événements, exceptions & accès limité</h1><div class="notice notice-warning"><p>Aucune saison disponible.</p></div></div>';
            return;
        }
        $settings = Parcs_HT_Defaults::settings($year);
        $specials = isset($settings['special_periods']) && is_array($settings['special_periods']) ? $settings['special_periods'] : array();
        $period_count = 0;
        $event_count = 0;
        foreach ($specials as $row) ((string)($row['kind'] ?? 'event') === 'event') ? $event_count++ : $period_count++;
        $exceptions = isset($settings['exceptions']) && is_array($settings['exceptions']) ? $settings['exceptions'] : array();
        $domains = isset($settings['domain_rules']) && is_array($settings['domain_rules']) ? $settings['domain_rules'] : array();
        $holidays = isset($settings['public_holidays']) && is_array($settings['public_holidays']) ? $settings['public_holidays'] : array();
        $g = isset($settings['general']) && is_array($settings['general']) ? $settings['general'] : array();
        ?>
        <div class="wrap htp-admin htp-1174-periods">
            <div class="htp-1174-title"><div><h1>Périodes, événements, exceptions & accès limité</h1><p class="description">Un seul écran annuel pour gérer les informations qui complètent les horaires habituels, sans mélanger leurs règles métier.</p></div><span>Interface 1.17.4</span></div>
            <?php if (class_exists('Parcs_HT_Admin_Navigation') && method_exists('Parcs_HT_Admin_Navigation', 'render_year_context')) Parcs_HT_Admin_Navigation::render_year_context(self::PAGE, $year); ?>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message visuel uniquement. */ ?><div class="notice notice-success is-dismissible"><p>Les périodes, événements, exceptions et règles d’accès ont été enregistrés.</p></div><?php endif; ?>

            <section class="htp-1174-summary"><div><span>Année</span><strong><?php echo esc_html($year); ?></strong></div><div><span>Périodes repères</span><strong><?php echo esc_html((string)$period_count); ?></strong></div><div><span>Événements</span><strong><?php echo esc_html((string)$event_count); ?></strong></div><div><span>Exceptions</span><strong><?php echo esc_html((string)count($exceptions)); ?></strong></div><div><span>Règles d’accès</span><strong><?php echo esc_html((string)count($domains)); ?></strong></div></section>
            <nav class="htp-1174-anchor-nav" aria-label="Sections"><a class="button" href="#htp-1174-periods-events">Périodes & événements</a><a class="button" href="#htp-1174-exceptions">Exceptions</a><a class="button" href="#htp-1174-access">Accès limité</a></nav>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-htp-1174-form>
                <input type="hidden" name="action" value="parcs_ht_save">
                <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
                <input type="hidden" name="htp_active_tab" value="htp-holidays">
                <input type="hidden" name="parcs_ht_1174_workspace" value="1">
                <input type="hidden" name="settings[_complete][holidays]" value="1">
                <input type="hidden" name="settings[_complete][exceptions]" value="1">
                <input type="hidden" name="settings[_complete][domain_rules]" value="1">
                <?php wp_nonce_field('parcs_ht_save'); ?>

                <section id="htp-1174-periods-events" class="htp-card htp-1174-card">
                    <div class="htp-1174-card-head"><div><h2>Périodes repères & événements</h2><p class="description">Les périodes repères décrivent un contexte (vacances, forte affluence…). Les événements sont des informations publiques indépendantes, chacune avec son marqueur, sa couleur et son éventuel pop-up.</p></div></div>
                    <div class="htp-subsection"><h3>Périodes repères</h3><div class="htp-repeater" data-template="htp-template-special-period"><div class="htp-repeater-rows"><?php foreach ($specials as $index=>$row) if ((string)($row['kind'] ?? 'event') !== 'event') self::special_period_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter une période repère</button></div></div>
                    <div class="htp-subsection"><h3>Événements</h3><div class="htp-repeater" data-template="htp-template-event"><div class="htp-repeater-rows"><?php foreach ($specials as $index=>$row) if ((string)($row['kind'] ?? 'event') === 'event') self::special_period_row($index, $row, true); ?></div><button type="button" class="button htp-add-row">Ajouter un événement</button></div></div>
                    <script type="text/html" id="htp-template-special-period"><?php self::special_period_row('__INDEX__', array('kind'=>'other')); ?></script>
                    <script type="text/html" id="htp-template-event"><?php self::special_period_row('__INDEX__', array('kind'=>'event'), true); ?></script>
                    <details class="htp-advanced htp-1174-wide-details"><summary>Jours fériés et légende du calendrier</summary><div class="htp-details-content">
                        <div class="htp-repeater" data-template="htp-template-public-holiday"><div class="htp-repeater-rows"><?php foreach ($holidays as $index=>$row) self::public_holiday_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter une journée particulière</button></div>
                        <script type="text/html" id="htp-template-public-holiday"><?php self::public_holiday_row('__INDEX__', array()); ?></script>
                        <div class="htp-check-list"><?php self::checkbox('settings[general][show_public_holidays]', $g['show_public_holidays'] ?? '0', 'Afficher les jours fériés sur le calendrier'); ?></div>
                        <div class="htp-grid htp-grid-3"><?php self::input('settings[general][holiday_border_color]', $g['holiday_border_color'] ?? '#e7c55b', 'Couleur du cadre', 'color'); ?><?php self::input('settings[general][holiday_border_width]', $g['holiday_border_width'] ?? '3', 'Épaisseur du cadre', 'number'); ?><?php self::translated_input('settings[general][holiday_message]', $g['holiday_message'] ?? array('fr'=>'Jour férié','en'=>'Public holiday','de'=>'Feiertag'), 'Message après clic'); ?></div>
                        <div class="htp-grid htp-grid-2"><?php self::translated_input('settings[general][period_legend_label]', $g['period_legend_label'] ?? array('fr'=>'Période spécifique','en'=>'Special period','de'=>'Besonderer Zeitraum'), 'Libellé de légende — période'); ?><?php self::translated_input('settings[general][event_legend_label]', $g['event_legend_label'] ?? array('fr'=>'Événement','en'=>'Event','de'=>'Veranstaltung'), 'Libellé de légende — événement'); ?></div>
                    </div></details>
                </section>

                <section id="htp-1174-exceptions" class="htp-card htp-1174-card">
                    <div class="htp-1174-card-head"><div><h2>Horaires & fermetures exceptionnels</h2><p class="description">Une exception active est prioritaire sur les horaires habituels. À priorité égale, une fermeture l’emporte. Après la période exceptionnelle, le calendrier reprend automatiquement l’horaire normal.</p></div></div>
                    <div class="htp-repeater" data-template="htp-template-exception"><div class="htp-repeater-rows"><?php foreach ($exceptions as $index=>$row) self::exception_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter une exception</button></div>
                    <script type="text/html" id="htp-template-exception"><?php self::exception_row('__INDEX__', array()); ?></script>
                </section>

                <section id="htp-1174-access" class="htp-card htp-1174-card">
                    <div class="htp-1174-card-head"><div><h2>Accès temporairement limité</h2><p class="description">Ce module concerne une zone dont l’accès peut être interrompu temporairement. Il ne ferme jamais le parc entier.</p></div></div>
                    <div class="htp-grid htp-grid-2"><?php self::translated_input('settings[general][calendar_hours_title]', $g['calendar_hours_title'] ?? array('fr'=>'Horaires du parc','en'=>'Park opening hours','de'=>'Öffnungszeiten des Parks'), 'Titre au-dessus des horaires du parc'); ?></div>
                    <div class="htp-repeater" data-template="htp-template-domain"><div class="htp-repeater-rows"><?php foreach ($domains as $index=>$row) self::domain_row($index, $row); ?></div><button type="button" class="button htp-add-row">Ajouter une règle d’accès</button></div>
                    <script type="text/html" id="htp-template-domain"><?php self::domain_row('__INDEX__', array()); ?></script>
                </section>

                <?php if (class_exists('Parcs_HT_Schedule_CSV')) : ?><section class="htp-card htp-1174-card"><h2>Import / export CSV</h2><p class="description">L’outil CSV commun couvre aussi les périodes, événements, exceptions et règles d’accès de cette saison.</p><?php Parcs_HT_Schedule_CSV::render_controls($year); ?></section><?php endif; ?>

                <div class="htp-1174-save"><button type="submit" name="htp_save_active" value="1" class="button button-primary button-large">Enregistrer Périodes & événements</button></div>
            </form>
            <?php if (class_exists('Parcs_HT_Schedule_CSV')) Parcs_HT_Schedule_CSV::render_external_form($year); ?>
        </div>
        <?php
    }
}
