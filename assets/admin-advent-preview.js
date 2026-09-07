(function(){
'use strict';
function sync(){var date=document.querySelector('[data-htp-preview-date]');var time=document.querySelector('[data-htp-preview-time]');if(!date||!window.ParcsHTAdvent)return;window.ParcsHTAdvent.initAll();window.ParcsHTAdvent.setPreview(String(date.value||''),time?String(time.value||'12:00'):'12:00');}
function init(){var section=document.getElementById('htp-preview');if(!section)return;var date=section.querySelector('[data-htp-preview-date]');var time=section.querySelector('[data-htp-preview-time]');var button=section.querySelector('[data-htp-preview-button]');if(date)date.addEventListener('change',sync);if(time)time.addEventListener('change',sync);if(button)button.addEventListener('click',function(){window.setTimeout(sync,0);});sync();}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
