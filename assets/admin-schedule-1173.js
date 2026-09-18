(function(){
'use strict';
var container=document.querySelector('[data-htp-periods]');
var add=document.querySelector('[data-htp-add-period]');
var template=document.getElementById('htp-1173-period-template');
if(!container||!add||!template)return;
function nextIndex(){var max=-1;container.querySelectorAll('[name^="regular_periods["]').forEach(function(el){var m=el.name.match(/^regular_periods\[(\d+)\]/);if(m)max=Math.max(max,parseInt(m[1],10));});return max+1;}
function wire(root){root.querySelectorAll('[data-htp-remove-period]').forEach(function(button){if(button.dataset.htpWired)return;button.dataset.htpWired='1';button.addEventListener('click',function(){var row=button.closest('[data-htp-period-row]');if(row&&window.confirm('Supprimer cette période d’ouverture ?'))row.remove();});});}
add.addEventListener('click',function(){var html=template.innerHTML.replace(/__INDEX__/g,String(nextIndex()));var box=document.createElement('div');box.innerHTML=html.trim();var row=box.firstElementChild;if(row){container.appendChild(row);wire(row);var first=row.querySelector('input:not([type="hidden"]),select');if(first)first.focus();}});
wire(container);
}());