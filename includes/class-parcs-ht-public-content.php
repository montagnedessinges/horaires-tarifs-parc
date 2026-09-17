<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Référentiel central des textes publics modifiables.
 *
 * Cette classe ne remplace aucun moteur métier. Elle fournit uniquement une source
 * de vérité éditoriale FR / EN / DE et une interface d'administration dédiée.
 */
final class Parcs_HT_Public_Content {
    const OPTION = 'parcs_ht_public_content';
    const PAGE = 'parcs-ht-public-content';
    const STORE_VERSION = 1;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 35);
        add_action('admin_post_parcs_ht_save_public_content', array(__CLASS__, 'save'));
        add_action('wp_footer', array(__CLASS__, 'inject_runtime_dictionary'), 4);
        add_filter('do_shortcode_tag', array(__CLASS__, 'filter_shortcode_output'), 100, 4);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'frontend_guard'), 120);
    }

    public static function defaults() {
        return array(
            'version' => self::STORE_VERSION,
            'texts' => array(
                'common.prices' => self::triple('Tarifs', 'Prices', 'Preise'),
                'common.individual' => self::triple('Individuels', 'Individuals', 'Einzelbesucher'),
                'common.reduced' => self::triple('Tarifs réduits', 'Reduced rates', 'Ermäßigte Tarife'),
                'common.groups' => self::triple('Groupes', 'Groups', 'Gruppen'),
                'common.payments' => self::triple('Moyens de paiement', 'Payment methods', 'Zahlungsmittel'),
                'common.onsite' => self::triple('Sur place', 'On site', 'Vor Ort'),
                'common.online' => self::triple('En ligne', 'Online', 'Online'),
                'common.tickets' => self::triple('Acheter vos billets', 'Buy tickets', 'Tickets kaufen'),
                'common.quote' => self::triple('Faire une demande de devis', 'Request a quote', 'Angebot anfordern'),
                'common.print_rates' => self::triple('Imprimer les tarifs', 'Print rates', 'Tarife drucken'),
                'common.download_pdf' => self::triple('Télécharger en PDF', 'Download PDF', 'PDF herunterladen'),
                'common.rate_year' => self::triple('Année des tarifs', 'Rate year', 'Tarifjahr'),

                'schedule.today' => self::triple('Aujourd’hui', 'Today', 'Heute'),
                'schedule.openNow' => self::triple('OUVERT', 'OPEN', 'GEÖFFNET'),
                'schedule.opensToday' => self::triple('Ouverture aujourd’hui à {open}', 'Opens today at {open}', 'Öffnet heute um {open}'),
                'schedule.reopensToday' => self::triple('Réouverture aujourd’hui à {open}', 'Reopens today at {open}', 'Öffnet heute wieder um {open}'),
                'schedule.openToday' => self::triple('Ouvert aujourd’hui de {open} à {close}', 'Open today from {open} to {close}', 'Heute geöffnet von {open} bis {close}'),
                'schedule.closedToday' => self::triple('Fermé aujourd’hui', 'Closed today', 'Heute geschlossen'),
                'schedule.closedForToday' => self::triple('Fermé pour aujourd’hui', 'Closed for today', 'Für heute geschlossen'),
                'schedule.opensTomorrowAt' => self::triple('Ouverture demain à {time}', 'Open tomorrow at {time}', 'Morgen ab {time} geöffnet'),
                'schedule.opensOnAt' => self::triple('Ouverture le {date} à {time}', 'Open on {date} at {time}', 'Geöffnet am {date} ab {time}'),
                'schedule.lastEntry' => self::triple('Dernière entrée à {time}', 'Last admission at {time}', 'Letzter Einlass um {time}'),
                'schedule.lastEntryCompact' => self::triple('Dernière entrée : {time}', 'Last admission: {time}', 'Letzter Einlass: {time}'),
                'schedule.fromTime' => self::triple('À partir de {time}', 'From {time}', 'Ab {time}'),
                'schedule.openingAt' => self::triple('Ouverture à {time}', 'Opens at {time}', 'Öffnung um {time}'),
                'schedule.seeYouTomorrow' => self::triple('À demain !', 'See you tomorrow!', 'Bis morgen!'),
                'schedule.nextOpeningLabel' => self::triple('Prochaine ouverture', 'Next opening', 'Nächste Öffnung'),
                'schedule.nextOpeningCompact' => self::triple('{date} à {time}', '{date} at {time}', '{date} um {time}'),
                'schedule.openTodayCompact' => self::triple('Ouvert aujourd’hui', 'Open today', 'Heute geöffnet'),
                'schedule.nextOpening' => self::triple('Prochaine ouverture : {date} à {time}', 'Next opening: {date} at {time}', 'Nächste Öffnung: {date} um {time}'),
                'schedule.calendar' => self::triple('Calendrier', 'Calendar', 'Kalender'),
                'schedule.monthHours' => self::triple('Horaires du mois : {hours}', 'Opening hours this month: {hours}', 'Öffnungszeiten in diesem Monat: {hours}'),
                'schedule.closed' => self::triple('Fermé', 'Closed', 'Geschlossen'),
                'schedule.exceptionalHours' => self::triple('Horaires exceptionnels', 'Exceptional opening hours', 'Außergewöhnliche Öffnungszeiten'),
                'schedule.exceptionalClosure' => self::triple('Fermeture exceptionnelle', 'Exceptional closure', 'Außergewöhnliche Schließung'),
                'schedule.selectDate' => self::triple('Sélectionnez une journée pour afficher ses horaires.', 'Select a day to view its opening hours.', 'Wählen Sie einen Tag, um die Öffnungszeiten anzuzeigen.'),
                'schedule.publicHoliday' => self::triple('Jour férié', 'Public holiday', 'Feiertag'),
                'schedule.schoolHoliday' => self::triple('Vacances scolaires', 'School holidays', 'Schulferien'),
                'schedule.event' => self::triple('Événement', 'Event', 'Veranstaltung'),
                'schedule.closedShort' => self::triple('Fermé', 'Closed', 'Geschlossen'),
                'schedule.exceptionallyClosedShort' => self::triple('Fermé exceptionnellement', 'Exceptionally closed', 'Ausnahmsweise geschlossen'),
                'schedule.reopensOn' => self::triple('Réouverture le {date}', 'Reopens on {date}', 'Wiedereröffnung am {date}'),
                'schedule.reopensIn' => self::triple('Réouverture dans {days} jours', 'Reopens in {days} days', 'Wiedereröffnung in {days} Tagen'),
                'schedule.reopensTomorrow' => self::triple('Réouverture demain', 'Reopens tomorrow', 'Wiedereröffnung morgen'),
                'schedule.closePopup' => self::triple('Fermer', 'Close', 'Schließen'),
                'schedule.notAvailable' => self::triple('Les dates et horaires ne sont pas encore disponibles.', 'Dates and opening hours are not available yet.', 'Termine und Öffnungszeiten sind noch nicht verfügbar.'),

                'groups.portal.tariffs_tab' => self::triple('Tarifs groupes', 'Group rates', 'Gruppentarife'),
                'groups.portal.hours_tab' => self::triple('Horaires d’ouverture', 'Opening hours', 'Öffnungszeiten'),
                'groups.portal.year_label' => self::triple('Année', 'Year', 'Jahr'),
                'groups.portal.tariffs_unavailable' => self::triple('Les tarifs groupes ne sont pas disponibles pour cette année.', 'Group rates are not available for this year.', 'Für dieses Jahr sind keine Gruppentarife verfügbar.'),
                'groups.portal.hours_unavailable' => self::triple('Les horaires d’ouverture ne sont pas disponibles pour cette année.', 'Opening hours are not available for this year.', 'Für dieses Jahr sind keine Öffnungszeiten verfügbar.'),
                'groups.portal.empty' => self::triple('Aucun tarif groupe ni horaire disponible pour le moment.', 'No group rates or opening hours are available at the moment.', 'Derzeit sind keine Gruppentarife oder Öffnungszeiten verfügbar.'),
                'groups.tariffs.empty' => self::triple('Les tarifs groupes ne sont pas disponibles pour le moment.', 'Group rates are not available at the moment.', 'Die Gruppentarife sind derzeit nicht verfügbar.'),
                'groups.redirect.title' => self::triple('Vous venez en groupe ?', 'Visiting as a group?', 'Sie kommen als Gruppe?'),
                'groups.redirect.text' => self::triple('Retrouvez les tarifs groupes {year}, les horaires d’ouverture et toutes les informations pour organiser votre visite sur notre espace dédié.', 'Find the {year} group rates, opening hours and all the information you need to organise your visit in our dedicated group area.', 'Die Gruppentarife {year}, Öffnungszeiten und alle Informationen zur Organisation Ihres Besuchs finden Sie in unserem Gruppenbereich.'),
                'groups.redirect.visitor_notice' => self::triple('Les tarifs individuels et réduits {year} ne sont pas encore disponibles.', 'Individual and reduced rates for {year} are not available yet.', 'Einzel- und ermäßigte Tarife für {year} sind noch nicht verfügbar.'),
                'groups.redirect.button' => self::triple('Voir les tarifs et horaires groupes', 'View group rates and opening hours', 'Gruppentarife und Öffnungszeiten ansehen'),

                'guides.resources' => self::triple('Dossiers pédagogiques', 'Teaching resources', 'Pädagogische Materialien'),
                'guides.categories' => self::triple('Cycles / niveaux', 'Age groups / levels', 'Altersgruppen / Niveaus'),
                'guides.all_cycles' => self::triple('Tous', 'All', 'Alle'),
                'guides.languages' => self::triple('Langues', 'Languages', 'Sprachen'),
                'guides.all_languages' => self::triple('Toutes', 'All', 'Alle'),
                'guides.new' => self::triple('Nouveau', 'New', 'Neu'),
                'guides.coming' => self::triple('À venir', 'Coming soon', 'Demnächst'),
                'guides.read' => self::triple('Consulter', 'View', 'Ansehen'),
                'guides.download' => self::triple('Télécharger le PDF', 'Download PDF', 'PDF herunterladen'),
                'guides.info' => self::triple('Plus d’informations', 'More information', 'Mehr Informationen'),
            ),
            'urls' => array(
                'groups.redirect.url' => self::triple('', '', ''),
            ),
        );
    }

    private static function triple($fr, $en, $de) {
        return array('fr'=>(string)$fr, 'en'=>(string)$en, 'de'=>(string)$de);
    }

    public static function settings() {
        $defaults = self::defaults();
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) return $defaults;
        $out = $defaults;
        foreach (array('texts','urls') as $section) {
            foreach ((array)($saved[$section] ?? array()) as $key => $translations) {
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
        foreach ((array)$vars as $key => $value) $replace['{' . $key . '}'] = (string)$value;
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

    private static function groups() {
        return array(
            'Textes généraux et tarifs' => array(
                'common.prices','common.individual','common.reduced','common.groups','common.payments','common.onsite','common.online','common.tickets','common.quote','common.print_rates','common.download_pdf','common.rate_year'
            ),
            'Horaires et calendrier' => array(
                'schedule.today','schedule.openNow','schedule.opensToday','schedule.reopensToday','schedule.openToday','schedule.closedToday','schedule.closedForToday','schedule.opensTomorrowAt','schedule.opensOnAt','schedule.lastEntry','schedule.lastEntryCompact','schedule.fromTime','schedule.openingAt','schedule.seeYouTomorrow','schedule.nextOpeningLabel','schedule.nextOpeningCompact','schedule.openTodayCompact','schedule.nextOpening','schedule.calendar','schedule.monthHours','schedule.closed','schedule.exceptionalHours','schedule.exceptionalClosure','schedule.selectDate','schedule.publicHoliday','schedule.schoolHoliday','schedule.event','schedule.closedShort','schedule.exceptionallyClosedShort','schedule.reopensOn','schedule.reopensIn','schedule.reopensTomorrow','schedule.closePopup','schedule.notAvailable'
            ),
            'Espace groupes' => array(
                'groups.portal.tariffs_tab','groups.portal.hours_tab','groups.portal.year_label','groups.portal.tariffs_unavailable','groups.portal.hours_unavailable','groups.portal.empty','groups.tariffs.empty','groups.redirect.title','groups.redirect.text','groups.redirect.visitor_notice','groups.redirect.button'
            ),
            'Guides pédagogiques' => array(
                'guides.resources','guides.categories','guides.all_cycles','guides.languages','guides.all_languages','guides.new','guides.coming','guides.read','guides.download','guides.info'
            ),
        );
    }

    private static function label($key) {
        $labels = array(
            'common.prices'=>'Titre Tarifs','common.individual'=>'Onglet Individuels','common.reduced'=>'Onglet Tarifs réduits','common.groups'=>'Onglet Groupes','common.payments'=>'Moyens de paiement','common.onsite'=>'Sur place','common.online'=>'En ligne','common.tickets'=>'Bouton Acheter vos billets','common.quote'=>'Bouton Demande de devis','common.print_rates'=>'Lien Imprimer les tarifs','common.download_pdf'=>'Lien Télécharger en PDF','common.rate_year'=>'Libellé Année des tarifs',
            'schedule.today'=>'Aujourd’hui','schedule.openNow'=>'Ouvert maintenant','schedule.opensToday'=>'Ouverture aujourd’hui','schedule.reopensToday'=>'Réouverture aujourd’hui','schedule.openToday'=>'Ouvert aujourd’hui','schedule.closedToday'=>'Fermé aujourd’hui','schedule.closedForToday'=>'Fermé pour aujourd’hui','schedule.opensTomorrowAt'=>'Ouverture demain','schedule.opensOnAt'=>'Ouverture à une date','schedule.lastEntry'=>'Dernière entrée','schedule.lastEntryCompact'=>'Dernière entrée — format court','schedule.fromTime'=>'À partir de','schedule.openingAt'=>'Ouverture à','schedule.seeYouTomorrow'=>'À demain','schedule.nextOpeningLabel'=>'Titre prochaine ouverture','schedule.nextOpeningCompact'=>'Prochaine ouverture — format court','schedule.openTodayCompact'=>'Ouvert aujourd’hui — format court','schedule.nextOpening'=>'Prochaine ouverture','schedule.calendar'=>'Calendrier','schedule.monthHours'=>'Horaires du mois','schedule.closed'=>'Fermé','schedule.exceptionalHours'=>'Horaires exceptionnels','schedule.exceptionalClosure'=>'Fermeture exceptionnelle','schedule.selectDate'=>'Invitation à sélectionner une journée','schedule.publicHoliday'=>'Jour férié','schedule.schoolHoliday'=>'Vacances scolaires','schedule.event'=>'Événement','schedule.closedShort'=>'Fermé — court','schedule.exceptionallyClosedShort'=>'Fermé exceptionnellement','schedule.reopensOn'=>'Réouverture le','schedule.reopensIn'=>'Réouverture dans X jours','schedule.reopensTomorrow'=>'Réouverture demain','schedule.closePopup'=>'Fermer le pop-up','schedule.notAvailable'=>'Dates / horaires indisponibles',
            'groups.portal.tariffs_tab'=>'Onglet Tarifs groupes','groups.portal.hours_tab'=>'Onglet Horaires d’ouverture','groups.portal.year_label'=>'Libellé année','groups.portal.tariffs_unavailable'=>'Tarifs groupes indisponibles pour une année','groups.portal.hours_unavailable'=>'Horaires indisponibles pour une année','groups.portal.empty'=>'Aucune donnée groupes disponible','groups.tariffs.empty'=>'Aucun tarif groupe disponible','groups.redirect.title'=>'Renvoi groupes — titre','groups.redirect.text'=>'Renvoi groupes — texte','groups.redirect.visitor_notice'=>'Renvoi groupes — mention tarifs visiteurs','groups.redirect.button'=>'Renvoi groupes — bouton',
            'guides.resources'=>'Guides — titre','guides.categories'=>'Guides — catégories','guides.all_cycles'=>'Guides — tous les cycles','guides.languages'=>'Guides — langues','guides.all_languages'=>'Guides — toutes les langues','guides.new'=>'Guides — Nouveau','guides.coming'=>'Guides — À venir','guides.read'=>'Guides — Consulter','guides.download'=>'Guides — Télécharger le PDF','guides.info'=>'Guides — Plus d’informations',
        );
        return isset($labels[$key]) ? $labels[$key] : $key;
    }

    private static function is_long($key) {
        return in_array($key, array(
            'schedule.selectDate','schedule.notAvailable','groups.portal.tariffs_unavailable','groups.portal.hours_unavailable','groups.portal.empty','groups.tariffs.empty','groups.redirect.text','groups.redirect.visitor_notice'
        ), true);
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        ?>
        <div class="wrap htp-public-content-admin">
            <h1>Contenus & traductions</h1>
            <p>Modifiez ici les textes publics de l’extension sans modifier le code. Les variables entre accolades, comme <code>{year}</code>, <code>{date}</code>, <code>{time}</code>, <code>{open}</code>, <code>{close}</code>, <code>{hours}</code> ou <code>{days}</code>, doivent être conservées lorsqu’elles sont présentes.</p>
            <?php if (isset($_GET['updated'])) : /* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message de confirmation uniquement */ ?>
                <div class="notice notice-success is-dismissible"><p>Les contenus publics ont été enregistrés.</p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="parcs_ht_save_public_content">
                <?php wp_nonce_field('parcs_ht_save_public_content'); ?>
                <?php foreach (self::groups() as $title => $keys) : ?>
                    <section class="card" style="max-width:none;margin:18px 0;padding:18px;">
                        <h2><?php echo esc_html($title); ?></h2>
                        <table class="widefat striped" style="table-layout:fixed;">
                            <thead><tr><th style="width:22%">Élément</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                            <tbody>
                            <?php foreach ($keys as $key) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html(self::label($key)); ?></th>
                                    <?php foreach (array('fr','en','de') as $lang) : $value = $settings['texts'][$key][$lang] ?? ''; ?>
                                        <td>
                                        <?php if (self::is_long($key)) : ?>
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
                    </section>
                <?php endforeach; ?>

                <section class="card" style="max-width:none;margin:18px 0;padding:18px;">
                    <h2>Liens publics</h2>
                    <p class="description">Laissez vide pour conserver le lien déterminé automatiquement par l’extension ou par les réglages existants.</p>
                    <table class="widefat striped" style="table-layout:fixed;">
                        <thead><tr><th style="width:22%">Élément</th><th>FR</th><th>EN</th><th>DE</th></tr></thead>
                        <tbody><tr><th scope="row">Renvoi vers l’espace Groupes</th>
                        <?php foreach (array('fr','en','de') as $lang) : ?>
                            <td><input type="url" style="width:100%" name="content[urls][groups.redirect.url][<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($settings['urls']['groups.redirect.url'][$lang] ?? ''); ?>"></td>
                        <?php endforeach; ?>
                        </tr></tbody>
                    </table>
                </section>
                <?php submit_button('Enregistrer les contenus'); ?>
            </form>
        </div>
        <?php
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_public_content');
        $defaults = self::defaults();
        $raw = isset($_POST['content']) && is_array($_POST['content']) ? wp_unslash($_POST['content']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ ci-dessous.
        $out = $defaults;
        foreach ($defaults['texts'] as $key => $translations) {
            foreach (array('fr','en','de') as $lang) {
                $value = isset($raw['texts'][$key][$lang]) ? (string)$raw['texts'][$key][$lang] : (string)$translations[$lang];
                $out['texts'][$key][$lang] = self::is_long($key) ? sanitize_textarea_field($value) : sanitize_text_field($value);
            }
        }
        foreach ($defaults['urls'] as $key => $translations) {
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

    private static function replacement_map($language) {
        $defaults = self::defaults();
        $keys = array(
            'common.prices','common.individual','common.reduced','common.groups','common.payments','common.onsite','common.online','common.tickets','common.quote','common.print_rates','common.download_pdf','common.rate_year',
            'groups.portal.tariffs_tab','groups.portal.hours_tab','groups.portal.year_label','groups.portal.tariffs_unavailable','groups.portal.hours_unavailable','groups.portal.empty','groups.tariffs.empty',
            'groups.redirect.title','groups.redirect.button',
            'guides.resources','guides.categories','guides.all_cycles','guides.languages','guides.all_languages','guides.new','guides.coming','guides.read','guides.download','guides.info'
        );
        $map = array();
        foreach ($keys as $key) {
            $from = (string)($defaults['texts'][$key][$language] ?? '');
            $to = self::text($key, $language, $from);
            if ($from !== '' && $from !== $to) $map[esc_html($from)] = esc_html($to);
        }
        return $map;
    }

    private static function replace_dynamic_group_redirect($output, $language) {
        $patterns = array(
            'fr'=>array(
                'text'=>'~Retrouvez les tarifs groupes (20\\d{2}), les horaires d’ouverture et toutes les informations pour organiser votre visite sur notre espace dédié\\.~u',
                'notice'=>'~Les tarifs individuels et réduits (20\\d{2}) ne sont pas encore disponibles\\.~u',
            ),
            'en'=>array(
                'text'=>'~Find the (20\\d{2}) group rates, opening hours and all the information you need to organise your visit in our dedicated group area\\.~u',
                'notice'=>'~Individual and reduced rates for (20\\d{2}) are not available yet\\.~u',
            ),
            'de'=>array(
                'text'=>'~Die Gruppentarife (20\\d{2}), Öffnungszeiten und alle Informationen zur Organisation Ihres Besuchs finden Sie in unserem Gruppenbereich\\.~u',
                'notice'=>'~Einzel- und ermäßigte Tarife für (20\\d{2}) sind noch nicht verfügbar\\.~u',
            ),
        );
        foreach ($patterns[$language] as $kind => $pattern) {
            $key = $kind === 'text' ? 'groups.redirect.text' : 'groups.redirect.visitor_notice';
            $output = preg_replace_callback($pattern, static function ($match) use ($key, $language) {
                $value = Parcs_HT_Public_Content::text($key, $language, $match[0]);
                return esc_html(Parcs_HT_Public_Content::format($value, array('year'=>$match[1])));
            }, $output);
        }
        return $output;
    }

    private static function replace_group_redirect_url($output, $language) {
        $url = self::url('groups.redirect.url', $language, '');
        if ($url === '' || strpos($output, 'parcs-ht-group-redirect') === false) return $output;
        return preg_replace(
            '~(<div class="parcs-ht-tariff-ui__actions"><a class="parcs-ht-tariff-ui__button" href=")[^"]*(">)~',
            '$1' . esc_url($url) . '$2',
            $output,
            1
        );
    }

    public static function filter_shortcode_output($output, $tag, $attr, $m) {
        unset($attr, $m);
        $base = preg_replace('/_(fr|en|de)$/', '', (string)$tag);
        $allowed = array('parc_horaires_tarifs','parc_tableau_tarifs','parc_tarifs_groupes','parc_groupes_horaires_tarifs','parc_guides_pedagogiques');
        if (!in_array($base, $allowed, true) || !is_string($output) || $output === '') return $output;
        $language = self::language_from_tag($tag);
        $map = self::replacement_map($language);
        if ($map) $output = strtr($output, $map);
        $output = self::replace_dynamic_group_redirect($output, $language);
        $output = self::replace_group_redirect_url($output, $language);
        return $output;
    }

    private static function dictionary_overrides() {
        $mapping = array(
            'today'=>'schedule.today','openNow'=>'schedule.openNow','opensToday'=>'schedule.opensToday','reopensToday'=>'schedule.reopensToday','openToday'=>'schedule.openToday','closedToday'=>'schedule.closedToday','closedForToday'=>'schedule.closedForToday','opensTomorrowAt'=>'schedule.opensTomorrowAt','opensOnAt'=>'schedule.opensOnAt','lastEntry'=>'schedule.lastEntry','lastEntryCompact'=>'schedule.lastEntryCompact','fromTime'=>'schedule.fromTime','openingAt'=>'schedule.openingAt','seeYouTomorrow'=>'schedule.seeYouTomorrow','nextOpeningLabel'=>'schedule.nextOpeningLabel','nextOpeningCompact'=>'schedule.nextOpeningCompact','openTodayCompact'=>'schedule.openTodayCompact','nextOpening'=>'schedule.nextOpening','calendar'=>'schedule.calendar','monthHours'=>'schedule.monthHours','closed'=>'schedule.closed','exceptionalHours'=>'schedule.exceptionalHours','exceptionalClosure'=>'schedule.exceptionalClosure','selectDate'=>'schedule.selectDate','individual'=>'common.individual','reduced'=>'common.reduced','groups'=>'common.groups','prices'=>'common.prices','tickets'=>'common.tickets','quote'=>'common.quote','payments'=>'common.payments','publicHoliday'=>'schedule.publicHoliday','schoolHoliday'=>'schedule.schoolHoliday','event'=>'schedule.event','closedShort'=>'schedule.closedShort','exceptionallyClosedShort'=>'schedule.exceptionallyClosedShort','reopensOn'=>'schedule.reopensOn','reopensIn'=>'schedule.reopensIn','reopensTomorrow'=>'schedule.reopensTomorrow','closePopup'=>'schedule.closePopup','notAvailable'=>'schedule.notAvailable'
        );
        $out = array('fr'=>array(),'en'=>array(),'de'=>array());
        foreach ($out as $lang => $unused) {
            foreach ($mapping as $dictionary_key => $content_key) $out[$lang][$dictionary_key] = self::text($content_key, $lang, '');
        }
        return $out;
    }

    public static function inject_runtime_dictionary() {
        if (!wp_script_is('parcs-ht-frontend', 'enqueued')) return;
        $payload = self::dictionary_overrides();
        $js = '(function(){if(!window.ParcsHTPData)return;window.ParcsHTPData.dictionary=window.ParcsHTPData.dictionary||{};var p=' . wp_json_encode($payload) . ';Object.keys(p).forEach(function(lang){window.ParcsHTPData.dictionary[lang]=Object.assign({},window.ParcsHTPData.dictionary[lang]||{},p[lang]);});}());';
        wp_add_inline_script('parcs-ht-frontend', $js, 'after');
    }

    /**
     * Garde-fou du portail Groupes : le message d'indisponibilité ne doit jamais rester
     * visible lorsqu'un panneau tarifaire existe pour l'année sélectionnée.
     */
    public static function frontend_guard() {
        wp_register_style('parcs-ht-public-content-guard', false, array(), PARCS_HT_VERSION);
        wp_enqueue_style('parcs-ht-public-content-guard');
        wp_add_inline_style('parcs-ht-public-content-guard', '.parcs-ht-group-year-unavailable[hidden]{display:none!important}');
        wp_register_script('parcs-ht-public-content-guard', false, array(), PARCS_HT_VERSION, true);
        wp_enqueue_script('parcs-ht-public-content-guard');
        $js = <<<'JS'
(function(){
  'use strict';
  function sync(root){
    if(!root)return;
    var active=String(root.getAttribute('data-group-active-year')||root.getAttribute('data-group-selected-year')||'');
    var selected=root.querySelector('[data-group-year].is-active');
    if(selected)active=String(selected.getAttribute('data-group-year')||active);
    var missing=root.querySelector('[data-group-tariff-unavailable],[data-group-tariff-empty]');
    if(!missing||!active)return;
    var panel=root.querySelector('[data-group-tariff-year="'+active+'"],[data-htp-ui-year-panel="'+active+'"]');
    if(panel)missing.hidden=true;
  }
  function init(root){
    sync(root);
    root.addEventListener('click',function(event){if(event.target.closest('[data-group-year],[data-group-main-tab]'))window.setTimeout(function(){sync(root);},0);});
    if(window.MutationObserver)new MutationObserver(function(){sync(root);}).observe(root,{attributes:true,subtree:true,attributeFilter:['hidden','class','data-group-active-year','data-group-selected-year']});
  }
  function boot(){document.querySelectorAll('[data-group-portal]').forEach(init);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
JS;
        wp_add_inline_script('parcs-ht-public-content-guard', $js, 'after');
    }
}
