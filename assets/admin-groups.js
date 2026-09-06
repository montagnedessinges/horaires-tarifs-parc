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

    var $quoteTab=$nav.find('[data-htp-admin-tab="htp-quote"]');
    var $groupsButton=$('<button type="button" class="nav-tab htp-group-tab" role="tab" aria-selected="false" data-htp-admin-group="groups">Groupes</button>');
    if($quoteTab.length)$groupsButton.insertBefore($quoteTab);else $nav.append($groupsButton);
    $quoteTab.remove();
    var $groupsSub=$('<div class="htp-admin-subtabs" data-htp-subtabs="groups" hidden></div>');
    var $groupsTariffs=$('<button type="button" class="button htp-admin-subtab" data-htp-target="htp-tariffs-groups">Tarifs</button>');
    var $groupsQuote=$('<button type="button" class="button htp-admin-subtab" data-htp-target="htp-quote">Devis</button>');
    var $groupsGuides=$('<button type="button" class="button htp-admin-subtab" data-htp-target="htp-guides">Guides pédagogiques</button>');
    $groupsSub.append($groupsTariffs,$groupsQuote,$groupsGuides).insertAfter($nav);

    function tagLegacyGroupFields(){
        var $tariffs=$('#htp-tariffs');
        if(!$tariffs.length)return;
        $tariffs.children('h3').each(function(){
            if($.trim($(this).text())!=='Informations et réservation des groupes')return;
            var $title=$(this),$next=$title.next();
            $title.attr('data-htp-group-info','1');
            while($next.length && !$next.is('details,.htp-tariff-groups')){
                $next.attr('data-htp-group-info','1');
                $next=$next.next();
            }
        });
    }
    tagLegacyGroupFields();

    function resetTariffView(){
        var $tariffs=$('#htp-tariffs');
        $tariffs.find('[data-htp-tariff-group]').removeAttr('hidden');
        $tariffs.find('[data-htp-tariff-group="groups"]').attr('hidden',true);
        $tariffs.find('[data-htp-group-context],[data-htp-group-info]').attr('hidden',true);
    }

    function activate(target,group,saveTarget){
        $('.htp-admin-tab,.htp-group-tab').removeClass('nav-tab-active').attr('aria-selected','false');
        $('.htp-admin-subtabs[data-htp-subtabs]').attr('hidden',true);
        $('.htp-admin-subtab').removeClass('button-primary');
        if(group){
            $('[data-htp-admin-group="'+group+'"]').addClass('nav-tab-active').attr('aria-selected','true');
            $('[data-htp-subtabs="'+group+'"]').removeAttr('hidden').find('[data-htp-target="'+target+'"]').addClass('button-primary');
        }else $('[data-htp-admin-tab="'+target+'"]').addClass('nav-tab-active').attr('aria-selected','true');
        $('.htp-card[id^="htp-"]').attr('hidden',true);
        var visualTarget=target==='htp-tariffs-groups'?'htp-tariffs':target;
        $('#'+visualTarget).removeAttr('hidden');
        $('[data-htp-active-tab-input]').val(saveTarget||visualTarget);
        if(target!=='htp-tariffs-groups')resetTariffView();
        if(window.history&&window.history.replaceState){
            try{var url=new URL(window.location.href);url.searchParams.set('tab',target);window.history.replaceState(null,'',url.toString());}catch(error){}
        }
    }

    schedule.$button.on('click',function(){activate('htp-regular','schedule');});
    schedule.$sub.on('click','[data-htp-target]',function(){activate($(this).data('htp-target'),'schedule');});

    function escaped(value){return $('<div>').text(String(value==null?'':value)).html();}
    function optionList(items,valueKey,labelKey,selected){
        return (items||[]).map(function(item){
            var value=String(item[valueKey]||'');
            var rawLabel=String(item[labelKey]||value);
            var label=rawLabel+(value&&rawLabel.indexOf(value)===-1?' — '+value:'');
            return '<option value="'+escaped(value)+'"'+(String(selected)===value?' selected':'')+'>'+escaped(label)+'</option>';
        }).join('');
    }

    function decorateTariffIdentities(){
        var identities=cfg.tariff_identities||{};
        $('#htp-tariffs [data-htp-tariff-group]').each(function(){
            var $group=$(this),group=String($group.data('htp-tariff-group')||''),data=identities[group]||{rows:[],columns:[]};
            $group.find('.htp-tariff-repeater > .htp-repeater-rows > [data-htp-tariff-row]').each(function(index){
                var $row=$(this),stored=data.rows&&data.rows[index]?String(data.rows[index].id||''):'';
                if(!$row.find('[data-htp-tariff-row-id-input]').length){
                    var name='settings[tariffs]['+group+']['+index+'][id]';
                    $('<input type="hidden" data-htp-tariff-row-id-input>').attr('name',name).val(stored).appendTo($row);
                }
                var value=String($row.find('[data-htp-tariff-row-id-input]').val()||'');
                var $head=$row.find('.htp-row-head').first();
                if(!$head.find('[data-htp-tariff-id-badge]').length){
                    $('<code class="htp-tariff-id-badge" data-htp-tariff-id-badge></code>').text(/^tariff_row_\d{6,}$/.test(value)?value:'ID attribué à l’enregistrement').insertAfter($head.find('strong').first());
                }else $head.find('[data-htp-tariff-id-badge]').text(/^tariff_row_\d{6,}$/.test(value)?value:'ID attribué à l’enregistrement');
            });
            $group.find('[data-htp-tariff-columns] > [data-htp-tariff-column]').each(function(){
                var $column=$(this),value=String($column.find('[data-htp-column-id-input]').val()||$column.attr('data-col-id')||'');
                if(!$column.find('[data-htp-tariff-col-id-badge]').length){
                    $('<code class="htp-tariff-id-badge" data-htp-tariff-col-id-badge></code>').text(/^tariff_col_\d{6,}$/.test(value)?value:'ID attribué à l’enregistrement').insertAfter($column.find('.htp-sort-handle-column').first());
                }else $column.find('[data-htp-tariff-col-id-badge]').text(/^tariff_col_\d{6,}$/.test(value)?value:'ID attribué à l’enregistrement');
            });
        });
    }
    decorateTariffIdentities();
    var tariffRoot=document.querySelector('#htp-tariffs');
    if(tariffRoot&&window.MutationObserver){new MutationObserver(function(){decorateTariffIdentities();}).observe(tariffRoot,{childList:true,subtree:true});}
    document.addEventListener('click',function(event){
        var button=event.target.closest('[data-htp-duplicate-tariff-row]');
        if(!button)return;
        window.setTimeout(function(){
            var source=button.closest('[data-htp-tariff-row]'),clone=source&&source.nextElementSibling;
            if(!clone||!clone.matches('[data-htp-tariff-row]'))return;
            var input=clone.querySelector('[data-htp-tariff-row-id-input]');if(input)input.value='';
            var badge=clone.querySelector('[data-htp-tariff-id-badge]');if(badge)badge.textContent='ID attribué à l’enregistrement';
        },0);
    });

    function ensureBindingPanel(){
        var $group=$('#htp-tariffs [data-htp-tariff-group="groups"]');
        if(!$group.length)return;
        var $panel=$('#htp-tariffs [data-htp-quote-binding]');
        if($panel.length)return;
        var b=cfg.binding||{},columns=cfg.tariff_columns||[],rows=cfg.tariff_rows||[];
        $panel=$('<div class="htp-subsection" data-htp-quote-binding data-htp-group-context hidden><h3>Liaison avec le devis en ligne</h3><p class="description">Le devis utilise uniquement les identifiants permanents ci-dessous. Renommer ou déplacer un tarif ne casse plus le calcul. Si un tarif lié est supprimé, le devis est bloqué jusqu’à ce que vous choisissiez un nouveau tarif.</p><details class="htp-advanced" open><summary>Correspondance des tarifs pour '+escaped(cfg.year||'')+'</summary><div class="htp-advanced-content"><div class="htp-grid htp-grid-3"><label class="htp-field"><span>Colonne de prix utilisée</span><select data-bind="column_id"></select></label><label class="htp-field"><span>Enfant / scolaire</span><select data-bind="child_row_id"></select></label><label class="htp-field"><span>Adulte</span><select data-bind="adult_row_id"></select></label><label class="htp-field"><span>Personne en situation de handicap</span><select data-bind="disability_row_id"></select></label><label class="htp-field"><span>Accompagnateur</span><select data-bind="companion_row_id"></select></label><label class="htp-field"><span>1 adulte gratuit pour X enfants</span><input type="number" min="1" step="1" data-bind="free_adult_children"></label><label class="htp-field"><span>Arrondi supérieur à partir de X enfants restants</span><input type="number" min="1" step="1" data-bind="free_adult_round_threshold"></label></div><p class="description">Avec 10 et 5, 26 enfants donnent 3 adultes gratuits.</p><p><button type="button" class="button button-primary" data-bind-save>Enregistrer la liaison avec le devis</button> <span data-bind-status></span></p></div></details></div>');
        $panel.find('[data-bind="column_id"]').html(optionList(columns,'id','label',b.column_id||''));
        ['child_row_id','adult_row_id','disability_row_id','companion_row_id'].forEach(function(key){$panel.find('[data-bind="'+key+'"]').html(optionList(rows,'id','label',b[key]||''));});
        $panel.find('[data-bind="free_adult_children"]').val(b.free_adult_children||'10');
        $panel.find('[data-bind="free_adult_round_threshold"]').val(b.free_adult_round_threshold||'5');
        $panel.insertAfter($group);
    }

    function groupTranslationFields(prefix,label,multiline){
        var html='<div class="htp-field" data-gt-trans="'+prefix+'"><span>'+escaped(label)+'</span><div class="htp-mini-lang">';
        ['fr','en','de'].forEach(function(lang,index){html+='<button type="button" class="button button-small '+(index===0?'button-primary':'')+'" data-gt-lang="'+lang+'">'+lang.toUpperCase()+'</button>';});
        html+='</div>';
        ['fr','en','de'].forEach(function(lang,index){html+=multiline?'<textarea rows="3" data-gt-field="'+prefix+'_'+lang+'" '+(index?'hidden':'')+'></textarea>':'<input type="text" data-gt-field="'+prefix+'_'+lang+'" '+(index?'hidden':'')+'>';});
        return html+'</div>';
    }

    function ensureGroupSettingsPanel(){
        var $group=$('#htp-tariffs [data-htp-tariff-group="groups"]');
        if(!$group.length||$('#htp-tariffs [data-htp-group-tariff-settings]').length)return;
        var g=cfg.group_tariff||{};
        var html='<div class="htp-subsection" data-htp-group-tariff-settings data-htp-group-context hidden><h3>Publication et affichage des tarifs groupes</h3><p class="description">Ces réglages sont propres à la saison '+escaped(cfg.year||'')+'. Les horaires peuvent être publiés sans publier les tarifs groupes. Un devis ne sera jamais calculé avec les tarifs d’une autre année.</p><div class="htp-check-list"><label><input type="checkbox" data-gt="published"> Publier les tarifs groupes de cette année</label><label><input type="checkbox" data-gt="show_heading"> Afficher le titre du bloc</label><label><input type="checkbox" data-gt="show_future_notice"> Afficher un message pour une année future non disponible</label><label><input type="checkbox" data-gt="show_quote_button"> Afficher le bouton de devis</label></div><div class="htp-grid htp-grid-2">'+groupTranslationFields('title','Titre du bloc (vide = titre automatique)',false)+groupTranslationFields('intro','Texte d’introduction facultatif',true)+'<label class="htp-field"><span>Année future annoncée</span><input type="number" min="2020" max="2100" data-gt="future_year"></label>'+groupTranslationFields('future_notice','Message année future (vide = texte automatique)',true)+groupTranslationFields('button_label','Texte du bouton devis',false)+groupTranslationFields('button_url','Lien du bouton devis',false)+'</div><p><button type="button" class="button button-primary" data-gt-save>Enregistrer l’affichage groupes</button> <span data-gt-status></span></p></div>';
        var $panel=$(html);
        ['published','show_heading','show_future_notice','show_quote_button'].forEach(function(k){$panel.find('[data-gt="'+k+'"]').prop('checked',String(g[k])==='1');});
        $panel.find('[data-gt="future_year"]').val(g.future_year||((parseInt(cfg.year,10)||new Date().getFullYear())+1));
        ['title','intro','future_notice','button_label','button_url'].forEach(function(field){['fr','en','de'].forEach(function(lang){$panel.find('[data-gt-field="'+field+'_'+lang+'"]').val((g[field]&&g[field][lang])||'');});});
        $panel.on('click','[data-gt-trans] [data-gt-lang]',function(){var $wrap=$(this).closest('[data-gt-trans]'),lang=$(this).data('gt-lang');$wrap.find('[data-gt-lang]').removeClass('button-primary');$(this).addClass('button-primary');$wrap.find('[data-gt-field]').attr('hidden',true);$wrap.find('[data-gt-field$="_'+lang+'"]').removeAttr('hidden');});
        $panel.insertAfter($group);
    }

    function showGroupTariffs(){
        ensureGroupSettingsPanel();ensureBindingPanel();decorateTariffIdentities();
        activate('htp-tariffs-groups','groups','htp-tariffs');
        var $tariffs=$('#htp-tariffs');
        $tariffs.find('[data-htp-tariff-group]').attr('hidden',true);
        $tariffs.find('[data-htp-tariff-group="groups"]').removeAttr('hidden');
        $tariffs.find('[data-htp-group-context],[data-htp-group-info]').removeAttr('hidden');
        var $heading=$tariffs.find('> h2').first();
        if(!$tariffs.find('[data-htp-group-context-banner]').length){
            $('<div class="notice notice-info inline" data-htp-group-context data-htp-group-context-banner><p><strong>Groupes → Tarifs :</strong> cette grille est la source unique du shortcode général, du shortcode tarifs groupes et du devis. Les identifiants affichés sont permanents et non modifiables.</p></div>').insertAfter($heading);
        }else $tariffs.find('[data-htp-group-context-banner]').removeAttr('hidden');
    }

    $groupsButton.on('click',showGroupTariffs);
    $groupsTariffs.on('click',showGroupTariffs);
    $groupsQuote.on('click',function(){activate('htp-quote','groups');});
    $groupsGuides.on('click',function(){activate('htp-guides','groups');});
    $nav.on('click','[data-htp-admin-tab="htp-tariffs"]',resetTariffView);

    var $quote=$('#htp-quote');
    if($quote.length){
        var $existing=$quote.children().wrapAll('<div class="htp-quote-admin-pane" data-htp-quote-pane="content"></div>').parent();
        var $subnav=$('<div class="htp-admin-subtabs htp-quote-subtabs"></div>');
        var $contentBtn=$('<button type="button" class="button button-primary">Contenu</button>');
        var $gateBtn=$('<button type="button" class="button">Accès au devis</button>');
        $subnav.append($contentBtn,$gateBtn); $quote.prepend($subnav);

        var forms=cfg.forms||{},activeLang='fr';
        var $oldFormInput=$existing.find('input[name="settings[quote_page][form_shortcode]"]');
        var $oldFormField=$oldFormInput.closest('.htp-field'),$oldDiagnostic=$existing.find('.htp-cf7-status');
        $oldFormField.hide();$oldDiagnostic.hide();
        var $multiForm=$('<div class="htp-field htp-quote-language-form"><span>Shortcode du formulaire Contact Form 7</span><div class="htp-lang-tabs" data-q-lang-tabs><button type="button" class="button button-primary" data-lang="fr">FR</button><button type="button" class="button" data-lang="en">EN</button><button type="button" class="button" data-lang="de">DE</button></div><input type="text" class="large-text code" data-q-form-current placeholder="[contact-form-7 id=&quot;...&quot;]"><p class="description">Chaque langue utilise son propre formulaire Contact Form 7. Un champ vide utilise le formulaire général en secours.</p><p><button type="button" class="button" data-q-forms-save>Enregistrer les formulaires FR / EN / DE</button> <span data-q-forms-status></span></p></div>');
        if($oldFormField.length)$multiForm.insertBefore($oldFormField);else $existing.prepend($multiForm);
        function showLang(lang){activeLang=lang;$multiForm.find('[data-q-form-current]').val(forms[lang]||'');$multiForm.find('[data-lang]').removeClass('button-primary');$multiForm.find('[data-lang="'+lang+'"]').addClass('button-primary');}
        function storeCurrent(){forms[activeLang]=$multiForm.find('[data-q-form-current]').val();}
        $multiForm.on('input','[data-q-form-current]',storeCurrent);
        $multiForm.on('click','[data-lang]',function(){storeCurrent();showLang($(this).data('lang'));});showLang('fr');

        var g=cfg.gate||{};
        var $gate=$('<div class="htp-quote-admin-pane" data-htp-quote-pane="gate" hidden><h2>Accès au devis automatique</h2><p class="description">Le devis exige désormais une saison publiée, des tarifs groupes publiés et des liaisons par identifiants valides pour l’année choisie.</p><div class="htp-check-list"><label><input type="checkbox" data-g="enabled"> Activer le choix de la date avant l’affichage du formulaire complet</label></div><div class="htp-subsection"><h3>Date où le parc est fermé</h3><label><input type="checkbox" data-g="closed_enabled"> Afficher un avertissement</label><div class="htp-lang-tabs" data-g-lang-tabs="closed"><button type="button" class="button button-primary" data-lang="fr">FR</button><button type="button" class="button" data-lang="en">EN</button><button type="button" class="button" data-lang="de">DE</button></div><p><textarea class="large-text" rows="5" data-g-message="closed"></textarea></p><label class="htp-field"><span>Contact ou lien</span><input type="text" data-g="closed_contact"></label></div><div class="htp-subsection"><h3>Tarifs indisponibles</h3><label><input type="checkbox" data-g="unavailable_enabled"> Afficher le message d’indisponibilité</label><div class="htp-lang-tabs" data-g-lang-tabs="unavailable"><button type="button" class="button button-primary" data-lang="fr">FR</button><button type="button" class="button" data-lang="en">EN</button><button type="button" class="button" data-lang="de">DE</button></div><p><textarea class="large-text" rows="5" data-g-message="unavailable"></textarea></p><label class="htp-field"><span>Contact ou lien</span><input type="text" data-g="unavailable_contact"></label></div><p><button type="button" class="button button-primary" data-g-save>Enregistrer l’accès au devis</button> <span data-g-status></span></p></div>');
        ['enabled','closed_enabled','unavailable_enabled'].forEach(function(k){$gate.find('[data-g="'+k+'"]').prop('checked',String(g[k])==='1');});
        ['closed_contact','unavailable_contact'].forEach(function(k){$gate.find('[data-g="'+k+'"]').val(g[k]||'');});
        var gateMessages={closed:{fr:g.closed_message_fr||g.closed_message||'',en:g.closed_message_en||'',de:g.closed_message_de||''},unavailable:{fr:g.unavailable_message_fr||g.unavailable_message||'',en:g.unavailable_message_en||'',de:g.unavailable_message_de||''}},gateLang={closed:'fr',unavailable:'fr'};
        function showGateLang(type,lang){gateLang[type]=lang;$gate.find('[data-g-message="'+type+'"]').val(gateMessages[type][lang]||'');var $tabs=$gate.find('[data-g-lang-tabs="'+type+'"]');$tabs.find('[data-lang]').removeClass('button-primary');$tabs.find('[data-lang="'+lang+'"]').addClass('button-primary');}
        function storeGateMessage(type){gateMessages[type][gateLang[type]]=$gate.find('[data-g-message="'+type+'"]').val();}
        $gate.on('input','[data-g-message]',function(){storeGateMessage($(this).data('g-message'));});
        $gate.on('click','[data-g-lang-tabs] [data-lang]',function(){var type=$(this).closest('[data-g-lang-tabs]').data('g-lang-tabs');storeGateMessage(type);showGateLang(type,$(this).data('lang'));});showGateLang('closed','fr');showGateLang('unavailable','fr');$quote.append($gate);
        function quotePane($pane,$btn){$existing.add($gate).attr('hidden',true);$contentBtn.add($gateBtn).removeClass('button-primary');$pane.removeAttr('hidden');$btn.addClass('button-primary');}
        $contentBtn.on('click',function(){quotePane($existing,$contentBtn);});$gateBtn.on('click',function(){quotePane($gate,$gateBtn);});
        $multiForm.on('click','[data-q-forms-save]',function(){storeCurrent();var data={action:'parcs_ht_save_quote_language_forms',nonce:cfg.forms_nonce,fr:forms.fr||'',en:forms.en||'',de:forms.de||''};var $status=$multiForm.find('[data-q-forms-status]').text('Enregistrement…');$.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});});
        $gate.on('click','[data-g-save]',function(){storeGateMessage('closed');storeGateMessage('unavailable');var data={action:'parcs_ht_save_quote_gate_settings',nonce:cfg.gate_nonce};['enabled','closed_enabled','unavailable_enabled'].forEach(function(k){data[k]=$gate.find('[data-g="'+k+'"]').is(':checked')?'1':'0';});['closed_contact','unavailable_contact'].forEach(function(k){data[k]=$gate.find('[data-g="'+k+'"]').val();});['fr','en','de'].forEach(function(lang){data['closed_message_'+lang]=gateMessages.closed[lang]||'';data['unavailable_message_'+lang]=gateMessages.unavailable[lang]||'';});var $status=$gate.find('[data-g-status]').text('Enregistrement…');$.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});});
    }

    $('#htp-tariffs').on('click','[data-bind-save]',function(){var $panel=$(this).closest('[data-htp-quote-binding]');var data={action:'parcs_ht_save_quote_tariff_binding',nonce:cfg.binding_nonce,year:cfg.year};['column_id','child_row_id','adult_row_id','disability_row_id','companion_row_id','free_adult_children','free_adult_round_threshold'].forEach(function(k){data[k]=$panel.find('[data-bind="'+k+'"]').val();});var $status=$panel.find('[data-bind-status]').text('Enregistrement…');$.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});});

    $('#htp-tariffs').on('click','[data-gt-save]',function(){var $panel=$(this).closest('[data-htp-group-tariff-settings]');var data={action:'parcs_ht_save_group_tariff_settings',nonce:cfg.group_tariff_nonce,year:cfg.year};['published','show_heading','show_future_notice','show_quote_button'].forEach(function(k){data[k]=$panel.find('[data-gt="'+k+'"]').is(':checked')?'1':'0';});data.future_year=$panel.find('[data-gt="future_year"]').val();['title','intro','future_notice','button_label','button_url'].forEach(function(field){['fr','en','de'].forEach(function(lang){data[field+'_'+lang]=$panel.find('[data-gt-field="'+field+'_'+lang+'"]').val();});});var $status=$panel.find('[data-gt-status]').text('Enregistrement…');$.post(ajaxurl,data).done(function(res){$status.text(res&&res.success?res.data.message:(res.data&&res.data.message)||'Erreur.');}).fail(function(xhr){var m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message;$status.text(m||'Erreur lors de l’enregistrement.');});});

    var requested='';try{requested=new URL(window.location.href).searchParams.get('tab')||'';}catch(error){}
    if(requested==='htp-guides'&&$('#htp-guides').length)activate('htp-guides','groups');
    else if(requested==='htp-tariffs-groups')showGroupTariffs();
    else if(requested==='htp-quote')activate('htp-quote','groups');
    else if(requested==='htp-tariffs')resetTariffView();
    else {var current=$('[data-htp-active-tab-input]').val();if(current==='htp-quote')activate('htp-quote','groups');else if(current==='htp-tariffs')resetTariffView();}
});
})(jQuery);
