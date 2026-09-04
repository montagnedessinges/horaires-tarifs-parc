(function(){
  'use strict';

  var STORAGE_BG='parcs_ht_preview_background';
  var STORAGE_DEVICE='parcs_ht_preview_device';
  var raf=0;

  function q(root,name){return root.querySelector('[name="'+name.replace(/"/g,'\\"')+'"]');}
  function bySuffix(root,suffix){return root.querySelector('[name$="['+suffix+']"]');}
  function value(root,suffix,fallback){var el=bySuffix(root,suffix);return el&&el.value!==''?el.value:fallback;}
  function checked(root,suffix){var el=bySuffix(root,suffix);return !!(el&&el.checked);}
  function px(root,suffix,fallback){var v=parseFloat(value(root,suffix,''));return isFinite(v)&&v>0?v+'px':fallback;}
  function color(root,suffix,fallback){var v=value(root,suffix,'');return /^#[0-9a-f]{6}$/i.test(v)?v:fallback;}
  function text(root,suffix,fallback){var v=value(root,suffix,'');return v||fallback;}
  function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}

  function environment(){
    var bg='#ffffff',device='desktop';
    try{bg=sessionStorage.getItem(STORAGE_BG)||bg;device=sessionStorage.getItem(STORAGE_DEVICE)||device;}catch(e){}
    return{bg:bg,device:device==='mobile'?'mobile':'desktop'};
  }
  function saveEnvironment(bg,device){try{sessionStorage.setItem(STORAGE_BG,bg);sessionStorage.setItem(STORAGE_DEVICE,device);}catch(e){}}

  function toolbar(){
    var env=environment();
    var bar=document.createElement('div');
    bar.className='htp-live-preview-toolbar';
    bar.innerHTML='<div class="htp-live-preview-toolbar-group"><strong>Environnement de l’aperçu</strong><span>Simulation uniquement, sans modifier le site.</span></div>'+
      '<div class="htp-live-preview-toolbar-group"><label>Fond <input type="color" value="'+esc(/^#[0-9a-f]{6}$/i.test(env.bg)?env.bg:'#ffffff')+'" data-htp-preview-bg></label><button type="button" class="button" data-preview-bg-preset="#ffffff">Clair</button><button type="button" class="button" data-preview-bg-preset="#222222">Sombre</button></div>'+
      '<div class="htp-live-preview-toolbar-group htp-live-preview-devices"><button type="button" class="button '+(env.device==='desktop'?'button-primary':'')+'" data-preview-device="desktop">Desktop</button><button type="button" class="button '+(env.device==='mobile'?'button-primary':'')+'" data-preview-device="mobile">Mobile</button></div>';
    return bar;
  }

  function bindEnvironment(panel){
    var bar=panel.querySelector('.htp-live-preview-toolbar'),canvas=panel.querySelector('.htp-live-preview-canvas');
    if(!bar||!canvas)return;
    function apply(){var env=environment();canvas.style.setProperty('--htp-preview-page-bg',env.bg);canvas.dataset.previewDevice=env.device;bar.querySelectorAll('[data-preview-device]').forEach(function(b){b.classList.toggle('button-primary',b.dataset.previewDevice===env.device);});}
    var bg=bar.querySelector('[data-htp-preview-bg]');if(bg)bg.addEventListener('input',function(){var env=environment();saveEnvironment(bg.value,env.device);applyAllEnvironment();});
    bar.querySelectorAll('[data-preview-bg-preset]').forEach(function(b){b.addEventListener('click',function(){var env=environment();var c=b.dataset.previewBgPreset;saveEnvironment(c,env.device);if(bg)bg.value=c;applyAllEnvironment();});});
    bar.querySelectorAll('[data-preview-device]').forEach(function(b){b.addEventListener('click',function(){var env=environment();saveEnvironment(env.bg,b.dataset.previewDevice);applyAllEnvironment();});});
    apply();
  }

  function applyAllEnvironment(){document.querySelectorAll('[data-htp-live-preview]').forEach(function(panel){var env=environment(),canvas=panel.querySelector('.htp-live-preview-canvas');if(canvas){canvas.style.setProperty('--htp-preview-page-bg',env.bg);canvas.dataset.previewDevice=env.device;}var bg=panel.querySelector('[data-htp-preview-bg]');if(bg&&/^#[0-9a-f]{6}$/i.test(env.bg))bg.value=env.bg;panel.querySelectorAll('[data-preview-device]').forEach(function(b){b.classList.toggle('button-primary',b.dataset.previewDevice===env.device);});});}

  function shell(section,title,bodyClass){
    var panel=document.createElement('div');panel.className='htp-live-preview';panel.dataset.htpLivePreview='1';
    panel.innerHTML='<div class="htp-live-preview-head"><div><h3>'+esc(title)+'</h3><p>Aperçu mis à jour immédiatement pendant vos réglages.</p></div></div><div data-toolbar-host></div><div class="htp-live-preview-canvas"><div class="htp-live-preview-stage '+bodyClass+'" data-preview-stage></div></div>';
    panel.querySelector('[data-toolbar-host]').appendChild(toolbar());section.appendChild(panel);bindEnvironment(panel);return panel;
  }

  function renderGeneral(section,stage){
    var primary=color(section,'primary_color','#006757'),secondary=color(section,'secondary_color','#31ad81'),accent=color(section,'accent_color','#ef7b5b'),highlight=color(section,'highlight_color','#e7c55b');
    var body=color(section,'body_text_color','#222222'),heading=color(section,'heading_text_color',body),border=color(section,'border_color','rgba(0,0,0,.16)');
    var spacing=Math.max(0,parseInt(value(section,'block_spacing','8'),10)||0);var borderOn=checked(section,'block_border_enabled');
    var bodySize=px(section,'font_body_size','16px'),headingSize=px(section,'font_heading_size','26px'),buttonSize=px(section,'font_button_size','15px');
    stage.style.setProperty('--p',primary);stage.style.setProperty('--s',secondary);stage.style.setProperty('--a',accent);stage.style.setProperty('--h',highlight);stage.style.setProperty('--body',body);stage.style.setProperty('--heading',heading);stage.style.setProperty('--border',border);stage.style.setProperty('--gap',spacing+'px');stage.style.setProperty('--body-size',bodySize);stage.style.setProperty('--heading-size',headingSize);stage.style.setProperty('--button-size',buttonSize);stage.dataset.border=borderOn?'1':'0';
    stage.innerHTML='<div class="htp-preview-demo-block"><span class="htp-preview-kicker">APERÇU GÉNÉRAL</span><h4>Horaires et tarifs</h4><p>Texte courant hérité de vos réglages. Les changements de couleurs, tailles et espacements apparaissent directement ici.</p><div class="htp-preview-demo-actions"><span class="is-primary">Acheter les billets</span><span class="is-secondary">Voir les tarifs</span></div></div><div class="htp-preview-demo-row"><span class="htp-preview-demo-badge is-accent">Exception</span><span class="htp-preview-demo-badge is-highlight">Mise en valeur</span></div>';
  }

  function renderRegular(section,stage){
    var title=color(section,'today_title_color','#222222'),titleBg=checked(section,'today_title_bg_transparent')?'transparent':color(section,'today_title_bg_color','#ffffff');
    var open=color(section,'today_status_color','#16843d'),detail=color(section,'today_detail_color','#333333');
    var calTitle=color(section,'calendar_title_color','#222222'),calTitleBg=checked(section,'calendar_title_bg_transparent')?'transparent':color(section,'calendar_title_bg_color','#ffffff');
    var dayBg=checked(section,'calendar_day_bg_transparent')?'transparent':color(section,'calendar_day_bg_color','#ffffff'),nav=color(section,'calendar_nav_bg_color','#006757'),navText=color(section,'calendar_nav_text_color','#ffffff'),active=color(section,'calendar_nav_active_bg_color','#e7c55b'),activeText=color(section,'calendar_nav_active_text_color','#27342f'),closed=color(section,'calendar_closed_bg_color','#e3e5e4'),closedText=color(section,'calendar_closed_text_color','#616765'),selected=color(section,'calendar_selected_color','#006757');
    var t1=px(section,'font_today_title_size','13px'),t2=px(section,'font_today_status_size','28px'),t3=px(section,'font_today_detail_size','16px'),cTitle=px(section,'font_calendar_title_size','24px'),cDay=px(section,'font_calendar_day_size','15px');
    stage.innerHTML='<div class="htp-preview-today" style="--title:'+title+';--title-bg:'+titleBg+';--open:'+open+';--detail:'+detail+';--t1:'+t1+';--t2:'+t2+';--t3:'+t3+'"><span>AUJOURD’HUI</span><strong>OUVERT</strong><p>9h30 – 18h00 · dernière entrée 17h15</p></div><div class="htp-preview-calendar" style="--ct:'+calTitle+';--ctbg:'+calTitleBg+';--daybg:'+dayBg+';--nav:'+nav+';--navtext:'+navText+';--active:'+active+';--activetext:'+activeText+';--closed:'+closed+';--closedtext:'+closedText+';--selected:'+selected+';--ctsize:'+cTitle+';--daysize:'+cDay+'"><h4>Calendrier</h4><div class="months"><span>Avril</span><span class="active">Mai</span><span>Juin</span></div><div class="days"><span>12</span><span>13</span><span class="selected">14</span><span class="closed">15</span><span>16</span><span>17</span><span>18</span></div></div>';
  }

  function renderHolidays(section,stage){
    var holiday=color(section,'holiday_border_color','#e7c55b'),width=Math.max(1,parseInt(value(section,'holiday_border_width','3'),10)||3),period='#7b61a8';var periodInput=section.querySelector('input[type="color"][name*="special_periods"]');if(periodInput)period=periodInput.value;
    stage.innerHTML='<div class="htp-preview-period-demo"><div class="htp-preview-mini-calendar"><span>10</span><span class="holiday" style="--holiday:'+holiday+';--holiday-width:'+width+'px">11</span><span>12</span><span class="period" style="--period:'+period+'">13</span><span>14</span></div><div class="htp-preview-legend"><span><i style="--period:'+period+'"></i>Période repère</span><span><i class="holiday-dot" style="--holiday:'+holiday+'"></i>Jour férié</span></div></div>';
  }

  function renderAlerts(section,stage){
    var bg=color(section,'alert_bg_color','#006757'),title=color(section,'alert_title_color','#ffffff'),txt=color(section,'alert_text_color','#ffffff'),border=color(section,'alert_border_color','#ef7b5b'),bw=Math.max(0,parseInt(value(section,'alert_border_width','3'),10)||0),radius=Math.max(0,parseInt(value(section,'alert_radius','16'),10)||0),btn=color(section,'alert_button_bg_color','#ef7b5b'),btnTxt=color(section,'alert_button_text_color','#ffffff'),btnBorder=color(section,'alert_button_border_color','#ef7b5b'),closeBg=color(section,'alert_close_bg_color','#ffffff'),closeTxt=color(section,'alert_close_text_color','#222222'),overlay=color(section,'alert_overlay_color','#000000'),opacity=Math.max(0,Math.min(100,parseInt(value(section,'alert_overlay_opacity','68'),10)||0))/100;
    var fsTitle=px(section,'font_alert_title_size','26px'),fsText=px(section,'font_alert_text_size','16px'),fsBtn=px(section,'font_alert_button_size','15px');
    stage.innerHTML='<div class="htp-preview-overlay" style="--overlay:'+overlay+';--opacity:'+opacity+'"><div class="htp-preview-popup" style="--bg:'+bg+';--title:'+title+';--text:'+txt+';--border:'+border+';--bw:'+bw+'px;--radius:'+radius+'px;--btn:'+btn+';--btntxt:'+btnTxt+';--btnborder:'+btnBorder+';--closebg:'+closeBg+';--closetxt:'+closeTxt+';--fstitle:'+fsTitle+';--fstext:'+fsText+';--fsbtn:'+fsBtn+'"><button type="button" tabindex="-1">×</button><h4>Information importante</h4><p>Voici un exemple du message affiché aux visiteurs.</p><span class="action">En savoir plus</span></div></div>';
  }

  function renderTariffs(section,stage){
    var title=color(section,'tariff_title_color','#222222'),titleBg=checked(section,'tariff_title_bg_transparent')?'transparent':color(section,'tariff_title_bg_color','#ffffff'),tab=color(section,'tab_bg_color','#006757'),tabTxt=color(section,'tab_text_color','#ffffff'),active=color(section,'tab_active_bg_color','#e7c55b'),activeTxt=color(section,'tab_active_text_color','#27342f'),panel=checked(section,'panel_bg_transparent')?'transparent':color(section,'panel_bg_color','#ffffff'),panelTxt=color(section,'panel_text_color','#222222'),panelBorder=color(section,'panel_border_color','rgba(0,0,0,.15)'),price=color(section,'price_color',tab),buy=color(section,'primary_button_bg_color','#ef7b5b'),buyTxt=color(section,'primary_button_text_color','#ffffff'),quote=color(section,'button_bg_color','#006757'),quoteTxt=color(section,'button_text_color','#ffffff'),pay=color(section,'payment_item_bg_color','#006757'),payTxt=color(section,'payment_item_text_color','#ffffff');
    var titleSize=px(section,'font_tariff_title_size','26px'),tabSize=px(section,'font_tariff_tab_size','14px'),labelSize=px(section,'font_tariff_label_size','16px'),priceSize=px(section,'font_tariff_price_size','25px');
    stage.innerHTML='<div class="htp-preview-tariffs" style="--title:'+title+';--titlebg:'+titleBg+';--tab:'+tab+';--tabtxt:'+tabTxt+';--active:'+active+';--activetxt:'+activeTxt+';--panel:'+panel+';--paneltxt:'+panelTxt+';--panelborder:'+panelBorder+';--price:'+price+';--buy:'+buy+';--buytxt:'+buyTxt+';--quote:'+quote+';--quotetxt:'+quoteTxt+';--pay:'+pay+';--paytxt:'+payTxt+';--titlesize:'+titleSize+';--tabsize:'+tabSize+';--labelsize:'+labelSize+';--pricesize:'+priceSize+'"><h4>Tarifs</h4><div class="tabs"><span class="active">Individuels</span><span>Groupes</span></div><div class="panel"><div class="line"><span>Adulte</span><strong>12,00 €</strong></div><div class="line"><span>Enfant</span><strong>8,00 €</strong></div><div class="actions"><span class="buy">Acheter</span><span class="quote">Demander un devis</span></div></div><div class="payments"><span>CB</span><span>ANCV</span></div></div>';
  }

  var configs={
    'htp-general':{title:'Aperçu en direct — apparence générale',render:renderGeneral},
    'htp-regular':{title:'Aperçu en direct — horaires & calendrier',render:renderRegular},
    'htp-holidays':{title:'Aperçu en direct — périodes & événements',render:renderHolidays},
    'htp-alerts':{title:'Aperçu en direct — pop-up',render:renderAlerts},
    'htp-tariffs':{title:'Aperçu en direct — tarifs',render:renderTariffs}
  };

  function scheduleRender(section,panel,renderer){cancelAnimationFrame(raf);raf=requestAnimationFrame(function(){renderer(section,panel.querySelector('[data-preview-stage]'));});}
  function enhanceSection(id,cfg){var section=document.getElementById(id);if(!section||section.dataset.htpVisualPreview==='1')return;section.dataset.htpVisualPreview='1';var panel=shell(section,cfg.title,'htp-preview-'+id);var render=function(){scheduleRender(section,panel,cfg.render);};section.addEventListener('input',render);section.addEventListener('change',render);render();}

  function enhanceGuides(){var guide=document.querySelector('[data-guide-appearance-panel]');if(!guide||guide.dataset.htpVisualEnvironment==='1')return;guide.dataset.htpVisualEnvironment='1';var target=guide.querySelector('.htp-guide-full-preview')||guide.querySelector('[data-guide-preview]');if(!target)return;var panel=document.createElement('div');panel.className='htp-live-preview htp-live-preview-guide-shell';panel.dataset.htpLivePreview='1';panel.innerHTML='<div class="htp-live-preview-head"><div><h3>Environnement de prévisualisation</h3><p>Testez le shortcode sur un fond clair, sombre ou personnalisé, en Desktop ou Mobile.</p></div></div><div data-toolbar-host></div><div class="htp-live-preview-canvas"><div class="htp-guide-preview-relocation"></div></div>';panel.querySelector('[data-toolbar-host]').appendChild(toolbar());target.parentNode.insertBefore(panel,target);panel.querySelector('.htp-guide-preview-relocation').appendChild(target);bindEnvironment(panel);}

  function observeGuides(){enhanceGuides();var host=document.getElementById('htp-guides');if(!host)return;new MutationObserver(function(){enhanceGuides();}).observe(host,{childList:true,subtree:true});}

  function init(){Object.keys(configs).forEach(function(id){enhanceSection(id,configs[id]);});observeGuides();applyAllEnvironment();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());