/* global wp, GEP_MEDIA */
(function($){
'use strict';
if(!window.wp || !wp.media || !wp.media.view || !wp.media.view.AttachmentsBrowser) return;
var folders=GEP_MEDIA.folders||[], browsers=[], current='all';
// The open folder is shared by every picker on the page: new uploads go there and the next picker opens there.
function exists(id){return folders.concat(GEP_MEDIA.folders||[]).some(function(f){return String(f.id)===String(id);});}
GEP_MEDIA.setCurrent=function(id){current=String(id||'all');};
GEP_MEDIA.getCurrent=function(){return /^\d+$/.test(current)&&exists(current)?current:'';};
function api(action,data){return $.ajax({url:GEP_MEDIA.ajax,type:'POST',data:$.extend({action:'gep_media_'+action,nonce:GEP_MEDIA.nonce},data||{})});}
function err(r){return r&&r.responseJSON&&r.responseJSON.data&&r.responseJSON.data.message || r&&r.data&&r.data.message || 'No se pudo completar la operación.';}
function sorted(){var group={},done={},out=[];folders.forEach(function(f){(group[f.parent]||(group[f.parent]=[])).push(f);});Object.keys(group).forEach(function(k){group[k].sort(function(a,b){return a.name.localeCompare(b.name,'es');});});function walk(pid,d){(group[pid]||[]).forEach(function(f){if(done[f.id])return;done[f.id]=true;out.push({f:f,depth:d});walk(f.id,d+1);});}walk(0,0);return out;}
function active(b){return String(b.collection.props.get('gep_folder')||'all');}
function selection(b){var st=b.controller&&b.controller.state&&b.controller.state();return st&&st.get('selection');}
function selectedIds(b){var s=selection(b);return s?s.pluck('id'):[];}
// "Seleccionar varios": each click adds or removes a file, even in pickers that normally take one image.
function isMulti(b){return !!b.gepMulti;}
function setMulti(b,on){var s=selection(b);if(!s)return;if(on&&!b.gepMulti){b.gepMulti={original:s.multiple,selection:s};s.multiple='toggle';}
 else if(!on&&b.gepMulti){var original=b.gepMulti.original;b.gepMulti.selection.multiple=original;b.gepMulti=null;
  // Back in a single-image picker, keep only the last file clicked so "Usar esta imagen" stays unambiguous.
  if(!original&&s.length>1){var keep=s.single()||s.last();s.reset(keep?[keep]:[]);}}
 render(b);}
function refresh(){return api('folders').done(function(r){if(!r.success)return;folders=r.data.folders||[];browsers=browsers.filter(function(b){return b.el&&document.documentElement.contains(b.el);});browsers.forEach(render);});}
function choose(b,id){current=String(id);b.collection.props.set('gep_folder',String(id));render(b);}
function action(b,name,data,success){api(name,data).done(function(r){if(!r.success){window.alert(err(r));return;}if(r.data&&r.data.skipped)window.alert(r.data.skipped+' archivo(s) no se pudieron mover.');refresh().done(function(){if(success)success(r);});}).fail(function(r){window.alert(err(r));});}
function folderOpts(excluded,includeRoot){var rows=sorted(), skip={};if(excluded){skip[excluded]=true;var changed=true;while(changed){changed=false;rows.forEach(function(x){if(skip[x.f.parent]&&!skip[x.f.id]){skip[x.f.id]=true;changed=true;}});}}var out=[];if(includeRoot)out.push({id:0,label:'Nivel principal'});else out.push({id:0,label:'Sin clasificar'});rows.forEach(function(x){if(!skip[x.f.id])out.push({id:x.f.id,label:Array(x.depth+1).join('  ') + x.f.name});});return out;}
function form(b,title,fields,submit){var $side=$(b.el).find('.gep-explorer');var $dialog=$side.find('.gep-explorer-dialog');$dialog.empty().prop('hidden',false);$('<strong>').text(title).appendTo($dialog);var inputs={};fields.forEach(function(field){var $label=$('<label class="gep-explorer-field">').appendTo($dialog);$('<span>').text(field.label).appendTo($label);if(field.type==='select'){var $select=$('<select>');field.options.forEach(function(o){$('<option>').val(o.id).text(o.label).appendTo($select);});$select.val(String(field.value||0));inputs[field.key]=$select.appendTo($label);}else{inputs[field.key]=$('<input type="text" maxlength="200">').val(field.value||'').appendTo($label);}});var $buttons=$('<div class="gep-explorer-dialog-buttons">').appendTo($dialog);$('<button type="button" class="button button-primary">').text('Guardar').appendTo($buttons).on('click',function(){var data={};Object.keys(inputs).forEach(function(k){data[k]=inputs[k].val();});submit(data,function(){$dialog.prop('hidden',true).empty();});});$('<button type="button" class="button">').text('Cancelar').appendTo($buttons).on('click',function(){$dialog.prop('hidden',true).empty();});$dialog.find('input').first().trigger('focus');}
function render(b){if(!b.el)return;var $side=$(b.el).children('.gep-explorer');if(!$side.length)return;var id=active(b),$list=$side.find('.gep-explorer-tree'),sig=JSON.stringify(folders);
// Rebuild the buttons only when the folders change; otherwise just move the highlight.
if($list.data('gep-sig')!==sig){$list.data('gep-sig',sig).empty();function item(value,name,depth,count){var $btn=$('<button type="button" class="gep-explorer-folder">').attr('data-id',value).css('padding-left',(12+depth*17)+'px');$('<span class="dashicons dashicons-category" aria-hidden="true">').appendTo($btn);$('<span class="gep-explorer-folder-name">').text(name).appendTo($btn);if(count!==undefined)$('<small>').text(count).appendTo($btn);$list.append($btn);}
item('all','Todos los archivos',0);item('unassigned','Sin clasificar',0);sorted().forEach(function(x){item(x.f.id,x.f.name,x.depth+1,x.f.count);});}
$list.children('.gep-explorer-folder').each(function(){$(this).toggleClass('is-active',$(this).attr('data-id')===id);});var isFolder=/^\d+$/.test(id), current=folders.find(function(f){return String(f.id)===id;});$side.find('.gep-explorer-rename,.gep-explorer-reparent,.gep-explorer-delete').prop('disabled',!isFolder);$side.find('.gep-explorer-current').text(current?current.name:id==='unassigned'?'Sin clasificar':'Todos los archivos');var n=selectedIds(b).length;$side.find('.gep-explorer-move').prop('disabled',!n).text(n>1?'Mover '+n+' archivos a…':'Mover archivos seleccionados…');$side.find('.gep-explorer-multi').toggleClass('is-on',isMulti(b)).attr('aria-pressed',String(isMulti(b))).text(isMulti(b)?'Listo ('+n+')':'Seleccionar varios');$(b.el).toggleClass('gep-multi-on',isMulti(b));}
function mount(b){if(!b.el || !$(b.el).closest('.media-modal').length)return;var $browser=$(b.el);if($browser.children('.gep-explorer').length)return;$browser.addClass('gep-explorer-active');var $side=$('<aside class="gep-explorer" aria-label="Organizador de carpetas"><div class="gep-explorer-top"><strong>Carpetas</strong><button type="button" class="button-link gep-explorer-new">+ Nueva</button></div><div class="gep-explorer-current" aria-live="polite">Todos los archivos</div><div class="gep-explorer-tree" role="group" aria-label="Carpetas"></div><div class="gep-explorer-bottom"><div class="gep-explorer-row"><button type="button" class="button gep-explorer-rename">Renombrar</button><button type="button" class="button gep-explorer-reparent">Mover carpeta</button><button type="button" class="button gep-explorer-delete">Eliminar</button><button type="button" class="button gep-explorer-multi" aria-pressed="false" title="Haz clic en varias fotos para marcarlas o desmarcarlas">Seleccionar varios</button></div><button type="button" class="button gep-explorer-move">Mover archivos seleccionados…</button><p>Lo que subas queda en la carpeta abierta. Los archivos conservan su URL original.</p></div><div class="gep-explorer-dialog" hidden></div></aside>');$browser.prepend($side);
var $toggle=$('<button type="button" class="button gep-explorer-toggle" aria-expanded="true" aria-label="Mostrar u ocultar carpetas"><span class="dashicons dashicons-category" aria-hidden="true"></span> Carpetas</button>');
$browser.append($toggle);
function layout(){
 var width=$browser.width();
 var narrow=width<850;
 $browser.toggleClass('gep-explorer-compact',narrow);
 if(narrow && !$browser.data('gep-open-initialized')){
   $browser.addClass('gep-explorer-collapsed');
 }
 $browser.data('gep-open-initialized',true);
 $toggle.attr('aria-expanded',String(!$browser.hasClass('gep-explorer-collapsed')));
 $side.attr('aria-hidden',String($browser.hasClass('gep-explorer-collapsed')));
}
$toggle.on('click',function(){
 $browser.toggleClass('gep-explorer-collapsed');
 $toggle.attr('aria-expanded',String(!$browser.hasClass('gep-explorer-collapsed')));
 $side.attr('aria-hidden',String($browser.hasClass('gep-explorer-collapsed')));
 $(window).trigger('resize');
});
layout();
if(window.ResizeObserver){var ro=new ResizeObserver(function(){layout();});ro.observe(b.el);$browser.data('gep-resize-observer',ro);}

$side.on('click','.gep-explorer-folder',function(){choose(b,$(this).attr('data-id'));
 // On small screens the folder list is a drawer: close it once a folder is chosen.
 if($browser.hasClass('gep-explorer-compact')){$browser.addClass('gep-explorer-collapsed');$toggle.attr('aria-expanded','false');$side.attr('aria-hidden','true');}});
$side.on('click','.gep-explorer-new',function(){form(b,'Crear carpeta',[{type:'text',key:'name',label:'Nombre'},{type:'select',key:'parent',label:'Dentro de',value:/^\d+$/.test(active(b))?Number(active(b)):0,options:folderOpts(null,true)}],function(v,close){if(!v.name.trim())return window.alert('Escribe un nombre.');action(b,'create',v,function(r){close();choose(b,r.data.id);});});});
$side.on('click','.gep-explorer-rename',function(){var id=active(b),f=folders.find(function(x){return String(x.id)===id;});if(!f)return;form(b,'Renombrar carpeta',[{type:'text',key:'name',label:'Nombre',value:f.name}],function(v,close){if(!v.name.trim())return window.alert('Escribe un nombre.');action(b,'rename',{id:id,name:v.name},function(){close();});});});
$side.on('click','.gep-explorer-reparent',function(){var id=active(b);if(!/^\d+$/.test(id))return;form(b,'Mover carpeta',[{type:'select',key:'parent',label:'Nueva ubicación',value:0,options:folderOpts(Number(id),true)}],function(v,close){action(b,'reparent',{id:id,parent:v.parent},function(){close();});});});
$side.on('click','.gep-explorer-delete',function(){var id=active(b);if(!/^\d+$/.test(id))return;if(!window.confirm('¿Eliminar la carpeta? No se borrarán imágenes ni otros archivos. Las subcarpetas se conservan.'))return;action(b,'delete',{id:id},function(){choose(b,'all');});});
$side.on('click','.gep-explorer-multi',function(){setMulti(b,!isMulti(b));});
(function(){var s=selection(b);if(s&&s.on)s.on('add remove reset',function(){render(b);});})();
$side.on('click','.gep-explorer-move',function(){var ids=selectedIds(b);if(!ids.length)return window.alert('Selecciona uno o varios archivos primero.');form(b,'Mover '+ids.length+' archivo(s)',[{type:'select',key:'folder',label:'Carpeta de destino',options:folderOpts(null,false),value:0}],function(v,close){action(b,'move',{ids:ids,folder:v.folder},function(){close();if(isMulti(b)){var s=selection(b);if(s)s.reset();}if(b.collection&&b.collection._requery)b.collection._requery(true);render(b);});});});
$browser.on('click','.attachment',function(){window.setTimeout(function(){render(b);},0);});
if(GEP_MEDIA.getCurrent()&&active(b)==='all'){b.collection.props.set('gep_folder',GEP_MEDIA.getCurrent());}
render(b);
refresh();
window.setTimeout(function(){$(window).trigger('resize');},40);
}
var original=wp.media.view.AttachmentsBrowser.prototype.createToolbar;
wp.media.view.AttachmentsBrowser.prototype.createToolbar=function(){original.apply(this,arguments);if(browsers.indexOf(this)<0)browsers.push(this);var b=this;window.setTimeout(function(){mount(b);},0);};
// Uploads carry the open folder; the server files them there (GEP_Media_Organizer::assign_upload).
if(wp.Uploader&&wp.Uploader.prototype){var init=wp.Uploader.prototype.init;wp.Uploader.prototype.init=function(){if(init)init.apply(this,arguments);if(this.uploader&&this.uploader.bind){this.uploader.bind('BeforeUpload',function(up){up.settings.multipart_params=up.settings.multipart_params||{};up.settings.multipart_params.gep_folder=GEP_MEDIA.getCurrent();});}};}
// WordPress does not add new uploads to a grid filtered by a custom key, so reload folder views once uploads finish.
if(wp.Uploader&&wp.Uploader.queue){wp.Uploader.queue.on('reset',function(){refresh().done(function(){browsers.forEach(function(b){if(/^\d+$/.test(active(b))&&b.collection&&b.collection._requery)b.collection._requery(true);});});});}
// The "Carpeta GEP" field in the attachment details saves through WordPress; refresh counts and the grid afterwards.
$(document).ajaxSuccess(function(e,xhr,settings){var d=settings&&typeof settings.data==='string'?settings.data:'';if(d.indexOf('action=save-attachment-compat')<0||d.indexOf('gep_folder')<0)return;refresh().done(function(){browsers.forEach(function(b){if(b.collection&&b.collection._requery&&active(b)!=='all')b.collection._requery(true);});$(document).trigger('gep-media-folders-changed');});});
// Only mount new pickers. Re-drawing the folder list on every DOM change looped forever (the redraw is itself a DOM change)
// and replaced the buttons between mousedown and mouseup, so the first click on a folder was often lost.
$(function(){var scheduled=false;var observer=new MutationObserver(function(list){if(scheduled)return;if(list.every(function(m){return $(m.target).closest('.gep-explorer').length;}))return;scheduled=true;window.setTimeout(function(){scheduled=false;browsers.forEach(mount);},180);});observer.observe(document.body,{childList:true,subtree:true});});
})(jQuery);
