(function($){
'use strict';
$(function(){
    var cfg=window.ParcsHTAdminGroups||{};
    var $nav=$('[data-htp-admin-tabs]');
    if(!$nav.length)return;

    function groupButton(id,label,children){
        var $first=$nav.find('[data-htp-admin-tab="'+children[0].id+'"]');
        var $button=$('<button type="button" class="nav-tab htp-group-tab" role="tab" aria-selected="false"></button>').text(label).attr('data-htp-admin-group',id);
        var $sub=$('<div class="htp-admin-subtabs" hidden></div>').attr('data-htp-subtabs',id);
        if($first.length)$button.insertBefore($first);else $nav.append($button);
        children.forEach(function(child){
            var $old=$nav.find('[data-htp-admin-tab="'+child.id+'"]');
            if(!$old.length)return;
            $sub.append($('<button type="button" class="button htp-admin-subtab"></button>').text(child.label).attr('data-htp-target',child.id));
            $old.remove();
        });
        $sub.insertAfter($nav);
        return {$button:$button,$sub:$sub};
    }

    var schedule=groupButton('schedule','Horaires & calendrier',[
        {id:'htp-regular',label:'Horaires & calendrier'},
        {id:'htp-holidays',label:'Périodes & événements'},
        {id:'htp-exceptions',label:'Exceptions'},
        {id:'htp-domain',label:'Accès limité'}
    ]);

    function activate(target,group){
        $('.htp-admin-tab,.htp-group-tab').removeClass('nav-tab-active').attr('aria-selected','false');
        $('.htp-admin-subtabs[data-htp-subtabs]').attr('hidden',true);
        $('.htp-admin-subtab').removeClass('button-primary');
        if(group){
            $('[data-htp-admin-group="'+group+'"]').addClass('nav-tab-active').attr('aria-selected','true');
            $('[data-htp-subtabs="'+group+'"]').removeAttr('hidden').find('[data-htp-target="'+target+'"]').addClass('button-primary');
        }else $('[data-htp-admin-tab="'+target+'"]').addClass('nav-tab-active').attr('aria-selected','true');
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
        var $contentBtn=$('<button type="button" class="button button-primary">Contenu</button>');
        var $ratesBtn=$('<button type="button" class="button">Tarifs du devis '+(cfg.year||'')+'</button>');
        var $gateBtn=$('<button type="button" class="button">Accès au devis</button>');
        $subnav.append($contentBtn,$ratesBtn,$gateBtn); $quote.prepend($subnav);

        var forms=cfg.forms||{};
        var activeLang='fr';
        var $oldFormInput=$existing.find('input[name="settings[quote_page][form_shortcode]"]');
        var $oldFormField=$oldFormInput.closest('.htp-field');
        var $oldDiagnostic=$existing.find('.htp-cf7-status');
        $oldFormField.hide();
        $oldDiagnostic.hide();
        var $multiForm=$('<div class="htp-field htp-quote-language-form"><span>Shortcode du formulaire Contact Form 7</span><div class="htp-lang-tabs" data-q-lang-tabs><button type="button" class="button button-primary" data-lang="fr">FR</button><button type="button" class="button" data-lang="en">EN</button><button type="button" class="button" data-lang="de">DE</button></div><input type="text" class="large-text code" data-q-form-current placeholder="[contact-form-7 id=&quot;...&quot;]"><p class="description">Chaque langue utilise son propre formulaire Contact Form 7. Un champ vide utilise le formulaire général en secours.</p><p><button type="button" class="button" data-q-forms-save>Enregistrer les formulaires FR / EN / DE</button> <span data-q-forms-status></span></p></div>');
        if($oldFormField.length)$multiForm.insertBefore($oldFormField);else $existing.prepend($multiForm);
        function showLang(lang){activeLang=lang;$multiForm.find('[data-q-form-current]').val(forms[lang]||'');$multiForm.find('[data-lang]').removeClass('button-primary');$multiForm.find('[data-lang="'+lang+'"]').addClass('button-primary');}
        function storeCurrent(){forms[activeLang]=$multiForm.find('[data-q-form-current]').val();}
        $multiForm.on('input','[data-q-form-current]',storeCurrent);
        $multiForm.on('click','[data-lang]',function(){storeCurrent();showLang($(this).data('lang'));});
        showLang('fr');

        var r=cfg.rates||{};
        var $rates=$('<div class="htp-quote-admin-pane" data-htp-quote-pane="rates" hidden><h2>Tarifs du devis groupe — saison '+(cfg.year||'')+'</h2><p class="description">Ces tarifs sont propres au devis groupe de la saison sélectionnée. Ils ne modifient pas le tableau public « Tarifs ».</p><div class="htp-grid htp-grid-3"><label class="htp-field"><span>Disponible pour cette saison</span><span><input type="checkbox" data-q="published"> Autoriser les devis automatiques</span></label><label class="htp-field"><span>Enfant (€)</span><input type="number" min="0" step="0.01" data-q="child"></label><label class="htp-field"><span>Adulte (€)</span><input type="number" min="0" step="0.01" data-q="adult"></label><label class="htp-field"><span>Personne en situation de handicap (€)</span><input type="number" min="0" step="0.01" data-q="disability"></label><label class="htp-field"><span>Accompagnateur (€)</span><input type="number" min="0" step="0.01" data-q="companion"></label><label class="htp-field"><span>1 adulte gratuit pour X enfants</span><input type="number" min="1" step="1" data-q="free_adult_children"></label></div><p><button type="button" class="button button-primary" data-q-save>Enregistrer les tarifs '+(cfg.year||'')+'</button> <span data-q-status></span></p></div>');
        Object.keys(r).forEach(function(k){var $f=$rates.find('[data-q="'+k+'"]');if(k==='published')$f.prop('checked',String(r[k])==='1');else $f.val(r[k]);});
        $quote.append($rates);

        var g=cfg.gate||{};
        var $gate=$('<div class="htp-quote-admin-pane" data-htp-quote-pane="gate" hidden><h2>Accès au devis automatique</h2><p class="description">Réglages du choix de la date avant l’affichage du formulaire complet.</p><div class="htp-check-list"><label><input type="checkbox" data-g="enabled"> Activer le choix de la date avant l’affichage du formulaire complet</label></div><div class="htp-subsection"><h3>Date où le parc est fermé</h3><label><input type="checkbox" data-g="closed_enabled"> Afficher un avertissement</label><div class="htp-lang-tabs" data-g-lang-tabs="closed"><button type="button" class="button button-primary" data-lang="fr">FR</button><button type="button" class="button" data-lang="en">EN</button><button type="button" class="button" data-lang="de">DE</button></div><p><textarea class="large-text" rows="5" data-g-message="closed"></textarea></p><label class="htp-field"><span>Contact ou lien</span><input type="text" data-g="closed_contact"></label><p class="description">Le formulaire reste accessible et le devis reste générable.</p></div><div class="htp-subsection"><h3>Tarifs indisponibles</h3><label><input type="checkbox" data-g="unavailable_enabled"> Afficher le message d’indisponibilité</label><div class="htp-lang-tabs" data-g-lang-tabs="unavailable"><button type="button" class="button button-primary" data-lang="fr">FR</button><button type="button" class="button" data-lang="en">EN</button><button type="button" class="button" data-lang="de">DE</button></div><p><textarea class="large-text" rows="5" data-g-message="unavailable"></textarea></p><label class="htp-field"><span>Contact ou lien</span><input type="text" data-g="unavailable_contact"></label><p class="description">Sans grille tarifaire publiée pour l’année choisie, le formulaire complet reste masqué et aucun devis chiffré ne peut être envoyé.</p></div><p><button type="button" class="button button-primary" data-g-save>Enregistrer l’accès au devis</button> <span data-g-status></span></p></div>');
        ['enabled','closed_enabled','unavailable_enabled'].forEach(function(k){$gate.find('[data-g="'+k+'"]').prop('checked',String(g[k])==='1');});
        ['closed_contact','unavailable_contact'].forEach(function(k){$gate.find('[data-g="'+k+'"]').val(g[k]||'');});

        var gateMessages={
            closed:{fr:g.closed_message_fr||g.closed_message||'',en:g.closed_message_en||'',de:g.closed_message_de||''},
            unavailable:{fr:g.unavailable_message_fr||g.unavailable_message||'',en:g.unavailable_message_en||'',de:g.unavailable_message_de||''}
        };
        var gateLang={closed:'fr',unavailable:'fr'};
        function showGateLang(type,lang){
            gateLang[type]=lang;
            $gate.find('[data-g-message="'+type+'"]').val(gateMessages[type][lang]||'');
            var $tabs=$gate.find('[data-g-lang-tabs="'+type+'"]');
            $tabs.find('[data-lang]').removeClass('button-primary');
            $tabs.find('[data-lang="'+lang+'"]').addClass('button-primary');
        }
        function storeGateMessage(type){gateMessages[type][gateLang[type]]=$gate.find('[data-g-message="'+type+'"]').val();}
        $gate.on('input','[data-g-message]',function(){storeGateMessage($(this).data('g-message'));});
        $gate.on('click','[data-g-lang-tabs] [data-lang]',function(){
            var type=$(this).closest('[data-g-lang-tabs]').data('g-lang-tabs');
            storeGateMessage(type);
            showGateLang(type,$(this).data('lang'));
        });
        showGateLang('closed','fr');
        showGateLang('unavailable','fr');
        $quote.append($gate);

        function quotePane($pane,$btn){$existing.add($rates).add($gate).attr('hidden',true);$contentBtn.add($ratesBtn).add($gateBtn).removeClass('button-primary');$pane.removeAttr('hidden');$btn.addClass('button-primary');}
        $contentBtn.on('click',function(){quotePane($existing,$contentBtn);});
        $ratesBtn.on('click',function(){quotePane($rates,$ratesBtn);});
        $gateBtn.on('click',function(){quotePane($gate,$gateBtn);});

        $multiForm.on('click','[data-q-forms-save]',function(){
            storeCurrent();
            var data={action:'parcs_ht_save_quote_language_forms',nonce:cfg.forms_nonce,fr:forms.fr||'',en:forms.en||'',de:forms.de||''};
            var $status=$multiForm.find('[data-q-forms-status]').text('Enregistrement…');
            $.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});
        });
        $rates.on('click','[data-q-save]',function(){
            var data={action:'parcs_ht_save_quote_season_rates',nonce:cfg.nonce,year:cfg.year,published:$rates.find('[data-q="published"]').is(':checked')?'1':'0'};
            ['child','adult','disability','companion','free_adult_children'].forEach(function(k){data[k]=$rates.find('[data-q="'+k+'"]').val();});
            var $status=$rates.find('[data-q-status]').text('Enregistrement…');
            $.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});
        });
        $gate.on('click','[data-g-save]',function(){
            storeGateMessage('closed');
            storeGateMessage('unavailable');
            var data={action:'parcs_ht_save_quote_gate_settings',nonce:cfg.gate_nonce};
            ['enabled','closed_enabled','unavailable_enabled'].forEach(function(k){data[k]=$gate.find('[data-g="'+k+'"]').is(':checked')?'1':'0';});
            ['closed_contact','unavailable_contact'].forEach(function(k){data[k]=$gate.find('[data-g="'+k+'"]').val();});
            ['fr','en','de'].forEach(function(lang){data['closed_message_'+lang]=gateMessages.closed[lang]||'';data['unavailable_message_'+lang]=gateMessages.unavailable[lang]||'';});
            var $status=$gate.find('[data-g-status]').text('Enregistrement…');
            $.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});
        });
    }
});
})(jQuery);
