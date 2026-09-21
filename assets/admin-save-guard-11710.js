(function(){
  'use strict';

  var config=window.ParcsHTSaveGuard11710||{};
  var field=String(config.field||'');
  var snapshotField=String(config.snapshotField||'');
  var actions=Array.isArray(config.actions)?config.actions.map(String):[];
  if(!field||!snapshotField||!actions.length)return;

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

  function nameParts(name){
    var match=String(name||'').match(/^([^\[]+)((?:\[[^\]]*\])*)$/);
    if(!match)return[];
    var out=[match[1]];
    var brackets=match[2]||'';
    var regex=/\[([^\]]*)\]/g;
    var part;
    while((part=regex.exec(brackets))!==null)out.push(part[1]);
    return out;
  }

  function assign(root,parts,value){
    if(!parts.length)return;
    var node=root;
    for(var i=0;i<parts.length;i++){
      var key=parts[i];
      var last=i===parts.length-1;
      if(key===''){
        if(!Array.isArray(node))return;
        if(last){node.push(value);return;}
        var appended=parts[i+1]===''?[]:{};
        node.push(appended);
        node=appended;
        continue;
      }
      if(last){node[key]=value;return;}
      var shouldArray=parts[i+1]==='';
      if(!node[key]||typeof node[key]!=='object'||(shouldArray&&!Array.isArray(node[key]))||(!shouldArray&&Array.isArray(node[key]))){
        node[key]=shouldArray?[]:{};
      }
      node=node[key];
    }
  }

  function payloadFrom(formData){
    var payload={};
    formData.forEach(function(value,name){
      if(name===field||name===snapshotField)return;
      if(typeof File!=='undefined'&&value instanceof File)return;
      assign(payload,nameParts(name),String(value));
    });
    return payload;
  }

  function scalar(formData,name){
    var value=formData.get(name);
    return typeof value==='string'?value:'';
  }

  function filesFrom(formData){
    var files=[];
    formData.forEach(function(value,name){
      if(typeof File!=='undefined'&&value instanceof File&&value.name){
        files.push([name,value]);
      }
    });
    return files;
  }

  function clearFormData(formData){
    var keys=[];
    formData.forEach(function(unused,name){
      if(keys.indexOf(name)===-1)keys.push(name);
    });
    keys.forEach(function(name){formData.delete(name);});
  }

  document.addEventListener('submit',function(event){
    var form=event.target;
    var action=protectedForm(form);
    if(action)appendDomMarker(form,action);
  },true);

  document.addEventListener('formdata',function(event){
    var form=event.target;
    var action=protectedForm(form);
    var formData=event.formData;
    if(!action||!formData)return;

    var payload=payloadFrom(formData);
    var files=filesFrom(formData);
    var nonce=scalar(formData,'_wpnonce');
    var referer=scalar(formData,'_wp_http_referer');

    clearFormData(formData);
    formData.append('action',action);
    if(nonce)formData.append('_wpnonce',nonce);
    if(referer)formData.append('_wp_http_referer',referer);
    formData.append(snapshotField,JSON.stringify(payload));
    files.forEach(function(item){formData.append(item[0],item[1]);});
    formData.append(field,action);
  });
}());
