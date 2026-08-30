(function($){
'use strict';
$(function(){
    var cfg=window.ParcsHTAdminGroups||{};
    var $nav=$('[data-htp-admin-tabs]');
    if(!$nav.length)return;

    function groupButton(id,label,children){
        var $button=$('<button type="button" class="nav-tab htp-admin-tab htp-group-tab" role="tab" aria-selected="false"></button>').text(label).attr('data-htp-admin-group',id);
        var $sub=$('<div class="htp-admin-subtabs" hidden></div>').attr('data-htp-subtabs',id);
        children.forEach(function(child){
            var $old=$nav.find('[data-htp-admin-tab="'+child.id+'"]');
            if(!$old.length)return;
            $sub.append($('<button type="button" class="button htp-admin-subtab"></button>').text(child.label).attr('data-htp-target',child.id));
            $old.remove();
        });
        $nav.append($button).after($sub);
        return {$button:$button,$sub:$sub};
    }

    var schedule=groupButton('schedule','Horaires & calendrier',[
        {id:'htp-regular',label:'Horaires & calendrier'},
        {id:'htp-holidays',label:'Périodes & événements'},
        {id:'htp-exceptions',label:'Exceptions'},
        {id:'htp-domain',label:'Accès limité'}
    ]);

    var $quoteOld=$nav.find('[data-htp-admin-tab="htp-quote"]');
    if($quoteOld.length){
        $quoteOld.text('Devis groupe');
    }

    function activate(target,group){
        $('.htp-admin-tab').removeClass('nav-tab-active').attr('aria-selected','false');
        $('.htp-admin-subtabs').attr('hidden',true);
        $('.htp-admin-subtab').removeClass('button-primary');
        if(group){
            $('[data-htp-admin-group="'+group+'"]').addClass('nav-tab-active').attr('aria-selected','true');
            $('[data-htp-subtabs="'+group+'"]').removeAttr('hidden').find('[data-htp-target="'+target+'"]').addClass('button-primary');
        }else{
            $('[data-htp-admin-tab="'+target+'"]').addClass('nav-tab-active').attr('aria-selected','true');
        }
        $('.htp-card[id^="htp-"]').attr('hidden',true);
        $('#'+target).removeAttr('hidden');
        $('[data-htp-active-tab-input]').val(target);
    }

    schedule.$button.on('click',function(){activate('htp-regular','schedule');});
    schedule.$sub.on('click','[data-htp-target]',function(){activate($(this).data('htp-target'),'schedule');});

    var $quote=$('#htp-quote');
    if($quote.length){
        var $existing=$quote.children().wrapAll('<div class="htp-quote-admin-pane" data-htp-quote-pane="content"></div>').parent();
        var $subnav=$('<div class="htp-admin-subtabs htp-quote-subtabs"></div>');
        var $contentBtn=$('<button type="button" class="button button-primary">Contenu & formulaire</button>');
        var $ratesBtn=$('<button type="button" class="button">Tarifs du devis '+(cfg.year||'')+'</button>');
        $subnav.append($contentBtn,$ratesBtn);
        $quote.prepend($subnav);
        var r=cfg.rates||{};
        var $rates=$('<div class="htp-quote-admin-pane" data-htp-quote-pane="rates" hidden><h2>Tarifs du devis groupe — saison '+(cfg.year||'')+'</h2><p class="description">Ces tarifs sont propres au devis groupe de la saison sélectionnée. Ils ne modifient pas le tableau public « Tarifs ».</p><div class="htp-grid htp-grid-3"><label class="htp-field"><span>Disponible pour cette saison</span><span><input type="checkbox" data-q="published"> Autoriser les devis automatiques</span></label><label class="htp-field"><span>Enfant (€)</span><input type="number" min="0" step="0.01" data-q="child"></label><label class="htp-field"><span>Adulte (€)</span><input type="number" min="0" step="0.01" data-q="adult"></label><label class="htp-field"><span>Personne en situation de handicap (€)</span><input type="number" min="0" step="0.01" data-q="disability"></label><label class="htp-field"><span>Accompagnateur (€)</span><input type="number" min="0" step="0.01" data-q="companion"></label><label class="htp-field"><span>1 adulte gratuit pour X enfants</span><input type="number" min="1" step="1" data-q="free_adult_children"></label></div><p><button type="button" class="button button-primary" data-q-save>Enregistrer les tarifs '+(cfg.year||'')+'</button> <span data-q-status></span></p></div>');
        Object.keys(r).forEach(function(k){var $f=$rates.find('[data-q="'+k+'"]');if(k==='published')$f.prop('checked',String(r[k])==='1');else $f.val(r[k]);});
        $quote.append($rates);
        $contentBtn.on('click',function(){$rates.attr('hidden',true);$existing.removeAttr('hidden');$contentBtn.addClass('button-primary');$ratesBtn.removeClass('button-primary');});
        $ratesBtn.on('click',function(){$existing.attr('hidden',true);$rates.removeAttr('hidden');$ratesBtn.addClass('button-primary');$contentBtn.removeClass('button-primary');});
        $rates.on('click','[data-q-save]',function(){
            var data={action:'parcs_ht_save_quote_season_rates',nonce:cfg.nonce,year:cfg.year,published:$rates.find('[data-q="published"]').is(':checked')?'1':'0'};
            ['child','adult','disability','companion','free_adult_children'].forEach(function(k){data[k]=$rates.find('[data-q="'+k+'"]').val();});
            var $status=$rates.find('[data-q-status]').text('Enregistrement…');
            $.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});
        });
    }
});
})(jQuery);
