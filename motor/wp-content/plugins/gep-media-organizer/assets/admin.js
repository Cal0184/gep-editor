/* global GEP_MEDIA, wp */
(function($){
    'use strict';
    var root = $('#gep-media-organizer'); if (!root.length) return;
    var folder = 'all', folders=[], items=[], selected=new Set(), page=1, hasMore=false, busy=false, searchTimer=null;
    var $tree=$('#gep-folders'), $items=$('#gep-items'), $notice=$('#gep-notice');
    function api(action, data){ return $.ajax({url:GEP_MEDIA.ajax, method:'POST', data:$.extend({action:'gep_media_'+action,nonce:GEP_MEDIA.nonce},data||{})}); }
    function error(xhr) { var message=xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message; notify(message || 'No se pudo completar la operación.',true); }
    function notify(msg,isError){ $notice.text(msg).toggleClass('is-error',!!isError); }
    function clean(s){return $('<span>').text(s||'').html();}
    function tree(){
        var byParent={}; folders.forEach(function(f){(byParent[f.parent]||(byParent[f.parent]=[])).push(f);});
        function branch(parent,depth){ var out=''; (byParent[parent]||[]).forEach(function(f){out+='<button type="button" role="treeitem" class="gep-folder '+(folder===String(f.id)?'active':'')+'" data-folder="'+f.id+'" style="padding-left:'+(12+depth*16)+'px"><span class="dashicons dashicons-category"></span><span class="gep-folder-name">'+clean(f.name)+'</span><small>'+f.count+'</small></button>';out+=branch(f.id,depth+1);});return out; }
        $tree.html('<button type="button" class="gep-folder '+(folder==='all'?'active':'')+'" data-folder="all">Todos los archivos</button><button type="button" class="gep-folder '+(folder==='unassigned'?'active':'')+'" data-folder="unassigned">Sin clasificar</button>'+branch(0,0));
        $('#gep-rename-folder,#gep-reparent-folder,#gep-delete-folder').prop('disabled',folder==='all'||folder==='unassigned');
        if(GEP_MEDIA.setCurrent)GEP_MEDIA.setCurrent(folder);
        var current=folders.find(function(f){return String(f.id)===folder;});
        $('#gep-active-title').text(current?current.name:(folder==='unassigned'?'Sin clasificar':'Todos los archivos'));
    }
    function getFolders(){return api('folders').done(function(r){if(r.success){folders=r.data.folders;GEP_MEDIA.folders=folders;tree();}}).fail(error);}
    function render(append){
        if(!append) $items.empty();
        if(!items.length && !append) $items.html('<p class="gep-empty">No se encontraron archivos en esta carpeta.</p>');
        var frag=document.createDocumentFragment();
        items.forEach(function(a){var tile=document.createElement('button');tile.type='button';tile.className='gep-item'+(selected.has(a.id)?' selected':'');tile.setAttribute('data-id',a.id);tile.setAttribute('draggable','true');tile.setAttribute('aria-pressed',selected.has(a.id)?'true':'false');tile.innerHTML='<span class="gep-check">✓</span><img src="'+clean(a.thumb)+'" alt=""><span class="gep-item-name">'+clean(a.title||('Archivo #'+a.id))+'</span>';frag.appendChild(tile);});
        $items.append(frag);$('#gep-load-more').prop('hidden',!hasMore);bulk();
    }
    function load(append){if(busy)return;busy=true; if(!append){page=1;selected.clear(); $items.html('<p class="gep-empty">Cargando archivos…</p>');}notify('');
        api('items',{folder:folder,search:$('#gep-search').val(),page:page}).done(function(r){if(!r.success)return;items=r.data.items;hasMore=r.data.hasMore;$('#gep-total').text(r.data.total+' archivos');render(append);}).fail(error).always(function(){busy=false;});
    }
    function bulk(){ $('#gep-bulk').prop('hidden',!selected.size);$('#gep-selected-count').text(selected.size+' seleccionados'); }
    function move(ids,target){if(!ids.length)return;api('move',{ids:ids,folder:target}).done(function(r){if(!r.success)return;selected.clear();getFolders();load(false);notify(r.data.moved+' archivos organizados.'+(r.data.skipped?' '+r.data.skipped+' omitidos.':''),!!r.data.skipped);}).fail(error);}
    function folderChoice(exclude,rootLabel){var options=['<option value="0">'+(rootLabel||'Sin clasificar')+'</option>'];function add(parent,depth){folders.filter(function(f){return f.parent===parent&&f.id!==exclude;}).forEach(function(f){options.push('<option value="'+f.id+'">'+Array(depth+1).join('— ')+clean(f.name)+'</option>');add(f.id,depth+1);});}add(0,0);return options.join('');}
    function movePrompt(){var ids=Array.from(selected);if(!ids.length)return;var html='<div class="gep-choice"><label for="gep-target">Carpeta de destino</label><select id="gep-target">'+folderChoice()+'</select><button class="button button-primary" id="gep-confirm-move">Mover '+ids.length+' archivos</button><button class="button" id="gep-cancel-move">Cancelar</button></div>';$('#gep-bulk').append(html);$('#gep-move-selected').prop('disabled',true);}
    $tree.on('click','.gep-folder',function(){folder=String($(this).data('folder'));tree();load(false);});
    $('#gep-new-folder').on('click',function(){var name=prompt('Nombre de la nueva carpeta:');if(name===null || !name.trim())return;var parent=/^\d+$/.test(folder)?Number(folder):0;api('create',{name:name.trim(),parent:parent}).done(function(r){if(r.success){folder=String(r.data.id);getFolders();load(false);notify('Carpeta creada.');}}).fail(error);});
    $('#gep-rename-folder').on('click',function(){var f=folders.find(function(x){return String(x.id)===folder;});if(!f)return;var name=prompt('Nuevo nombre:',f.name);if(name===null||!name.trim())return;api('rename',{id:f.id,name:name.trim()}).done(function(r){if(r.success){getFolders();notify('Carpeta renombrada.');}}).fail(error);});
    $('#gep-reparent-folder').on('click',function(){var f=folders.find(function(x){return String(x.id)===folder;});if(!f||$('.gep-reparent-choice').length)return;var html='<div class="gep-choice gep-reparent-choice"><label for="gep-parent-target">Mover «'+clean(f.name)+'» a</label><select id="gep-parent-target">'+folderChoice(f.id,'Nivel principal')+'</select><button class="button button-primary" id="gep-confirm-reparent">Mover carpeta</button><button class="button" id="gep-cancel-reparent">Cancelar</button></div>';$('.gep-folder-actions').after(html);$('#gep-parent-target').val(String(f.parent));});
    root.on('click','#gep-cancel-reparent',function(){$('.gep-reparent-choice').remove();});
    root.on('click','#gep-confirm-reparent',function(){var parent=Number($('#gep-parent-target').val());$('.gep-reparent-choice').remove();api('reparent',{id:folder,parent:parent}).done(function(r){if(r.success){getFolders();notify('Carpeta movida.');}}).fail(error);});
    // Ordenar fotos del GEP: first show what would change, then apply on confirmation.
    function autoGroups(groups){return Object.keys(groups).map(function(k){return '<li><span>'+clean(k)+'</span><strong>'+groups[k]+'</strong></li>';}).join('');}
    $('#gep-auto-preview').on('click',function(){var $btn=$(this).prop('disabled',true),$out=$('#gep-auto-result').prop('hidden',false).text('Revisando las páginas del GEP…');
        api('auto').done(function(r){if(!r.success)return;if(!r.data.total){$out.html('<p>No hay fotos del GEP en Sin clasificar. Todo está ordenado.</p>');return;}
            $out.html('<p>Se ordenarán <strong>'+r.data.total+'</strong> archivos en la carpeta GEP:</p><ul>'+autoGroups(r.data.groups)+'</ul><button type="button" class="button button-primary" id="gep-auto-apply">Ordenar ahora</button>');
        }).fail(function(x){$out.prop('hidden',true);error(x);}).always(function(){$btn.prop('disabled',false);});});
    root.on('click','#gep-auto-apply',function(){var $btn=$(this).prop('disabled',true).text('Ordenando…');api('auto',{apply:1}).done(function(r){if(!r.success)return;$('#gep-auto-result').html('<p>Listo: '+r.data.moved+' archivos ordenados. Puedes moverlos a mano si alguno quedó en otra carpeta.</p>');getFolders();load(false);notify(r.data.moved+' archivos ordenados.');}).fail(function(x){$btn.prop('disabled',false).text('Ordenar ahora');error(x);});});
    $('#gep-delete-folder').on('click',function(){if(!/^\d+$/.test(folder))return;if(!confirm('¿Eliminar esta carpeta virtual? Los archivos NO se borrarán. Las subcarpetas pasarán al nivel superior.'))return;api('delete',{id:folder}).done(function(r){if(r.success){folder='all';getFolders();load(false);notify('Carpeta eliminada. Los archivos permanecen en WordPress.');}}).fail(error);});
    $items.on('click','.gep-item',function(){var id=Number($(this).data('id'));if(selected.has(id)){selected.delete(id);}else{selected.add(id);}$(this).toggleClass('selected',selected.has(id)).attr('aria-pressed',selected.has(id));bulk();});
    $items.on('dragstart','.gep-item',function(e){var id=Number($(this).data('id'));if(!selected.has(id)){selected.clear();selected.add(id);}e.originalEvent.dataTransfer.setData('text/plain',Array.from(selected).join(','));e.originalEvent.dataTransfer.effectAllowed='move';bulk();});
    $tree.on('dragover','.gep-folder',function(e){e.preventDefault();e.originalEvent.dataTransfer.dropEffect='move';$(this).addClass('drop-here');}).on('dragleave','.gep-folder',function(){$(this).removeClass('drop-here');}).on('drop','.gep-folder',function(e){e.preventDefault();$(this).removeClass('drop-here');var ids=(e.originalEvent.dataTransfer.getData('text/plain')||'').split(',').map(Number).filter(Boolean);var target=String($(this).data('folder'));if(target==='all')return;move(ids,target==='unassigned'?0:Number(target));});
    $('#gep-clear-selection').on('click',function(){selected.clear();$items.find('.selected').removeClass('selected').attr('aria-pressed','false');bulk();});
    $('#gep-move-selected').on('click',movePrompt);
    root.on('click','#gep-cancel-move',function(){$('.gep-choice').remove();$('#gep-move-selected').prop('disabled',false);});
    root.on('click','#gep-confirm-move',function(){var target=Number($('#gep-target').val());$('.gep-choice').remove();$('#gep-move-selected').prop('disabled',false);move(Array.from(selected),target);});
    $('#gep-search').on('input',function(){clearTimeout(searchTimer);searchTimer=setTimeout(function(){load(false);},300);});
    $('#gep-load-more').on('click',function(){if(busy)return;page++;load(true);});
    $('#gep-add-media').on('click',function(){if(GEP_MEDIA.setCurrent)GEP_MEDIA.setCurrent(folder);var frame=wp.media({title:'Subir o elegir archivos',button:{text:'Listo'},multiple:true});frame.on('close',function(){if(GEP_MEDIA.setCurrent)GEP_MEDIA.setCurrent(folder);getFolders();load(false);});frame.open();});
    getFolders();load(false);
})(jQuery);
