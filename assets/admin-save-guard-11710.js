(function(){
  'use strict';

  var config=window.ParcsHTSaveGuard11710||{};
  var field=String(config.field||'');
  var actions=Array.isArray(config.actions)?config.actions.map(String):[];
  if(!field||!actions.length)return;

  function actionFor(form){
    if(!form||!form.elements)return'';
    var control=form.elements.namedItem('action');
    if(!control)return'';
    if(typeof control.value==='string')return control.value;
    return'';
  }

  function protectedForm(form){
    var action=actionFor(form);
    return action!==''&&actions.indexOf(action)!==-1?action:'';
  }

  function appendDomMarker(form,action){
    if(!form||!action)return;
    var old=form.querySelectorAll('input[data-htp-save-guard-11710]');
    Array.prototype.forEach.call(old,function(node){node.remove();});
    var input=document.createElement('input');
    input.type='hidden';
    input.name=field;
    input.value=action;
    input.setAttribute('data-htp-save-guard-11710','1');
    form.appendChild(input);
  }

  document.addEventListener('submit',function(event){
    var form=event.target;
    var action=protectedForm(form);
    if(action)appendDomMarker(form,action);
  },true);

  document.addEventListener('formdata',function(event){
    var form=event.target;
    var action=protectedForm(form);
    if(!action||!event.formData)return;
    event.formData.delete(field);
    event.formData.append(field,action);
  });
}());
