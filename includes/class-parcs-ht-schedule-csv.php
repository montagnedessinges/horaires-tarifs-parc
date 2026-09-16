<?php

if (!defined('ABSPATH')) { exit; }

/** Import/export CSV des données horaires et calendrier d'une saison. */
final class Parcs_HT_Schedule_CSV {
    const MAX_BYTES = 2097152;

    public static function init() {
        add_action('admin_post_parcs_ht_schedule_csv_template', array(__CLASS__, 'download_template'));
        add_action('admin_post_parcs_ht_schedule_csv_export', array(__CLASS__, 'export_csv'));
        add_action('admin_post_parcs_ht_schedule_csv_import', array(__CLASS__, 'import_csv'));
    }

    public static function render_controls($year) {
        if (!current_user_can('manage_options')) return;
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
        if ($year === '') return;
        $template_url = wp_nonce_url(
            add_query_arg(array('action'=>'parcs_ht_schedule_csv_template','season_year'=>$year), admin_url('admin-post.php')),
            'parcs_ht_schedule_csv_template_' . $year
        );
        $export_url = wp_nonce_url(
            add_query_arg(array('action'=>'parcs_ht_schedule_csv_export','season_year'=>$year), admin_url('admin-post.php')),
            'parcs_ht_schedule_csv_export_' . $year
        );
        $tool_url = add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-regular'), admin_url('admin.php')) . '#htp-schedule-csv-controls';
        ?>
        <div class="htp-subsection htp-schedule-csv" data-htp-csv-tool>
            <h3>CSV — horaires & calendrier</h3>
            <p class="description">Importez ou exportez les horaires, exceptions, jours fériés, périodes repères, événements et règles d’accès temporairement limité de la saison <?php echo esc_html($year); ?>. L’import valide tout le fichier avant écriture et ne remplace que les catégories réellement présentes dans le CSV.</p>
            <p class="htp-csv-actions"><a class="button" href="<?php echo esc_url($template_url); ?>">Télécharger le modèle CSV</a> <a class="button" href="<?php echo esc_url($export_url); ?>">Exporter les données <?php echo esc_html($year); ?></a></p>
            <label class="htp-field"><span>Fichier CSV</span><input type="file" name="schedule_csv" accept=".csv,text/csv,text/plain" required form="htp-schedule-csv-form"></label>
            <p><button class="button button-primary" type="submit" form="htp-schedule-csv-form">Importer dans <?php echo esc_html($year); ?></button></p>
            <p class="description">Types disponibles : <code>regular</code>, <code>exception_hours</code>, <code>exception_closed</code>, <code>holiday</code>, <code>period</code>, <code>school_holiday</code>, <code>event</code>, <code>limited_access</code>.</p>
        </div>
        <style>
        .htp-list-search{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:10px 0 12px;padding:8px 10px;border:1px solid #dcdcde;border-radius:6px;background:#fff}
        .htp-list-search input[type="search"]{min-width:220px;max-width:360px;flex:1 1 240px}
        .htp-list-search-count{color:#646970;font-size:12px;white-space:nowrap}
        .htp-list-search-empty{margin:0 0 10px;color:#646970;font-style:italic}
        .htp-csv-section-link{margin:8px 0 12px;font-size:12px}
        .htp-csv-actions{display:flex;gap:8px;flex-wrap:wrap}
        </style>
        <script>
        (function(){
            'use strict';
            var csvUrl=<?php echo wp_json_encode($tool_url); ?>;
            var configs=[
                {section:'#htp-holidays .htp-subsection',multiple:true},
                {section:'#htp-domain',multiple:false},
                {section:'#htp-exceptions',multiple:false}
            ];
            function textForRow(row){
                var parts=[row.textContent||''];
                row.querySelectorAll('input,select,textarea').forEach(function(field){
                    if(field.type==='checkbox'||field.type==='radio'){
                        if(field.checked)parts.push(field.value||'1');
                    }else{
                        parts.push(field.value||'');
                        if(field.tagName==='SELECT'&&field.selectedIndex>=0)parts.push(field.options[field.selectedIndex].text||'');
                    }
                });
                return parts.join(' ').toLocaleLowerCase();
            }
            function labelFor(container){
                var heading=container.querySelector('h2,h3');
                return heading&&heading.textContent?heading.textContent.trim():'cette liste';
            }
            function addCsvLink(container){
                if(container.querySelector('.htp-csv-section-link'))return;
                var p=document.createElement('p');
                p.className='description htp-csv-section-link';
                p.innerHTML='CSV : cette section est couverte par l’import/export. <a href="'+String(csvUrl).replace(/"/g,'&quot;')+'">Ouvrir l’outil CSV</a>';
                var repeater=container.querySelector('.htp-repeater');
                if(repeater)container.insertBefore(p,repeater);
            }
            function enhance(container,repeater){
                if(!container||!repeater||repeater.dataset.htpSearchReady==='1')return;
                var rows=repeater.querySelector('.htp-repeater-rows');
                if(!rows)return;
                repeater.dataset.htpSearchReady='1';
                var bar=document.createElement('div');
                bar.className='htp-list-search';
                bar.setAttribute('data-htp-admin-list-search','1');
                var input=document.createElement('input');
                input.type='search'; input.placeholder='Rechercher…'; input.setAttribute('aria-label','Rechercher dans '+labelFor(container));
                var clear=document.createElement('button'); clear.type='button'; clear.className='button button-small'; clear.textContent='Effacer';
                var count=document.createElement('span'); count.className='htp-list-search-count'; count.setAttribute('aria-live','polite');
                bar.appendChild(input); bar.appendChild(clear); bar.appendChild(count);
                repeater.parentNode.insertBefore(bar,repeater);
                var empty=document.createElement('p'); empty.className='htp-list-search-empty'; empty.textContent='Aucun résultat'; empty.hidden=true;
                repeater.parentNode.insertBefore(empty,repeater.nextSibling);
                function filter(){
                    var query=(input.value||'').trim().toLocaleLowerCase();
                    var all=Array.prototype.slice.call(rows.children).filter(function(row){return row.classList.contains('htp-repeat-row');});
                    var shown=0;
                    all.forEach(function(row){var visible=!query||textForRow(row).indexOf(query)!==-1;row.hidden=!visible;if(visible)shown++;});
                    count.textContent=shown+' / '+all.length;
                    empty.hidden=shown!==0;
                }
                input.addEventListener('input',filter);
                clear.addEventListener('click',function(){input.value='';filter();input.focus();});
                var add=repeater.querySelector('.htp-add-row');
                if(add)add.addEventListener('click',function(){if(input.value){input.value='';window.setTimeout(filter,0);}});
                rows.addEventListener('input',function(){if(input.value)filter();});
                rows.addEventListener('change',function(){if(input.value)filter();});
                if(window.MutationObserver){new MutationObserver(function(){filter();}).observe(rows,{childList:true});}
                filter();
            }
            function init(){
                configs.forEach(function(config){
                    document.querySelectorAll(config.section).forEach(function(container){
                        var repeaters=container.querySelectorAll(':scope > .htp-repeater, :scope > div > .htp-repeater');
                        if(!repeaters.length)repeaters=container.querySelectorAll('.htp-repeater');
                        repeaters.forEach(function(repeater){enhance(container,repeater);});
                        if(container.querySelector('.htp-repeater'))addCsvLink(container);
                    });
                });
            }
            if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
        }());
        </script>
        <?php
    }

    public static function render_external_form($year) {
        if (!current_user_can('manage_options')) return;
        $year = preg_match('/^20\d{2}$/', (string)$year) ? (string)$year : '';
        if ($year === '') return;
        ?>
        <form id="htp-schedule-csv-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="parcs_ht_schedule_csv_import">
            <input type="hidden" name="season_year" value="<?php echo esc_attr($year); ?>">
            <?php wp_nonce_field('parcs_ht_schedule_csv_import_' . $year); ?>
        </form>
        <?php
    }

    private static function headers() {
        return array(
            'type','enabled','label','start','end','weekdays','open','close','open2','close2','last_entry_minutes','color','priority',
            'context_fr','context_en','context_de','title_fr','title_en','title_de','message_fr','message_en','message_de','show_on_calendar',
            'apply_domain_rules','show_public_marker','display_mode','skip_domain_rules','show_button','button_label_fr','button_label_en','button_label_de','button_url_fr','button_url_en','button_url_de',
            'show_popup','popup_show_dates','popup_show_hours','popup_lead_mode','popup_days_before','popup_start','popup_end','popup_title_fr','popup_title_en','popup_title_de','popup_message_fr','popup_message_en','popup_message_de','popup_show_button','popup_button_label_fr','popup_button_label_en','popup_button_label_de','popup_button_url_fr','popup_button_url_en','popup_button_url_de','popup_image_url',
            'pause_start','resume','last_entry','exclude_weekends','exclude_school_holidays','exclude_public_holidays','auto_details','public_title_fr','public_title_en','public_title_de','access_message_fr','access_message_en','access_message_de','details_message_fr','details_message_en','details_message_de','show_tooltip','tooltip_text_fr','tooltip_text_en','tooltip_text_de','info_fr','info_en','info_de'
        );
    }

    private static function empty_export_row($type) {
        $row = array_fill_keys(self::headers(), '');
        $row['type'] = $type;
        return $row;
    }

    private static function export_translation(&$row, $prefix, $value) {
        $value = is_array($value) ? $value : array();
        foreach (array('fr','en','de') as $lang) $row[$prefix . '_' . $lang] = (string)($value[$lang] ?? '');
    }

    private static function output_row($out, $row) {
        $ordered = array();
        foreach (self::headers() as $header) $ordered[] = isset($row[$header]) ? (string)$row[$header] : '';
        fputcsv($out, $ordered, ';');
    }

    public static function download_template() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_GET['season_year']) ? sanitize_text_field(wp_unslash($_GET['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_schedule_csv_template_' . $year);
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="horaires-calendrier-' . $year . '-modele.csv"');
        $out = fopen('php://output', 'w');
        if (!$out) exit;
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::headers(), ';');

        $row = self::empty_export_row('regular');
        $row = array_merge($row, array('enabled'=>'1','label'=>'Haute saison','start'=>$year.'-07-01','end'=>$year.'-08-31','weekdays'=>'1,2,3,4,5,6,7','open'=>'09:30','close'=>'18:00','last_entry_minutes'=>'30','color'=>'#9AAA8B'));
        self::output_row($out, $row);
        $row = self::empty_export_row('exception_closed');
        $row = array_merge($row, array('enabled'=>'1','label'=>'Fermeture exceptionnelle','start'=>$year.'-11-15','end'=>$year.'-11-15','priority'=>'200','context_fr'=>'Fermeture exceptionnelle','title_fr'=>'Fermeture exceptionnelle','message_fr'=>'Parc fermé ce jour','show_public_marker'=>'1'));
        self::output_row($out, $row);
        $row = self::empty_export_row('event');
        $row = array_merge($row, array('enabled'=>'1','label'=>'Animation spéciale','start'=>$year.'-10-20','end'=>$year.'-10-20','title_fr'=>'Animation spéciale','title_en'=>'Special event','title_de'=>'Sonderveranstaltung','message_fr'=>'Informations à compléter','show_on_calendar'=>'1','display_mode'=>'spot'));
        self::output_row($out, $row);
        $row = self::empty_export_row('limited_access');
        $row = array_merge($row, array('enabled'=>'1','label'=>'Pause équipe','start'=>$year.'-07-01','end'=>$year.'-08-31','weekdays'=>'1,2,3,4,5','pause_start'=>'12:00','resume'=>'13:00','last_entry'=>'11:45','color'=>'#e7c55b','auto_details'=>'1','show_tooltip'=>'1','public_title_fr'=>'Accès temporairement limité'));
        self::output_row($out, $row);
        fclose($out);
        exit;
    }

    public static function export_csv() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_GET['season_year']) ? sanitize_text_field(wp_unslash($_GET['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_schedule_csv_export_' . $year);
        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_die('Saison introuvable.');
        $season = $all['seasons'][$year];
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="horaires-calendrier-' . $year . '-export.csv"');
        $out = fopen('php://output', 'w');
        if (!$out) exit;
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::headers(), ';');

        foreach ((array)($season['regular_periods'] ?? array()) as $item) {
            $row = self::empty_export_row('regular');
            $row = array_merge($row, array('enabled'=>(string)($item['enabled'] ?? '1'),'label'=>(string)($item['label'] ?? ''),'start'=>(string)($item['start'] ?? ''),'end'=>(string)($item['end'] ?? ''),'weekdays'=>implode(',', (array)($item['weekdays'] ?? array())),'open'=>(string)($item['open'] ?? ''),'close'=>(string)($item['close'] ?? ''),'open2'=>(string)($item['open2'] ?? ''),'close2'=>(string)($item['close2'] ?? ''),'last_entry_minutes'=>(string)($item['last_entry_minutes'] ?? ''),'color'=>(string)($item['color'] ?? '')));
            self::output_row($out, $row);
        }
        foreach ((array)($season['exceptions'] ?? array()) as $item) {
            $row = self::empty_export_row(((string)($item['type'] ?? 'hours') === 'closed') ? 'exception_closed' : 'exception_hours');
            $row = array_merge($row, array('enabled'=>(string)($item['enabled'] ?? '1'),'label'=>(string)($item['label'] ?? ''),'start'=>(string)($item['start'] ?? ''),'end'=>(string)($item['end'] ?? ''),'open'=>(string)($item['open'] ?? ''),'close'=>(string)($item['close'] ?? ''),'open2'=>(string)($item['open2'] ?? ''),'close2'=>(string)($item['close2'] ?? ''),'last_entry_minutes'=>(string)($item['last_entry_minutes'] ?? ''),'priority'=>(string)($item['priority'] ?? '100'),'apply_domain_rules'=>(string)($item['apply_domain_rules'] ?? '1'),'show_public_marker'=>(string)($item['show_public_marker'] ?? '1'),'show_popup'=>(string)($item['show_popup'] ?? '0'),'popup_show_dates'=>(string)($item['popup_show_dates'] ?? '1'),'popup_show_hours'=>(string)($item['popup_show_hours'] ?? '1'),'popup_lead_mode'=>(string)($item['popup_lead_mode'] ?? 'days_before'),'popup_days_before'=>(string)($item['popup_days_before'] ?? '1'),'popup_start'=>(string)($item['popup_start'] ?? ''),'popup_end'=>(string)($item['popup_end'] ?? ''),'popup_show_button'=>(string)($item['popup_show_button'] ?? '0')));
            self::export_translation($row, 'context', $item['context'] ?? array()); self::export_translation($row, 'title', $item['title'] ?? array()); self::export_translation($row, 'message', $item['message'] ?? array()); self::export_translation($row, 'popup_button_label', $item['popup_button_label'] ?? array()); self::export_translation($row, 'popup_button_url', $item['popup_button_url'] ?? array());
            self::output_row($out, $row);
        }
        foreach ((array)($season['public_holidays'] ?? array()) as $item) {
            $row = self::empty_export_row('holiday');
            $row = array_merge($row, array('enabled'=>(string)($item['enabled'] ?? '1'),'label'=>(string)($item['label'] ?? ''),'start'=>(string)($item['date'] ?? ''),'end'=>(string)($item['date'] ?? '')));
            self::output_row($out, $row);
        }
        foreach ((array)($season['special_periods'] ?? array()) as $item) {
            $kind = (string)($item['kind'] ?? 'other');
            $type = $kind === 'event' ? 'event' : ($kind === 'school_holiday' ? 'school_holiday' : 'period');
            $row = self::empty_export_row($type);
            $row = array_merge($row, array('enabled'=>(string)($item['enabled'] ?? '1'),'label'=>(string)($item['internal_label'] ?? ''),'start'=>(string)($item['start'] ?? ''),'end'=>(string)($item['end'] ?? ''),'color'=>(string)($item['color'] ?? ''),'show_on_calendar'=>(string)($item['show_on_calendar'] ?? '1'),'display_mode'=>(string)($item['display_mode'] ?? 'spot'),'skip_domain_rules'=>(string)($item['skip_domain_rules'] ?? '0'),'show_button'=>(string)($item['show_button'] ?? '0'),'show_popup'=>(string)($item['show_popup'] ?? '0'),'popup_lead_mode'=>(string)($item['popup_lead_mode'] ?? 'days_before'),'popup_days_before'=>(string)($item['popup_days_before'] ?? '14'),'popup_start'=>(string)($item['popup_start'] ?? ''),'popup_end'=>(string)($item['popup_end'] ?? ''),'popup_show_button'=>(string)($item['popup_show_button'] ?? '0'),'popup_image_url'=>(string)($item['popup_image_url'] ?? '')));
            foreach (array('title','message','button_label','button_url','popup_title','popup_message','popup_button_label','popup_button_url') as $prefix) self::export_translation($row, $prefix, $item[$prefix] ?? array());
            self::output_row($out, $row);
        }
        foreach ((array)($season['domain_rules'] ?? array()) as $item) {
            $row = self::empty_export_row('limited_access');
            $row = array_merge($row, array('enabled'=>(string)($item['enabled'] ?? '0'),'label'=>(string)($item['label'] ?? ''),'start'=>(string)($item['start'] ?? ''),'end'=>(string)($item['end'] ?? ''),'weekdays'=>implode(',', (array)($item['weekdays'] ?? array())),'color'=>(string)($item['color'] ?? '#e7c55b'),'pause_start'=>(string)($item['pause_start'] ?? ''),'resume'=>(string)($item['resume'] ?? ''),'last_entry'=>(string)($item['last_entry'] ?? ''),'exclude_weekends'=>(string)($item['exclude_weekends'] ?? '0'),'exclude_school_holidays'=>(string)($item['exclude_school_holidays'] ?? '0'),'exclude_public_holidays'=>(string)($item['exclude_public_holidays'] ?? '0'),'auto_details'=>(string)($item['auto_details'] ?? '1'),'show_tooltip'=>(string)($item['show_tooltip'] ?? '1')));
            foreach (array('public_title','access_message','details_message','tooltip_text','info') as $prefix) self::export_translation($row, $prefix, $item[$prefix] ?? array());
            self::output_row($out, $row);
        }
        fclose($out);
        exit;
    }

    private static function delimiter($line) {
        $scores = array(';'=>substr_count($line,';'), ','=>substr_count($line,','), "\t"=>substr_count($line,"\t"));
        arsort($scores);
        $delimiter = (string)key($scores);
        return $scores[$delimiter] > 0 ? $delimiter : ';';
    }

    private static function date_value($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        if (!preg_match('/^(20\d{2})-(\d{2})-(\d{2})$/', $value, $matches)) return false;
        return checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]) ? $value : false;
    }

