/* GEP Editor v9.23.0 (aviso de estado v9.26.0) · Crea NOMBRE-respaldo.jpg (primer cuadro) para cada video
 * de cabecera que no la tenga. Corre en segundo plano al abrir el editor y al
 * elegir otro video. No cambia nada de lo que se guarda en la página. */
(function($){
    'use strict';
    var data = window.gepPosterData;
    if (!data || !window.HTMLCanvasElement) return;
    var done = {};

    function videoUrls(){
        var list = [];
        $('input[name$="hero_video"], input[name$="[hero_video]"]').each(function(){
            var v = $.trim($(this).val() || '');
            if (v && /\.(mp4|m4v|mov|webm)(\?|#|$)/i.test(v) && !done[v]) list.push(v);
        });
        return list;
    }

    function grabFrame(path){
        return new Promise(function(resolve, reject){
            var video = document.createElement('video'), finished = false;
            var timer = setTimeout(function(){ fail('tiempo'); }, 45000);
            function cleanup(){ clearTimeout(timer); video.removeAttribute('src'); try { video.load(); } catch (e) {} }
            function fail(why){ if (finished) return; finished = true; cleanup(); reject(why); }
            video.muted = true; video.playsInline = true; video.preload = 'auto';
            video.addEventListener('error', function(){ fail('error'); });
            video.addEventListener('loadeddata', function(){
                // Un poco después del inicio: el cuadro 0 a veces viene negro.
                try { video.currentTime = Math.min(0.1, (video.duration || 1) / 2); } catch (e) { fail('seek'); }
            });
            video.addEventListener('seeked', function(){
                if (finished || !video.videoWidth) return;
                var w = Math.min(1280, video.videoWidth), h = Math.round(video.videoHeight * w / video.videoWidth);
                var canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                try {
                    canvas.getContext('2d').drawImage(video, 0, 0, w, h);
                    canvas.toBlob(function(blob){
                        if (finished) return; finished = true; cleanup();
                        blob ? resolve(blob) : reject('blob');
                    }, 'image/jpeg', 0.8);
                } catch (e) { fail('canvas'); }
            });
            video.src = path;
        });
    }

    function upload(item, blob){
        var fd = new FormData();
        fd.append('action', 'gep_poster_upload');
        fd.append('nonce', data.nonce);
        fd.append('url', item.url);
        fd.append('poster', blob, 'respaldo.jpg');
        return $.ajax({ url: data.ajaxUrl, method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' });
    }

    /* v9.26.0 · aviso corto bajo cada selector de video de cabecera: dice si el
     * video ya tiene imagen de respaldo (lo que evita la cabecera vacía o
     * verde mientras el video empieza) y si hay versión para teléfono. */
    var states = {};
    function statusText(url){
        if (/youtu\.?be|vimeo\.com/i.test(url)) return {cls: 'is-ok', text: /vimeo/i.test(url) ? 'Vimeo: mientras empieza se muestra la imagen de cabecera, si eliges una.' : 'YouTube: mientras empieza se muestra la miniatura del video.'};
        var st = states[url];
        if (!st) return null;
        if (st.poster === 'ok') return {cls: 'is-ok', text: 'Imagen de respaldo lista: la cabecera se ve completa desde el primer instante.' + (st.mobile ? ' Tiene versión para teléfono.' : '')};
        if (st.poster === 'creating') return {cls: 'is-wait', text: 'Creando la imagen de respaldo del video…'};
        if (st.poster === 'external') return {cls: 'is-warn', text: 'Video fuera de la Mediateca: elige también una imagen de cabecera para que no se vea vacía mientras carga.'};
        return {cls: 'is-warn', text: 'Falta la imagen de respaldo. Elige una imagen de cabecera o abre el editor en Chrome para crearla sola.'};
    }
    function renderStatus(){
        $('input[name$="hero_video"], input[name$="[hero_video]"]').each(function(){
            var $in = $(this), url = $.trim($in.val() || ''), $ctl = $in.closest('.gep-video-control');
            if (!$ctl.length) return;
            var $st = $ctl.next('.gep-video-status'), info = url ? statusText(url) : null;
            if (!info) { $st.remove(); return; }
            if (!$st.length) $st = $('<p class="gep-video-status" aria-live="polite"></p>').insertAfter($ctl);
            $st.attr('class', 'gep-video-status ' + info.cls).text(info.text);
        });
    }
    window.gepVideoStatusRefresh = renderStatus;

    function run(){
        renderStatus();
        var urls = videoUrls();
        if (!urls.length) return;
        $.each(urls, function(_, u){ done[u] = true; });
        $.post(data.ajaxUrl, { action: 'gep_poster_status', nonce: data.nonce, urls: urls }, null, 'json').done(function(res){
            if (res && res.success && res.data.states) { $.each(res.data.states, function(_, st){ states[st.url] = st; }); $.each(res.data.missing || [], function(_, m){ if (states[m.url]) states[m.url].poster = 'creating'; }); renderStatus(); }
            if (!res || !res.success || !res.data.missing.length) return;
            // Uno a la vez para no cargar el servidor ni la conexión.
            var queue = res.data.missing.slice();
            (function next(){
                var item = queue.shift();
                if (!item) return;
                grabFrame(item.path).then(function(blob){ return upload(item, blob); })
                    .then(function(r){ if (states[item.url]) states[item.url].poster = r && r.success ? 'ok' : 'missing'; renderStatus(); next(); }, function(){ if (states[item.url]) states[item.url].poster = 'missing'; renderStatus(); next(); });
            })();
        });
    }

    $(function(){ setTimeout(run, 2500); });
    $(document).on('change', 'input[name$="hero_video"], input[name$="[hero_video]"]', function(){ setTimeout(run, 500); renderStatus(); });
    // Al elegir un video en la Mediateca el campo cambia sin aviso: se revisa cada 10 s.
    setInterval(run, 10000);
})(jQuery);
