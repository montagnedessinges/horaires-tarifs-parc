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

  /**
   * L'ancien admin.js interprète un bouton nommé htp_save_active comme une
   * demande de désactivation de toutes les sections sauf l'onglet actif. Les
   * écrans métier 1.17.x ne possèdent plus ce marqueur d'onglet ; sur Périodes,
   * ce comportement désactivait donc tous les champs avant l'envoi tout en
   * laissant passer les marqueurs _complete.
   *
   * On conserve htp_save_active côté serveur via un champ caché, mais le bouton
   * n'est plus présenté à l'ancien JavaScript comme un bouton de sauvegarde
   * d'onglet lorsqu'aucun vrai contexte d'onglet n'existe.
   */
  function neutralizeLegacyScopedSave(form,event){
    if(form.querySelector('[data-htp-active-tab-input]'))return;
    var submitter=event&&event.submitter?event.submitter:document.activeElement;
    if(!submitter||String(submitter.name)!=='htp_save_active')return;
    submitter.name='submit';
    if(!form.querySelector('input[type="hidden"][name="htp_save_active"]')){
      appendHidden(form,'htp_save_active','1');
    }
  }

  function prepare(form,event){
    if(!canonicalSaveForm(form))return;

    neutralizeLegacyScopedSave(form,event);

    removeNamed(form,workspaceField);
    var workspace=workspaceFor(form);
    if(workspace)appendHidden(form,workspaceField,workspace);

    // Toujours réinsérer le marqueur en dernier. Si PHP coupe la requête à cause
    // de max_input_vars, ce champ n'arrive pas et le serveur refuse l'écriture.
    removeNamed(form,completeField);
    appendHidden(form,completeField,'1');
  }

  document.addEventListener('submit',function(event){
    prepare(event.target,event);
  },true);
}());
