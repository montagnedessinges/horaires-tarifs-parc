(function($){
'use strict';
$(function(){
    var cfg=window.ParcsHTGroupTariffSwitch||{};
    if(!cfg.year)return;

    function yes(value){return value?'Oui':'Non';}
    function badge(label,value,ok){
        return '<span style="display:inline-flex;gap:6px;align-items:center;padding:5px 9px;border:1px solid #dcdcde;border-radius:999px;background:#fff"><strong>'+label+'</strong> <span style="color:'+(ok?'#16843d':'#9b2c2c')+'">'+value+'</span></span>';
    }
    function render($box){
        var r=cfg.readiness||{};
        $box.find('[data-gts-date]').val(cfg.displayFrom||'');
        var active=r.active_year||'Aucune';
        var html='';
        html+=badge('Année affichée',active,active===String(cfg.year));
        html+=badge('Grille prête',yes(!!r.grid_ready),!!r.grid_ready);
        html+=badge('Publication groupes',yes(!!r.group_published),!!r.group_published);
        html+=badge('Liaison devis',yes(!!r.quote_binding_valid),!!r.quote_binding_valid);
        html+=badge('Saison publique générale',r.general_published?'Publiée':'Brouillon',true);
        $box.find('[data-gts-statuses]').html(html);
        var next=r.next_switch&&r.next_switch.year?('Prochaine bascule : '+r.next_switch.year+' le '+r.next_switch.date):'Aucune autre bascule programmée.';
        $box.find('[data-gts-next]').text(next);
        var warnings=r.warnings||[];
        var $warnings=$box.find('[data-gts-warnings]').empty();
        if(warnings.length){
            $('<div class="notice notice-warning inline"><p><strong>À vérifier :</strong> '+warnings.map(function(v){return $('<div>').text(v).html();}).join('<br>')+'</p></div>').appendTo($warnings);
        }else{
            $('<div class="notice notice-success inline"><p>Les contrôles de cette année ne signalent pas de problème de bascule.</p></div>').appendTo($warnings);
        }
    }

    function mount(){
        var $parent=$('[data-htp-group-tariff-settings]');
        if(!$parent.length||$parent.find('[data-gts-panel]').length)return;
        var $box=$('<div class="htp-subsection" data-gts-panel><h3>Bascule commerciale des tarifs groupes</h3><p class="description">Cette date pilote uniquement <code>[parc_tarifs_groupes]</code>. La saison publique générale peut rester sur l’année précédente. Le devis, lui, utilise toujours l’année de la date de visite.</p><div class="htp-grid htp-grid-2"><label class="htp-field"><span>Afficher les tarifs groupes de '+String(cfg.year)+' à partir du</span><input type="date" data-gts-date></label><div class="htp-field"><span>État des contrôles</span><div data-gts-statuses style="display:flex;flex-wrap:wrap;gap:8px"></div><small data-gts-next></small></div></div><div data-gts-warnings></div><p><button type="button" class="button" data-gts-save>Enregistrer la date de bascule</button> <span data-gts-save-status></span></p><p class="description">Si la date reste vide, l’année devient affichable à partir du 1er janvier de cette année. Une bascule programmée déclenche aussi une purge du cache LiteSpeed au moment prévu.</p></div>');
        var $intro=$parent.children('.description').first();
        if($intro.length)$box.insertAfter($intro);else $parent.prepend($box);
        render($box);
        $box.on('click','[data-gts-save]',function(){
            var $status=$box.find('[data-gts-save-status]').text('Enregistrement…');
            $.post(ajaxurl,{action:'parcs_ht_save_group_tariff_switch',nonce:cfg.nonce,year:cfg.year,display_from:$box.find('[data-gts-date]').val()||''})
                .done(function(res){
                    if(res&&res.success){
                        cfg.displayFrom=res.data.displayFrom||'';
                        cfg.readiness=res.data.readiness||{};
                        $status.text(res.data.message||'Enregistré.');
                        render($box);
                    }else{
                        $status.text((res&&res.data&&res.data.message)||'Erreur.');
                    }
                })
                .fail(function(xhr){
                    var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;
                    $status.text(m||'Erreur lors de l’enregistrement.');
                });
        });
    }

    mount();
    var root=document.getElementById('htp-tariffs');
    if(root&&window.MutationObserver){
        new MutationObserver(mount).observe(root,{childList:true,subtree:true});
    }
});
})(jQuery);