    private static function time_value($value) {
        $value = trim((string)$value);
        if ($value === '') return '';
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : false;
    }

    private static function flag($value, $default = '1') {
        $value = strtolower(trim((string)$value));
        if ($value === '') return $default;
        return in_array($value, array('1','true','oui','yes','on'), true) ? '1' : '0';
    }

    private static function translations($row, $prefix, $textarea = false) {
        $sanitize = $textarea ? 'sanitize_textarea_field' : 'sanitize_text_field';
        return array(
            'fr'=>$sanitize((string)($row[$prefix.'_fr'] ?? '')),
            'en'=>$sanitize((string)($row[$prefix.'_en'] ?? '')),
            'de'=>$sanitize((string)($row[$prefix.'_de'] ?? '')),
        );
    }

    private static function weekdays_value($value, $default = array('1','2','3','4','5','6','7')) {
        $days = array_values(array_intersect(array('1','2','3','4','5','6','7'), preg_split('/\s*[,|]\s*/', (string)$value)));
        return $days ? $days : $default;
    }

    public static function import_csv() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $year = isset($_POST['season_year']) ? sanitize_text_field(wp_unslash($_POST['season_year'])) : '';
        if (!preg_match('/^20\d{2}$/', $year)) wp_die('Année invalide.');
        check_admin_referer('parcs_ht_schedule_csv_import_' . $year);
        if (!isset($_FILES['schedule_csv']) || !is_array($_FILES['schedule_csv'])) wp_die('Aucun fichier CSV reçu.');
        $file = $_FILES['schedule_csv']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- fichier contrôlé ci-dessous.
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) wp_die('Erreur lors de l’envoi du fichier CSV.');
        if ((int)($file['size'] ?? 0) < 1 || (int)$file['size'] > self::MAX_BYTES) wp_die('Le fichier CSV est vide ou trop volumineux.');
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) wp_die('Fichier CSV invalide.');

        $handle = fopen($tmp, 'r');
        if (!$handle) wp_die('Impossible de lire le CSV.');
        $first = fgets($handle);
        if ($first === false) { fclose($handle); wp_die('CSV vide.'); }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = self::delimiter($first);
        rewind($handle);
        $header = fgetcsv($handle, 0, $delimiter);
        if (!is_array($header)) { fclose($handle); wp_die('En-tête CSV invalide.'); }
        $header = array_map(static function($v){ return sanitize_key(trim((string)$v)); }, $header);
        foreach (array('type','start') as $required) if (!in_array($required, $header, true)) { fclose($handle); wp_die('Colonne obligatoire absente : ' . esc_html($required)); }

        $regular = array(); $exceptions = array(); $holidays = array(); $special = array(); $domain = array();
        $seen = array(); $errors = array(); $line = 1;
        $allowed_types = array('regular','exception_hours','exception_closed','holiday','period','school_holiday','event','limited_access');
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (!array_filter($values, static function($v){ return trim((string)$v) !== ''; })) continue;
            $row = array();
            foreach ($header as $i=>$key) if ($key !== '') $row[$key] = isset($values[$i]) ? trim((string)$values[$i]) : '';
            $type = sanitize_key((string)($row['type'] ?? ''));
            if (!in_array($type, $allowed_types, true)) { $errors[] = 'Ligne '.$line.' : type inconnu « '.$type.' ».'; continue; }
            $start = self::date_value($row['start'] ?? '');
            $end = self::date_value(($row['end'] ?? '') !== '' ? $row['end'] : ($row['start'] ?? ''));
            if ($start === false || $end === false || $start === '' || $end === '') { $errors[] = 'Ligne '.$line.' : date invalide.'; continue; }
            if ($end < $start) { $errors[] = 'Ligne '.$line.' : la date de fin précède le début.'; continue; }
            $enabled = self::flag($row['enabled'] ?? '1');
            $label = sanitize_text_field((string)($row['label'] ?? ''));
            $color = sanitize_hex_color((string)($row['color'] ?? '')) ?: ($type === 'event' ? '#e7c55b' : '#9AAA8B');
            $seen[$type] = true;

            if ($type === 'regular') {
                $open = self::time_value($row['open'] ?? ''); $close = self::time_value($row['close'] ?? '');
                $open2 = self::time_value($row['open2'] ?? ''); $close2 = self::time_value($row['close2'] ?? '');
                if ($open === false || $close === false || $open === '' || $close === '' || $open2 === false || $close2 === false) { $errors[] = 'Ligne '.$line.' : horaires invalides.'; continue; }
                $regular[] = array('enabled'=>$enabled,'label'=>$label,'start'=>$start,'end'=>$end,'weekdays'=>self::weekdays_value($row['weekdays'] ?? ''),'open'=>$open,'close'=>$close,'open2'=>$open2,'close2'=>$close2,'last_entry_minutes'=>preg_match('/^\d{1,4}$/',(string)($row['last_entry_minutes']??''))?(string)(int)$row['last_entry_minutes']:'','color'=>$color);
                continue;
            }

            if ($type === 'exception_hours' || $type === 'exception_closed') {
                $open = self::time_value($row['open'] ?? ''); $close = self::time_value($row['close'] ?? '');
                $open2 = self::time_value($row['open2'] ?? ''); $close2 = self::time_value($row['close2'] ?? '');
                if ($type === 'exception_hours' && ($open === false || $close === false || $open === '' || $close === '')) { $errors[] = 'Ligne '.$line.' : horaires exceptionnels invalides.'; continue; }
                $exceptions[] = array('enabled'=>$enabled,'type'=>$type==='exception_closed'?'closed':'hours','label'=>$label,'start'=>$start,'end'=>$end,'open'=>$open===false?'':$open,'close'=>$close===false?'':$close,'open2'=>$open2===false?'':$open2,'close2'=>$close2===false?'':$close2,'last_entry_minutes'=>preg_match('/^\d{1,4}$/',(string)($row['last_entry_minutes']??''))?(string)(int)$row['last_entry_minutes']:'','priority'=>preg_match('/^\d+$/',(string)($row['priority']??''))?(string)(int)$row['priority']:'100','apply_domain_rules'=>self::flag($row['apply_domain_rules'] ?? '1'),'show_public_marker'=>self::flag($row['show_public_marker'] ?? '1'),'context'=>self::translations($row,'context'),'title'=>self::translations($row,'title'),'message'=>self::translations($row,'message',true),'show_popup'=>self::flag($row['show_popup'] ?? '0','0'),'popup_show_dates'=>self::flag($row['popup_show_dates'] ?? '1'),'popup_show_hours'=>self::flag($row['popup_show_hours'] ?? '1'),'popup_mode'=>'auto','popup_title'=>array('fr'=>'','en'=>'','de'=>''),'popup_message'=>array('fr'=>'','en'=>'','de'=>''),'popup_button_label'=>self::translations($row,'popup_button_label'),'popup_button_url'=>self::translations($row,'popup_button_url'),'popup_show_button'=>self::flag($row['popup_show_button'] ?? '0','0'),'popup_lead_mode'=>in_array((string)($row['popup_lead_mode']??''),array('same','days_before','custom'),true)?(string)$row['popup_lead_mode']:'days_before','popup_days_before'=>preg_match('/^\d+$/',(string)($row['popup_days_before']??''))?(string)(int)$row['popup_days_before']:'1','popup_start'=>sanitize_text_field((string)($row['popup_start']??'')),'popup_end'=>sanitize_text_field((string)($row['popup_end']??'')));
                continue;
            }

            if ($type === 'holiday') {
                $holidays[] = array('enabled'=>$enabled,'label'=>$label,'date'=>$start);
                continue;
            }

            if ($type === 'limited_access') {
                $pause = self::time_value($row['pause_start'] ?? ''); $resume = self::time_value($row['resume'] ?? ''); $last = self::time_value($row['last_entry'] ?? '');
                if ($pause === false || $resume === false || $last === false) { $errors[] = 'Ligne '.$line.' : horaires d’accès limité invalides.'; continue; }
                $domain[] = array('enabled'=>$enabled,'label'=>$label,'public_title'=>self::translations($row,'public_title'),'start'=>$start,'end'=>$end,'weekdays'=>self::weekdays_value($row['weekdays'] ?? '',array('1','2','3','4','5')),'pause_start'=>$pause,'resume'=>$resume,'last_entry'=>$last,'exclude_weekends'=>self::flag($row['exclude_weekends'] ?? '0','0'),'exclude_school_holidays'=>self::flag($row['exclude_school_holidays'] ?? '0','0'),'exclude_public_holidays'=>self::flag($row['exclude_public_holidays'] ?? '0','0'),'auto_details'=>self::flag($row['auto_details'] ?? '1'),'color'=>sanitize_hex_color((string)($row['color'] ?? '')) ?: '#e7c55b','access_message'=>self::translations($row,'access_message'),'details_message'=>self::translations($row,'details_message'),'show_tooltip'=>self::flag($row['show_tooltip'] ?? '1'),'tooltip_text'=>self::translations($row,'tooltip_text',true),'info'=>self::translations($row,'info',true));
                continue;
            }

            $kind = $type === 'event' ? 'event' : ($type === 'school_holiday' ? 'school_holiday' : 'other');
            $display_mode = in_array((string)($row['display_mode'] ?? ''), array('spot','long'), true) ? (string)$row['display_mode'] : 'spot';
            $special[] = array('enabled'=>$enabled,'kind'=>$kind,'internal_label'=>$label,'title'=>self::translations($row,'title'),'start'=>$start,'end'=>$end,'color'=>$kind==='event'?'#e7c55b':$color,'icon'=>'star','display_mode'=>$display_mode,'message'=>self::translations($row,'message',true),'button_label'=>self::translations($row,'button_label'),'button_url'=>self::translations($row,'button_url'),'show_button'=>self::flag($row['show_button'] ?? '0','0'),'show_on_calendar'=>self::flag($row['show_on_calendar'] ?? '1'),'skip_domain_rules'=>self::flag($row['skip_domain_rules'] ?? '0','0'),'show_popup'=>self::flag($row['show_popup'] ?? '0','0'),'popup_lead_mode'=>in_array((string)($row['popup_lead_mode']??''),array('same','days_before','custom'),true)?(string)$row['popup_lead_mode']:'days_before','popup_days_before'=>preg_match('/^\d+$/',(string)($row['popup_days_before']??''))?(string)(int)$row['popup_days_before']:'14','popup_start'=>sanitize_text_field((string)($row['popup_start']??'')),'popup_end'=>sanitize_text_field((string)($row['popup_end']??'')),'popup_title'=>self::translations($row,'popup_title'),'popup_message'=>self::translations($row,'popup_message',true),'popup_button_label'=>self::translations($row,'popup_button_label'),'popup_button_url'=>self::translations($row,'popup_button_url'),'popup_show_button'=>self::flag($row['popup_show_button'] ?? '0','0'),'popup_image_url'=>esc_url_raw((string)($row['popup_image_url']??'')));
        }
        fclose($handle);
        if ($errors) wp_die(esc_html(implode("\n", array_slice($errors,0,20))));

        $all = Parcs_HT_Defaults::all_settings();
        if (!isset($all['seasons'][$year]) || !is_array($all['seasons'][$year])) wp_die('Saison introuvable.');
        if (class_exists('Parcs_HT_Health')) Parcs_HT_Health::store_revision($all, $year, 'Avant import CSV horaires/calendrier');
        $season =& $all['seasons'][$year];
        if (isset($seen['regular'])) $season['regular_periods'] = $regular;
        if (isset($seen['exception_hours']) || isset($seen['exception_closed'])) $season['exceptions'] = $exceptions;
        if (isset($seen['holiday'])) $season['public_holidays'] = $holidays;
        if (isset($seen['period']) || isset($seen['school_holiday']) || isset($seen['event'])) {
            $season['special_periods'] = $special;
            $season['school_holidays'] = array();
            foreach ($special as $item) if ($item['enabled']==='1' && $item['kind']==='school_holiday') $season['school_holidays'][] = array('enabled'=>'1','label'=>$item['internal_label'],'start'=>$item['start'],'end'=>$item['end']);
        }
        if (isset($seen['limited_access'])) $season['domain_rules'] = $domain;
        update_option(Parcs_HT_Defaults::OPTION, $all, false);
        wp_safe_redirect(add_query_arg(array('page'=>Parcs_HT_Admin::PAGE,'season'=>$year,'tab'=>'htp-regular','csv_imported'=>1), admin_url('admin.php')));
        exit;
    }
}
