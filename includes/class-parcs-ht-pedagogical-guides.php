<?php

if (!defined('ABSPATH')) { exit; }

/** Bibliothèque de guides pédagogiques multilingues. */
final class Parcs_HT_Pedagogical_Guides {
    const OPTION = 'parcs_ht_pedagogical_guides';
    const PAGE = 'parcs-ht-pedagogical-guides';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_parcs_ht_save_pedagogical_guides', array(__CLASS__, 'save'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
        add_shortcode('parc_guides_pedagogiques', array(__CLASS__, 'shortcode'));
        foreach (array('fr','en','de') as $language) {
            add_shortcode('parc_guides_pedagogiques_' . $language, static function ($atts = array()) use ($language) {
                return Parcs_HT_Pedagogical_Guides::render($language, is_array($atts) ? $atts : array());
            });
        }
    }

    public static function defaults() {
        return array(
            'cycles'=>array(
                'cycle1'=>array('title'=>array('fr'=>'Cycle 1 – Maternelle','en'=>'Cycle 1 – Nursery school','de'=>'Zyklus 1 – Kindergarten'),'order'=>10),
                'cycle2'=>array('title'=>array('fr'=>'Cycle 2 – Élémentaire','en'=>'Cycle 2 – Primary school','de'=>'Zyklus 2 – Grundschule'),'order'=>20),
                'multi'=>array('title'=>array('fr'=>'Multiniveaux','en'=>'Multi-level','de'=>'Mehrstufig'),'order'=>30),
            ),
            'guides'=>array(),
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION, array());
        $saved = is_array($saved) ? $saved : array();
        $defaults = self::defaults();
        $out = $defaults;
        if (isset($saved['cycles']) && is_array($saved['cycles'])) {
            foreach ($defaults['cycles'] as $id=>$cycle) {
                if (!isset($saved['cycles'][$id]) || !is_array($saved['cycles'][$id])) continue;
                $out['cycles'][$id] = array_merge($cycle, $saved['cycles'][$id]);
            }
        }
        $out['guides'] = isset($saved['guides']) && is_array($saved['guides']) ? array_values($saved['guides']) : array();
        return $out;
    }

    public static function menu() {
        add_submenu_page(Parcs_HT_Admin::PAGE, 'Guides pédagogiques', 'Guides pédagogiques', 'manage_options', self::PAGE, array(__CLASS__, 'page'));
    }

    public static function register_assets() {
        wp_register_style('parcs-ht-pedagogical-guides', PARCS_HT_URL . 'assets/pedagogical-guides.css', array(), PARCS_HT_VERSION);
    }

    public static function admin_assets($hook) {
        if ($hook !== 'horaires-du-parc_page_' . self::PAGE && $hook !== 'parcs-horaires-tarifs_page_' . self::PAGE) return;
        wp_enqueue_media();
        wp_enqueue_style('parcs-ht-pedagogical-guides-admin', PARCS_HT_URL . 'assets/pedagogical-guides-admin.css', array(), PARCS_HT_VERSION);
        wp_enqueue_script('jquery-ui-sortable');
    }

    private static function translations($value, $textarea = false) {
        $out = array('fr'=>'','en'=>'','de'=>'');
        $value = is_array($value) ? $value : array();
        foreach ($out as $lang=>$unused) {
            $raw = isset($value[$lang]) ? wp_unslash((string)$value[$lang]) : '';
            $out[$lang] = $textarea ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        }
        return $out;
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('parcs_ht_save_pedagogical_guides');
        $raw = isset($_POST['guides']) && is_array($_POST['guides']) ? $_POST['guides'] : array();
        $defaults = self::defaults();
        $out = array('cycles'=>array(),'guides'=>array());
        foreach ($defaults['cycles'] as $id=>$cycle) {
            $posted = isset($raw['cycles'][$id]) && is_array($raw['cycles'][$id]) ? $raw['cycles'][$id] : array();
            $out['cycles'][$id] = array(
                'title'=>self::translations($posted['title'] ?? $cycle['title']),
                'order'=>isset($posted['order']) ? (int)$posted['order'] : (int)$cycle['order'],
            );
        }
        $allowed_cycles = array_keys($defaults['cycles']);
        $allowed_status = array('available','new','coming');
        foreach (isset($raw['items']) && is_array($raw['items']) ? $raw['items'] : array() as $item) {
            if (!is_array($item)) continue;
            $cycle = sanitize_key($item['cycle'] ?? ''); if (!in_array($cycle,$allowed_cycles,true)) $cycle='cycle1';
            $status = sanitize_key($item['status'] ?? 'available'); if (!in_array($status,$allowed_status,true)) $status='available';
            $languages = array();
            foreach (array('fr','en','de') as $lang) if (!empty($item['languages'][$lang])) $languages[]=$lang;
            if (!$languages) $languages=array('fr');
            $title = self::translations($item['title'] ?? array());
            if (!array_filter($title) && empty($item['pdf_url']) && $status !== 'coming') continue;
            $out['guides'][] = array(
                'enabled'=>!empty($item['enabled']) ? '1' : '0',
                'cycle'=>$cycle,
                'languages'=>$languages,
                'status'=>$status,
                'title'=>$title,
                'description'=>self::translations($item['description'] ?? array(), true),
                'pdf_url'=>esc_url_raw(wp_unslash((string)($item['pdf_url'] ?? ''))),
                'cover_url'=>esc_url_raw(wp_unslash((string)($item['cover_url'] ?? ''))),
                'order'=>isset($item['order']) ? (int)$item['order'] : 0,
            );
        }
        update_option(self::OPTION, $out, false);
        wp_safe_redirect(add_query_arg(array('page'=>self::PAGE,'updated'=>'1'), admin_url('admin.php')));
        exit;
    }

    private static function tr($values, $language, $fallback = '') {
        $values = is_array($values) ? $values : array();
        $value = trim((string)($values[$language] ?? ''));
        if ($value !== '') return $value;
        $fr = trim((string)($values['fr'] ?? ''));
        if ($fr !== '') return $fr;
        foreach (array('de','en') as $lang) { $v=trim((string)($values[$lang] ?? '')); if($v!=='') return $v; }
        return $fallback;
    }

    private static function language_meta($language) {
        $all = array(
            'fr'=>array('flag'=>'🇫🇷','fr'=>'Français','en'=>'French','de'=>'Französisch'),
            'de'=>array('flag'=>'🇩🇪','fr'=>'Allemand','en'=>'German','de'=>'Deutsch'),
            'en'=>array('flag'=>'🇬🇧','fr'=>'Anglais','en'=>'English','de'=>'Englisch'),
        );
        return $all[$language] ?? array('flag'=>'','fr'=>$language,'en'=>$language,'de'=>$language);
    }

    public static function shortcode($atts = array()) {
        return self::render(Parcs_HT_Schedule::language(), is_array($atts) ? $atts : array());
    }

    public static function render($language, $atts = array()) {
        $language = in_array($language,array('fr','en','de'),true) ? $language : 'fr';
        wp_enqueue_style('parcs-ht-pedagogical-guides');
        $settings = self::settings();
        $cycle_filter = sanitize_key($atts['cycle'] ?? '');
        $cycles = $settings['cycles'];
        uasort($cycles, static function($a,$b){ return ((int)($a['order']??0)) <=> ((int)($b['order']??0)); });
        if ($cycle_filter !== '' && isset($cycles[$cycle_filter])) $cycles = array($cycle_filter=>$cycles[$cycle_filter]);
        $guides = array_values(array_filter($settings['guides'], static function($g){return is_array($g) && (string)($g['enabled']??'0')==='1';}));
        usort($guides, static function($a,$b) use ($language){
            $ap=in_array($language,(array)($a['languages']??array()),true)?0:1; $bp=in_array($language,(array)($b['languages']??array()),true)?0:1;
            if($ap!==$bp)return $ap<=>$bp; return ((int)($a['order']??0))<=>((int)($b['order']??0));
        });
        $ui = array(
            'fr'=>array('resources'=>'Ressources pédagogiques','available'=>'Disponible','new'=>'Nouveau','coming'=>'À venir','read'=>'Consulter','download'=>'Télécharger le PDF','other'=>'Autres langues disponibles'),
            'en'=>array('resources'=>'Teaching resources','available'=>'Available','new'=>'New','coming'=>'Coming soon','read'=>'View','download'=>'Download PDF','other'=>'Other available languages'),
            'de'=>array('resources'=>'Pädagogische Materialien','available'=>'Verfügbar','new'=>'Neu','coming'=>'Demnächst','read'=>'Ansehen','download'=>'PDF herunterladen','other'=>'Weitere verfügbare Sprachen'),
        ); $u=$ui[$language];
        $id='parcs-ht-guides-'.wp_rand(1000,999999);
        ob_start(); ?>
        <section id="<?php echo esc_attr($id); ?>" class="parcs-ht-guides" data-htp-guides>
            <header class="parcs-ht-guides-head"><h2><?php echo esc_html($u['resources']); ?></h2></header>
            <div class="parcs-ht-guide-cycle-nav" role="tablist">
                <?php $first=true; foreach($cycles as $cycle_id=>$cycle):
                    $cycle_guides=array_values(array_filter($guides,static function($g)use($cycle_id){return ($g['cycle']??'')===$cycle_id;}));
                    $langs=array(); foreach($cycle_guides as $g) foreach((array)($g['languages']??array()) as $lg)$langs[$lg]=true;
                    ?>
                    <button type="button" class="parcs-ht-guide-cycle-button<?php echo $first?' is-active':''; ?>" data-guide-cycle-button="<?php echo esc_attr($cycle_id); ?>" aria-selected="<?php echo $first?'true':'false'; ?>">
                        <span><?php echo esc_html(self::tr($cycle['title'],$language)); ?></span>
                        <span class="parcs-ht-guide-cycle-flags" aria-label="Langues disponibles"><?php foreach(array_keys($langs) as $lg){$m=self::language_meta($lg);echo '<span title="'.esc_attr($m[$language]).'">'.esc_html($m['flag']).'</span>';} ?></span>
                    </button>
                <?php $first=false; endforeach; ?>
            </div>
            <?php $first=true; foreach($cycles as $cycle_id=>$cycle): $cycle_guides=array_values(array_filter($guides,static function($g)use($cycle_id){return ($g['cycle']??'')===$cycle_id;})); ?>
                <div class="parcs-ht-guide-cycle-panel" data-guide-cycle-panel="<?php echo esc_attr($cycle_id); ?>" <?php if(!$first)echo 'hidden'; ?>>
                    <h3><?php echo esc_html(self::tr($cycle['title'],$language)); ?></h3>
                    <div class="parcs-ht-guide-grid">
                    <?php foreach($cycle_guides as $guide):
                        $status=(string)($guide['status']??'available'); $langs=(array)($guide['languages']??array()); $pdf=trim((string)($guide['pdf_url']??'')); ?>
                        <article class="parcs-ht-guide-card<?php echo $status==='coming'?' is-coming':''; ?>">
                            <div class="parcs-ht-guide-cover">
                                <?php if(!empty($guide['cover_url'])):?><img src="<?php echo esc_url($guide['cover_url']); ?>" alt="" loading="lazy"><?php else:?><span class="parcs-ht-guide-book" aria-hidden="true">📘</span><?php endif; ?>
                                <?php if($status==='new'):?><span class="parcs-ht-guide-status is-new"><?php echo esc_html($u['new']); ?></span><?php elseif($status==='coming'):?><span class="parcs-ht-guide-status is-coming"><?php echo esc_html($u['coming']); ?></span><?php endif; ?>
                            </div>
                            <div class="parcs-ht-guide-body">
                                <div class="parcs-ht-guide-languages"><?php foreach($langs as $lg){$m=self::language_meta($lg);echo '<span class="parcs-ht-guide-language">'.esc_html($m['flag'].' '.$m[$language]).'</span>';} ?></div>
                                <h4><?php echo esc_html(self::tr($guide['title'],$language)); ?></h4>
                                <?php $desc=self::tr($guide['description'],$language); if($desc!==''):?><p><?php echo nl2br(esc_html($desc)); ?></p><?php endif; ?>
                                <?php if($status!=='coming' && $pdf!==''):?><div class="parcs-ht-guide-actions"><a class="parcs-ht-guide-primary" href="<?php echo esc_url($pdf); ?>" target="_blank" rel="noopener"><?php echo esc_html($u['read']); ?></a><a class="parcs-ht-guide-secondary" href="<?php echo esc_url($pdf); ?>" download><?php echo esc_html($u['download']); ?></a></div><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php $first=false; endforeach; ?>
        </section>
        <script>(function(){var root=document.getElementById(<?php echo wp_json_encode($id); ?>);if(!root)return;root.querySelectorAll('[data-guide-cycle-button]').forEach(function(btn){btn.addEventListener('click',function(){var id=btn.getAttribute('data-guide-cycle-button');root.querySelectorAll('[data-guide-cycle-button]').forEach(function(b){var on=b===btn;b.classList.toggle('is-active',on);b.setAttribute('aria-selected',on?'true':'false');});root.querySelectorAll('[data-guide-cycle-panel]').forEach(function(p){p.hidden=p.getAttribute('data-guide-cycle-panel')!==id;});});});}());</script>
        <?php return ob_get_clean();
    }

    private static function lang_fields($base, $values, $textarea = false) {
        $values=is_array($values)?$values:array(); foreach(array('fr'=>'FR','en'=>'EN','de'=>'DE') as $lang=>$label): ?>
        <label class="htp-guide-lang-field"><span><?php echo esc_html($label); ?></span><?php if($textarea):?><textarea name="<?php echo esc_attr($base.'['.$lang.']'); ?>" rows="2"><?php echo esc_textarea($values[$lang]??''); ?></textarea><?php else:?><input type="text" name="<?php echo esc_attr($base.'['.$lang.']'); ?>" value="<?php echo esc_attr($values[$lang]??''); ?>"><?php endif; ?></label>
        <?php endforeach;
    }

    public static function page() {
        if(!current_user_can('manage_options'))return; $s=self::settings(); ?>
        <div class="wrap htp-guides-admin"><h1>Guides pédagogiques</h1><p>Gérez ici les dossiers affichés par le shortcode <code>[parc_guides_pedagogiques]</code>. Les visiteurs voient d'abord leur langue, mais les autres langues restent accessibles.</p>
        <?php if(isset($_GET['updated'])):?><div class="notice notice-success is-dismissible"><p>Les guides pédagogiques ont été enregistrés.</p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="parcs_ht_save_pedagogical_guides"><?php wp_nonce_field('parcs_ht_save_pedagogical_guides'); ?>
        <section class="htp-guide-admin-card"><h2>Cycles</h2><p class="description">Les trois familles restent stables, mais leurs titres sont modifiables dans les trois langues.</p><?php foreach($s['cycles'] as $id=>$cycle):?><div class="htp-guide-cycle-admin"><strong><?php echo esc_html($id); ?></strong><input type="number" name="guides[cycles][<?php echo esc_attr($id); ?>][order]" value="<?php echo (int)$cycle['order']; ?>" class="small-text" title="Ordre"><?php self::lang_fields('guides[cycles]['.$id.'][title]',$cycle['title']); ?></div><?php endforeach; ?></section>
        <section class="htp-guide-admin-card"><div class="htp-guide-admin-head"><h2>Documents</h2><button type="button" class="button" data-add-guide>Ajouter un guide</button></div><div data-guide-list><?php foreach($s['guides'] as $i=>$guide) self::guide_admin_row($i,$guide); ?></div></section>
        <?php submit_button('Enregistrer les guides'); ?></form>
        <template id="htp-guide-template"><?php self::guide_admin_row('__INDEX__',array('enabled'=>'1','cycle'=>'cycle1','languages'=>array('fr'),'status'=>'available','title'=>array(),'description'=>array(),'pdf_url'=>'','cover_url'=>'','order'=>0)); ?></template>
        <script>(function($){var list=$('[data-guide-list]');$('[data-add-guide]').on('click',function(){var i=Date.now(),html=$('#htp-guide-template').html().replaceAll('__INDEX__',i);list.append(html);});$(document).on('click','[data-remove-guide]',function(){$(this).closest('[data-guide-row]').remove();});$(document).on('click','[data-media-field]',function(){var btn=$(this),target=btn.siblings('input[type=url]'),type=btn.data('media-field'),frame=wp.media({title:type==='pdf'?'Choisir un PDF':'Choisir une image',multiple:false,library:type==='pdf'?{type:'application/pdf'}:{type:'image'}});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();target.val(a.url);});frame.open();});if($.fn.sortable)list.sortable({items:'[data-guide-row]',handle:'[data-guide-handle]',update:function(){list.children('[data-guide-row]').each(function(i){$(this).find('[data-guide-order]').val((i+1)*10);});}});})(jQuery);</script>
        </div><?php
    }

    private static function guide_admin_row($i,$guide) {
        $guide=wp_parse_args($guide,array('enabled'=>'1','cycle'=>'cycle1','languages'=>array('fr'),'status'=>'available','title'=>array(),'description'=>array(),'pdf_url'=>'','cover_url'=>'','order'=>0)); $base='guides[items]['.$i.']'; ?>
        <article class="htp-guide-admin-row" data-guide-row><div class="htp-guide-row-head"><button type="button" class="button" data-guide-handle title="Déplacer">↕</button><label><input type="checkbox" name="<?php echo esc_attr($base.'[enabled]'); ?>" value="1" <?php checked($guide['enabled'],'1'); ?>> Publié</label><select name="<?php echo esc_attr($base.'[cycle]'); ?>"><option value="cycle1" <?php selected($guide['cycle'],'cycle1'); ?>>Cycle 1 – Maternelle</option><option value="cycle2" <?php selected($guide['cycle'],'cycle2'); ?>>Cycle 2 – Élémentaire</option><option value="multi" <?php selected($guide['cycle'],'multi'); ?>>Multiniveaux</option></select><select name="<?php echo esc_attr($base.'[status]'); ?>"><option value="available" <?php selected($guide['status'],'available'); ?>>Disponible</option><option value="new" <?php selected($guide['status'],'new'); ?>>Nouveau</option><option value="coming" <?php selected($guide['status'],'coming'); ?>>À venir</option></select><input type="number" class="small-text" data-guide-order name="<?php echo esc_attr($base.'[order]'); ?>" value="<?php echo (int)$guide['order']; ?>"><button type="button" class="button-link-delete" data-remove-guide>Supprimer</button></div>
        <div class="htp-guide-languages-admin"><strong>Langue du document :</strong><?php foreach(array('fr'=>'🇫🇷 Français','de'=>'🇩🇪 Deutsch','en'=>'🇬🇧 English') as $lang=>$label):?><label><input type="checkbox" name="<?php echo esc_attr($base.'[languages]['.$lang.']'); ?>" value="1" <?php checked(in_array($lang,(array)$guide['languages'],true)); ?>> <?php echo esc_html($label); ?></label><?php endforeach; ?></div>
        <div class="htp-guide-admin-grid"><div><h4>Titre</h4><?php self::lang_fields($base.'[title]',$guide['title']); ?></div><div><h4>Description</h4><?php self::lang_fields($base.'[description]',$guide['description'],true); ?></div></div>
        <div class="htp-guide-media-grid"><label>PDF <span><input type="url" name="<?php echo esc_attr($base.'[pdf_url]'); ?>" value="<?php echo esc_attr($guide['pdf_url']); ?>"><button type="button" class="button" data-media-field="pdf">Choisir</button></span></label><label>Couverture <span><input type="url" name="<?php echo esc_attr($base.'[cover_url]'); ?>" value="<?php echo esc_attr($guide['cover_url']); ?>"><button type="button" class="button" data-media-field="image">Choisir</button></span></label></div></article>
        <?php
    }
}
