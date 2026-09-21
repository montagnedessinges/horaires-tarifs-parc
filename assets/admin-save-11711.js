(function(){
  'use strict';

  var completeField='parcs_ht_11711_complete';
  var workspaceField='parcs_ht_11711_workspace';

  var workspaceSelectors=[
    ['form[data-htp-1174-form]','periods'],
    ['form[data-htp-retail-tariffs-form]','retail'],
    ['form[data-htp-group-tariffs-form]','groups'],
    ['form[data-htp-popup-1179]','popup']
  ];

  function canonicalSaveForm(form){
    if(!form||!form.querySelector)return false;
    var action=form.querySelector('input[name="action"]');
    return !!action&&String(action.value)==='parcs_ht_save';
  }

  function workspaceFor(form){
    for(var i=0;i<workspaceSelectors.length;i++){
      if(form.matches(workspaceSelectors[i][0]))return workspaceSelectors[i][1];
    }
    return '';
  }

  function removeNamed(form,name){
    form.querySelectorAll('input[name="'+name+'"]').forEach(function(input){
      input.remove();
    });
  }

  function appendHidden(form,name,value){
    var input=document.createElement('input');
    input.type='hidden';
    input.name=name;
    input.value=value;
    form.appendChild(input);
  }

  function prepare(form){
    if(!canonicalSaveForm(form))return;

    removeNamed(form,workspaceField);
    var workspace=workspaceFor(form);
    if(workspace)appendHidden(form,workspaceField,workspace);

    // Toujours réinsérer le marqueur en dernier. Si PHP coupe la requête à cause
    // de max_input_vars, ce champ n'arrive pas et le serveur refuse l'écriture.
    removeNamed(form,completeField);
    appendHidden(form,completeField,'1');
  }

  document.addEventListener('submit',function(event){
    prepare(event.target);
  },true);
}());
