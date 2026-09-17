<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Référentiel central des contenus publics modifiables.
 *
 * Une seule définition décrit à la fois la valeur par défaut, la rubrique
 * d'administration, le libellé du champ et le type de saisie. Les moteurs métier
 * restent indépendants : cette classe ne gère que l'éditorial FR / EN / DE.
 */
final class Parcs_HT_Public_Content {
    const OPTION = 'parcs_ht_public_content';
    const PAGE = 'parcs-ht-public-content';
    const STORE_VERSION = 2;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 35);
        add_action('admin_post_parcs_ht_save_public_content', array(__CLASS__, 'save'));
        add_action('wp_footer', array(__CLASS__, 'inject_runtime_dictionary'), 4);
        // Pont de compatibilité pour les anciens renderers qui ne lisent pas encore
        // directement le référentiel. Les nouveaux renderers doivent appeler text().
        add_filter('do_shortcode_tag', array(__CLASS__, 'filter_shortcode_output'), 100, 4);
    }

    private static function triple($fr, $en, $de) {
        return array('fr'=>(string)$fr, 'en'=>(string)$en, 'de'=>(string)$de);
    }

    private static function field($section, $label, $fr, $en, $de, $long = false) {
        return array(
            'section'=>(string)$section,
            'label'=>(string)$label,
            'values'=>self::triple($fr, $en, $de),
            'long'=>(bool)$long,
        );
    }

    /** Source unique des textes éditables. */
    private static function catalog() {
        return array(
            'common.prices'=>self::field('Textes généraux et tarifs','Titre Tarifs','Tarifs','Prices','Preise'),
            'common.individual'=>self::field('Textes généraux et tarifs','Onglet Individuels','Individuels','Individuals','Einzelbesucher'),
            'common.reduced'=>self::field('Textes généraux et tarifs','Onglet Tarifs réduits','Tarifs réduits','Reduced rates','Ermäßigte Tarife'),
            'common.groups'=>self::field('Textes généraux et tarifs','Onglet Groupes','Groupes','Groups','Gruppen'),
            'common.payments'=>self::field('Textes généraux et tarifs','Moyens de paiement','Moyens de paiement','Payment methods','Zahlungsmittel'),
            'common.onsite'=>self::field('Textes généraux et tarifs','Sur place','Sur place','On site','Vor Ort'),
            'common.online'=>self::field('Textes généraux et tarifs','En ligne','En ligne','Online','Online'),
            'common.tickets'=>self::field('Textes généraux et tarifs','Bouton Acheter vos billets','Acheter vos billets','Buy tickets','Tickets kaufen'),
            'common.quote'=>self::field('Textes généraux et tarifs','Bouton Demande de devis','Faire une demande de devis','Request a quote','Angebot anfordern'),
            'common.print_rates'=>self::field('Textes généraux et tarifs','Lien Imprimer les tarifs','Imprimer les tarifs','Print rates','Tarife drucken'),
            'common.download_pdf'=>self::field('Textes généraux et tarifs','Lien Télécharger en PDF','Télécharger en PDF','Download PDF','PDF herunterladen'),
            'common.rate_year'=>self::field('Textes généraux et tarifs','Libellé Année des tarifs','Année des tarifs','Rate year','Tarifjahr'),
            'common.useful_links'=>self::field('Textes généraux et tarifs','Libellé Liens utiles','Liens utiles','Useful links','Nützliche Links'),
            'tariffs.download_pdf'=>self::field('Textes généraux et tarifs','Bouton PDF des tarifs','Télécharger les tarifs en PDF','Download prices as PDF','Preise als PDF herunterladen'),

            'schedule.today'=>self::field('Horaires et calendrier','Aujourd’hui','Aujourd’hui','Today','Heute'),
            'schedule.openNow'=>self::field('Horaires et calendrier','Ouvert maintenant','OUVERT','OPEN','GEÖFFNET'),
            'schedule.opensToday'=>self::field('Horaires et calendrier','Ouverture aujourd’hui','Ouverture aujourd’hui à {open}','Opens today at {open}','Öffnet heute um {open}'),
            'schedule.reopensToday'=>self::field('Horaires et calendrier','Réouverture aujourd’hui','Réouverture aujourd’hui à {open}','Reopens today at {open}','Öffnet heute wieder um {open}'),
            'schedule.openToday'=>self::field('Horaires et calendrier','Ouvert aujourd’hui','Ouvert aujourd’hui de {open} à {close}','Open today from {open} to {close}','Heute geöffnet von {open} bis {close}'),
            'schedule.closedToday'=>self::field('Horaires et calendrier','Fermé aujourd’hui','Fermé aujourd’hui','Closed today','Heute geschlossen'),
            'schedule.closedForToday'=>self::field('Horaires et calendrier','Fermé pour aujourd’hui','Fermé pour aujourd’hui','Closed for today','Für heute geschlossen'),
            'schedule.opensTomorrowAt'=>self::field('Horaires et calendrier','Ouverture demain','Ouverture demain à {time}','Open tomorrow at {time}','Morgen ab {time} geöffnet'),
            'schedule.opensOnAt'=>self::field('Horaires et calendrier','Ouverture à une date','Ouverture le {date} à {time}','Open on {date} at {time}','Geöffnet am {date} ab {time}'),
            'schedule.lastEntry'=>self::field('Horaires et calendrier','Dernière entrée','Dernière entrée à {time}','Last admission at {time}','Letzter Einlass um {time}'),
            'schedule.lastEntryCompact'=>self::field('Horaires et calendrier','Dernière entrée — format court','Dernière entrée : {time}','Last admission: {time}','Letzter Einlass: {time}'),
            'schedule.fromTime'=>self::field('Horaires et calendrier','À partir de','À partir de {time}','From {time}','Ab {time}'),
            'schedule.openingAt'=>self::field('Horaires et calendrier','Ouverture à','Ouverture à {time}','Opens at {time}','Öffnung um {time}'),
            'schedule.seeYouTomorrow'=>self::field('Horaires et calendrier','À demain','À demain !','See you tomorrow!','Bis morgen!'),
            'schedule.nextOpeningLabel'=>self::field('Horaires et calendrier','Titre prochaine ouverture','Prochaine ouverture','Next opening','Nächste Öffnung'),
            'schedule.nextOpeningCompact'=>self::field('Horaires et calendrier','Prochaine ouverture — format court','{date} à {time}','{date} at {time}','{date} um {time}'),
            'schedule.openTodayCompact'=>self::field('Horaires et calendrier','Ouvert aujourd’hui — format court','Ouvert aujourd’hui','Open today','Heute geöffnet'),
            'schedule.nextOpening'=>self::field('Horaires et calendrier','Prochaine ouverture','Prochaine ouverture : {date} à {time}','Next opening: {date} at {time}','Nächste Öffnung: {date} um {time}'),
            'schedule.calendar'=>self::field('Horaires et calendrier','Calendrier','Calendrier','Calendar','Kalender'),
            'schedule.monthHours'=>self::field('Horaires et calendrier','Horaires du mois','Horaires du mois : {hours}','Opening hours this month: {hours}','Öffnungszeiten in diesem Monat: {hours}'),
            'schedule.closed'=>self::field('Horaires et calendrier','Fermé','Fermé','Closed','Geschlossen'),
            'schedule.exceptionalHours'=>self::field('Horaires et calendrier','Horaires exceptionnels','Horaires exceptionnels','Exceptional opening hours','Außergewöhnliche Öffnungszeiten'),
            'schedule.exceptionalClosure'=>self::field('Horaires et calendrier','Fermeture exceptionnelle','Fermeture exceptionnelle','Exceptional closure','Außergewöhnliche Schließung'),
            'schedule.selectDate'=>self::field('Horaires et calendrier','Invitation à sélectionner une journée','Sélectionnez une journée pour afficher ses horaires.','Select a day to view its opening hours.','Wählen Sie einen Tag, um die Öffnungszeiten anzuzeigen.',true),
            'schedule.publicHoliday'=>self::field('Horaires et calendrier','Jour férié','Jour férié','Public holiday','Feiertag'),
            'schedule.schoolHoliday'=>self::field('Horaires et calendrier','Vacances scolaires','Vacances scolaires','School holidays','Schulferien'),
            'schedule.event'=>self::field('Horaires et calendrier','Événement','Événement','Event','Veranstaltung'),
            'schedule.closedShort'=>self::field('Horaires et calendrier','Fermé — court','Fermé','Closed','Geschlossen'),
            'schedule.exceptionallyClosedShort'=>self::field('Horaires et calendrier','Fermé exceptionnellement','Fermé exceptionnellement','Exceptionally closed','Ausnahmsweise geschlossen'),
            'schedule.reopensOn'=>self::field('Horaires et calendrier','Réouverture le','Réouverture le {date}','Reopens on {date}','Wiedereröffnung am {date}'),
            'schedule.reopensIn'=>self::field('Horaires et calendrier','Réouverture dans X jours','Réouverture dans {days} jours','Reopens in {days} days','Wiedereröffnung in {days} Tagen'),
            'schedule.reopensTomorrow'=>self::field('Horaires et calendrier','Réouverture demain','Réouverture demain','Reopens tomorrow','Wiedereröffnung morgen'),
            'schedule.closePopup'=>self::field('Horaires et calendrier','Fermer le pop-up','Fermer','Close','Schließen'),
            'schedule.notAvailable'=>self::field('Horaires et calendrier','Dates / horaires indisponibles','Les dates et horaires ne sont pas encore disponibles.','Dates and opening hours are not available yet.','Termine und Öffnungszeiten sind noch nicht verfügbar.',true),
            'schedule.prev_month'=>self::field('Horaires et calendrier','Navigation — mois précédent','Mois précédent','Previous month','Vorheriger Monat'),
            'schedule.next_month'=>self::field('Horaires et calendrier','Navigation — mois suivant','Mois suivant','Next month','Nächster Monat'),
            'schedule.download_pdf'=>self::field('Horaires et calendrier','Bouton PDF des horaires','Télécharger le planning des horaires','Download opening-hours schedule','Öffnungszeitenplan herunterladen'),

            'groups.portal.tariffs_tab'=>self::field('Espace groupes','Onglet Tarifs groupes','Tarifs groupes','Group rates','Gruppentarife'),
            'groups.portal.hours_tab'=>self::field('Espace groupes','Onglet Horaires d’ouverture','Horaires d’ouverture','Opening hours','Öffnungszeiten'),
            'groups.portal.year_label'=>self::field('Espace groupes','Libellé année','Année','Year','Jahr'),
            'groups.portal.tariffs_unavailable'=>self::field('Espace groupes','Tarifs groupes indisponibles pour une année','Les tarifs groupes ne sont pas disponibles pour cette année.','Group rates are not available for this year.','Für dieses Jahr sind keine Gruppentarife verfügbar.',true),
            'groups.portal.hours_unavailable'=>self::field('Espace groupes','Horaires indisponibles pour une année','Les horaires d’ouverture ne sont pas disponibles pour cette année.','Opening hours are not available for this year.','Für dieses Jahr sind keine Öffnungszeiten verfügbar.',true),
            'groups.portal.empty'=>self::field('Espace groupes','Aucune donnée groupes disponible','Aucun tarif groupe ni horaire disponible pour le moment.','No group rates or opening hours are available at the moment.','Derzeit sind keine Gruppentarife oder Öffnungszeiten verfügbar.',true),
            'groups.tariffs.empty'=>self::field('Espace groupes','Aucun tarif groupe disponible','Les tarifs groupes ne sont pas disponibles pour le moment.','Group rates are not available at the moment.','Die Gruppentarife sind derzeit nicht verfügbar.',true),
            'groups.redirect.title'=>self::field('Espace groupes','Renvoi groupes — titre','Vous venez en groupe ?','Visiting as a group?','Sie kommen als Gruppe?'),
            'groups.redirect.text'=>self::field('Espace groupes','Renvoi groupes — texte','Retrouvez les tarifs groupes {year}, les horaires d’ouverture et toutes les informations pour organiser votre visite sur notre espace dédié.','Find the {year} group rates, opening hours and all the information you need to organise your visit in our dedicated group area.','Die Gruppentarife {year}, Öffnungszeiten und alle Informationen zur Organisation Ihres Besuchs finden Sie in unserem Gruppenbereich.',true),
            'groups.redirect.visitor_notice'=>self::field('Espace groupes','Renvoi groupes — mention tarifs visiteurs','Les tarifs individuels et réduits {year} ne sont pas encore disponibles.','Individual and reduced rates for {year} are not available yet.','Einzel- und ermäßigte Tarife für {year} sind noch nicht verfügbar.',true),
            'groups.redirect.button'=>self::field('Espace groupes','Renvoi groupes — bouton','Voir les tarifs et horaires groupes','View group rates and opening hours','Gruppentarife und Öffnungszeiten ansehen'),
            'groups.special.buy'=>self::field('Espace groupes','Offre spéciale — bouton Acheter','Acheter','Buy','Kaufen'),
            'groups.special.online_only'=>self::field('Espace groupes','Offre spéciale — uniquement en ligne','Uniquement en ligne','Online only','Nur online'),
            'groups.special.onsite_only'=>self::field('Espace groupes','Offre spéciale — uniquement sur place','Uniquement sur place','On-site only','Nur vor Ort'),
            'groups.special.valid_between'=>self::field('Espace groupes','Offre spéciale — validité entre deux dates','Valable du {from} au {to}','Valid from {from} to {to}','Gültig vom {from} bis {to}'),
            'groups.special.valid_from'=>self::field('Espace groupes','Offre spéciale — valide à partir du','Valable à partir du {from}','Valid from {from}','Gültig ab {from}'),
            'groups.special.valid_until'=>self::field('Espace groupes','Offre spéciale — valide jusqu’au','Valable jusqu’au {to}','Valid until {to}','Gültig bis {to}'),

            'guides.resources'=>self::field('Guides pédagogiques','Guides — titre','Dossiers pédagogiques','Teaching resources','Pädagogische Materialien'),
            'guides.categories'=>self::field('Guides pédagogiques','Guides — catégories','Cycles / niveaux','Age groups / levels','Altersgruppen / Niveaus'),
            'guides.all_cycles'=>self::field('Guides pédagogiques','Guides — tous les cycles','Tous','All','Alle'),
            'guides.languages'=>self::field('Guides pédagogiques','Guides — langues','Langues','Languages','Sprachen'),
            'guides.all_languages'=>self::field('Guides pédagogiques','Guides — toutes les langues','Toutes','All','Alle'),
            'guides.new'=>self::field('Guides pédagogiques','Guides — Nouveau','Nouveau','New','Neu'),
            'guides.coming'=>self::field('Guides pédagogiques','Guides — À venir','À venir','Coming soon','Demnächst'),
            'guides.read'=>self::field('Guides pédagogiques','Guides — Consulter','Consulter','View','Ansehen'),
            'guides.download'=>self::field('Guides pédagogiques','Guides — Télécharger le PDF','Télécharger le PDF','Download PDF','PDF herunterladen'),
            'guides.info'=>self::field('Guides pédagogiques','Guides — Plus d’informations','Plus d’informations','More information','Mehr Informationen'),
        );
    }

    private static function link_catalog() {
        return array(
            'groups.redirect.url'=>array(
                'label'=>'Renvoi vers l’espace Groupes',
                'values'=>self::triple('', '', ''),
            ),
        );
    }

    public static function defaults() {
        $texts = array();
        foreach (self::catalog() as $key=>$field) $texts[$key] = $field['values'];
        $urls = array();
        foreach (self::link_catalog() as $key=>$field) $urls[$key] = $field['values'];
        return array('version'=>self::STORE_VERSION, 'texts'=>$texts, 'urls'=>$urls);
    }

    public static function settings() {
        $defaults = self::defaults();
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) return $defaults;
        $out = $defaults;
        foreach (array('texts','urls') as $section) {
            foreach ((array)($saved[$section] ?? array()) as $key=>$translations) {
                if (!isset($out[$section][$key]) || !is_array($translations)) continue;
                foreach (array('fr','en','de') as $lang) {
                    if (array_key_exists($lang, $translations)) $out[$section][$key][$lang] = (string)$translations[$lang];
                }
            }
        }
        $out['version'] = self::STORE_VERSION;
        return $out;
    }

    private static function get_value($section, $key, $language, $fallback = '') {
        $language = in_array($language, array('fr','en','de'), true) ? $language : 'fr';
        $settings = self::settings();
        $value = isset($settings[$section][$key][$language]) ? trim((string)$settings[$section][$key][$language]) : '';
        if ($value === '') $value = isset($settings[$section][$key]['fr']) ? trim((string)$settings[$section][$key]['fr']) : '';
        return $value !== '' ? $value : (string)$fallback;
    }

    public static function text($key, $language, $fallback = '') {
        return self::get_value('texts', (string)$key, $language, $fallback);
    }

    public static function url($key, $language, $fallback = '') {
        return self::get_value('urls', (string)$key, $language, $fallback);
    }

    public static function format($text, $vars = array()) {
        $replace = array();
        foreach ((array)$vars as $key=>$value) $replace['{' . $key . '}'] = (string)$value;
        return strtr((string)$text, $replace);
    }

    public static function menu() {
        if (!class_exists('Parcs_HT_Admin')) return;
        add_submenu_page(
            Parcs_HT_Admin::PAGE,
            'Contenus et traductions',
            'Contenus & traductions',
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'page')
        );
    }

    private static function grouped_catalog() {
        $groups = array();
        foreach (self::catalog() as $key=>$field) $groups[$field['section']][$key] = $field;
        return $groups;
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        ?>
        <div class="wrap htp-public-content-admin">
            <h1>Contenus & traductions</h1>
            <p>Modifiez ici les textes publics de l’extension sans modifier le code. Les variables entre accolades, comme <code>{year}</code>, <code>{date}</code>, <code>{time}</code>, <code>{open}</code>, <code>{close}</code>, <code>{from}</code>, <code>{to}</code>, <code>{hours}</code> ou <code>{days}</code>, doivent être conservées lorsqu’elles sont présentes.</p>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message de confirmation uniquement */ ?>
                <div class="notice notice-success is-dismissible"><p>Les contenus publics ont été enregistrés.</p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_public_content">
                <?php wp_nonce_field('parcs_ht_save_public_content'); ?>
                <?php foreach (self::grouped_catalog() as $title=>$fields) : ?>
                    <details class="card" style="max-width:none;margin:18px 0;padding:0 18px;" open>
                        <summary style="cursor:pointer;padding:16px 0;font-size:1.2em;font-weight:600;"><?php echo esc_html($title); ?></summary>
                        <table class="widefat striped" style="table-layout:fixed;margin-bottom:18px;">
                            <thead><tr><th style="width:22%">Élément</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                            <tbody>
                            <?php foreach ($fields as $key=>$field) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html($field['label']); ?></th>
                                    <?php foreach (array('fr','en','de') as $lang) : $value = $settings['texts'][$key][$lang] ?? ''; ?>
                                        <td>
                                        <?php if (!empty($field['long'])) : ?>
                                            <textarea rows="3" style="width:100%" name="content[texts][<?php echo esc_attr($key); ?>][<?php echo esc_attr($lang); ?>]"><?php echo esc_textarea($value); ?></textarea>
                                        <?php else : ?>
                                            <input type="text" style="width:100%" name="content[texts][<?php echo esc_attr($key); ?>][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($value); ?>">
                                        <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </details>
                <?php endforeach; ?>

                <details class="card" style="max-width:none;margin:18px 0;padding:0 18px;" open>
                    <summary style="cursor:pointer;padding:16px 0;font-size:1.2em;font-weight:600;">Liens publics</summary>
                    <p class="description">Laissez vide pour conserver le lien déterminé automatiquement par l’extension ou par les réglages existants.</p>
                    <table class="widefat striped" style="table-layout:fixed;margin-bottom:18px;">
                        <thead><tr><th style="width:22%">Élément</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                        <tbody>
                        <?php foreach (self::link_catalog() as $key=>$field) : ?>
                            <tr><th scope="row"><?php echo esc_html($field['label']); ?></th>
                            <?php foreach (array('fr','en','de') as $lang) : ?>
                                <td><input type="url" style="width:100%" name="content[urls][<?php echo esc_attr($key); ?>][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($settings['urls'][$key][$lang] ?? ''); ?>"></td>
                            <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
                <?php submit_button('Enregistrer les contenus'); ?>
            </form>
        </div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_public_content');
        $defaults = self::defaults();
        $catalog = self::catalog();
        $raw = isset($_POST['content']) && is_array($_POST['content']) ? wp_unslash($_POST['content']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ ci-dessous.
        $out = $defaults;
        foreach ($defaults['texts'] as $key=>$translations) {
            foreach (array('fr','en','de') as $lang) {
                $value = isset($raw['texts'][$key][$lang]) ? (string)$raw['texts'][$key][$lang] : (string)$translations[$lang];
                $out['texts'][$key][$lang] = !empty($catalog[$key]['long']) ? sanitize_textarea_field($value) : sanitize_text_field($value);
            }
        }
        foreach ($defaults['urls'] as $key=>$translations) {
            foreach (array('fr','en','de') as $lang) {
                $value = isset($raw['urls'][$key][$lang]) ? (string)$raw['urls'][$key][$lang] : '';
                $out['urls'][$key][$lang] = esc_url_raw($value);
            }
        }
        $out['version'] = self::STORE_VERSION;
        update_option(self::OPTION, $out, false);
        do_action('litespeed_purge_all');
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function language_from_tag($tag) {
        if (preg_match('/_(fr|en|de)$/', (string)$tag, $match)) return $match[1];
        return class_exists('Parcs_HT_Schedule') ? Parcs_HT_Schedule::language() : 'fr';
    }

    /**
     * Remplacement transitoire des libellés statiques de renderers historiques.
     * Les textes dynamiques passent par le dictionnaire JS ou par un appel direct à text().
     */
    private static function replacement_map($language) {
        $map = array();
        foreach (self::catalog() as $key=>$field) {
            $from = (string)($field['values'][$language] ?? '');
            if ($from === '' || strpos($from, '{') !== false) continue;
            $to = self::text($key, $language, $from);
            if ($from !== $to) $map[esc_html($from)] = esc_html($to);
        }
        return $map;
    }

    public static function filter_shortcode_output($output, $tag, $attr, $m) {
        unset($attr, $m);
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        $allowed = array(
            'parc_horaires_tarifs','parc_calendrier','parc_tableau_tarifs','parc_tarifs_groupes',
            'parc_groupes_horaires_tarifs','parc_guides_pedagogiques','parc_devis','parc_devis_groupe'
        );
        if (!in_array($base, $allowed, true) || !is_string($output) || $output === '') return $output;
        $language = self::language_from_tag($tag);
        $map = self::replacement_map($language);
        return $map ? strtr($output, $map) : $output;
    }

    private static function dictionary_overrides() {
        $mapping = array(
            'today'=>'schedule.today','openNow'=>'schedule.openNow','opensToday'=>'schedule.opensToday','reopensToday'=>'schedule.reopensToday','openToday'=>'schedule.openToday','closedToday'=>'schedule.closedToday','closedForToday'=>'schedule.closedForToday','opensTomorrowAt'=>'schedule.opensTomorrowAt','opensOnAt'=>'schedule.opensOnAt','lastEntry'=>'schedule.lastEntry','lastEntryCompact'=>'schedule.lastEntryCompact','fromTime'=>'schedule.fromTime','openingAt'=>'schedule.openingAt','seeYouTomorrow'=>'schedule.seeYouTomorrow','nextOpeningLabel'=>'schedule.nextOpeningLabel','nextOpeningCompact'=>'schedule.nextOpeningCompact','openTodayCompact'=>'schedule.openTodayCompact','nextOpening'=>'schedule.nextOpening','calendar'=>'schedule.calendar','monthHours'=>'schedule.monthHours','closed'=>'schedule.closed','exceptionalHours'=>'schedule.exceptionalHours','exceptionalClosure'=>'schedule.exceptionalClosure','selectDate'=>'schedule.selectDate','individual'=>'common.individual','reduced'=>'common.reduced','groups'=>'common.groups','prices'=>'common.prices','tickets'=>'common.tickets','quote'=>'common.quote','payments'=>'common.payments','publicHoliday'=>'schedule.publicHoliday','schoolHoliday'=>'schedule.schoolHoliday','event'=>'schedule.event','closedShort'=>'schedule.closedShort','exceptionallyClosedShort'=>'schedule.exceptionallyClosedShort','reopensOn'=>'schedule.reopensOn','reopensIn'=>'schedule.reopensIn','reopensTomorrow'=>'schedule.reopensTomorrow','closePopup'=>'schedule.closePopup','notAvailable'=>'schedule.notAvailable'
        );
        $out = array('fr'=>array(),'en'=>array(),'de'=>array());
        foreach ($out as $lang=>$unused) {
            foreach ($mapping as $dictionary_key=>$content_key) $out[$lang][$dictionary_key] = self::text($content_key, $lang, '');
        }
        return $out;
    }

    public static function inject_runtime_dictionary() {
        if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        $payload = self::dictionary_overrides();
        $js = '(function(){if(!window.ParcsHTPData)return;window.ParcsHTPData.dictionary=window.ParcsHTPData.dictionary||{};var p=' . wp_json_encode($payload) . ';Object.keys(p).forEach(function(lang){window.ParcsHTPData.dictionary[lang]=Object.assign({},window.ParcsHTPData.dictionary[lang]||{},p[lang]);});}());';
        wp_add_inline_script('parcs-ht-frontend', $js, 'after');
    }
}
